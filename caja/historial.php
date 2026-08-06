<?php

require_once("../config/config.php");
require_once("../includes/header.php");

//=========================================
// FECHA
//=========================================

$hoy = date("Y-m-d");

//=========================================
// PEDIDOS ENTREGADOS DEL DÍA
//=========================================

$sql = "SELECT *
        FROM pedidos
        WHERE estado='Entregado'
        AND DATE(fecha_hora_entrega)='$hoy'
        ORDER BY fecha_hora_entrega DESC";

$resultado = mysqli_query($conexion,$sql);

//=========================================
// TOTALES
//=========================================

$totalVentas = 0;
$totalPedidos = 0;

?>

<h2>Historial de Caja</h2>

<table class="tabla">

<tr>

    <th>Pedido</th>

    <th>Hora</th>

    <th>Tipo</th>

    <th>Mesa</th>

    <th>Total</th>

    <th>Forma de Pago</th>

    <th>Ticket</th>

</tr>

<?php

while($pedido=mysqli_fetch_assoc($resultado)){

    $totalVentas += $pedido["total"];
    $totalPedidos++;

    switch($pedido["id_forma_pago"]){

        case 1:
            $forma="Efectivo";
        break;

        case 2:
            $forma="Débito";
        break;

        case 3:
            $forma="Crédito";
        break;

        case 4:
            $forma="Transferencia";
        break;

        case 5:
            $forma="Mercado Pago";
        break;

        default:
            $forma="-";

    }

?>

<tr>

<td>

#<?= $pedido["id_pedido"] ?>

</td>

<td>

<?= date("H:i",strtotime($pedido["fecha_hora_entrega"])) ?>

</td>

<td>

<?= $pedido["tipo_pedido"] ?>

</td>

<td>

<?= $pedido["mesa"] ?>

</td>

<td>

$<?= number_format($pedido["total"],0,",",".") ?>

</td>

<td>

<?= $forma ?>

</td>

<td>

<a href="ticket.php?id=<?= $pedido["id_pedido"] ?>">

Ver

</a>

</td>

</tr>

<?php

}

?>

</table>

<hr>

<h3>

Pedidos del día:

<?= $totalPedidos ?>

</h3>

<h3>

Ventas del día:

$<?= number_format($totalVentas,0,",",".") ?>

</h3>

<?php

require_once("../includes/footer.php");

?>