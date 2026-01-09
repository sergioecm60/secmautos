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
                    a.*,
                    v.patente, v.marca, v.modelo,
                    CONCAT(e.nombre, ' ', e.apellido) as empleado_nombre
                FROM asignaciones a
                JOIN vehiculos v ON a.vehiculo_id = v.id
                JOIN empleados e ON a.empleado_id = e.id
                WHERE a.fecha_devolucion IS NULL
                ORDER BY a.fecha_asignacion DESC
            ");
            $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            json_response(['success' => true, 'data' => $asignaciones]);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Error al obtener asignaciones: ' . $e->getMessage()], 500);
        }
        break;
    
    case 'POST':
        if (!verificar_csrf($_POST['csrf_token'] ?? '')) {
            json_response(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }
        
        try {
            $vehiculo_id = (int)($_POST['vehiculo_id'] ?? 0);
            $empleado_id = (int)($_POST['empleado_id'] ?? 0);
            $km_salida = (int)($_POST['km_salida'] ?? 0);
            $observaciones = sanitizar_input($_POST['observaciones'] ?? '');
            
            if (empty($vehiculo_id) || empty($empleado_id)) {
                json_response(['success' => false, 'message' => 'Vehículo y empleado son obligatorios'], 400);
            }
            
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("UPDATE vehiculos SET estado = 'asignado' WHERE id = ?");
            $stmt->execute([$vehiculo_id]);
            
            $stmt = $pdo->prepare("
                INSERT INTO asignaciones (vehiculo_id, empleado_id, km_salida, observaciones)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$vehiculo_id, $empleado_id, $km_salida, $observaciones]);
            
            $pdo->commit();
            
            registrarLog($_SESSION['usuario_id'], 'ASIGNAR_VEHICULO', 'asignaciones', "Vehículo $vehiculo_id asignado a empleado $empleado_id", $pdo);
            
            json_response(['success' => true, 'message' => 'Vehículo asignado exitosamente']);
        } catch (Exception $e) {
            $pdo->rollBack();
            json_response(['success' => false, 'message' => 'Error al asignar vehículo: ' . $e->getMessage()], 500);
        }
        break;
    
    default:
        json_response(['success' => false, 'message' => 'Método no permitido'], 405);
        break;
}
