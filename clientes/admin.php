<?php
require_once("../config/config.php");
require_once("../php/seguridad.php");
require_once("../php/puntos.php");
requerir_permiso($conexion, "clientes");

$empleado = empleado_actual($conexion);
$puede_ajustar_puntos = empleado_tiene_permiso($empleado, "configuracion");
$csrf = token_csrf();
$mensaje = "";
$tipo_mensaje = "exito";
$id_cliente = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

function admin_clientes_moneda($importe)
{
    return "$" . number_format((float) $importe, 0, ",", ".");
}

function admin_clientes_redirigir($id_cliente, $mensaje, $tipo = "exito")
{
    $url = "/genesisbar1/clientes/admin.php";

    if ($id_cliente > 0) {
        $url .= "?id=" . (int) $id_cliente;
    }

    $separador = strpos($url, "?") === false ? "?" : "&";
    header("Location: " . $url . $separador . "mensaje=" . urlencode($mensaje) . "&tipo=" . urlencode($tipo));
    exit;
}

if (isset($_GET["mensaje"])) {
    $mensaje = trim($_GET["mensaje"]);
    $tipo_mensaje = ($_GET["tipo"] ?? "exito") === "error" ? "error" : "exito";
}

if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    if (!$puede_ajustar_puntos) {
        admin_clientes_redirigir($id_cliente, "No tenes permiso para modificar puntos.", "error");
    }

    if (!validar_csrf($_POST["csrf_token"] ?? "")) {
        admin_clientes_redirigir($id_cliente, "La sesion vencio. Volve a intentar.", "error");
    }

    $accion = $_POST["accion"] ?? "";
    $id_cliente_post = isset($_POST["id_cliente"]) ? (int) $_POST["id_cliente"] : 0;
    $puntos = isset($_POST["puntos"]) ? abs((int) $_POST["puntos"]) : 0;
    $descripcion = trim($_POST["descripcion"] ?? "");

    if ($id_cliente_post < 1 || $puntos < 1 || $descripcion === "") {
        admin_clientes_redirigir($id_cliente_post, "Completa puntos y descripcion.", "error");
    }

    mysqli_begin_transaction($conexion);

    try {
        $saldo_actual = puntos_saldo_cliente($conexion, $id_cliente_post);
        $tipo = "ajuste";
        $delta = $puntos;

        if ($accion === "canje") {
            $tipo = "canje";
            $delta = -$puntos;

            if ($saldo_actual < $puntos) {
                throw new Exception("El cliente no tiene puntos suficientes.");
            }
        } elseif ($accion === "ajuste_resta") {
            $delta = -$puntos;
        } elseif ($accion !== "ajuste_suma") {
            throw new Exception("Accion invalida.");
        }

        if (!puntos_registrar_movimiento($conexion, $id_cliente_post, $tipo, $delta, $descripcion, null, $empleado["id_usuario"] ?? null)) {
            throw new Exception("No se pudo registrar el movimiento de puntos.");
        }

        $stmt_update = mysqli_prepare($conexion, "UPDATE clientes SET puntos = puntos + ? WHERE id_cliente = ?");

        if (!$stmt_update) {
            throw new Exception("No se pudo actualizar el saldo.");
        }

        mysqli_stmt_bind_param($stmt_update, "ii", $delta, $id_cliente_post);
        mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);

        mysqli_commit($conexion);
        admin_clientes_redirigir($id_cliente_post, "Movimiento de puntos registrado.");
    } catch (Exception $e) {
        mysqli_rollback($conexion);
        admin_clientes_redirigir($id_cliente_post, $e->getMessage(), "error");
    }
}

$extra_css = ["/genesisbar1/css/clientes.css?v=2"];
require_once("../includes/header.php");

if ($id_cliente > 0) {
    $stmt_cliente = mysqli_prepare($conexion, "SELECT
            clientes.id_cliente,
            clientes.nombre,
            clientes.apellido,
            clientes.telefono,
            clientes.email,
            clientes.direccion,
            clientes.fecha_registro,
            clientes.puntos,
            clientes.puntos_mes,
            clientes.observaciones,
            clientes.estado,
            clientes.ultimo_acceso,
            COUNT(pedidos.id_pedido) AS cantidad_pedidos,
            MAX(pedidos.fecha_hora_inicio) AS ultima_compra,
            COALESCE(SUM(CASE WHEN pedidos.estado_pago = 'Pagado' THEN IF(pedidos.total_final > 0, pedidos.total_final, pedidos.total) ELSE 0 END), 0) AS total_gastado
        FROM clientes
        LEFT JOIN pedidos ON pedidos.id_cliente = clientes.id_cliente
        WHERE clientes.id_cliente = ?
        GROUP BY clientes.id_cliente
        LIMIT 1");
    mysqli_stmt_bind_param($stmt_cliente, "i", $id_cliente);
    mysqli_stmt_execute($stmt_cliente);
    $cliente_detalle = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cliente));
    mysqli_stmt_close($stmt_cliente);

    if (!$cliente_detalle) {
        $id_cliente = 0;
    }
}
?>

