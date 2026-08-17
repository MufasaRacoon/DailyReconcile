<?php
require_once __DIR__ . '/../../../app/config/auth.php';
require_once __DIR__ . '/../../../app/config/database.php';
requireAuth();
require_once __DIR__ . '/../../../app/controllers/MachineController.php';

$error = null;
$id = (int)($_GET['id'] ?? 0);
$controller_choco_tank = new MachineController($pdo);

$is_exist = $controller_choco_tank->find($id);
if (!$is_exist) {
    die('données introuvable');
}

// $equipment_id = $controller_conche->getIdConche1();
$data = $controller_choco_tank->get_batch($controller_choco_tank->getIdChocoTank1());
$ofList = $controller_choco_tank->getSelectableOF($controller_choco_tank->getIdChocoTank1());
$isExist_sous_of = $controller_choco_tank->charge_sous_of($id);

// Data Equipment
$dataChocoTank2 = $controller_choco_tank->getEquipment($controller_choco_tank->getIdChocoTank2());
$dataBufferTank1 = $controller_choco_tank->getEquipment($controller_choco_tank->getIdBufferTank1());
$dataBufferTank2 = $controller_choco_tank->getEquipment($controller_choco_tank->getIdBufferTank2());
$dataSelmiTank1 = $controller_choco_tank->getEquipment($controller_choco_tank->getIdSelmiTank1());
$dataSelmiTank2 = $controller_choco_tank->getEquipment($controller_choco_tank->getIdSelmiTank2());
$dataSelmiTank3 = $controller_choco_tank->getEquipment($controller_choco_tank->getIdSelmiTank3());
$dataConche2 = $controller_choco_tank->getEquipment($controller_choco_tank->getIdConche2());
$dataConche1 = $controller_choco_tank->getEquipment($controller_choco_tank->getIdConche1());

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $result = $controller_choco_tank->transfert($id, $_POST, $_SESSION['user_id']);
    if ($result === true){
        header('Location: ../../chocolat_tank_1.php');
        exit;
    } else {
        $error = $result;
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <title>Transfert Depuis Chocolat Tank 1</title>
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.6);
            --glass-border: rgba(255, 255, 255, 0.4);
            --text-main: #1e293b;
            --text-muted: #64748b;
            --accent: #3b82f6;
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

            <a href="../logout.php" class="btn-action" style="color: var(--danger);"><i class="bi bi-power"></i></a>
        </div>
    </nav>

    <div class="glass-container">
        <header style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px;">
            <h1>TRANSFERT DEPUIS Chocolat Tank 1</h1>
            <button class="btn accent" onclick="location.href='../../chocolat_tank_1.php'" style="width: auto;">Annuler</button>
            <button class="btn accent" onclick="location.href='../../index.php'" style="width: auto;">Back to menu</button>
        </header>

        <main>
            <!-- Affichage des messages d'erreur du PHP -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" style="background: rgba(255, 0, 0, 0.34); border: 1px solid red; color: black; padding: 15px; margin-bottom: 15px; border-radius: 8px;">
                    ⚠️ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form id="transferForm" action="chocolat_tank_transfert.php?id=<?= (int)$data['id'] ?>" method="post" class="section-card" style="border-left: 5px solid var(--accent);">
                
                <input type="hidden" name="equipment_id" value='<?= htmlspecialchars((string)$data['equipment_id']) ?>'>

                <div class="stock-display">
                    <label style="color: rgba(255,255,255,0.6);">Stock Actuel en Chocolat Tank 1</label>
                    <div class="total-value"><?= htmlspecialchars((string)$data['current_qty_kg']) ?> kg </div>
                    
                    <label style="color: rgba(255,255,255,0.6);">OF :</label>
                    <div class="total-value" id="ofCodeDisplay"><?= htmlspecialchars((string)$data['of_code_principale']) ?></div>
                    
                    <label style="color: rgba(255,255,255,0.6);">Type de Chocolat :</label>
                    <div class="total-value"><?= htmlspecialchars((string)$data['type_chocolat']) ?></div>
                </div>

                <div class="grid-3">
                    <?php if(!empty($isExist_sous_of)): ?>
                    <div class="input-group">
                        <label>Sous-OF Cible <span style="color:red; font-size: 16px">*</span></label>
                        <select name="of_code" id="selectSousOf" required>
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($ofList as $item): ?>
                                <?php if (!empty($item['sous_of'])): ?>
                                    <?php foreach ($item['sous_of'] as $sousOf): ?>
                                        <option value="<?= $sousOf['id'] ?>">
                                            <?= htmlspecialchars($sousOf['code']) ?> | <?= htmlspecialchars($sousOf['type']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="<?= htmlspecialchars($item['of_principal']) ?>">
                                        <?= htmlspecialchars($item['of_principal']) ?> | <?= htmlspecialchars($item['type_chocolat']) ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="input-group">
                        <label>Poids à transférer <span style="color:red; font-size: 16px">*</span></label> 
                        <div class="input-wrapper">
                            <input type="number" step="0.01" name="poids_transfert" id="poidsTransfert" required>
                            <span>kg</span>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Emplacement / Destination <span style="color:red; font-size: 16px">*</span></label>
                        <select name="destination" id="selectDestination" required>
                            <option value="">Sélectionner...</option>
                            <option value="<?= htmlspecialchars($controller_choco_tank->getIdChocoTank2()) ?>">Choco Tank 2 - <?= htmlspecialchars((string)$dataChocoTank2['current_qty_kg']) ?> Kg / <?= htmlspecialchars((string)$dataChocoTank2['capacity_kg']) ?> Kg</option>
                            <option value="<?= htmlspecialchars($controller_choco_tank->getIdBufferTank1()) ?>">Buffer Tank 1 - <?= htmlspecialchars((string)$dataBufferTank1['current_qty_kg']) ?> Kg / <?= htmlspecialchars((string)$dataBufferTank1['capacity_kg']) ?> Kg</option>
                            <option value="<?= htmlspecialchars($controller_choco_tank->getIdBufferTank2()) ?>">Buffer Tank 2 - <?= htmlspecialchars((string)$dataBufferTank2['current_qty_kg']) ?> Kg / <?= htmlspecialchars((string)$dataBufferTank2['capacity_kg']) ?> Kg</option>
                            <option value="<?= htmlspecialchars($controller_choco_tank->getIdSelmiTank1()) ?>">Selmi Tank 1 - <?= htmlspecialchars((string)$dataSelmiTank1['current_qty_kg']) ?> Kg / <?= htmlspecialchars((string)$dataSelmiTank1['capacity_kg']) ?> Kg</option>
                            <option value="<?= htmlspecialchars($controller_choco_tank->getIdSelmiTank2()) ?>">Selmi Tank 2 - <?= htmlspecialchars((string)$dataSelmiTank2['current_qty_kg']) ?> Kg / <?= htmlspecialchars((string)$dataSelmiTank2['capacity_kg']) ?> Kg</option>
                            <option value="<?= htmlspecialchars($controller_choco_tank->getIdSelmiTank3()) ?>">Selmi Tank 3 - <?= htmlspecialchars((string)$dataSelmiTank3['current_qty_kg']) ?> Kg / <?= htmlspecialchars((string)$dataSelmiTank3['capacity_kg']) ?> Kg</option>
                            <option value="<?= htmlspecialchars($controller_choco_tank->getIdConche2()) ?>">Conche 2 - <?= htmlspecialchars((string)$dataConche2['current_qty_kg']) ?> Kg / <?= htmlspecialchars((string)$dataConche2['capacity_kg']) ?> Kg</option>
                            <option value="<?= htmlspecialchars($controller_choco_tank->getIdConche1()) ?>">Conche 1 - <?= htmlspecialchars((string)$dataConche1['current_qty_kg']) ?> Kg / <?= htmlspecialchars((string)$dataConche1['capacity_kg']) ?> Kg</option>
                        </select>
                    </div>
                </div>

                <!-- Bouton déclencheur qui ouvre la modale -->
                <button type="button" class="btn accent" onclick="openConfirmModal()">Exécuter le Transfert</button>
            </form>
        </main>
    </div>

    <!-- MODALE DE CONFIRMATION JS -->
    <dialog id="confirmModal" style="border: none; border-radius: 12px; padding: 25px; background: #222; color: #fff; box-shadow: 0px 10px 30px rgba(0,0,0,0.5); max-width: 450px; width: 100%;">
        <h3 style="margin-top:0; color: #4CAF50;">Confirmation de Transfert</h3>
        <hr style="border-color: #444;">
        <p>Veuillez vérifier les informations ci-dessous avant d'exécuter l'action :</p>
        
        <ul style="list-style: none; padding: 0; line-height: 1.8;">
            <li><strong>OF Principal :</strong> <span id="modalOF"></span></li>
            <li><strong>Poids à transférer :</strong> <span id="modalPoids" style="color:#FFD700; font-weight:bold;"></span> kg</li>
            <li><strong>Destination :</strong> <span id="modalDest"></span></li>
        </ul>

        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
            <button type="button" class="btn" onclick="closeConfirmModal()" style="background: #555; color: white; border:none; padding: 8px 16px; border-radius: 4px; cursor:pointer;">Annuler</button>
            <button type="button" class="btn accent" onclick="submitTransferForm()" style="background: #4CAF50; color: white; border:none; padding: 8px 16px; border-radius: 4px; cursor:pointer;">Valider le Transfert</button>
        </div>
    </dialog>

    <script>
    function openConfirmModal() {
        const form = document.getElementById('transferForm');
        
        // Vérification de la validité du formulaire HTML (champs required)
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // Récupération des données sélectionnées
        const poids = document.getElementById('poidsTransfert').value;
        const destSelect = document.getElementById('selectDestination');
        const destText = destSelect.options[destSelect.selectedIndex].text;
        const ofCode = document.getElementById('ofCodeDisplay').innerText;

        // Injection dans le pop-up
        document.getElementById('modalOF').innerText = ofCode;
        document.getElementById('modalPoids').innerText = poids;
        document.getElementById('modalDest').innerText = destText;

        // Ouverture du Pop-Up
        document.getElementById('confirmModal').showModal();
    }

    function closeConfirmModal() {
        document.getElementById('confirmModal').close();
    }

    function submitTransferForm() {
        document.getElementById('transferForm').submit();
    }
    </script>
</body>
</html>