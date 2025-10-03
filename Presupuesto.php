<?php
session_start();
if (!isset($_SESSION["usuario"]) || $_SESSION["rol"] != "vendedor") {
    header("Location: Index.php");
    exit();
}

// 🔹 Conexión a SQL Server
$serverName = "db28471.public.databaseasp.net"; 
$connectionOptions = [
    "Database" => "db28471",
    "Uid" => "db28471",
    "PWD" => "2Fb%y9-EH_z7",
    "CharacterSet" => "UTF-8"
];
$conn = sqlsrv_connect($serverName, $connectionOptions);

/* =========================
   🔹 CRUD PRESUPUESTOS
   ========================= */

// ➡️ CREATE Presupuesto
if (isset($_POST["crear"])) {
    $idVehiculo = $_POST["idVehiculo"];
    $sql = "INSERT INTO Presupuestos (IdVehiculo) VALUES (?)";
    sqlsrv_query($conn, $sql, [$idVehiculo]);
    header("Location: Presupuesto.php");
    exit();
}

// ➡️ UPDATE Presupuesto (Estado)
if (isset($_POST["editarPresupuesto"])) {
    $idPresupuesto = $_POST["idPresupuesto"];
    $estado = $_POST["estado"];
    $sql = "UPDATE Presupuestos SET Estado=? WHERE IdPresupuesto=?";
    sqlsrv_query($conn, $sql, [$estado, $idPresupuesto]);
    header("Location: Presupuesto.php?id=$idPresupuesto");
    exit();
}

// ➡️ DELETE Presupuesto
if (isset($_POST["eliminarPresupuesto"])) {
    $idPresupuesto = $_POST["idPresupuesto"];
    $sql = "DELETE FROM DetallePresupuesto WHERE IdPresupuesto=?";
    sqlsrv_query($conn, $sql, [$idPresupuesto]);
    $sql = "DELETE FROM Presupuestos WHERE IdPresupuesto=?";
    sqlsrv_query($conn, $sql, [$idPresupuesto]);
    header("Location: Presupuesto.php");
    exit();
}

/* =========================
   🔹 CRUD DETALLES
   ========================= */

// ➡️ CREATE Detalle
if (isset($_POST["agregarDetalle"])) {
    $idPresupuesto = $_POST["idPresupuesto"];
    $tipo = $_POST["tipo"];
    $descripcion = $_POST["descripcion"];
    $cantidad = $_POST["cantidad"];
    $precio = $_POST["precio"];

    $sql = "INSERT INTO DetallePresupuesto (IdPresupuesto, Tipo, Descripcion, Cantidad, PrecioUnitario)
            VALUES (?, ?, ?, ?, ?)";
    sqlsrv_query($conn, $sql, [$idPresupuesto, $tipo, $descripcion, $cantidad, $precio]);

    // Recalcular total
    $sql = "UPDATE Presupuestos
            SET Total = (SELECT SUM(Subtotal) FROM DetallePresupuesto WHERE IdPresupuesto=?)
            WHERE IdPresupuesto=?";
    sqlsrv_query($conn, $sql, [$idPresupuesto, $idPresupuesto]);

    header("Location: Presupuesto.php?id=$idPresupuesto");
    exit();
}

// ➡️ UPDATE Detalle
if (isset($_POST["editarDetalle"])) {
    $idDetalle = $_POST["idDetalle"];
    $idPresupuesto = $_POST["idPresupuesto"];
    $cantidad = $_POST["cantidad"];
    $precio = $_POST["precio"];

    $sql = "UPDATE DetallePresupuesto SET Cantidad=?, PrecioUnitario=? WHERE IdDetalle=?";
    sqlsrv_query($conn, $sql, [$cantidad, $precio, $idDetalle]);

    // Recalcular total
    $sql = "UPDATE Presupuestos
            SET Total = (SELECT SUM(Subtotal) FROM DetallePresupuesto WHERE IdPresupuesto=?)
            WHERE IdPresupuesto=?";
    sqlsrv_query($conn, $sql, [$idPresupuesto, $idPresupuesto]);

    header("Location: Presupuesto.php?id=$idPresupuesto");
    exit();
}

