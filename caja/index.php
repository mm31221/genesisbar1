<?php
require_once("../config/config.php");
require_once("funciones.php");
require_once("../php/seguridad.php");
requerir_permiso($conexion, "caja");

$empleado = empleado_actual($conexion, "caja");
$csrf = token_csrf();
$mensaje = "";
$tipo_mensaje = "exito";
$resumen_cierre = $_SESSION["resumen_cierre_caja"] ?? null;
unset($_SESSION["resumen_cierre_caja"]);

if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    if (!validar_csrf($_POST["csrf_token"] ?? "")) {
        $mensaje = "La sesion vencio. Volve a intentar.";
        $tipo_mensaje = "error";
    } else {
        $accion = $_POST["accion"] ?? "";

        if ($accion === "abrir_caja") {
            if (caja_abierta_actual($conexion)) {
                $mensaje = "La caja ya esta iniciada.";
                $tipo_mensaje = "error";
            } else {
                $efectivo_inicial = isset($_POST["efectivo_inicial"]) ? (float) $_POST["efectivo_inicial"] : 0;
                $hora_inicio = trim($_POST["hora_inicio"] ?? "");
                $cajero_nombre = trim($_POST["cajero_nombre"] ?? "");

                if ($efectivo_inicial < 0 || $hora_inicio === "" || $cajero_nombre === "") {
                    $mensaje = "Completa efectivo inicial, hora de inicio y cajero.";
                    $tipo_mensaje = "error";
                } else {
                    $fecha_inicio = date("Y-m-d") . " " . $hora_inicio . ":00";
                    $observaciones = "Cajero: " . $cajero_nombre;
                    $concepto = "Inicio de caja";
                    $tipo = "Apertura";
                    $id_usuario = $empleado ? (int) $empleado["id_usuario"] : null;
                    $stmt = mysqli_prepare($conexion, "INSERT INTO movimientos_caja (id_pedido, tipo, concepto, monto, id_forma_pago, id_usuario, fecha_hora, observaciones) VALUES (NULL, ?, ?, ?, NULL, ?, ?, ?)");

                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, "ssdiss", $tipo, $concepto, $efectivo_inicial, $id_usuario, $fecha_inicio, $observaciones);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                        $mensaje = "Caja iniciada correctamente.";
                    } else {
                        $mensaje = "No se pudo iniciar la caja.";
                        $tipo_mensaje = "error";
                    }
                }
            }
        } elseif ($accion === "cerrar_caja") {
            $apertura = caja_abierta_actual($conexion);

            if (!$apertura) {
                $mensaje = "No hay una caja abierta para cerrar.";
                $tipo_mensaje = "error";
            } else {
                $fecha_cierre = date("Y-m-d H:i:s");
                $resumen = caja_resumen_turno($conexion, $apertura["fecha_hora"], $fecha_cierre);
                $efectivo_esperado = (float) $apertura["monto"] + (float) $resumen["efectivo"];
                $concepto = "Cierre de caja";
                $tipo = "Cierre";
                $id_usuario = $empleado ? (int) $empleado["id_usuario"] : null;
                $observaciones = "Pedidos cobrados: " . $resumen["cantidad_pedidos"] . ". Ventas: " . caja_moneda($resumen["total_ingresos"]);
                $stmt = mysqli_prepare($conexion, "INSERT INTO movimientos_caja (id_pedido, tipo, concepto, monto, id_forma_pago, id_usuario, fecha_hora, observaciones) VALUES (NULL, ?, ?, ?, NULL, ?, ?, ?)");

                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "ssdiss", $tipo, $concepto, $efectivo_esperado, $id_usuario, $fecha_cierre, $observaciones);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    $_SESSION["resumen_cierre_caja"] = [
                        "fecha_cierre" => $fecha_cierre,
                        "efectivo_inicial" => (float) $apertura["monto"],
                        "efectivo_esperado" => $efectivo_esperado,
                        "cantidad_pedidos" => $resumen["cantidad_pedidos"],
                        "total_ingresos" => $resumen["total_ingresos"],
                        "formas" => $resumen["formas"]
                    ];
                    header("Location: /genesisbar1/caja/index.php?cierre=1");
                    exit;
                }

                $mensaje = "No se pudo cerrar la caja.";
                $tipo_mensaje = "error";
            }
        }
    }
}

