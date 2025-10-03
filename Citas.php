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

// --- Capturar la placa si existe ---
$placa = isset($_GET['placa']) ? trim($_GET['placa']) : "";

// Condición dinámica para SQL
$condicion = "";
if ($placa !== "") {
    $condicion = "WHERE Placa = :placa";
}

// --- Reporte de historial ---
$sql = "SELECT COUNT(*) AS TotalReparaciones 
        FROM HistorialReparaciones $condicion";
$stmt = $conn->prepare($sql);
if ($placa !== "") $stmt->bindParam(":placa", $placa);
$stmt->execute();
$totalReparaciones = $stmt->fetch(PDO::FETCH_ASSOC);

$sql = "SELECT TOP 5 Servicio, COUNT(*) AS Cantidad 
        FROM HistorialReparaciones 
        " . ($placa !== "" ? "WHERE Placa = :placa" : "") . "
        GROUP BY Servicio 
        ORDER BY Cantidad DESC";
$stmt = $conn->prepare($sql);
if ($placa !== "") $stmt->bindParam(":placa", $placa);
$stmt->execute();
$serviciosMas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Reporte de facturación ---
$sql = "SELECT COUNT(*) AS TotalFacturas, SUM(Total) AS IngresosTotales 
        FROM Facturas 
        " . ($placa !== "" ? "WHERE Placa = :placa" : "");
$stmt = $conn->prepare($sql);
if ($placa !== "") $stmt->bindParam(":placa", $placa);
$stmt->execute();
$facturacion = $stmt->fetch(PDO::FETCH_ASSOC);

$sql = "SELECT COUNT(*) AS Pendientes 
        FROM Facturas 
        WHERE Estado = 'Pendiente' " . ($placa !== "" ? "AND Placa = :placa" : "");
$stmt = $conn->prepare($sql);
if ($placa !== "") $stmt->bindParam(":placa", $placa);
$stmt->execute();
$pendientes = $stmt->fetch(PDO::FETCH_ASSOC);

// --- Reporte de repuestos ---
$sql = "SELECT COUNT(*) AS TotalRepuestos, SUM(Cantidad) AS StockTotal 
        FROM Repuestos";
$repuestos = $conn->query($sql)->fetch(PDO::FETCH_ASSOC);

$sql = "SELECT TOP 5 PiezasUsadas, COUNT(*) AS VecesUsada 
        FROM HistorialReparaciones 
        WHERE PiezasUsadas IS NOT NULL AND PiezasUsadas <> '' 
        " . ($placa !== "" ? "AND Placa = :placa" : "") . "
        GROUP BY PiezasUsadas 
        ORDER BY VecesUsada DESC";
$stmt = $conn->prepare($sql);
if ($placa !== "") $stmt->bindParam(":placa", $placa);
$stmt->execute();
$piezasMas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Finanzas (promedios) ---
$sql = "SELECT AVG(Total) AS PromedioFactura 
        FROM Facturas 
        WHERE Estado = 'Pagada' " . ($placa !== "" ? "AND Placa = :placa" : "");
$stmt = $conn->prepare($sql);
if ($placa !== "") $stmt->bindParam(":placa", $placa);
$stmt->execute();
$finanzas = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes del Taller</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #1e1e1e;
            color: #f0f0f0;
            text-align: center;
        }
        .volver {
            position: absolute;
            top: 15px;
            left: 20px;
            background-color: #444;
            color: #f0f0f0;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: bold;
        }
        .volver:hover { background-color: #666; }
        .logo { width: 100px; margin: 10px auto 20px; display: block; }
        h2, h3 {
            color: #f7cbcb;
            text-shadow: -1px -1px 0 #ff3b3b,1px -1px 0 #ff3b3b,
                         -1px  1px 0 #ff3b3b,1px  1px 0 #ff3b3b;
        }
        .panel {
            background-color: #2a2a2a;
            width: 85%;
            max-width: 800px;
            margin: auto;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.6);
        }
        table {
            width: 100%; border-collapse: collapse; margin: 20px 0 35px;
            background-color: #1e1e1e; border-radius: 8px; overflow: hidden;
        }
        th, td { padding: 10px; border: 1px solid #444; text-align: center; }
        th { background-color: #ff3b3b; color: white; }
        tr:hover { background-color: #333; }
        p { margin: 8px 0; color: #ddd; }
        .form-placa {
            margin: 20px auto;
            text-align: center;
        }
        input[type="text"] {
            padding: 6px;
            border-radius: 6px;
            border: none;
            outline: none;
            width: 180px;
            text-align: center;
        }
        button {
            padding: 6px 12px;
            margin-left: 5px;
            border: none;
            border-radius: 6px;
            background: #ff3b3b;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover { background: #cc2e2e; }
    </style>
</head>
<body>
    <a href="Admin.php" class="volver">⬅ Volver al Panel Admin</a>
    <img src="logo.png" alt="Logo Auto Parts" class="logo">

    <div class="panel">
        <h2>Reportes del Taller</h2>

        <!-- Formulario búsqueda -->
        <form method="GET" class="form-placa">
            <label for="placa">Buscar por placa:</label>
            <input type="text" name="placa" id="placa" value="<?= htmlspecialchars($placa) ?>" placeholder="Ej: ABC123">
            <button type="submit">Buscar</button>
            <a href="reportes.php"><button type="button">Ver General</button></a>
        </form>

        <?php if ($placa !== ""): ?>
            <h3>📌 Reporte filtrado por placa: <b><?= htmlspecialchars($placa) ?></b></h3>
        <?php endif; ?>

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
        <p>Promedio por factura pagada: 
            <b>$<?= number_format($finanzas['PromedioFactura'], 2) ?></b>
        </p>
    </div>
</body>
</html>
