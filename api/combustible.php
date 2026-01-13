<?php
require_once '../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function exportarCSV($pdo, $patente, $fecha_desde, $fecha_hasta) {
    $sql = "SELECT c.*, v.marca, v.modelo, v.patente
              FROM consumo_combustible c
              LEFT JOIN vehiculos v ON c.patente = v.patente
              WHERE 1=1";
    $params = [];

    if (!empty($patente)) {
        $sql .= " AND c.patente LIKE ?";
        $params[] = "%$patente%";
    }

    if (!empty($fecha_desde)) {
        $sql .= " AND c.fecha_carga >= ?";
        $params[] = $fecha_desde . ' 00:00:00';
    }

    if (!empty($fecha_hasta)) {
        $sql .= " AND c.fecha_carga <= ?";
        $params[] = $fecha_hasta . ' 23:59:59';
    }

    $sql .= " ORDER BY c.fecha_carga DESC, c.hora_carga DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="consumo_combustible_' . date('Y-m-d') . '.csv"');
    header('Cache-Control: no-cache, no-store, must-revalidate');

    $output = fopen('php://output', 'w');
    fprintf($output, "Fecha/Hora,Patente,Vehículo,Conductor,Tipo,Litros,Precio/Litro,Odómetro,Km Recorridos,Rendimiento,Costo Total\n");

    foreach ($registros as $r) {
        fprintf($output, "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
            $r['fecha_carga'] . ' ' . $r['hora_carga'],
            $r['patente'],
            $r['marca'] . ' ' . $r['modelo'],
            $r['conductor'],
            $r['tipo_comb'],
            number_format($r['litros'], 2, ',', '.'),
            number_format($r['precio_litro'], 2, ',', '.'),
            number_format($r['odometro'], 0, ',', '.'),
            $r['km_recorridos'] !== null ? $r['km_recorridos'] : '',
            $r['rendimiento'] !== null ? $r['rendimiento'] : '',
            number_format($r['costo_total'], 2, ',', '.')
        );
    }

    fclose($output);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        obtenerRegistros();
        break;
    case 'POST':
        crearRegistro();
        break;
    case 'PUT':
        actualizarRegistro();
        break;
    case 'DELETE':
        eliminarRegistro();
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        break;
}

function obtenerRegistros() {
    global $pdo;

    $patente = $_GET['patente'] ?? '';
    $fecha_desde = $_GET['fecha_desde'] ?? '';
    $fecha_hasta = $_GET['fecha_hasta'] ?? '';

    // Verificar si es exportación CSV
    if (isset($_GET['exportar'])) {
        exportarCSV($pdo, $patente, $fecha_desde, $fecha_hasta);
        return;
    }

    $sql = "SELECT c.*, v.marca, v.modelo
              FROM consumo_combustible c
              LEFT JOIN vehiculos v ON c.patente = v.patente
              WHERE 1=1";
    $params = [];

    if (!empty($patente)) {
        $sql .= " AND c.patente LIKE ?";
        $params[] = "%$patente%";
    }

    if (!empty($fecha_desde)) {
        $sql .= " AND c.fecha_carga >= ?";
        $params[] = $fecha_desde . ' 00:00:00';
    }

    if (!empty($fecha_hasta)) {
        $sql .= " AND c.fecha_carga <= ?";
        $params[] = $fecha_hasta . ' 23:59:59';
    }

    $sql .= " ORDER BY c.fecha_carga DESC, c.hora_carga DESC LIMIT 200";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $registros]);
}

