<?php

class Selmi_tank3_Controller {

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function show(int $id){
        $stmt = $this->pdo->prepare("
            SELECT * FROM equipment
            WHERE id = ? AND deleted_at IS NULL
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function find(int $id)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM batch
            WHERE id = ? AND completed_at IS NULL
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

// SELECT BATCH EN COURS DANS EQUIPMENT

    public function get_batch(int $equipment_id){
        $stmt = $this->pdo->prepare("
                SELECT 
                    b.*, 
                    e.current_qty_kg, 
                    e.capacity_kg, 
                    e.status AS status_equip, 
                    e.id AS equipment_id, 
                    l.status AS status_load
                FROM loading l

                INNER JOIN batch b 
                    ON l.batch_id = b.id
                INNER JOIN equipment e 
                    ON l.equipment_id = e.id

                WHERE l.equipment_id = ?
                AND l.status IN ('TRANSFERING', 'LAST_TRANSFERING')
                ORDER BY l.id DESC
                LIMIT 1;
            ");
        $stmt->execute([$equipment_id]);
        return $stmt->fetch();
    }

// SELECT BATCH EN COURS DANS EQUIPMENT

    public function get_sous_of(int $batch_id){
        $stmt = $this->pdo->prepare("
                SELECT sous_of_code,
                       sous_type_chocolat

                FROM sous_of 

                WHERE batch_id = ?
            ");
        $stmt->execute([$batch_id]);
        return $stmt->fetch();
    }

// SELECT OF TO TRANSFER

    public function getSelectableOF()
    {
        $sql = "
            SELECT
                b.id AS batch_id,
                b.of_code_principale,
                b.type_chocolat,
                s.id AS sous_of_id,
                s.sous_of_code,
                s.sous_type_chocolat

            FROM batch b

            INNER JOIN stock_mouvements sm
                ON b.id = sm.batch_id

            LEFT JOIN sous_of s
                ON b.id = s.batch_id

            WHERE sm.equipment_id = 7
            ORDER BY b.id ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $results = $stmt->fetchAll();

        $formatted = [];

        foreach ($results as $row) {

            $batchId = $row['batch_id'];

            if (!isset($formatted[$batchId])) {
                $formatted[$batchId] = [
                    'batch_id' => $batchId,
                    'of_principal' => $row['of_code_principale'],
                    'type_chocolat' => $row['type_chocolat'],
                    'sous_of' => [],
                    'sous_type_chocolat' => []
                ];
            }

            if (!empty($row['sous_of_code'])) {
                $formatted[$batchId]['sous_of'][] = [
                    'id' => $row['sous_of_id'],
                    'code' => $row['sous_of_code'],
                    'type' => $row['sous_type_chocolat']
                ];
            }
        }

        return $formatted;
    }

// SELECT DATA OF EQUIPMENT CONCHE 1

    public function getDataMachine(int $id_equipment){
        $stmt = $this->pdo->prepare("
                SELECT

                e.id AS equipment_id,
                e.name,
                e.current_qty_kg,
                e.capacity_kg,

                b.id AS batch_id,
                b.of_code_principale,
                b.type_chocolat,

                el.loaded_qty_kg,
                el.loaded_at

            FROM equipment e

            LEFT JOIN loading el
                ON e.id = el.equipment_id
                AND el.status = 'ACTIVE'

            LEFT JOIN batch b
                ON el.batch_id = b.id

            ORDER BY e.name ASC
            ");
        $stmt->execute([$id_equipment]);
        return $stmt->fetchAll();
    }

// SELECT ALL EQUIPMENT

    public function getMachine(){
        $stmt = $this->pdo->prepare("
                SELECT  * 
                FROM equipment 
                WHERE deleted_at is NULL;
            ");
            $stmt->execute();
            return $stmt->fetchAll();
    }
    
    public function can_transfer(array $batch){
        return $batch['status'] === 'READY_TRANSFER';
    }

    public function charge_sous_of(int $batch_id){
        $stmt = $this->pdo->prepare("
                SELECT  * 
                FROM sous_of 
                WHERE batch_id = ?;
            ");
        $stmt->execute([$batch_id]);
        return $stmt->fetchAll();
    }
    
    // Fonction get quantity actuel dans l'equipment
    public function get_current_qty(int $equipment_id){
        $stmt = $this->pdo->prepare("
                SELECT current_qty_kg, capacity_kg
                FROM equipment 
                WHERE id = ?;
            ");
        $stmt->execute([$equipment_id]);
        $result = $stmt->fetch();
        return $result;
    }

// Transfer module

    public function transfert(int $batch_id, array $data, int $userId){
        try {
            $this->pdo->beginTransaction();

            $sourceId = (int)$data['equipment_id'];
            $destId = (int)$data['destination'];
            $poidsTransfert = (float)$data['poids_transfert'];
            $sousOfId = !empty($data['of_code']) ? (int)$data['of_code'] : null;

            // 1. Validation de base des entrées
            if ($poidsTransfert <= 0) {
                throw new Exception("Le poids à transférer doit être supérieur à 0 kg.");
            }

            // 2. Récupération et Verrouillage (FOR UPDATE) de l'équipement Source
            $stmtSource = $this->pdo->prepare("
                SELECT id, current_qty_kg, capacity_kg, status 
                FROM equipment 
                WHERE id = ? AND deleted_at IS NULL 
                FOR UPDATE
            ");
            $stmtSource->execute([$sourceId]);
            $source = $stmtSource->fetch(PDO::FETCH_ASSOC);

            if (!$source) {
                throw new Exception("Équipement source introuvable.");
            }

            // CONDITION 1 : Stock source suffisant ?
            if ($poidsTransfert > $source['current_qty_kg']) {
                throw new Exception("Impossible : Le poids demandé (" . $poidsTransfert . " kg) dépasse le stock disponible dans l'équipement source (" . $source['current_qty_kg'] . " kg).");
            }

            // 3. Récupération et Verrouillage de l'équipement Destination
            $stmtDest = $this->pdo->prepare("
                SELECT id, current_qty_kg, capacity_kg, status 
                FROM equipment 
                WHERE id = ? AND deleted_at IS NULL 
                FOR UPDATE
            ");
            $stmtDest->execute([$destId]);
            $dest = $stmtDest->fetch(PDO::FETCH_ASSOC);

            if (!$dest) {
                throw new Exception("Équipement de destination introuvable.");
            }

            // CONDITION 2 : Capacité max de la destination dépassée ?
            $nouvelleQtyDest = $dest['current_qty_kg'] + $poidsTransfert;
            if ($nouvelleQtyDest > $dest['capacity_kg']) {
                throw new Exception("Impossible : Ce transfert ferait dépasser la capacité maximale de l'équipement de destination (" . $dest['capacity_kg'] . " kg max, actuel : " . $dest['current_qty_kg'] . " kg).");
            }

            // CONDITION 3 : Vérification du même OF si l'équipement destination n'est PAS vide
            if ($dest['current_qty_kg'] > 0) {
                // Récupérer le dernier Batch/OF présent dans cet équipement destination via la table loading
                $stmtCheckOF = $this->pdo->prepare("
                    SELECT l.batch_id, b.of_code_principale 
                    FROM loading l
                    JOIN batch b ON l.batch_id = b.id
                    WHERE l.equipment_id = ? AND l.status IN ('TRANSFERING', 'LAST_TRANSFERING')
                    ORDER BY l.id DESC LIMIT 1
                ");
                $stmtCheckOF->execute([$destId]);
                $currentDestBatch = $stmtCheckOF->fetch(PDO::FETCH_ASSOC);

                if ($currentDestBatch && $currentDestBatch['batch_id'] != $batch_id) {
                    throw new Exception("Impossible : L'équipement de destination contient déjà du produit associé à l'OF n°" . $currentDestBatch['of_code_principale'] . ". Vous ne pouvez pas mélanger deux OFs différents.");
                }
            }

            // --- EXÉCUTION DU TRANSFERT ---

            // 4. Mise à jour Équipement Destination
            $stateDestination = ($nouvelleQtyDest >= $dest['capacity_kg']) ? 'FULL' : 'BUSY';
            $stmtUpdateDest = $this->pdo->prepare("
                UPDATE equipment SET current_qty_kg = ?, status = ? WHERE id = ?
            ");
            $stmtUpdateDest->execute([$nouvelleQtyDest, $stateDestination, $destId]);

            // 5. Mise à jour Équipement Source
            $nouvelleQtySource = $source['current_qty_kg'] - $poidsTransfert;
            $stateSource = ($nouvelleQtySource <= 0) ? 'IDLE' : 'BUSY';
            $unloaded = ($nouvelleQtySource <= 0) ? 1 : 0;

            $stmtUpdateSource = $this->pdo->prepare("
                UPDATE equipment SET current_qty_kg = ?, status = ? WHERE id = ?
            ");
            $stmtUpdateSource->execute([$nouvelleQtySource, $stateSource, $sourceId]);

            // 6. Historisation dans la table TRANSFER
            $stmtTransfer = $this->pdo->prepare("
                INSERT INTO transfert (
                    batch_id, sous_of_code_id, source_equipment_id, destination_equipment_id, 
                    user_id, qty_kg_transfered, transferred_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmtTransfer->execute([
                $batch_id,
                $sousOfId,
                $sourceId,
                $destId,
                $userId,
                $poidsTransfert
            ]);

            // 7. Historisation dans LOADING
            $loadingStatus = ($unloaded === 1) ? 'LAST_TRANSFERING' : 'TRANSFERING';
            $stmtLoad = $this->pdo->prepare("
                INSERT INTO loading (batch_id, equipment_id, status, user_id, loaded_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmtLoad->execute([$batch_id, $destId, $loadingStatus, $userId]);
            $idLoading = (int)$this->pdo->lastInsertId();

            // 8. Historisation STOCK_MOUVEMENTS (IN & OUT)
            $sousOfFlag = ($sousOfId === null) ? 1 : 0;
            
            $stmtStock = $this->pdo->prepare("
                INSERT INTO stock_mouvements 
                (batch_id, sous_of_flag, equipment_id, mouvement_type, qty_kg, reference_type, reference_id, user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            // Movement IN (Destination)
            $stmtStock->execute([$batch_id, $sousOfFlag, $destId, 'TRANSFER', $poidsTransfert, 'TRANSFER_IN', $idLoading, $userId]);
            // Movement OUT (Source)
            $stmtStock->execute([$batch_id, $sousOfFlag, $sourceId, 'TRANSFER', $poidsTransfert, 'TRANSFER_OUT', $idLoading, $userId]);

            // 9. Update Status BATCH
            $stmtBatch = $this->pdo->prepare("
                UPDATE batch SET status = 'TRANSFERED' WHERE id = ? AND completed_at IS NULL
            ");
            $stmtBatch->execute([$batch_id]);

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            // error_log($e->getMessage());
            return $e->getMessage();
        }
    }
}