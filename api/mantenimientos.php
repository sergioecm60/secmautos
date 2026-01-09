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
                    m.*,
                    v.patente, v.marca, v.modelo
                FROM mantenimientos m
                JOIN vehiculos v ON m.vehiculo_id = v.id
                ORDER BY m.fecha DESC, m.created_at DESC
            ");
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
            $fecha = $_POST['fecha'] ?? '';
            $tipo = sanitizar_input($_POST['tipo'] ?? 'preventivo');
            $descripcion = sanitizar_input($_POST['descripcion'] ?? '');
            $costo = (float)($_POST['costo'] ?? 0);
            $kilometraje = (int)($_POST['kilometraje'] ?? 0);
            $proveedor = sanitizar_input($_POST['proveedor'] ?? '');
            $comprobante = sanitizar_input($_POST['comprobante'] ?? '');
            $observaciones = sanitizar_input($_POST['observaciones'] ?? '');
            
            if (empty($vehiculo_id) || empty($fecha) || empty($descripcion)) {
                json_response(['success' => false, 'message' => 'Vehículo, fecha y descripción son obligatorios'], 400);
            }
            
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                INSERT INTO mantenimientos (vehiculo_id, fecha, tipo, descripcion, costo, kilometraje, proveedor, comprobante, observaciones)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$vehiculo_id, $fecha, $tipo, $descripcion, $costo, $kilometraje, $proveedor, $comprobante, $observaciones]);
            
            if ($kilometraje > 0) {
                $stmt = $pdo->prepare("UPDATE vehiculos SET kilometraje_actual = ?, km_proximo_service = ? WHERE id = ?");
                $stmt->execute([$kilometraje, $kilometraje + 10000, $vehiculo_id]);
            }
            
            $pdo->commit();
            
            registrarLog($_SESSION['usuario_id'], 'REGISTRAR_MANTENIMIENTO', 'mantenimientos', "Mantenimiento registrado para vehículo $vehiculo_id", $pdo);
            
            json_response(['success' => true, 'message' => 'Mantenimiento registrado exitosamente']);
        } catch (Exception $e) {
            $pdo->rollBack();
            json_response(['success' => false, 'message' => 'Error al registrar mantenimiento: ' . $e->getMessage()], 500);
        }
        break;
    
    default:
        json_response(['success' => false, 'message' => 'Método no permitido'], 405);
        break;
}
