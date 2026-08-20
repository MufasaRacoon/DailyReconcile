<?php
require_once __DIR__ . '/../app/config/auth.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/controllers/MachineController.php';
requireAuthMenu();

$dechetController = new MachineController($pdo);
$dechets = $dechetController->getDechetHistory();

// Calcul du total cumulé des déchets
$totalDechetsKg = array_reduce($dechets, function($sum, $item) {
    return $sum + (float)$item['qty_kg'];
}, 0.0);
?>

<div class="glass-container">
    <header style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 25px;">
        <div>
            <h1 style="margin: 0;">Gestion des Déchets & Résidus</h1>
            <span class="info-label" style="margin-top: 5px; display: block;">Suivi des pertes et rebuts de production</span>
        </div>
        <button class="btn accent" onclick="location.href='index.php'" style="width: auto;">
            ← Retour au menu
        </button>
    </header>

    <main>
        <!-- RÉSUMÉ CUMUL -->
        <div class="info-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
            <div class="info-card highlight">
                <span class="info-label">Total Déchets Cumulés</span>
                <span class="info-value" style="color: #ff6b6b; font-size: 1.8rem;">
                    <?= number_format($totalDechetsKg, 2, '.', ' ') ?> kg
                </span>
            </div>
            <div class="info-card">
                <span class="info-label">Nombre de Transferts</span>
                <span class="info-value"><?= count($dechets) ?> transfert(s)</span>
            </div>
        </div>

        <h3 style="margin: 30px 0 15px 0; color: rgba(255,255,255,0.9); font-size: 1.1rem;">
            Historique des Transferts vers Déchets
        </h3>

        <!-- TABLEAU DES DÉCHETS -->
        <?php if (empty($dechets)): ?>
            <div class="stock-display" style="text-align: center; padding: 30px;">
                <span class="info-label">Aucun déchet enregistré pour le moment.</span>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="glass-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date & Heure</th>
                            <th>Provenance (Source)</th>
                            <th>Code OF</th>
                            <th>Type de Chocolat</th>
                            <th>Opérateur</th>
                            <th>Quantité Transferée</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dechets as $index => $row): 
                            $dateTxt = !empty($row['date_transfert']) 
                                ? date('d/m/Y H:i:s', strtotime($row['date_transfert'])) 
                                : '-';
                        ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= $dateTxt ?></td>
                                <td>
                                    <span class="badge-source">
                                        <?= htmlspecialchars((string)($row['source_equipment_name'] ?? 'Inconnu')) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-of">
                                        <?= htmlspecialchars((string)($row['of_code_principale'] ?? 'N/A')) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars((string)($row['type_chocolat'] ?? 'N/A')) ?></td>
                                <td><?= htmlspecialchars((string)($row['operator_name'] ?? 'Système')) ?></td>
                                <td style="font-weight: 700; color: #ff6b6b;">
                                    <?= number_format((float)$row['qty_kg'], 2, '.', ' ') ?> kg
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6" style="text-align: right; font-weight: 600;">Total Général :</td>
                            <td style="color: #ff6b6b; font-weight: 700; font-size: 1.05rem;">
                                <?= number_format($totalDechetsKg, 2, '.', ' ') ?> kg
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </main>
</div>