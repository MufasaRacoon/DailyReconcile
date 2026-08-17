<?php
require_once __DIR__ . '/../../../app/config/auth.php';
require_once __DIR__ . '/../../../app/config/database.php';
requireAuth();
require_once __DIR__ . '/../../../app/controllers/MachineController.php';

$error = null;
$id = (int)($_GET['id'] ?? 0);
$controller_conche = new MachineController($pdo);

$is_exist = $controller_conche->find($id);
if (!$is_exist) {
    die('données introuvable');
}

$equipment_id = $controller_conche->getIdConche2();
$data = $controller_conche->get_batch($equipment_id);
// $sous_of = $controller_conche->get_sous_of($id);

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $result = $controller_conche->loading($id, $_POST, $_SESSION['user_id'], false);

    if ($result === true){
        header('Location: ../../conche_2.php');
        exit;
    } else {
        $error = $result;
    }
}

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

            <a href="logout.php" class="btn-action" style="color: var(--danger);"><i class="bi bi-power"></i></a>
        </div>
    </nav>

    <div class="glass-container">
        <header style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px;">
            <h1>CHARGEMENT DANS CONCHE 2</h1>
            <button class="btn accent" onclick="location.href='../../conche_2.php'" style="width: auto;" >Annuler</button>
            <button class="btn accent" onclick="location.href='../../index.php'" style="width: auto;" >Back to menu</button>
        </header>

        <?php echo  $error;?>

        <main>
    <!-- Formulaire CONCHE 2 OF -->
            <form action="conche_loading.php?id=<?= (int)$data['id'] ?>" method="post" class="section-card">
                <div class="grid-2">
                    <div class="input-group">
                        <label>Date de session <span style="color:red; font-size: 16px">*</span></label>
                        <input type="date" id="currentDate" name="date" readonly
                               value="<?= htmlspecialchars($data['date']) ?>" required>
                    </div>
                    <div class="input-group">
                        <label>Heure d'activation <span style="color:red; font-size: 16px">*</span></label>
                        <input type="time" id="currentTime" name="heure" readonly
                               value="<?= htmlspecialchars($data['heure']) ?>" required>
                    </div>
                    <div class="input-group">
                        <label>OF code <span style="color:red; font-size: 16px">*</span></label>
                        <input type="text" name="of_code_principale" placeholder="Code de Fabrication"
                               value="<?= htmlspecialchars($data['of_code_principale']) ?>">
                        <label>Type de Chocolat <span style="color:red; font-size: 16px">*</span></label>
                        <input list='type-choco' name="type_chocolat" placeholder="Le type de chocolat cible"
                               value="<?= htmlspecialchars($data['type_chocolat']) ?>" required>
                                <datalist id="type-choco">
                                    <option value="80% BARS">
                                    <option value="92% BARS">
                                    <option value="70% BARS">
                                    <option value="92% DISC">
                                    <option value="70% DISC">
                                    <option value="60% UG DISC">
                                    <option value="63% SSN 75g">
                                    <option value="63% SSA">
                                    <option value="70% TC">
                                    <option value="73% SC">
                                    <option value="73% OZ">
                                    <option value="73% CR">
                                    <option value="73% TH">
                                    <option value="72% FS">
                                    <option value="72% PSS">
                                    <option value="72% VB">
                                    <option value="85% PDC">
                                    <option value="85% SS">
                                    <option value="63% AC">
                                    <option value="96% PDC">
                                    <option value="91% PDC">
                                    <option value="60% Almond.V">
                                    <option value="60% VANILLA TJ">
                                    <option value="60% VANILLA BEAN">
                                    <option value="60% PEPPERMINT MG 12g">
                                    <option value="60% HAZELNUT BRITTLE 12g">
                                    <option value="UG 100% DATE SESAME 60g">
                                    <option value="UG 70% RHUM RAISINS 60g">
                                    <option value="72% SEA SALT and NIBS 60g">
                                    <option value="MG 72% VANILLA BEAN 60g">
                                    <option value="UG 73% ORANGE ZEST 60g">
                                    <option value="60% PEPPERMINT HOLIDAY 100g">
                                    <option value="60% HAZELNUT BRITTLE HOLIDAY 100g">
                                    <option value="MG 72% CANDIED GINGER 60g">
                                    <option value="MG 72% SEA SALT and NIBS 60g">
                                </datalist>
                    </div>
                </div>

            <!-- SOUS OF -->
                <div class="input-group" style="margin-top:20px;">
                    <label>
                        Activer les Sous-OF
                    </label>
                        <input type="checkbox" id="enableSousOf">
                </div>
                
                <div id="sous-of-container" class="grid-3" style="margin-top: 20px; display:none;">
                    <div class="input-group">
                        <label>SOUS-OF 1</label>
                        <!-- <input type="text" name="of_1" placeholder="OF code 1"> -->
                        <input type="text" name="sous_of[]" placeholder="Extension OF code 1">
                        <input list='type-choco' name="sous_type_chocolat[]" placeholder="Le type de chocolat cible">
                    </div>
                    <div class="input-group">
                        <label>SOUS-OF 2</label>
                        <!-- <input type="text" name="of_2" placeholder="OF code 2"> -->
                        <input type="text" name="sous_of[]" placeholder="Extension OF code 2">
                        <input list='type-choco' name="sous_type_chocolat[]" placeholder="Le type de chocolat cible">
                    </div>
                    <div class="input-group">
                        <label>SOUS-OF 3</label>
                        <!-- <input type="text" name="of_3" placeholder="OF code 3"> -->
                        <input type="text" name="sous_of[]" placeholder="Extension OF code 3">
                        <input list='type-choco' name="sous_type_chocolat[]" placeholder="Le type de chocolat cible">
                    </div>
                </div>
                
                <div class="input-group" style="margin-bottom: 25px;">
                    <label>Batch / Volume Total <span style="color:red; font-size: 16px">*</span></label>
                    <div class="input-wrapper">
                        <input type="number" name="qty_principale" placeholder="Ex: 500" 
                               value="<?= htmlspecialchars($data['qty_principale']) ?>" required>
                        <span>kg</span>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="input-group"><label>Cocoa Nibs (chargé)</label><div class="input-wrapper"><input type="number" name="qty_cocoa_nibs_charged" id="cocoa-nibs" value="<?= htmlspecialchars($data['qty_cocoa_nibs']) ?>" ><span>kg</span></div></div>
                    <div class="input-group"><label>Cocoa Nibs (Nouveau) </label><div class="input-wrapper"><input type="number" name="qty_cocoa_nibs" id="new-cocoa-nibs"><span>kg</span></div></div>

                    <div class="input-group"><label>Sugar Cane(chargé)</label><div class="input-wrapper"><input type="number" name="qty_sugar_cane_charged" id="sugar-cane" value="<?= htmlspecialchars($data['qty_sugar_cane']) ?>" ><span>kg</span></div></div>
                    <div class="input-group"><label>Sugar Cane (Nouveau)</label><div class="input-wrapper"><input type="number" name="qty_sugar_cane" id="new-sugar-cane"><span>kg</span></div></div>

                    <div class="input-group"><label>Cocoa Butter (chargé)</label><div class="input-wrapper"><input type="number" name="qty_cocoa_butter_charged" id="cocoa-butter" value="<?= htmlspecialchars($data['qty_cocoa_butter']) ?>" ><span>kg</span></div></div>
                    <div class="input-group"><label>Cocoa Butter (Nouveau)</label><div class="input-wrapper"><input type="number" name="qty_cocoa_butter" id="new-cocoa-butter"><span>kg</span></div></div>
                    
                    <div class="input-group"><label>Lecithin (chargé)</label><div class="input-wrapper"><input type="number" name="qty_lecithin_charged" id="lecithin" value="<?= htmlspecialchars($data['qty_lecithin']) ?>" ><span>kg</span></div></div>
                    <div class="input-group"><label>Lecithin (Nouveau)</label><div class="input-wrapper"><input type="number" name="qty_lecithin" id="new-lecithin"><span>kg</span></div></div>
                </div>

                <div class="stock-display">
                    <label style="color: rgba(255,255,255,0.6);">Stock Actuel chargé en Conche</label>
                    <div class="total-value">
                    <input type="number" name="qty_stock_charged" id="stock-conche" style="text-align: center; font-size: 2rem; font-weight: 800; color: var(--success); background-color: var(--text-main)"
                            value="<?= htmlspecialchars($data['qty_stock']) ?>">    
                    kg
                    </div>
                    <label style="color: rgba(255,255,255,0.6);">Stock après chargement en Conche</label>
                    <div class="total-value">
                    <input type="number" name="qty_stock" id="new-stock-conche" style="text-align: center; font-size: 2rem; font-weight: 800; color: var(--success); background-color: var(--text-main)">    
                    kg
                    </div>
                </div>

                <button type="submit" id="btn-load" class="btn success" >Lancer le Chargement (LOAD)</button>
            </form>
        </main>
    </div>

    <script>

        const stock = document.getElementById('stock-conche').value;

        // New quantity of ingredient
        const new_m1 = document.getElementById('new-cocoa-nibs');
        const new_m2 = document.getElementById('new-sugar-cane');
        const new_m3 = document.getElementById('new-cocoa-butter');
        const new_m4 = document.getElementById('new-lecithin');
        const new_stock = document.getElementById('new-stock-conche');

    // Mise à jour automatique de la date et l'heure
        function updateDateTime() {
            // const now = new Date();
            // const dateStr = now.toLocaleDateString('us-US', { day: '2-digit', month: '2-digit', year: 'numeric' });
            // const timeStr = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });

            const maintenant = new Date();

            // 1. Remplissage de la Date (format YYYY-MM-DD)
            const jour = ("0" + maintenant.getDate()).slice(-2);
            const mois = ("0" + (maintenant.getMonth() + 1)).slice(-2);
            const annee = maintenant.getFullYear();
            document.getElementById('currentDate').value = `${annee}-${mois}-${jour}`;

            // 2. Remplissage de l'Heure (format HH:MM)
            const heures = ("0" + maintenant.getHours()).slice(-2);
            const minutes = ("0" + maintenant.getMinutes()).slice(-2);
            document.getElementById('currentTime').value = `${heures}:${minutes}`;
            
            // document.getElementById('currentDate').value = dateStr;
            // document.getElementById('currentTime').value = timeStr;
        }
        
        updateDateTime();
        setInterval(updateDateTime, 60000); // Mise à jour chaque minute

    // Affichage des sous of apres validation checkbox
        const checkboxSousOf = document.getElementById('enableSousOf');

        const sousOfContainer = document.getElementById('sous-of-container');

        checkboxSousOf.addEventListener('change', function () {

            if (this.checked) {

                sousOfContainer.style.display = 'grid';

            } else {

                sousOfContainer.style.display = 'none';

                // Nettoyer les champs automatiquement
                const inputs = sousOfContainer.querySelectorAll('input');

                inputs.forEach(input => {
                    input.value = '';
                });
            }
        });

    // Calcule automatique du stock en conche (Somme des nouveaux ingredients)
        function calculNewSomme(stock) {

            // Conversion en float

            const v1 = parseFloat(new_m1.value) || 0;
            const v2 = parseFloat(new_m2.value) || 0;
            const v3 = parseFloat(new_m3.value) || 0;
            const v4 = parseFloat(new_m4.value) || 0;

            // Vérifier si au moins un champ contient une valeur

            const inputs = [
                new_m1.value,
                new_m2.value,
                new_m3.value,
                new_m4.value
            ].filter(val => val !== "" && !isNaN(parseFloat(val)));

            if (inputs.length > 0) {

                const somme = v1 + v2 + v3 + v4;

                new_stock.value = (somme + Number(stock)).toFixed(2);

            } else {

                new_stock.value = Number(stock).toFixed(2);
            }
        }

    // On écoute l'événement 'input' sur les 3 champs
        [new_m1, new_m2, new_m3, new_m4].forEach(input => {

            input.addEventListener('input', () => {

                calculNewSomme(stock);

            });

        });

    // On récupère tous les éléments nécessaires
        const batchInput = document.getElementsByName('qty_principale')[0];
        const ingredients = [m1, m2, m3, m4];
        const stockDisplay = document.getElementById('stock-conche');
        const loadBtn = document.getElementById('btn-load');

        function checkWeights() {
            // 1. Calcul de la somme des ingrédients
            let totalIngredients = 0;
            ingredients.forEach(input => {
                totalIngredients += parseFloat(input.value) || 0;
            });

            // 2. Mise à jour de l'affichage du stock (optionnel mais recommandé)
            stockDisplay.value = totalIngredients.toFixed(2);

            // 3. Récupération de la valeur cible du Batch
            const targetBatch = parseFloat(batchInput.value) || 0;

            // 4. Logique d'activation du bouton
            // Le bouton est actif SEULEMENT SI le total == batch ET que le batch est > 0
            if (totalIngredients === targetBatch && targetBatch > 0) {
                loadBtn.disabled = false;
                loadBtn.style.opacity = "1";
                loadBtn.style.cursor = "pointer";
            } else {
                loadBtn.disabled = true;
                loadBtn.style.opacity = "0.5";
                loadBtn.style.cursor = "not-allowed";
            }
        }

        // On écoute les changements sur le champ Batch et tous les ingrédients
        // batchInput.addEventListener('input', checkWeights);
        // ingredients.forEach(input => {
        //     input.addEventListener('input', checkWeights);
        // });
        
    </script>
</body>
</html>