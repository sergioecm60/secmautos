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

    case 'PUT':
        parse_str(file_get_contents('php://input'), $_PUT);

        if (!verificar_csrf($_PUT['csrf_token'] ?? '')) {
            json_response(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $id = (int)($_PUT['id'] ?? 0);
            $km_regreso = (int)($_PUT['km_regreso'] ?? 0);
            $observaciones = sanitizar_input($_PUT['observaciones'] ?? '');

            if (empty($id) || empty($km_regreso)) {
                json_response(['success' => false, 'message' => 'ID y kilometraje de regreso son obligatorios'], 400);
            }

            $pdo->beginTransaction();

            // Obtener vehiculo_id de la asignación
            $stmt = $pdo->prepare("SELECT vehiculo_id, km_salida FROM asignaciones WHERE id = ?");
            $stmt->execute([$id]);
            $asignacion = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$asignacion) {
                json_response(['success' => false, 'message' => 'Asignación no encontrada'], 404);
            }

            // Validar que km_regreso sea mayor a km_salida
            if ($km_regreso < $asignacion['km_salida']) {
                json_response(['success' => false, 'message' => 'El kilometraje de regreso debe ser mayor al de salida'], 400);
            }

            // Registrar devolución con fecha actual y km_regreso
            $stmt = $pdo->prepare("
                UPDATE asignaciones
                SET fecha_devolucion = NOW(), km_regreso = ?, observaciones = CONCAT(COALESCE(observaciones, ''), '\n', ?)
                WHERE id = ?
            ");
            $stmt->execute([$km_regreso, $observaciones, $id]);

            // Actualizar estado del vehículo a disponible
            $stmt = $pdo->prepare("UPDATE vehiculos SET estado = 'disponible', kilometraje_actual = ? WHERE id = ?");
            $stmt->execute([$km_regreso, $asignacion['vehiculo_id']]);

            $pdo->commit();

            registrarLog($_SESSION['usuario_id'], 'DEVOLVER_VEHICULO', 'asignaciones', "Vehículo devuelto (Asignación ID: $id, KM: $km_regreso)", $pdo);

            json_response(['success' => true, 'message' => 'Vehículo devuelto exitosamente']);
        } catch (Exception $e) {
            $pdo->rollBack();
            json_response(['success' => false, 'message' => 'Error al devolver vehículo: ' . $e->getMessage()], 500);
        }
        break;

    default:
        json_response(['success' => false, 'message' => 'Método no permitido'], 405);
        break;
}
