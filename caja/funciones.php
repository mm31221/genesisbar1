<?php

function caja_numero_pedido($pedido)
{
    $numero = trim($pedido["numero_pedido"] ?? "");
    return $numero !== "" ? $numero : "#" . (int) $pedido["id_pedido"];
}

function caja_total_pedido($pedido)
{
    $total_final = isset($pedido["total_final"]) ? (float) $pedido["total_final"] : 0;
    return $total_final > 0 ? $total_final : (float) ($pedido["total"] ?? 0);
}

function caja_moneda($importe)
{
    return "$" . number_format((float) $importe, 0, ",", ".");
}

function caja_hora($fecha)
{
    $timestamp = strtotime((string) $fecha);
    return $timestamp ? date("H:i", $timestamp) : "-";
}

function caja_mesa_texto($pedido)
{
    $mesa = trim((string) ($pedido["numero_mesa"] ?? ""));

    if ($mesa === "") {
        $mesa = trim((string) ($pedido["mesa"] ?? ""));
    }

    return $mesa;
}

function caja_cliente_texto($pedido)
{
    $nombre = trim((string) ($pedido["nombre_cliente"] ?? ""));
    $telefono = trim((string) ($pedido["telefono_cliente"] ?? ""));

    if ($nombre !== "" && $telefono !== "") {
        return $nombre . " - " . $telefono;
    }

    return $nombre !== "" ? $nombre : $telefono;
}

function caja_pedido_cobrable($pedido)
{
    $estado = $pedido["estado"] ?? "";
    $estado_pago = $pedido["estado_pago"] ?? "Pendiente";

    if ($estado_pago !== "Pendiente" || in_array($estado, ["Cobrado", "Cancelado"], true)) {
        return false;
    }

    if (($pedido["tipo_reserva"] ?? "Ninguna") === "Mesa" && caja_total_pedido($pedido) <= 0) {
        return false;
    }

    return true;
}

function caja_where_pedidos_cobrables()
{
    return "(
        pedidos.estado_pago = 'Pendiente'
        AND pedidos.estado NOT IN ('Cobrado', 'Cancelado')
    )";
}

function caja_where_pedidos_cobrados()
{
    return "(
        pedidos.estado_pago = 'Pagado'
        AND pedidos.fecha_hora_cobro IS NOT NULL
    )";
}

function caja_movimiento_efectivo($movimiento)
{
    $recibido = isset($movimiento["dinero_recibido"]) ? (float) $movimiento["dinero_recibido"] : 0;
    $vuelto = isset($movimiento["vuelto"]) ? (float) $movimiento["vuelto"] : 0;

    return [
        "dinero_recibido" => $recibido > 0 ? $recibido : null,
        "vuelto" => $vuelto > 0 ? $vuelto : null
    ];
}

function caja_forma_pago_id_por_nombre($conexion, $patron)
{
    $like = "%" . $patron . "%";
    $stmt = mysqli_prepare($conexion, "SELECT id_forma_pago FROM formas_pago WHERE LOWER(nombre) LIKE LOWER(?) ORDER BY id_forma_pago ASC LIMIT 1");

    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, "s", $like);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $forma = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);

    return $forma ? (int) $forma["id_forma_pago"] : 0;
}

function caja_forma_pago_por_id($conexion, $id_forma_pago)
{
    $id_forma_pago = (int) $id_forma_pago;

    if ($id_forma_pago < 1) {
        return null;
    }

    $stmt = mysqli_prepare($conexion, "SELECT id_forma_pago, nombre FROM formas_pago WHERE id_forma_pago = ? LIMIT 1");

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, "i", $id_forma_pago);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $forma = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);

    return $forma ?: null;
}

function caja_forma_pago_es_efectivo_nombre($nombre)
{
    return strpos(strtolower((string) $nombre), "efectivo") !== false;
}

function caja_ultima_apertura($conexion)
{
    $sql = "SELECT movimientos_caja.*, usuarios.nombre AS usuario_nombre
        FROM movimientos_caja
        LEFT JOIN usuarios ON usuarios.id_usuario = movimientos_caja.id_usuario
        WHERE movimientos_caja.tipo IN ('Apertura', 'Cierre')
        ORDER BY movimientos_caja.fecha_hora DESC, movimientos_caja.id_movimiento DESC
        LIMIT 1";
    $resultado = mysqli_query($conexion, $sql);

    if (!$resultado) {
        return null;
    }

    $movimiento = mysqli_fetch_assoc($resultado);
    return $movimiento ?: null;
}

