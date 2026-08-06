<?php
require_once("../config/config.php");
require_once("../includes/header.php");

$sql = "SELECT
        pedidos.id_pedido,
        pedidos.numero_pedido,
        pedidos.tipo_pedido,
        pedidos.mesa,
        pedidos.estado,
        pedidos.total,
        pedidos.descuento,
        pedidos.recargo,
        pedidos.total_final,
        pedidos.fecha_hora_inicio,
        clientes.nombre AS nombre_cliente,
        clientes.telefono AS telefono_cliente,
        formas_pago.nombre AS forma_pago,
        mesas.numero AS numero_mesa
    FROM pedidos
    LEFT JOIN clientes ON clientes.id_cliente = pedidos.id_cliente
    LEFT JOIN formas_pago ON formas_pago.id_forma_pago = pedidos.id_forma_pago
    LEFT JOIN mesas ON mesas.id_mesa = pedidos.id_mesa
    WHERE pedidos.estado IN ('Listo', 'Entregado')
    ORDER BY pedidos.fecha_hora_inicio ASC";

$pedidos = mysqli_query($conexion, $sql);
?>

<h2>Caja</h2>

<p>Pedidos listos para cobrar.</p>

<div id="contenedorPedidos" class="caja-grid">

<?php if (!$pedidos || mysqli_num_rows($pedidos) === 0) { ?>

    <div class="tarjeta-pedido">
        <h3>No hay pedidos pendientes de cobro.</h3>
    </div>

<?php } else { ?>

    <?php while ($pedido = mysqli_fetch_assoc($pedidos)) { ?>

        <div class="tarjeta-pedido">

            <h3>Pedido <?= htmlspecialchars($pedido["numero_pedido"] ?: "#" . $pedido["id_pedido"]); ?></h3>

            <p><b>Tipo:</b> <?= htmlspecialchars($pedido["tipo_pedido"]); ?></p>

            <?php if ($pedido["tipo_pedido"] === "Mesa" && trim($pedido["numero_mesa"] ?: $pedido["mesa"]) !== "") { ?>
                <p><b>Mesa:</b> <?= htmlspecialchars($pedido["numero_mesa"] ?: $pedido["mesa"]); ?></p>
            <?php } ?>

            <?php if ($pedido["tipo_pedido"] !== "Mesa" && trim($pedido["nombre_cliente"] ?? "") !== "") { ?>
                <p><b>Cliente:</b> <?= htmlspecialchars($pedido["nombre_cliente"]); ?></p>
            <?php } ?>

            <?php if ($pedido["tipo_pedido"] !== "Mesa" && trim($pedido["telefono_cliente"] ?? "") !== "") { ?>
                <p><b>Telefono:</b> <?= htmlspecialchars($pedido["telefono_cliente"]); ?></p>
            <?php } ?>

            <?php if ($pedido["tipo_pedido"] !== "Mesa" && trim($pedido["forma_pago"] ?? "") !== "") { ?>
                <p><b>Pago previsto:</b> <?= htmlspecialchars($pedido["forma_pago"]); ?></p>
            <?php } ?>

            <hr>

            <h4>Productos</h4>

            <?php
            $id_pedido = (int) $pedido["id_pedido"];
            $sql_detalle = "SELECT detalle_pedido.*, productos.nombre
                FROM detalle_pedido
                INNER JOIN productos ON productos.id_producto = detalle_pedido.id_producto
                WHERE detalle_pedido.id_pedido = $id_pedido
                ORDER BY detalle_pedido.id_detalle ASC";
            $detalle = mysqli_query($conexion, $sql_detalle);
            ?>

            <?php while ($producto = mysqli_fetch_assoc($detalle)) { ?>
                <p><?= (int) $producto["cantidad"]; ?> x <?= htmlspecialchars($producto["nombre"]); ?></p>
            <?php } ?>

            <hr>

            <p><b>Subtotal:</b> $<?= number_format((float) $pedido["total"], 0, ",", "."); ?></p>

            <a class="boton" href="cobrar.php?id=<?= (int) $pedido["id_pedido"]; ?>">Cobrar</a>

        </div>

    <?php } ?>

<?php } ?>

</div>

<?php
require_once("../includes/footer.php");
?>
