<?php
include("conexion.php");

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$volver = $_GET['volver'] ?? '';
$destino = ($volver === 'cocina') ? 'cocina.php' : 'comandas.php';

if ($id > 0) {
    $sql = "UPDATE pedidos SET estado = 'Entregado' WHERE id = ?";
    $stmt = mysqli_prepare($conexion, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

header("Location: " . $destino);
exit;
?>
