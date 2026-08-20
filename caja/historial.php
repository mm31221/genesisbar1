<?php
require_once("../config/config.php");
require_once("funciones.php");
require_once("../php/seguridad.php");
requerir_permiso($conexion, "caja");

$vista = $_GET["vista"] ?? "hoy";
$fecha_desde = $_GET["fecha_desde"] ?? ($_GET["fecha"] ?? date("Y-m-d"));
$fecha_hasta = $_GET["fecha_hasta"] ?? $fecha_desde;
$id_forma_pago = isset($_GET["id_forma_pago"]) ? (int) $_GET["id_forma_pago"] : 0;
$id_usuario = isset($_GET["id_usuario"]) ? (int) $_GET["id_usuario"] : 0;

if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $fecha_desde)) {
    $fecha_desde = date("Y-m-d");
}

if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $fecha_hasta)) {
    $fecha_hasta = $fecha_desde;
}

if ($fecha_hasta < $fecha_desde) {
    $fecha_hasta = $fecha_desde;
}

if ($vista !== "general") {
    $vista = "hoy";
}

$inicio = $fecha_desde . " 00:00:00";
$fin = $fecha_hasta . " 23:59:59";
$where_cobrados = caja_where_pedidos_cobrados();
$condiciones = ["$where_cobrados"];
$tipos = "";
$params = [];

if ($vista === "hoy") {
    $condiciones[] = "pedidos.fecha_hora_cobro BETWEEN ? AND ?";
    $tipos .= "ss";
    $params[] = date("Y-m-d") . " 00:00:00";
    $params[] = date("Y-m-d") . " 23:59:59";
} elseif (isset($_GET["fecha_desde"]) || isset($_GET["fecha_hasta"])) {
    $condiciones[] = "pedidos.fecha_hora_cobro BETWEEN ? AND ?";
    $tipos .= "ss";
    $params[] = $inicio;
    $params[] = $fin;
}

if ($id_forma_pago > 0) {
    $condiciones[] = "EXISTS (
        SELECT 1
        FROM movimientos_caja filtro_mov
        WHERE filtro_mov.id_pedido = pedidos.id_pedido
          AND filtro_mov.tipo = 'Ingreso'
          AND filtro_mov.id_forma_pago = ?
    )";
    $tipos .= "i";
    $params[] = $id_forma_pago;
}

if ($id_usuario > 0) {
    $condiciones[] = "pedidos.id_usuario_cobro = ?";
    $tipos .= "i";
    $params[] = $id_usuario;
}

$where = implode(" AND ", $condiciones);

$sql = "SELECT
        pedidos.id_pedido,
        pedidos.numero_pedido,
        pedidos.tipo_pedido,
        pedidos.mesa,
        pedidos.estado_pago,
        pedidos.total,
        pedidos.total_final,
        pedidos.fecha_hora_cobro,
        clientes.nombre AS nombre_cliente,
        clientes.telefono AS telefono_cliente,
        formas_pago.nombre AS forma_pago,
        usuarios.nombre AS cajero,
        mesas.numero AS numero_mesa
    FROM pedidos
    LEFT JOIN clientes ON clientes.id_cliente = pedidos.id_cliente
    LEFT JOIN formas_pago ON formas_pago.id_forma_pago = pedidos.id_forma_pago
    LEFT JOIN usuarios ON usuarios.id_usuario = pedidos.id_usuario_cobro
    LEFT JOIN mesas ON mesas.id_mesa = pedidos.id_mesa
    WHERE $where
    ORDER BY pedidos.fecha_hora_cobro DESC, pedidos.id_pedido DESC";

$stmt = mysqli_prepare($conexion, $sql);
if ($tipos !== "") {
    caja_bind_parametros($stmt, $tipos, $params);
}
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

$pedidos = [];
$total_ventas = 0;

while ($pedido = mysqli_fetch_assoc($resultado)) {
    $pagos_pedido = caja_resumen_pagos_pedido($conexion, (int) $pedido["id_pedido"]);
    $importe = $pagos_pedido["total_pagado"] > 0 ? $pagos_pedido["total_pagado"] : caja_total_pedido($pedido);
    $pedido["importe_cobrado"] = $importe;
    $pedido["pago_efectivo"] = $pagos_pedido["efectivo"];
    $pedido["pago_mercado_pago"] = $pagos_pedido["mercado_pago"];
    $formas_usadas = array_map(function ($forma) {
        return $forma["nombre"];
    }, $pagos_pedido["formas"]);

    if (count($formas_usadas) === 0) {
        $formas_usadas[] = $pedido["forma_pago"] ?: "-";
    }

    $pedido["formas_pago_resumen"] = implode(" + ", $formas_usadas);
    $pedidos[] = $pedido;
    $total_ventas += $importe;
}

mysqli_stmt_close($stmt);

$cantidad_pedidos = count($pedidos);
$ticket_promedio = $cantidad_pedidos > 0 ? $total_ventas / $cantidad_pedidos : 0;

$formas_pago = mysqli_query($conexion, "SELECT id_forma_pago, nombre FROM formas_pago ORDER BY nombre ASC");
$cajeros = mysqli_query($conexion, "SELECT id_usuario, nombre FROM usuarios WHERE rol IN ('Administrador', 'Cajero') AND activo = 1 ORDER BY nombre ASC");

$sql_formas = "SELECT
        formas_pago.nombre,
        COUNT(movimientos_caja.id_movimiento) AS operaciones,
        COALESCE(SUM(movimientos_caja.monto), 0) AS total
    FROM pedidos
    INNER JOIN movimientos_caja ON movimientos_caja.id_pedido = pedidos.id_pedido AND movimientos_caja.tipo = 'Ingreso'
    INNER JOIN formas_pago ON formas_pago.id_forma_pago = movimientos_caja.id_forma_pago
    WHERE $where
    GROUP BY formas_pago.id_forma_pago, formas_pago.nombre
    ORDER BY total DESC";