// ➡️ DELETE Detalle
if (isset($_POST["eliminarDetalle"])) {
    $idDetalle = $_POST["idDetalle"];
    $idPresupuesto = $_POST["idPresupuesto"];

    $sql = "DELETE FROM DetallePresupuesto WHERE IdDetalle=?";
    sqlsrv_query($conn, $sql, [$idDetalle]);

    // Recalcular total
    $sql = "UPDATE Presupuestos
            SET Total = (SELECT SUM(Subtotal) FROM DetallePresupuesto WHERE IdPresupuesto=?)
            WHERE IdPresupuesto=?";
    sqlsrv_query($conn, $sql, [$idPresupuesto, $idPresupuesto]);

    header("Location: Presupuesto.php?id=$idPresupuesto");
    exit();
}

/* =========================
   🔹 CONSULTAS
   ========================= */

// Presupuesto seleccionado
$idPresupuesto = isset($_GET["id"]) ? $_GET["id"] : null;
$presupuesto = null;
$detalles = null;
if ($idPresupuesto) {
    $sql = "SELECT P.IdPresupuesto, V.Placa, P.FechaPresupuesto, P.Total, P.Estado
            FROM Presupuestos P
            INNER JOIN Vehiculos V ON P.IdVehiculo = V.IdVehiculo
            WHERE P.IdPresupuesto=?";
    $res = sqlsrv_query($conn, $sql, [$idPresupuesto]);
    $presupuesto = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);

    $sql = "SELECT * FROM DetallePresupuesto WHERE IdPresupuesto=?";
    $detalles = sqlsrv_query($conn, $sql, [$idPresupuesto]);
}

