<?php
require 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// 🔹 Conexión BD
$serverName = "db28471.public.databaseasp.net";
$database   = "db28471";
$username   = "db28471";
$password   = "2Fb%y9-EH_z7";

try {
    $conn = new PDO("sqlsrv:Server=$serverName;Database=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

/* ===================================================
   🔹 PROCESAR FACTURA
   =================================================== */
if (isset($_POST['generar'])) {
    $idPresupuesto = !empty($_POST['idPresupuesto']) ? $_POST['idPresupuesto'] : null;
    $idCita        = !empty($_POST['idCita']) ? $_POST['idCita'] : null;
    $total         = 0;
    $detalleDesc   = "";

    // Si se factura un presupuesto
    if ($idPresupuesto) {
        $sql = "SELECT Total FROM Presupuestos WHERE IdPresupuesto = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $idPresupuesto]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = $row ? $row['Total'] : 0;
        $detalleDesc = "Facturación de Presupuesto #$idPresupuesto";

        // Eliminar presupuesto
        $conn->prepare("DELETE FROM Presupuestos WHERE IdPresupuesto = :id")
             ->execute([':id' => $idPresupuesto]);
    }

    // Si se factura una cita
    if ($idCita) {
        $sql = "SELECT Servicio FROM Citas WHERE IdCita = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $idCita]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $detalleDesc = $row ? $row['Servicio'] : "Cita";

        $total = 150000; // 👈 costo fijo o lo puedes calcular según reglas

        // Eliminar cita
        $conn->prepare("DELETE FROM Citas WHERE IdCita = :id")
             ->execute([':id' => $idCita]);
    }

    // Insertar factura
    $sql = "INSERT INTO Facturas (IdPresupuesto, Total) 
            OUTPUT INSERTED.IdFactura
            VALUES (:presupuesto, :total)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':presupuesto', $idPresupuesto);
    $stmt->bindParam(':total', $total);
    $stmt->execute();

    $idFactura = $stmt->fetchColumn();

    // Insertar detalle
    $sql = "INSERT INTO DetalleFactura (IdFactura, Tipo, Descripcion, Cantidad, PrecioUnitario) 
            VALUES (:idFactura, 'Servicio', :desc, 1, :total)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':idFactura' => $idFactura,
        ':desc' => $detalleDesc,
        ':total' => $total
    ]);

    // Generar PDF
    $html = "
    <h1>Factura #$idFactura</h1>
    <p><strong>Fecha:</strong> " . date("Y-m-d H:i:s") . "</p>
    <table border='1' cellpadding='6' cellspacing='0' width='100%'>
        <tr>
            <th>Descripción</th>
            <th>Cantidad</th>
            <th>Precio Unitario</th>
            <th>Subtotal</th>
        </tr>
        <tr>
            <td>$detalleDesc</td>
            <td>1</td>
            <td>$$total</td>
            <td>$$total</td>
        </tr>
    </table>
    <h3>Total: $$total</h3>
    <p><strong>Estado:</strong> Pagada</p>
    ";

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Descargar PDF directamente
    $dompdf->stream("Factura_$idFactura.pdf", ["Attachment" => true]);
    exit;
}

/* ===================================================
   🔹 CONSULTAR DATOS PARA EL FORMULARIO
   =================================================== */
// Presupuestos pendientes
$presupuestos = $conn->query("SELECT IdPresupuesto, Total FROM Presupuestos")->fetchAll(PDO::FETCH_ASSOC);
// Citas programadas
$citas = $conn->query("SELECT IdCita, Servicio, FechaCita FROM Citas")->fetchAll(PDO::FETCH_ASSOC);
// Facturas existentes
$facturas = $conn->query("SELECT * FROM Facturas ORDER BY FechaFactura DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Facturación</title>
    <style>
        body { font-family: Arial; background:#222; color:#eee; text-align:center; }
        form, table { margin:20px auto; padding:15px; background:#333; border-radius:8px; }
        select, button { margin:5px; padding:8px; }
        table { border-collapse: collapse; width:80%; }
        th, td { border:1px solid #555; padding:8px; }
        th { background:#ff3b3b; color:white; }
    </style>
</head>
<body>
    <h1>Generar Factura</h1>

    <form method="POST">
        <label>Presupuesto:</label>
        <select name="idPresupuesto">
            <option value="">-- Ninguno --</option>
            <?php foreach ($presupuestos as $p): ?>
                <option value="<?= $p['IdPresupuesto'] ?>">#<?= $p['IdPresupuesto'] ?> - $<?= $p['Total'] ?></option>
            <?php endforeach; ?>
        </select>

        <label>Cita:</label>
        <select name="idCita">
            <option value="">-- Ninguna --</option>
            <?php foreach ($citas as $c): ?>
                <option value="<?= $c['IdCita'] ?>">#<?= $c['IdCita'] ?> - <?= $c['Servicio'] ?> (<?= $c['FechaCita'] ?>)</option>
            <?php endforeach; ?>
        </select>

        <button type="submit" name="generar">🧾 Facturar</button>
    </form>

    <h2>Facturas Generadas</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Presupuesto</th>
            <th>Total</th>
            <th>Fecha</th>
        </tr>
        <?php foreach ($facturas as $f): ?>
        <tr>
            <td><?= $f['IdFactura'] ?></td>
            <td><?= $f['IdPresupuesto'] ?></td>
            <td>$<?= $f['Total'] ?></td>
            <td><?= $f['FechaFactura'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
