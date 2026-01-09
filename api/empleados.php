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
                SELECT * FROM empleados 
                WHERE activo = 1 
                ORDER BY apellido, nombre ASC
            ");
            $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            json_response(['success' => true, 'data' => $empleados]);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Error al obtener empleados: ' . $e->getMessage()], 500);
        }
        break;
    
    case 'POST':
        if (!verificar_csrf($_POST['csrf_token'] ?? '')) {
            json_response(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }
        
        try {
            $nombre = sanitizar_input($_POST['nombre'] ?? '');
            $apellido = sanitizar_input($_POST['apellido'] ?? '');
            $dni = sanitizar_input($_POST['dni'] ?? '');
            $email = sanitizar_input($_POST['email'] ?? '');
            $telefono = sanitizar_input($_POST['telefono'] ?? '');
            $direccion = sanitizar_input($_POST['direccion'] ?? '');
            
            if (empty($nombre) || empty($apellido)) {
                json_response(['success' => false, 'message' => 'Nombre y apellido son obligatorios'], 400);
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO empleados (nombre, apellido, dni, email, telefono, direccion)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$nombre, $apellido, $dni, $email, $telefono, $direccion]);
            
            registrarLog($_SESSION['usuario_id'], 'CREAR_EMPLEADO', 'empleados', "Empleado creado: $apellido, $nombre", $pdo);
            
            json_response(['success' => true, 'message' => 'Empleado creado exitosamente']);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                json_response(['success' => false, 'message' => 'El DNI ya existe en el sistema'], 409);
            }
            json_response(['success' => false, 'message' => 'Error al crear empleado: ' . $e->getMessage()], 500);
        }
        break;

    case 'PUT':
        parse_str(file_get_contents('php://input'), $_PUT);

        if (!verificar_csrf($_PUT['csrf_token'] ?? '')) {
            json_response(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $id = (int)($_PUT['id'] ?? 0);
            $nombre = sanitizar_input($_PUT['nombre'] ?? '');
            $apellido = sanitizar_input($_PUT['apellido'] ?? '');
            $dni = sanitizar_input($_PUT['dni'] ?? '');
            $email = sanitizar_input($_PUT['email'] ?? '');
            $telefono = sanitizar_input($_PUT['telefono'] ?? '');
            $direccion = sanitizar_input($_PUT['direccion'] ?? '');

            if (empty($id) || empty($nombre) || empty($apellido)) {
                json_response(['success' => false, 'message' => 'ID, nombre y apellido son obligatorios'], 400);
            }

            $stmt = $pdo->prepare("
                UPDATE empleados SET
                    nombre = ?, apellido = ?, dni = ?, email = ?, telefono = ?, direccion = ?
                WHERE id = ?
            ");

            $stmt->execute([$nombre, $apellido, $dni, $email, $telefono, $direccion, $id]);

            registrarLog($_SESSION['usuario_id'], 'ACTUALIZAR_EMPLEADO', 'empleados', "Empleado actualizado: $apellido, $nombre (ID: $id)", $pdo);

            json_response(['success' => true, 'message' => 'Empleado actualizado exitosamente']);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                json_response(['success' => false, 'message' => 'El DNI ya existe en el sistema'], 409);
            }
            json_response(['success' => false, 'message' => 'Error al actualizar empleado: ' . $e->getMessage()], 500);
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

            // Obtener datos del empleado antes de dar de baja
            $stmt = $pdo->prepare("SELECT nombre, apellido FROM empleados WHERE id = ?");
            $stmt->execute([$id]);
            $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$empleado) {
                json_response(['success' => false, 'message' => 'Empleado no encontrado'], 404);
            }

            // Soft delete: cambiar activo a false
            $stmt = $pdo->prepare("UPDATE empleados SET activo = 0 WHERE id = ?");
            $stmt->execute([$id]);

            registrarLog($_SESSION['usuario_id'], 'DAR_BAJA_EMPLEADO', 'empleados', "Empleado dado de baja: {$empleado['apellido']}, {$empleado['nombre']} (ID: $id)", $pdo);

            json_response(['success' => true, 'message' => 'Empleado dado de baja exitosamente']);
        } catch (PDOException $e) {
            json_response(['success' => false, 'message' => 'Error al dar de baja empleado: ' . $e->getMessage()], 500);
        }
        break;

    default:
        json_response(['success' => false, 'message' => 'Método no permitido'], 405);
        break;
}
