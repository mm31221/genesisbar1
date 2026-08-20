<?php
require_once("../config/config.php");
require_once("../php/seguridad.php");
requerir_permiso($conexion, "empleados");

$roles_validos = ["Administrador", "Mozo", "Cocina", "Cajero"];
$mensaje = "";
$tipo_mensaje = "exito";
$csrf = token_csrf();
$empleado_actual = empleado_actual($conexion);

function empleados_redirigir($mensaje, $tipo = "exito")
{
    header("Location: index.php?mensaje=" . urlencode($mensaje) . "&tipo=" . urlencode($tipo));
    exit;
}

if (isset($_GET["mensaje"])) {
    $mensaje = trim($_GET["mensaje"]);
    $tipo_mensaje = ($_GET["tipo"] ?? "exito") === "error" ? "error" : "exito";
}

if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    if (!validar_csrf($_POST["csrf_token"] ?? "")) {
        empleados_redirigir("La sesion vencio. Volve a intentar.", "error");
    }

    $accion = $_POST["accion"] ?? "";
    $id_usuario = isset($_POST["id_usuario"]) ? (int) $_POST["id_usuario"] : 0;

    if ($accion === "crear") {
        $nombre = trim($_POST["nombre"] ?? "");
        $usuario = trim($_POST["usuario"] ?? "");
        $rol = rol_normalizado($_POST["rol"] ?? "");
        $password = $_POST["password"] ?? "";

        if ($nombre === "" || $usuario === "" || !in_array($rol, $roles_validos, true)) {
            empleados_redirigir("Completa nombre, usuario y rol valido.", "error");
        }

        if (!password_segura($password)) {
            empleados_redirigir("La contrasena debe tener al menos 8 caracteres, letras y numeros.", "error");
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $activo = 1;
        $stmt = mysqli_prepare($conexion, "INSERT INTO usuarios (nombre, usuario, password, rol, activo) VALUES (?, ?, ?, ?, ?)");

        if (!$stmt) {
            empleados_redirigir("No se pudo preparar el alta del empleado.", "error");
        }

        mysqli_stmt_bind_param($stmt, "ssssi", $nombre, $usuario, $hash, $rol, $activo);

        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            empleados_redirigir("No se pudo crear el empleado. Revisa que el usuario no exista.", "error");
        }

        mysqli_stmt_close($stmt);
        empleados_redirigir("Empleado creado correctamente.");
    }

    if ($accion === "editar") {
        $nombre = trim($_POST["nombre"] ?? "");
        $usuario = trim($_POST["usuario"] ?? "");
        $rol = rol_normalizado($_POST["rol"] ?? "");

        if ($id_usuario < 1 || $nombre === "" || $usuario === "" || !in_array($rol, $roles_validos, true)) {
            empleados_redirigir("Datos invalidos para editar empleado.", "error");
        }

        $stmt = mysqli_prepare($conexion, "UPDATE usuarios SET nombre = ?, usuario = ?, rol = ? WHERE id_usuario = ?");

        if (!$stmt) {
            empleados_redirigir("No se pudo preparar la edicion.", "error");
        }

        mysqli_stmt_bind_param($stmt, "sssi", $nombre, $usuario, $rol, $id_usuario);

        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            empleados_redirigir("No se pudo editar el empleado. Revisa que el usuario no se repita.", "error");
        }

        mysqli_stmt_close($stmt);
        empleados_redirigir("Empleado actualizado correctamente.");
    }

    if ($accion === "password") {
        $password = $_POST["password"] ?? "";

        if ($id_usuario < 1 || !password_segura($password)) {
            empleados_redirigir("La nueva contrasena debe tener al menos 8 caracteres, letras y numeros.", "error");
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conexion, "UPDATE usuarios SET password = ? WHERE id_usuario = ?");

        if (!$stmt) {
            empleados_redirigir("No se pudo preparar el cambio de contrasena.", "error");
        }

        mysqli_stmt_bind_param($stmt, "si", $hash, $id_usuario);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        empleados_redirigir("Contrasena actualizada correctamente.");
    }

    if ($accion === "estado") {
        $activo = isset($_POST["activo"]) ? (int) $_POST["activo"] : 0;

        if ($id_usuario < 1) {
            empleados_redirigir("Empleado invalido.", "error");
        }

        if ($empleado_actual && $id_usuario === (int) $empleado_actual["id_usuario"] && $activo === 0) {
            empleados_redirigir("No podes desactivar tu propio usuario.", "error");
        }

        $stmt = mysqli_prepare($conexion, "UPDATE usuarios SET activo = ? WHERE id_usuario = ?");

        if (!$stmt) {
            empleados_redirigir("No se pudo preparar el cambio de estado.", "error");
        }

        mysqli_stmt_bind_param($stmt, "ii", $activo, $id_usuario);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        empleados_redirigir($activo ? "Empleado activado." : "Empleado desactivado.");
    }
}

