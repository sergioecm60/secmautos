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
                    v.patente, v.marca, v.modelo,
                    e.nombre as nombre_empleado, e.apellido as apellido_empleado,
                    m.motivo as motivo_multa, m.acta_numero as numero_acta_multa,
                    t.numero_dispositivo as numero_dispositivo_telepase
                FROM pagos p
                JOIN vehiculos v ON p.vehiculo_id = v.id
                LEFT JOIN empleados e ON p.empleado_id = e.id
                LEFT JOIN multas m ON p.multa_id = m.id
                LEFT JOIN telepases t ON p.telepase_id = t.id
            ";

            $params = [];

            if (isset($_GET['vehiculo_id'])) {
                $sql .= " WHERE p.vehiculo_id = ?";
                $params[] = (int)$_GET['vehiculo_id'];
            }

            if (isset($_GET['empleado_id'])) {
                $sql .= (isset($_GET['vehiculo_id'])) ? " AND" : " WHERE";
                $sql .= " p.empleado_id = ?";
                $params[] = (int)$_GET['empleado_id'];
            }

            if (isset($_GET['tipo'])) {
                $sql .= (isset($_GET['vehiculo_id']) || isset($_GET['empleado_id'])) ? " AND" : " WHERE";
                $sql .= " p.tipo = ?";
                $params[] = sanitizar_input($_GET['tipo']);
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
            $aseguradora = sanitizar_input($_POST['aseguradora'] ?? '');
            $poliza_numero = sanitizar_input($_POST['poliza_numero'] ?? '');
            $fecha_inicio = !empty($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : null;
            $fecha_vencimiento = $_POST['fecha_vencimiento'] ?? '';
            $fecha_pago = !empty($_POST['fecha_pago']) ? $_POST['fecha_pago'] : null;
            $monto = (float)($_POST['monto'] ?? 0);
            $comprobante = sanitizar_input($_POST['comprobante'] ?? '');
            $observaciones = sanitizar_input($_POST['observaciones'] ?? '');
            $pagado = isset($_POST['pagado']) ?1 : 0;
            $multa_id = !empty($_POST['multa_id']) ? (int)$_POST['multa_id'] : null;
            $telepase_id = !empty($_POST['telepase_id']) ? (int)$_POST['telepase_id'] : null;

            if (empty($vehiculo_id) || empty($tipo)) {
                json_response(['success' => false, 'message' => 'Vehículo y tipo son obligatorios'], 400);
            }

            $stmt = $pdo->prepare("
                INSERT INTO pagos (vehiculo_id, tipo, aseguradora, poliza_numero, fecha_inicio, fecha_vencimiento, fecha_pago, monto, comprobante, observaciones, pagado, multa_id, telepase_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$vehiculo_id, $tipo, $aseguradora, $poliza_numero, $fecha_inicio, $fecha_vencimiento, $fecha_pago, $monto, $comprobante, $observaciones, $pagado, $multa_id, $telepase_id]);

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

            $vehiculo_id = (int)($_PUT['vehiculo_id'] ?? 0);
            $tipo = sanitizar_input($_PUT['tipo'] ?? '');
            $aseguradora = sanitizar_input($_PUT['aseguradora'] ?? '');
            $poliza_numero = sanitizar_input($_PUT['poliza_numero'] ?? '');
            $fecha_inicio = !empty($_PUT['fecha_inicio']) ? $_PUT['fecha_inicio'] : null;
            $fecha_vencimiento = $_PUT['fecha_vencimiento'] ?? '';
            $fecha_pago = !empty($_PUT['fecha_pago']) ? $_PUT['fecha_pago'] : null;
            $monto = (float)($_PUT['monto'] ?? 0);
            $comprobante = sanitizar_input($_PUT['comprobante'] ?? '');
            $observaciones = sanitizar_input($_PUT['observaciones'] ?? '');
            $pagado = isset($_PUT['pagado']) ? 1 : 0;

            $stmt = $pdo->prepare("
                UPDATE pagos
                SET vehiculo_id = ?,
                    tipo = ?,
                    aseguradora = ?,
                    poliza_numero = ?,
                    fecha_inicio = ?,
                    fecha_vencimiento = ?,
                    fecha_pago = ?,
                    monto = ?,
                    comprobante = ?,
                    observaciones = ?,
                    pagado = ?
                WHERE id = ?
            ");
            $stmt->execute([$vehiculo_id, $tipo, $aseguradora, $poliza_numero, $fecha_inicio, $fecha_vencimiento, $fecha_pago, $monto, $comprobante, $observaciones, $pagado, $id]);

            if ($stmt->rowCount() === 0) {
                json_response(['success' => false, 'message' => 'Pago no encontrado'], 404);
            }

            registrarLog($_SESSION['usuario_id'], 'ACTUALIZAR_PAGO', 'pagos', "Pago $id actualizado", $pdo);

            json_response(['success' => true, 'message' => 'Pago actualizado correctamente']);
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
