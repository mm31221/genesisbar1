<?php
require_once("../config/config.php");

if (!isset($_GET["id"])) {
    die("Pedido inexistente.");
}

$id_pedido = (int) $_GET["id"];

$sql = "SELECT
        pedidos.*,
        formas_pago.nombre AS forma_pago,
        mesas.numero AS numero_mesa
    FROM pedidos
    LEFT JOIN formas_pago ON formas_pago.id_forma_pago = pedidos.id_forma_pago
    LEFT JOIN mesas ON mesas.id_mesa = pedidos.id_mesa
    WHERE pedidos.id_pedido = ?
    LIMIT 1";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_pedido);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$pedido = mysqli_fetch_assoc($resultado);
mysqli_stmt_close($stmt);

if (!$pedido) {
    die("Pedido no encontrado.");
}

$total_final = (float) ($pedido["total_final"] > 0 ? $pedido["total_final"] : $pedido["total"]);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ticket</title>
<link rel="stylesheet" href="../css/imprimir.css">
</head>
<body>

<div class="ticket">

<h2>GENESIS BAR</h2>

<hr>

<p><b>Pedido:</b> <?= htmlspecialchars($pedido["numero_pedido"] ?: "#" . $pedido["id_pedido"]); ?></p>
<p><b>Fecha:</b> <?= htmlspecialchars(date("d/m/Y H:i", strtotime($pedido["fecha_hora_cobro"] ?: $pedido["fecha_hora_entrega"] ?: $pedido["fecha_hora_inicio"]))); ?></p>
<p><b>Tipo:</b> <?= htmlspecialchars($pedido["tipo_pedido"]); ?></p>

<?php if ($pedido["tipo_pedido"] === "Mesa" && trim($pedido["numero_mesa"] ?: $pedido["mesa"]) !== "") { ?>
    <p><b>Mesa:</b> <?= htmlspecialchars($pedido["numero_mesa"] ?: $pedido["mesa"]); ?></p>
<?php } ?>

<hr>

<h3>Detalle</h3>

<?php
$sql_detalle = "SELECT detalle_pedido.*, productos.nombre
    FROM detalle_pedido
    INNER JOIN productos ON productos.id_producto = detalle_pedido.id_producto
    WHERE detalle_pedido.id_pedido = ?
    ORDER BY detalle_pedido.id_detalle ASC";
$stmt_detalle = mysqli_prepare($conexion, $sql_detalle);
mysqli_stmt_bind_param($stmt_detalle, "i", $id_pedido);
mysqli_stmt_execute($stmt_detalle);
$detalle = mysqli_stmt_get_result($stmt_detalle);
?>

<?php while ($producto = mysqli_fetch_assoc($detalle)) { ?>
    <p><?= (int) $producto["cantidad"]; ?> x <?= htmlspecialchars($producto["nombre"]); ?></p>
<?php } ?>

<?php mysqli_stmt_close($stmt_detalle); ?>

<hr>

<p><b>Subtotal:</b> $<?= number_format((float) $pedido["total"], 0, ",", "."); ?></p>

<?php if ((float) $pedido["descuento"] > 0) { ?>
    <p><b>Descuento:</b> $<?= number_format((float) $pedido["descuento"], 0, ",", "."); ?></p>
<?php } ?>

<?php if ((float) $pedido["recargo"] > 0) { ?>
    <p><b>Recargo:</b> $<?= number_format((float) $pedido["recargo"], 0, ",", "."); ?></p>
<?php } ?>

<h3>TOTAL $<?= number_format($total_final, 0, ",", "."); ?></h3>

<p><b>Forma de Pago:</b> <?= htmlspecialchars($pedido["forma_pago"] ?: "-"); ?></p>

<hr>

<p>Gracias por elegir Genesis Bar.</p>

</div>

<script>
window.print();
</script>

</body>
</html>
