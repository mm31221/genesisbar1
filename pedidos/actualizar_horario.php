<?php
date_default_timezone_set("America/Argentina/Buenos_Aires");

require_once("../config/config.php");
require_once("../php/seguridad.php");

requerir_permiso($conexion, "pedidos");

if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
    die("Metodo no permitido.");
}

if (!validar_csrf($_POST["csrf_token"] ?? "")) {
    die("La sesion vencio. Volve a intentar.");
}

$id_pedido = isset($_POST["id_pedido"]) ? (int) $_POST["id_pedido"] : 0;
$horario_input = trim($_POST["horario_entrega"] ?? "");

if ($id_pedido < 1 || !columna_existe($conexion, "pedidos", "horario_entrega")) {
    die("Datos invalidos.");
}

$horario_entrega = null;

if ($horario_input !== "") {
    $timestamp = strtotime($horario_input);

    if (!$timestamp) {
        die("Horario de entrega invalido.");
    }

    $horario_entrega = date("Y-m-d H:i:s", $timestamp);
}

$stmt = mysqli_prepare($conexion, "UPDATE pedidos SET horario_entrega = ? WHERE id_pedido = ? AND estado_pago = 'Pendiente'");

if (!$stmt) {
    die("No se pudo preparar la actualizacion.");
}

mysqli_stmt_bind_param($stmt, "si", $horario_entrega, $id_pedido);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

header("Location: ver.php?id=" . $id_pedido);
exit;
?>
