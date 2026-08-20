<?php
require_once("../config/config.php");
require_once("../php/seguridad.php");
require_once("../php/pedidos_estados.php");

$empleado = empleado_actual($conexion);
$puede_gestionar_pedidos = empleado_tiene_permiso($empleado, "pedidos");
$puede_ver_cocina = empleado_tiene_permiso($empleado, "cocina");
$puede_ver_caja = empleado_tiene_permiso($empleado, "caja");

if (!$empleado) {
    header("Location: " . genesis_url("empleados/login.php"));
    exit;
}

if (!$puede_gestionar_pedidos && !$puede_ver_cocina && !$puede_ver_caja) {
    header("Location: " . genesis_url("empleados/acceso-denegado.php"));
    exit;
}

$solo_cocina = !$puede_gestionar_pedidos && $puede_ver_cocina;
$solo_caja = !$puede_gestionar_pedidos && $puede_ver_caja;
$url_volver = $solo_cocina
    ? genesis_url("cocina/index.php")
    : ($solo_caja ? genesis_url("caja/index.php") : genesis_url("pedidos/index.php"));

$extra_css = ["/genesisbar1/css/pedidos.css?v=7"];
$extra_js = ["/genesisbar1/pedidos/ver.js?v=1"];
require_once("../includes/header.php");
$csrf = token_csrf();

$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($id < 1) {
?>
<section class="pedido-detalle-page">
    <h2>Pedido no encontrado</h2>
    <p>El identificador recibido no es valido.</p>
    <a class="boton" href="<?= htmlspecialchars($url_volver); ?>">Volver</a>
</section>
<?php
    require_once("../includes/footer.php");
    exit;
}

$sql = "SELECT
        pedidos.*,
        clientes.nombre AS nombre_cliente,
        clientes.telefono AS telefono_cliente,
        formas_pago.nombre AS forma_pago,
        mesas.numero AS numero_mesa
    FROM pedidos
    LEFT JOIN clientes ON clientes.id_cliente = pedidos.id_cliente
    LEFT JOIN formas_pago ON formas_pago.id_forma_pago = pedidos.id_forma_pago
    LEFT JOIN mesas ON mesas.id_mesa = pedidos.id_mesa
    WHERE pedidos.id_pedido = ?
    LIMIT 1";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {
    die("No se pudo consultar el pedido.");
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$pedido = mysqli_fetch_assoc($resultado);
mysqli_stmt_close($stmt);

if (!$pedido) {
?>
<section class="pedido-detalle-page">
    <h2>Pedido inexistente</h2>
    <p>No existe un pedido con el identificador #<?= htmlspecialchars((string) $id); ?>.</p>
    <a class="boton" href="<?= htmlspecialchars($url_volver); ?>">Volver</a>
</section>
<?php
    require_once("../includes/footer.php");
    exit;
}

$mesa = trim((string) ($pedido["numero_mesa"] ?? "")) !== "" ? $pedido["numero_mesa"] : ($pedido["mesa"] ?? "");
$cliente = trim((string) ($pedido["nombre_cliente"] ?? "")) !== "" ? $pedido["nombre_cliente"] : "Cliente no identificado";
$direccion_entrega = direccion_compuesta($pedido["direccion_calle"] ?? "", $pedido["direccion_altura"] ?? "", $pedido["direccion_entrega"] ?? "");
$usa_horario_entrega = columna_existe($conexion, "pedidos", "horario_entrega");
$horario_entrega = $usa_horario_entrega ? ($pedido["horario_entrega"] ?? null) : null;
$horario_entrega_texto = $horario_entrega ? date("d/m/Y H:i", strtotime($horario_entrega)) : "-";
$horario_entrega_input = $horario_entrega ? date("Y-m-d\TH:i", strtotime($horario_entrega)) : "";
$tipo_reserva = columna_existe($conexion, "pedidos", "tipo_reserva") ? ($pedido["tipo_reserva"] ?? "Ninguna") : "Ninguna";
$estados_permitidos = pedido_estados_permitidos_desde($pedido["estado"]);

if ($solo_cocina) {
    $estados_permitidos = [$pedido["estado"]];
} elseif ($solo_caja) {
    $estados_permitidos = [$pedido["estado"]];
}

$bloquea_cambios = pedido_estado_bloquea_cambios($pedido["estado"]);
$puede_cambiar_estado = !$bloquea_cambios && count($estados_permitidos) > 1;
?>

