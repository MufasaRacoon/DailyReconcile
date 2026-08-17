
<?php
require_once __DIR__ . '/../app/config/auth.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/controllers/MachineController.php';
requireAuthMenu();

$controller_conche = new MachineController($pdo);
$data = $controller_conche->get_batch($controller_conche->getIdConche2());
// $details = $controller_conche->get_details_of_loading($controller_conche->getIdConche2());
$hasStock = $data && (float)$data['current_qty_kg'] > 0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conche 2 - Smart Factory</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.6);
            --glass-border: rgba(255, 255, 255, 0.4);
            --text-main: #1e293b;
            --text-muted: #64748b;
            --accent: #4e5eaf;
            --success: #10b981;
            --danger: #ef4444;
            --bg-light: #f8fafc;
            --bg-gradient: linear-gradient(135deg, #e2e8f0 0%, #f8fafc 100%);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Inter', 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-gradient);
            background-attachment: fixed;
            color: var(--text-main);
            padding: 100px 20px 40px; /* Espace pour la navbar fixe */
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* --- NAVIGATION --- */
        .navbar {
            height: 70px;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 5%;
            position: fixed;
            top: 0; width: 100%; z-index: 2000;
        }

        .nav-logo { font-weight: 800; font-size: 1.1rem; letter-spacing: -0.5px; }
        .nav-logo span { color: var(--accent); }

        .nav-right { display: flex; align-items: center; gap: 15px; }

        .btn-action {
            width: 38px; height: 38px;
            display: flex; align-items: center; justify-content: center;
            background: white;
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            cursor: pointer; color: var(--text-main);
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .btn-action:hover { transform: translateY(-2px); background: var(--accent); color: white; border-color: var(--accent); }

        .user-pill {
            display: flex; align-items: center; gap: 10px;
            padding: 4px 12px; background: white;
            border: 1px solid var(--glass-border); border-radius: 40px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.03);
        }

        /* --- CONTAINER PRINCIPAL --- */
        .glass-container {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 35px;
            width: 100%;
            max-width: 950px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.05);
        }

        h1 {
            font-size: 1.5rem;
            margin: 0 0 25px 0;
            font-weight: 700;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-card {
            background: rgba(255, 255, 255, 0.4);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid rgba(255,255,255,0.5);
        }

        /* --- FORMULAIRES & INPUTS --- */
        .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px; }
        .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }

        .input-group { display: flex; flex-direction: column; gap: 6px; }
        label { font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-left: 4px; }

        input, select {
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            padding: 12px 15px;
            font-size: 0.95rem;
            color: var(--text-main);
            transition: 0.2s;
            width: 100%;
        }

        input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }

        .input-wrapper { position: relative; display: flex; align-items: center; }
        .input-wrapper span { position: absolute; right: 15px; font-weight: 700; color: var(--text-muted); font-size: 0.8rem; }

        /* --- AFFICHAGE STOCK --- */
        .stock-display {
            background: var(--text-main);
            color: white;
            padding: 25px;
            border-radius: 16px;
            text-align: center;
            margin: 20px 0;
            box-shadow: 0 10px 20px rgba(30, 41, 59, 0.2);
        }
        .total-value { font-size: 2rem; font-weight: 800; color: var(--success); }

        /* --- BOUTONS --- */
        .btn {
            padding: 14px 28px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            width: 100%;
        }
        .btn.primary { background: var(--accent); color: white; }
        .btn.success { background: var(--success); color: white; }
        .btn.accent { background: #6366f1; color: white; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); filter: brightness(1.1); }

        hr { border: 0; border-top: 1px solid rgba(0,0,0,0.05); margin: 30px 0; }

        @media (max-width: 640px) {
            .navbar { padding: 0 15px; }
            .nav-logo { font-size: 0.9rem; }
            .user-pill { display: none; }
            .glass-container { padding: 20px; border-radius: 0; }
        }
    </style>
</head>
<body>
<!-- NAVBAR -->
    <nav class="navbar">
        <div class="nav-logo">AMCHO || <span>Daily Production</span></div>
        
        <div class="nav-right">
            <button class="btn-action" id="theme-toggle"><i class="bi bi-brightness-high"></i></button>

            <div class="user-pill">
                <div style="text-align: right; line-height: 1.1;">
                    <span style="font-weight: 700; font-size: 0.8rem; display: block;"><?= htmlspecialchars($_SESSION['username']) ?></span>
                    <span style="font-size: 0.6rem; color: var(--accent); font-weight: 800; text-transform: uppercase;"><?= htmlspecialchars($_SESSION['role']) ?></span>
                </div>
                <div style="width: 30px; height: 30px; background: var(--accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; color: #fff; font-weight: 800;">AM</div>
            </div>

            <a href="logout.php" class="btn-action" style="color: var(--danger);"><i class="bi bi-power"></i></a>
        </div>
    </nav>

    <div class="glass-container">
        <header style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px;">
            <h1>Conche 2</h1>
            <button class="btn accent" onclick="location.href='index.php'" style="width: auto;" >Back to menu</button>
        </header>

<!-- IF PAS DE BATCH DANS Conche 2 -->
<?php if (!$hasStock) {  ?>
        <main>
            <div class="stock-display">
                <label style="color: rgba(255,255,255,0.6);">Stock Actuel en Conche</label>
                <div class="total-value">Vide</div>
            </div>
                <button class="btn accent" onclick="location.href='metier/Conche2/conche_start.php'" style="margin-top:20px;">Nouveau Chargement</button>
        </main>

<!-- S'IL Y A DE BACTH EN COURS DANS Conche 2 -->
<?php } else { ?>
        <main>
            <div class="stock-display">
                <label style="color: rgba(255,255,255,0.6);">Stock Actuel en Conche</label>
                <div class="total-value"><?= htmlspecialchars((string)$data['current_qty_kg']) ?> kg </div>
                <label style="color: rgba(255,255,255,0.6);">OF :</label>
                <div class="total-value"><?= htmlspecialchars((string)$data['of_code_principale']) ?></div>
                <label style="color: rgba(255,255,255,0.6);">Type de Chocolat :</label>
                <div class="total-value"><?= htmlspecialchars((string)$data['type_chocolat']) ?></div>
                <label style="color: rgba(255,255,255,0.6);">Etat du Conche 2 :</label>
                <div class="total-value"><?= htmlspecialchars((string)$data['status']) ?></div>
                <?php if($data['current_qty_kg'] == $data['qty_principale']) { ?>
                <label style="color: rgb(255, 255, 255);">Le chargement a atteint la quantité voulu pour un batch, vous pouvez faire un transfert</label>
                <?php } ?>
            </div>
            <?php if ($data['status_load'] == 'LOADING' && $data['status'] == 'LOADING') { ?>
                <button class="btn accent" onclick="location.href='metier/Conche2/conche_loading.php?id=<?= (int)$data['id'] ?>'" style="margin-top:20px;">Chargement</button>
                <button class="btn accent" onclick="location.href='metier/Conche2/conche_details.php?id=<?= (int)$data['id'] ?>'" style="margin-top:20px;">Voir details des chargements</button>
            <?php } else if($data['status_load'] == 'LOADING' && ($data['status'] == 'FINISHED_LOADING')) { ?>
                <button class="btn accent" onclick="location.href='metier/Conche2/conche_transfert.php?id=<?= (int)$data['id'] ?>'" style="margin-top:20px;">Lancer le transfert</button>
                <button class="btn accent" onclick="location.href='metier/Conche2/conche_details.php?id=<?= (int)$data['id'] ?>'" style="margin-top:20px;">Voir details des chargements</button>
            <?php } else if($data['status_load'] == 'TRANSFERING' || ($data['status'] == 'TRANSFERED')) { ?>
                <button class="btn accent" onclick="location.href='metier/Conche2/conche_transfert.php?id=<?= (int)$data['id'] ?>'" style="margin-top:20px;">Lancer le transfert</button>
                <button class="btn accent" onclick="location.href='metier/Conche2/conche_details.php?id=<?= (int)$data['id'] ?>'" style="margin-top:20px;">Voir details des chargements</button>
              <?php  }?>
        </main>
<?php } ?>
    </div>
</body>
</html>


