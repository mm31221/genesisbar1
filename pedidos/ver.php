<?php
require_once("../php/conexion.php");
require_once("../includes/header.php");

if(!isset($_GET['id'])){
    die("Pedido no encontrado.");
}

$id = intval($_GET['id']);

// Pedido
$sql = "SELECT * FROM pedidos WHERE id_pedido=$id";
$resultado = mysqli_query($conexion,$sql);

if(mysqli_num_rows($resultado)==0){
    die("Pedido inexistente.");
}

$pedido = mysqli_fetch_assoc($resultado);

// Productos del pedido
$sqlDetalle = "
SELECT
detalle_pedido.*,
productos.nombre
FROM detalle_pedido
INNER JOIN productos
ON productos.id_producto=detalle_pedido.id_producto
WHERE id_pedido=$id
";

$detalle = mysqli_query($conexion,$sqlDetalle);
?>

<div class="contenedor">

<h2>Comanda #<?= $pedido['id_pedido']; ?></h2>

<div class="pedido-info">

<p><strong>Tipo:</strong> <?= $pedido['tipo_pedido']; ?></p>

<p><strong>Mesa:</strong> <?= $pedido['mesa']; ?></p>

<p><strong>Estado:</strong>

<span class="estado <?= strtolower($pedido['estado']); ?>">

<?= $pedido['estado']; ?>

</span>

</p>

<p><strong>Hora:</strong> <?= $pedido["fecha_hora_inicio"]; ?>

<p><strong>Total:</strong>

$<?= number_format($pedido['total'],0,",","."); ?>

</p>

</div>

<br>

<h3>Productos</h3>

<table>

<tr>

<th>Producto</th>

<th>Cantidad</th>

<th>Precio</th>

<th>Subtotal</th>

</tr>

<?php

while($fila=mysqli_fetch_assoc($detalle)){

?>

<tr>

<td><?= $fila['nombre']; ?></td>

<td><?= $fila['cantidad']; ?></td>

<td>$<?= number_format($fila['precio_unitario'],0,",","."); ?></td>

<td>$<?= number_format($fila['subtotal'],0,",","."); ?></td>

</tr>

<?php } ?>

</table>

<br>

<?php

if($pedido['observaciones']!=""){

?>

<h3>Observaciones</h3>

<div class="observaciones">

<?= nl2br($pedido['observaciones']); ?>

</div>

<br>

<?php } ?>

<h3>Cambiar Estado</h3>

<div class="acciones">

<a class="btn-estado pendiente"
href="estado.php?id=<?= $id ?>&estado=Pendiente">

Pendiente

</a>

<a class="btn-estado preparando"
href="estado.php?id=<?= $id ?>&estado=Preparando">

Preparando

</a>

<a class="btn-estado listo"
href="estado.php?id=<?= $id ?>&estado=Listo">

Listo

</a>

<a class="btn-estado entregado"
href="estado.php?id=<?= $id ?>&estado=Entregado">

Entregado

</a>

</div>

<br><br>

<a href="index.php" class="boton">

← Volver

</a>

</div>

<?php
require_once("../includes/footer.php");
?>