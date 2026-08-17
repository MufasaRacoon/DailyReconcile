<?php
require_once __DIR__ . '/../../app/config/auth.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/controllers/UserController.php';
requireAuthMenu();
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$controller = new \app\Controllers\UserController($pdo);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Action non autorisée (CSRF)';
    } elseif (isset($_POST['create_user'])) {
        $message = $controller->createUser(
            $_POST['username'],
            $_POST['password'],
            $_POST['role']
        ) ?: 'Utilisateur créé.';
    } elseif (isset($_POST['update_user'])) {
        $pass = $_POST['password'] ?? '';
        $message = $controller->updateUser(
            (int)$_POST['id'],
            $_POST['username'],
            $pass ?: null,
            $_POST['role'],
            (int)($_POST['is_active'] ?? 1)
        ) ?: 'Utilisateur mis à jour.';
    }
}

$users = $controller->listUsers();
$token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Utilisateurs | Daily Production AMCHO</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Variables CSS Système Premium */
        :root {
            --bg-main: #0b0f19;
            --bg-gradient: radial-gradient(circle at 50% 0%, #1e2640 0%, #0b0f19 70%);
            --glass: rgba(17, 24, 39, 0.7);
            --glass-hover: rgba(24, 32, 51, 0.85);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text-main: #f3f4f6;
            --text-dim: #9ca3af;
            --primary: #3b82f6;
            --primary-glow: rgba(59, 130, 246, 0.3);
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }

        body { 
            background: var(--bg-main); 
            background-image: var(--bg-gradient);
            color: var(--text-main); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            margin: 0; 
            padding: 0; 
            min-height: 100vh;
        }

        .container { 
            max-width: 1100px; 
            margin: 0 auto; 
            padding: 40px 25px; 
        }

        /* Barre supérieure */
        .back-bar { 
            display: flex; 
            align-items: center; 
            justify-content: space-between;
            margin-bottom: 35px; 
        }

        .back-title {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .back-bar a { 
            color: var(--text-dim); 
            text-decoration: none; 
            font-weight: 600; 
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        .back-bar a:hover { 
            color: #fff;
            background: rgba(255, 255, 255, 0.08);
            transform: translateX(-3px);
        }

        h1 { 
            margin: 0; 
            font-size: 1.6rem; 
            font-weight: 800; 
            letter-spacing: -0.025em;
            background: linear-gradient(to right, #fff, var(--text-dim));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Alertes et Messages */
        .msg { 
            padding: 14px 20px; 
            border-radius: 14px; 
            font-size: 0.85rem; 
            margin-bottom: 25px; 
            background: rgba(16, 185, 129, 0.12); 
            border: 1px solid rgba(16, 185, 129, 0.25);
            color: var(--success); 
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .msg-error { 
            background: rgba(239, 68, 68, 0.12); 
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: var(--danger); 
        }

        /* Cartes & Formulaires */
        .card { 
            background: var(--glass); 
            backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border); 
            border-radius: 20px; 
            padding: 24px; 
            margin-bottom: 30px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .card h3 { 
            margin: 0 0 20px; 
            font-size: 1.05rem; 
            font-weight: 700;
            color: #fff;
        }

        .flex-row { 
            display: flex; 
            gap: 16px; 
            align-items: flex-end; 
            flex-wrap: wrap; 
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        .flex-row label { 
            display: block; 
            font-size: 0.75rem; 
            font-weight: 600;
            color: var(--text-dim); 
            margin-bottom: 8px; 
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .flex-row input, .flex-row select { 
            width: 100%;
            box-sizing: border-box;
            background: rgba(0, 0, 0, 0.25); 
            border: 1px solid var(--glass-border); 
            border-radius: 10px; 
            padding: 11px 14px; 
            color: #fff; 
            font-size: 0.85rem; 
            transition: all 0.2s ease;
        }

        .flex-row input:focus, .flex-row select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-glow);
        }

        .btn-primary { 
            background: var(--primary); 
            color: #fff; 
            border: none; 
            border-radius: 10px; 
            padding: 11px 24px; 
            font-weight: 700; 
            font-size: 0.85rem;
            cursor: pointer; 
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            height: 41px;
        }

        .btn-primary:hover { 
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
            opacity: 0.95;
        }

        /* Tableaux Haute Finition */
        .table-container {
            background: var(--glass);
            backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 0.85rem; 
        }

        th, td { 
            text-align: left; 
            padding: 16px 20px; 
        }

        th { 
            background: rgba(255, 255, 255, 0.02);
            color: var(--text-dim); 
            font-weight: 700; 
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--glass-border);
        }

        tr {
            transition: background 0.2s ease;
        }

        tr:not(:last-child) td {
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }

        tr:hover td { 
            background: rgba(255, 255, 255, 0.02); 
        }

        .td-id {
            font-family: 'JetBrains Mono', monospace;
            color: var(--text-dim);
            font-weight: 700;
        }

        .td-username {
            font-weight: 600;
            color: #fff;
        }

        /* Badges modernisés */
        .badge { 
            display: inline-flex; 
            align-items: center;
            gap: 5px;
            padding: 4px 10px; 
            border-radius: 8px; 
            font-size: 0.7rem; 
            font-weight: 700; 
            text-transform: uppercase; 
            letter-spacing: 0.03em;
        }
        
        .badge::before {
            content: '';
            width: 5px;
            height: 5px;
            border-radius: 50%;
            display: inline-block;
        }

        .badge-success { background: rgba(16, 185, 129, 0.12); color: #10b981; }
        .badge-success::before { background: #10b981; }

        .badge-danger { background: rgba(239, 68, 68, 0.12); color: #ef4444; }
        .badge-danger::before { background: #ef4444; }

        .badge-warning { background: rgba(245, 158, 11, 0.12); color: #f59e0b; }
        .badge-warning::before { background: #f59e0b; }
        
        .badge-info { background: rgba(59, 130, 246, 0.12); color: #3b82f6; }
        .badge-info::before { background: #3b82f6; }

        /* Formulaire d'édition en ligne nettoyé */
        .inline-edit-form {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .inline-edit-form input[type="text"],
        .inline-edit-form input[type="password"],
        .inline-edit-form select {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            padding: 6px 10px;
            color: #fff;
            font-size: 0.8rem;
            transition: border 0.2s ease;
        }

        .inline-edit-form input:focus, .inline-edit-form select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .checkbox-container {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            color: var(--text-dim);
            cursor: pointer;
            user-select: none;
        }

        .checkbox-container input {
            cursor: pointer;
            accent-color: var(--primary);
        }

        .btn-save {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            padding: 7px 14px;
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-save:hover {
            background: var(--primary);
            border-color: var(--primary);
            box-shadow: 0 4px 12px var(--primary-glow);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Barre supérieure -->
        <div class="back-bar">
            <div class="back-title">
                <a href="../index.php"><i class="bi bi-arrow-left"></i> Dashboard</a>
                <h1>Gestion des Utilisateurs</h1>
            </div>
        </div>

        <!-- Notification -->
        <?php if (!empty($message)): ?>
        <div class="msg <?= str_contains($message, 'existe') || str_contains($message, 'erreur') ? 'msg-error' : '' ?>">
            <i class="bi <?= str_contains($message, 'existe') || str_contains($message, 'erreur') ? 'bi-exclamation-triangle' : 'bi-check-circle' ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <!-- Formulaire de Création -->
        <div class="card">
            <h3><i class="bi bi-person-plus" style="margin-right: 8px; color: var(--primary);"></i>Créer un nouvel utilisateur</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $token ?>">
                <input type="hidden" name="create_user" value="1">
                <div class="flex-row">
                    <div class="form-group">
                        <label>Nom d'utilisateur</label>
                        <input type="text" name="username" placeholder="ex: j.doe" required>
                    </div>
                    <div class="form-group">
                        <label>Mot de passe</label>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                    <div class="form-group">
                        <label>Rôle Système</label>
                        <select name="role">
                            <option value="operator">Opérateur</option>
                            <option value="supervisor">Superviseur</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary">Créer le compte</button>
                </div>
            </form>
        </div>

        <!-- Liste des utilisateurs -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Utilisateur</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th style="text-align: right; padding-right: 25px;">Actions / Modification rapide</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="td-id">#<?= $u['id'] ?></td>
                        <td class="td-username"><?= htmlspecialchars($u['username']) ?></td>
                        <td>
                            <?php if($u['role'] === 'admin'): ?>
                                <span class="badge badge-warning">Admin</span>
                            <?php elseif($u['role'] === 'supervisor'): ?>
                                <span class="badge badge-info">Superviseur</span>
                            <?php else: ?>
                                <span class="badge badge-success">Opérateur</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= $u['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                                <?= $u['is_active'] ? 'Actif' : 'Inactif' ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <form method="POST" class="inline-edit-form" style="justify-content: flex-end;">
                                <input type="hidden" name="csrf_token" value="<?= $token ?>">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="update_user" value="1">
                                
                                <input type="text" name="username" value="<?= htmlspecialchars($u['username']) ?>" style="width:130px;" title="Modifier le nom">
                                <input type="password" name="password" placeholder="Nouveau MDP" style="width:110px;">
                                
                                <select name="role" style="padding: 6px 8px;">
                                    <option value="operator" <?= $u['role'] === 'operator' ? 'selected' : '' ?>>Opérateur</option>
                                    <option value="supervisor" <?= $u['role'] === 'supervisor' ? 'selected' : '' ?>>Superviseur</option>
                                    <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                                
                                <label class="checkbox-container">
                                    <input type="checkbox" name="is_active" value="1" <?= $u['is_active'] ? 'checked' : '' ?>> Actif
                                </label>
                                
                                <button type="submit" class="btn-save" title="Enregistrer les modifications"><i class="bi bi-check-lg"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
