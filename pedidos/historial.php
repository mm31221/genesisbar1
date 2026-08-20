<?php
date_default_timezone_set("America/Argentina/Buenos_Aires");

require_once("../config/config.php");
require_once("../php/seguridad.php");
requerir_permiso($conexion, "pedidos");

$vista = $_GET["vista"] ?? "hoy";
$fecha_desde = $_GET["fecha_desde"] ?? date("Y-m-d");
$fecha_hasta = $_GET["fecha_hasta"] ?? $fecha_desde;
$estado = trim($_GET["estado"] ?? "");

if ($vista !== "general") {
    $vista = "hoy";
}

if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $fecha_desde)) {
    $fecha_desde = date("Y-m-d");
}

if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $fecha_hasta)) {
    $fecha_hasta = $fecha_desde;
}

if ($fecha_hasta < $fecha_desde) {
    $fecha_hasta = $fecha_desde;
}

$estados = ["", "Pendiente", "Preparando", "Listo", "Entregado", "Cobrado", "Cancelado"];

if (!in_array($estado, $estados, true)) {
    $estado = "";
}

$condiciones = [];
$tipos = "";
$params = [];

if ($vista === "hoy") {
    $condiciones[] = "pedidos.fecha_hora_inicio BETWEEN ? AND ?";
    $tipos .= "ss";
    $params[] = date("Y-m-d") . " 00:00:00";
    $params[] = date("Y-m-d") . " 23:59:59";
} elseif (isset($_GET["fecha_desde"]) || isset($_GET["fecha_hasta"])) {
    $condiciones[] = "pedidos.fecha_hora_inicio BETWEEN ? AND ?";
    $tipos .= "ss";
    $params[] = $fecha_desde . " 00:00:00";
    $params[] = $fecha_hasta . " 23:59:59";
}

if ($estado !== "") {
    $condiciones[] = "pedidos.estado = ?";
    $tipos .= "s";
    $params[] = $estado;
}

$where = count($condiciones) > 0 ? "WHERE " . implode(" AND ", $condiciones) : "";
$campo_calle = columna_existe($conexion, "pedidos", "direccion_calle") ? "pedidos.direccion_calle" : "NULL";
$campo_altura = columna_existe($conexion, "pedidos", "direccion_altura") ? "pedidos.direccion_altura" : "NULL";

$sql = "SELECT
        pedidos.id_pedido,
        pedidos.numero_pedido,
        pedidos.tipo_pedido,
        pedidos.mesa,
        pedidos.direccion_entrega,
        $campo_calle AS direccion_calle,
        $campo_altura AS direccion_altura,
        pedidos.estado,
        pedidos.estado_pago,
        pedidos.total,
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
    $where
    ORDER BY pedidos.fecha_hora_inicio DESC, pedidos.id_pedido DESC
    LIMIT 200";

$stmt = mysqli_prepare($conexion, $sql);

if ($stmt && $tipos !== "") {
    $refs = [$tipos];

    foreach ($params as $i => &$param) {
        $refs[] = &$param;
    }

    call_user_func_array([$stmt, "bind_param"], $refs);
}

if ($stmt) {
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
} else {
    $resultado = false;
}

$pedidos = [];
$total = 0;

if ($resultado) {
    while ($pedido = mysqli_fetch_assoc($resultado)) {
        $importe = (float) $pedido["total_final"] > 0 ? (float) $pedido["total_final"] : (float) $pedido["total"];
        $pedido["importe"] = $importe;
        $pedidos[] = $pedido;
        $total += $importe;
    }
}

if ($stmt) {
    mysqli_stmt_close($stmt);
}

function pedidos_historial_moneda($importe)
{
    return "$" . number_format((float) $importe, 0, ",", ".");
}

function pedidos_historial_destino($pedido)
{
    if (($pedido["tipo_pedido"] ?? "") === "Mesa") {
        $mesa = trim((string) ($pedido["numero_mesa"] ?? ""));

        if ($mesa === "") {
            $mesa = trim((string) ($pedido["mesa"] ?? ""));
        }

        return $mesa !== "" ? "Mesa " . $mesa : "Mesa";
    }

    if (($pedido["tipo_pedido"] ?? "") === "Delivery") {
        return direccion_compuesta($pedido["direccion_calle"] ?? "", $pedido["direccion_altura"] ?? "", $pedido["direccion_entrega"] ?? "");
    }

    $cliente = trim((string) ($pedido["nombre_cliente"] ?? ""));
    $telefono = trim((string) ($pedido["telefono_cliente"] ?? ""));
    return trim($cliente . ($cliente !== "" && $telefono !== "" ? " - " : "") . $telefono);
}

$extra_css = ["/genesisbar1/css/pedidos.css?v=8"];
require_once("../includes/header.php");
?>

<section class="pedido-detalle-page">
    <div class="pedidos-cabecera">
        <div>
            <h2>Historial de pedidos</h2>
            <p><?= $vista === "hoy" ? "Pedidos realizados durante el dia actual." : "Consulta general de pedidos."; ?></p>
        </div>
        <a class="boton boton-secundario" href="/genesisbar1/pedidos/index.php">Volver a pedidos</a>
    </div>

    <form class="pedidos-historial-filtros" method="get">
        <a class="boton <?= $vista === "hoy" ? "" : "boton-secundario"; ?>" href="/genesisbar1/pedidos/historial.php?vista=hoy">Pedidos de hoy</a>
        <a class="boton <?= $vista === "general" ? "" : "boton-secundario"; ?>" href="/genesisbar1/pedidos/historial.php?vista=general">Historial general</a>

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
            Estado
            <select name="estado">
                <option value="">Todos</option>
                <?php foreach (array_slice($estados, 1) as $estado_opcion) { ?>
                    <option value="<?= htmlspecialchars($estado_opcion); ?>" <?= $estado === $estado_opcion ? "selected" : ""; ?>>
                        <?= htmlspecialchars($estado_opcion); ?>
                    </option>
                <?php } ?>
            </select>
        </label>
        <button class="boton" type="submit">Filtrar</button>
    </form>

    <div class="pedidos-historial-resumen">
        <div><span>Pedidos</span><strong><?= count($pedidos); ?></strong></div>
        <div><span>Total registrado</span><strong><?= htmlspecialchars(pedidos_historial_moneda($total)); ?></strong></div>
    </div>

    <section class="panel-pedido">
        <?php if (count($pedidos) === 0) { ?>
            <p>No hay pedidos para esta vista.</p>
        <?php } else { ?>
            <table class="tabla-detalle-pedido pedidos-historial-tabla">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Destino</th>
                        <th>Estado</th>
                        <th>Pago</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $pedido) { ?>
                    <tr>
                        <td><a href="/genesisbar1/pedidos/ver.php?id=<?= (int) $pedido["id_pedido"]; ?>"><?= htmlspecialchars($pedido["numero_pedido"] ?: "#" . $pedido["id_pedido"]); ?></a></td>
                        <td><?= htmlspecialchars(date("d/m/Y H:i", strtotime($pedido["fecha_hora_inicio"]))); ?></td>
                        <td><?= htmlspecialchars($pedido["tipo_pedido"]); ?></td>
                        <td><?= htmlspecialchars(pedidos_historial_destino($pedido) ?: "-"); ?></td>
                        <td><?= htmlspecialchars($pedido["estado"]); ?></td>
                        <td><?= htmlspecialchars($pedido["estado_pago"] ?: "-"); ?></td>
                        <td><?= htmlspecialchars(pedidos_historial_moneda($pedido["importe"])); ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } ?>
    </section>
</section>

<?php require_once("../includes/footer.php"); ?>
