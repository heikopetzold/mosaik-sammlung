<?php
namespace App\Classes;

use App\Interfaces\MosaicRepositoryInterface;
use App\Facades\DB;

class MosaicRepository implements MosaicRepositoryInterface
{

    public function getAllSorted(string $order = 'ASC'): array
    {
        $direction = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
        $sql = "SELECT * FROM mosaics ORDER BY release_year $direction, release_month $direction";
        return DB::query($sql)->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = DB::query("SELECT * FROM mosaics WHERE id = :id", ['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function save(array $data): bool
    {
        $sql = "INSERT INTO mosaics (title, type, issue_number, release_year, release_month, description, image_path)
                VALUES (:title, :type, :issue_number, :release_year, :release_month, :description, :image_path)";

        $stmt = DB::query($sql, [
            'title' => $data['title'],
            'type' => $data['type'] ?? 'heft',
            'issue_number' => $this->normalizeIssueNumber($data['issue_number'] ?? null),
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
                    issue_number = :issue_number,
                    release_year = :release_year,
                    release_month = :release_month,
                    description = :description,
                    image_path = :image_path
                WHERE id = :id";

        $stmt = DB::query($sql, [
            'title' => $data['title'],
            'type' => $data['type'] ?? 'heft',
            'issue_number' => $this->normalizeIssueNumber($data['issue_number'] ?? null),
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
