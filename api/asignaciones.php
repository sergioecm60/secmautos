<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!verificar_autenticacion()) {
    json_response(['success' => false, 'message' => 'No autenticado'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Si se pide un responsable específico para una multa
        if (isset($_GET['vehiculo_id']) && isset($_GET['fecha'])) {
            try {
                $vehiculo_id = (int)$_GET['vehiculo_id'];
                $fecha = $_GET['fecha'];

                $stmt = $pdo->prepare("
                    SELECT 
                        a.empleado_id,
                        CONCAT(e.nombre, ' ', e.apellido) as nombre_empleado,
                        e.dni as dni_empleado
                    FROM asignaciones a
                    JOIN empleados e ON a.empleado_id = e.id
                    WHERE a.vehiculo_id = ? 
                    AND ? >= a.fecha_asignacion 
                    AND (a.fecha_devolucion IS NULL OR ? <= a.fecha_devolucion)
                    ORDER BY a.fecha_asignacion DESC
                    LIMIT 1
                ");
                $stmt->execute([$vehiculo_id, $fecha, $fecha]);
                $asignacion = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($asignacion) {
                    json_response(['success' => true, 'data' => $asignacion]);
                } else {
                    json_response(['success' => false, 'message' => 'No se encontró asignación para ese vehículo en esa fecha.']);
                }
            } catch (Exception $e) {
                json_response(['success' => false, 'message' => 'Error al buscar asignación: ' . $e->getMessage()], 500);
            }
        } else {
            // Comportamiento original: listar todas las asignaciones
            try {
                $stmt = $pdo->query("
                    SELECT 
                        a.*,
                        v.patente, 
                        CONCAT(v.marca, ' ', v.modelo) as marca_modelo,
                        CONCAT(e.nombre, ' ', e.apellido) as nombre_empleado
                    FROM asignaciones a
                    JOIN vehiculos v ON a.vehiculo_id = v.id
                    JOIN empleados e ON a.empleado_id = e.id
                    ORDER BY a.fecha_devolucion IS NULL DESC, a.fecha_asignacion DESC
                ");
                $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
                json_response(['success' => true, 'data' => $asignaciones]);
            } catch (Exception $e) {
                json_response(['success' => false, 'message' => 'Error al obtener asignaciones: ' . $e->getMessage()], 500);
            }
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
            $asignacion_id = (int)($_PUT['asignacion_id'] ?? 0);
            $km_regreso = (int)($_PUT['km_regreso'] ?? 0);
            $observaciones_devolucion = sanitizar_input($_PUT['observaciones'] ?? '');

            if (empty($asignacion_id) || empty($km_regreso)) {
                json_response(['success' => false, 'message' => 'ID de asignación y kilometraje de regreso son obligatorios'], 400);
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT vehiculo_id, km_salida, observaciones FROM asignaciones WHERE id = ?");
            $stmt->execute([$asignacion_id]);
            $asignacion = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$asignacion) {
                $pdo->rollBack();
                json_response(['success' => false, 'message' => 'Asignación no encontrada'], 404);
            }

            if ($km_regreso < $asignacion['km_salida']) {
                 $pdo->rollBack();
                json_response(['success' => false, 'message' => 'El kilometraje de regreso no puede ser menor al de salida'], 400);
            }
            
            $nuevas_observaciones = $asignacion['observaciones'];
            if (!empty($observaciones_devolucion)) {
                $nuevas_observaciones .= "\n[Devolución " . date('Y-m-d') . "]: " . $observaciones_devolucion;
            }

            $stmt = $pdo->prepare("
                UPDATE asignaciones
                SET fecha_devolucion = NOW(), km_regreso = ?, observaciones = ?
                WHERE id = ?
            ");
            $stmt->execute([$km_regreso, trim($nuevas_observaciones), $asignacion_id]);

            $stmt = $pdo->prepare("UPDATE vehiculos SET estado = 'disponible', kilometraje_actual = ? WHERE id = ?");
            $stmt->execute([$km_regreso, $asignacion['vehiculo_id']]);

            $pdo->commit();

            registrarLog($_SESSION['usuario_id'], 'DEVOLVER_VEHICULO', 'asignaciones', "Vehículo devuelto (Asignación ID: $asignacion_id, KM: $km_regreso)", $pdo);

            json_response(['success' => true, 'message' => 'Vehículo devuelto exitosamente']);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            json_response(['success' => false, 'message' => 'Error al devolver vehículo: ' . $e->getMessage()], 500);
        }
        break;

    default:
        json_response(['success' => false, 'message' => 'Método no permitido'], 405);
        break;
}
