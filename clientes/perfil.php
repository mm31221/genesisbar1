<?php
date_default_timezone_set("America/Argentina/Buenos_Aires");

require_once("../config/config.php");
require_once("../php/seguridad.php");

iniciar_sesion_segura();

$cliente = cliente_actual($conexion);

if (!$cliente) {
    header("Location: login.php");
    exit;
}

$id_cliente = (int) $cliente["id_cliente"];
$pedidos = [];
$movimientos = [];
$direccion_cliente = direccion_compuesta($cliente["direccion_calle"] ?? "", $cliente["direccion_altura"] ?? "", $cliente["direccion"] ?? "");

$stmt_pedidos = mysqli_prepare($conexion, "SELECT
        pedidos.id_pedido,
        pedidos.numero_pedido,
        pedidos.tipo_pedido,
        pedidos.estado,
        pedidos.estado_pago,
        pedidos.total,
        pedidos.total_final,
        pedidos.fecha_hora_inicio,
        pedidos.fecha_hora_cobro,
        formas_pago.nombre AS forma_pago
    FROM pedidos
    LEFT JOIN formas_pago ON formas_pago.id_forma_pago = pedidos.id_forma_pago
    WHERE pedidos.id_cliente = ?
    ORDER BY pedidos.fecha_hora_inicio DESC, pedidos.id_pedido DESC
    LIMIT 20");

if ($stmt_pedidos) {
    mysqli_stmt_bind_param($stmt_pedidos, "i", $id_cliente);
    mysqli_stmt_execute($stmt_pedidos);
    $resultado_pedidos = mysqli_stmt_get_result($stmt_pedidos);

    while ($pedido = mysqli_fetch_assoc($resultado_pedidos)) {
        $pedidos[] = $pedido;
    }

    mysqli_stmt_close($stmt_pedidos);
}

$stmt_movimientos = mysqli_prepare($conexion, "SELECT
        puntos_movimientos.*,
        pedidos.numero_pedido
    FROM puntos_movimientos
    LEFT JOIN pedidos ON pedidos.id_pedido = puntos_movimientos.id_pedido
    WHERE puntos_movimientos.id_cliente = ?
    ORDER BY puntos_movimientos.fecha DESC, puntos_movimientos.id_movimiento DESC
    LIMIT 30");

if ($stmt_movimientos) {
    mysqli_stmt_bind_param($stmt_movimientos, "i", $id_cliente);
    mysqli_stmt_execute($stmt_movimientos);
    $resultado_movimientos = mysqli_stmt_get_result($stmt_movimientos);

    while ($movimiento = mysqli_fetch_assoc($resultado_movimientos)) {
        $movimientos[] = $movimiento;
    }

    mysqli_stmt_close($stmt_movimientos);
}

function cliente_moneda($importe)
{
    return "$" . number_format((float) $importe, 0, ",", ".");
}

function cliente_total_pedido($pedido)
{
    $total_final = isset($pedido["total_final"]) ? (float) $pedido["total_final"] : 0;
    return $total_final > 0 ? $total_final : (float) ($pedido["total"] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi perfil - GenesisBar</title>
    <link rel="stylesheet" href="/genesisbar1/css/estilos.css?v=5">
    <link rel="stylesheet" href="/genesisbar1/css/clientes.css?v=2">
</head>
<body class="portal-clientes">
<header class="portal-navbar">
    <a class="portal-brand" href="/genesisbar1/clientes/index.php">
        <span class="portal-brand-mark">GB</span>
        <span><strong>GenesisBar</strong><small>Portal clientes</small></span>
    </a>
    <nav class="portal-nav portal-nav-simple">
        <a href="/genesisbar1/clientes/index.php#mi-pedido">Mi pedido</a>
        <a href="/genesisbar1/clientes/index.php#estado-pedido">Estado del pedido</a>
        <a href="/genesisbar1/clientes/logout.php">Cerrar sesion</a>
    </nav>
</header>

<main class="portal-auth-main portal-perfil-main">
    <section class="panel-pedido portal-auth-panel">
        <span class="portal-eyebrow">Cuenta cliente</span>
        <h1>Mi perfil</h1>
        <div class="portal-perfil-datos">
            <p><strong>Nombre:</strong> <?= htmlspecialchars(trim(($cliente["nombre"] ?? "") . " " . ($cliente["apellido"] ?? ""))); ?></p>
            <p><strong>Correo:</strong> <?= htmlspecialchars($cliente["email"] ?? "-"); ?></p>
            <p><strong>Telefono:</strong> <?= htmlspecialchars($cliente["telefono"] ?? "-"); ?></p>
            <p><strong>Direccion:</strong> <?= htmlspecialchars($direccion_cliente !== "" ? $direccion_cliente : "-"); ?></p>
        </div>
    </section>

    <section class="panel-pedido portal-puntos-panel">
        <span>Saldo disponible</span>
        <strong><?= (int) ($cliente["puntos"] ?? 0); ?> pts</strong>
        <p>Regla vigente: 1 punto cada $<?= (int) PUNTOS_POR_PESOS; ?> cobrados.</p>
    </section>

    <section class="panel-pedido portal-tabla-panel">
        <h2>Historial de pedidos</h2>
        <?php if (count($pedidos) === 0) { ?>
            <p>Todavia no tenes pedidos asociados a tu cuenta.</p>
        <?php } else { ?>
            <table class="tabla-cliente">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Pago</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $pedido) { ?>
                        <tr>
                            <td><?= htmlspecialchars($pedido["numero_pedido"] ?: "#" . $pedido["id_pedido"]); ?></td>
                            <td><?= htmlspecialchars(date("d/m/Y H:i", strtotime($pedido["fecha_hora_inicio"]))); ?></td>
                            <td><?= htmlspecialchars($pedido["tipo_pedido"]); ?></td>
                            <td><?= htmlspecialchars($pedido["estado"]); ?></td>
                            <td><?= htmlspecialchars($pedido["estado_pago"]); ?></td>
                            <td><?= htmlspecialchars(cliente_moneda(cliente_total_pedido($pedido))); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } ?>
    </section>

    <section class="panel-pedido portal-tabla-panel">
        <h2>Movimientos de puntos</h2>
        <?php if (count($movimientos) === 0) { ?>
            <p>Todavia no tenes movimientos de puntos.</p>
        <?php } else { ?>
            <table class="tabla-cliente">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Pedido</th>
                        <th>Detalle</th>
                        <th>Puntos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movimientos as $movimiento) { ?>
                        <tr>
                            <td><?= htmlspecialchars(date("d/m/Y H:i", strtotime($movimiento["fecha"]))); ?></td>
                            <td><?= htmlspecialchars($movimiento["tipo"]); ?></td>
                            <td><?= htmlspecialchars($movimiento["numero_pedido"] ?: "-"); ?></td>
                            <td><?= htmlspecialchars($movimiento["descripcion"] ?: "-"); ?></td>
                            <td class="<?= (int) $movimiento["puntos"] >= 0 ? "puntos-positivos" : "puntos-negativos"; ?>">
                                <?= (int) $movimiento["puntos"]; ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } ?>
    </section>
</main>
</body>
</html>
