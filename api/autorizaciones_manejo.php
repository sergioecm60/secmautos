<?php
require_once __DIR__ . '/../bootstrap.php';
requiereAutenticacion();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("
                SELECT am.*, e.nombre, e.apellido, e.dni, v.patente, v.marca, v.modelo
                FROM autorizaciones_manejo am
                JOIN empleados e ON am.empleado_id = e.id
                JOIN vehiculos v ON am.vehiculo_id = v.id
                WHERE am.id = ?
            ");
            $stmt->execute([$_GET['id']]);
            $autorizacion = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($autorizacion) {
                echo json_encode(['success' => true, 'data' => $autorizacion]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Autorización no encontrada']);
            }
        } else {
            $sql = "
                SELECT am.*, e.nombre, e.apellido, e.dni, v.patente, v.marca, v.modelo
                FROM autorizaciones_manejo am
                JOIN empleados e ON am.empleado_id = e.id
                JOIN vehiculos v ON am.vehiculo_id = v.id
                ORDER BY am.fecha_otorgamiento DESC
            ";
            $stmt = $pdo->query($sql);
            $autorizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $autorizaciones]);
        }
        break;

    case 'POST':
        $token = $_POST['csrf_token'] ?? '';
        if (!verificar_csrf($token)) {
            echo json_encode(['success' => false, 'message' => 'Error de CSRF']);
            exit;
        }

        $empleado_id = trim($_POST['empleado_id'] ?? '');
        $vehiculo_id = trim($_POST['vehiculo_id'] ?? '');
        $fecha_otorgamiento = trim($_POST['fecha_otorgamiento'] ?? date('Y-m-d'));
        $observaciones = trim($_POST['observaciones'] ?? '');
        $activa = isset($_POST['activa']) ? 1 : 0;

        if (empty($empleado_id) || empty($vehiculo_id)) {
            echo json_encode(['success' => false, 'message' => 'Debe seleccionar empleado y vehículo']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO autorizaciones_manejo
                (empleado_id, vehiculo_id, fecha_otorgamiento, observaciones, activa)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$empleado_id, $vehiculo_id, $fecha_otorgamiento, $observaciones, $activa]);

            registrarLog($_SESSION['usuario_id'], 'OTORGAR_AUTORIZACION', 'AUTORIZACIONES', "Autorización otorgada: Empleado $empleado_id - Vehículo $vehiculo_id", $pdo);

            echo json_encode(['success' => true, 'message' => 'Autorización otorgada correctamente']);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo json_encode(['success' => false, 'message' => 'Este empleado ya tiene autorización para este vehículo']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al otorgar autorización: ' . $e->getMessage()]);
            }
        }
        break;

    case 'PUT':
        parse_str(file_get_contents("php://input"), $data);
        $token = $data['csrf_token'] ?? '';
        if (!verificar_csrf($token)) {
            echo json_encode(['success' => false, 'message' => 'Error de CSRF']);
            exit;
        }

        $id = $data['id'] ?? '';
        $empleado_id = trim($data['empleado_id'] ?? '');
        $vehiculo_id = trim($data['vehiculo_id'] ?? '');
        $fecha_otorgamiento = trim($data['fecha_otorgamiento'] ?? '');
        $observaciones = trim($data['observaciones'] ?? '');
        $activa = isset($data['activa']) ? 1 : 0;

        if (empty($id) || empty($empleado_id) || empty($vehiculo_id)) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos obligatorios son requeridos']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("
                UPDATE autorizaciones_manejo
                SET empleado_id = ?, vehiculo_id = ?, fecha_otorgamiento = ?, observaciones = ?, activa = ?
                WHERE id = ?
            ");
            $stmt->execute([$empleado_id, $vehiculo_id, $fecha_otorgamiento, $observaciones, $activa, $id]);

            registrarLog($_SESSION['usuario_id'], 'EDITAR_AUTORIZACION', 'AUTORIZACIONES', "Autorización editada: ID $id", $pdo);

            echo json_encode(['success' => true, 'message' => 'Autorización actualizada correctamente']);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo json_encode(['success' => false, 'message' => 'Este empleado ya tiene autorización para este vehículo']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar autorización: ' . $e->getMessage()]);
            }
        }
        break;

    case 'DELETE':
        parse_str(file_get_contents("php://input"), $data);
        $token = $data['csrf_token'] ?? '';
        if (!verificar_csrf($token)) {
            echo json_encode(['success' => false, 'message' => 'Error de CSRF']);
            exit;
        }

        $id = $data['id'] ?? '';

        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'ID de autorización requerido']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM autorizaciones_manejo WHERE id = ?");
            $stmt->execute([$id]);

            registrarLog($_SESSION['usuario_id'], 'ELIMINAR_AUTORIZACION', 'AUTORIZACIONES', "Autorización eliminada: ID $id", $pdo);

            echo json_encode(['success' => true, 'message' => 'Autorización eliminada correctamente']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar autorización: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        break;
}
