<?php
require_once '../bootstrap.php';

$patente = $_GET['patente'] ?? '';
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';

$sql = "SELECT t.*, v.marca, v.modelo
          FROM telepases t
          LEFT JOIN vehiculos v ON t.vehiculo_id = v.id
          WHERE 1=1";
$params = [];

if (!empty($patente)) {
    $sql .= " AND t.patente LIKE ?";
    $params[] = "%$patente%";
}

if (!empty($fecha_desde)) {
    $sql .= " AND t.fecha_hora_paso >= ?";
    $params[] = $fecha_desde . ' 00:00:00';
}

if (!empty($fecha_hasta)) {
    $sql .= " AND t.fecha_hora_paso <= ?";
    $params[] = $fecha_hasta . ' 23:59:59';
}

$sql .= " ORDER BY t.fecha_hora_paso DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$telepases = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular totales por vehiculo
$porVehiculo = [];
$totalGeneral = 0;

foreach ($telepases as $telepase) {
    $patente = $telepase['patente'];
    $monto = floatval($telepase['monto']);

    if (!isset($porVehiculo[$patente])) {
        $porVehiculo[$patente] = [
            'cantidad' => 0,
            'total' => 0,
            'marca' => $telepase['marca'],
            'modelo' => $telepase['modelo']
        ];
    }

    $porVehiculo[$patente]['cantidad']++;
    $porVehiculo[$patente]['total'] += $monto;
    $totalGeneral += $monto;
}

ksort($porVehiculo);

$filasResumen = '';
foreach ($porVehiculo as $patente => $datos) {
    $filasResumen .= "<tr>
            <td><strong>$patente</strong></td>
            <td>{$datos['marca']} {$datos['modelo']}</td>
            <td>{$datos['cantidad']}</td>
            <td>$" . number_format($datos['total'], 2, ',', '.') . "</td>
        </tr>";
}

// Filas de detalle
$filasDetalle = '';
foreach ($telepases as $telepase) {
    $fecha = new DateTime($telepase['fecha_hora_paso']);
    $fechaFormateada = $fecha->format('d/m/Y H:i');

    $filasDetalle .= "<tr>
            <td><strong>{$telepase['patente']}</strong></td>
            <td>{$telepase['numero_dispositivo']}</td>
            <td>{$telepase['nombre_peaje']}</td>
            <td>" . ($telepase['ubicacion'] ?: '-') . "</td>
            <td>$fechaFormateada</td>
            <td>$" . number_format($telepase['monto'], 2, ',', '.') . "</td>
            <td>" . ($telepase['comprobante'] ?: '-') . "</td>
        </tr>";
}

$fechaHoy = new DateTime();
$fechaDesdeFiltro = $fecha_desde ? new DateTime($fecha_desde) : null;
$fechaHastaFiltro = $fecha_hasta ? new DateTime($fecha_hasta) : null;

$fechaReporte = $fechaHoy->format('d/m/Y H:i');
$periodoFiltro = '';
if ($fechaDesdeFiltro && $fechaHastaFiltro) {
    $periodoFiltro = "<p>Período: del {$fechaDesdeFiltro->format('d/m/Y')} al {$fechaHastaFiltro->format('d/m/Y')}</p>";
}

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Reporte de Telepases</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2em;
        }
        .header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        .summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .summary h2 {
            margin-top: 0;
            margin-bottom: 15px;
        }
        .summary table {
            width: 100%;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        .summary th,
        .summary td {
            padding: 12px 15px;
            color: white;
        }
        .summary th {
            background: rgba(0,0,0,0.2);
            font-weight: bold;
        }
        .section-title {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            margin: 20px 0 10px 0;
            font-size: 1.2em;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9em;
            letter-spacing: 0.5px;
        }
        tr:nth-child(even) {
            background: #f8f9fa;
        }
        tr:hover {
            background: #e9ecef;
        }
        .total {
            background: #28a745;
            color: white;
            font-weight: bold;
            font-size: 1.3em;
        }
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
        }
        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        @media print {
            .print-btn { display: none; }
            body { margin: 0; padding: 10px; }
            .container { box-shadow: none; padding: 15px; }
        }
    </style>
</head>
<body>
    <button class='print-btn' onclick='window.print()'>🖨️ Imprimir / Guardar PDF</button>

    <div class='container'>
        <div class='header'>
            <h1>🎫 Reporte de Telepases</h1>
            <p>Fecha del reporte: $fechaReporte</p>
            $periodoFiltro
        </div>

        <div class='summary'>
            <h2>📊 Resumen por Vehículo</h2>
            <table>
                <thead>
                    <tr>
                        <th>Patente</th>
                        <th>Vehículo</th>
                        <th>Cantidad de Pases</th>
                        <th>Total Gastado</th>
                    </tr>
                </thead>
                <tbody>
                    $filasResumen
                </tbody>
                <tr class='total'>
                    <td colspan='3'><strong>TOTAL GENERAL</strong></td>
                    <td><strong>$" . number_format($totalGeneral, 2, ',', '.') . "</strong></td>
                </tr>
            </table>
        </div>

        <div class='section-title'>📋 Detalle de Telepases</div>
        <table>
            <thead>
                <tr>
                    <th>Patente</th>
                    <th>Nº Dispositivo</th>
                    <th>Peaje</th>
                    <th>Ubicación</th>
                    <th>Fecha y Hora</th>
                    <th>Monto</th>
                    <th>Comprobante</th>
                </tr>
            </thead>
            <tbody>
                $filasDetalle
            </tbody>
        </table>

        <div style='text-align: center; margin-top: 30px; color: #666; font-size: 0.9em;'>
            <p><strong>SECM Autos - Sistema de Gestión de Flota</strong></p>
            <p>Generado el $fechaReporte</p>
        </div>
    </div>
</body>
</html>";
