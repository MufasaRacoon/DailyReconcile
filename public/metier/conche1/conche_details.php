<?php

require_once __DIR__ . '/../../../app/config/auth.php';
require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/controllers/Conche1_Controller.php';
requireAuthMenu();

$batch_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$batch_id) {
    header('Location: ../../index.php');
    exit;
}

$controller_conche = new Conche1_Controller($pdo);
$details = $controller_conche->get_batch_loading_details($batch_id);

$batch = $details['batch'];
$loadings = $details['loadings'];

if (!$batch) {
    die("Lot introuvable.");
}
?>
<style>
    /* Container de table glassmorphism */
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        margin-top: 15px;
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(255, 255, 255, 0.02);
    }

    .glass-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 0.9rem;
    }

    .glass-table th {
        background: rgba(255, 255, 255, 0.08);
        color: rgba(255, 255, 255, 0.7);
        padding: 12px 16px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .glass-table td {
        padding: 14px 16px;
        color: #ffffff;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        white-space: nowrap;
    }

    .glass-table tbody tr:hover {
        background: rgba(255, 255, 255, 0.04);
    }

    .glass-table tfoot td {
        background: rgba(255, 255, 255, 0.06);
        font-weight: 600;
        border-top: 1px solid rgba(255, 255, 255, 0.15);
    }

    /* Multi-badges pour la table */
    .badge-status {
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .badge-status.loading {
        background: rgba(255, 193, 7, 0.2);
        color: #ffc107;
    }
    .badge-status.finished {
        background: rgba(40, 167, 69, 0.2);
        color: #28a745;
    }
</style>

<div class="glass-container">
    <header style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 25px;">
        <div>
            <h1 style="margin: 0;">Détails des Chargements</h1>
            <span class="info-label" style="margin-top: 5px; display: block;">Équipement : <?= htmlspecialchars((string)($batch['equipment_name'] ?? 'CONCHE 1')) ?></span>
        </div>
        <button class="btn accent" onclick="history.back()" style="width: auto;">
            ← Retour
        </button>
    </header>

    <main>
        <!-- RAPPEL CONTEXTUEL DU BATCH -->
        <div class="info-grid">
            <div class="info-card">
                <span class="info-label">Code OF</span>
                <span class="info-value"><?= htmlspecialchars((string)$batch['of_code_principale']) ?></span>
            </div>
            <div class="info-card">
                <span class="info-label">Type de Chocolat</span>
                <span class="info-value"><?= htmlspecialchars((string)$batch['type_chocolat']) ?></span>
            </div>
            <div class="info-card highlight">
                <span class="info-label">Quantité Cible</span>
                <span class="info-value"><?= number_format((float)$batch['qty_principale'], 2, '.', ' ') ?> kg</span>
            </div>
        </div>

        <h3 style="margin: 25px 0 10px 0; color: rgba(255,255,255,0.9); font-size: 1.1rem;">
            Historique des Apports (`loading`)
        </h3>

        <!-- TABLEAU DES CHARGEMENTS -->
        <?php if (empty($loadings)): ?>
            <div class="stock-display" style="text-align: center; padding: 30px;">
                <span class="info-label">Aucun mouvement enregistre pour ce lot</span>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="glass-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date & Heure</th>
                            <th>Operateur</th>
                            <th>Statut Session</th>
                            <th>Masse Ajoutee</th>
                            <th>Cumul Progressif</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $cumul = 0.0;
                        foreach ($loadings as $index => $load): 
                            $qty = (float)$load['qty_sum'];
                            $cumul += $qty;
                            $dateLoaded = !empty($load['loaded_at']) ? date('d/m/Y H:i:s', strtotime($load['loaded_at'])) : '-';
                        ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= $dateLoaded ?></td>
                                <td><?= htmlspecialchars((string)($load['operator_name'] ?? 'N/A')) ?></td>
                                <td>
                                    <?php if ($load['status_load'] === 'LOADING'): ?>
                                        <span class="badge-status loading">LOADING</span>
                                    <?php else: ?>
                                        <span class="badge-status finished"><?= htmlspecialchars((string)$load['status_load']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight: 600; color: #ffd700;">+ <?= number_format($qty, 2, '.', ' ') ?> kg</td>
                                <td style="color: rgba(255,255,255,0.85);"><?= number_format($cumul, 2, '.', ' ') ?> kg</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" style="text-align: right;">Total Actuel Charge :</td>
                            <td colspan="2" style="color: #28a745; font-size: 1rem;">
                                <?= number_format($cumul, 2, '.', ' ') ?> kg 
                                / <?= number_format((float)$batch['qty_principale'], 2, '.', ' ') ?> kg
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </main>
</div>