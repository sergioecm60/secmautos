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
                    pt.*,
                    t.numero_dispositivo,
                    v.patente,
                    v.marca,
                    v.modelo
                FROM pagos_telepase pt
                INNER JOIN telepases t ON pt.telepase_id = t.id
                INNER JOIN vehiculos v ON pt.vehiculo_id = v.id
                WHERE 1=1
            ";

            $params = [];
            $conditions = [];

            // Filtros opcionales
            if (isset($_GET['telepase_id'])) {
                $conditions[] = "pt.telepase_id = ?";
                $params[] = (int)$_GET['telepase_id'];
            }

            if (isset($_GET['vehiculo_id'])) {
                $conditions[] = "pt.vehiculo_id = ?";
                $params[] = (int)$_GET['vehiculo_id'];
            }

            if (isset($_GET['estado'])) {
                $conditions[] = "pt.estado = ?";
                $params[] = sanitizar_input($_GET['estado']);
            }

            if (isset($_GET['fecha_desde'])) {
                $conditions[] = "pt.periodo >= ?";
                $params[] = $_GET['fecha_desde'];
            }

            if (isset($_GET['fecha_hasta'])) {
                $conditions[] = "pt.periodo <= ?";
                $params[] = $_GET['fecha_hasta'];
            }

            if (!empty($conditions)) {
                $sql .= " AND " . implode(" AND ", $conditions);
            }

            $sql .= " ORDER BY pt.periodo DESC, pt.fecha_vencimiento DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            json_response(['success' => true, 'data' => $pagos]);
        } catch (Exception $e) {
            error_log("Error en GET pagos_telepase: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Error al obtener pagos: ' . $e->getMessage()], 500);
        }
        break;

    case 'POST':
        try {
            // Detectar si es JSON (importación masiva) o FormData (pago individual)
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

            if (strpos($contentType, 'application/json') !== false) {
                // Importación masiva desde archivo TXT/CSV
                $input = json_decode(file_get_contents('php://input'), true);

                if (!isset($input['csrf_token']) || !verificar_csrf($input['csrf_token'])) {
                    json_response(['success' => false, 'message' => 'Token CSRF inválido'], 403);
                }

                $telepase_id = (int)($input['telepase_id'] ?? 0);
                $vehiculo_id = (int)($input['vehiculo_id'] ?? 0);
                $pagos = $input['pagos'] ?? [];

                if (empty($telepase_id) || empty($vehiculo_id) || empty($pagos)) {
                    json_response(['success' => false, 'message' => 'Datos incompletos para importación'], 400);
                }

                // Verificar que el telepase existe
                $stmt = $pdo->prepare("SELECT id FROM telepases WHERE id = ?");
                $stmt->execute([$telepase_id]);
                if (!$stmt->fetch()) {
                    json_response(['success' => false, 'message' => 'Dispositivo telepase no existe'], 400);
                }

                // Iniciar transacción
                $pdo->beginTransaction();

                try {
                    $insertados = 0;
                    $duplicados = 0;

                    $stmt = $pdo->prepare("
                        INSERT INTO pagos_telepase (
                            telepase_id, vehiculo_id, periodo, concesionario,
                            numero_comprobante, fecha_vencimiento, fecha_vencimiento_recargo,
                            monto, monto_recargo, estado, fecha_pago
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    foreach ($pagos as $pago) {
                        // Verificar si ya existe (evitar duplicados)
                        $stmtCheck = $pdo->prepare("
                            SELECT id FROM pagos_telepase
                            WHERE telepase_id = ? AND numero_comprobante = ?
                        ");
                        $stmtCheck->execute([$telepase_id, $pago['numero_comprobante']]);

                        if ($stmtCheck->fetch()) {
                            $duplicados++;
                            continue; // Saltar duplicados
                        }

                        $fecha_pago = ($pago['estado'] === 'pagado') ? date('Y-m-d') : null;

                        $stmt->execute([
                            $telepase_id,
                            $vehiculo_id,
                            $pago['periodo'],
                            $pago['concesionario'],
                            $pago['numero_comprobante'],
                            $pago['fecha_vencimiento'],
                            $pago['fecha_vencimiento_recargo'] ?? null,
                            $pago['monto'],
                            $pago['monto_recargo'] ?? null,
                            $pago['estado'],
                            $fecha_pago
                        ]);

                        $insertados++;
                    }

                    registrarLog(
                        $_SESSION['usuario_id'],
                        'IMPORTAR_PAGOS_TELEPASE',
                        'pagos_telepase',
                        "Importados $insertados pagos para telepase ID: $telepase_id (Duplicados: $duplicados)",
                        $pdo
                    );

                    $pdo->commit();

                    json_response([
                        'success' => true,
                        'message' => "Se importaron $insertados pagos correctamente" .
                                     ($duplicados > 0 ? " ($duplicados duplicados omitidos)" : ""),
                        'insertados' => $insertados,
                        'duplicados' => $duplicados
                    ]);
                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw $e;
                }
            } else {
                // Pago individual
                if (!verificar_csrf($_POST['csrf_token'] ?? '')) {
                    json_response(['success' => false, 'message' => 'Token CSRF inválido'], 403);
                }

                $telepase_id = (int)($_POST['telepase_id'] ?? 0);
                $vehiculo_id = (int)($_POST['vehiculo_id'] ?? 0);
                $periodo = $_POST['periodo'] ?? '';
                $concesionario = sanitizar_input($_POST['concesionario'] ?? '');
                $numero_comprobante = sanitizar_input($_POST['numero_comprobante'] ?? '');
                $fecha_vencimiento = $_POST['fecha_vencimiento'] ?? '';
                $fecha_vencimiento_recargo = !empty($_POST['fecha_vencimiento_recargo']) ? $_POST['fecha_vencimiento_recargo'] : null;
                $monto = (float)($_POST['monto'] ?? 0);
                $monto_recargo = !empty($_POST['monto_recargo']) ? (float)$_POST['monto_recargo'] : null;
                $estado = sanitizar_input($_POST['estado'] ?? 'pendiente');
                $fecha_pago = !empty($_POST['fecha_pago']) ? $_POST['fecha_pago'] : null;

                // Validaciones
                if (empty($telepase_id) || empty($vehiculo_id) || empty($periodo) ||
                    empty($concesionario) || empty($numero_comprobante) ||
                    empty($fecha_vencimiento) || empty($monto)) {
                    json_response(['success' => false, 'message' => 'Campos obligatorios faltantes'], 400);
                }

                if (!in_array($estado, ['pendiente', 'pagado', 'vencido'])) {
                    json_response(['success' => false, 'message' => 'Estado inválido'], 400);
                }

                // Verificar duplicados
                $stmt = $pdo->prepare("
                    SELECT id FROM pagos_telepase
                    WHERE telepase_id = ? AND numero_comprobante = ?
                ");
                $stmt->execute([$telepase_id, $numero_comprobante]);
                if ($stmt->fetch()) {
                    json_response(['success' => false, 'message' => 'Ya existe un pago con ese número de comprobante'], 400);
                }

                // Insertar pago
                $stmt = $pdo->prepare("
                    INSERT INTO pagos_telepase (
                        telepase_id, vehiculo_id, periodo, concesionario,
                        numero_comprobante, fecha_vencimiento, fecha_vencimiento_recargo,
                        monto, monto_recargo, estado, fecha_pago, observaciones
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $telepase_id,
                    $vehiculo_id,
                    $periodo,
                    $concesionario,
                    $numero_comprobante,
                    $fecha_vencimiento,
                    $fecha_vencimiento_recargo,
                    $monto,
                    $monto_recargo,
                    $estado,
                    $fecha_pago,
                    sanitizar_input($_POST['observaciones'] ?? '')
                ]);

                $pago_id = $pdo->lastInsertId();

                registrarLog(
                    $_SESSION['usuario_id'],
                    'CREAR_PAGO_TELEPASE',
                    'pagos_telepase',
                    "Pago telepase registrado - Comprobante: $numero_comprobante",
                    $pdo
                );

                json_response([
                    'success' => true,
                    'message' => 'Pago registrado exitosamente',
                    'id' => $pago_id
                ]);
            }
        } catch (Exception $e) {
            error_log("Error en POST pagos_telepase: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Error al registrar pago: ' . $e->getMessage()], 500);
        }
        break;

    case 'PUT':
        parse_str(file_get_contents('php://input'), $_PUT);

        if (!verificar_csrf($_PUT['csrf_token'] ?? '')) {
            json_response(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $id = (int)($_PUT['id'] ?? 0);

            if (empty($id)) {
                json_response(['success' => false, 'message' => 'ID de pago requerido'], 400);
            }

            // Verificar que el pago existe
            $stmt = $pdo->prepare("SELECT * FROM pagos_telepase WHERE id = ?");
            $stmt->execute([$id]);
            $pago = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$pago) {
                json_response(['success' => false, 'message' => 'Pago no encontrado'], 404);
            }

            // Campos actualizables
            $estado = isset($_PUT['estado']) ? sanitizar_input($_PUT['estado']) : $pago['estado'];
            $fecha_pago = isset($_PUT['fecha_pago']) ? $_PUT['fecha_pago'] : $pago['fecha_pago'];
            $observaciones = isset($_PUT['observaciones']) ? sanitizar_input($_PUT['observaciones']) : $pago['observaciones'];

            if (!in_array($estado, ['pendiente', 'pagado', 'vencido'])) {
                json_response(['success' => false, 'message' => 'Estado inválido'], 400);
            }

            // Si se marca como pagado y no tiene fecha, usar hoy
            if ($estado === 'pagado' && empty($fecha_pago)) {
                $fecha_pago = date('Y-m-d');
            }

            // Actualizar pago
            $stmt = $pdo->prepare("
                UPDATE pagos_telepase
                SET estado = ?,
                    fecha_pago = ?,
                    observaciones = ?
                WHERE id = ?
            ");
            $stmt->execute([$estado, $fecha_pago, $observaciones, $id]);

            registrarLog(
                $_SESSION['usuario_id'],
                'ACTUALIZAR_PAGO_TELEPASE',
                'pagos_telepase',
                "Pago telepase ID: $id actualizado a estado: $estado",
                $pdo
            );

            json_response([
                'success' => true,
                'message' => 'Pago actualizado correctamente'
            ]);
        } catch (Exception $e) {
            error_log("Error en PUT pagos_telepase: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Error al actualizar pago: ' . $e->getMessage()], 500);
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
                json_response(['success' => false, 'message' => 'ID de pago requerido'], 400);
            }

            // Verificar que el pago existe
            $stmt = $pdo->prepare("SELECT numero_comprobante FROM pagos_telepase WHERE id = ?");
            $stmt->execute([$id]);
            $pago = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$pago) {
                json_response(['success' => false, 'message' => 'Pago no encontrado'], 404);
            }

            // Eliminar pago
            $stmt = $pdo->prepare("DELETE FROM pagos_telepase WHERE id = ?");
            $stmt->execute([$id]);

            registrarLog(
                $_SESSION['usuario_id'],
                'ELIMINAR_PAGO_TELEPASE',
                'pagos_telepase',
                "Pago telepase eliminado - Comprobante: {$pago['numero_comprobante']}",
                $pdo
            );

            json_response([
                'success' => true,
                'message' => 'Pago eliminado exitosamente'
            ]);
        } catch (Exception $e) {
            error_log("Error en DELETE pagos_telepase: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Error al eliminar pago: ' . $e->getMessage()], 500);
        }
        break;

    default:
        json_response(['success' => false, 'message' => 'Método no permitido'], 405);
        break;
}
