<?php

require_once "../php/conexion.php";

if (!isset($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$id = (int) $_GET["id"];

// Eliminar producto
$sql = "DELETE FROM productos WHERE id_producto = ?";

$stmt = mysqli_prepare($conexion, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

// Volver al listado
header("Location: index.php");
exit;

?>