<?php
// Conexión a la BD
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

// --- Reporte de historial ---
$sql = "SELECT COUNT(*) AS TotalReparaciones FROM HistorialReparaciones";
$totalReparaciones = $conn->query($sql)->fetch(PDO::FETCH_ASSOC);

$sql = "SELECT TOP 5 Servicio, COUNT(*) AS Cantidad 
        FROM HistorialReparaciones 
        GROUP BY Servicio 
        ORDER BY Cantidad DESC";
$serviciosMas = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// --- Reporte de facturación ---
$sql = "SELECT COUNT(*) AS TotalFacturas, SUM(Total) AS IngresosTotales 
        FROM Facturas";
$facturacion = $conn->query($sql)->fetch(PDO::FETCH_ASSOC);

$sql = "SELECT COUNT(*) AS Pendientes 
        FROM Facturas 
        WHERE Estado = 'Pendiente'";
$pendientes = $conn->query($sql)->fetch(PDO::FETCH_ASSOC);

// --- Reporte de repuestos ---
$sql = "SELECT COUNT(*) AS TotalRepuestos, SUM(Cantidad) AS StockTotal 
        FROM Repuestos";
$repuestos = $conn->query($sql)->fetch(PDO::FETCH_ASSOC);

$sql = "SELECT TOP 5 PiezasUsadas, COUNT(*) AS VecesUsada 
        FROM HistorialReparaciones 
        WHERE PiezasUsadas IS NOT NULL AND PiezasUsadas <> '' 
        GROUP BY PiezasUsadas 
        ORDER BY VecesUsada DESC";
$piezasMas = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// --- Finanzas (promedios) ---
$sql = "SELECT AVG(Total) AS PromedioFactura 
        FROM Facturas 
        WHERE Estado = 'Pagada'";
$finanzas = $conn->query($sql)->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes del Taller</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2 { color: #2c3e50; }
        table { border-collapse: collapse; width: 60%; margin: 15px 0; }
        th, td { border: 1px solid black; padding: 6px; text-align: center; }
        th { background-color: #eee; }
    </style>
</head>
<body>
    <h2>📊 Reportes del Taller</h2>

    <!-- Reporte de historial -->
    <h3>Historial de Reparaciones</h3>
    <p>Total reparaciones: <b><?= $totalReparaciones['TotalReparaciones'] ?></b></p>
    <table>
        <tr><th>Servicio</th><th>Cantidad</th></tr>
        <?php foreach ($serviciosMas as $s): ?>
            <tr>
                <td><?= $s['Servicio'] ?></td>
                <td><?= $s['Cantidad'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <!-- Reporte de facturación -->
    <h3>Facturación</h3>
    <p>Total facturas: <b><?= $facturacion['TotalFacturas'] ?></b></p>
    <p>Ingresos totales: <b>$<?= $facturacion['IngresosTotales'] ?></b></p>
    <p>Facturas pendientes: <b><?= $pendientes['Pendientes'] ?></b></p>

    <!-- Reporte de repuestos -->
    <h3>Piezas de Repuesto</h3>
    <p>Total tipos de repuestos: <b><?= $repuestos['TotalRepuestos'] ?></b></p>
    <p>Stock total: <b><?= $repuestos['StockTotal'] ?></b></p>
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
    <p>Promedio por factura pagada: <b>$<?= number_format($finanzas['PromedioFactura'], 2) ?></b></p>
    <div style="text-align:center;">
        <a href="Admin.php">⬅ Volver al Panel Admin</a>
    </div>
</body>
</html>
