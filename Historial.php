<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: Index.php");
    exit();
}

// Conexión a SQL Server
$serverName = "db28471.public.databaseasp.net"; 
    $connectionOptions = [
        "Database" => "db28471",
        "Uid" => "db28471",     // Usuario que creaste en SSMS
        "PWD" => "2Fb%y9-EH_z7",     // Contraseña que le diste
        "CharacterSet" => "UTF-8"
    ];
$conn = sqlsrv_connect($serverName, $connectionOptions);

// Agregar reparación
if (isset($_POST["agregar"])) {
    $idVehiculo = $_POST["idVehiculo"];
    $servicio = $_POST["servicio"];
    $piezas = $_POST["piezas"];

    $sql = "INSERT INTO HistorialReparaciones (IdVehiculo, Servicio, PiezasUsadas) VALUES (?, ?, ?)";
    $params = [$idVehiculo, $servicio, $piezas];
    sqlsrv_query($conn, $sql, $params);
}

// Obtener historial
$sql = "SELECT H.IdHistorial, V.Placa, H.FechaReparacion, H.Servicio, H.PiezasUsadas
        FROM HistorialReparaciones H
        INNER JOIN Vehiculos V ON H.IdVehiculo = V.IdVehiculo
        ORDER BY H.FechaReparacion DESC";
$result = sqlsrv_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Reparaciones</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background: #007bff; color: white; }
        form { margin-bottom: 20px; }
        input, textarea, select { margin: 5px; padding: 8px; width: 95%; }
        .btn { padding: 10px; background: #28a745; color: white; border: none; cursor: pointer; }
        .btn:hover { background: #218838; }
    </style>
</head>
<body>
    <h1>📜 Historial de Reparaciones</h1>

    <!-- Formulario -->
    <form method="POST">
        <label>Vehículo:</label><br>
        <select name="idVehiculo" required>
            <?php
            $vehiculos = sqlsrv_query($conn, "SELECT IdVehiculo, Placa FROM Vehiculos");
            while ($v = sqlsrv_fetch_array($vehiculos, SQLSRV_FETCH_ASSOC)) {
                echo "<option value='{$v['IdVehiculo']}'>{$v['Placa']}</option>";
            }
            ?>
        </select><br>

        <label>Servicio realizado:</label><br>
        <input type="text" name="servicio" required><br>

        <label>Piezas usadas:</label><br>
        <textarea name="piezas"></textarea><br>

        <button type="submit" name="agregar" class="btn">➕ Registrar Reparación</button>
    </form>

    <!-- Tabla -->
    <h2>Historial</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Vehículo</th>
            <th>Fecha</th>
            <th>Servicio</th>
            <th>Piezas Usadas</th>
        </tr>
        <?php
        while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
            echo "<tr>
                    <td>{$row['IdHistorial']}</td>
                    <td>{$row['Placa']}</td>
                    <td>" . $row['FechaReparacion']->format('Y-m-d') . "</td>
                    <td>{$row['Servicio']}</td>
                    <td>{$row['PiezasUsadas']}</td>
                  </tr>";
        }
        ?>
    </table>
    <div style="text-align:center;">
        <a href="Admin.php">⬅ Volver al Panel Admin</a>
    </div>
</body>
</html>
