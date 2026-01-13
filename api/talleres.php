<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!verificar_autenticacion()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        obtenerTalleres();
        break;
    case 'POST':
        crearTaller();
        break;
    case 'PUT':
        actualizarTaller();
        break;
    case 'DELETE':
        eliminarTaller();
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        break;
}

function obtenerTalleres() {
    global $pdo;

    $activo = $_GET['activo'] ?? null;
    $id = $_GET['id'] ?? null;

    $sql = "SELECT * FROM talleres WHERE 1=1";
    $params = [];

    if ($activo !== null) {
        $sql .= " AND activo = ?";
        $params[] = $activo;
    }

    if ($id) {
        $sql .= " AND id = ?";
        $params[] = $id;
    }

    $sql .= " ORDER BY nombre ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $talleres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $talleres]);
}

function crearTaller() {
    global $pdo;

    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verificar_csrf($csrf_token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
        return;
    }

    $nombre = trim($_POST['nombre'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contacto_principal = trim($_POST['contacto_principal'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');

    if (empty($nombre)) {
        echo json_encode(['success' => false, 'message' => 'El nombre del taller es obligatorio']);
        return;
    }

    try {
        $sql = "INSERT INTO talleres (nombre, direccion, telefono, email, contacto_principal, observaciones)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $direccion, $telefono, $email, $contacto_principal, $observaciones]);

        echo json_encode(['success' => true, 'message' => 'Taller registrado correctamente']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al registrar taller: ' . $e->getMessage()]);
    }
}

function actualizarTaller() {
    global $pdo;

    parse_str(file_get_contents('php://input'), $data);

    $csrf_token = $data['csrf_token'] ?? '';

    if (!verificar_csrf($csrf_token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
        return;
    }

    $id = $data['id'] ?? '';

    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID requerido']);
        return;
    }

    $nombre = trim($data['nombre'] ?? '');
    $direccion = trim($data['direccion'] ?? '');
    $telefono = trim($data['telefono'] ?? '');
    $email = trim($data['email'] ?? '');
    $contacto_principal = trim($data['contacto_principal'] ?? '');
    $observaciones = trim($data['observaciones'] ?? '');
    $activo = isset($data['activo']) ? 1 : 0;

    if (empty($nombre)) {
        echo json_encode(['success' => false, 'message' => 'El nombre del taller es obligatorio']);
        return;
    }

    try {
        $sql = "UPDATE talleres SET
                nombre = ?,
                direccion = ?,
                telefono = ?,
                email = ?,
                contacto_principal = ?,
                observaciones = ?,
                activo = ?
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $direccion, $telefono, $email, $contacto_principal, $observaciones, $activo, $id]);

        echo json_encode(['success' => true, 'message' => 'Taller actualizado correctamente']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar taller: ' . $e->getMessage()]);
    }
}

function eliminarTaller() {
    global $pdo;

    parse_str(file_get_contents('php://input'), $data);

    $csrf_token = $data['csrf_token'] ?? '';

    if (!verificar_csrf($csrf_token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
        return;
    }

    $id = $data['id'] ?? '';

    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID requerido']);
        return;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM talleres WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'Taller eliminado correctamente']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar taller: ' . $e->getMessage()]);
    }
}
