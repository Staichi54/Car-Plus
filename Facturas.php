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

// --- Emitir factura ---
if (isset($_POST['emitir'])) {
    $idPresupuesto = !empty($_POST['idPresupuesto']) ? $_POST['idPresupuesto'] : null;
    $idHistorial   = !empty($_POST['idHistorial']) ? $_POST['idHistorial'] : null;
    $estado        = $_POST['estado'];

    $total = 0;
    $detalleDesc = "Factura generada";

    // Si viene de presupuesto, obtener total y descripción
    if ($idPresupuesto) {
        $sql = "SELECT Total FROM Presupuestos WHERE IdPresupuesto = :idPresupuesto";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':idPresupuesto', $idPresupuesto);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $total = $row['Total'];
            $detalleDesc = "Presupuesto #" . $idPresupuesto;
        }
    }

    // Si viene de historial, poner un valor fijo o personalizado
    if ($idHistorial) {
        $total = 250000; 
        $detalleDesc = "Reparación #" . $idHistorial;
    }

    // Insertar factura primero
    $sql = "INSERT INTO Facturas (IdPresupuesto, IdHistorial, Total, Estado) 
            OUTPUT INSERTED.IdFactura
            VALUES (:idPresupuesto, :idHistorial, :total, :estado)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':idPresupuesto', $idPresupuesto);
    $stmt->bindParam(':idHistorial', $idHistorial);
    $stmt->bindParam(':total', $total);
    $stmt->bindParam(':estado', $estado);
    $stmt->execute();
    $idFactura = $stmt->fetchColumn();

    // Insertar detalle de la factura
    $sql = "INSERT INTO DetalleFactura (IdFactura, Tipo, Descripcion, Cantidad, PrecioUnitario) 
            VALUES (:idFactura, 'Servicio', :desc, 1, :precio)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':idFactura' => $idFactura,
        ':desc' => $detalleDesc,
        ':precio' => $total
    ]);

    // Ahora eliminar presupuesto o historial
    if ($idPresupuesto) {
        $conn->prepare("DELETE FROM Presupuestos WHERE IdPresupuesto = :id")
             ->execute([':id' => $idPresupuesto]);
    }
    if ($idHistorial) {
        $conn->prepare("DELETE FROM HistorialReparaciones WHERE IdHistorial = :id")
             ->execute([':id' => $idHistorial]);
    }
}

// --- Eliminar factura ---
if (isset($_POST['eliminar'])) {
    $idFactura = $_POST['idFactura'];
    $sql = "DELETE FROM Facturas WHERE IdFactura = :idFactura";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':idFactura', $idFactura);
    $stmt->execute();
}

// --- Consultar facturas ---
$sql = "SELECT * FROM Facturas ORDER BY FechaFactura DESC";
$stmt = $conn->query($sql);
$facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Obtener presupuestos pendientes ---
$sql = "SELECT IdPresupuesto, Total FROM Presupuestos WHERE Estado='Pendiente'";
$stmt = $conn->query($sql);
$presupuestos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Obtener reparaciones ---
$sql = "SELECT IdHistorial, Servicio, FechaReparacion FROM HistorialReparaciones";
$stmt = $conn->query($sql);
$reparaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Facturación Automática</title>
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
        .logo { width: 120px; margin: 10px auto 20px; display: block; }
        h2, h3 {
            color: #f7cbcb;
            text-shadow: -1px -1px 0 #ff3b3b, 1px -1px 0 #ff3b3b, -1px 1px 0 #ff3b3b, 1px 1px 0 #ff3b3b;
        }
        form {
            background-color: #2a2a2a;
            padding: 20px;
            border-radius: 12px;
            max-width: 600px;
            margin: 20px auto;
            text-align: left;
        }
        label { display: block; margin: 10px 0 5px; font-weight: bold; }
        select, input {
            width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #555;
            background-color: #1e1e1e; color: #fff;
        }
        button {
            background-color: #ff3b3b; color: #fff; border: none; padding: 10px 14px;
            margin-top: 15px; border-radius: 8px; font-weight: bold; cursor: pointer;
        }
        button:hover { background-color: #cc2e2e; }
        table {
            width: 100%; border-collapse: collapse; margin: 30px 0;
            background-color: #2a2a2a; border-radius: 12px; overflow: hidden;
        }
        th, td { padding: 10px; border: 1px solid #444; text-align: center; }
        th { background-color: #ff3b3b; color: white; }
        tr:hover { background-color: #3a3a3a; }
    </style>
</head>
<body>
    <a href="Vendedor.php" class="volver">⬅ Volver al Panel Vendedor</a>
    <img src="logo.png" alt="Logo Auto Parts" class="logo">
    <h2> Facturación Automática</h2>

    <!-- Formulario -->
    <form method="POST">
        <label>Presupuesto: </label>
        <select name="idPresupuesto">
            <option value="">-- Ninguno --</option>
            <?php foreach ($presupuestos as $p): ?>
                <option value="<?= $p['IdPresupuesto'] ?>">#<?= $p['IdPresupuesto'] ?> - Total: <?= $p['Total'] ?></option>
            <?php endforeach; ?>
        </select>

        <label>Reparación: </label>
        <select name="idHistorial">
            <option value="">-- Ninguna --</option>
            <?php foreach ($reparaciones as $r): ?>
                <option value="<?= $r['IdHistorial'] ?>">#<?= $r['IdHistorial'] ?> - <?= $r['Servicio'] ?> (<?= $r['FechaReparacion'] ?>)</option>
            <?php endforeach; ?>
        </select>

        <label>Estado: </label>
        <select name="estado">
            <option value="Pagada">Pagada</option>
            <option value="Pendiente">Pendiente</option>
        </select>

        <button type="submit" name="emitir">➕ Emitir Factura</button>
    </form>

    <!-- Tabla de facturas -->
    <h3>Listado de Facturas</h3>
    <table>
        <tr>
            <th>ID Factura</th>
            <th>Fecha</th>
            <th>Total</th>
            <th>Estado</th>
            <th>Acción</th>
        </tr>
        <?php foreach ($facturas as $f): ?>
            <tr>
                <td><?= $f['IdFactura'] ?></td>
                <td><?= $f['FechaFactura'] ?></td>
                <td><?= $f['Total'] ?></td>
                <td><?= $f['Estado'] ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="idFactura" value="<?= $f['IdFactura'] ?>">
                        <button type="submit" name="eliminar">🗑 Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>