$caja_abierta = caja_abierta_actual($conexion);
$extra_css = ["/genesisbar1/css/caja.css?v=3"];
$extra_js = ["/genesisbar1/caja/js/caja.js?v=3"];

require_once("../includes/header.php");
?>

<section class="caja-page">
    <div class="caja-header">
        <div>
            <h2>Caja</h2>
            <p><?= $caja_abierta ? "Caja iniciada. Pedidos disponibles para cobrar." : "Inicia la caja para comenzar el turno."; ?></p>
        </div>
        <div class="caja-header__acciones">
            <?php if ($caja_abierta) { ?>
                <a class="boton boton-secundario" href="/genesisbar1/caja/historial.php?vista=hoy">Ventas del dia</a>
                <a class="boton boton-secundario" href="/genesisbar1/caja/historial.php?vista=general">Historial general</a>
            <?php } ?>
        </div>
    </div>

    <?php if ($mensaje !== "") { ?>
        <div class="mensaje-pedido <?= htmlspecialchars($tipo_mensaje); ?>"><?= htmlspecialchars($mensaje); ?></div>
    <?php } ?>

    <?php if ($resumen_cierre) { ?>
        <section class="caja-panel caja-cierre-resumen">
            <h3>Cierre de caja</h3>
            <dl class="caja-datos">
                <div><dt>Efectivo total esperado</dt><dd><?= htmlspecialchars(caja_moneda($resumen_cierre["efectivo_esperado"])); ?></dd></div>
                <div><dt>Fecha y hora de cierre</dt><dd><?= htmlspecialchars(date("d/m/Y H:i", strtotime($resumen_cierre["fecha_cierre"]))); ?></dd></div>
                <div><dt>Pedidos cobrados</dt><dd><?= (int) $resumen_cierre["cantidad_pedidos"]; ?></dd></div>
                <div><dt>Ventas del turno</dt><dd><?= htmlspecialchars(caja_moneda($resumen_cierre["total_ingresos"])); ?></dd></div>
            </dl>
            <?php if (!empty($resumen_cierre["formas"])) { ?>
                <table class="tabla-caja">
                    <thead><tr><th>Forma de pago</th><th>Total</th></tr></thead>
                    <tbody>
                        <?php foreach ($resumen_cierre["formas"] as $forma) { ?>
                            <tr><td><?= htmlspecialchars($forma["nombre"]); ?></td><td><?= htmlspecialchars(caja_moneda($forma["total"])); ?></td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
        </section>
    <?php } ?>

    <?php if (!$caja_abierta) { ?>
        <section class="caja-panel caja-inicio-panel">
            <button class="boton" type="button" id="mostrarInicioCaja">Inicio de caja</button>
            <form class="caja-form caja-form-inicio" id="formInicioCaja" method="post" hidden>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf); ?>">
                <input type="hidden" name="accion" value="abrir_caja">

                <label for="efectivo_inicial">Efectivo inicial</label>
                <input id="efectivo_inicial" name="efectivo_inicial" type="number" min="0" step="0.01" value="0" required>

                <label for="hora_inicio">Hora de inicio</label>
                <input id="hora_inicio" name="hora_inicio" type="time" value="<?= htmlspecialchars(date("H:i")); ?>" required>

                <label for="cajero_nombre">Cajero</label>
                <input id="cajero_nombre" name="cajero_nombre" value="<?= htmlspecialchars($empleado["nombre"] ?? ""); ?>" required>

                <button class="boton" type="submit">Guardar inicio</button>
            </form>
        </section>
    <?php } else { ?>
        <div class="caja-toolbar">
            <span id="cajaActualizado">Caja iniciada <?= htmlspecialchars(date("d/m/Y H:i", strtotime($caja_abierta["fecha_hora"]))); ?></span>
            <div class="caja-toolbar__acciones">
                <button class="boton boton-secundario" type="button" id="actualizarCaja">Actualizar</button>
                <form method="post" onsubmit="return confirm('Confirmar cierre de caja.');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf); ?>">
                    <input type="hidden" name="accion" value="cerrar_caja">
                    <button class="boton boton-secundario" type="submit">Cerrar caja</button>
                </form>
            </div>
        </div>

        <div id="contenedorPedidos" class="caja-grid" aria-live="polite">
            <?php caja_render_pedidos_cobrables($conexion); ?>
        </div>
    <?php } ?>
</section>

<?php
require_once("../includes/footer.php");
?>
