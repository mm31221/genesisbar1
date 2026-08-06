<?php

require_once("../php/conexion.php");

$sql = "SELECT *
        FROM pedidos
        WHERE estado <> 'Entregado'
        ORDER BY fecha_hora_inicio ASC";

$pedidos = mysqli_query($conexion,$sql);

if(mysqli_num_rows($pedidos)==0){

    echo "<h3>No hay pedidos activos.</h3>";

    exit;

}

while($pedido=mysqli_fetch_assoc($pedidos)){

?>

<div class="comanda">

    <h2>Comanda #<?= $pedido["id_pedido"] ?></h2>

    <p>

        <b>Tipo:</b>

        <?= $pedido["tipo_pedido"] ?>

    </p>

<?php

if(!empty($pedido["id_mesa"])){

?>

<p>

<b>Mesa:</b>

<?= $pedido["id_mesa"] ?>

</p>

<?php

}

?>

<p>

<b>Total:</b>

$<?= number_format($pedido["total"],0,",",".") ?>

</p>

<p>

<b>Estado</b>

</p>

<select
class="estado"
data-id="<?= $pedido["id_pedido"] ?>">

<option
value="Pendiente"
<?= $pedido["estado"]=="Pendiente"?"selected":""; ?>>

Pendiente

</option>

<option
value="Preparando"
<?= $pedido["estado"]=="Preparando"?"selected":""; ?>>

Preparando

</option>

<option
value="Listo"
<?= $pedido["estado"]=="Listo"?"selected":""; ?>>

Listo

</option>

<option
value="Entregado">

Entregado

</option>

</select>

</div>

<?php

}

?>

<script>

document.querySelectorAll(".estado").forEach(function(select){

    select.addEventListener("change",function(){

        fetch("cambiar_estado.php",{

            method:"POST",

            headers:{

                "Content-Type":"application/x-www-form-urlencoded"

            },

            body:

            "id_pedido="+this.dataset.id+

            "&estado="+this.value

        })

        .then(r=>r.text())

        .then(function(){

            location.reload();

        });

    });

});

</script>