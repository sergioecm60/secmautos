<?php
require_once __DIR__ . '/../bootstrap.php';
requiereAutenticacion();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT id, nombre, apellido, email, rol, activo, ultimo_acceso FROM usuarios WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                echo json_encode(['success' => true, 'data' => $usuario]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
            }
        } else {
            $stmt = $pdo->query("SELECT id, nombre, apellido, email, rol, activo, ultimo_acceso FROM usuarios ORDER BY id DESC");
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $usuarios]);
        }
        break;

    case 'POST':
        $token = $_POST['csrf_token'] ?? '';
        if (!verificar_csrf($token)) {
            echo json_encode(['success' => false, 'message' => 'Error de CSRF']);
            exit;
        }

        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $rol = trim($_POST['rol'] ?? 'user');
        $activo = isset($_POST['activo']) ? 1 : 0;

        if (empty($nombre) || empty($apellido) || empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos obligatorios son requeridos']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Email inválido']);
            exit;
        }

        if (!in_array($rol, ['superadmin', 'admin', 'user'])) {
            echo json_encode(['success' => false, 'message' => 'Rol inválido']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'El email ya está registrado']);
                exit;
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, apellido, email, password_hash, rol, activo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $apellido, $email, $password_hash, $rol, $activo]);

            registrarLog($_SESSION['usuario_id'], 'CREAR_USUARIO', 'USUARIOS', "Usuario creado: $email", $pdo);

            echo json_encode(['success' => true, 'message' => 'Usuario creado correctamente']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al crear usuario: ' . $e->getMessage()]);
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
        $nombre = trim($data['nombre'] ?? '');
        $apellido = trim($data['apellido'] ?? '');
        $email = trim($data['email'] ?? '');
        $rol = trim($data['rol'] ?? 'user');
        $activo = isset($data['activo']) ? 1 : 0;
        $cambiar_password = !empty(trim($data['password'] ?? ''));
        $password = trim($data['password'] ?? '');

        if (empty($id) || empty($nombre) || empty($apellido) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos obligatorios son requeridos']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Email inválido']);
            exit;
        }

        if (!in_array($rol, ['superadmin', 'admin', 'user'])) {
            echo json_encode(['success' => false, 'message' => 'Rol inválido']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);

            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'El email ya está registrado en otro usuario']);
                exit;
            }

            if ($cambiar_password) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, rol = ?, activo = ?, password_hash = ? WHERE id = ?");
                $stmt->execute([$nombre, $apellido, $email, $rol, $activo, $password_hash, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, rol = ?, activo = ? WHERE id = ?");
                $stmt->execute([$nombre, $apellido, $email, $rol, $activo, $id]);
            }

            registrarLog($_SESSION['usuario_id'], 'EDITAR_USUARIO', 'USUARIOS', "Usuario editado: ID $id", $pdo);

            echo json_encode(['success' => true, 'message' => 'Usuario actualizado correctamente']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar usuario: ' . $e->getMessage()]);
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
            echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
            exit;
        }

        if ($id == $_SESSION['usuario_id']) {
            echo json_encode(['success' => false, 'message' => 'No puedes eliminar tu propio usuario']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);

            registrarLog($_SESSION['usuario_id'], 'ELIMINAR_USUARIO', 'USUARIOS', "Usuario eliminado: ID $id", $pdo);

            echo json_encode(['success' => true, 'message' => 'Usuario eliminado correctamente']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar usuario: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        break;
}
