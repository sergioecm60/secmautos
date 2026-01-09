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
                    c.*,
                    v.patente, v.marca, v.modelo
                FROM compras c
                JOIN vehiculos v ON c.vehiculo_id = v.id
                ORDER BY c.fecha DESC
            ");
            $compras = $stmt->fetchAll(PDO::FETCH_ASSOC);

            json_response(['success' => true, 'data' => $compras]);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Error al obtener compras: ' . $e->getMessage()], 500);
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
            $proveedor = sanitizar_input($_POST['proveedor'] ?? '');
            $cuit = sanitizar_input($_POST['cuit'] ?? '');
            $importe_neto = (float)($_POST['importe_neto'] ?? 0);
            $iva = (float)($_POST['iva'] ?? 0);
            $total = (float)($_POST['total'] ?? 0);
            $comprobante = sanitizar_input($_POST['comprobante'] ?? '');
            $observaciones = sanitizar_input($_POST['observaciones'] ?? '');

            if (empty($vehiculo_id) || empty($fecha) || empty($proveedor)) {
                json_response(['success' => false, 'message' => 'Vehículo, fecha y proveedor son obligatorios'], 400);
            }

            $stmt = $pdo->prepare("
                INSERT INTO compras (
                    vehiculo_id, fecha, factura_numero, proveedor, cuit,
                    importe_neto, iva, total, comprobante, observaciones
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $vehiculo_id, $fecha, $factura_numero, $proveedor, $cuit,
                $importe_neto, $iva, $total, $comprobante, $observaciones
            ]);

            registrarLog($_SESSION['usuario_id'], 'REGISTRAR_COMPRA', 'compras', "Compra registrada para vehículo ID: $vehiculo_id", $pdo);

            json_response(['success' => true, 'message' => 'Compra registrada exitosamente']);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Error al registrar compra: ' . $e->getMessage()], 500);
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
            $proveedor = sanitizar_input($_PUT['proveedor'] ?? '');
            $cuit = sanitizar_input($_PUT['cuit'] ?? '');
            $importe_neto = (float)($_PUT['importe_neto'] ?? 0);
            $iva = (float)($_PUT['iva'] ?? 0);
            $total = (float)($_PUT['total'] ?? 0);
            $comprobante = sanitizar_input($_PUT['comprobante'] ?? '');
            $observaciones = sanitizar_input($_PUT['observaciones'] ?? '');

            if (empty($id) || empty($fecha) || empty($proveedor)) {
                json_response(['success' => false, 'message' => 'ID, fecha y proveedor son obligatorios'], 400);
            }

            $stmt = $pdo->prepare("
                UPDATE compras SET
                    fecha = ?, factura_numero = ?, proveedor = ?, cuit = ?,
                    importe_neto = ?, iva = ?, total = ?, comprobante = ?, observaciones = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $fecha, $factura_numero, $proveedor, $cuit,
                $importe_neto, $iva, $total, $comprobante, $observaciones, $id
            ]);

            registrarLog($_SESSION['usuario_id'], 'ACTUALIZAR_COMPRA', 'compras', "Compra actualizada (ID: $id)", $pdo);

            json_response(['success' => true, 'message' => 'Compra actualizada exitosamente']);
        } catch (PDOException $e) {
            json_response(['success' => false, 'message' => 'Error al actualizar compra: ' . $e->getMessage()], 500);
        }
        break;

    default:
        json_response(['success' => false, 'message' => 'Método no permitido'], 405);
        break;
}
