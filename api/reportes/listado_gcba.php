<?php
require_once __DIR__ . '/../../bootstrap.php';

if (!verificar_autenticacion()) {
    header('HTTP/1.0 401 Unauthorized');
    exit;
}

header('Content-Type: text/html; charset=utf-8');

try {
    $sql = "SELECT * FROM vehiculos WHERE 1=1";
    $params = [];

    if (isset($_GET['estado']) && $_GET['estado'] !== 'todos') {
        $sql .= " AND estado = ?";
        $params[] = $_GET['estado'];
    }

    $sql .= " ORDER BY patente ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $vehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    echo '<p style="font-family: Arial, sans-serif;">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    exit;
}

function formatDate($date) {
    return $date ? date('d/m/Y', strtotime($date)) : '-';
}

function formatNumber($number) {
    return $number ? number_format($number) : '-';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado GCBA - SECM Autos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            font-size: 11px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #333;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .header p {
            margin: 10px 0 0 0;
            font-size: 14px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #666;
            padding: 6px 10px;
            text-align: left;
        }
        th {
            background: #333;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .estado-baja {
            background: #ffdddd !important;
        }
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #333;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 6px;
            font-size: 14px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #333;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        @media print {
            .print-btn { display: none; }
            body { margin: 0; }
            th { background: #ddd !important; -webkit-print-color-adjust: exact; }
            .estado-baja { background: #ffdddd !important; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>

    <div class="header">
        <h1>📋 LISTADO DE VEHÍCULOS</h1>
        <p>SECM Flota de Autos - Para uso en GCBA/Rentas</p>
        <p>Fecha: <?= date('d/m/Y H:i') ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Patente</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Año</th>
                <th>Motor</th>
                <th>Chasis</th>
                <th>Titularidad</th>
                <th>Km Actual</th>
                <th>Estado</th>
                <th>VTV</th>
                <th>Seguro</th>
                <th>Patente</th>
                <th>Fecha Baja</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($vehiculos) > 0): ?>
                <?php foreach ($vehiculos as $v): ?>
                <tr class="<?= $v['estado'] === 'baja' ? 'estado-baja' : '' ?>">
                    <td><strong><?= $v['patente'] ?></strong></td>
                    <td><?= $v['marca'] ?></td>
                    <td><?= $v['modelo'] ?></td>
                    <td><?= formatNumber($v['anio']) ?></td>
                    <td><?= $v['motor'] ?></td>
                    <td><?= $v['chasis'] ?></td>
                    <td><?= $v['titularidad'] ?></td>
                    <td><?= formatNumber($v['kilometraje_actual']) ?> km</td>
                    <td><strong><?= ucfirst($v['estado']) ?></strong></td>
                    <td><?= formatDate($v['fecha_vtv']) ?></td>
                    <td><?= formatDate($v['fecha_seguro']) ?></td>
                    <td><?= formatDate($v['fecha_patente']) ?></td>
                    <td><?= formatDate($v['fecha_baja']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="14" style="text-align: center; padding: 30px;">
                        No hay vehículos registrados
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <p><strong>SECM Flota de Autos</strong> | Sistema de Gestión de Vehículos</p>
        <p>Generado el <?= date('d/m/Y H:i') ?> | Usuario: <?= $_SESSION['nombre'] . ' ' . $_SESSION['apellido'] ?></p>
    </div>
</body>
</html>
