<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!verificar_autenticacion()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        obtenerPaquetes();
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        break;
}

function obtenerPaquetes() {
    global $pdo;

    try {
        $sql = "
            SELECT 
                p.*,
                GROUP_CONCAT(i.item SEPARATOR '|') as items
            FROM paquetes_mantenimiento p
            LEFT JOIN items_paquete_mantenimiento i ON p.id = i.paquete_id
            WHERE p.activo = 1
            GROUP BY p.id
            ORDER BY p.codigo ASC
        ";

        $stmt = $pdo->query($sql);
        $paquetes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($paquetes as &$paquete) {
            if ($paquete['items']) {
                $paquete['items'] = explode('|', $paquete['items']);
            } else {
                $paquete['items'] = [];
            }
        }

        echo json_encode(['success' => true, 'data' => $paquetes]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener paquetes: ' . $e->getMessage()]);
    }
}
