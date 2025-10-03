<?php
// =======================
// Conexión a la BD
// =======================
$serverName = "db28471.public.databaseasp.net";
$database = "db28471";
$username = "db28471";
$password = "2Fb%y9-EH_z7";

try {
    $conn = new PDO("sqlsrv:Server=$serverName;Database=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// =======================
// Capturar filtro de placa
// =======================
$placa = isset($_GET['placa']) && $_GET['placa'] !== "" ? $_GET['placa'] : null;

// =======================
// Reporte de historial
// =======================
$sql = "SELECT COUNT(*) AS TotalReparaciones 
        FROM HistorialReparaciones h
        INNER JOIN Vehiculos v ON h.IdVehiculo = v.IdVehiculo
        " . ($placa ? "WHERE v.Placa = :placa" : "");
$stmt = $conn->prepare($sql);
if ($placa) $stmt->bindParam(':placa', $placa);
$stmt->execute();
$totalReparaciones = $stmt->fetch(PDO::FETCH_ASSOC);

$sql = "SELECT TOP 5 h.Servicio, COUNT(*) AS Cantidad 
        FROM HistorialReparaciones h
        INNER JOIN Vehiculos v ON h.IdVehiculo = v.IdVehiculo
        " . ($placa ? "WHERE v.Placa = :placa" : "") . "
        GROUP BY h.Servicio 
        ORDER BY Cantidad DESC";
$stmt = $conn->prepare($sql);
if ($placa) $stmt->bindParam(':placa', $placa);
$stmt->execute();
$serviciosMas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =======================
// Reporte de facturación
// =======================
$sql = "SELECT COUNT(*) AS TotalFacturas, SUM(f.Total) AS IngresosTotales 
        FROM Facturas f
        LEFT JOIN Presupuestos p ON f.IdPresupuesto = p.IdPresupuesto
        LEFT JOIN Vehiculos v ON p.IdVehiculo = v.IdVehiculo
        " . ($placa ? "WHERE v.Placa = :placa" : "");
$stmt = $conn->prepare($sql);
if ($placa) $stmt->bindParam(':placa', $placa);
$stmt->execute();
$facturacion = $stmt->fetch(PDO::FETCH_ASSOC);

$sql = "SELECT COUNT(*) AS Pendientes 
        FROM Facturas f
        LEFT JOIN Presupuestos p ON f.IdPresupuesto = p.IdPresupuesto
        LEFT JOIN Vehiculos v ON p.IdVehiculo = v.IdVehiculo
        WHERE f.Estado = 'Pendiente' " . ($placa ? "AND v.Placa = :placa" : "");
$stmt = $conn->prepare($sql);
if ($placa) $stmt->bindParam(':placa', $placa);
$stmt->execute();
$pendientes = $stmt->fetch(PDO::FETCH_ASSOC);

// =======================
// Reporte de repuestos
// =======================
$sql = "SELECT COUNT(*) AS TotalRepuestos, SUM(Cantidad) AS StockTotal 
        FROM Repuestos";
$repuestos = $conn->query($sql)->fetch(PDO::FETCH_ASSOC);

$sql = "SELECT TOP 5 h.PiezasUsadas, COUNT(*) AS VecesUsada 
        FROM HistorialReparaciones h
        INNER JOIN Vehiculos v ON h.IdVehiculo = v.IdVehiculo
        WHERE (h.PiezasUsadas IS NOT NULL AND h.PiezasUsadas <> '') 
        " . ($placa ? "AND v.Placa = :placa" : "") . "
        GROUP BY h.PiezasUsadas 
        ORDER BY VecesUsada DESC";
$stmt = $conn->prepare($sql);
if ($placa) $stmt->bindParam(':placa', $placa);
$stmt->execute();
$piezasMas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =======================
// Finanzas
// =======================
$sql = "SELECT AVG(f.Total) AS PromedioFactura 
        FROM Facturas f
        LEFT JOIN Presupuestos p ON f.IdPresupuesto = p.IdPresupuesto
        LEFT JOIN Vehiculos v ON p.IdVehiculo = v.IdVehiculo
        WHERE f.Estado = 'Pagada' " . ($placa ? "AND v.Placa = :placa" : "");
$stmt = $conn->prepare($sql);
if ($placa) $stmt->bindParam(':placa', $placa);
$stmt->execute();
$finanzas = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes del Taller</title>
    <style>
        body { font-family: Arial, sans-serif; background:#1e1e1e; color:#f0f0f0; text-align:center; padding:20px;}
        .panel { background:#2a2a2a; padding:20px; border-radius:10px; width:85%; max-width:900px; margin:auto;}
        table { width:100%; border-collapse:collapse; margin:20px 0; }
        th, td { border:1px solid #444; padding:8px; text-align:center;}
        th { background:#ff3b3b; color:white;}
        tr:hover { background:#333;}
        .volver { position:absolute; top:15px; left:20px; background:#444; padding:8px 12px; border-radius:6px; text-decoration:none; color:white;}
        .volver:hover { background:#666;}
        input, button { padding:6px; border-radius:5px; border:1px solid #555; }
        button { background:#ff3b3b; color:white; cursor:pointer; }
        button:hover { background:#e52e2e; }
    </style>
</head>
<body>
    <a href="Admin.php" class="volver">⬅ Volver</a>

    <div class="panel">
        <h2>Reportes del Taller</h2>

        <!-- Formulario de búsqueda por placa -->
        <form method="GET" action="">
            <input type="text" name="placa" placeholder="Ingrese placa..." value="<?= htmlspecialchars($placa ?? '') ?>">
            <button type="submit">Buscar</button>
            <?php if ($placa): ?>
                <a href="Reportes.php" style="margin-left:10px;color:#ff3b3b;">Quitar filtro</a>
            <?php endif; ?>
        </form>

        <h3><?= $placa ? "Reporte del vehículo con placa: $placa" : "Reporte General" ?></h3>

        <!-- Historial -->
        <h3>Historial de Reparaciones</h3>
        <p>Total reparaciones: <b><?= $totalReparaciones['TotalReparaciones'] ?? 0 ?></b></p>
        <table>
            <tr><th>Servicio</th><th>Cantidad</th></tr>
            <?php foreach ($serviciosMas as $s): ?>
                <tr>
                    <td><?= $s['Servicio'] ?></td>
                    <td><?= $s['Cantidad'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <!-- Facturación -->
        <h3>Facturación</h3>
        <p>Total facturas: <b><?= $facturacion['TotalFacturas'] ?? 0 ?></b></p>
        <p>Ingresos totales: <b>$<?= $facturacion['IngresosTotales'] ?? 0 ?></b></p>
        <p>Facturas pendientes: <b><?= $pendientes['Pendientes'] ?? 0 ?></b></p>

        <!-- Repuestos -->
        <h3>Piezas de Repuesto</h3>
        <p>Total tipos de repuestos: <b><?= $repuestos['TotalRepuestos'] ?? 0 ?></b></p>
        <p>Stock total: <b><?= $repuestos['StockTotal'] ?? 0 ?></b></p>
        <table>
            <tr><th>Pieza</th><th>Veces Usada</th></tr>
            <?php foreach ($piezasMas as $p): ?>
                <tr>
                    <td><?= $p['PiezasUsadas'] ?></td>
                    <td><?= $p['VecesUsada'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <!-- Finanzas -->
        <h3>Finanzas</h3>
        <p>Promedio por factura pagada: <b>$<?= number_format($finanzas['PromedioFactura'] ?? 0, 2) ?></b></p>
    </div>
</body>
</html>
