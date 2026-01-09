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
                FROM ceta c
                JOIN vehiculos v ON c.vehiculo_id = v.id
                ORDER BY c.fecha_vencimiento ASC
            ");
            $cetas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            json_response(['success' => true, 'data' => $cetas]);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Error al obtener CETAs: ' . $e->getMessage()], 500);
        }
        break;

    case 'POST':
        if (!verificar_csrf($_POST['csrf_token'] ?? '')) {
            json_response(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $vehiculo_id = (int)($_POST['vehiculo_id'] ?? 0);
            $cedula_azul_numero = sanitizar_input($_POST['cedula_azul_numero'] ?? '');
            $fecha_vencimiento = $_POST['fecha_vencimiento'] ?? '';
            $fecha_envio = !empty($_POST['fecha_envio']) ? $_POST['fecha_envio'] : null;
            $enviado = (bool)($_POST['enviado'] ?? false);
            $observaciones = sanitizar_input($_POST['observaciones'] ?? '');

            if (empty($vehiculo_id) || empty($fecha_vencimiento)) {
                json_response(['success' => false, 'message' => 'Vehículo y fecha de vencimiento son obligatorios'], 400);
            }

            $stmt = $pdo->prepare("
                INSERT INTO ceta (
                    vehiculo_id, cedula_azul_numero, fecha_vencimiento,
                    fecha_envio, enviado, observaciones
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $vehiculo_id, $cedula_azul_numero, $fecha_vencimiento,
                $fecha_envio, $enviado, $observaciones
            ]);

            registrarLog($_SESSION['usuario_id'], 'REGISTRAR_CETA', 'ceta', "CETA registrada para vehículo ID: $vehiculo_id", $pdo);

            json_response(['success' => true, 'message' => 'CETA registrada exitosamente']);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Error al registrar CETA: ' . $e->getMessage()], 500);
        }
        break;

    case 'PUT':
        parse_str(file_get_contents('php://input'), $_PUT);

        if (!verificar_csrf($_PUT['csrf_token'] ?? '')) {
            json_response(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $id = (int)($_PUT['id'] ?? 0);
            $cedula_azul_numero = sanitizar_input($_PUT['cedula_azul_numero'] ?? '');
            $fecha_vencimiento = $_PUT['fecha_vencimiento'] ?? '';
            $fecha_envio = !empty($_PUT['fecha_envio']) ? $_PUT['fecha_envio'] : null;
            $enviado = (bool)($_PUT['enviado'] ?? false);
            $observaciones = sanitizar_input($_PUT['observaciones'] ?? '');

            if (empty($id) || empty($fecha_vencimiento)) {
                json_response(['success' => false, 'message' => 'ID y fecha de vencimiento son obligatorios'], 400);
            }

            $stmt = $pdo->prepare("
                UPDATE ceta SET
                    cedula_azul_numero = ?, fecha_vencimiento = ?,
                    fecha_envio = ?, enviado = ?, observaciones = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $cedula_azul_numero, $fecha_vencimiento,
                $fecha_envio, $enviado, $observaciones, $id
            ]);

            registrarLog($_SESSION['usuario_id'], 'ACTUALIZAR_CETA', 'ceta', "CETA actualizada (ID: $id)", $pdo);

            json_response(['success' => true, 'message' => 'CETA actualizada exitosamente']);
        } catch (PDOException $e) {
            json_response(['success' => false, 'message' => 'Error al actualizar CETA: ' . $e->getMessage()], 500);
        }
        break;

    default:
        json_response(['success' => false, 'message' => 'Método no permitido'], 405);
        break;
}
