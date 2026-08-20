<?php
require_once("../config/config.php");
require_once("funciones.php");
require_once("../php/seguridad.php");
requerir_permiso($conexion, "caja");

$id_pedido = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
$csrf = token_csrf();

if ($id_pedido < 1) {
    die("Pedido inexistente.");
}

$sql = "SELECT
        pedidos.*,
        clientes.nombre AS nombre_cliente,
        clientes.telefono AS telefono_cliente,
        mesas.numero AS numero_mesa
    FROM pedidos
    LEFT JOIN clientes ON clientes.id_cliente = pedidos.id_cliente
    LEFT JOIN mesas ON mesas.id_mesa = pedidos.id_mesa
    WHERE pedidos.id_pedido = ?
    LIMIT 1";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {
    die("No se pudo consultar el pedido.");
}

mysqli_stmt_bind_param($stmt, "i", $id_pedido);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$pedido = mysqli_fetch_assoc($resultado);
mysqli_stmt_close($stmt);

if (!$pedido) {
    die("Pedido no encontrado.");
}

if ($pedido["estado"] === "Cobrado" || ($pedido["estado_pago"] ?? "Pendiente") === "Pagado") {
    header("Location: ticket.php?id=" . $id_pedido);
    exit;
}

$extra_css = ["/genesisbar1/css/caja.css?v=3"];
$extra_js = ["/genesisbar1/caja/js/caja.js?v=3"];

if (!caja_pedido_cobrable($pedido)) {
    require_once("../includes/header.php");
?>
<section class="caja-page">
    <div class="caja-header">
        <div>
            <h2>Pedido no disponible para cobrar</h2>
            <p><?= htmlspecialchars(caja_numero_pedido($pedido)); ?></p>
        </div>
        <a class="boton boton-secundario" href="/genesisbar1/caja/index.php">Volver a Caja</a>
    </div>

    <div class="mensaje-pedido error">Este pedido no esta listo para cobrar o ya tiene el pago cerrado.</div>
</section>
<?php
    require_once("../includes/footer.php");
    exit;
}

$total = caja_total_pedido($pedido);
$mesa = caja_mesa_texto($pedido);
$cliente = caja_cliente_texto($pedido);
$horario_entrega = columna_existe($conexion, "pedidos", "horario_entrega") ? ($pedido["horario_entrega"] ?? null) : null;
$tipo_reserva = columna_existe($conexion, "pedidos", "tipo_reserva") ? ($pedido["tipo_reserva"] ?? "Ninguna") : "Ninguna";
$formas_pago = [];
$resultado_formas = mysqli_query($conexion, "SELECT id_forma_pago, nombre FROM formas_pago ORDER BY nombre ASC");

if ($resultado_formas) {
    while ($forma = mysqli_fetch_assoc($resultado_formas)) {
        $formas_pago[] = $forma;
    }
}

require_once("../includes/header.php");
?>