function caja_abierta_actual($conexion)
{
    $movimiento = caja_ultima_apertura($conexion);

    if (!$movimiento || ($movimiento["tipo"] ?? "") !== "Apertura") {
        return null;
    }

    return $movimiento;
}

function caja_resumen_turno($conexion, $inicio, $fin = null)
{
    $fin = $fin ?: date("Y-m-d H:i:s");
    $resumen = [
        "cantidad_pedidos" => 0,
        "total_ingresos" => 0.0,
        "efectivo" => 0.0,
        "otros" => 0.0,
        "formas" => []
    ];

    $stmt = mysqli_prepare($conexion, "SELECT
            COUNT(DISTINCT movimientos_caja.id_pedido) AS cantidad_pedidos,
            COALESCE(SUM(movimientos_caja.monto), 0) AS total_ingresos
        FROM movimientos_caja
        WHERE movimientos_caja.tipo = 'Ingreso'
          AND movimientos_caja.fecha_hora BETWEEN ? AND ?");

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $inicio, $fin);
        mysqli_stmt_execute($stmt);
        $fila = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($fila) {
            $resumen["cantidad_pedidos"] = (int) $fila["cantidad_pedidos"];
            $resumen["total_ingresos"] = (float) $fila["total_ingresos"];
        }
    }

    $stmt_formas = mysqli_prepare($conexion, "SELECT
            formas_pago.nombre,
            COALESCE(SUM(movimientos_caja.monto), 0) AS total
        FROM movimientos_caja
        LEFT JOIN formas_pago ON formas_pago.id_forma_pago = movimientos_caja.id_forma_pago
        WHERE movimientos_caja.tipo = 'Ingreso'
          AND movimientos_caja.fecha_hora BETWEEN ? AND ?
        GROUP BY movimientos_caja.id_forma_pago, formas_pago.nombre
        ORDER BY total DESC");

    if ($stmt_formas) {
        mysqli_stmt_bind_param($stmt_formas, "ss", $inicio, $fin);
        mysqli_stmt_execute($stmt_formas);
        $formas = mysqli_stmt_get_result($stmt_formas);

        while ($forma = mysqli_fetch_assoc($formas)) {
            $nombre = $forma["nombre"] ?: "Sin forma";
            $total = (float) $forma["total"];
            $resumen["formas"][] = [
                "nombre" => $nombre,
                "total" => $total
            ];

            if (caja_forma_pago_es_efectivo_nombre($nombre)) {
                $resumen["efectivo"] += $total;
            } else {
                $resumen["otros"] += $total;
            }
        }

        mysqli_stmt_close($stmt_formas);
    }

    return $resumen;
}

