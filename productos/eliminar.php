<?php

require_once "../config/config.php";
require_once "../php/seguridad.php";
requerir_permiso($conexion, "productos");

if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
    header("Location: index.php");
    exit;
}

if (!validar_csrf($_POST["csrf_token"] ?? "")) {
    die("La sesion vencio. Volve a intentar.");
}

$id = isset($_POST["id"]) ? (int) $_POST["id"] : 0;

// Eliminar producto
$sql = "DELETE FROM productos WHERE id_producto = ?";

$stmt = mysqli_prepare($conexion, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

// Volver al listado
header("Location: index.php");
exit;

?>
