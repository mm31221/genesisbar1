<?php
require_once("../config/config.php");
require_once("funciones.php");
require_once("../php/seguridad.php");
requerir_permiso($conexion, "caja");

$id_pedido = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($id_pedido < 1) {
    die("Pedido inexistente.");
}

$sql = "SELECT
        pedidos.*,
        formas_pago.nombre AS forma_pago,
        clientes.nombre AS nombre_cliente,
        clientes.telefono AS telefono_cliente,
        clientes.puntos AS puntos_acumulados,
        usuarios.nombre AS cajero,
        mesas.numero AS numero_mesa
    FROM pedidos
    LEFT JOIN formas_pago ON formas_pago.id_forma_pago = pedidos.id_forma_pago
    LEFT JOIN clientes ON clientes.id_cliente = pedidos.id_cliente
    LEFT JOIN usuarios ON usuarios.id_usuario = pedidos.id_usuario_cobro
    LEFT JOIN mesas ON mesas.id_mesa = pedidos.id_mesa
    WHERE pedidos.id_pedido = ?
    LIMIT 1";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {
    die("No se pudo consultar el ticket.");
}

mysqli_stmt_bind_param($stmt, "i", $id_pedido);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$pedido = mysqli_fetch_assoc($resultado);
mysqli_stmt_close($stmt);

if (!$pedido) {
    die("Pedido no encontrado.");
}

$total_final = caja_total_pedido($pedido);
$puntos_obtenidos = !empty($pedido["id_cliente"]) ? (int) floor($total_final / PUNTOS_POR_PESOS) : 0;
$fecha_ticket = $pedido["fecha_hora_cobro"] ?: ($pedido["fecha_hora_entrega"] ?: $pedido["fecha_hora_inicio"]);
$mesa = caja_mesa_texto($pedido);
$pagos = caja_resumen_pagos_pedido($conexion, $id_pedido);
$saldo_pendiente = max(0, $total_final - $pagos["total_pagado"]);
$horario_entrega = columna_existe($conexion, "pedidos", "horario_entrega") ? ($pedido["horario_entrega"] ?? null) : null;

$extra_css = ["/genesisbar1/css/caja.css?v=3"];
require_once("../includes/header.php");
?>

<section class="caja-page">
    <div class="caja-ticket-acciones">
        <a class="boton boton-secundario" href="/genesisbar1/caja/index.php">Volver a Caja</a>
        <button class="boton" type="button" onclick="window.print()">Imprimir</button>
    </div>

    <?php if (isset($_GET["cobrado"])) { ?>
        <div class="mensaje-pedido exito">Cobro registrado correctamente.</div>
    <?php } ?>

    <article class="caja-ticket">
        <h1>GENESIS BAR</h1>
        <h2>Ticket</h2>

        <hr>

        <p><b>Pedido:</b> <?= htmlspecialchars(caja_numero_pedido($pedido)); ?></p>
        <p><b>Fecha:</b> <?= htmlspecialchars(date("d/m/Y H:i", strtotime($fecha_ticket))); ?></p>
        <p><b>Tipo:</b> <?= htmlspecialchars($pedido["tipo_pedido"]); ?></p>
        <p><b>Cajero:</b> <?= htmlspecialchars($pedido["cajero"] ?: "-"); ?></p>

        <?php if ($mesa !== "") { ?>
        <p><b>Mesa:</b> <?= htmlspecialchars($mesa); ?></p>
        <?php } ?>

        <?php if (!empty($pedido["nombre_cliente"])) { ?>
        <p><b>Cliente:</b> <?= htmlspecialchars($pedido["nombre_cliente"]); ?></p>
        <?php } ?>

        <?php if (!empty($horario_entrega)) { ?>
        <p><b>Horario entrega:</b> <?= htmlspecialchars(date("d/m/Y H:i", strtotime($horario_entrega))); ?></p>
        <?php } ?>

        <hr>

        <table class="tabla-caja">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cant.</th>
                    <th>Precio</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
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
                <tr>
                    <td><?= htmlspecialchars($producto["nombre"]); ?></td>
                    <td><?= (int) $producto["cantidad"]; ?></td>
                    <td><?= htmlspecialchars(caja_moneda($producto["precio_unitario"])); ?></td>
                    <td><?= htmlspecialchars(caja_moneda($producto["subtotal"])); ?></td>
                </tr>
                <?php } ?>
                <?php mysqli_stmt_close($stmt_detalle); ?>
            </tbody>
        </table>

        <hr>

        <p><b>Total:</b> <?= htmlspecialchars(caja_moneda($total_final)); ?></p>
        <?php foreach ($pagos["formas"] as $forma_pago) { ?>
        <p><b><?= htmlspecialchars($forma_pago["nombre"]); ?>:</b> <?= htmlspecialchars(caja_moneda($forma_pago["monto"])); ?></p>
        <?php } ?>
        <p><b>Total pagado:</b> <?= htmlspecialchars(caja_moneda($pagos["total_pagado"])); ?></p>
        <p><b>Saldo pendiente:</b> <?= htmlspecialchars(caja_moneda($saldo_pendiente)); ?></p>
        <p><b>Estado de pago:</b> <?= htmlspecialchars($pedido["estado_pago"] ?: "-"); ?></p>

        <?php if ($pagos["dinero_recibido"] !== null) { ?>
        <p><b>Recibido efectivo:</b> <?= htmlspecialchars(caja_moneda($pagos["dinero_recibido"])); ?></p>
        <p><b>Vuelto:</b> <?= htmlspecialchars(caja_moneda($pagos["vuelto"] ?? 0)); ?></p>
        <?php } ?>

        <?php if (!empty($pedido["id_cliente"])) { ?>
        <hr>
        <p><b>Puntos obtenidos:</b> <?= $puntos_obtenidos; ?></p>
        <p><b>Puntos acumulados:</b> <?= (int) $pedido["puntos_acumulados"]; ?></p>
        <?php } ?>

        <hr>

        <p>Gracias por elegir Genesis Bar.</p>
    </article>
</section>

<?php
require_once("../includes/footer.php");
?>
