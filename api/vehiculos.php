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
                    v.*,
                    CASE 
                        WHEN a.empleado_id IS NOT NULL THEN e.nombre 
                        ELSE 'N/A' 
                    END as empleado_actual
                FROM vehiculos v
                LEFT JOIN (
                    SELECT vehiculo_id, empleado_id 
                    FROM asignaciones 
                    WHERE fecha_devolucion IS NULL 
                    ORDER BY fecha_asignacion DESC 
                    LIMIT 1
                ) a ON v.id = a.vehiculo_id
                LEFT JOIN empleados e ON a.empleado_id = e.id
                WHERE v.estado != 'baja'
                ORDER BY v.patente ASC
            ");
            $vehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            json_response(['success' => true, 'data' => $vehiculos]);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Error al obtener vehículos: ' . $e->getMessage()], 500);
        }
        break;
    
    case 'POST':
        if (!verificar_csrf($_POST['csrf_token'] ?? '')) {
            json_response(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }
        
        try {
            $patente = strtoupper(trim($_POST['patente'] ?? ''));
            $marca = sanitizar_input($_POST['marca'] ?? '');
            $modelo = sanitizar_input($_POST['modelo'] ?? '');
            $anio = (int)($_POST['anio'] ?? 0);
            $motor = sanitizar_input($_POST['motor'] ?? '');
            $chasis = sanitizar_input($_POST['chasis'] ?? '');
            $titularidad = sanitizar_input($_POST['titularidad'] ?? '');
            $kilometraje_actual = (int)($_POST['kilometraje_actual'] ?? 0);
            $estado = sanitizar_input($_POST['estado'] ?? 'disponible');
            $fecha_vtv = !empty($_POST['fecha_vtv']) ? $_POST['fecha_vtv'] : null;
            $fecha_seguro = !empty($_POST['fecha_seguro']) ? $_POST['fecha_seguro'] : null;
            $fecha_patente = !empty($_POST['fecha_patente']) ? $_POST['fecha_patente'] : null;
            $km_proximo_service = (int)($_POST['km_proximo_service'] ?? 0);
            $observaciones = sanitizar_input($_POST['observaciones'] ?? '');
            
            if (empty($patente) || empty($marca) || empty($modelo)) {
                json_response(['success' => false, 'message' => 'Patente, marca y modelo son obligatorios'], 400);
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO vehiculos (
                    patente, marca, modelo, anio, motor, chasis, titularidad,
                    kilometraje_actual, estado, fecha_vtv, fecha_seguro, 
                    fecha_patente, km_proximo_service, observaciones
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $patente, $marca, $modelo, $anio, $motor, $chasis, $titularidad,
                $kilometraje_actual, $estado, $fecha_vtv, $fecha_seguro,
                $fecha_patente, $km_proximo_service, $observaciones
            ]);
            
            registrarLog($_SESSION['usuario_id'], 'CREAR_VEHICULO', 'vehiculos', "Vehículo creado: $patente", $pdo);
            
            json_response(['success' => true, 'message' => 'Vehículo creado exitosamente']);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                json_response(['success' => false, 'message' => 'La patente ya existe en el sistema'], 409);
            }
            json_response(['success' => false, 'message' => 'Error al crear vehículo: ' . $e->getMessage()], 500);
        }
        break;
    
    default:
        json_response(['success' => false, 'message' => 'Método no permitido'], 405);
        break;
}
