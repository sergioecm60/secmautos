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
    
    default:
        json_response(['success' => false, 'message' => 'Método no permitido'], 405);
        break;
}
