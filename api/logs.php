<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!verificar_autenticacion()) {
    json_response(['success' => false, 'message' => 'No autenticado'], 401);
}

// Solo administradores pueden ver logs
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['superadmin', 'admin'])) {
    json_response(['success' => false, 'message' => 'No tiene permisos para ver los logs'], 403);
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = ($page - 1) * $limit;

            // Construir la consulta con filtros opcionales
            $sql = "
                SELECT
                    l.*,
                    u.username as usuario_username,
                    u.email as usuario_email,
                    u.nombre as usuario_nombre,
                    u.apellido as usuario_apellido
                FROM logs l
                LEFT JOIN usuarios u ON l.usuario_id = u.id
                WHERE 1=1
            ";

            $params = [];

            // Filtro por usuario
            if (!empty($_GET['usuario_id'])) {
                $sql .= " AND l.usuario_id = ?";
                $params[] = (int)$_GET['usuario_id'];
            }

            // Filtro por acción
            if (!empty($_GET['accion'])) {
                $sql .= " AND l.accion = ?";
                $params[] = sanitizar_input($_GET['accion']);
            }

            // Filtro por entidad/módulo
            if (!empty($_GET['entidad'])) {
                $sql .= " AND l.entidad = ?";
                $params[] = sanitizar_input($_GET['entidad']);
            }

            // Filtro por fecha desde
            if (!empty($_GET['fecha_desde'])) {
                $sql .= " AND DATE(l.created_at) >= ?";
                $params[] = $_GET['fecha_desde'];
            }

            // Filtro por fecha hasta
            if (!empty($_GET['fecha_hasta'])) {
                $sql .= " AND DATE(l.created_at) <= ?";
                $params[] = $_GET['fecha_hasta'];
            }

            // Contar total de registros
            $countSql = "SELECT COUNT(*) as total FROM (" . $sql . ") as subquery";
            $stmtCount = $pdo->prepare($countSql);
            $stmtCount->execute($params);
            $total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];

            // Obtener registros paginados
            $sql .= " ORDER BY l.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            json_response([
                'success' => true,
                'data' => $logs,
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ]);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Error al obtener logs: ' . $e->getMessage()], 500);
        }
        break;

    default:
        json_response(['success' => false, 'message' => 'Método no permitido'], 405);
        break;
}
