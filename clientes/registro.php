<?php
date_default_timezone_set("America/Argentina/Buenos_Aires");

require_once("../config/config.php");
require_once("../php/seguridad.php");
require_once("../php/clientes_auth.php");

iniciar_sesion_segura();

if (cliente_actual($conexion)) {
    header("Location: index.php");
    exit;
}

$mensaje = "";
$tipo_mensaje = "mensaje-pedido";

if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    if (!validar_csrf($_POST["csrf_token"] ?? "")) {
        $mensaje = "La sesion vencio. Actualiza la pagina e intenta nuevamente.";
        $tipo_mensaje = "mensaje-pedido error";
    } else {
        $id_cliente = cliente_registrar_cuenta($conexion, $_POST, $mensaje);

        if ($id_cliente > 0) {
            $_SESSION["cliente_id"] = $id_cliente;
            session_regenerate_id(true);
            header("Location: index.php");
            exit;
        }

        $tipo_mensaje = "mensaje-pedido error";
    }
}

$csrf = token_csrf();
$usa_direccion_partes_cliente = columna_existe($conexion, "clientes", "direccion_calle")
    && columna_existe($conexion, "clientes", "direccion_altura");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarme - GenesisBar</title>
    <link rel="stylesheet" href="/genesisbar1/css/estilos.css?v=5">
</head>
<body class="portal-clientes">
<header class="portal-navbar">
    <a class="portal-brand" href="/genesisbar1/clientes/index.php">
        <span class="portal-brand-mark">GB</span>
        <span><strong>GenesisBar</strong><small>Portal clientes</small></span>
    </a>
    <nav class="portal-nav portal-nav-simple">
        <a href="/genesisbar1/clientes/index.php">Inicio</a>
        <a href="/genesisbar1/clientes/login.php">Iniciar sesión</a>
    </nav>
</header>

<main class="portal-auth-main">
    <section class="panel-pedido portal-auth-panel portal-auth-panel-amplio">
        <span class="portal-eyebrow">Nueva cuenta</span>
        <h1>Registrarme</h1>
        <p>Guardá tus datos para pedir más rápido y consultar el estado de tus pedidos.</p>

        <?php if ($mensaje !== "") { ?>
            <div class="<?= htmlspecialchars($tipo_mensaje); ?>"><?= htmlspecialchars($mensaje); ?></div>
        <?php } ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf); ?>">
            <div class="pedido-grid">
                <div>
                    <label for="nombre">Nombre</label>
                    <input id="nombre" name="nombre" value="<?= htmlspecialchars($_POST["nombre"] ?? ""); ?>" required>
                </div>
                <div>
                    <label for="apellido">Apellido</label>
                    <input id="apellido" name="apellido" value="<?= htmlspecialchars($_POST["apellido"] ?? ""); ?>" required>
                </div>
            </div>

            <label for="telefono">Telefono</label>
            <input id="telefono" name="telefono" value="<?= htmlspecialchars($_POST["telefono"] ?? ""); ?>" required>

            <label for="email">Correo electronico</label>
            <input id="email" type="email" name="email" value="<?= htmlspecialchars($_POST["email"] ?? ""); ?>" required>

            <?php if ($usa_direccion_partes_cliente) { ?>
                <label>Direccion</label>
                <div class="pedido-grid">
                    <div>
                        <label for="direccion_calle">Calle</label>
                        <input id="direccion_calle" name="direccion_calle" value="<?= htmlspecialchars($_POST["direccion_calle"] ?? ""); ?>">
                    </div>
                    <div>
                        <label for="direccion_altura">Altura</label>
                        <input id="direccion_altura" name="direccion_altura" value="<?= htmlspecialchars($_POST["direccion_altura"] ?? ""); ?>">
                    </div>
                </div>
                <input type="hidden" name="direccion" value="<?= htmlspecialchars(direccion_compuesta($_POST["direccion_calle"] ?? "", $_POST["direccion_altura"] ?? "", $_POST["direccion"] ?? "")); ?>">
            <?php } else { ?>
                <label for="direccion">Direccion</label>
                <input id="direccion" name="direccion" value="<?= htmlspecialchars($_POST["direccion"] ?? ""); ?>">
            <?php } ?>

            <label for="referencias">Referencias para delivery</label>
            <textarea id="referencias" name="referencias" rows="3"><?= htmlspecialchars($_POST["referencias"] ?? ""); ?></textarea>

            <div class="pedido-grid">
                <div>
                    <label for="password">Contrasena</label>
                    <input id="password" type="password" name="password" minlength="8" required>
                </div>
                <div>
                    <label for="password_confirmacion">Confirmar contrasena</label>
                    <input id="password_confirmacion" type="password" name="password_confirmacion" minlength="8" required>
                </div>
            </div>

            <button class="boton portal-boton-principal" type="submit">Crear cuenta</button>
        </form>
    </section>
</main>
</body>
</html>
