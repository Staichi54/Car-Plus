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
    $mensajeLog = "";

    // Validar presupuesto
    if ($idPresupuesto) {
        $sql = "SELECT Total FROM Presupuestos WHERE IdPresupuesto = :idPresupuesto";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':idPresupuesto' => $idPresupuesto]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $total = $row['Total'];
            $detalleDesc = "Presupuesto #" . $idPresupuesto;
            $mensajeLog .= "Factura con Presupuesto válido ($idPresupuesto). ";
        } else {
            $idPresupuesto = null; // Evitamos el error de FK
            $mensajeLog .= "Presupuesto no encontrado, se usó NULL. ";
        }
    }

    // Validar historial
    if ($idHistorial) {
        $sql = "SELECT Servicio FROM HistorialReparaciones WHERE IdHistorial = :idHistorial";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':idHistorial' => $idHistorial]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $total = 250000; // Aquí puedes ajustar la lógica real
            $detalleDesc = "Reparación #" . $idHistorial;
            $mensajeLog .= "Factura con Historial válido ($idHistorial). ";
        } else {
            $idHistorial = null; // Evitamos el error de FK
            $mensajeLog .= "Historial no encontrado, se usó NULL. ";
        }
    }

    // Insertar factura
    $sql = "INSERT INTO Facturas (IdPresupuesto, IdHistorial, Total, Estado) 
            OUTPUT INSERTED.IdFactura
            VALUES (:idPresupuesto, :idHistorial, :total, :estado)";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':idPresupuesto', $idPresupuesto, is_null($idPresupuesto) ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(':idHistorial', $idHistorial, is_null($idHistorial) ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindParam(':total', $total);
    $stmt->bindParam(':estado', $estado);
    $stmt->execute();
    $idFactura = $stmt->fetchColumn();

    // Insertar detalle
    $sql = "INSERT INTO DetalleFactura (IdFactura, Tipo, Descripcion, Cantidad, PrecioUnitario) 
            VALUES (:idFactura, 'Servicio', :desc, 1, :precio)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':idFactura' => $idFactura,
        ':desc' => $detalleDesc,
        ':precio' => $total
    ]);

    // Guardar log en LogFacturas
    $sql = "INSERT INTO LogFacturas (Mensaje) VALUES (:msg)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':msg' => "Factura $idFactura creada. $mensajeLog"]);

    // Eliminar presupuesto o historial (si existían realmente)
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
