<?php

class MachineController {
    
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
// Getter id Equipment
    public function getIdConche1(){
        $id = 1;
        return  $id;
    }
    public function getIdConche2(){
        $id = 2;
        return  $id;
    }
    public function getIdChocoTank1(){
        $id = 3;
        return  $id;
    }
    public function getIdChocoTank2(){
        $id = 4;
        return  $id;
    }
    public function getIdBufferTank1(){
        $id = 5;
        return  $id;
    }
    public function getIdBufferTank2(){
        $id = 6;
        return  $id;
    }
    public function getIdSelmiTank1(){
        $id = 7;
        return  $id;
    }
    public function getIdSelmiTank2(){
        $id = 8;
        return  $id;
    }
    public function getIdSelmiTank3(){
        $id = 9;
        return  $id;
    }
    public function getIdDechet(){
        $id = 10;
        return  $id;
    }
    public function getIdQuarantine(){
        $id = 11;
        return  $id;
    }

// Select equipment all 
    public function getEquipment(int $id){
        $stmt = $this->pdo->prepare("
            SELECT * FROM equipment
            WHERE id = ? AND deleted_at IS NULL
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

// find batch in database
    public function find(int $id){
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
                SELECT b.*, e.current_qty_kg, e.capacity_kg, e.status as status_equip, e.id AS equipment_id, l.status as status_load

                FROM loading l

                INNER JOIN batch b
                    ON l.batch_id = b.id

                INNER JOIN equipment e
                    ON l.equipment_id = e.id

                WHERE l.equipment_id = ?
                AND l.unloaded_at IS NULL
                AND l.status IN ('TRANSFERING', 'LAST_TRANSFERING', 'LOADING')

                ORDER BY l.loaded_at DESC
                LIMIT 1
            ");
        $stmt->execute([$equipment_id]);
        return $stmt->fetch();
    }
  
// SELECT OF or SOUS OF TO TRANSFER
    public function getSelectableOF(int $equipment_id){
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

            WHERE sm.equipment_id = ?
            ORDER BY b.id ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$equipment_id]);

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

    // Fonction pour vérifier si un batch a des sous_of associés 
    public function charge_sous_of(int $batch_id){
        $stmt = $this->pdo->prepare("
                SELECT  * 
                FROM sous_of 
                WHERE batch_id = ?;
            ");
        $stmt->execute([$batch_id]);
        $resultat = $stmt->fetchAll();
        if(!empty($resultat)){
            return true;
        } else { return false; }
    }    

// START CHARGE
    // public function createStart(array $data, int $userId, string $machine_name, bool $isConche1){
    //     // Vérification CSRF
    //     // if (!verifyCsrfToken($data['csrf_token'] ?? '')) {
    //     //     return "Action non autorisée (CSRF)";
    //     // }

    //     try {
    //         $this->pdo->beginTransaction();

    //     // INSERT Dans Batch

    //         $stmtBatch = $this->pdo->prepare("
    //             INSERT INTO batch
    //             (date, heure, of_code_principale, type_chocolat, qty_principale, qty_cocoa_nibs, qty_sugar_cane, 
    //             qty_cocoa_butter, qty_lecithin, qty_stock, machine_start, status, created_by)
    //             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    //         ");

    //         $cocoa_nibs = ($data['qty_cocoa_nibs'] === '' ? null : floatval($data['qty_cocoa_nibs']));
    //         $sugar_cane = ($data['qty_sugar_cane'] === '' ? null : floatval($data['qty_sugar_cane']));
    //         $cocoa_butter = ($data['qty_cocoa_butter'] === '' ? null : floatval($data['qty_cocoa_butter']));
    //         $lecithin = ($data['qty_lecithin'] === '' ? null : floatval($data['qty_lecithin']));

    //         if($data['qty_stock'] == $data['qty_principale']){
    //             $status = 'FINISHED_LOADING';
    //         } else if($data['qty_stock'] <= $data['qty_principale']){
    //             $status = 'LOADING';
    //         } else {
    //             $status = 'ISSUES';
    //         }

    //         $stmtBatch->execute([
    //             $data['date'],
    //             $data['heure'],
    //             $data['of_code_principale'],
    //             $data['type_chocolat'],
    //             $data['qty_principale'],
    //             $cocoa_nibs,
    //             $sugar_cane,
    //             $cocoa_butter,
    //             $lecithin,
    //             $data['qty_stock'],
    //             $machine_name,
    //             $status,
    //             $userId
    //         ]);

    //         $batch_id = (int)$this->pdo->lastInsertId();

    //     // Insert dans sous_of

    //         $sous_of_flag = 0;
    //         $stmtSous = $this->pdo->prepare("
    //             INSERT INTO sous_of (batch_id, sous_of_code, sous_type_chocolat)
    //             VALUES (:batch_id, :sous_of_code, :sous_type_chocolat)
    //         ");
    //         // $sousOfArray = $data['sous_of'] === '' ? null : $data['sous_of'];
    //         // $typeChocoArray = $data['sous_type_chocolat'] === '' ? null : $data['sous_type_chocolat'];
    //         $sousOfArray = $data['sous_of'] ?? [];
    //         $typeChocoArray = $data['sous_type_chocolat'] ?? [];

    //         // foreach ($sousOfArray as $index => $sousOf) {
    //         //     $type = $typeChocoArray[$index] ?? null;
    //         //     if (!empty(trim($sousOf)) && !empty(($type))) {

    //         //         $stmtSous->execute([
    //         //             ':batch_id' => $id_batch,
    //         //             ':sous_of_code' => trim($sousOf),
    //         //             ':sous_type_chocolat' => trim($type),
    //         //         ]);
    //         //         // $id_sous_of = (int)$this->pdo->lastInsertId();
    //         //     }
    //         // }
    //         if (!empty($sousOfArray)) {
    //             foreach ($sousOfArray as $index => $sousOf) {
    //                 $sousOf = trim($sousOf);
    //                 $type = trim($typeChocoArray[$index] ?? '');

    //                 // Ignorer lignes vides
    //                 if ($sousOf === '' || $type === '') {
    //                     continue;
    //                 }

    //                 $stmtSous->execute([
    //                     ':batch_id' => $batch_id,
    //                     ':sous_of_code' => $sousOf,
    //                     ':sous_type_chocolat' => $type,
    //                 ]);
    //                 $sous_of_flag = 1;
    //             }
    //         }   


    //     // INSERT Dans loading

    //         if ($isConche1) {
    //             $equipment_id = self::getIdConche1();
    //         } else {
    //             $equipment_id = self::getIdConche2();
    //         }

    //         $stateLoad = 'LOADING';

    //         $stmtLoad = $this->pdo->prepare("
    //             INSERT INTO loading
    //             (batch_id, equipment_id, qty_cocoa_nibs, qty_sugar_cane, 
    //             qty_cocoa_butter, qty_lecithin, qty_sum, status, user_id)
    //             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    //         ");

    //         $stmtLoad->execute([
    //             $batch_id,
    //             $equipment_id,
    //             $cocoa_nibs,
    //             $sugar_cane,
    //             $cocoa_butter,
    //             $lecithin,
    //             $data['qty_stock'],
    //             $stateLoad,
    //             $userId
    //         ]);

    //         $loading_id = (int)$this->pdo->lastInsertId();

    //     // INSERT Dans stock_mouvement
            
    //         $mouvement_type = 'LOAD';
    //         $reference_type = 'LOADING_CONCHE';

    //         $stmtStock = $this->pdo->prepare("
    //             INSERT INTO stock_mouvements
    //             (batch_id, sous_of_flag, equipment_id, mouvement_type, qty_kg, reference_type, reference_id, user_id)
    //             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    //         ");

    //         $stmtStock->execute([
    //             $batch_id,
    //             $sous_of_flag,
    //             $equipment_id,
    //             $mouvement_type,
    //             $data['qty_stock'],
    //             $reference_type,
    //             $loading_id,
    //             $userId
    //         ]);
        

    //     // UPDATE equipment pour la quantité a jour
    //         $state_equip = 'BUSY';
    //         $stmtEquip = $this->pdo->prepare("
    //             UPDATE equipment SET current_qty_kg = ?, status = ? WHERE id = ?
    //         ");

    //         $stmtEquip->execute([
    //             $data['qty_stock'],
    //             $state_equip,
    //             $equipment_id
    //         ]);

    //         $this->pdo->commit();
    //         return true;

    //     } catch (Exception $e) {
    //         $this->pdo->rollBack();
    //         // error_log($e->getMessage());
    //         return $e->getMessage();
    //     }
    // }

    // CREATE AND START BATCH MODULE
    public function createStart(array $data, int $userId, string $machine_name, bool $isConche1): bool {
        try {
            // 1. Identification de l'équipement
            $equipment_id = $isConche1 ? self::getIdConche1() : self::getIdConche2();

            // 2. Contrôle anti-mélange d'OF : Vérifier si l'équipement est disponible
            $stmtCheck = $this->pdo->prepare("
                SELECT COUNT(*) 
                FROM loading 
                WHERE equipment_id = ? AND unloaded_at IS NULL
            ");
            $stmtCheck->execute([$equipment_id]);
            if ($stmtCheck->fetchColumn() > 0) {
                error_log("Erreur createStart() : L'équipement ID {$equipment_id} contient déjà un chargement actif.");
                return false;
            }

            $this->pdo->beginTransaction();

            // 3. Normalisation des quantités d'ingrédients saisis
            $cocoa_nibs   = !empty($data['qty_cocoa_nibs']) ? floatval($data['qty_cocoa_nibs']) : 0.0;
            $sugar_cane   = !empty($data['qty_sugar_cane']) ? floatval($data['qty_sugar_cane']) : 0.0;
            $cocoa_butter = !empty($data['qty_cocoa_butter']) ? floatval($data['qty_cocoa_butter']) : 0.0;
            $lecithin     = !empty($data['qty_lecithin']) ? floatval($data['qty_lecithin']) : 0.0;

            $qty_principale = floatval($data['qty_principale'] ?? 0);
            $qty_stock      = floatval($data['qty_stock'] ?? 0);

            // Somme des ingrédients réellement introduits
            $qty_sum_ingredients = $cocoa_nibs + $sugar_cane + $cocoa_butter + $lecithin;

            // Détermination du statut du lot
            if ($qty_stock == $qty_principale) {
                $batchStatus = 'FINISHED_LOADING';
            } else if ($qty_stock < $qty_principale) {
                $batchStatus = 'LOADING';
            } else {
                $batchStatus = 'ISSUES';
            }

            // 4. INSERT dans BATCH
            $stmtBatch = $this->pdo->prepare("
                INSERT INTO batch (
                    date, heure, of_code_principale, type_chocolat, qty_principale, 
                    qty_cocoa_nibs, qty_sugar_cane, qty_cocoa_butter, qty_lecithin, 
                    qty_stock, machine_start, status, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmtBatch->execute([
                $data['date'],
                $data['heure'],
                $data['of_code_principale'],
                $data['type_chocolat'],
                $qty_principale,
                $cocoa_nibs,
                $sugar_cane,
                $cocoa_butter,
                $lecithin,
                $qty_stock,
                $machine_name,
                $batchStatus,
                $userId
            ]);

            $batch_id = (int)$this->pdo->lastInsertId();

            // 5. INSERT dans SOUS_OF
            $sous_of_flag = 0;
            $sousOfArray = $data['sous_of'] ?? [];
            $typeChocoArray = $data['sous_type_chocolat'] ?? [];

            if (!empty($sousOfArray)) {
                $stmtSous = $this->pdo->prepare("
                    INSERT INTO sous_of (batch_id, sous_of_code, sous_type_chocolat)
                    VALUES (:batch_id, :sous_of_code, :sous_type_chocolat)
                ");

                foreach ($sousOfArray as $index => $sousOf) {
                    $sousOf = trim($sousOf);
                    $type   = trim($typeChocoArray[$index] ?? '');

                    if ($sousOf === '' || $type === '') {
                        continue;
                    }

                    $stmtSous->execute([
                        ':batch_id'          => $batch_id,
                        ':sous_of_code'      => $sousOf,
                        ':sous_type_chocolat'=> $type,
                    ]);
                    $sous_of_flag = 1;
                }
            }

            // 6. INSERT dans LOADING (Début de cycle dans la conche)
            $stmtLoad = $this->pdo->prepare("
                INSERT INTO loading (
                    batch_id, equipment_id, qty_cocoa_nibs, qty_sugar_cane, 
                    qty_cocoa_butter, qty_lecithin, qty_sum, status, user_id, loaded_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'LOADING', ?, NOW())
            ");

            $stmtLoad->execute([
                $batch_id,
                $equipment_id,
                $cocoa_nibs,
                $sugar_cane,
                $cocoa_butter,
                $lecithin,
                $qty_sum_ingredients,
                $userId
            ]);

            $loading_id = (int)$this->pdo->lastInsertId();

            // 7. INSERT dans STOCK_MOUVEMENTS
            $mouvement_type = ($qty_stock == $qty_principale) ? 'LAST_LOAD' : 'LOAD';
            $reference_type = 'LOADING_CONCHE';

            $stmtStock = $this->pdo->prepare("
                INSERT INTO stock_mouvements (
                    batch_id, sous_of_flag, equipment_id, mouvement_type, qty_kg, 
                    reference_type, reference_id, user_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmtStock->execute([
                $batch_id,
                $sous_of_flag,
                $equipment_id,
                $mouvement_type,
                $qty_sum_ingredients,
                $reference_type,
                $loading_id,
                $userId
            ]);

            // 8. UPDATE EQUIPMENT (Stock & Statut selon capacité)
            $stmtCap = $this->pdo->prepare("SELECT capacity_kg FROM equipment WHERE id = ?");
            $stmtCap->execute([$equipment_id]);
            $capacity = (float)$stmtCap->fetchColumn();

            $equipmentStatus = ($qty_stock >= $capacity) ? 'FULL' : 'BUSY';

            $stmtEquip = $this->pdo->prepare("
                UPDATE equipment 
                SET current_qty_kg = ?, status = ? 
                WHERE id = ?
            ");

            $stmtEquip->execute([
                $qty_stock,
                $equipmentStatus,
                $equipment_id
            ]);

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Erreur dans createStart(): " . $e->getMessage());
            return false;
        }
    }

    // LOADING MODULE
    public function loading(int $batch_id, array $data, int $userId, bool $isConche1): bool {
        try {
            $this->pdo->beginTransaction();

            // 1. Nettoyage et sécurisation des quantités ajoutées (Saisie courante)
            $cocoa_nibs_add   = !empty($data['qty_cocoa_nibs']) ? floatval($data['qty_cocoa_nibs']) : 0.0;
            $sugar_cane_add   = !empty($data['qty_sugar_cane']) ? floatval($data['qty_sugar_cane']) : 0.0;
            $cocoa_butter_add = !empty($data['qty_cocoa_butter']) ? floatval($data['qty_cocoa_butter']) : 0.0;
            $lecithin_add     = !empty($data['qty_lecithin']) ? floatval($data['qty_lecithin']) : 0.0;

            // Quantités déjà chargées au préalable
            $cocoa_nibs_prev   = !empty($data['qty_cocoa_nibs_charged']) ? floatval($data['qty_cocoa_nibs_charged']) : 0.0;
            $sugar_cane_prev   = !empty($data['qty_sugar_cane_charged']) ? floatval($data['qty_sugar_cane_charged']) : 0.0;
            $cocoa_butter_prev = !empty($data['qty_cocoa_butter_charged']) ? floatval($data['qty_cocoa_butter_charged']) : 0.0;
            $lecithin_prev     = !empty($data['qty_lecithin_charged']) ? floatval($data['qty_lecithin_charged']) : 0.0;

            // Sommes cumulées pour la table BATCH
            $qty_cocoa_nibs   = $cocoa_nibs_prev + $cocoa_nibs_add;
            $qty_sugar_cane   = $sugar_cane_prev + $sugar_cane_add;
            $qty_cocoa_butter = $cocoa_butter_prev + $cocoa_butter_add;
            $qty_lecithin     = $lecithin_prev + $lecithin_add;

            $qty_stock = floatval($data['qty_stock'] ?? 0);
            $qty_principale = floatval($data['qty_principale'] ?? 0);

            // Détermination du statut de la fournée (BATCH)
            if ($qty_stock == $qty_principale) {
                $batchStatus = 'FINISHED_LOADING';
            } else if ($qty_stock < $qty_principale) {
                $batchStatus = 'LOADING';
            } else {
                $batchStatus = 'ISSUES';
            }

            // 2. UPDATE BATCH
            $stmtBatch = $this->pdo->prepare("
                UPDATE batch SET
                    of_code_principale = ?,
                    type_chocolat = ?,
                    qty_principale = ?,
                    qty_cocoa_nibs = ?,
                    qty_sugar_cane = ?,
                    qty_cocoa_butter = ?,
                    qty_lecithin = ?,
                    qty_stock = ?,
                    status = ?
                WHERE id = ?
            ");

            $stmtBatch->execute([
                $data['of_code_principale'],
                $data['type_chocolat'],
                $qty_principale,
                $qty_cocoa_nibs,
                $qty_sugar_cane,
                $qty_cocoa_butter,
                $qty_lecithin,
                $qty_stock,
                $batchStatus,
                $batch_id
            ]);

            // 3. Remplacement des SOUS_OF
            $stmtDeleteSous = $this->pdo->prepare("DELETE FROM sous_of WHERE batch_id = ?");
            $stmtDeleteSous->execute([$batch_id]);

            $sous_of_flag = 0;
            $sousOfArray = $data['sous_of'] ?? [];
            $typeChocoArray = $data['sous_type_chocolat'] ?? [];

            if (!empty($sousOfArray)) {
                $stmtSous = $this->pdo->prepare("
                    INSERT INTO sous_of (batch_id, sous_of_code, sous_type_chocolat)
                    VALUES (:batch_id, :sous_of_code, :sous_type_chocolat)
                ");

                foreach ($sousOfArray as $index => $sousOf) {
                    $sousOf = trim($sousOf);
                    $type = trim($typeChocoArray[$index] ?? '');

                    if ($sousOf === '' || $type === '') {
                        continue;
                    }

                    $stmtSous->execute([
                        ':batch_id' => $batch_id,
                        ':sous_of_code' => $sousOf,
                        ':sous_type_chocolat' => $type,
                    ]);
                    $sous_of_flag = 1;
                }
            }

            // 4. Identification de l'équipement
            $equipment_id = $isConche1 ? self::getIdConche1() : self::getIdConche2();
            $qty_added_this_time = $cocoa_nibs_add + $sugar_cane_add + $cocoa_butter_add + $lecithin_add;

            // 5. INSERT dans LOADING
            $stmtLoad = $this->pdo->prepare("
                INSERT INTO loading
                (batch_id, equipment_id, qty_cocoa_nibs, qty_sugar_cane, 
                qty_cocoa_butter, qty_lecithin, qty_sum, status, user_id, loaded_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'LOADING', ?, NOW())
            ");

            $stmtLoad->execute([
                $batch_id,
                $equipment_id,
                $cocoa_nibs_add,
                $sugar_cane_add,
                $cocoa_butter_add,
                $lecithin_add,
                $qty_added_this_time,
                $userId
            ]);

            $id_loading = (int)$this->pdo->lastInsertId();

            // 6. INSERT dans STOCK_MOUVEMENTS
            if ($qty_stock == $qty_principale) {
                $mouvement_type = 'LAST_LOAD';
                $reference_type = 'LOADING_CONCHE';
            } else if ($qty_stock < $qty_principale) {
                $mouvement_type = 'LOAD';
                $reference_type = 'LOADING_CONCHE';
            } else {
                $mouvement_type = 'ISSUES_CONCHE';
                $reference_type = 'ISSUES_CONCHE';
            }

            $stmtStock = $this->pdo->prepare("
                INSERT INTO stock_mouvements
                (batch_id, sous_of_flag, equipment_id, mouvement_type, qty_kg, reference_type, reference_id, user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmtStock->execute([
                $batch_id,
                $sous_of_flag,
                $equipment_id,
                $mouvement_type,
                $qty_added_this_time,
                $reference_type,
                $id_loading,
                $userId
            ]);

            // 7. UPDATE EQUIPMENT (Quantité + Statut)
            // Récupération de la capacité pour mettre le bon état
            $stmtCap = $this->pdo->prepare("SELECT capacity_kg FROM equipment WHERE id = ?");
            $stmtCap->execute([$equipment_id]);
            $capacity = (float)$stmtCap->fetchColumn();

            $equipmentStatus = ($qty_stock >= $capacity) ? 'FULL' : 'BUSY';

            $stmtEquip = $this->pdo->prepare("
                UPDATE equipment 
                SET current_qty_kg = ?, status = ? 
                WHERE id = ?
            ");
            $stmtEquip->execute([
                $qty_stock,
                $equipmentStatus,
                $equipment_id
            ]);

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Erreur dans loading(): " . $e->getMessage());
            return false;
        }
    }

// // LOADING MODULE
//     public function loading(int $batch_id, array $data, int $userId, bool $isConche1){
//         try {
//             $this->pdo->beginTransaction();

//             // New quantity ingredient
//             $cocoa_nibs = ($data['qty_cocoa_nibs'] === '' ? null : floatval($data['qty_cocoa_nibs']));
//             $sugar_cane = ($data['qty_sugar_cane'] === '' ? null : floatval($data['qty_sugar_cane']));
//             $cocoa_butter = ($data['qty_cocoa_butter'] === '' ? null : floatval($data['qty_cocoa_butter']));
//             $lecithin = ($data['qty_lecithin'] === '' ? null : floatval($data['qty_lecithin']));
            

//             $cocoa_nibs_charged = ($data['qty_cocoa_nibs_charged'] === '' ? null : floatval($data['qty_cocoa_nibs_charged']));
//             $sugar_cane_charged = ($data['qty_sugar_cane_charged'] === '' ? null : floatval($data['qty_sugar_cane_charged']));
//             $cocoa_butter_charged = ($data['qty_cocoa_butter_charged'] === '' ? null : floatval($data['qty_cocoa_butter_charged']));
//             $lecithin_charged = ($data['qty_lecithin_charged'] === '' ? null : floatval($data['qty_lecithin_charged']));

//             if($data['qty_stock'] == $data['qty_principale']){
//                 $status = 'FINISHED_LOADING';
//             } else if($data['qty_stock'] <= $data['qty_principale']){
//                 $status = 'LOADING';
//             } else {
//                 $status = 'ISSUES';
//             }

//         // UPDATE batch
                        
//             $qty_cocoa_nibs = $cocoa_nibs + $cocoa_nibs_charged;
//             $qty_sugar_cane = $sugar_cane + $sugar_cane_charged;
//             $qty_cocoa_butter = $cocoa_butter + $cocoa_butter_charged;
//             $qty_lecithin = $lecithin + $lecithin_charged;
            
//             $stmtBatch = $this->pdo->prepare("
//                 UPDATE batch SET
//                     of_code_principale = ?,
//                     type_chocolat = ?,
//                     qty_principale = ?,
//                     qty_cocoa_nibs = ?,
//                     qty_sugar_cane = ?,
//                     qty_cocoa_butter = ?,
//                     qty_lecithin = ?,
//                     qty_stock = ?,
//                     status = ?
//                 WHERE id = ?
//             ");

//             $stmtBatch->execute([
//                 $data['of_code_principale'],
//                 $data['type_chocolat'],
//                 $data['qty_principale'],
//                 $qty_cocoa_nibs,
//                 $qty_sugar_cane,
//                 $qty_cocoa_butter,
//                 $qty_lecithin,
//                 $data['qty_stock'],
//                 $status,
//                 $batch_id
//             ]);

//         // DELETE sous_of
//             $stmtDeleteSous = $this->pdo->prepare("
//                 DELETE FROM sous_of
//                 WHERE batch_id = ?
//             ");

//             $stmtDeleteSous->execute([$batch_id]);

//         // INSERT nouveau sous_of
//             $sous_of_flag = 0;
//             $sousOfArray = $data['sous_of'] ?? [];
//             $typeChocoArray = $data['sous_type_chocolat'] ?? [];

//             if (!empty($sousOfArray)) {

//                 $stmtSous = $this->pdo->prepare("
//                     INSERT INTO sous_of (
//                         batch_id,
//                         sous_of_code,
//                         sous_type_chocolat
//                     )
//                     VALUES (
//                         :batch_id,
//                         :sous_of_code,
//                         :sous_type_chocolat
//                     )
//                 ");

//                 foreach ($sousOfArray as $index => $sousOf) {

//                     $sousOf = trim($sousOf);

//                     $type = trim($typeChocoArray[$index] ?? '');

//                     if ($sousOf === '' || $type === '') {
//                         continue;
//                     }

//                     $stmtSous->execute([
//                         ':batch_id' => $batch_id,
//                         ':sous_of_code' => $sousOf,
//                         ':sous_type_chocolat' => $type,
//                     ]);
//                     $sous_of_flag = 1;
//                 }
//             }
            
//         // INSERT Dans loading

//             if ($isConche1) {
//                 $equipment_id = self::getIdConche1();
//             } else {
//                 $equipment_id = self::getIdConche2();
//             }

//             $qty_sum =  ($cocoa_nibs ?? 0) +
//                         ($sugar_cane ?? 0) +
//                         ($cocoa_butter ?? 0) +
//                         ($lecithin ?? 0);


//             $stmtLoad = $this->pdo->prepare("
//                 INSERT INTO loading
//                 (batch_id, equipment_id, qty_cocoa_nibs, qty_sugar_cane, 
//                 qty_cocoa_butter, qty_lecithin, qty_sum, status, user_id)
//                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
//             ");

//             $stateLoad = 'LOADING';

//             $stmtLoad->execute([
//                 $batch_id,
//                 $equipment_id,
//                 $cocoa_nibs,
//                 $sugar_cane,
//                 $cocoa_butter,
//                 $lecithin,
//                 $qty_sum,
//                 $stateLoad,
//                 $userId
//             ]);

//             $id_loading = (int)$this->pdo->lastInsertId();

//         // INSERT Dans stock_mouvement
             
//             if ($data['qty_stock'] == $data['qty_principale']) {
//                 $mouvement_type = 'LAST_LOAD';
//                 $reference_type = 'LOADING_CONCHE';
//             } else if ($data['qty_stock'] < $data['qty_principale']){
//                 $mouvement_type = 'LOAD';
//                 $reference_type = 'LOADING_CONCHE';
//             } else {
//                 $mouvement_type = 'ISSUES_CONCHE';
//                 $reference_type = 'ISSUES_CONCHE';
//             }

//             $stmtStock = $this->pdo->prepare("
//                 INSERT INTO stock_mouvements
//                 (batch_id, sous_of_flag, equipment_id, mouvement_type, qty_kg,  reference_type, reference_id, user_id)
//                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)
//             ");

//             $stmtStock->execute([
//                 $batch_id,
//                 $sous_of_flag,
//                 $equipment_id,
//                 $mouvement_type,
//                 $qty_sum,
//                 $reference_type,
//                 $id_loading,
//                 $userId
//             ]);

//         // UPDATE equipment pour la quantité a jour

//             $stmtEquip = $this->pdo->prepare("
//                 UPDATE equipment SET current_qty_kg = ? WHERE id = ?
//             ");

//             $stmtEquip->execute([
//                 $data['qty_stock'],
//                 $equipment_id
//             ]);

//             $this->pdo->commit();
//             return true;

//         } catch (Exception $e) {
//             $this->pdo->rollBack();
//             // error_log($e->getMessage());
//             return $e->getMessage();
//         }
//     }

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
                    WHERE l.equipment_id = ? AND l.unloaded_at IS NULL
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

            // $stateSource = ($nouvelleQtySource <= 0) ? 'IDLE' : 'BUSY';
            // $unloaded = ($nouvelleQtySource <= 0) ? 1 : 0;

            $isSourceEmpty = ($nouvelleQtySource <= 0);
            $stateSource = $isSourceEmpty ? 'IDLE' : 'BUSY';

            $stmtUpdateSource = $this->pdo->prepare("
                UPDATE equipment SET current_qty_kg = ?, status = ? WHERE id = ?
            ");
            $stmtUpdateSource->execute([$nouvelleQtySource, $stateSource, $sourceId]);

            //  Si la source est totalement vidée, on clôture son chargement actif
            if ($isSourceEmpty) {
                $stmtCloseSourceLoading = $this->pdo->prepare("
                    UPDATE loading 
                    SET unloaded_at = NOW(), status = 'FINISHED' 
                    WHERE equipment_id = ? AND unloaded_at IS NULL
                ");
                $stmtCloseSourceLoading->execute([$sourceId]);
            }

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
            $idTransfert = (int)$this->pdo->lastInsertId();

            // 7. Historisation dans LOADING
            $loadingStatus = $isSourceEmpty ? 'LAST_TRANSFERING' : 'TRANSFERING';

            // if($loadingStatus == 'LAST_TRANSFERING'){
            //     $stmtLoad = $this->pdo->prepare("
            //     INSERT INTO loading (batch_id, equipment_id, status, user_id, loaded_at, unloaded_at)
            //     VALUES (?, ?, ?, ?, null, NOW())
            //     ");
            // } else {
            //     $stmtLoad = $this->pdo->prepare("
            //     INSERT INTO loading (batch_id, equipment_id, status, user_id, loaded_at)
            //     VALUES (?, ?, ?, ?, NOW())
            //     ");
            // }
            
            // $stmtLoad->execute([$batch_id, $destId, $loadingStatus, $userId]);
            // // $idLoading = (int)$this->pdo->lastInsertId();

            // La destination reçoit du produit, donc loaded_at = NOW() et unloaded_at = NULL
            $stmtLoad = $this->pdo->prepare("
                INSERT INTO loading (batch_id, equipment_id, status, user_id, loaded_at, unloaded_at)
                VALUES (?, ?, ?, ?, NOW(), NULL)
            ");
            $stmtLoad->execute([$batch_id, $destId, $loadingStatus, $userId]);
            // $idLoading = (int)$this->pdo->lastInsertId();

            // 8. Historisation STOCK_MOUVEMENTS (IN & OUT)
            $sousOfFlag = ($sousOfId === null) ? 1 : 0;
            
            $stmtStock = $this->pdo->prepare("
                INSERT INTO stock_mouvements 
                (batch_id, sous_of_flag, equipment_id, mouvement_type, qty_kg, reference_type, reference_id, user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            // Movement IN (Destination)
            $stmtStock->execute([$batch_id, $sousOfFlag, $destId, 'TRANSFER', $poidsTransfert, 'TRANSFER_IN', $idTransfert, $userId]);
            // Movement OUT (Source)
            $stmtStock->execute([$batch_id, $sousOfFlag, $sourceId, 'TRANSFER', $poidsTransfert, 'TRANSFER_OUT', $idTransfert, $userId]);

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
   
// Transfert to trash or quarantine
    public function transfertToTrashOrQuarantine(int $batch_id, array $data, int $userId){
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
            
            $nouvelleQtyDest = $dest['current_qty_kg'] + $poidsTransfert;

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

// Dechet
    /**
     * Récupère l'historique des transferts vers l'équipement Déchets.
     */
    public function getDechetHistory(): array
    {
        $stmt = $this->pdo->prepare("
        SELECT 
            sm.id AS mouvement_id,
            sm.qty_kg,
            sm.created_at AS date_transfert,
            e_source.name AS source_equipment_name,
            e_source.code AS source_equipment_code,
            b.of_code_principale,
            b.type_chocolat,
            u.username AS operator_name
        FROM stock_mouvements sm

        -- Jointure sur la table transfer via reference_id
        INNER JOIN `transfert` t 
            ON sm.reference_id = t.id AND sm.mouvement_type = 'transfer'

        -- Équipement DESTINATION (doit être l'équipement Déchets)
        INNER JOIN equipment e_dest 
            ON t.destination_equipment_id = e_dest.id

        -- Équipement SOURCE (d'où proviennent les résidus/déchets)
        INNER JOIN equipment e_source 
            ON t.source_equipment_id = e_source.id

        -- Récupération du batch / OF lié au transfert
        LEFT JOIN batch b 
            ON t.batch_id = b.id

        -- Opérateur ayant effectué le mouvement
        LEFT JOIN users u 
            ON sm.user_id = u.id

        WHERE e_dest.code = 'TRASH' -- Code de ton équipement déchet
        AND sm.reference_type = 'TRANSFER_OUT' -- Côté sortie de l'équipement source
        ORDER BY sm.occurred_at DESC
        ");

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}