<?php
require_once __DIR__ . '/bootstrap.php';
requiereAutenticacion();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $id = sanitizeId($_GET['id']);
            if ($id === null) {
                json_response(['success' => false, 'message' => 'ID inválido'], 400);
                return;
            }

            $stmt = $pdo->prepare("SELECT id, nombre, apellido, email, rol, activo, ultimo_acceso FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                json_response(['success' => true, 'data' => $usuario]);
            } else {
                json_response(['success' => false, 'message' => 'Usuario no encontrado'], 404);
            }
        } else {
            $stmt = $pdo->query("SELECT id, nombre, apellido, email, rol, activo, ultimo_acceso FROM usuarios ORDER BY id DESC");
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            json_response(['success' => true, 'data' => $usuarios]);
        }
        break;

    case 'POST':
        $token = $_POST['csrf_token'] ?? '';
        if (!verificar_csrf($token)) {
            json_response(['success' => false, 'message' => 'Error de CSRF'], 403);
            return;
        }

        $nombre = sanitizeInput($_POST['nombre'] ?? '', 'string');
        $apellido = sanitizeInput($_POST['apellido'] ?? '', 'string');
        $email = sanitizeInput($_POST['email'] ?? '', 'email');
        $password = sanitizeInput($_POST['password'] ?? '', 'string');
        $rol = sanitizeInput($_POST['rol'] ?? 'user', 'string');
        $activo = isset($_POST['activo']) ? 1 : 0;

        if (empty($nombre) || empty($apellido) || empty($email) || empty($password)) {
            json_response(['success' => false, 'message' => 'Todos los campos obligatorios son requeridos'], 400);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['success' => false, 'message' => 'Email inválido'], 400);
            return;
        }

        if (!in_array($rol, ['superadmin', 'admin', 'user'])) {
            json_response(['success' => false, 'message' => 'Rol inválido'], 400);
            return;
        }

        try {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                json_response(['success' => false, 'message' => 'El email ya está registrado'], 400);
                return;
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, apellido, email, password_hash, rol, activo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $apellido, $email, $password_hash, $rol, $activo]);

            registrarLog($_SESSION['usuario_id'], 'CREAR_USUARIO', 'USUARIOS', "Usuario creado: $email", $pdo);

            json_response(['success' => true, 'message' => 'Usuario creado correctamente']);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Error al crear usuario: ' . $e->getMessage()], 500);
        }
        break;

    case 'PUT':
        parse_str(file_get_contents("php://input"), $data);
        $token = $data['csrf_token'] ?? '';
        if (!verificar_csrf($token)) {
            json_response(['success' => false, 'message' => 'Error de CSRF'], 403);
            return;
        }

        $id = sanitizeId($data['id'] ?? '');
        $nombre = sanitizeInput($data['nombre'] ?? '', 'string');
        $apellido = sanitizeInput($data['apellido'] ?? '', 'string');
        $email = sanitizeInput($data['email'] ?? '', 'email');
        $rol = sanitizeInput($data['rol'] ?? 'user', 'string');
        $activo = isset($data['activo']) ? 1 : 0;
        $cambiar_password = !empty(trim($data['password'] ?? ''));
        $password = sanitizeInput($data['password'] ?? '', 'string');

        if (empty($id) || empty($nombre) || empty($apellido) || empty($email)) {
            json_response(['success' => false, 'message' => 'Todos los campos obligatorios son requeridos'], 400);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['success' => false, 'message' => 'Email inválido'], 400);
            return;
        }

        if (!in_array($rol, ['superadmin', 'admin', 'user'])) {
            json_response(['success' => false, 'message' => 'Rol inválido'], 400);
            return;
        }

        try {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);

            if ($stmt->fetch()) {
                json_response(['success' => false, 'message' => 'El email ya está registrado en otro usuario'], 400);
                return;
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

            json_response(['success' => true, 'message' => 'Usuario actualizado correctamente']);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Error al actualizar usuario: ' . $e->getMessage()], 500);
        }
        break;

    case 'DELETE':
        parse_str(file_get_contents("php://input"), $data);
        $token = $data['csrf_token'] ?? '';
        if (!verificar_csrf($token)) {
            json_response(['success' => false, 'message' => 'Error de CSRF'], 403);
            return;
        }

        $id = sanitizeId($data['id'] ?? '');

        if (empty($id)) {
            json_response(['success' => false, 'message' => 'ID de usuario requerido'], 400);
            return;
        }

        if ($id == $_SESSION['usuario_id']) {
            json_response(['success' => false, 'message' => 'No puedes eliminar tu propio usuario'], 403);
            return;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);

            registrarLog($_SESSION['usuario_id'], 'ELIMINAR_USUARIO', 'USUARIOS', "Usuario eliminado: ID $id", $pdo);

            json_response(['success' => true, 'message' => 'Usuario eliminado correctamente']);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Error al eliminar usuario: ' . $e->getMessage()], 500);
        }
        break;

    default:
        json_response(['success' => false, 'message' => 'Método no permitido'], 405);
        break;
}