$resultado = mysqli_query($conexion, "SELECT id_usuario, nombre, usuario, rol, activo, fecha_creacion, ultimo_acceso FROM usuarios ORDER BY activo DESC, rol ASC, nombre ASC");
$empleados = [];

if ($resultado) {
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $fila["rol"] = rol_normalizado($fila["rol"] ?? "");
        $empleados[] = $fila;
    }
}

$extra_css = ["/genesisbar1/css/empleados.css?v=3"];
require_once("../includes/header.php");
?>

<section class="empleados-page">
    <div class="empleados-header">
        <div>
            <h2>Empleados y roles</h2>
            <p>Administracion de accesos internos. Los clientes se gestionan en su modulo separado.</p>
        </div>
    </div>

    <?php if ($mensaje !== "") { ?>
        <div class="mensaje-pedido <?= htmlspecialchars($tipo_mensaje); ?>"><?= htmlspecialchars($mensaje); ?></div>
    <?php } ?>

    <div class="empleados-grid">
        <form class="empleado-form" method="post">
            <h3>Nuevo empleado</h3>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf); ?>">
            <input type="hidden" name="accion" value="crear">

            <label for="nombre">Nombre</label>
            <input id="nombre" name="nombre" required>

            <label for="usuario">Usuario</label>
            <input id="usuario" name="usuario" required>

            <label for="rol">Rol</label>
            <select id="rol" name="rol" required>
                <?php foreach ($roles_validos as $rol) { ?>
                    <option value="<?= htmlspecialchars($rol); ?>"><?= htmlspecialchars($rol); ?></option>
                <?php } ?>
            </select>

            <label for="password">Contrasena inicial</label>
            <input id="password" name="password" type="password" minlength="8" required>

            <button class="boton" type="submit">Crear empleado</button>
        </form>

        <div class="empleados-lista">
            <?php foreach ($empleados as $empleado) { ?>
                <?php $activo = (int) $empleado["activo"] === 1; ?>
                <article class="empleado-card">
                    <div class="empleado-card__top">
                        <div>
                            <h3><?= htmlspecialchars($empleado["nombre"]); ?></h3>
                            <p><?= htmlspecialchars($empleado["usuario"]); ?> &middot; <?= htmlspecialchars($empleado["rol"]); ?></p>
                            <small>
                                Alta: <?= htmlspecialchars($empleado["fecha_creacion"] ? date("d/m/Y", strtotime($empleado["fecha_creacion"])) : "-"); ?>
                                &middot; Ultimo acceso: <?= htmlspecialchars($empleado["ultimo_acceso"] ? date("d/m/Y H:i", strtotime($empleado["ultimo_acceso"])) : "-"); ?>
                            </small>
                        </div>
                        <span class="estado-empleado <?= $activo ? "activo" : "inactivo"; ?>">
                            <?= $activo ? "Activo" : "Inactivo"; ?>
                        </span>
                    </div>

                    <form class="empleado-form" method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf); ?>">
                        <input type="hidden" name="accion" value="editar">
                        <input type="hidden" name="id_usuario" value="<?= (int) $empleado["id_usuario"]; ?>">

                        <label>Nombre
                            <input name="nombre" value="<?= htmlspecialchars($empleado["nombre"]); ?>" required>
                        </label>

                        <label>Usuario
                            <input name="usuario" value="<?= htmlspecialchars($empleado["usuario"]); ?>" required>
                        </label>

                        <label>Rol
                            <select name="rol" required>
                                <?php foreach ($roles_validos as $rol) { ?>
                                    <option value="<?= htmlspecialchars($rol); ?>" <?= $empleado["rol"] === $rol ? "selected" : ""; ?>>
                                        <?= htmlspecialchars($rol); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </label>

                        <button class="boton" type="submit">Guardar cambios</button>
                    </form>

                    <div class="empleado-acciones">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf); ?>">
                            <input type="hidden" name="accion" value="estado">
                            <input type="hidden" name="id_usuario" value="<?= (int) $empleado["id_usuario"]; ?>">
                            <input type="hidden" name="activo" value="<?= $activo ? 0 : 1; ?>">
                            <button class="boton <?= $activo ? "boton-peligro" : ""; ?>" type="submit">
                                <?= $activo ? "Desactivar" : "Activar"; ?>
                            </button>
                        </form>

                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf); ?>">
                            <input type="hidden" name="accion" value="password">
                            <input type="hidden" name="id_usuario" value="<?= (int) $empleado["id_usuario"]; ?>">
                            <input name="password" type="password" minlength="8" placeholder="Nueva contrasena" required>
                            <button class="boton boton-secundario" type="submit">Cambiar contrasena</button>
                        </form>
                    </div>
                </article>
            <?php } ?>

            <?php if (count($empleados) === 0) { ?>
                <div class="empleado-card">No hay empleados cargados.</div>
            <?php } ?>
        </div>
    </div>
</section>

<?php require_once("../includes/footer.php"); ?>
