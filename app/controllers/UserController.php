<?php
namespace App\Controllers;

use PDO;
use Exception;


class UserController {

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }    
    
    public function listUsers(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, username, role, is_active, created_at
            FROM users
            ORDER BY username ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getUser(int $id)
    {
        $stmt = $this->pdo->prepare("
            SELECT id, username, role, is_active
            FROM users
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function createUser(string $username, string $password, string $role): string
    {
        $check = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$username]);
        if ($check->fetch()) {
            return "Ce nom d'utilisateur existe déjà.";
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("
            INSERT INTO users (username, password_hash, role, is_active)
            VALUES (?, ?, ?, 1)
        ");
        $stmt->execute([$username, $hash, $role]);
        return '';
    }

    public function updateUser(int $id, string $username, ?string $password, string $role, int $is_active): string
    {
        if ($password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("
                UPDATE users SET username = ?, password_hash = ?, role = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([$username, $hash, $role, $is_active, $id]);
        } else {
            $stmt = $this->pdo->prepare("
                UPDATE users SET username = ?, role = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([$username, $role, $is_active, $id]);
        }
        return '';
    }
}