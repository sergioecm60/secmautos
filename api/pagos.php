<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!verificar_autenticacion()) {
    json_response(['success' => false, 'message' => 'No autenticado'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            $sql = "
                SELECT
                    p.*,
                    v.patente, v.marca, v.modelo
                FROM pagos p
                JOIN vehiculos v ON p.vehiculo_id = v.id
            ";

            $params = [];

            if (isset($_GET['vehiculo_id'])) {
                $sql .= " WHERE p.vehiculo_id = ?";
                $params[] = (int)$_GET['vehiculo_id'];
            }

            $sql .= " ORDER BY p.fecha_vencimiento ASC, p.created_at DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            json_response(['success' => true, 'data' => $pagos]);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Error al obtener pagos: ' . $e->getMessage()], 500);
        }
        break;
    
    case 'POST':
        if (!verificar_csrf($_POST['csrf_token'] ?? '')) {
            json_response(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $vehiculo_id = (int)($_POST['vehiculo_id'] ?? 0);
            $tipo = sanitizar_input($_POST['tipo'] ?? '');
            $fecha_vencimiento = $_POST['fecha_vencimiento'] ?? '';
            $fecha_pago = !empty($_POST['fecha_pago']) ? $_POST['fecha_pago'] : null;
            $monto = (float)($_POST['monto'] ?? 0);
            $comprobante = sanitizar_input($_POST['comprobante'] ?? '');
            $observaciones = sanitizar_input($_POST['observaciones'] ?? '');
            $pagado = isset($_POST['pagado']) ? 1 : 0;

            if (empty($vehiculo_id) || empty($tipo) || empty($fecha_vencimiento)) {
                json_response(['success' => false, 'message' => 'Vehículo, tipo y fecha de vencimiento son obligatorios'], 400);
            }

            $stmt = $pdo->prepare("
                INSERT INTO pagos (vehiculo_id, tipo, fecha_vencimiento, fecha_pago, monto, comprobante, observaciones, pagado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$vehiculo_id, $tipo, $fecha_vencimiento, $fecha_pago, $monto, $comprobante, $observaciones, $pagado]);

            registrarLog($_SESSION['usuario_id'], 'REGISTRAR_PAGO', 'pagos', "Pago registrado para vehículo $vehiculo_id", $pdo);

            json_response(['success' => true, 'message' => 'Pago registrado exitosamente']);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Error al registrar pago: ' . $e->getMessage()], 500);
        }
        break;

    case 'PUT':
        parse_str(file_get_contents('php://input'), $_PUT);

        if (!verificar_csrf($_PUT['csrf_token'] ?? '')) {
            json_response(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $id = (int)($_PUT['id'] ?? 0);

            if (empty($id)) {
                json_response(['success' => false, 'message' => 'ID de pago requerido'], 400);
            }

            $stmt = $pdo->prepare("
                UPDATE pagos
                SET pagado = 1,
                    fecha_pago = COALESCE(?, fecha_pago)
                WHERE id = ?
            ");
            $stmt->execute([$_PUT['fecha_pago'] ?? date('Y-m-d'), $id]);

            if ($stmt->rowCount() === 0) {
                json_response(['success' => false, 'message' => 'Pago no encontrado'], 404);
            }

            registrarLog($_SESSION['usuario_id'], 'MARCAR_PAGO_PAGADO', 'pagos', "Pago $id marcado como pagado", $pdo);

            json_response(['success' => true, 'message' => 'Pago marcado como pagado']);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Error al actualizar pago: ' . $e->getMessage()], 500);
        }
        break;

    case 'DELETE':
        parse_str(file_get_contents('php://input'), $_DELETE);

        if (!verificar_csrf($_DELETE['csrf_token'] ?? '')) {
            json_response(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $id = (int)($_DELETE['id'] ?? 0);

            if (empty($id)) {
                json_response(['success' => false, 'message' => 'ID de pago requerido'], 400);
            }

            $stmt = $pdo->prepare("DELETE FROM pagos WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                json_response(['success' => false, 'message' => 'Pago no encontrado'], 404);
            }

            registrarLog($_SESSION['usuario_id'], 'ELIMINAR_PAGO', 'pagos', "Pago $id eliminado", $pdo);

            json_response(['success' => true, 'message' => 'Pago eliminado exitosamente']);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Error al eliminar pago: ' . $e->getMessage()], 500);
        }
        break;
    
    default:
        json_response(['success' => false, 'message' => 'Método no permitido'], 405);
        break;
}
