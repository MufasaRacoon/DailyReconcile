<?php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/config/auth.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Tous les champs sont obligatoires.";
    } else {
        require_once __DIR__ . '/../app/controllers/AuthController.php';
        $auth = new AuthController($pdo);
        $result = $auth->login($username, $password);

        if ($result !== true) {
            $error = $result;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion | Portail Qualité NPD</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- <link rel="stylesheet" href="style.css"> -->
     <style>

        :root {
            --primary-green: #6366f1;
            --accent-green: #4e5eaf;
            --light-bg: #f3f7f5;
            --glass: rgba(255, 255, 255, 0.85);
            --text-dark: #1b4332;
        }
        /* --- CONFIGURATION LOGIN --- */

        .login-body {
            background-color: #f3f0f4;
            background-image: 
                radial-gradient(circle at 20% 20%, rgba(82, 183, 136, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 80% 80%, rgba(45, 106, 79, 0.1) 0%, transparent 40%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
            z-index: 10;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid white;
            border-radius: 32px;
            padding: 40px;
            box-shadow: 0 25px 50px rgba(27, 67, 50, 0.1);
            text-align: center;
        }

        .auth-icon {
            width: 60px;
            height: 60px;
            background: var(--primary-green);
            color: white;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 20px;
            box-shadow: 0 10px 20px rgba(75, 45, 106, 0.3);
        }

        .login-header h1 {
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: 2px;
            color: var(--primary-green);
            margin-bottom: 5px;
        }

        .login-header p {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 30px;
        }

        /* Formulaire */
        .input-wrapper {
            text-align: left;
            margin-bottom: 20px;
        }

        .input-wrapper label {
            font-size: 0.65rem;
            font-weight: 700;
            color: var(--primary-green);
            margin-left: 5px;
            margin-bottom: 8px;
            display: block;
            letter-spacing: 1px;
        }

        .input-field {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-field i {
            position: absolute;
            left: 15px;
            color: var(--accent-green);
            font-size: 1.1rem;
        }

        .input-field input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border-radius: 12px;
            border: 2px solid #e8f0ed;
            background: white;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .input-field input:focus {
            border-color: var(--accent-green);
            box-shadow: 0 0 0 4px rgba(82, 183, 136, 0.1);
            outline: none;
        }

        /* Boutons */
        .login-actions {
            margin-top: 30px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .btn-login {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 12px;
            font-weight: 700;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-login:hover {
            background: #261b43;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(45, 106, 79, 0.2);
        }

        .btn-back {
            text-decoration: none;
            color: #666;
            font-size: 0.8rem;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-back:hover { color: var(--primary-green); }

        /* Erreur */
        .login-error {
            background: #fee2e2;
            color: #dc2626;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .login-footer {
            margin-top: 30px;
            font-size: 0.7rem;
            color: #999;
        }
     </style>
</head>
<body class="login-body">

<!-- <div class="login-bg-decor"></div> -->

    <div class="login-container fade-in">
        <div class="login-card">
            <header class="login-header">
                <div class="auth-icon">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <h1>AUTHENTIFICATION</h1>
                <p>Accès à l'outils de Suivi Logistique des Produits - AMCHO</p>
            </header>

            <?php if (!empty($error)): ?>
                <div class="login-error">
                    <i class="bi bi-exclamation-triangle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="input-wrapper">
                    <label>NOM D'UTILISATEUR</label>
                    <div class="input-field">
                        <i class="bi bi-person"></i>
                        <input type="text" name="username" placeholder="Identifiant" required autofocus>
                    </div>
                </div>

                <div class="input-wrapper">
                    <label>MOT DE PASSE</label>
                    <div class="input-field">
                        <i class="bi bi-key"></i>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>

                <div class="login-actions">
                    <button type="submit" class="btn-login">
                        <span>SE CONNECTER</span>
                        <i class="bi bi-chevron-right"></i>
                    </button>
                    
                    <a href="../../index.php" class="btn-back">
                        <i class="bi bi-arrow-left"></i> Menu Principal
                    </a>
                </div>
            </form>

            <footer class="login-footer">
                <p>© <?= date('Y') ?> - AMCHO — Madagascar</p>
            </footer>
        </div>
    </div>

</body>
</html>