<section class="caja-page">
    <div class="caja-header">
        <div>
            <h2>Cobrar Pedido</h2>
            <p><?= htmlspecialchars(caja_numero_pedido($pedido)); ?></p>
        </div>
        <a class="boton boton-secundario" href="/genesisbar1/caja/index.php">Volver a Caja</a>
    </div>

    <div class="caja-layout">
        <div class="caja-panel">
            <h3>Detalle del pedido</h3>

            <dl class="caja-datos">
                <div>
                    <dt>Tipo</dt>
                    <dd><?= htmlspecialchars($pedido["tipo_pedido"]); ?></dd>
                </div>
                <?php if ($tipo_reserva !== "Ninguna") { ?>
                <div>
                    <dt>Reserva</dt>
                    <dd><?= htmlspecialchars($tipo_reserva === "Mesa" ? "Reserva de mesa" : "Pedido programado"); ?></dd>
                </div>
                <?php } ?>
                <?php if ($mesa !== "") { ?>
                <div>
                    <dt>Mesa</dt>
                    <dd><?= htmlspecialchars($mesa); ?></dd>
                </div>
                <?php } ?>
                <?php if ($cliente !== "") { ?>
                <div>
                    <dt>Cliente</dt>
                    <dd><?= htmlspecialchars($cliente); ?></dd>
                </div>
                <?php } ?>
                <div>
                    <dt>Estado</dt>
                    <dd><?= htmlspecialchars($pedido["estado"]); ?></dd>
                </div>
                <?php if (!empty($horario_entrega)) { ?>
                <div>
                    <dt>Entrega</dt>
                    <dd><?= htmlspecialchars(date("d/m/Y H:i", strtotime($horario_entrega))); ?></dd>
                </div>
                <?php } ?>
            </dl>

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
                    <?php while ($item = mysqli_fetch_assoc($detalle)) { ?>
                    <tr>
                        <td><?= htmlspecialchars($item["nombre"]); ?></td>
                        <td><?= (int) $item["cantidad"]; ?></td>
                        <td><?= htmlspecialchars(caja_moneda($item["precio_unitario"])); ?></td>
                        <td><?= htmlspecialchars(caja_moneda($item["subtotal"])); ?></td>
                    </tr>
                    <?php } ?>
                    <?php mysqli_stmt_close($stmt_detalle); ?>
                </tbody>
            </table>
        </div>

        <aside class="caja-panel">
            <h3>Confirmar cobro</h3>

            <div class="caja-total">
                <span>Total</span>
                <strong><?= htmlspecialchars(caja_moneda($total)); ?></strong>
            </div>

            <form id="formCobro" class="caja-form" action="confirmar.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf); ?>">
                <input type="hidden" name="id_pedido" value="<?= (int) $pedido["id_pedido"]; ?>">
                <input type="hidden" id="totalPedido" value="<?= htmlspecialchars(number_format($total, 2, ".", "")); ?>">

                <?php if (count($formas_pago) === 0) { ?>
                    <div class="mensaje-pedido error">Falta configurar formas de pago.</div>
                <?php } ?>

                <div class="caja-pagos-mixtos">
                    <label for="id_forma_pago_1">Forma de pago</label>
                    <select id="id_forma_pago_1" name="id_forma_pago_1" required>
                        <option value="">Seleccionar</option>
                        <?php foreach ($formas_pago as $forma) { ?>
                            <option value="<?= (int) $forma["id_forma_pago"]; ?>" data-efectivo="<?= caja_forma_pago_es_efectivo_nombre($forma["nombre"]) ? "1" : "0"; ?>">
                                <?= htmlspecialchars($forma["nombre"]); ?>
                            </option>
                        <?php } ?>
                    </select>

                    <input id="monto_pago_1" name="monto_pago_1" type="hidden" value="<?= htmlspecialchars(number_format($total, 2, ".", "")); ?>">

                    <button class="boton boton-secundario" type="button" id="habilitarPagoDividido">Agregar segunda forma</button>

                    <div id="grupoPago2" class="caja-pago-secundario" hidden>
                        <label for="id_forma_pago_2">Segunda forma de pago</label>
                        <select id="id_forma_pago_2" name="id_forma_pago_2">
                            <option value="">Seleccionar</option>
                            <?php foreach ($formas_pago as $forma) { ?>
                                <option value="<?= (int) $forma["id_forma_pago"]; ?>" data-efectivo="<?= caja_forma_pago_es_efectivo_nombre($forma["nombre"]) ? "1" : "0"; ?>">
                                    <?= htmlspecialchars($forma["nombre"]); ?>
                                </option>
                            <?php } ?>
                        </select>

                        <label for="monto_pago_2">Importe segunda forma</label>
                        <input id="monto_pago_2" name="monto_pago_2" type="number" min="0" step="0.01" value="0">
                    </div>

                    <div id="grupoEfectivo" hidden>
                        <label for="dinero_recibido">Dinero recibido en efectivo</label>
                        <input id="dinero_recibido" name="dinero_recibido" type="number" min="0" step="0.01" value="0">
                    </div>
                </div>

                <div class="caja-resumen-pago">
                    <div><span>Total del pedido</span><strong id="resumenTotal"><?= htmlspecialchars(caja_moneda($total)); ?></strong></div>
                    <div><span>Pago principal</span><strong id="resumenPago1">$0</strong></div>
                    <div><span>Segundo pago</span><strong id="resumenPago2">$0</strong></div>
                    <div><span>Total pagado</span><strong id="resumenPagado">$0</strong></div>
                    <div><span>Saldo pendiente</span><strong id="resumenSaldo"><?= htmlspecialchars(caja_moneda($total)); ?></strong></div>
                    <div><span>Vuelto</span><strong id="vuelto">$0</strong></div>
                </div>

                <button type="submit" class="boton" <?= count($formas_pago) === 0 ? "disabled" : ""; ?>>Confirmar cobro</button>
            </form>
        </aside>
    </div>
</section>

<?php
require_once("../includes/footer.php");
?>
