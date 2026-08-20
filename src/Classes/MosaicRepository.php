<?php
namespace App\Classes;

use App\Interfaces\MosaicRepositoryInterface;
use App\Facades\DB;

class MosaicRepository implements MosaicRepositoryInterface
{

    public function getRandom(int $limit = 6): array
    {
        $limit = max(1, min(100, (int) $limit));
        return DB::query("SELECT * FROM mosaics ORDER BY RAND() LIMIT $limit")->fetchAll();
    }

    public function getAllSorted(string $order = 'ASC'): array
    {
        return $this->getFiltered([], $order);
    }

    public function getFiltered(array $filters = [], string $order = 'ASC'): array
    {
        $direction = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        $where = [];
        $params = [];

        if (!empty($filters['year'])) {
            $where[] = 'release_year = :year';
            $params['year'] = (int) $filters['year'];
        }
        if (!empty($filters['release_year'])) {
            $where[] = 'release_year = :release_year';
            $params['release_year'] = (int) $filters['release_year'];
        }
        if (!empty($filters['category'])) {
            $where[] = 'category = :category';
            $params['category'] = $filters['category'];
        }
        if (!empty($filters['type'])) {
            $where[] = 'type = :type';
            $params['type'] = $filters['type'];
        }
        if (!empty($filters['title'])) {
            $where[] = 'title = :title';
            $params['title'] = $filters['title'];
        }
        if (isset($filters['issue_number']) && $filters['issue_number'] !== '' && $filters['issue_number'] !== null) {
            $where[] = 'issue_number = :issue_number';
            $params['issue_number'] = (int) $filters['issue_number'];
        }
        if (!empty($filters['main_serie'])) {
            $where[] = 'main_serie = :main_serie';
            $params['main_serie'] = $filters['main_serie'];
        }
        if (!empty($filters['serie'])) {
            $where[] = 'serie = :serie';
            $params['serie'] = $filters['serie'];
        }
        if (!empty($filters['availability'])) {
            $where[] = 'availability = :availability';
            $params['availability'] = $filters['availability'];
        }
        if (!empty($filters['condition'])) {
            $where[] = 'item_condition = :condition';
            $params['condition'] = $filters['condition'];
        }
        if (!empty($filters['item_condition'])) {
            $where[] = 'item_condition = :item_condition';
            $params['item_condition'] = $filters['item_condition'];
        }

        $sql = "SELECT * FROM mosaics";
        if ($where) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY release_year $direction, release_month $direction";

        return DB::query($sql, $params)->fetchAll();
    }

    public function getDistinctYears(): array
    {
        $rows = DB::query("SELECT DISTINCT release_year FROM mosaics ORDER BY release_year DESC")->fetchAll();
        return array_map(static fn($row) => (int) $row['release_year'], $rows);
    }

    public function find(int $id): ?array
    {
        $stmt = DB::query("SELECT * FROM mosaics WHERE id = :id", ['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findByUuid(string $uuid): ?array
    {
        $uuid = trim($uuid);
        if ($uuid === '') {
            return null;
        }
        $stmt = DB::query("SELECT * FROM mosaics WHERE uuid = :uuid", ['uuid' => $uuid]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function save(array $data): bool
    {
        $uuid = $data['uuid'] ?? bin2hex(random_bytes(16));

        $sql = "INSERT INTO mosaics (uuid, type, category, title, issue_number, main_serie, serie, availability, item_condition, release_year, release_month, description, image_path, image_path_current_condition)
                VALUES (:uuid, :type, :category, :title, :issue_number, :main_serie, :serie, :availability, :item_condition, :release_year, :release_month, :description, :image_path, :image_path_current_condition)";

        $stmt = DB::query($sql, [
            'uuid' => $uuid,
            'type' => $data['type'] ?? 'heft',
            'category' => $data['category'] ?? 'Abrafaxe',
            'title' => $data['title'],
            'issue_number' => $this->normalizeIssueNumber($data['issue_number'] ?? null),
            'main_serie' => $data['main_serie'] ?? null,
            'serie' => $data['serie'] ?? null,
            'availability' => $data['availability'] ?? 'vorhanden',
            'item_condition' => $data['item_condition'] ?? 'sehr_gut',
            'release_year' => (int) $data['release_year'],
            'release_month' => (int) $data['release_month'],
            'description' => $data['description'] ?? null,
            'image_path' => $data['image_path'] ?? null,
            'image_path_current_condition' => $this->normalizeConditionImagePaths($data['image_path_current_condition'] ?? null)
        ]);

        return $stmt ? true : false;
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE mosaics SET
                    uuid = :uuid,
                    type = :type,
                    category = :category,
                    title = :title,
                    issue_number = :issue_number,
                    main_serie = :main_serie,
                    serie = :serie,
                    availability = :availability,
                    item_condition = :item_condition,
                    release_year = :release_year,
                    release_month = :release_month,
                    description = :description,
                    image_path = :image_path,
                    image_path_current_condition = :image_path_current_condition
                WHERE id = :id";

        $stmt = DB::query($sql, [
            // keep existing uuid unless explicitly set; admin passes existing one
            'uuid' => $data['uuid'] ?? bin2hex(random_bytes(16)),
            'type' => $data['type'] ?? 'heft',
            'category' => $data['category'] ?? 'Abrafaxe',
            'title' => $data['title'],
            'issue_number' => $this->normalizeIssueNumber($data['issue_number'] ?? null),
            'main_serie' => $data['main_serie'] ?? null,
            'serie' => $data['serie'] ?? null,
            'availability' => $data['availability'] ?? 'vorhanden',
            'item_condition' => $data['item_condition'] ?? 'sehr_gut',
            'release_year' => (int) $data['release_year'],
            'release_month' => (int) $data['release_month'],
            'description' => $data['description'] ?? null,
            'image_path' => $data['image_path'] ?? null,
            'image_path_current_condition' => $this->normalizeConditionImagePaths($data['image_path_current_condition'] ?? null),
            'id' => $id
        ]);

        return $stmt ? true : false;
    }

    public function delete(int $id): bool
    {
        $stmt = DB::query("DELETE FROM mosaics WHERE id = :id", ['id' => $id]);
        return $stmt ? true : false;
    }

    private function normalizeIssueNumber($value): ?int
    {
        return ($value !== null && $value !== '') ? (int) $value : null;
    }

    private function normalizeConditionImagePaths($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Accept either a single path string or an array of path strings.
        if (is_array($value)) {
            $paths = array_values(array_filter(array_map('strval', $value), static fn($p) => $p !== ''));
            return $paths ? json_encode($paths, JSON_UNESCAPED_SLASHES) : null;
        }

        return (string) $value;
    }
}
