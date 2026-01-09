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
                    m.*,
                    v.patente, v.marca, v.modelo,
                    CONCAT(e.nombre, ' ', e.apellido) as empleado_nombre
                FROM multas m
                JOIN vehiculos v ON m.vehiculo_id = v.id
                JOIN empleados e ON m.empleado_id = e.id
                ORDER BY m.fecha_multa DESC, m.created_at DESC
            ");
            $multas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            json_response(['success' => true, 'data' => $multas]);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Error al obtener multas: ' . $e->getMessage()], 500);
        }
        break;
    
    case 'POST':
        if (!verificar_csrf($_POST['csrf_token'] ?? '')) {
            json_response(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }
        
        try {
            $vehiculo_id = (int)($_POST['vehiculo_id'] ?? 0);
            $empleado_id = (int)($_POST['empleado_id'] ?? 0);
            $fecha_multa = $_POST['fecha_multa'] ?? '';
            $monto = (float)($_POST['monto'] ?? 0);
            $motivo = sanitizar_input($_POST['motivo'] ?? '');
            $acta_numero = sanitizar_input($_POST['acta_numero'] ?? '');
            $observaciones = sanitizar_input($_POST['observaciones'] ?? '');
            
            if (empty($vehiculo_id) || empty($empleado_id) || empty($fecha_multa)) {
                json_response(['success' => false, 'message' => 'Vehículo, empleado y fecha son obligatorios'], 400);
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO multas (vehiculo_id, empleado_id, fecha_multa, monto, motivo, acta_numero, observaciones)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$vehiculo_id, $empleado_id, $fecha_multa, $monto, $motivo, $acta_numero, $observaciones]);
            
            registrarLog($_SESSION['usuario_id'], 'REGISTRAR_MULTA', 'multas', "Multa registrada en vehículo $vehiculo_id", $pdo);
            
            json_response(['success' => true, 'message' => 'Multa registrada exitosamente']);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Error al registrar multa: ' . $e->getMessage()], 500);
        }
        break;
    
    default:
        json_response(['success' => false, 'message' => 'Método no permitido'], 405);
        break;
}
