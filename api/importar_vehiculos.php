<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!verificar_autenticacion()) {
    json_response(['success' => false, 'message' => 'No autenticado'], 401);
}

// Solo administradores pueden importar
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['superadmin', 'admin'])) {
    json_response(['success' => false, 'message' => 'No tiene permisos para importar vehículos'], 403);
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Obtener datos del archivo JSON para preview
        try {
            $json_file = __DIR__ . '/../docs y dbs/vehiculos_importar.json';

            if (!file_exists($json_file)) {
                json_response(['success' => false, 'message' => 'Archivo de importación no encontrado'], 404);
            }

            $data = json_decode(file_get_contents($json_file), true);

            if (!$data || !isset($data['vehiculos'])) {
                json_response(['success' => false, 'message' => 'Formato de archivo inválido'], 400);
            }

            // Verificar duplicados
            $patentes_existentes = [];
            $stmt = $pdo->query("SELECT patente FROM vehiculos");
            while ($row = $stmt->fetch()) {
                $patentes_existentes[] = $row['patente'];
            }

            // Marcar vehículos que ya existen
            foreach ($data['vehiculos'] as &$vehiculo) {
                $vehiculo['existe'] = in_array($vehiculo['patente'], $patentes_existentes);
            }

            json_response([
                'success' => true,
                'data' => $data,
                'patentes_existentes' => $patentes_existentes
            ]);

        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Error al leer archivo: ' . $e->getMessage()], 500);
        }
        break;

    case 'POST':
        // Ejecutar importación
        if (!verificar_csrf($_POST['csrf_token'] ?? '')) {
            json_response(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $json_file = __DIR__ . '/../docs y dbs/vehiculos_importar.json';

            if (!file_exists($json_file)) {
                json_response(['success' => false, 'message' => 'Archivo de importación no encontrado'], 404);
            }

            $data = json_decode(file_get_contents($json_file), true);

            if (!$data || !isset($data['vehiculos'])) {
                json_response(['success' => false, 'message' => 'Formato de archivo inválido'], 400);
            }

            $modo = $_POST['modo'] ?? 'crear_nuevos'; // 'crear_nuevos' o 'actualizar_existentes'

            $pdo->beginTransaction();

            $stats = [
                'total' => count($data['vehiculos']),
                'creados' => 0,
                'actualizados' => 0,
                'omitidos' => 0,
                'errores' => []
            ];

            foreach ($data['vehiculos'] as $vehiculo) {
                try {
                    // Verificar si existe
                    $stmt = $pdo->prepare("SELECT id FROM vehiculos WHERE patente = ?");
                    $stmt->execute([$vehiculo['patente']]);
                    $existe = $stmt->fetch();

                    if ($existe) {
                        if ($modo === 'actualizar_existentes') {
                            // Actualizar
                            $stmt = $pdo->prepare("
                                UPDATE vehiculos SET
                                    marca = ?, modelo = ?, anio = ?, motor = ?, chasis = ?, titulo_dnrpa = ?,
                                    titularidad = ?, empleado_actual = ?, tipo_vehiculo = ?, estado = ?, observaciones = ?
                                WHERE patente = ?
                            ");

                            $stmt->execute([
                                $vehiculo['marca'],
                                $vehiculo['modelo'],
                                $vehiculo['anio'],
                                $vehiculo['motor'],
                                $vehiculo['chasis'],
                                $vehiculo['titulo_dnrpa'],
                                $vehiculo['titularidad'],
                                $vehiculo['empleado_actual'],  // NUEVO: empleado que usa el vehículo
                                $vehiculo['tipo_vehiculo'] ?: 'Auto',
                                $vehiculo['estado'] ?: 'disponible',
                                $vehiculo['observaciones'],
                                $vehiculo['patente']
                            ]);

                            $stats['actualizados']++;
                        } else {
                            $stats['omitidos']++;
                        }
                    } else {
                        // Crear nuevo
                        $stmt = $pdo->prepare("
                            INSERT INTO vehiculos (
                                patente, marca, modelo, anio, motor, chasis, titulo_dnrpa,
                                titularidad, empleado_actual, tipo_vehiculo, estado, observaciones, kilometraje_actual
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
                        ");

                        $stmt->execute([
                            $vehiculo['patente'],
                            $vehiculo['marca'],
                            $vehiculo['modelo'],
                            $vehiculo['anio'],
                            $vehiculo['motor'],
                            $vehiculo['chasis'],
                            $vehiculo['titulo_dnrpa'],
                            $vehiculo['titularidad'],
                            $vehiculo['empleado_actual'],  // NUEVO: empleado que usa el vehículo
                            $vehiculo['tipo_vehiculo'] ?: 'Auto',
                            $vehiculo['estado'] ?: 'disponible',
                            $vehiculo['observaciones']
                        ]);

                        $stats['creados']++;
                    }

                } catch (Exception $e) {
                    $stats['errores'][] = [
                        'patente' => $vehiculo['patente'],
                        'error' => $e->getMessage()
                    ];
                }
            }

            $pdo->commit();

            // Registrar en log
            registrarLog(
                $_SESSION['usuario_id'],
                'IMPORTAR_VEHICULOS',
                'vehiculos',
                "Importación masiva: {$stats['creados']} creados, {$stats['actualizados']} actualizados, {$stats['omitidos']} omitidos",
                $pdo
            );

            json_response([
                'success' => true,
                'message' => 'Importación completada',
                'stats' => $stats
            ]);

        } catch (Exception $e) {
            $pdo->rollBack();
            json_response(['success' => false, 'message' => 'Error en la importación: ' . $e->getMessage()], 500);
        }
        break;

    default:
        json_response(['success' => false, 'message' => 'Método no permitido'], 405);
        break;
}
