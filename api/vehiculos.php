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
            ";

            $params = [];

            if (isset($_GET['id'])) {
                $sql .= " WHERE v.id = ?";
                $params[] = (int)$_GET['id'];
            } else {
                $sql .= " WHERE v.estado != 'baja'";
                $sql .= " ORDER BY v.patente ASC";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
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
            $titulo_dnrpa = sanitizar_input($_POST['titulo_dnrpa'] ?? '');
            $titularidad = sanitizar_input($_POST['titularidad'] ?? '');
            $titulo_automotor = sanitizar_input($_POST['titulo_automotor'] ?? '');
            $cedula_verde = sanitizar_input($_POST['cedula_verde'] ?? '');
            $kilometraje_actual = (int)($_POST['kilometraje_actual'] ?? 0);
            $estado = sanitizar_input($_POST['estado'] ?? 'disponible');
            $fecha_vtv = !empty($_POST['fecha_vtv']) ? $_POST['fecha_vtv'] : null;
            $fecha_seguro = !empty($_POST['fecha_seguro']) ? $_POST['fecha_seguro'] : null;
            $fecha_patente = !empty($_POST['fecha_patente']) ? $_POST['fecha_patente'] : null;
            $km_proximo_service = (int)($_POST['km_proximo_service'] ?? 0);
            $observaciones = sanitizar_input($_POST['observaciones'] ?? '');
            $color = sanitizar_input($_POST['color'] ?? '');
            $tipo_vehiculo = sanitizar_input($_POST['tipo_vehiculo'] ?? 'Auto');
            $carga_maxima_kg = (int)($_POST['carga_maxima_kg'] ?? 0);
            $km_odometro_inicial = (int)($_POST['km_odometro_inicial'] ?? 0);
            $ciclo_mantenimiento_preventivo_km = (int)($_POST['ciclo_mantenimiento_preventivo_km'] ?? 0);

            if (empty($patente) || empty($marca) || empty($modelo)) {
                json_response(['success' => false, 'message' => 'Patente, marca y modelo son obligatorios'], 400);
            }

            $stmt = $pdo->prepare("
                INSERT INTO vehiculos (
                    patente, marca, modelo, color, tipo_vehiculo, carga_maxima_kg, anio, motor, chasis, titulo_dnrpa, titularidad,
                    titulo_automotor, cedula_verde,
                    kilometraje_actual, estado, fecha_vtv, fecha_seguro,
                    fecha_patente, km_proximo_service, km_odometro_inicial, ciclo_mantenimiento_preventivo_km, observaciones
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $patente, $marca, $modelo, $color, $tipo_vehiculo, $carga_maxima_kg, $anio, $motor, $chasis, $titulo_dnrpa, $titularidad,
                $titulo_automotor, $cedula_verde,
                $kilometraje_actual, $estado, $fecha_vtv, $fecha_seguro,
                $fecha_patente, $km_proximo_service, $km_odometro_inicial, $ciclo_mantenimiento_preventivo_km, $observaciones
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

    case 'PUT':
        parse_str(file_get_contents('php://input'), $_PUT);

        if (!verificar_csrf($_PUT['csrf_token'] ?? '')) {
            json_response(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $id = (int)($_PUT['id'] ?? 0);
            $patente = strtoupper(trim($_PUT['patente'] ?? ''));
            $marca = sanitizar_input($_PUT['marca'] ?? '');
            $modelo = sanitizar_input($_PUT['modelo'] ?? '');
            $anio = (int)($_PUT['anio'] ?? 0);
            $motor = sanitizar_input($_PUT['motor'] ?? '');
            $chasis = sanitizar_input($_PUT['chasis'] ?? '');
            $titulo_dnrpa = sanitizar_input($_PUT['titulo_dnrpa'] ?? '');
            $titularidad = sanitizar_input($_PUT['titularidad'] ?? '');
            $titulo_automotor = sanitizar_input($_PUT['titulo_automotor'] ?? '');
            $cedula_verde = sanitizar_input($_PUT['cedula_verde'] ?? '');
            $kilometraje_actual = (int)($_PUT['kilometraje_actual'] ?? 0);
            $estado = sanitizar_input($_PUT['estado'] ?? 'disponible');
            $fecha_vtv = !empty($_PUT['fecha_vtv']) ? $_PUT['fecha_vtv'] : null;
            $fecha_seguro = !empty($_PUT['fecha_seguro']) ? $_PUT['fecha_seguro'] : null;
            $fecha_patente = !empty($_PUT['fecha_patente']) ? $_PUT['fecha_patente'] : null;
            $km_proximo_service = (int)($_PUT['km_proximo_service'] ?? 0);
            $observaciones = sanitizar_input($_PUT['observaciones'] ?? '');
            $color = sanitizar_input($_PUT['color'] ?? '');
            $tipo_vehiculo = sanitizar_input($_PUT['tipo_vehiculo'] ?? 'Auto');
            $carga_maxima_kg = (int)($_PUT['carga_maxima_kg'] ?? 0);
            $km_odometro_inicial = (int)($_PUT['km_odometro_inicial'] ?? 0);
            $ciclo_mantenimiento_preventivo_km = (int)($_PUT['ciclo_mantenimiento_preventivo_km'] ?? 0);

            if (empty($id) || empty($patente) || empty($marca) || empty($modelo)) {
                json_response(['success' => false, 'message' => 'ID, patente, marca y modelo son obligatorios'], 400);
            }

            $stmt = $pdo->prepare("
                UPDATE vehiculos SET
                    patente = ?, marca = ?, modelo = ?, color = ?, tipo_vehiculo = ?, carga_maxima_kg = ?,
                    anio = ?, motor = ?, chasis = ?, titulo_dnrpa = ?, titularidad = ?, titulo_automotor = ?, cedula_verde = ?,
                    kilometraje_actual = ?, estado = ?, fecha_vtv = ?, fecha_seguro = ?, fecha_patente = ?,
                    km_proximo_service = ?, km_odometro_inicial = ?, ciclo_mantenimiento_preventivo_km = ?, observaciones = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $patente, $marca, $modelo, $color, $tipo_vehiculo, $carga_maxima_kg,
                $anio, $motor, $chasis, $titulo_dnrpa, $titularidad, $titulo_automotor, $cedula_verde,
                $kilometraje_actual, $estado, $fecha_vtv, $fecha_seguro, $fecha_patente,
                $km_proximo_service, $km_odometro_inicial, $ciclo_mantenimiento_preventivo_km, $observaciones, $id
            ]);

            registrarLog($_SESSION['usuario_id'], 'ACTUALIZAR_VEHICULO', 'vehiculos', "Vehículo actualizado: $patente (ID: $id)", $pdo);

            json_response(['success' => true, 'message' => 'Vehículo actualizado exitosamente']);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                json_response(['success' => false, 'message' => 'La patente ya existe en el sistema'], 409);
            }
            json_response(['success' => false, 'message' => 'Error al actualizar vehículo: ' . $e->getMessage()], 500);
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

            // Obtener datos del vehículo antes de dar de baja
            $stmt = $pdo->prepare("SELECT patente FROM vehiculos WHERE id = ?");
            $stmt->execute([$id]);
            $vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$vehiculo) {
                json_response(['success' => false, 'message' => 'Vehículo no encontrado'], 404);
            }

            // Soft delete: cambiar estado a 'baja' y registrar fecha
            $stmt = $pdo->prepare("UPDATE vehiculos SET estado = 'baja', fecha_baja = CURDATE() WHERE id = ?");
            $stmt->execute([$id]);

            registrarLog($_SESSION['usuario_id'], 'DAR_BAJA_VEHICULO', 'vehiculos', "Vehículo dado de baja: {$vehiculo['patente']} (ID: $id)", $pdo);

            json_response(['success' => true, 'message' => 'Vehículo dado de baja exitosamente']);
        } catch (PDOException $e) {
            json_response(['success' => false, 'message' => 'Error al dar de baja vehículo: ' . $e->getMessage()], 500);
        }
        break;

    default:
        json_response(['success' => false, 'message' => 'Método no permitido'], 405);
        break;
}
