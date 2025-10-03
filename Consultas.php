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
    die("❌ Error de conexión: " . $e->getMessage());
}

// Inicializar variables vacías
$historial = $agenda = $facturas = $presupuestos = [];

// --- Buscar todo por placa ---
if (isset($_POST['buscar_historial'])) {
    $placa = $_POST['placa'];

    // Historial del vehículo
    $sql = "SELECT H.IdHistorial, H.FechaReparacion, H.Servicio, H.PiezasUsadas
            FROM HistorialReparaciones H
            INNER JOIN Vehiculos V ON H.IdVehiculo = V.IdVehiculo
            WHERE V.Placa = :placa";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':placa', $placa);
    $stmt->execute();
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Citas
    $sql = "SELECT C.* 
            FROM Citas C
            INNER JOIN Vehiculos V ON C.IdVehiculo = V.IdVehiculo
            WHERE V.Placa = :placa
            ORDER BY FechaCita";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':placa', $placa);
    $stmt->execute();
    $agenda = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Presupuestos
    $sql = "SELECT P.* 
            FROM Presupuestos P
            INNER JOIN Vehiculos V ON P.IdVehiculo = V.IdVehiculo
            WHERE V.Placa = :placa
            ORDER BY FechaPresupuesto DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':placa', $placa);
    $stmt->execute();
    $presupuestos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Facturas
    $sql = "SELECT F.* 
            FROM Facturas F
            LEFT JOIN Presupuestos P ON F.IdPresupuesto = P.IdPresupuesto
            LEFT JOIN HistorialReparaciones H ON F.IdHistorial = H.IdHistorial
            INNER JOIN Vehiculos V 
                ON (P.IdVehiculo = V.IdVehiculo OR H.IdVehiculo = V.IdVehiculo)
            WHERE V.Placa = :placa
            ORDER BY FechaFactura DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':placa', $placa);
    $stmt->execute();
    $facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Repuestos (catálogo general)
$sql = "SELECT * FROM Repuestos";
$repuestos = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consultas por Matrícula</title>
    <style>
        body {font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #1e1e1e; color: #f0f0f0;}
        .volver {position: absolute; top: 15px; left: 20px; background: #444; color: #f0f0f0; text-decoration: none; padding: 8px 12px; border-radius: 8px; font-weight: bold;}
        .volver:hover {background: #666;}
        .logo {width: 120px; margin: 10px auto 20px; display: block;}
        h2, h3 {color: #f7cbcb; text-align: center; text-shadow: -1px -1px 0 #ff3b3b,1px -1px 0 #ff3b3b,-1px 1px 0 #ff3b3b,1px 1px 0 #ff3b3b;}
        form {background: #2a2a2a; padding: 15px; border-radius: 12px; max-width: 500px; margin: 20px auto; box-shadow: 0 0 12px rgba(0,0,0,0.5); text-align: center;}
        input[type="text"] {padding: 8px; border-radius: 6px; border: 1px solid #555; background: #1e1e1e; color: #fff; width: 200px;}
        button {background: #ff3b3b; color: #fff; border: none; padding: 8px 12px; margin-left: 10px; border-radius: 8px; font-weight: bold; cursor: pointer;}
        button:hover {background: #cc2e2e;}
        table {width: 90%; margin: 25px auto; background: #2a2a2a; border-collapse: collapse; border-radius: 12px; overflow: hidden;}
        th, td {padding: 8px 10px; border: 1px solid #444; text-align: center; font-size: 14px;}
        th {background: #ff3b3b; color: white;}
        tr:hover {background: #3a3a3a;}
    </style>
</head>
<body>
    <a href="Vendedor.php" class="volver">⬅ Volver al Panel Vendedor</a>
    <img src="logo.png" alt="Logo Auto Parts" class="logo">
    <h2>Consultas por Matrícula</h2>

    <!-- Buscar por placa -->
    <form method="POST">
        <label>Matrícula (Placa):</label>
        <input type="text" name="placa" required>
        <button type="submit" name="buscar_historial">Buscar</button>
    </form>

    <!-- Historial -->
    <?php if (!empty($historial)): ?>
        <h3>Historial de Reparaciones</h3>
        <table>
            <tr><th>ID</th><th>Fecha Reparación</th><th>Servicio</th><th>Piezas Usadas</th></tr>
            <?php foreach ($historial as $h): ?>
            <tr>
                <td><?= $h['IdHistorial'] ?></td>
                <td><?= $h['FechaReparacion'] ?></td>
                <td><?= $h['Servicio'] ?></td>
                <td><?= $h['PiezasUsadas'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <!-- Citas -->
    <?php if (!empty($agenda)): ?>
        <h3>Agenda de Citas</h3>
        <table>
            <tr><th>ID</th><th>ID Vehículo</th><th>Fecha</th><th>Servicio</th><th>Estado</th></tr>
            <?php foreach ($agenda as $c): ?>
            <tr>
                <td><?= $c['IdCita'] ?></td>
                <td><?= $c['IdVehiculo'] ?></td>
                <td><?= $c['FechaCita'] ?></td>
                <td><?= $c['Servicio'] ?></td>
                <td><?= $c['Estado'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <!-- Presupuestos -->
    <?php if (!empty($presupuestos)): ?>
        <h3>Presupuestos</h3>
        <table>
            <tr><th>ID Presupuesto</th><th>ID Vehículo</th><th>Fecha</th><th>Detalle</th><th>Monto Estimado</th><th>Estado</th></tr>
            <?php foreach ($presupuestos as $p): ?>
            <tr>
                <td><?= $p['IdPresupuesto'] ?></td>
                <td><?= $p['IdVehiculo'] ?></td>
                <td><?= $p['FechaPresupuesto'] ?></td>
                <td><?= $p['Detalle'] ?></td>
                <td><?= $p['MontoEstimado'] ?></td>
                <td><?= $p['Estado'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <!-- Facturas -->
    <?php if (!empty($facturas)): ?>
        <h3>Facturas</h3>
        <table>
            <tr><th>ID Factura</th><th>ID Presupuesto</th><th>ID Historial</th><th>Fecha</th><th>Total</th><th>Estado</th></tr>
            <?php foreach ($facturas as $f): ?>
            <tr>
                <td><?= $f['IdFactura'] ?></td>
                <td><?= $f['IdPresupuesto'] ?></td>
                <td><?= $f['IdHistorial'] ?></td>
                <td><?= $f['FechaFactura'] ?></td>
                <td><?= $f['Total'] ?></td>
                <td><?= $f['Estado'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <!-- Repuestos (siempre visible) -->
    <h3>Piezas de Repuesto (Catálogo)</h3>
    <table>
        <tr><th>ID</th><th>Nombre</th><th>Marca</th><th>Cantidad</th><th>Precio</th></tr>
        <?php foreach ($repuestos as $r): ?>
        <tr>
            <td><?= $r['IdRepuesto'] ?></td>
            <td><?= $r['Nombre'] ?></td>
            <td><?= $r['Marca'] ?></td>
            <td><?= $r['Cantidad'] ?></td>
            <td><?= $r['Precio'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
