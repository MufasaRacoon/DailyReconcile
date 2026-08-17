<?php
require_once __DIR__ . '/../app/config/auth.php';
require_once __DIR__ . '/../app/config/database.php';

require_once __DIR__ . '/../app/controllers/DashboardService.php';
requireAuthMenu();

$dashboardService = new DashboardService($pdo);

// Chargement des données globales du tableau de bord
$equipments = $dashboardService->getEquipments();
$logs = $dashboardService->getRecentLogs(10);
?>

<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AMCHO | High-Performance Control Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #6366f1;
            --accent: #4e5eaf;
            --success: #10b981;
            --danger: #ef4444;
            
            /* Thème Dark (GPU Friendly) */
            --bg-main: #0f172a;
            --nav-bg: rgba(15, 23, 42, 0.9);
            --glass: rgba(255, 255, 255, 0.04);
            --glass-hover: rgba(255, 255, 255, 0.08);
            --glass-border: rgba(255, 255, 255, 0.1);
            --text-main: #f8fafc;
            --text-dim: #94a3b8;
        }

        [data-theme="light"] {
            --bg-main: #f1f5f9;
            --nav-bg: rgba(255, 255, 255, 0.9);
            --glass: rgba(255, 255, 255, 0.8);
            --glass-hover: #ffffff;
            --glass-border: rgba(0, 0, 0, 0.06);
            --text-main: #1e293b;
            --text-dim: #64748b;
            
        }

        /* Optimisation Globale */
        * { box-sizing: border-box; }
        
        body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-main);
            min-height: 100vh;
            padding-top: 90px;
            overflow-x: hidden;
            /* Transition limitée pour ne pas charger le CPU */
            transition: background-color 0.3s ease;
        }

        /* --- NAVIGATION OPTIMISÉE --- */
        .navbar {
            height: 75px;
            background: var(--nav-bg);
            backdrop-filter: blur(10px); /* Flou réduit pour performance */
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
            position: fixed;
            top: 0; width: 100%; z-index: 2000;
        }

        .nav-logo { font-weight: 800; font-size: 1.25rem; letter-spacing: -0.5px; }
        .nav-logo span { color: var(--accent); }

        .nav-right { display: flex; align-items: center; gap: 12px; }

        .btn-action {
            width: 38px; height: 38px;
            display: flex; align-items: center; justify-content: center;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            cursor: pointer; color: var(--text-main);
            transition: transform 0.2s ease, background 0.2s ease;
        }
        .btn-action:hover { transform: translateY(-2px); background: #94a3b8; color: white; }

        .user-pill {
            display: flex; align-items: center; gap: 10px;
            padding: 5px 12px; background: var(--glass);
            border: 1px solid var(--glass-border); border-radius: 40px;
        }

        /* --- LAYOUT SECTIONS --- */
        .main-container { max-width: 1300px; margin: 0 auto; padding: 0 25px 50px; }

        .section-header {
            display: flex; align-items: center; gap: 15px; margin: 35px 0 20px;
        }
        .section-header h2 { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 2px; color: var(--text-dim); margin: 0; }
        .line { flex-grow: 1; height: 1px; background: var(--glass-border); }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        /* --- CARDS HAUTE PERFORMANCE --- */
        .card-glass {
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 18px;
            padding: 25px;
            position: relative;
            cursor: pointer;
            /* Utilisation de will-change pour booster le GPU */
            will-change: transform, opacity;
            transition: transform 0.3s cubic-bezier(0.25, 1, 0.5, 1), background 0.2s ease;
        }

        /* Animation au survol allégée (pas de box-shadow lourd) */
        .card-glass:hover {
            transform: translateY(-6px);
            background: var(--glass-hover);
            border-color: var(--accent);
        }

        .card-product-info {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            font-size: 0.75rem;
        }

        .badge-of {
            background: rgba(var(--text-dim, 255, 255, 255), 0.15);
            color: var(--accent, #ffd700);
            padding: 2px 7px;
            border-radius: 4px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .badge-type {
            background: rgba(125, 112, 146, 0.58);
            color: var(--bg-main, #fff);
            padding: 2px 7px;
            border-radius: 4px;
            font-style: italic;
        }

        .badge-empty {
            color: rgba(255, 255, 255, 0.35);
            font-size: 0.72rem;
            font-style: italic;
        }

        .card-glass i { font-size: 1.8rem; color: var(--primary); margin-bottom: 15px; display: block; }
        .card-glass h3 { margin: 0 0 8px; font-weight: 700; font-size: 1rem; }
        .card-glass p { margin: 0; color: var(--text-dim); font-size: 0.8rem; line-height: 1.5; }

        .status-dot {
            position: absolute; top: 20px; right: 20px;
            width: 8px; height: 8px; border-radius: 50%;
            background: var(--success);
            box-shadow: 0 0 8px var(--success);
        }

        /* --- LOGS & VISUELS --- */
        .bottom-layout {
            display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 35px;
        }

        .visual-panel {
            height: 300px; border-radius: 18px; border: 1px solid var(--glass-border);
            background: var(--glass); display: flex; align-items: center; justify-content: center;
        }

        .log-terminal {
            background: rgba(0,0,0,0.2); border-radius: 18px; padding: 20px;
            font-family: 'JetBrains Mono', monospace; font-size: 0.75rem;
            height: 300px; overflow-y: auto; border: 1px solid var(--glass-border);
        }
        .log-entry { margin-bottom: 8px; border-left: 2px solid var(--accent); padding-left: 10px; color: var(--text-dim); }

        .status-dot.status-idle {
            background-color: #4CAF50; /* Vert (Inactif / Prêt) */
        }

        .status-dot.status-busy {
            background-color: #FF9800; /* Orange (En cours de chargement/transfert) */
        }

        .status-dot.status-full {
            background-color: #F44336; /* Rouge (Plein) */
        }

        /* --- ANIMATION D'ENTRÉE (SANS FLOU POUR FPS) --- */
        @keyframes quickFadeUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-in { animation: quickFadeUp 0.4s ease-out both; }
        .delay-1 { animation-delay: 0.05s; }
        .delay-2 { animation-delay: 0.1s; }
        .delay-3 { animation-delay: 0.15s; }

        @media (max-width: 850px) { .bottom-layout { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-logo">AMCHO || <span>Daily Production</span></div>
        
        <div class="nav-right">
            <button class="btn-action" id="theme-toggle" title="Toggle Mode">
                <i class="bi bi-moon-stars" id="theme-icon"></i>
            </button>

            <div class="user-pill">
                <div style="text-align: right; line-height: 1.1;">
                    <span style="font-weight: 700; font-size: 0.8rem; display: block; color: var(--text-main);"><?= htmlspecialchars($_SESSION['username']) ?></span>
                    <span style="font-size: 0.6rem; color: var(--accent); font-weight: 800; text-transform: uppercase;"><?= htmlspecialchars($_SESSION['role']) ?></span>
                </div>
                <div style="width: 28px; height: 28px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; color: #fff; font-weight: 800;">WR</div>
            </div>

            <a href="logout.php" class="btn-action" style="color: var(--danger);" title="Déconnexion">
                <i class="bi bi-power"></i>
            </a>
        </div>
    </nav>

    <div class="main-container">

        <!-- Administration -->
        <div class="section-header animate-in delay-1">
            <h2>Administration Système</h2><div class="line"></div>
        </div>
        <div class="grid-container animate-in delay-1">
            <div class="card-glass" onclick="location.href='users/mgmt_users.php'">
                <i class="bi bi-shield-lock"></i>
                <h3>Gestion Users</h3>
                <p>Accès et rôles sécurisés.</p>
            </div>
        </div>

        <!-- Chaîne de production Live -->
        <div class="section-header animate-in delay-2">
            <h2>Chaine de Production Live</h2><div class="line"></div>
        </div>

        <!-- Conching -->
        <div class="section-header animate-in delay-2">
            <div class="line"></div><h2>Conching</h2><div class="line"></div>
        </div>
        <div class="grid-container animate-in delay-2">
            <?php $info = $dashboardService->getEquipmentInfo($equipments, 'CONCHE_1', 1000); ?>
            <div class="card-glass" onclick="location.href='conche_1.php'">
                <div class="status-dot <?= $info['status_class'] ?>"></div>
                <i class="bi bi-recycle"></i>
                <h3>Conche 1</h3>
                <p><?= $info['percent'] ?>% | <?= $info['current'] ?> Kg / <?= $info['max'] ?> Kg</p>
                <!-- Zone OF & Type de Chocolat -->
                <div class="card-product-info">
                    <?php if (!empty($info['of_code'])): ?>
                        <span class="badge-of">OF: <?= htmlspecialchars($info['of_code']) ?></span>
                        <?php if (!empty($info['type_chocolat'])): ?>
                            <span class="badge-type"><?= htmlspecialchars($info['type_chocolat']) ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge-empty">Équipement vide</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php $info = $dashboardService->getEquipmentInfo($equipments, 'CONCHE_2', 1000); ?>
            <div class="card-glass" onclick="location.href='conche_2.php'">
                <div class="status-dot <?= $info['status_class'] ?>"></div>
                <i class="bi bi-recycle"></i>
                <h3>Conche 2</h3>
                <p><?= $info['percent'] ?>% | <?= $info['current'] ?> Kg / <?= $info['max'] ?> Kg</p>
                <!-- Zone OF & Type de Chocolat -->
                <div class="card-product-info">
                    <?php if (!empty($info['of_code'])): ?>
                        <span class="badge-of">OF: <?= htmlspecialchars($info['of_code']) ?></span>
                        <?php if (!empty($info['type_chocolat'])): ?>
                            <span class="badge-type"><?= htmlspecialchars($info['type_chocolat']) ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge-empty">Équipement vide</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Chocolat Tank -->
        <div class="section-header animate-in delay-3">
            <div class="line"></div><h2>Chocolat Tank</h2><div class="line"></div>
        </div>
        <div class="grid-container animate-in delay-3">
            <?php $info = $dashboardService->getEquipmentInfo($equipments, 'CHOCO_TANK_1', 1000); ?>
            <div class="card-glass" onclick="location.href='chocolat_tank_1.php'">
                <div class="status-dot <?= $info['status_class'] ?>"></div>
                <i class="bi bi-c-square"></i>
                <h3>Choco TANK 1</h3>
                
                <p><?= $info['percent'] ?>% | <?= $info['current'] ?> Kg / <?= $info['max'] ?> Kg</p>

                <!-- Zone OF & Type de Chocolat -->
                <div class="card-product-info">
                    <?php if (!empty($info['of_code'])): ?>
                        <span class="badge-of">OF: <?= htmlspecialchars($info['of_code']) ?></span>
                        <?php if (!empty($info['type_chocolat'])): ?>
                            <span class="badge-type"><?= htmlspecialchars($info['type_chocolat']) ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge-empty">Équipement vide</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php $info = $dashboardService->getEquipmentInfo($equipments, 'CHOCO_TANK_2', 1000); ?>
            <div class="card-glass" onclick="location.href='chocolat_tank_2.php'">
                <div class="status-dot <?= $info['status_class'] ?>"></div>
                <i class="bi bi-c-square"></i>
                <h3>Choco TANK 2</h3>
                <p><?= $info['percent'] ?>% | <?= $info['current'] ?> Kg / <?= $info['max'] ?> Kg</p>
                <!-- Zone OF & Type de Chocolat -->
                <div class="card-product-info">
                    <?php if (!empty($info['of_code'])): ?>
                        <span class="badge-of">OF: <?= htmlspecialchars($info['of_code']) ?></span>
                        <?php if (!empty($info['type_chocolat'])): ?>
                            <span class="badge-type"><?= htmlspecialchars($info['type_chocolat']) ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge-empty">Équipement vide</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Buffer Tank -->
        <div class="section-header animate-in delay-3">
            <div class="line"></div><h2>Buffer Tank</h2><div class="line"></div>
        </div>
        <div class="grid-container animate-in delay-3">
            <?php $info = $dashboardService->getEquipmentInfo($equipments, 'BUFFER_TANK_1', 500); ?>
            <div class="card-glass" onclick="location.href='buffer_tank_1.php'">
                <div class="status-dot <?= $info['status_class'] ?>"></div>
                <i class="bi bi-bootstrap"></i>
                <h3>Buffer TANK 1</h3>
                <p><?= $info['percent'] ?>% | <?= $info['current'] ?> Kg / <?= $info['max'] ?> Kg</p>
                <!-- Zone OF & Type de Chocolat -->
                <div class="card-product-info">
                    <?php if (!empty($info['of_code'])): ?>
                        <span class="badge-of">OF: <?= htmlspecialchars($info['of_code']) ?></span>
                        <?php if (!empty($info['type_chocolat'])): ?>
                            <span class="badge-type"><?= htmlspecialchars($info['type_chocolat']) ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge-empty">Équipement vide</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php $info = $dashboardService->getEquipmentInfo($equipments, 'BUFFER_TANK_2', 500); ?>
            <div class="card-glass" onclick="location.href='buffer_tank_2.php'">
                <div class="status-dot <?= $info['status_class'] ?>"></div>
                <i class="bi bi-bootstrap"></i>
                <h3>Buffer TANK 2</h3>
                <p><?= $info['percent'] ?>% | <?= $info['current'] ?> Kg / <?= $info['max'] ?> Kg</p>
                <!-- Zone OF & Type de Chocolat -->
                <div class="card-product-info">
                    <?php if (!empty($info['of_code'])): ?>
                        <span class="badge-of">OF: <?= htmlspecialchars($info['of_code']) ?></span>
                        <?php if (!empty($info['type_chocolat'])): ?>
                            <span class="badge-type"><?= htmlspecialchars($info['type_chocolat']) ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge-empty">Équipement vide</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Selmi Tank -->
        <div class="section-header animate-in delay-3">
            <div class="line"></div><h2>Selmi Tank</h2><div class="line"></div>
        </div>
        <div class="grid-container animate-in delay-3">
            <?php $info = $dashboardService->getEquipmentInfo($equipments, 'SELMI_TANK_1', 400); ?>
            <div class="card-glass" onclick="location.href='selmi_tank_1.php'">
                <div class="status-dot <?= $info['status_class'] ?>"></div>
                <i class="bi bi-stripe"></i>
                <h3>Selmi TANK 1</h3>
                <p><?= $info['percent'] ?>% | <?= $info['current'] ?> Kg / <?= $info['max'] ?> Kg</p>
                <!-- Zone OF & Type de Chocolat -->
                <div class="card-product-info">
                    <?php if (!empty($info['of_code'])): ?>
                        <span class="badge-of">OF: <?= htmlspecialchars($info['of_code']) ?></span>
                        <?php if (!empty($info['type_chocolat'])): ?>
                            <span class="badge-type"><?= htmlspecialchars($info['type_chocolat']) ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge-empty">Équipement vide</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php $info = $dashboardService->getEquipmentInfo($equipments, 'SELMI_TANK_2', 400); ?>
            <div class="card-glass" onclick="location.href='selmi_tank_2.php'">
                <div class="status-dot <?= $info['status_class'] ?>"></div>
                <i class="bi bi-stripe"></i>
                <h3>Selmi TANK 2</h3>
                <p><?= $info['percent'] ?>% | <?= $info['current'] ?> Kg / <?= $info['max'] ?> Kg</p>
                <!-- Zone OF & Type de Chocolat -->
                <div class="card-product-info">
                    <?php if (!empty($info['of_code'])): ?>
                        <span class="badge-of">OF: <?= htmlspecialchars($info['of_code']) ?></span>
                        <?php if (!empty($info['type_chocolat'])): ?>
                            <span class="badge-type"><?= htmlspecialchars($info['type_chocolat']) ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge-empty">Équipement vide</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php $info = $dashboardService->getEquipmentInfo($equipments, 'SELMI_TANK_3', 400); ?>
            <div class="card-glass" onclick="location.href='selmi_tank_3.php'">
                <div class="status-dot <?= $info['status_class'] ?>"></div>
                <i class="bi bi-stripe"></i>
                <h3>Selmi TANK 3</h3>
                <p><?= $info['percent'] ?>% | <?= $info['current'] ?> Kg / <?= $info['max'] ?> Kg</p>
                <!-- Zone OF & Type de Chocolat -->
                <div class="card-product-info">
                    <?php if (!empty($info['of_code'])): ?>
                        <span class="badge-of">OF: <?= htmlspecialchars($info['of_code']) ?></span>
                        <?php if (!empty($info['type_chocolat'])): ?>
                            <span class="badge-type"><?= htmlspecialchars($info['type_chocolat']) ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge-empty">Équipement vide</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!--Dechet & Mise en quarantaine -->
        <div class="section-header animate-in delay-3">
            <h2>Dechet & Mise en quarantaine</h2><div class="line"></div>
        </div>

        <!-- Dechet -->
        <div class="grid-container animate-in delay-3">
            <?php $info = $dashboardService->getEquipmentInfo($equipments, 'TRASH'); ?>
            <div class="card-glass" onclick="location.href='dechet.php'">
                <div class="status-dot <?= $info['status_class'] ?>"></div>
                <i class="bi bi-trash2-fill"></i>
                <h3>Dechets</h3>
                <p><?= $info['current'] ?> Kg / No limits</p>
            </div>
            
            <!-- Mise en quarantaine -->
            <?php $info = $dashboardService->getEquipmentInfo($equipments, 'QUARANTINE'); ?>
            <div class="card-glass" onclick="location.href='quarantine.php'">
                <div class="status-dot <?= $info['status_class'] ?>"></div>
                <i class="bi bi-shield-exclamation"></i>
                <h3>Mise en quarantaine</h3>
                <p><?= $info['current'] ?> Kg / <?= $info['max'] ?> Kg</p>
            </div>
        </div>

        <!-- Terminal de Logs -->
        <div class="section-header animate-in delay-4">
            <h2>Actions & Diagnostic</h2><div class="line"></div>
        </div>
        <div class="bottom-layout animate-in delay-4">
            <div class="log-terminal">
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log): ?>
                        <?php 
                            $time = date('H:i', strtotime($log['occurred_at']));
                            $operator = htmlspecialchars($log['operator_name'] ?? 'Système');
                            $equipment = htmlspecialchars($log['equipment_name'] ?? 'Équipement');
                            $qty = (float)$log['qty_kg'];
                            $action = ($log['reference_type'] === 'TRANSFER_IN' || $log['reference_type'] === 'LOADING_CONCHE') ? " (IN) Entrée de" : " (OUT) Sortie de";
                        ?>
                        <div class="log-entry">
                            <span style="color: var(--accent);"><?= $time ?></span> 
                            <?= $action ?> <?= $qty ?> Kg dans <strong><?= $equipment ?></strong> par <?= $operator ?>.
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="log-entry">Aucun mouvement récent enregistré.</div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <script>
        const btn = document.getElementById('theme-toggle');
        const icon = document.getElementById('theme-icon');
        const root = document.documentElement;

        btn.addEventListener('click', () => {
            const isDark = root.getAttribute('data-theme') === 'dark';
            root.setAttribute('data-theme', isDark ? 'light' : 'dark');
            icon.classList.toggle('bi-moon-stars');
            icon.classList.toggle('bi-sun');
            localStorage.setItem('amcho-theme', isDark ? 'light' : 'dark');
        });

        // Restore Theme
        if(localStorage.getItem('amcho-theme') === 'light') {
            root.setAttribute('data-theme', 'light');
            icon.classList.replace('bi-moon-stars', 'bi-sun');
        }
    </script>
</body>
</html>