// Lista de presupuestos
$lista = sqlsrv_query($conn, "
    SELECT P.IdPresupuesto, V.Placa, P.FechaPresupuesto, P.Total, P.Estado
    FROM Presupuestos P
    INNER JOIN Vehiculos V ON P.IdVehiculo = V.IdVehiculo
    ORDER BY P.FechaPresupuesto DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Presupuestos</title>
  <style>
    /* Fondo general en modo oscuro */
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #1e1e1e;
      color: #f0f0f0;
      text-align: center;
    }

    /* Encabezados */
    h1 {
      margin: 20px 0 10px;
      color: #f7cbcb;
      font-weight: bold;
      font-size: 28px;
      text-shadow: 
        -1px -1px 0 #ff3b3b,
         1px -1px 0 #ff3b3b,
        -1px  1px 0 #ff3b3b,
         1px  1px 0 #ff3b3b;
    }

    h2, h3 {
      color: #ff3b3b;
      margin: 20px 0 10px;
    }

    /* Logo */
    .logo {
      width: 120px;
      margin: 20px auto;
      display: block;
    }

    /* Formularios */
    form {
      width: 90%;
      max-width: 800px;
      margin: 20px auto;
      padding: 20px;
      border-radius: 12px;
      background: #2a2a2a;
      box-shadow: 0 0 12px rgba(0, 0, 0, 0.6);
    }

    label {
      display: block;
      margin-top: 10px;
      font-weight: bold;
      text-align: left;
      color: #f0f0f0;
    }

    input, select {
      margin: 6px 0;
      padding: 8px;
      width: 95%;
      border: none;
      border-radius: 6px;
      background: #3b3b3b;
      color: #f0f0f0;
    }

    input:focus, select:focus {
      outline: none;
      border: 1px solid #ff3b3b;
      background: #444;
    }

    /* Botones */
    .btn {
      display: inline-block;
      margin: 8px 5px;
      padding: 10px 16px;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
      transition: 0.3s ease;
      text-decoration: none;
      color: #fff;
    }

    .btn-primary { background: #ff3b3b; }
    .btn-primary:hover { background: #cc2e2e; }

    .btn-view { background: #ffaa2e; }
    .btn-view:hover { background: #e68a00; }

    /* Tablas */
    table {
      border-collapse: collapse;
      width: 90%;
      margin: 20px auto;
      background: #2a2a2a;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 0 12px rgba(0, 0, 0, 0.6);
    }

    th, td {
      border: 1px solid #444;
      padding: 10px;
      text-align: center;
    }

    th {
      background: #ff3b3b;
      color: #fff;
    }

    tr {
      transition: background 0.3s ease;
    }

    tr:hover {
      background: #3b3b3b;
      cursor: pointer;
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
  </style>
</head>
<body>
  <!-- Botón volver -->
  <a href="Vendedor.php" class="volver">⬅ Volver al Panel Vendedor</a>

  <!-- Logo -->
  <img src="logo.png" alt="Logo Auto Parts" class="logo">

  <h1> Gestión de Presupuestos</h1>

  <!-- Crear nuevo presupuesto -->
  <form method="POST">
    <label>Vehículo:</label>
    <select name="idVehiculo" required>
      <?php
      $vehiculos = sqlsrv_query($conn, "SELECT IdVehiculo, Placa FROM Vehiculos");
      while ($v = sqlsrv_fetch_array($vehiculos, SQLSRV_FETCH_ASSOC)) {
          echo "<option value='{$v['IdVehiculo']}'>{$v['Placa']}</option>";
      }
      ?>
    </select>
    <button type="submit" name="crear" class="btn btn-primary">➕ Crear Presupuesto</button>
  </form>

  <!-- Lista de presupuestos -->
  <h2> Presupuestos Existentes</h2>
  <table>
    <tr>
      <th>ID</th>
      <th>Vehículo</th>
      <th>Fecha</th>
      <th>Total</th>
      <th>Estado</th>
      <th>Acción</th>
    </tr>
    <?php while ($row = sqlsrv_fetch_array($lista, SQLSRV_FETCH_ASSOC)) { ?>
      <tr>
        <td><?php echo $row["IdPresupuesto"]; ?></td>
        <td><?php echo $row["Placa"]; ?></td>
        <td><?php echo $row["FechaPresupuesto"]->format("Y-m-d"); ?></td>
        <td><?php echo number_format($row["Total"], 2); ?></td>
        <td><?php echo $row["Estado"]; ?></td>
        <td><a href="Presupuesto.php?id=<?php echo $row['IdPresupuesto']; ?>" class="btn btn-view">🔍 Ver</a></td>
      </tr>
    <?php } ?>
  </table>

  <?php if ($presupuesto) { ?>
    <h2>📝 Presupuesto #<?php echo $presupuesto["IdPresupuesto"]; ?> - Vehículo <?php echo $presupuesto["Placa"]; ?></h2>
    <p>Fecha: <?php echo $presupuesto["FechaPresupuesto"]->format("Y-m-d"); ?> | 
       Estado: <?php echo $presupuesto["Estado"]; ?> | 
       Total: <b><?php echo number_format($presupuesto["Total"], 2); ?></b></p>

    <!-- Agregar detalle -->
    <form method="POST">
      <input type="hidden" name="idPresupuesto" value="<?php echo $presupuesto["IdPresupuesto"]; ?>">

      <label>Tipo:</label>
      <select name="tipo" required>
        <option value="Servicio">Servicio</option>
        <option value="Repuesto">Repuesto</option>
      </select>

      <label>Descripción:</label>
      <input type="text" name="descripcion" required>

      <label>Cantidad:</label>
      <input type="number" name="cantidad" value="1" min="1" required>

      <label>Precio unitario:</label>
      <input type="number" step="0.01" name="precio" required>

      <button type="submit" name="agregarDetalle" class="btn btn-primary">➕ Agregar</button>
    </form>

    <!-- Detalle del presupuesto -->
    <h3>Detalle</h3>
    <table>
      <tr>
        <th>ID</th>
        <th>Tipo</th>
        <th>Descripción</th>
        <th>Cantidad</th>
        <th>Precio</th>
        <th>Subtotal</th>
      </tr>
      <?php while ($d = sqlsrv_fetch_array($detalles, SQLSRV_FETCH_ASSOC)) { ?>
        <tr>
          <td><?php echo $d["IdDetalle"]; ?></td>
          <td><?php echo $d["Tipo"]; ?></td>
          <td><?php echo $d["Descripcion"]; ?></td>
          <td><?php echo $d["Cantidad"]; ?></td>
          <td><?php echo number_format($d["PrecioUnitario"], 2); ?></td>
          <td><?php echo number_format($d["Subtotal"], 2); ?></td>
        </tr>
      <?php } ?>
    </table>
  <?php } ?>
</body>
</html>

