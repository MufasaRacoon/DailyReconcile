<?php

class DashboardService 
{
    private PDO $pdo;

    public function __construct(PDO $pdo) 
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupère tous les équipements indexés par leur code, avec l'OF et le type de chocolat en cours.
     */
    public function getEquipments(): array 
    {
        // Jointure pour récupérer le produit / OF en cours dans chaque équipement
        $stmt = $this->pdo->query("
            SELECT 
                e.id,
                e.code, 
                e.name, 
                e.capacity_kg, 
                e.current_qty_kg, 
                e.status,
                b.of_code_principale,
                b.type_chocolat
            FROM equipment e
            LEFT JOIN (
                SELECT l1.equipment_id, l1.batch_id
                FROM loading l1
                INNER JOIN (
                    SELECT equipment_id, MAX(id) AS max_id 
                    FROM loading 
                    GROUP BY equipment_id
                ) l2 ON l1.id = l2.max_id
            ) last_load ON e.id = last_load.equipment_id
            LEFT JOIN batch b ON last_load.batch_id = b.id
            WHERE e.deleted_at IS NULL
        ");

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $equipments = [];
        foreach ($rows as $row) {
            $equipments[$row['code']] = $row;
        }

        return $equipments;
    }

    /**
     * Formate les informations d'un équipement spécifique
     */
    public function getEquipmentInfo(array $equipments, string $code, float $defaultCap = 0): array 
    {
        if (!isset($equipments[$code])) {
            return [
                'current' => 0,
                'max' => $defaultCap,
                'percent' => 0,
                'status_class' => 'status-idle',
                'of_code' => null,
                'type_chocolat' => null
            ];
        }

        $eq = $equipments[$code];
        $current = (float)$eq['current_qty_kg'];
        $max = (float)$eq['capacity_kg'] > 0 ? (float)$eq['capacity_kg'] : $defaultCap;
        $percent = $max > 0 ? round(($current / $max) * 100) : 0;

        $statusClass = match ($eq['status']) {
            'BUSY', 'TRANSFERING' => 'status-busy',
            'FULL' => 'status-full',
            default => 'status-idle'
        };

        // Si l'équipement est vide (0 kg), on n'affiche pas d'OF/Type
        $hasStock = $current > 0;

        return [
            'current' => $current,
            'max' => $max,
            'percent' => $percent,
            'status_class' => $statusClass,
            'of_code' => $hasStock ? $eq['of_code_principale'] : null,
            'type_chocolat' => $hasStock ? $eq['type_chocolat'] : null
        ];
    }

    public function getRecentLogs(int $limit = 10): array 
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                sm.qty_kg, 
                sm.mouvement_type, 
                sm.reference_type, 
                sm.occurred_at,
                e.name AS equipment_name,
                u.username AS operator_name
            FROM stock_mouvements sm
            LEFT JOIN equipment e ON sm.equipment_id = e.id
            LEFT JOIN users u ON sm.user_id = u.id
            ORDER BY sm.id DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}