<section class="clientes-admin-page">
    <div class="clientes-admin-header">
        <div>
            <h2>Clientes</h2>
            <p>Administracion de clientes, historial de compras y puntos.</p>
        </div>
        <?php if ($id_cliente > 0) { ?>
            <a class="boton boton-secundario" href="/genesisbar1/clientes/admin.php">Volver al listado</a>
        <?php } ?>
    </div>

    <?php if ($mensaje !== "") { ?>
        <div class="mensaje-pedido <?= htmlspecialchars($tipo_mensaje); ?>"><?= htmlspecialchars($mensaje); ?></div>
    <?php } ?>

    <?php if ($id_cliente > 0 && isset($cliente_detalle)) { ?>
        <?php
        $pedidos_cliente = [];
        $movimientos_cliente = [];
        $stmt_pedidos = mysqli_prepare($conexion, "SELECT id_pedido, numero_pedido, tipo_pedido, estado, estado_pago, total, total_final, fecha_hora_inicio, fecha_hora_cobro
            FROM pedidos
            WHERE id_cliente = ?
            ORDER BY fecha_hora_inicio DESC, id_pedido DESC
            LIMIT 30");
        mysqli_stmt_bind_param($stmt_pedidos, "i", $id_cliente);
        mysqli_stmt_execute($stmt_pedidos);
        $resultado_pedidos = mysqli_stmt_get_result($stmt_pedidos);
        while ($pedido = mysqli_fetch_assoc($resultado_pedidos)) {
            $pedidos_cliente[] = $pedido;
        }
        mysqli_stmt_close($stmt_pedidos);

        $stmt_movimientos = mysqli_prepare($conexion, "SELECT puntos_movimientos.*, pedidos.numero_pedido, usuarios.nombre AS usuario_nombre
            FROM puntos_movimientos
            LEFT JOIN pedidos ON pedidos.id_pedido = puntos_movimientos.id_pedido
            LEFT JOIN usuarios ON usuarios.id_usuario = puntos_movimientos.id_usuario
            WHERE puntos_movimientos.id_cliente = ?
            ORDER BY puntos_movimientos.fecha DESC, puntos_movimientos.id_movimiento DESC
            LIMIT 40");
        mysqli_stmt_bind_param($stmt_movimientos, "i", $id_cliente);
        mysqli_stmt_execute($stmt_movimientos);
        $resultado_movimientos = mysqli_stmt_get_result($stmt_movimientos);
        while ($movimiento = mysqli_fetch_assoc($resultado_movimientos)) {
            $movimientos_cliente[] = $movimiento;
        }
        mysqli_stmt_close($stmt_movimientos);
        ?>

        <div class="clientes-ficha-grid">
            <article class="panel-pedido cliente-ficha-card">
                <h3><?= htmlspecialchars(trim($cliente_detalle["nombre"] . " " . $cliente_detalle["apellido"])); ?></h3>
                <dl class="cliente-datos-lista">
                    <div><dt>Telefono</dt><dd><?= htmlspecialchars($cliente_detalle["telefono"] ?: "-"); ?></dd></div>
                    <div><dt>Email</dt><dd><?= htmlspecialchars($cliente_detalle["email"] ?: "-"); ?></dd></div>
                    <div><dt>Direccion</dt><dd><?= htmlspecialchars($cliente_detalle["direccion"] ?: "-"); ?></dd></div>
                    <div><dt>Estado</dt><dd><?= htmlspecialchars($cliente_detalle["estado"]); ?></dd></div>
                    <div><dt>Registro</dt><dd><?= htmlspecialchars(date("d/m/Y", strtotime($cliente_detalle["fecha_registro"]))); ?></dd></div>
                    <div><dt>Ultimo acceso</dt><dd><?= htmlspecialchars($cliente_detalle["ultimo_acceso"] ? date("d/m/Y H:i", strtotime($cliente_detalle["ultimo_acceso"])) : "-"); ?></dd></div>
                </dl>
            </article>

            <article class="panel-pedido cliente-puntos-card">
                <span>Saldo</span>
                <strong><?= (int) $cliente_detalle["puntos"]; ?> pts</strong>
                <p>Compras: <?= (int) $cliente_detalle["cantidad_pedidos"]; ?> - Total: <?= htmlspecialchars(admin_clientes_moneda($cliente_detalle["total_gastado"])); ?></p>
            </article>

            <?php if ($puede_ajustar_puntos) { ?>
                <form class="panel-pedido cliente-puntos-form" method="post">
                    <h3>Ajustar puntos</h3>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf); ?>">
                    <input type="hidden" name="id_cliente" value="<?= (int) $id_cliente; ?>">

                    <label for="accion">Operacion</label>
                    <select id="accion" name="accion" required>
                        <option value="ajuste_suma">Sumar ajuste</option>
                        <option value="ajuste_resta">Restar ajuste</option>
                        <option value="canje">Canje</option>
                    </select>

                    <label for="puntos">Puntos</label>
                    <input id="puntos" name="puntos" type="number" min="1" required>

                    <label for="descripcion">Descripcion</label>
                    <input id="descripcion" name="descripcion" maxlength="255" required>

                    <button class="boton" type="submit">Registrar</button>
                </form>
            <?php } ?>
        </div>

        <section class="panel-pedido cliente-tabla-panel">
            <h3>Historial de compras</h3>
            <?php if (count($pedidos_cliente) === 0) { ?>
                <p>Este cliente todavia no tiene pedidos.</p>
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
                        <?php foreach ($pedidos_cliente as $pedido) { ?>
                            <?php $total = (float) $pedido["total_final"] > 0 ? (float) $pedido["total_final"] : (float) $pedido["total"]; ?>
                            <tr>
                                <td><a href="/genesisbar1/pedidos/ver.php?id=<?= (int) $pedido["id_pedido"]; ?>"><?= htmlspecialchars($pedido["numero_pedido"] ?: "#" . $pedido["id_pedido"]); ?></a></td>
                                <td><?= htmlspecialchars(date("d/m/Y H:i", strtotime($pedido["fecha_hora_inicio"]))); ?></td>
                                <td><?= htmlspecialchars($pedido["tipo_pedido"]); ?></td>
                                <td><?= htmlspecialchars($pedido["estado"]); ?></td>
                                <td><?= htmlspecialchars($pedido["estado_pago"]); ?></td>
                                <td><?= htmlspecialchars(admin_clientes_moneda($total)); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
        </section>

        <section class="panel-pedido cliente-tabla-panel">
            <h3>Movimientos de puntos</h3>
            <?php if (count($movimientos_cliente) === 0) { ?>
                <p>No hay movimientos de puntos para este cliente.</p>
            <?php } else { ?>
                <table class="tabla-cliente">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Pedido</th>
                            <th>Descripcion</th>
                            <th>Responsable</th>
                            <th>Puntos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimientos_cliente as $movimiento) { ?>
                            <tr>
                                <td><?= htmlspecialchars(date("d/m/Y H:i", strtotime($movimiento["fecha"]))); ?></td>
                                <td><?= htmlspecialchars($movimiento["tipo"]); ?></td>
                                <td><?= htmlspecialchars($movimiento["numero_pedido"] ?: "-"); ?></td>
                                <td><?= htmlspecialchars($movimiento["descripcion"] ?: "-"); ?></td>
                                <td><?= htmlspecialchars($movimiento["usuario_nombre"] ?: "-"); ?></td>
                                <td class="<?= (int) $movimiento["puntos"] >= 0 ? "puntos-positivos" : "puntos-negativos"; ?>"><?= (int) $movimiento["puntos"]; ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
        </section>
    <?php } else { ?>
        <?php
        $q = trim($_GET["q"] ?? "");
        $orden = $_GET["orden"] ?? "fecha";
        $ordenes = [
            "fecha" => "clientes.fecha_registro DESC",
            "nombre" => "clientes.nombre ASC, clientes.apellido ASC",
            "pedidos" => "cantidad_pedidos DESC",
            "total" => "total_gastado DESC",
            "puntos" => "clientes.puntos DESC"
        ];

        if (!isset($ordenes[$orden])) {
            $orden = "fecha";
        }

        $where = "";
        $params = [];
        $tipos = "";

        if ($q !== "") {
            $where = "WHERE clientes.nombre LIKE ? OR clientes.apellido LIKE ? OR clientes.telefono LIKE ? OR clientes.email LIKE ?";
            $buscar = "%" . $q . "%";
            $params = [$buscar, $buscar, $buscar, $buscar];
            $tipos = "ssss";
        }

        $sql_clientes = "SELECT
                clientes.id_cliente,
                clientes.nombre,
                clientes.apellido,
                clientes.telefono,
                clientes.email,
                clientes.direccion,
                clientes.fecha_registro,
                clientes.puntos,
                clientes.estado,
                COUNT(pedidos.id_pedido) AS cantidad_pedidos,
                MAX(pedidos.fecha_hora_inicio) AS ultima_compra,
                COALESCE(SUM(CASE WHEN pedidos.estado_pago = 'Pagado' THEN IF(pedidos.total_final > 0, pedidos.total_final, pedidos.total) ELSE 0 END), 0) AS total_gastado
            FROM clientes
            LEFT JOIN pedidos ON pedidos.id_cliente = clientes.id_cliente
            $where
            GROUP BY clientes.id_cliente
            ORDER BY {$ordenes[$orden]}
            LIMIT 100";
        $stmt_clientes = mysqli_prepare($conexion, $sql_clientes);

        if ($stmt_clientes && $tipos !== "") {
            $refs = [$tipos];
            foreach ($params as $i => &$param) {
                $refs[] = &$param;
            }
            call_user_func_array([$stmt_clientes, "bind_param"], $refs);
        }

        mysqli_stmt_execute($stmt_clientes);
        $clientes = mysqli_stmt_get_result($stmt_clientes);
        ?>

        <form class="clientes-admin-filtros" method="get">
            <label>
                Buscar
                <input name="q" value="<?= htmlspecialchars($q); ?>" placeholder="Nombre, telefono o email">
            </label>
            <label>
                Ordenar por
                <select name="orden">
                    <option value="fecha" <?= $orden === "fecha" ? "selected" : ""; ?>>Fecha</option>
                    <option value="nombre" <?= $orden === "nombre" ? "selected" : ""; ?>>Nombre</option>
                    <option value="pedidos" <?= $orden === "pedidos" ? "selected" : ""; ?>>Cantidad de pedidos</option>
                    <option value="total" <?= $orden === "total" ? "selected" : ""; ?>>Total gastado</option>
                    <option value="puntos" <?= $orden === "puntos" ? "selected" : ""; ?>>Puntos</option>
                </select>
            </label>
            <button class="boton" type="submit">Filtrar</button>
            <a class="boton boton-secundario" href="/genesisbar1/clientes/admin.php">Limpiar</a>
        </form>

        <section class="panel-pedido cliente-tabla-panel">
            <table class="tabla-cliente">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Contacto</th>
                        <th>Pedidos</th>
                        <th>Ultima compra</th>
                        <th>Total</th>
                        <th>Puntos</th>
                        <th>Ficha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($cliente_fila = mysqli_fetch_assoc($clientes)) { ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars(trim($cliente_fila["nombre"] . " " . $cliente_fila["apellido"])); ?></strong>
                                <small><?= htmlspecialchars($cliente_fila["estado"]); ?></small>
                            </td>
                            <td>
                                <?= htmlspecialchars($cliente_fila["telefono"] ?: "-"); ?><br>
                                <small><?= htmlspecialchars($cliente_fila["email"] ?: "-"); ?></small>
                            </td>
                            <td><?= (int) $cliente_fila["cantidad_pedidos"]; ?></td>
                            <td><?= htmlspecialchars($cliente_fila["ultima_compra"] ? date("d/m/Y", strtotime($cliente_fila["ultima_compra"])) : "-"); ?></td>
                            <td><?= htmlspecialchars(admin_clientes_moneda($cliente_fila["total_gastado"])); ?></td>
                            <td><?= (int) $cliente_fila["puntos"]; ?></td>
                            <td><a href="/genesisbar1/clientes/admin.php?id=<?= (int) $cliente_fila["id_cliente"]; ?>">Ver</a></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </section>
        <?php mysqli_stmt_close($stmt_clientes); ?>
    <?php } ?>
</section>

<?php require_once("../includes/footer.php"); ?>
