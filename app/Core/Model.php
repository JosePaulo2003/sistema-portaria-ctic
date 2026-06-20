<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

// Modelo base com operacoes CRUD simples usadas pelos models especificos.
abstract class Model
{
    protected string $table;

    protected function db(): PDO
    {
        return Database::pdo();
    }

    public function all(string $orderBy = 'id DESC'): array
    {
        return $this->db()->query("SELECT * FROM {$this->table} ORDER BY {$orderBy}")->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db()->prepare("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn (string $column): string => ':' . $column, $columns);
        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $this->table, implode(',', $columns), implode(',', $placeholders));
        $stmt = $this->db()->prepare($sql);
        foreach ($data as $column => $value) {
            $stmt->bindValue(':' . $column, $value);
        }
        $stmt->execute();
        return (int) $this->db()->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        if (!$data) {
            return true;
        }
        $assignments = array_map(fn (string $column): string => "{$column} = :{$column}", array_keys($data));
        $stmt = $this->db()->prepare("UPDATE {$this->table} SET " . implode(', ', $assignments) . ' WHERE id = :id');
        foreach ($data as $column => $value) {
            $stmt->bindValue(':' . $column, $value);
        }
        $stmt->bindValue(':id', $id);
        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db()->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
