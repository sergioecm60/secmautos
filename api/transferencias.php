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
                    t.*,
                    v.patente, v.marca, v.modelo
                FROM transferencias t
                JOIN vehiculos v ON t.vehiculo_id = v.id
            ";

            $params = [];

            if (isset($_GET['vehiculo_id'])) {
                $sql .= " WHERE t.vehiculo_id = ?";
                $params[] = (int)$_GET['vehiculo_id'];
            }

            $sql .= " ORDER BY t.fecha DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $transferencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

            json_response(['success' => true, 'data' => $transferencias]);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Error al obtener transferencias: ' . $e->getMessage()], 500);
        }
        break;

    case 'POST':
        if (!verificar_csrf($_POST['csrf_token'] ?? '')) {
            json_response(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $vehiculo_id = (int)($_POST['vehiculo_id'] ?? 0);
            $fecha = $_POST['fecha'] ?? '';
            $registro = sanitizar_input($_POST['registro'] ?? '');
            $direccion_registro = sanitizar_input($_POST['direccion_registro'] ?? '');
            $numero_tramite = sanitizar_input($_POST['numero_tramite'] ?? '');
            $estado = sanitizar_input($_POST['estado'] ?? 'en_proceso');
            $observaciones = sanitizar_input($_POST['observaciones'] ?? '');

            if (empty($vehiculo_id) || empty($fecha)) {
                json_response(['success' => false, 'message' => 'Vehículo y fecha son obligatorios'], 400);
            }

            $stmt = $pdo->prepare("
                INSERT INTO transferencias (
                    vehiculo_id, fecha, registro, direccion_registro,
                    numero_tramite, estado, observaciones
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $vehiculo_id, $fecha, $registro, $direccion_registro,
                $numero_tramite, $estado, $observaciones
            ]);

            registrarLog($_SESSION['usuario_id'], 'REGISTRAR_TRANSFERENCIA', 'transferencias', "Transferencia registrada para vehículo ID: $vehiculo_id", $pdo);

            json_response(['success' => true, 'message' => 'Transferencia registrada exitosamente']);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Error al registrar transferencia: ' . $e->getMessage()], 500);
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
            $registro = sanitizar_input($_PUT['registro'] ?? '');
            $direccion_registro = sanitizar_input($_PUT['direccion_registro'] ?? '');
            $numero_tramite = sanitizar_input($_PUT['numero_tramite'] ?? '');
            $estado = sanitizar_input($_PUT['estado'] ?? 'en_proceso');
            $observaciones = sanitizar_input($_PUT['observaciones'] ?? '');

            if (empty($id) || empty($fecha)) {
                json_response(['success' => false, 'message' => 'ID y fecha son obligatorios'], 400);
            }

            $stmt = $pdo->prepare("
                UPDATE transferencias SET
                    fecha = ?, registro = ?, direccion_registro = ?,
                    numero_tramite = ?, estado = ?, observaciones = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $fecha, $registro, $direccion_registro,
                $numero_tramite, $estado, $observaciones, $id
            ]);

            registrarLog($_SESSION['usuario_id'], 'ACTUALIZAR_TRANSFERENCIA', 'transferencias', "Transferencia actualizada (ID: $id)", $pdo);

            json_response(['success' => true, 'message' => 'Transferencia actualizada exitosamente']);
        } catch (PDOException $e) {
            json_response(['success' => false, 'message' => 'Error al actualizar transferencia: ' . $e->getMessage()], 500);
        }
        break;

    default:
        json_response(['success' => false, 'message' => 'Método no permitido'], 405);
        break;
}
