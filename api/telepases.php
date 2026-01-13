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
            // Si viene el parámetro 'pagos', devolver pagos de un telepase específico
            if (isset($_GET['pagos'])) {
                $telepase_id = (int)$_GET['pagos'];

                $stmt = $pdo->prepare("
                    SELECT * FROM pagos_telepase
                    WHERE telepase_id = ?
                    ORDER BY periodo DESC, fecha_vencimiento DESC
                ");
                $stmt->execute([$telepase_id]);
                $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                json_response(['success' => true, 'data' => $pagos]);
                break;
            }

            // Consulta principal: obtener dispositivos telepase con datos de vehículo y contadores
            $sql = "
                SELECT
                    t.id,
                    t.vehiculo_id,
                    t.numero_dispositivo,
                    t.fecha_activacion,
                    t.fecha_baja,
                    t.estado,
                    t.observaciones,
                    t.created_at,
                    t.updated_at,
                    v.patente,
                    v.marca,
                    v.modelo,
                    v.anio,
                    COUNT(pt.id) as total_pagos,
                    SUM(CASE WHEN pt.estado = 'pendiente' THEN 1 ELSE 0 END) as pagos_pendientes,
                    COALESCE(SUM(CASE WHEN pt.estado = 'pendiente' THEN pt.monto ELSE 0 END), 0) as monto_pendiente
                FROM telepases t
                INNER JOIN vehiculos v ON t.vehiculo_id = v.id
                LEFT JOIN pagos_telepase pt ON t.id = pt.telepase_id
            ";

            $params = [];
            $conditions = [];

            // Filtros opcionales
            if (isset($_GET['vehiculo_id'])) {
                $conditions[] = "t.vehiculo_id = ?";
                $params[] = (int)$_GET['vehiculo_id'];
            }

            if (isset($_GET['estado'])) {
                $conditions[] = "t.estado = ?";
                $params[] = sanitizar_input($_GET['estado']);
            }

            if (isset($_GET['patente'])) {
                $conditions[] = "v.patente LIKE ?";
                $params[] = '%' . sanitizar_input($_GET['patente']) . '%';
            }

            if (isset($_GET['numero_dispositivo'])) {
                $conditions[] = "t.numero_dispositivo LIKE ?";
                $params[] = '%' . sanitizar_input($_GET['numero_dispositivo']) . '%';
            }

            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }

            $sql .= " GROUP BY t.id, v.id ORDER BY t.created_at DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $telepases = $stmt->fetchAll(PDO::FETCH_ASSOC);

            json_response(['success' => true, 'data' => $telepases]);
        } catch (Exception $e) {
            error_log("Error en GET telepases: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Error al obtener dispositivos: ' . $e->getMessage()], 500);
        }
        break;

    case 'POST':
        if (!verificar_csrf($_POST['csrf_token'] ?? '')) {
            json_response(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $vehiculo_id = (int)($_POST['vehiculo_id'] ?? 0);
            $numero_dispositivo = sanitizar_input($_POST['numero_dispositivo'] ?? '');
            $fecha_activacion = $_POST['fecha_activacion'] ?? '';
            $fecha_baja = !empty($_POST['fecha_baja']) ? $_POST['fecha_baja'] : null;
            $estado = sanitizar_input($_POST['estado'] ?? 'habilitado');
            $observaciones = sanitizar_input($_POST['observaciones'] ?? '');

            // Validaciones
            if (empty($vehiculo_id)) {
                json_response(['success' => false, 'message' => 'El vehículo es obligatorio'], 400);
            }

            if (empty($numero_dispositivo)) {
                json_response(['success' => false, 'message' => 'El número de dispositivo es obligatorio'], 400);
            }

            if (empty($fecha_activacion)) {
                json_response(['success' => false, 'message' => 'La fecha de activación es obligatoria'], 400);
            }

            if (!in_array($estado, ['habilitado', 'deshabilitado', 'baja'])) {
                json_response(['success' => false, 'message' => 'Estado inválido'], 400);
            }

            if ($estado === 'baja' && empty($fecha_baja)) {
                json_response(['success' => false, 'message' => 'La fecha de baja es obligatoria cuando el estado es "baja"'], 400);
            }

            // Verificar que el número de dispositivo no exista
            $stmt = $pdo->prepare("SELECT id FROM telepases WHERE numero_dispositivo = ?");
            $stmt->execute([$numero_dispositivo]);
            if ($stmt->fetch()) {
                json_response(['success' => false, 'message' => 'Ya existe un dispositivo con ese número'], 400);
            }

            // Verificar que el vehículo existe
            $stmt = $pdo->prepare("SELECT id FROM vehiculos WHERE id = ?");
            $stmt->execute([$vehiculo_id]);
            if (!$stmt->fetch()) {
                json_response(['success' => false, 'message' => 'El vehículo no existe'], 400);
            }

            // Insertar dispositivo
            $stmt = $pdo->prepare("
                INSERT INTO telepases (vehiculo_id, numero_dispositivo, fecha_activacion, fecha_baja, estado, observaciones)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $vehiculo_id,
                $numero_dispositivo,
                $fecha_activacion,
                $fecha_baja,
                $estado,
                $observaciones
            ]);

            $telepase_id = $pdo->lastInsertId();

            registrarLog(
                $_SESSION['usuario_id'],
                'CREAR_TELEPASE',
                'telepases',
                "Dispositivo telepase $numero_dispositivo registrado para vehículo ID: $vehiculo_id",
                $pdo
            );

            json_response([
                'success' => true,
                'message' => 'Dispositivo telepase registrado exitosamente',
                'id' => $telepase_id
            ]);
        } catch (Exception $e) {
            error_log("Error en POST telepases: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Error al registrar dispositivo: ' . $e->getMessage()], 500);
        }
        break;

    case 'PUT':
        parse_str(file_get_contents('php://input'), $_PUT);

        if (!verificar_csrf($_PUT['csrf_token'] ?? '')) {
            json_response(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $id = (int)($_PUT['id'] ?? 0);
            $vehiculo_id = (int)($_PUT['vehiculo_id'] ?? 0);
            $numero_dispositivo = sanitizar_input($_PUT['numero_dispositivo'] ?? '');
            $fecha_activacion = $_PUT['fecha_activacion'] ?? '';
            $fecha_baja = !empty($_PUT['fecha_baja']) ? $_PUT['fecha_baja'] : null;
            $estado = sanitizar_input($_PUT['estado'] ?? 'habilitado');
            $observaciones = sanitizar_input($_PUT['observaciones'] ?? '');

            // Validaciones
            if (empty($id)) {
                json_response(['success' => false, 'message' => 'ID de dispositivo requerido'], 400);
            }

            if (empty($vehiculo_id)) {
                json_response(['success' => false, 'message' => 'El vehículo es obligatorio'], 400);
            }

            if (empty($numero_dispositivo)) {
                json_response(['success' => false, 'message' => 'El número de dispositivo es obligatorio'], 400);
            }

            if (empty($fecha_activacion)) {
                json_response(['success' => false, 'message' => 'La fecha de activación es obligatoria'], 400);
            }

            if (!in_array($estado, ['habilitado', 'deshabilitado', 'baja'])) {
                json_response(['success' => false, 'message' => 'Estado inválido'], 400);
            }

            if ($estado === 'baja' && empty($fecha_baja)) {
                json_response(['success' => false, 'message' => 'La fecha de baja es obligatoria cuando el estado es "baja"'], 400);
            }

            // Verificar que el dispositivo existe
            $stmt = $pdo->prepare("SELECT id, numero_dispositivo FROM telepases WHERE id = ?");
            $stmt->execute([$id]);
            $telepase_actual = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$telepase_actual) {
                json_response(['success' => false, 'message' => 'Dispositivo no encontrado'], 404);
            }

            // Verificar que el número de dispositivo no esté duplicado (excepto el actual)
            if ($numero_dispositivo !== $telepase_actual['numero_dispositivo']) {
                $stmt = $pdo->prepare("SELECT id FROM telepases WHERE numero_dispositivo = ? AND id != ?");
                $stmt->execute([$numero_dispositivo, $id]);
                if ($stmt->fetch()) {
                    json_response(['success' => false, 'message' => 'Ya existe otro dispositivo con ese número'], 400);
                }
            }

            // Actualizar dispositivo
            $stmt = $pdo->prepare("
                UPDATE telepases
                SET vehiculo_id = ?,
                    numero_dispositivo = ?,
                    fecha_activacion = ?,
                    fecha_baja = ?,
                    estado = ?,
                    observaciones = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $vehiculo_id,
                $numero_dispositivo,
                $fecha_activacion,
                $fecha_baja,
                $estado,
                $observaciones,
                $id
            ]);

            registrarLog(
                $_SESSION['usuario_id'],
                'ACTUALIZAR_TELEPASE',
                'telepases',
                "Dispositivo telepase ID: $id actualizado",
                $pdo
            );

            json_response([
                'success' => true,
                'message' => 'Dispositivo actualizado correctamente'
            ]);
        } catch (Exception $e) {
            error_log("Error en PUT telepases: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Error al actualizar dispositivo: ' . $e->getMessage()], 500);
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
                json_response(['success' => false, 'message' => 'ID de dispositivo requerido'], 400);
            }

            // Verificar que el dispositivo existe
            $stmt = $pdo->prepare("SELECT numero_dispositivo FROM telepases WHERE id = ?");
            $stmt->execute([$id]);
            $telepase = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$telepase) {
                json_response(['success' => false, 'message' => 'Dispositivo no encontrado'], 404);
            }

            // Iniciar transacción
            $pdo->beginTransaction();

            try {
                // Eliminar primero los pagos asociados
                $stmt = $pdo->prepare("DELETE FROM pagos_telepase WHERE telepase_id = ?");
                $stmt->execute([$id]);

                // Eliminar el dispositivo
                $stmt = $pdo->prepare("DELETE FROM telepases WHERE id = ?");
                $stmt->execute([$id]);

                registrarLog(
                    $_SESSION['usuario_id'],
                    'ELIMINAR_TELEPASE',
                    'telepases',
                    "Dispositivo telepase {$telepase['numero_dispositivo']} eliminado (ID: $id)",
                    $pdo
                );

                $pdo->commit();

                json_response([
                    'success' => true,
                    'message' => 'Dispositivo y su historial de pagos eliminados exitosamente'
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            error_log("Error en DELETE telepases: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Error al eliminar dispositivo: ' . $e->getMessage()], 500);
        }
        break;

    default:
        json_response(['success' => false, 'message' => 'Método no permitido'], 405);
        break;
}