function crearRegistro() {
    global $pdo;

    if (!verificar_autenticacion()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'No autenticado']);
        return;
    }

    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verificar_csrf($csrf_token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
        return;
    }

    $patente = strtoupper(trim($_POST['patente'] ?? ''));
    $marca = trim($_POST['marca'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $version = trim($_POST['version'] ?? '');
    $conductor = trim($_POST['conductor'] ?? '');
    $fecha_carga = $_POST['fecha_carga'] ?? '';
    $hora_carga = $_POST['hora_carga'] ?? '';
    $lugar_carga = trim($_POST['lugar_carga'] ?? '');
    $tipo_comb = $_POST['tipo_comb'] ?? 'Nafta';
    $litros = floatval($_POST['litros'] ?? 0);
    $precio_litro = floatval($_POST['precio_litro'] ?? 0);
    $odometro = (int)($_POST['odometro'] ?? 0);
    $observaciones = trim($_POST['observaciones'] ?? '');

    if (empty($patente) || empty($conductor) || empty($fecha_carga) || empty($hora_carga) || $litros <= 0 || $precio_litro <= 0 || $odometro < 0) {
        echo json_encode(['success' => false, 'message' => 'Faltan campos requeridos']);
        return;
    }

    $pdo->beginTransaction();

    try {
        // Obtener último odómetro para este vehículo (si existe)
        $stmt = $pdo->prepare("SELECT odometro FROM consumo_combustible WHERE patente = ? ORDER BY fecha_carga DESC, hora_carga DESC LIMIT 1");
        $stmt->execute([$patente]);
        $km_anterior = $stmt->fetchColumn();

        // Insertar registro de consumo
        $sql = "INSERT INTO consumo_combustible
                (patente, marca, modelo, version, conductor, fecha_carga, hora_carga, lugar_carga, tipo_comb, litros, precio_litro, odometro, km_anterior, observaciones)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $patente,
            $marca,
            $modelo,
            $version,
            $conductor,
            $fecha_carga,
            $hora_carga,
            $lugar_carga,
            $tipo_comb,
            $litros,
            $precio_litro,
            $odometro,
            $km_anterior ?: null,
            $observaciones
        ]);

        // Actualizar odómetro del vehículo
        $stmt = $pdo->prepare("UPDATE vehiculos SET kilometraje_actual = ? WHERE patente = ?");
        $stmt->execute([$odometro, $patente]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Registro guardado correctamente']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error al guardar registro: ' . $e->getMessage()]);
    }
}

function actualizarRegistro() {
    global $pdo;

    parse_str(file_get_contents('php://input'), $data);

    if (!verificar_autenticacion()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'No autenticado']);
        return;
    }

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

    $patente = strtoupper(trim($data['patente'] ?? ''));
    $marca = trim($data['marca'] ?? '');
    $modelo = trim($data['modelo'] ?? '');
    $version = trim($data['version'] ?? '');
    $conductor = trim($data['conductor'] ?? '');
    $fecha_carga = $data['fecha_carga'] ?? '';
    $hora_carga = $data['hora_carga'] ?? '';
    $lugar_carga = trim($data['lugar_carga'] ?? '');
    $tipo_comb = $data['tipo_comb'] ?? 'Nafta';
    $litros = floatval($data['litros'] ?? 0);
    $precio_litro = floatval($data['precio_litro'] ?? 0);
    $odometro = (int)($data['odometro'] ?? 0);
    $observaciones = trim($data['observaciones'] ?? '');

    if ($litros <= 0 || $precio_litro <= 0 || $odometro < 0) {
        echo json_encode(['success' => false, 'message' => 'Valores inválidos']);
        return;
    }

    $pdo->beginTransaction();

    try {
        $sql = "UPDATE consumo_combustible SET
                patente = ?,
                marca = ?,
                modelo = ?,
                version = ?,
                conductor = ?,
                fecha_carga = ?,
                hora_carga = ?,
                lugar_carga = ?,
                tipo_comb = ?,
                litros = ?,
                precio_litro = ?,
                odometro = ?,
                observaciones = ?
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $patente,
            $marca,
            $modelo,
            $version,
            $conductor,
            $fecha_carga,
            $hora_carga,
            $lugar_carga,
            $tipo_comb,
            $litros,
            $precio_litro,
            $odometro,
            $observaciones,
            $id
        ]);

        // Actualizar odómetro del vehículo (si este es el registro más reciente)
        $stmt = $pdo->prepare("SELECT odometro FROM consumo_combustible WHERE patente = ? AND id != ? ORDER BY fecha_carga DESC, hora_carga DESC LIMIT 1");
        $stmt->execute([$patente, $id]);
        $ultimo_odometro = $stmt->fetchColumn();

        // Si el odómetro de este registro es mayor o igual al último, actualizamos el vehículo
        if ($ultimo_odometro === null || $odometro >= $ultimo_odometro) {
            $stmt = $pdo->prepare("UPDATE vehiculos SET kilometraje_actual = ? WHERE patente = ?");
            $stmt->execute([$odometro, $patente]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Registro actualizado correctamente']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error al actualizar registro: ' . $e->getMessage()]);
    }
}

function eliminarRegistro() {
    global $pdo;

    parse_str(file_get_contents('php://input'), $data);

    if (!verificar_autenticacion()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'No autenticado']);
        return;
    }

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
        $stmt = $pdo->prepare("DELETE FROM consumo_combustible WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'Registro eliminado correctamente']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar registro: ' . $e->getMessage()]);
    }
}