function caja_resumen_pagos_pedido($conexion, $id_pedido)
{
    $resumen = [
        "efectivo" => 0.0,
        "mercado_pago" => 0.0,
        "otros" => 0.0,
        "total_pagado" => 0.0,
        "dinero_recibido" => null,
        "vuelto" => null,
        "formas" => []
    ];

    $stmt = mysqli_prepare($conexion, "SELECT
            movimientos_caja.monto,
            movimientos_caja.dinero_recibido,
            movimientos_caja.vuelto,
            formas_pago.nombre AS forma_pago
        FROM movimientos_caja
        LEFT JOIN formas_pago ON formas_pago.id_forma_pago = movimientos_caja.id_forma_pago
        WHERE movimientos_caja.id_pedido = ? AND movimientos_caja.tipo = 'Ingreso'
        ORDER BY movimientos_caja.id_movimiento ASC");

    if (!$stmt) {
        return $resumen;
    }

    mysqli_stmt_bind_param($stmt, "i", $id_pedido);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    while ($movimiento = mysqli_fetch_assoc($resultado)) {
        $monto = (float) $movimiento["monto"];
        $forma = strtolower((string) ($movimiento["forma_pago"] ?? ""));
        $resumen["total_pagado"] += $monto;

        if (strpos($forma, "efectivo") !== false) {
            $resumen["efectivo"] += $monto;
            $resumen["dinero_recibido"] = $movimiento["dinero_recibido"] !== null ? (float) $movimiento["dinero_recibido"] : $resumen["dinero_recibido"];
            $resumen["vuelto"] = $movimiento["vuelto"] !== null ? (float) $movimiento["vuelto"] : $resumen["vuelto"];
        } elseif (strpos($forma, "mercado") !== false || strpos($forma, "mp") !== false) {
            $resumen["mercado_pago"] += $monto;
        } else {
            $resumen["otros"] += $monto;
        }

        $nombre_forma = $movimiento["forma_pago"] ?: "-";
        $indice_forma = strtolower($nombre_forma);

        if (!isset($resumen["formas"][$indice_forma])) {
            $resumen["formas"][$indice_forma] = [
                "nombre" => $nombre_forma,
                "monto" => 0.0
            ];
        }

        $resumen["formas"][$indice_forma]["monto"] += $monto;
    }

    mysqli_stmt_close($stmt);
    $resumen["formas"] = array_values($resumen["formas"]);

    return $resumen;
}

function caja_bind_parametros($stmt, $tipos, &$params)
{
    $referencias = [$tipos];

    foreach ($params as $indice => &$valor) {
        $referencias[] = &$valor;
    }

    return call_user_func_array([$stmt, "bind_param"], $referencias);
}

function caja_render_pedidos_cobrables($conexion)
{
    $where_cobrables = caja_where_pedidos_cobrables();
    $campo_tipo_reserva = columna_existe($conexion, "pedidos", "tipo_reserva")
        ? "pedidos.tipo_reserva"
        : "'Ninguna'";
    $sql = "SELECT
            pedidos.id_pedido,
            pedidos.numero_pedido,
            pedidos.tipo_pedido,
            $campo_tipo_reserva AS tipo_reserva,
            pedidos.mesa,
            pedidos.estado,
            pedidos.estado_pago,
            pedidos.total,
            pedidos.total_final,
            " . (columna_existe($conexion, "pedidos", "horario_entrega") ? "pedidos.horario_entrega" : "NULL") . " AS horario_entrega,
            pedidos.fecha_hora_inicio,
            clientes.nombre AS nombre_cliente,
            clientes.telefono AS telefono_cliente,
            mesas.numero AS numero_mesa
        FROM pedidos
        LEFT JOIN clientes ON clientes.id_cliente = pedidos.id_cliente
        LEFT JOIN mesas ON mesas.id_mesa = pedidos.id_mesa
        WHERE $where_cobrables
        ORDER BY pedidos.fecha_hora_inicio ASC, pedidos.id_pedido ASC";

    $resultado = mysqli_query($conexion, $sql);

    if (!$resultado) {
        echo '<div class="caja-vacio">No se pudieron cargar los pedidos para cobrar.</div>';
        return;
    }

    if (mysqli_num_rows($resultado) === 0) {
        echo '<div class="caja-vacio">No hay pedidos pendientes de cobro.</div>';
        return;
    }

    while ($pedido = mysqli_fetch_assoc($resultado)) {
        $id_pedido = (int) $pedido["id_pedido"];
        $mesa = caja_mesa_texto($pedido);
        $cliente = caja_cliente_texto($pedido);
        $total = caja_total_pedido($pedido);
?>
        <article class="caja-card">
            <div class="caja-card__top">
                <h3><?= htmlspecialchars(caja_numero_pedido($pedido)); ?></h3>
                <span class="estado estado-<?= htmlspecialchars(strtolower((string) $pedido["estado"])); ?>">
                    <?= htmlspecialchars($pedido["estado"]); ?>
                </span>
            </div>

            <dl class="caja-datos">
                <div>
                    <dt>Tipo</dt>
                    <dd><?= htmlspecialchars($pedido["tipo_pedido"]); ?></dd>
                </div>
                <?php if (($pedido["tipo_reserva"] ?? "Ninguna") !== "Ninguna") { ?>
                <div>
                    <dt>Reserva</dt>
                    <dd><?= htmlspecialchars($pedido["tipo_reserva"] === "Mesa" ? "Reserva de mesa" : "Pedido programado"); ?></dd>
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
                    <dt>Hora</dt>
                    <dd><?= htmlspecialchars(caja_hora($pedido["fecha_hora_inicio"])); ?></dd>
                </div>
                <?php if (!empty($pedido["horario_entrega"])) { ?>
                <div>
                    <dt>Entrega</dt>
                    <dd><?= htmlspecialchars(date("d/m H:i", strtotime($pedido["horario_entrega"]))); ?></dd>
                </div>
                <?php } ?>
            </dl>

            <div class="caja-total">
                <span>Total</span>
                <strong><?= htmlspecialchars(caja_moneda($total)); ?></strong>
            </div>

            <div class="caja-acciones">
                <?php if (caja_pedido_cobrable($pedido)) { ?>
                <a class="boton" href="/genesisbar1/caja/cobrar.php?id=<?= $id_pedido; ?>">Cobrar</a>
                <?php } ?>
                <a class="boton boton-secundario" href="/genesisbar1/pedidos/ver.php?id=<?= $id_pedido; ?>">Ver</a>
            </div>
        </article>
<?php
    }
}

?>
