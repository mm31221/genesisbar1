<?php
require_once("../config/config.php");
require_once("../php/seguridad.php");

iniciar_sesion_segura();

$mensaje = "";
$csrf = token_csrf();
$empleado_logueado = empleado_actual($conexion);

if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    if (!validar_csrf($_POST["csrf_token"] ?? "")) {
        $mensaje = "La sesion vencio. Actualiza la pagina e intenta nuevamente.";
    } else {
        $usuario = trim($_POST["usuario"] ?? "");
        $password = $_POST["password"] ?? "";

        $stmt = mysqli_prepare($conexion, "SELECT id_usuario, usuario, password, rol, activo FROM usuarios WHERE usuario = ? AND activo = 1 LIMIT 1");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $usuario);
            mysqli_stmt_execute($stmt);
            $resultado = mysqli_stmt_get_result($stmt);
            $empleado = mysqli_fetch_assoc($resultado);
            mysqli_stmt_close($stmt);
        } else {
            $empleado = null;
        }

        $password_db = $empleado["password"] ?? "";
        $valido = $empleado && password_verify($password, $password_db);

        if (!$valido) {
            $mensaje = "Usuario o contrasena incorrectos.";
        } else {
            $empleado["rol"] = rol_normalizado($empleado["rol"] ?? "");
            $_SESSION["empleado_id"] = (int) $empleado["id_usuario"];
            $_SESSION["empleado_rol_activo"] = $empleado["rol"];
            $_SESSION["empleados_por_rol"][$empleado["rol"]] = (int) $empleado["id_usuario"];
            session_regenerate_id(true);

            $fecha = date("Y-m-d H:i:s");
            $stmt_acceso = mysqli_prepare($conexion, "UPDATE usuarios SET ultimo_acceso = ? WHERE id_usuario = ?");

            if ($stmt_acceso) {
                mysqli_stmt_bind_param($stmt_acceso, "si", $fecha, $empleado["id_usuario"]);
                mysqli_stmt_execute($stmt_acceso);
                mysqli_stmt_close($stmt_acceso);
            }

            header("Location: " . empleado_inicio_url($empleado));

            exit;
        }
    }
}

$extra_css = ["/genesisbar1/css/empleados.css?v=4"];
require_once("../includes/header.php");
?>

<section class="login-empleado">
    <div class="login-empleado__intro">
        <h2>Ingreso de empleados</h2>
        <p>Ingresa con tus credenciales. El sistema abre automaticamente tu pantalla segun el rol de la base.</p>
    </div>

    <?php if ($empleado_logueado) { ?>
        <div class="mensaje-pedido">
            Sesion activa: <?= htmlspecialchars($empleado_logueado["nombre"]); ?> (<?= htmlspecialchars($empleado_logueado["rol"]); ?>).
            Podes ingresar otro rol sin cerrar esta sesion.
        </div>
    <?php } ?>

    <?php if ($mensaje !== "") { ?>
        <div class="mensaje-pedido error"><?= htmlspecialchars($mensaje); ?></div>
    <?php } ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf); ?>">

        <label for="usuario">Usuario</label>
        <input id="usuario" name="usuario" autocomplete="username" value="<?= htmlspecialchars($_POST["usuario"] ?? ""); ?>" autofocus required>

        <label for="password">Contrasena</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>

        <button class="boton" type="submit">Ingresar</button>
    </form>
</section>

<?php require_once("../includes/footer.php"); ?>
