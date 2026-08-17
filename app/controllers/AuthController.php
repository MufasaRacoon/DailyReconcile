<?php

class AuthController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function isBlocked(string $username): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT attempts, last_attempt
            FROM login_attempts
            WHERE username = ?
        ");
        $stmt->execute([$username]);
        $row = $stmt->fetch();

        if (!$row) return false;

        if ($row['attempts'] >= 5 && strtotime($row['last_attempt']) > strtotime('-15 minutes')) {
            return true;
        }

        return false;
    }

    public function login(string $username, string $password)
    {
        $stmt = $this->pdo->prepare("
            SELECT id, username, password_hash, role, is_active
            FROM users
            WHERE username = ?
            LIMIT 1
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Même message d'erreur pour éviter l'énumération
        if (!$user) {
            return "Nom d'utilisateur incorrect.";
        }

        if (!$user['is_active']) {
            return "Compte désactivé.";
        }

        if ($this->isBlocked($username)) {
            return "Compte temporairement bloqué. Réessayez plus tard.";
        }

        if (!password_verify($password, $user['password_hash'])) {
            $stmt = $this->pdo->prepare("
                INSERT INTO login_attempts (username, ip_address)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE
                    attempts = attempts + 1,
                    last_attempt = NOW()
            ");
            $stmt->execute([$username, $_SERVER['REMOTE_ADDR']]);

            return "Mot de passe incorrect.";
        }

        // Sécurité session
        session_regenerate_id(true);
        $this->pdo->prepare("DELETE FROM login_attempts WHERE username = ?")
             ->execute([$username]);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        header('Location: index.php');
        exit;
    }
}
