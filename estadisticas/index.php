<?php
require_once("../config/config.php");
require_once("../php/seguridad.php");
requerir_permiso($conexion, "estadisticas");

function estadisticas_moneda($importe)
{
    return "$" . number_format((float) $importe, 0, ",", ".");
}

function estadisticas_fecha_valida($fecha)
{
    return is_string($fecha) && preg_match("/^\d{4}-\d{2}-\d{2}$/", $fecha);
}

function estadisticas_fechas_periodo()
{
    $periodo = $_GET["periodo"] ?? "hoy";
    $hoy = date("Y-m-d");

    if ($periodo === "7dias") {
        return [$periodo, date("Y-m-d", strtotime("-6 days")), $hoy];
    }

    if ($periodo === "mes") {
        return [$periodo, date("Y-m-01"), $hoy];
    }

    if ($periodo === "personalizado") {
        $desde = $_GET["desde"] ?? $hoy;
        $hasta = $_GET["hasta"] ?? $hoy;

        if (!estadisticas_fecha_valida($desde)) {
            $desde = $hoy;
        }

        if (!estadisticas_fecha_valida($hasta)) {
            $hasta = $hoy;
        }

        if ($desde > $hasta) {
            $temporal = $desde;
            $desde = $hasta;
            $hasta = $temporal;
        }

        return [$periodo, $desde, $hasta];
    }

    return ["hoy", $hoy, $hoy];
}

