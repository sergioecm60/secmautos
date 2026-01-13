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
                    m.*,
                    v.patente, v.marca, v.modelo,
                    t.nombre as nombre_taller
                FROM mantenimientos m
                JOIN vehiculos v ON m.vehiculo_id = v.id
                LEFT JOIN talleres t ON m.taller_id = t.id
            ";

            $params = [];

            if (isset($_GET['vehiculo_id'])) {
                $sql .= " WHERE m.vehiculo_id = ?";
                $params[] = (int)$_GET['vehiculo_id'];
            }

            $sql .= " ORDER BY m.fecha DESC, m.created_at DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $mantenimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            json_response(['success' => true, 'data' => $mantenimientos]);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Error al obtener mantenimientos: ' . $e->getMessage()], 500);
        }
        break;
    
    case 'POST':
        if (!verificar_csrf($_POST['csrf_token'] ?? '')) {
            json_response(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }
        
        try {
            $vehiculo_id = (int)($_POST['vehiculo_id'] ?? 0);
            $taller_id = (int)($_POST['taller_id'] ?? 0);
            $fecha = $_POST['fecha'] ?? '';
            $fecha_salida = !empty($_POST['fecha_salida']) ? $_POST['fecha_salida'] : null;
            $tipo = sanitizar_input($_POST['tipo'] ?? 'preventivo');
            $paquete_mantenimiento = sanitizar_input($_POST['paquete_mantenimiento'] ?? '');
            $descripcion = sanitizar_input($_POST['descripcion'] ?? '');
            $costo = (float)($_POST['costo'] ?? 0);
            $kilometraje = (int)($_POST['kilometraje'] ?? 0);
            $proveedor = sanitizar_input($_POST['proveedor'] ?? '');
            $fecha_pago = !empty($_POST['fecha_pago']) ? $_POST['fecha_pago'] : null;
            $comprobante = sanitizar_input($_POST['comprobante'] ?? '');
            $observaciones = sanitizar_input($_POST['observaciones'] ?? '');
            
            if (empty($vehiculo_id) || empty($fecha) || empty($descripcion)) {
                json_response(['success' => false, 'message' => 'Vehículo, fecha y descripción son obligatorios'], 400);
            }

            $stmt = $pdo->prepare("
                INSERT INTO mantenimientos (
                    vehiculo_id, taller_id, fecha, fecha_salida, tipo, paquete_mantenimiento,
                    descripcion, costo, kilometraje, proveedor, fecha_pago, comprobante, observaciones
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $vehiculo_id, $taller_id, $fecha, $fecha_salida, $tipo, $paquete_mantenimiento,
                $descripcion, $costo, $kilometraje, $proveedor, $fecha_pago, $comprobante, $observaciones
            ]);

            registrarLog($_SESSION['usuario_id'], 'ACTUALIZAR_MANTENIMIENTO', 'mantenimientos', "Mantenimiento actualizado (ID: $id)", $pdo);

            json_response(['success' => true, 'message' => 'Mantenimiento actualizado exitosamente']);
        } catch (PDOException $e) {
            json_response(['success' => false, 'message' => 'Error al actualizar mantenimiento: ' . $e->getMessage()], 500);
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
                json_response(['success' => false, 'message' => 'ID es obligatorio'], 400);
            }

            $stmt = $pdo->prepare("DELETE FROM mantenimientos WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                registrarLog($_SESSION['usuario_id'], 'ELIMINAR_MANTENIMIENTO', 'mantenimientos', "Mantenimiento eliminado (ID: $id)", $pdo);
                json_response(['success' => true, 'message' => 'Mantenimiento eliminado exitosamente']);
            } else {
                json_response(['success' => false, 'message' => 'No se encontró el mantenimiento a eliminar.'], 404);
            }
        } catch (PDOException $e) {
            json_response(['success' => false, 'message' => 'Error al eliminar mantenimiento: ' . $e->getMessage()], 500);
        }
        break;
    
    default:
        json_response(['success' => false, 'message' => 'Método no permitido'], 405);
        break;
}
