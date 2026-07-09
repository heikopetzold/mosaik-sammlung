<?php

namespace App\Interfaces;

interface MosaicRepositoryInterface
{
    public function getAllSorted(string $order): array;
    public function find(int $id): ?array;
    public function save(array $data): bool;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}
