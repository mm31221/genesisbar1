<?php
date_default_timezone_set("America/Argentina/Buenos_Aires");

require_once("../config/config.php");
require_once("../php/seguridad.php");

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
        $email = email_normalizado($_POST["email_login"] ?? "");
        $password = $_POST["password_login"] ?? "";

        $stmt = mysqli_prepare($conexion, "SELECT id_cliente, password, estado FROM clientes WHERE email = ? LIMIT 1");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $resultado = mysqli_stmt_get_result($stmt);
            $cliente_login = mysqli_fetch_assoc($resultado);
            mysqli_stmt_close($stmt);
        } else {
            $cliente_login = null;
        }

        if (!$cliente_login || $cliente_login["estado"] !== "Activo" || !password_verify($password, $cliente_login["password"] ?? "")) {
            $mensaje = "Correo o contrasena incorrectos.";
            $tipo_mensaje = "mensaje-pedido error";
        } else {
            $_SESSION["cliente_id"] = (int) $cliente_login["id_cliente"];
            session_regenerate_id(true);
            $fecha = date("Y-m-d H:i:s");
            $stmt = mysqli_prepare($conexion, "UPDATE clientes SET ultimo_acceso = ? WHERE id_cliente = ?");

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "si", $fecha, $_SESSION["cliente_id"]);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            header("Location: index.php");
            exit;
        }
    }
}

$csrf = token_csrf();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - GenesisBar</title>
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
        <a href="/genesisbar1/clientes/registro.php">Registrarme</a>
    </nav>
</header>

<main class="portal-auth-main">
    <section class="panel-pedido portal-auth-panel">
        <span class="portal-eyebrow">Acceso clientes</span>
        <h1>Iniciar sesión</h1>
        <p>Ingresá con tu correo para confirmar pedidos y seguir su estado.</p>

        <?php if ($mensaje !== "") { ?>
            <div class="<?= htmlspecialchars($tipo_mensaje); ?>"><?= htmlspecialchars($mensaje); ?></div>
        <?php } ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf); ?>">
            <label for="email_login">Correo electronico</label>
            <input id="email_login" type="email" name="email_login" required>

            <label for="password_login">Contrasena</label>
            <input id="password_login" type="password" name="password_login" required>

            <button class="boton portal-boton-principal" type="submit">Iniciar sesión</button>
        </form>
    </section>
</main>
</body>
</html>
