<?php
namespace App\Classes;

use App\Interfaces\MosaicRepositoryInterface;
use App\Facades\DB;

class MosaicRepository implements MosaicRepositoryInterface
{

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
        if (!empty($filters['series'])) {
            $where[] = 'series = :series';
            $params['series'] = $filters['series'];
        }
        if (!empty($filters['condition'])) {
            $where[] = 'item_condition = :condition';
            $params['condition'] = $filters['condition'];
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

    public function save(array $data): bool
    {
        $sql = "INSERT INTO mosaics (title, type, series, issue_number, availability, item_condition, release_year, release_month, description, image_path)
                VALUES (:title, :type, :series, :issue_number, :availability, :item_condition, :release_year, :release_month, :description, :image_path)";

        $stmt = DB::query($sql, [
            'title' => $data['title'],
            'type' => $data['type'] ?? 'heft',
            'series' => $data['series'] ?? 'abrafaxe',
            'issue_number' => $this->normalizeIssueNumber($data['issue_number'] ?? null),
            'availability' => $data['availability'] ?? 'vorhanden',
            'item_condition' => $data['item_condition'] ?? 'sehr_gut',
            'release_year' => (int) $data['release_year'],
            'release_month' => (int) $data['release_month'],
            'description' => $data['description'] ?? null,
            'image_path' => $data['image_path']
        ]);

        return $stmt ? true : false;
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE mosaics SET
                    title = :title,
                    type = :type,
                    series = :series,
                    issue_number = :issue_number,
                    availability = :availability,
                    item_condition = :item_condition,
                    release_year = :release_year,
                    release_month = :release_month,
                    description = :description,
                    image_path = :image_path
                WHERE id = :id";

        $stmt = DB::query($sql, [
            'title' => $data['title'],
            'type' => $data['type'] ?? 'heft',
            'series' => $data['series'] ?? 'abrafaxe',
            'issue_number' => $this->normalizeIssueNumber($data['issue_number'] ?? null),
            'availability' => $data['availability'] ?? 'vorhanden',
            'item_condition' => $data['item_condition'] ?? 'sehr_gut',
            'release_year' => (int) $data['release_year'],
            'release_month' => (int) $data['release_month'],
            'description' => $data['description'] ?? null,
            'image_path' => $data['image_path'],
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
}