<section class="pedido-detalle-page">
    <div class="pedidos-cabecera">
        <div>
            <h2><?= htmlspecialchars($pedido["numero_pedido"] ?: "#" . $pedido["id_pedido"]); ?></h2>
            <p>Detalle y estado del pedido.</p>
        </div>
        <a href="<?= htmlspecialchars($url_volver); ?>" class="boton boton-secundario">Volver</a>
    </div>

    <div id="mensajeVerPedido" class="mensaje-pedido" role="status" aria-live="polite"></div>

    <div class="pedido-detalle-layout">
        <div class="panel-pedido">
            <h3>Productos</h3>
            <table class="tabla-detalle-pedido">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio</th>
                        <th>Subtotal</th>
                        <th>Obs.</th>
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
                    mysqli_stmt_bind_param($stmt_detalle, "i", $id);
                    mysqli_stmt_execute($stmt_detalle);
                    $detalle = mysqli_stmt_get_result($stmt_detalle);
                    ?>
                    <?php while ($fila = mysqli_fetch_assoc($detalle)) { ?>
                    <tr>
                        <td><?= htmlspecialchars($fila["nombre"]); ?></td>
                        <td><?= (int) $fila["cantidad"]; ?></td>
                        <td>$<?= number_format((float) $fila["precio_unitario"], 0, ",", "."); ?></td>
                        <td>$<?= number_format((float) $fila["subtotal"], 0, ",", "."); ?></td>
                        <td><?= htmlspecialchars(trim((string) ($fila["observaciones"] ?? "")) !== "" ? $fila["observaciones"] : "-"); ?></td>
                    </tr>
                    <?php } ?>
                    <?php mysqli_stmt_close($stmt_detalle); ?>
                </tbody>
            </table>

            <?php if (trim((string) $pedido["observaciones"]) !== "") { ?>
            <h3>Observaciones</h3>
            <div class="observaciones"><?= nl2br(htmlspecialchars($pedido["observaciones"])); ?></div>
            <?php } ?>
        </div>

        <aside class="panel-pedido">
            <h3>Informacion</h3>
            <dl class="pedido-datos-grid">
                <div><dt>Cliente</dt><dd><?= htmlspecialchars($cliente); ?></dd></div>
                <div><dt>Telefono</dt><dd><?= htmlspecialchars($pedido["telefono_cliente"] ?: "-"); ?></dd></div>
                <div><dt>Tipo</dt><dd><?= htmlspecialchars($pedido["tipo_pedido"]); ?></dd></div>
                <?php if ($tipo_reserva !== "Ninguna") { ?>
                <div><dt>Reserva</dt><dd><?= htmlspecialchars($tipo_reserva === "Mesa" ? "Reserva de mesa" : "Pedido programado"); ?></dd></div>
                <?php } ?>
                <div><dt>Mesa/destino</dt><dd><?= htmlspecialchars($mesa ?: ($direccion_entrega ?: "-")); ?></dd></div>
                <div><dt>Origen</dt><dd><?= htmlspecialchars($pedido["origen"] ?: "-"); ?></dd></div>
                <div><dt>Forma de pago</dt><dd><?= htmlspecialchars($pedido["forma_pago"] ?: "-"); ?></dd></div>
                <div><dt>Hora</dt><dd><?= htmlspecialchars(date("d/m/Y H:i", strtotime($pedido["fecha_hora_inicio"]))); ?></dd></div>
                <?php if ($usa_horario_entrega) { ?>
                <div><dt>Horario entrega</dt><dd><?= htmlspecialchars($horario_entrega_texto); ?></dd></div>
                <?php } ?>
                <div><dt>Tiempo</dt><dd><?= htmlspecialchars(pedido_minutos_transcurridos($pedido["fecha_hora_inicio"])); ?> min</dd></div>
                <div><dt>Estado</dt><dd><span id="estadoActualPedido" class="estado"><?= htmlspecialchars(pedido_estado_etiqueta($pedido["estado"])); ?></span></dd></div>
                <div><dt>Pago</dt><dd><?= htmlspecialchars($pedido["estado_pago"] ?? "Pendiente"); ?></dd></div>
                <div><dt>Total</dt><dd>$<?= number_format((float) $pedido["total"], 0, ",", "."); ?></dd></div>
            </dl>

            <?php if ($puede_gestionar_pedidos && $usa_horario_entrega && !pedido_estado_bloquea_cambios($pedido["estado"])) { ?>
                <h3>Horario de entrega</h3>
                <form class="form-horario-entrega" action="actualizar_horario.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf); ?>">
                    <input type="hidden" name="id_pedido" value="<?= (int) $pedido["id_pedido"]; ?>">
                    <input type="datetime-local" name="horario_entrega" value="<?= htmlspecialchars($horario_entrega_input); ?>">
                    <button class="boton" type="submit">Guardar horario</button>
                </form>
            <?php } ?>

            <?php if ($puede_cambiar_estado) { ?>
                <h3>Cambiar estado</h3>
                <div class="acciones-estado" data-id-pedido="<?= (int) $pedido["id_pedido"]; ?>">
                    <input type="hidden" id="csrfPedidoDetalle" value="<?= htmlspecialchars($csrf); ?>">
                    <?php foreach ($estados_permitidos as $estado) { ?>
                    <button type="button" data-estado="<?= htmlspecialchars($estado); ?>" <?= $estado === $pedido["estado"] ? "disabled" : ""; ?>>
                        <?= htmlspecialchars(pedido_estado_etiqueta($estado)); ?>
                    </button>
                    <?php } ?>
                </div>
            <?php } elseif ($bloquea_cambios) { ?>
                <p class="ayuda-estado">Este pedido ya no admite cambios operativos.</p>
            <?php } ?>
        </aside>
    </div>
</section>

<?php
require_once("../includes/footer.php");
?>
