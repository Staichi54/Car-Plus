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

// --- Buscar historial por placa ---
$historial = [];
if (isset($_POST['buscar_historial'])) {
    $placa = $_POST['placa'];
    $sql = "SELECT H.IdHistorial, H.FechaReparacion, H.Servicio, H.PiezasUsadas
            FROM HistorialReparaciones H
            INNER JOIN Vehiculos V ON H.IdVehiculo = V.IdVehiculo
            WHERE V.Placa = :placa";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':placa', $placa);
    $stmt->execute();
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --- Agenda de citas ---
$sql = "SELECT * FROM Citas ORDER BY FechaCita";
$agenda = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// --- Facturas ---
$sql = "SELECT * FROM Facturas ORDER BY FechaFactura DESC";
$facturas = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// --- Repuestos ---
$sql = "SELECT * FROM Repuestos";
$repuestos = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consultas Rápidas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #1e1e1e;
            color: #f0f0f0;
            text-align: center;
        }

        /* Enlace volver */
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
        .volver:hover {
            background-color: #666;
        }

        .logo {
            width: 120px;
            margin: 10px auto 20px;
            display: block;
        }

        h2, h3 {
            color: #f7cbcb;
            text-align: center;
            text-shadow: 
                -1px -1px 0 #ff3b3b,
                 1px -1px 0 #ff3b3b,
                -1px  1px 0 #ff3b3b,
                 1px  1px 0 #ff3b3b;
        }

        h3 {
            margin-top: 35px;
        }

        form {
            background-color: #2a2a2a;
            padding: 15px;
            border-radius: 12px;
            max-width: 500px;
            margin: 20px auto;
            box-shadow: 0 0 12px rgba(0,0,0,0.5);
            text-align: left;
        }

        label {
            font-weight: bold;
            margin-right: 10px;
        }

        input[type="text"] {
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #555;
            background-color: #1e1e1e;
            color: #fff;
            width: 200px;
        }

        button {
            background-color: #ff3b3b;
            color: #fff;
            border: none;
            padding: 8px 12px;
            margin-left: 10px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        button:hover {
            background-color: #cc2e2e;
            transform: translateY(-2px);
        }

        table {
            width: 90%;              /* más reducido */
            margin: 35px auto;       /* más separación arriba/abajo y centrado */
            background-color: #2a2a2a;
            border-collapse: collapse;
            border-radius: 12px;
            overflow: hidden;
        }


        th, td {
            padding: 8px 10px;
            border: 1px solid #444;
            text-align: center;
            font-size: 14px;
        }

        th {
            background-color: #ff3b3b;
            color: white;
        }

        tr:hover {
            background-color: #3a3a3a;
        }
    </style>
</head>
<body>
    <!-- Volver -->
    <a href="Vendedor.php" class="volver">⬅ Volver al Panel Vendedor</a>

    <!-- Logo -->
    <img src="logo.png" alt="Logo Auto Parts" class="logo">

    <h2>Consultas Rápidas</h2>

    <!-- 🔍 Buscar historial de un vehículo -->
    <h3>Historial de Vehículo</h3>
    <form method="POST">
        <label>Placa:</label>
        <input type="text" name="placa" required>
        <button type="submit" name="buscar_historial">Buscar</button>
    </form>
    <?php if (!empty($historial)): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Fecha Reparación</th>
                <th>Servicio</th>
                <th>Piezas Usadas</th>
            </tr>
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

    <!-- 📅 Agenda de citas -->
    <h3>Agenda de Citas</h3>
    <table>
        <tr>
            <th>ID</th>
            <th>Vehículo</th>
            <th>Fecha</th>
            <th>Servicio</th>
            <th>Estado</th>
        </tr>
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

    <!-- 💰 Facturas -->
    <h3>Facturas</h3>
    <table>
        <tr>
            <th>ID Factura</th>
            <th>ID Presupuesto</th>
            <th>ID Historial</th>
            <th>Fecha</th>
            <th>Total</th>
            <th>Estado</th>
        </tr>
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

    <!-- 🔧 Repuestos -->
    <h3>Piezas de Repuesto</h3>
    <table>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Marca</th>
            <th>Cantidad</th>
            <th>Precio</th>
        </tr>
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
