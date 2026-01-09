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
            $stmt = $pdo->query("
                SELECT
                    vt.*,
                    v.patente, v.marca, v.modelo
                FROM ventas vt
                JOIN vehiculos v ON vt.vehiculo_id = v.id
                ORDER BY vt.fecha DESC
            ");
            $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            json_response(['success' => true, 'data' => $ventas]);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Error al obtener ventas: ' . $e->getMessage()], 500);
        }
        break;

    case 'POST':
        if (!verificar_csrf($_POST['csrf_token'] ?? '')) {
            json_response(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $vehiculo_id = (int)($_POST['vehiculo_id'] ?? 0);
            $fecha = $_POST['fecha'] ?? '';
            $factura_numero = sanitizar_input($_POST['factura_numero'] ?? '');
            $comprador = sanitizar_input($_POST['comprador'] ?? '');
            $cuit = sanitizar_input($_POST['cuit'] ?? '');
            $importe = (float)($_POST['importe'] ?? 0);
            $comprobante = sanitizar_input($_POST['comprobante'] ?? '');
            $observaciones = sanitizar_input($_POST['observaciones'] ?? '');

            if (empty($vehiculo_id) || empty($fecha) || empty($comprador)) {
                json_response(['success' => false, 'message' => 'Vehículo, fecha y comprador son obligatorios'], 400);
            }

            $pdo->beginTransaction();

            // Registrar venta
            $stmt = $pdo->prepare("
                INSERT INTO ventas (
                    vehiculo_id, fecha, factura_numero, comprador, cuit,
                    importe, comprobante, observaciones
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $vehiculo_id, $fecha, $factura_numero, $comprador, $cuit,
                $importe, $comprobante, $observaciones
            ]);

            // Cambiar estado del vehículo a 'baja' y registrar fecha
            $stmt = $pdo->prepare("UPDATE vehiculos SET estado = 'baja', fecha_baja = ? WHERE id = ?");
            $stmt->execute([$fecha, $vehiculo_id]);

            $pdo->commit();

            registrarLog($_SESSION['usuario_id'], 'REGISTRAR_VENTA', 'ventas', "Venta registrada para vehículo ID: $vehiculo_id", $pdo);

            json_response(['success' => true, 'message' => 'Venta registrada exitosamente. Vehículo dado de baja.']);
        } catch (Exception $e) {
            $pdo->rollBack();
            json_response(['success' => false, 'message' => 'Error al registrar venta: ' . $e->getMessage()], 500);
        }
        break;

    case 'PUT':
        parse_str(file_get_contents('php://input'), $_PUT);

        if (!verificar_csrf($_PUT['csrf_token'] ?? '')) {
            json_response(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $id = (int)($_PUT['id'] ?? 0);
            $fecha = $_PUT['fecha'] ?? '';
            $factura_numero = sanitizar_input($_PUT['factura_numero'] ?? '');
            $comprador = sanitizar_input($_PUT['comprador'] ?? '');
            $cuit = sanitizar_input($_PUT['cuit'] ?? '');
            $importe = (float)($_PUT['importe'] ?? 0);
            $comprobante = sanitizar_input($_PUT['comprobante'] ?? '');
            $observaciones = sanitizar_input($_PUT['observaciones'] ?? '');

            if (empty($id) || empty($fecha) || empty($comprador)) {
                json_response(['success' => false, 'message' => 'ID, fecha y comprador son obligatorios'], 400);
            }

            $stmt = $pdo->prepare("
                UPDATE ventas SET
                    fecha = ?, factura_numero = ?, comprador = ?, cuit = ?,
                    importe = ?, comprobante = ?, observaciones = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $fecha, $factura_numero, $comprador, $cuit,
                $importe, $comprobante, $observaciones, $id
            ]);

            registrarLog($_SESSION['usuario_id'], 'ACTUALIZAR_VENTA', 'ventas', "Venta actualizada (ID: $id)", $pdo);

            json_response(['success' => true, 'message' => 'Venta actualizada exitosamente']);
        } catch (PDOException $e) {
            json_response(['success' => false, 'message' => 'Error al actualizar venta: ' . $e->getMessage()], 500);
        }
        break;

    default:
        json_response(['success' => false, 'message' => 'Método no permitido'], 405);
        break;
}
