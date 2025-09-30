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

// --- Emitir factura automática ---
if (isset($_POST['emitir'])) {
    $idPresupuesto = !empty($_POST['idPresupuesto']) ? $_POST['idPresupuesto'] : null;
    $idHistorial = !empty($_POST['idHistorial']) ? $_POST['idHistorial'] : null;
    $estado = $_POST['estado'];

    $total = 0;

    // Si viene de presupuesto, tomar el total
    if ($idPresupuesto) {
        $sql = "SELECT Total FROM Presupuestos WHERE IdPresupuesto = :idPresupuesto";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':idPresupuesto', $idPresupuesto);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = $row ? $row['Total'] : 0;
    }

    // Si viene de historial, se podría calcular un costo fijo o de piezas usadas
    if ($idHistorial) {
        // Para simplificar, pongamos un costo fijo por reparación
        $total = 250000; 
    }

    $sql = "INSERT INTO Facturas (IdPresupuesto, IdHistorial, Total, Estado) 
            VALUES (:idPresupuesto, :idHistorial, :total, :estado)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':idPresupuesto', $idPresupuesto);
    $stmt->bindParam(':idHistorial', $idHistorial);
    $stmt->bindParam(':total', $total);
    $stmt->bindParam(':estado', $estado);
    $stmt->execute();
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
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid black; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h2>💳 Facturación Automática</h2>

    <!-- Formulario -->
    <form method="POST">
        <label>Presupuesto: </label>
        <select name="idPresupuesto">
            <option value="">-- Ninguno --</option>
            <?php foreach ($presupuestos as $p): ?>
                <option value="<?= $p['IdPresupuesto'] ?>">#<?= $p['IdPresupuesto'] ?> - Total: <?= $p['Total'] ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Reparación: </label>
        <select name="idHistorial">
            <option value="">-- Ninguna --</option>
            <?php foreach ($reparaciones as $r): ?>
                <option value="<?= $r['IdHistorial'] ?>">#<?= $r['IdHistorial'] ?> - <?= $r['Servicio'] ?> (<?= $r['FechaReparacion'] ?>)</option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Estado: </label>
        <select name="estado">
            <option value="Pagada">Pagada</option>
            <option value="Pendiente">Pendiente</option>
        </select><br><br>

        <button type="submit" name="emitir">➕ Emitir Factura</button>
    </form>

    <!-- Tabla de facturas -->
    <h3>📑 Listado de Facturas</h3>
    <table>
        <tr>
            <th>ID Factura</th>
            <th>Presupuesto</th>
            <th>Historial</th>
            <th>Fecha</th>
            <th>Total</th>
            <th>Estado</th>
            <th>Acción</th>
        </tr>
        <?php foreach ($facturas as $f): ?>
            <tr>
                <td><?= $f['IdFactura'] ?></td>
                <td><?= $f['IdPresupuesto'] ?></td>
                <td><?= $f['IdHistorial'] ?></td>
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
    <div style="text-align:center;">
        <a href="Vendedor.php">⬅ Volver al Panel Vendedor</a>
    </div>
</body>
</html>