$stmt_formas = mysqli_prepare($conexion, $sql_formas);
if ($tipos !== "") {
    caja_bind_parametros($stmt_formas, $tipos, $params);
}
mysqli_stmt_execute($stmt_formas);
$formas = mysqli_stmt_get_result($stmt_formas);

$extra_css = ["/genesisbar1/css/caja.css?v=3"];
require_once("../includes/header.php");
?>

<section class="caja-page">
    <div class="caja-header">
        <div>
            <h2>Historial de Caja</h2>
            <p><?= $vista === "hoy" ? "Ventas cobradas durante el dia actual." : "Historial general de ventas cobradas."; ?></p>
        </div>
        <a class="boton boton-secundario" href="/genesisbar1/caja/index.php">Volver a Caja</a>
    </div>

    <form class="caja-filtros" method="get">
        <a class="boton <?= $vista === "hoy" ? "" : "boton-secundario"; ?>" href="/genesisbar1/caja/historial.php?vista=hoy">Ventas del dia</a>
        <a class="boton <?= $vista === "general" ? "" : "boton-secundario"; ?>" href="/genesisbar1/caja/historial.php?vista=general">Historial general</a>
        <input type="hidden" name="vista" value="general">
        <label>
            Desde
            <input type="date" name="fecha_desde" value="<?= htmlspecialchars($fecha_desde); ?>">
        </label>
        <label>
            Hasta
            <input type="date" name="fecha_hasta" value="<?= htmlspecialchars($fecha_hasta); ?>">
        </label>
        <label>
            Forma de pago
            <select name="id_forma_pago">
                <option value="0">Todas</option>
                <?php while ($forma_filtro = mysqli_fetch_assoc($formas_pago)) { ?>
                    <option value="<?= (int) $forma_filtro["id_forma_pago"]; ?>" <?= $id_forma_pago === (int) $forma_filtro["id_forma_pago"] ? "selected" : ""; ?>>
                        <?= htmlspecialchars($forma_filtro["nombre"]); ?>
                    </option>
                <?php } ?>
            </select>
        </label>
        <label>
            Cajero
            <select name="id_usuario">
                <option value="0">Todos</option>
                <?php while ($cajero_filtro = mysqli_fetch_assoc($cajeros)) { ?>
                    <option value="<?= (int) $cajero_filtro["id_usuario"]; ?>" <?= $id_usuario === (int) $cajero_filtro["id_usuario"] ? "selected" : ""; ?>>
                        <?= htmlspecialchars($cajero_filtro["nombre"]); ?>
                    </option>
                <?php } ?>
            </select>
        </label>
        <button class="boton" type="submit">Filtrar</button>
        <a class="boton boton-secundario" href="/genesisbar1/caja/historial.php?vista=hoy">Hoy</a>
    </form>

    <div class="caja-metricas">
        <div class="caja-metrica">
            <span>Ventas totales</span>
            <strong><?= htmlspecialchars(caja_moneda($total_ventas)); ?></strong>
        </div>
        <div class="caja-metrica">
            <span>Cantidad de pedidos</span>
            <strong><?= (int) $cantidad_pedidos; ?></strong>
        </div>
        <div class="caja-metrica">
            <span>Ticket promedio</span>
            <strong><?= htmlspecialchars(caja_moneda($ticket_promedio)); ?></strong>
        </div>
    </div>

    <div class="caja-layout">
        <div class="caja-panel">
            <h3>Ventas</h3>

            <?php if (count($pedidos) === 0) { ?>
                <p>No hay ventas cobradas para esta fecha.</p>
            <?php } else { ?>
            <table class="tabla-caja">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Hora</th>
                        <th>Tipo</th>
                        <th>Mesa/cliente</th>
                        <th>Forma</th>
                        <th>Cajero</th>
                        <th>Total</th>
                        <th>Ticket</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $pedido) { ?>
                    <?php
                    $mesa_cliente = caja_mesa_texto($pedido);
                    if ($mesa_cliente === "") {
                        $mesa_cliente = caja_cliente_texto($pedido);
                    }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars(caja_numero_pedido($pedido)); ?></td>
                        <td><?= htmlspecialchars(caja_hora($pedido["fecha_hora_cobro"])); ?></td>
                        <td><?= htmlspecialchars($pedido["tipo_pedido"]); ?></td>
                        <td><?= htmlspecialchars($mesa_cliente !== "" ? $mesa_cliente : "-"); ?></td>
                        <td><?= htmlspecialchars($pedido["formas_pago_resumen"]); ?></td>
                        <td><?= htmlspecialchars($pedido["cajero"] ?: "-"); ?></td>
                        <td><?= htmlspecialchars(caja_moneda($pedido["importe_cobrado"])); ?></td>
                        <td><a href="/genesisbar1/caja/ticket.php?id=<?= (int) $pedido["id_pedido"]; ?>">Ver</a></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
            <?php } ?>
        </div>

        <aside class="caja-panel">
            <h3>Por forma de pago</h3>
            <table class="tabla-caja">
                <thead>
                    <tr>
                        <th>Forma</th>
                        <th>Ops.</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($forma = mysqli_fetch_assoc($formas)) { ?>
                    <tr>
                        <td><?= htmlspecialchars($forma["nombre"]); ?></td>
                        <td><?= (int) $forma["operaciones"]; ?></td>
                        <td><?= htmlspecialchars(caja_moneda($forma["total"])); ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </aside>
    </div>
</section>

<?php
mysqli_stmt_close($stmt_formas);
require_once("../includes/footer.php");
?>
