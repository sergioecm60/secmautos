<?php
require_once __DIR__ . '/../../bootstrap.php';

if (!verificar_autenticacion()) {
    header('HTTP/1.0 401 Unauthorized');
    exit;
}

header('Content-Type: text/html; charset=utf-8');

if (!isset($_GET['vehiculo_id'])) {
    echo '<p style="font-family: Arial, sans-serif;">Vehículo no especificado</p>';
    exit;
}

$vehiculo_id = (int)$_GET['vehiculo_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM vehiculos WHERE id = ?");
    $stmt->execute([$vehiculo_id]);
    $vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vehiculo) {
        echo '<p style="font-family: Arial, sans-serif;">Vehículo no encontrado</p>';
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT a.*, CONCAT(e.nombre, ' ', e.apellido) as empleado_nombre
        FROM asignaciones a
        LEFT JOIN empleados e ON a.empleado_id = e.id
        WHERE a.vehiculo_id = ?
        ORDER BY a.fecha_asignacion DESC
    ");
    $stmt->execute([$vehiculo_id]);
    $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT m.*, CONCAT(e.nombre, ' ', e.apellido) as empleado_nombre
        FROM multas m
        LEFT JOIN empleados e ON m.empleado_id = e.id
        WHERE m.vehiculo_id = ?
        ORDER BY m.fecha_multa DESC
    ");
    $stmt->execute([$vehiculo_id]);
    $multas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM mantenimientos WHERE vehiculo_id = ? ORDER BY fecha DESC");
    $stmt->execute([$vehiculo_id]);
    $mantenimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT * FROM pagos
        WHERE vehiculo_id = ?
        ORDER BY fecha_vencimiento DESC
    ");
    $stmt->execute([$vehiculo_id]);
    $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM compras WHERE vehiculo_id = ? ORDER BY fecha DESC LIMIT1");
    $stmt->execute([$vehiculo_id]);
    $compra = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM ventas WHERE vehiculo_id = ? ORDER BY fecha DESC LIMIT1");
    $stmt->execute([$vehiculo_id]);
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);

    $totalMultas = array_sum(array_column($multas, 'monto'));
    $totalMantenimientos = array_sum(array_column($mantenimientos, 'costo'));

} catch (Exception $e) {
    echo '<p style="font-family: Arial, sans-serif;">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    exit;
}

function formatDate($date) {
    return $date ? date('d/m/Y', strtotime($date)) : '-';
}