function estadisticas_resumen($conexion, $inicio, $fin)
{
    $stmt = mysqli_prepare($conexion, "SELECT
            COUNT(*) AS cantidad,
            COALESCE(SUM(CASE WHEN total_final > 0 THEN total_final ELSE total END), 0) AS total
        FROM pedidos
        WHERE estado_pago = 'Pagado'
        AND fecha_hora_cobro BETWEEN ? AND ?");

    if (!$stmt) {
        return ["cantidad" => 0, "total" => 0, "promedio" => 0];
    }

    mysqli_stmt_bind_param($stmt, "ss", $inicio, $fin);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $fila = mysqli_fetch_assoc($resultado) ?: ["cantidad" => 0, "total" => 0];
    mysqli_stmt_close($stmt);

    $cantidad = (int) $fila["cantidad"];
    $total = (float) $fila["total"];

    return [
        "cantidad" => $cantidad,
        "total" => $total,
        "promedio" => $cantidad > 0 ? $total / $cantidad : 0
    ];
}

function estadisticas_query($conexion, $sql, $inicio, $fin)
{
    $stmt = mysqli_prepare($conexion, $sql);
    $filas = [];

    if (!$stmt) {
        return $filas;
    }

    mysqli_stmt_bind_param($stmt, "ss", $inicio, $fin);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    while ($fila = mysqli_fetch_assoc($resultado)) {
        $filas[] = $fila;
    }

    mysqli_stmt_close($stmt);

    return $filas;
}

function estadisticas_query_simple($conexion, $sql, $inicio, $fin)
{
    return estadisticas_query($conexion, $sql, $inicio, $fin);
}

function estadisticas_filas_por_dia($filas, $desde, $hasta)
{
    $por_fecha = [];

    foreach ($filas as $fila) {
        $por_fecha[$fila["fecha"]] = $fila;
    }

    $dias = [];
    $actual = strtotime($desde);
    $limite = strtotime($hasta);

    while ($actual <= $limite) {
        $fecha = date("Y-m-d", $actual);
        $fila = $por_fecha[$fecha] ?? ["fecha" => $fecha, "cantidad" => 0, "total" => 0];
        $dias[] = [
            "label" => date("d/m", $actual),
            "fecha" => $fecha,
            "cantidad" => (int) $fila["cantidad"],
            "total" => (float) $fila["total"]
        ];
        $actual = strtotime("+1 day", $actual);
    }

    return $dias;
}

function estadisticas_porcentaje_cambio($actual, $anterior)
{
    $actual = (float) $actual;
    $anterior = (float) $anterior;

    if ($anterior == 0) {
        return $actual > 0 ? 100 : 0;
    }

    return (($actual - $anterior) / $anterior) * 100;
}

[$periodo, $desde, $hasta] = estadisticas_fechas_periodo();

$inicio = $desde . " 00:00:00";
$fin = $hasta . " 23:59:59";
$dias_periodo = max(1, (int) floor((strtotime($hasta) - strtotime($desde)) / 86400) + 1);
$hasta_anterior = date("Y-m-d", strtotime($desde . " -1 day"));
$desde_anterior = date("Y-m-d", strtotime($hasta_anterior . " -" . ($dias_periodo - 1) . " days"));
$inicio_anterior = $desde_anterior . " 00:00:00";
$fin_anterior = $hasta_anterior . " 23:59:59";
$inicio_hoy = date("Y-m-d") . " 00:00:00";
$fin_hoy = date("Y-m-d") . " 23:59:59";
$inicio_7dias = date("Y-m-d", strtotime("-6 days")) . " 00:00:00";
$inicio_mes = date("Y-m-01") . " 00:00:00";

$resumen = estadisticas_resumen($conexion, $inicio, $fin);
$resumen_anterior = estadisticas_resumen($conexion, $inicio_anterior, $fin_anterior);
$ventas_hoy = estadisticas_resumen($conexion, $inicio_hoy, $fin_hoy);
$ventas_7dias = estadisticas_resumen($conexion, $inicio_7dias, $fin_hoy);
$ventas_mes = estadisticas_resumen($conexion, $inicio_mes, $fin_hoy);

$top_productos = estadisticas_query($conexion, "SELECT
        productos.nombre,
        SUM(detalle_pedido.cantidad) AS cantidad,
        SUM(detalle_pedido.subtotal) AS total
    FROM detalle_pedido
    INNER JOIN pedidos ON pedidos.id_pedido = detalle_pedido.id_pedido
    INNER JOIN productos ON productos.id_producto = detalle_pedido.id_producto
    WHERE pedidos.estado_pago = 'Pagado'
    AND pedidos.fecha_hora_cobro BETWEEN ? AND ?
    GROUP BY productos.id_producto, productos.nombre
    ORDER BY cantidad DESC, total DESC
    LIMIT 5", $inicio, $fin);

$top_categorias = estadisticas_query($conexion, "SELECT
        COALESCE(categorias.nombre, 'Sin categoria') AS nombre,
        SUM(detalle_pedido.cantidad) AS cantidad,
        SUM(detalle_pedido.subtotal) AS total
    FROM detalle_pedido
    INNER JOIN pedidos ON pedidos.id_pedido = detalle_pedido.id_pedido
    INNER JOIN productos ON productos.id_producto = detalle_pedido.id_producto
    LEFT JOIN categorias ON categorias.id_categoria = productos.id_categoria
    WHERE pedidos.estado_pago = 'Pagado'
    AND pedidos.fecha_hora_cobro BETWEEN ? AND ?
    GROUP BY categorias.id_categoria, categorias.nombre
    ORDER BY total DESC
    LIMIT 5", $inicio, $fin);

$formas_pago = estadisticas_query($conexion, "SELECT
        formas_pago.nombre,
        COUNT(pedidos.id_pedido) AS operaciones,
        COALESCE(SUM(CASE WHEN pedidos.total_final > 0 THEN pedidos.total_final ELSE pedidos.total END), 0) AS total
    FROM pedidos
    INNER JOIN formas_pago ON formas_pago.id_forma_pago = pedidos.id_forma_pago
    WHERE pedidos.estado_pago = 'Pagado'
    AND pedidos.fecha_hora_cobro BETWEEN ? AND ?
    GROUP BY formas_pago.id_forma_pago, formas_pago.nombre
    ORDER BY total DESC", $inicio, $fin);

$tipos_pedido = estadisticas_query($conexion, "SELECT
        tipo_pedido,
        COUNT(*) AS cantidad,
        COALESCE(SUM(CASE WHEN total_final > 0 THEN total_final ELSE total END), 0) AS total
    FROM pedidos
    WHERE estado_pago = 'Pagado'
    AND fecha_hora_cobro BETWEEN ? AND ?
    GROUP BY tipo_pedido
    ORDER BY total DESC", $inicio, $fin);

$top_clientes = estadisticas_query($conexion, "SELECT
        clientes.nombre,
        clientes.puntos_mes,
        COUNT(pedidos.id_pedido) AS cantidad,
        COALESCE(SUM(CASE WHEN pedidos.total_final > 0 THEN pedidos.total_final ELSE pedidos.total END), 0) AS total
    FROM pedidos
    INNER JOIN clientes ON clientes.id_cliente = pedidos.id_cliente
    WHERE pedidos.estado_pago = 'Pagado'
    AND pedidos.fecha_hora_cobro BETWEEN ? AND ?
    GROUP BY clientes.id_cliente, clientes.nombre, clientes.puntos_mes
    ORDER BY total DESC
    LIMIT 5", $inicio_mes, $fin_hoy);

$ventas_por_dia_raw = estadisticas_query_simple($conexion, "SELECT
        DATE(fecha_hora_cobro) AS fecha,
        COUNT(*) AS cantidad,
        COALESCE(SUM(CASE WHEN total_final > 0 THEN total_final ELSE total END), 0) AS total
    FROM pedidos
    WHERE estado_pago = 'Pagado'
    AND fecha_hora_cobro BETWEEN ? AND ?
    GROUP BY DATE(fecha_hora_cobro)
    ORDER BY fecha ASC", $inicio, $fin);

$ventas_por_dia = estadisticas_filas_por_dia($ventas_por_dia_raw, $desde, $hasta);

$ventas_por_hora = estadisticas_query_simple($conexion, "SELECT
        HOUR(fecha_hora_cobro) AS hora,
        COUNT(*) AS cantidad,
        COALESCE(SUM(CASE WHEN total_final > 0 THEN total_final ELSE total END), 0) AS total
    FROM pedidos
    WHERE estado_pago = 'Pagado'
    AND fecha_hora_cobro BETWEEN ? AND ?
    GROUP BY HOUR(fecha_hora_cobro)
    ORDER BY hora ASC", $inicio, $fin);

$cancelados = estadisticas_query_simple($conexion, "SELECT
        COUNT(*) AS cantidad
    FROM pedidos
    WHERE estado = 'Cancelado'
    AND fecha_hora_inicio BETWEEN ? AND ?", $inicio, $fin);

$puntos_resumen = estadisticas_query_simple($conexion, "SELECT
        COALESCE(SUM(CASE WHEN puntos > 0 THEN puntos ELSE 0 END), 0) AS acreditados,
        COALESCE(SUM(CASE WHEN puntos < 0 THEN ABS(puntos) ELSE 0 END), 0) AS descontados,
        COUNT(*) AS movimientos
    FROM puntos_movimientos
    WHERE fecha BETWEEN ? AND ?", $inicio, $fin);

$chart_data = [
    "ventasPorDia" => $ventas_por_dia,
    "formasPago" => array_map(function ($fila) {
        return [
            "label" => $fila["nombre"],
            "valor" => (float) $fila["total"],
            "cantidad" => (int) $fila["operaciones"]
        ];
    }, $formas_pago),
    "tiposPedido" => array_map(function ($fila) {
        return [
            "label" => $fila["tipo_pedido"],
            "valor" => (int) $fila["cantidad"],
            "total" => (float) $fila["total"]
        ];
    }, $tipos_pedido),
    "productos" => array_map(function ($fila) {
        return [
            "label" => $fila["nombre"],
            "valor" => (int) $fila["cantidad"],
            "total" => (float) $fila["total"]
        ];
    }, $top_productos),
    "horas" => array_map(function ($fila) {
        return [
            "label" => str_pad((string) $fila["hora"], 2, "0", STR_PAD_LEFT) . ":00",
            "valor" => (int) $fila["cantidad"],
            "total" => (float) $fila["total"]
        ];
    }, $ventas_por_hora)
];

$extra_css = ["/genesisbar1/estadisticas/css/estadisticas.css?v=2"];
$extra_js = ["/genesisbar1/estadisticas/js/estadisticas.js?v=2"];

require_once("../includes/header.php");
?>

<section class="estadisticas-page">
    <div class="estadisticas-header">
        <div>
            <h2>Estadisticas</h2>
            <p>Ventas cobradas desde <?= htmlspecialchars(date("d/m/Y", strtotime($desde))); ?> hasta <?= htmlspecialchars(date("d/m/Y", strtotime($hasta))); ?>.</p>
        </div>
    </div>

    <form class="estadisticas-filtros" method="get">
        <label>
            Periodo
            <select id="periodoEstadisticas" name="periodo">
                <option value="hoy" <?= $periodo === "hoy" ? "selected" : ""; ?>>Hoy</option>
                <option value="7dias" <?= $periodo === "7dias" ? "selected" : ""; ?>>Ultimos 7 dias</option>
                <option value="mes" <?= $periodo === "mes" ? "selected" : ""; ?>>Este mes</option>
                <option value="personalizado" <?= $periodo === "personalizado" ? "selected" : ""; ?>>Personalizado</option>
            </select>
        </label>
        <label class="fecha-personalizada">
            Desde
            <input type="date" name="desde" value="<?= htmlspecialchars($desde); ?>">
        </label>
        <label class="fecha-personalizada">
            Hasta
            <input type="date" name="hasta" value="<?= htmlspecialchars($hasta); ?>">
        </label>
        <button class="boton" type="submit">Aplicar</button>
    </form>

    <div class="estadisticas-metricas">
        <article class="estadisticas-card">
            <span>Ventas del periodo</span>
            <strong><?= htmlspecialchars(estadisticas_moneda($resumen["total"])); ?></strong>
            <small><?= number_format(estadisticas_porcentaje_cambio($resumen["total"], $resumen_anterior["total"]), 1, ",", "."); ?>% vs periodo anterior</small>
        </article>
        <article class="estadisticas-card">
            <span>Cantidad de pedidos</span>
            <strong><?= (int) $resumen["cantidad"]; ?></strong>
            <small><?= (int) ($cancelados[0]["cantidad"] ?? 0); ?> cancelados</small>
        </article>
        <article class="estadisticas-card">
            <span>Ticket promedio</span>
            <strong><?= htmlspecialchars(estadisticas_moneda($resumen["promedio"])); ?></strong>
            <small>Periodo anterior: <?= htmlspecialchars(estadisticas_moneda($resumen_anterior["promedio"])); ?></small>
        </article>
        <article class="estadisticas-card">
            <span>Puntos del periodo</span>
            <strong><?= (int) ($puntos_resumen[0]["acreditados"] ?? 0); ?> pts</strong>
            <small><?= (int) ($puntos_resumen[0]["descontados"] ?? 0); ?> descontados</small>
        </article>
    </div>

    <div class="estadisticas-metricas">
        <article class="estadisticas-card">
            <span>Ventas de hoy</span>
            <strong><?= htmlspecialchars(estadisticas_moneda($ventas_hoy["total"])); ?></strong>
        </article>
        <article class="estadisticas-card">
            <span>Ultimos 7 dias</span>
            <strong><?= htmlspecialchars(estadisticas_moneda($ventas_7dias["total"])); ?></strong>
        </article>
        <article class="estadisticas-card">
            <span>Mes actual</span>
            <strong><?= htmlspecialchars(estadisticas_moneda($ventas_mes["total"])); ?></strong>
        </article>
    </div>

    <script id="estadisticasData" type="application/json"><?= json_encode($chart_data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?></script>

    <div class="estadisticas-graficos">
        <section class="estadisticas-panel estadisticas-panel--wide">
            <div class="estadisticas-panel-header">
                <h3>Ventas por dia</h3>
                <span><?= htmlspecialchars(date("d/m/Y", strtotime($desde))); ?> - <?= htmlspecialchars(date("d/m/Y", strtotime($hasta))); ?></span>
            </div>
            <canvas class="estadisticas-chart" data-chart="ventasPorDia" height="260"></canvas>
            <p class="estadisticas-vacio" data-empty-for="ventasPorDia">Sin ventas para graficar en este periodo.</p>
        </section>

        <section class="estadisticas-panel">
            <div class="estadisticas-panel-header">
                <h3>Formas de pago</h3>
                <span>Por monto</span>
            </div>
            <canvas class="estadisticas-chart" data-chart="formasPago" height="240"></canvas>
            <p class="estadisticas-vacio" data-empty-for="formasPago">Sin cobros por forma de pago.</p>
        </section>

        <section class="estadisticas-panel">
            <div class="estadisticas-panel-header">
                <h3>Tipos de pedido</h3>
                <span>Por cantidad</span>
            </div>
            <canvas class="estadisticas-chart" data-chart="tiposPedido" height="240"></canvas>
            <p class="estadisticas-vacio" data-empty-for="tiposPedido">Sin pedidos cobrados.</p>
        </section>

        <section class="estadisticas-panel">
            <div class="estadisticas-panel-header">
                <h3>Productos mas vendidos</h3>
                <span>Unidades</span>
            </div>
            <canvas class="estadisticas-chart" data-chart="productos" height="260"></canvas>
            <p class="estadisticas-vacio" data-empty-for="productos">Sin productos vendidos.</p>
        </section>

        <section class="estadisticas-panel">
            <div class="estadisticas-panel-header">
                <h3>Horarios de demanda</h3>
                <span>Pedidos por hora</span>
            </div>
            <canvas class="estadisticas-chart" data-chart="horas" height="260"></canvas>
            <p class="estadisticas-vacio" data-empty-for="horas">Sin horarios para mostrar.</p>
        </section>
    </div>

    <div class="estadisticas-grid">
        <section class="estadisticas-panel">
            <h3>Top productos</h3>
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Monto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_productos as $producto) { ?>
                    <tr>
                        <td><?= htmlspecialchars($producto["nombre"]); ?></td>
                        <td><?= (int) $producto["cantidad"]; ?></td>
                        <td><?= htmlspecialchars(estadisticas_moneda($producto["total"])); ?></td>
                    </tr>
                    <?php } ?>
                    <?php if (count($top_productos) === 0) { ?>
                    <tr><td colspan="3">Sin ventas en el periodo.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </section>

        <section class="estadisticas-panel">
            <h3>Categorias</h3>
            <table>
                <thead>
                    <tr>
                        <th>Categoria</th>
                        <th>Cantidad</th>
                        <th>Monto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_categorias as $categoria) { ?>
                    <tr>
                        <td><?= htmlspecialchars($categoria["nombre"]); ?></td>
                        <td><?= (int) $categoria["cantidad"]; ?></td>
                        <td><?= htmlspecialchars(estadisticas_moneda($categoria["total"])); ?></td>
                    </tr>
                    <?php } ?>
                    <?php if (count($top_categorias) === 0) { ?>
                    <tr><td colspan="3">Sin ventas en el periodo.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </section>

        <section class="estadisticas-panel">
            <h3>Formas de pago</h3>
            <table>
                <thead>
                    <tr>
                        <th>Forma</th>
                        <th>Operaciones</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($formas_pago as $forma) { ?>
                    <tr>
                        <td><?= htmlspecialchars($forma["nombre"]); ?></td>
                        <td><?= (int) $forma["operaciones"]; ?></td>
                        <td><?= htmlspecialchars(estadisticas_moneda($forma["total"])); ?></td>
                    </tr>
                    <?php } ?>
                    <?php if (count($formas_pago) === 0) { ?>
                    <tr><td colspan="3">Sin cobros en el periodo.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </section>

        <section class="estadisticas-panel">
            <h3>Tipos de pedido</h3>
            <table>
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Pedidos</th>
                        <th>Facturacion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tipos_pedido as $tipo) { ?>
                    <tr>
                        <td><?= htmlspecialchars($tipo["tipo_pedido"]); ?></td>
                        <td><?= (int) $tipo["cantidad"]; ?></td>
                        <td><?= htmlspecialchars(estadisticas_moneda($tipo["total"])); ?></td>
                    </tr>
                    <?php } ?>
                    <?php if (count($tipos_pedido) === 0) { ?>
                    <tr><td colspan="3">Sin cobros en el periodo.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </section>

        <section class="estadisticas-panel estadisticas-panel--wide">
            <h3>Top compradores del mes</h3>
            <table>
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Pedidos</th>
                        <th>Total gastado</th>
                        <th>Puntos mes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_clientes as $cliente) { ?>
                    <tr>
                        <td><?= htmlspecialchars($cliente["nombre"]); ?></td>
                        <td><?= (int) $cliente["cantidad"]; ?></td>
                        <td><?= htmlspecialchars(estadisticas_moneda($cliente["total"])); ?></td>
                        <td><?= (int) $cliente["puntos_mes"]; ?></td>
                    </tr>
                    <?php } ?>
                    <?php if (count($top_clientes) === 0) { ?>
                    <tr><td colspan="4">Sin clientes con compras cobradas este mes.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </section>
    </div>
</section>

<?php
require_once("../includes/footer.php");
?>
