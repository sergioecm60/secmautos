<?php
require_once __DIR__ . '/../bootstrap.php';
requiereAutenticacion();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                json_response(['success' => false, 'message' => 'ID inválido'], 400);
                return;
            }

            $stmt = $pdo->prepare("SELECT id, username, nombre, apellido, email, rol, activo, ultimo_acceso FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                json_response(['success' => true, 'data' => $usuario]);
            } else {
                json_response(['success' => false, 'message' => 'Usuario no encontrado'], 404);
            }
        } else {
            $stmt = $pdo->query("SELECT id, username, nombre, apellido, email, rol, activo, ultimo_acceso FROM usuarios ORDER BY id DESC");
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

        $username = sanitizar_input($_POST['username'] ?? '');
        $nombre = sanitizar_input($_POST['nombre'] ?? '');
        $apellido = sanitizar_input($_POST['apellido'] ?? '');
        $email = sanitizar_input($_POST['email'] ?? '');
        $password = sanitizar_input($_POST['password'] ?? '');
        $activo = isset($_POST['activo']) ?1 : 0;

        if (empty($username) || empty($password)) {
            json_response(['success' => false, 'message' => 'Usuario y contraseña son obligatorios'], 400);
            return;
        }

        // Validar formato del username (solo letras, números y puntos)
        if (!preg_match('/^[a-zA-Z0-9.]+$/', $username)) {
            json_response(['success' => false, 'message' => 'El usuario solo puede contener letras, números y puntos'], 400);
            return;
        }

        // Si se proporciona email, validarlo
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['success' => false, 'message' => 'Email inválido'], 400);
            return;
        }

        try {
            // Verificar que el username no exista
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                json_response(['success' => false, 'message' => 'El usuario ya existe'], 400);
                return;
            }

            // Si se proporciona email, verificar que no exista
            if (!empty($email)) {
                $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    json_response(['success' => false, 'message' => 'El email ya está registrado'], 400);
                    return;
                }
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO usuarios (username, nombre, apellido, email, password_hash, activo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $nombre, $apellido, $email ?: null, $password_hash, $activo]);

            registrarLog($_SESSION['usuario_id'], 'CREAR_USUARIO', 'USUARIOS', "Usuario creado: $username", $pdo);

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

        $id = (int)($data['id'] ?? 0);
        $username = sanitizar_input($data['username'] ?? '');
        $nombre = sanitizar_input($data['nombre'] ?? '');
        $apellido = sanitizar_input($data['apellido'] ?? '');
        $email = sanitizar_input($data['email'] ?? '');
        $activo = isset($data['activo']) ? 1 : 0;
        $cambiar_password = !empty(trim($data['password'] ?? ''));
        $password = sanitizar_input($data['password'] ?? '');

        if (empty($id) || empty($username)) {
            json_response(['success' => false, 'message' => 'El usuario es obligatorio'], 400);
            return;
        }

        // Validar formato del username
        if (!preg_match('/^[a-zA-Z0-9.]+$/', $username)) {
            json_response(['success' => false, 'message' => 'El usuario solo puede contener letras, números y puntos'], 400);
            return;
        }

        // Si se proporciona email, validarlo
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['success' => false, 'message' => 'Email inválido'], 400);
            return;
        }

        try {
            // Verificar que el username no esté duplicado
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ? AND id != ?");
            $stmt->execute([$username, $id]);
            if ($stmt->fetch()) {
                json_response(['success' => false, 'message' => 'El usuario ya está registrado'], 400);
                return;
            }

            // Si se proporciona email, verificar que no esté duplicado
            if (!empty($email)) {
                $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
                $stmt->execute([$email, $id]);
                if ($stmt->fetch()) {
                    json_response(['success' => false, 'message' => 'El email ya está registrado en otro usuario'], 400);
                    return;
                }
            }

            if ($cambiar_password) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET username = ?, nombre = ?, apellido = ?, email = ?, activo = ?, password_hash = ? WHERE id = ?");
                $stmt->execute([$username, $nombre, $apellido, $email ?: null, $activo, $password_hash, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET username = ?, nombre = ?, apellido = ?, email = ?, activo = ? WHERE id = ?");
                $stmt->execute([$username, $nombre, $apellido, $email ?: null, $activo, $id]);
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

        $id = (int)($data['id'] ?? 0);
        if ($id <= 0) {
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
