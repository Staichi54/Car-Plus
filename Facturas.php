<?php
require 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// 🔹 Conexión BD
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

/* ===================================================
   🔹 GENERAR FACTURA Y PDF
   =================================================== */
if (isset($_POST['generar'])) {
    $idPresupuesto = !empty($_POST['idPresupuesto']) ? $_POST['idPresupuesto'] : null;
    $idHistorial   = !empty($_POST['idHistorial']) ? $_POST['idHistorial'] : null;
    $estado        = $_POST['estado'];

    $total = 0;

    // Buscar total según presupuesto
    if ($idPresupuesto) {
        $sql = "SELECT Total FROM Presupuestos WHERE IdPresupuesto = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $idPresupuesto);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = $row ? $row['Total'] : 0;
    }

    // Si viene del historial (ejemplo costo fijo)
    if ($idHistorial) {
        $total = 250000;
    }

    // Insertar factura
    $sql = "INSERT INTO Facturas (IdPresupuesto, IdHistorial, Total, Estado) 
            OUTPUT INSERTED.IdFactura
            VALUES (:presupuesto, :historial, :total, :estado)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':presupuesto', $idPresupuesto);
    $stmt->bindParam(':historial', $idHistorial);
    $stmt->bindParam(':total', $total);
    $stmt->bindParam(':estado', $estado);
    $stmt->execute();

    $idFactura = $stmt->fetchColumn();

    // Insertar detalle de ejemplo
    $sql = "INSERT INTO DetalleFactura (IdFactura, Tipo, Descripcion, Cantidad, PrecioUnitario) 
            VALUES (:idFactura, 'Servicio', 'Trabajo mecánico', 1, :total)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':idFactura', $idFactura);
    $stmt->bindParam(':total', $total);
    $stmt->execute();

    // 🔹 Generar PDF de la factura
    $html = "
    <h1>Factura #$idFactura</h1>
    <p><strong>Fecha:</strong> " . date("Y-m-d H:i:s") . "</p>
    <table border='1' cellpadding='6' cellspacing='0' width='100%'>
        <tr>
            <th>Tipo</th>
            <th>Descripción</th>
            <th>Cantidad</th>
            <th>Precio Unitario</th>
            <th>Subtotal</th>
        </tr>
        <tr>
            <td>Servicio</td>
            <td>Trabajo mecánico</td>
            <td>1</td>
            <td>$$total</td>
            <td>$$total</td>
        </tr>
    </table>
    <h3>Total: $$total</h3>
    <p><strong>Estado:</strong> $estado</p>
    ";

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Guardar PDF en carpeta
    $output = $dompdf->output();
    $rutaPDF = "facturas/Factura_$idFactura.pdf";
    file_put_contents($rutaPDF, $output);

    // OPCIONAL: Si agregas columna RutaPDF a Facturas
    // $sql = "UPDATE Facturas SET RutaPDF = :ruta WHERE IdFactura = :id";
    // $stmt = $conn->prepare($sql);
    // $stmt->bindParam(':ruta', $rutaPDF);
    // $stmt->bindParam(':id', $idFactura);
    // $stmt->execute();

    echo "<p style='color:lime;'>✅ Factura generada y guardada en <b>$rutaPDF</b></p>";
}

/* ===================================================
   🔹 CONSULTAR FACTURAS
   =================================================== */
$sql = "SELECT * FROM Facturas ORDER BY FechaFactura DESC";
$stmt = $conn->query($sql);
$facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Presupuestos
$sql = "SELECT IdPresupuesto, Total FROM Presupuestos WHERE Estado='Pendiente'";
$stmt = $conn->query($sql);
$presupuestos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Historial
$sql = "SELECT IdHistorial, Servicio FROM HistorialReparaciones";
$stmt = $conn->query($sql);
$historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Facturación</title>
    <style>
        body { font-family: Arial; background:#222; color:#eee; text-align:center; }
        form, table { margin:20px auto; padding:15px; background:#333; border-radius:8px; }
        select, input, button { margin:5px; padding:8px; }
        table { border-collapse: collapse; width:80%; }
        th, td { border:1px solid #555; padding:8px; }
        th { background:#ff3b3b; color:white; }
    </style>
</head>
<body>
    <h1>Facturación</h1>

    <!-- Generar factura -->
    <form method="POST">
        <label>Presupuesto:</label>
        <select name="idPresupuesto">
            <option value="">-- Ninguno --</option>
            <?php foreach ($presupuestos as $p): ?>
                <option value="<?= $p['IdPresupuesto'] ?>">#<?= $p['IdPresupuesto'] ?> - $<?= $p['Total'] ?></option>
            <?php endforeach; ?>
        </select>

        <label>Historial:</label>
        <select name="idHistorial">
            <option value="">-- Ninguno --</option>
            <?php foreach ($historial as $h): ?>
                <option value="<?= $h['IdHistorial'] ?>">#<?= $h['IdHistorial'] ?> - <?= $h['Servicio'] ?></option>
            <?php endforeach; ?>
        </select>

        <label>Estado:</label>
        <select name="estado">
            <option value="Pagada">Pagada</option>
            <option value="Pendiente">Pendiente</option>
        </select>

        <button type="submit" name="generar">🧾 Generar Factura</button>
    </form>

    <!-- Listado facturas -->
    <h2>Facturas Generadas</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Presupuesto</th>
            <th>Historial</th>
            <th>Total</th>
            <th>Estado</th>
            <th>PDF</th>
        </tr>
        <?php foreach ($facturas as $f): ?>
        <tr>
            <td><?= $f['IdFactura'] ?></td>
            <td><?= $f['IdPresupuesto'] ?></td>
            <td><?= $f['IdHistorial'] ?></td>
            <td>$<?= $f['Total'] ?></td>
            <td><?= $f['Estado'] ?></td>
            <td><a href="facturas/Factura_<?= $f['IdFactura'] ?>.pdf" target="_blank">📄 Ver PDF</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