function formatMoney($amount) {
    return $amount ? '$' . number_format($amount, 2) : '-';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe de Dominio - <?= $vehiculo['patente'] ?></title>
    <link rel="stylesheet" href="../../assets/css/reportes.css">
</head>
<body>
    <div class="btn-group">
        <button class="print-btn" onclick="window.print()">🖨️ Imprimir</button>
        <button class="print-btn" onclick="window.print()">💾 Guardar como PDF</button>
    </div>

    <div class="header">
        <h1>📋 Informe de Dominio</h1>
        <p>Fecha de emisión: <?= date('d/m/Y H:i') ?></p>
    </div>

    <div class="info-grid">
        <div class="info-item"><strong>Patente:</strong> <?= $vehiculo['patente'] ?></div>
        <div class="info-item"><strong>Marca/Modelo:</strong> <?= $vehiculo['marca'] . ' ' . $vehiculo['modelo'] ?></div>
        <div class="info-item"><strong>Año:</strong> <?= $vehiculo['anio'] ?></div>
        <div class="info-item"><strong>Motor:</strong> <?= $vehiculo['motor'] ?></div>
        <div class="info-item"><strong>Chasis:</strong> <?= $vehiculo['chasis'] ?></div>
        <div class="info-item"><strong>Titularidad:</strong> <?= $vehiculo['titularidad'] ?></div>
        <div class="info-item"><strong>Kilometraje:</strong> <?= number_format($vehiculo['kilometraje_actual']) . ' km' ?></div>
        <div class="info-item"><strong>Estado:</strong> <?= ucfirst($vehiculo['estado']) ?></div>
    </div>

    <section>
        <div class="report-title">📅 Vencimientos</div>
        <table>
            <tr><th>TVT</th><td><?= formatDate($vehiculo['fecha_tvt']) ?></td></tr>
            <tr><th>Seguro</th><td><?= formatDate($vehiculo['fecha_seguro']) ?></td></tr>
            <tr><th>Patente</th><td><?= formatDate($vehiculo['fecha_patente']) ?></td></tr>
        </table>
    </section>

    <?php if ($compra): ?>
    <section>
        <div class="report-title">🛒 Compra</div>
        <table>
            <tr><th>Fecha:</th><td><?= formatDate($compra['fecha']) ?></td></tr>
            <tr><th>Proveedor:</th><td><?= $compra['proveedor'] ?></td></tr>
            <tr><th>CUIT:</th><td><?= $compra['cuit'] ?></td></tr>
            <tr><th>Factura Nº:</th><td><?= $compra['factura_numero'] ?></td></tr>
            <tr><th>Neto:</th><td><?= formatMoney($compra['importe_neto']) ?></td></tr>
            <tr><th>IVA:</th><td><?= formatMoney($compra['iva']) ?></td></tr>
            <tr><th><strong>Total:</strong></th><td><strong><?= formatMoney($compra['total']) ?></strong></td></tr>
        </table>
    </section>
    <?php endif; ?>

    <?php if ($venta): ?>
    <section>
        <div class="report-title">💰 Venta</div>
        <table>
            <tr><th>Fecha:</th><td><?= formatDate($venta['fecha']) ?></td></tr>
            <tr><th>Comprador:</th><td><?= $venta['comprador'] ?></td></tr>
            <tr><th>CUIT:</th><td><?= $venta['cuit'] ?></td></tr>
            <tr><th>Factura Nº:</th><td><?= $venta['factura_numero'] ?></td></tr>
            <tr><th><strong>Importe:</strong></th><td><strong><?= formatMoney($venta['importe']) ?></strong></td></tr>
        </table>
    </section>
    <?php endif; ?>

    <section>
        <div class="report-title">🔄 Historial de Asignaciones</div>
        <?php if (count($asignaciones) > 0): ?>
        <table>
            <tr><th>Empleado</th><th>Fecha Salida</th><th>Km Salida</th><th>Fecha Regreso</th><th>Km Regreso</th></tr>
            <?php foreach ($asignaciones as $a): ?>
            <tr>
                <td><?= $a['empleado_nombre'] ?></td>
                <td><?= formatDate($a['fecha_asignacion']) ?></td>
                <td><?= number_format($a['km_salida']) ?></td>
                <td><?= formatDate($a['fecha_devolucion']) ?></td>
                <td><?= number_format($a['km_regreso']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php else: ?>
        <div class="no-data">Sin asignaciones registradas</div>
        <?php endif; ?>
    </section>

    <section>
        <div class="report-title">⚠️ Multas</div>
        <?php if (count($multas) > 0): ?>
        <table>
            <tr><th>Fecha</th><th>Motivo</th><th>Acta Nº</th><th>Responsable</th><th>Monto</th><th>Estado</th></tr>
            <?php foreach ($multas as $m): ?>
            <tr>
                <td><?= formatDate($m['fecha_multa']) ?></td>
                <td><?= $m['motivo'] ?></td>
                <td><?= $m['numero_acta'] ?></td>
                <td><?= $m['empleado_nombre'] ?></td>
                <td><?= formatMoney($m['monto']) ?></td>
                <td><?= $m['pagada'] ? 'Pagada' : 'Pendiente' ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <div class="total">Total Multas: <?= formatMoney($totalMultas) ?></div>
        <?php else: ?>
        <div class="no-data">Sin multas registradas</div>
        <?php endif; ?>
    </section>

    <section>
        <div class="report-title">🔧 Mantenimientos</div>
        <?php if (count($mantenimientos) > 0): ?>
        <table>
            <tr><th>Fecha</th><th>Tipo</th><th>Descripción</th><th>Km</th><th>Costo</th></tr>
            <?php foreach ($mantenimientos as $m): ?>
            <tr>
                <td><?= formatDate($m['fecha']) ?></td>
                <td><?= ucfirst($m['tipo']) ?></td>
                <td><?= $m['descripcion'] ?></td>
                <td><?= $m['kilometraje'] ? number_format($m['kilometraje']) . ' km' : '-' ?></td>
                <td><?= formatMoney($m['costo']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <div class="total">Total Mantenimientos: <?= formatMoney($totalMantenimientos) ?></div>
        <?php else: ?>
        <div class="no-data">Sin mantenimientos registrados</div>
        <?php endif; ?>
    </section>

    <section>
        <div class="report-title">💳 Pagos</div>
        <?php if (count($pagos) > 0): ?>
        <table>
            <tr><th>Tipo</th><th>Vencimiento</th><th>Monto</th><th>Fecha Pago</th><th>Estado</th></tr>
            <?php foreach ($pagos as $p): ?>
            <tr>
                <td><?= ucfirst($p['tipo']) ?></td>
                <td><?= formatDate($p['fecha_vencimiento']) ?></td>
                <td><?= formatMoney($p['monto']) ?></td>
                <td><?= formatDate($p['fecha_pago']) ?></td>
                <td><?= $p['pagado'] ? 'Pagado' : 'Pendiente' ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php else: ?>
        <div class="no-data">Sin pagos registrados</div>
        <?php endif; ?>
    </section>

    <div class="summary-section">
        <div class="total">RESUMEN ECONÓMICO</div>
        <div class="total" style="font-size: 18px; background: rgba(255,255,255,0.2);">
            Total Invertido (Mantenimientos + Multas): <?= formatMoney($totalMantenimientos + $totalMultas) ?>
        </div>
    </div>
</body>
</html>
