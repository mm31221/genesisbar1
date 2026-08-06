<?php
date_default_timezone_set('America/Argentina/Buenos_Aires');

include("pag php/conexion.php");
include("pag php/helpers.php");
include("pag php/menu.php");

$mensaje_perfil = "";
$mensaje_estado = "";
$pedidos = [];
$accion = $_POST["accion"] ?? $_GET["accion"] ?? "";
$nombre = trim($_POST["nombre"] ?? "");
$telefono = trim($_POST["telefono"] ?? "");
$email = trim($_POST["email"] ?? "");
$direccion = trim($_POST["direccion"] ?? "");
$consulta = trim($_POST["consulta"] ?? $_GET["consulta"] ?? "");
$id_pedido = isset($_POST["id_pedido"]) ? (int) $_POST["id_pedido"] : (int) ($_GET["id_pedido"] ?? 0);

if ($_SERVER["REQUEST_METHOD"] === "POST" && $accion === "perfil") {
    if ($nombre === "") {
        $mensaje_perfil = "Ingresa tu nombre.";
    } elseif ($telefono === "" && $email === "") {
        $mensaje_perfil = "Ingresa un telefono o un mail.";
    } else {
        $id_cliente = guardar_cliente($conexion, $nombre, $telefono, $email, $direccion);
        $mensaje_perfil = $id_cliente ? "Perfil guardado correctamente." : "No se pudo guardar el perfil.";
    }
}

if ($accion === "estado") {
    if ($consulta === "" && $id_pedido < 1) {
        $mensaje_estado = "Ingresa tu telefono, mail o numero de comanda.";
    } elseif ($id_pedido > 0 && $consulta !== "") {
        $sql = "SELECT * FROM pedidos
            WHERE id = ? AND (telefono_cliente = ? OR email_cliente = ?)
            ORDER BY id DESC";
        $stmt = mysqli_prepare($conexion, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iss", $id_pedido, $consulta, $consulta);
            mysqli_stmt_execute($stmt);
            $resultado = mysqli_stmt_get_result($stmt);

            while ($fila = mysqli_fetch_assoc($resultado)) {
                $pedidos[] = $fila;
            }

            mysqli_stmt_close($stmt);
        }
    } elseif ($id_pedido > 0) {
        $sql = "SELECT * FROM pedidos WHERE id = ? ORDER BY id DESC";
        $stmt = mysqli_prepare($conexion, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $id_pedido);
            mysqli_stmt_execute($stmt);
            $resultado = mysqli_stmt_get_result($stmt);

            while ($fila = mysqli_fetch_assoc($resultado)) {
                $pedidos[] = $fila;
            }

            mysqli_stmt_close($stmt);
        }
    } else {
        $sql = "SELECT * FROM pedidos
            WHERE telefono_cliente = ? OR email_cliente = ?
            ORDER BY id DESC
            LIMIT 10";
        $stmt = mysqli_prepare($conexion, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $consulta, $consulta);
            mysqli_stmt_execute($stmt);
            $resultado = mysqli_stmt_get_result($stmt);

            while ($fila = mysqli_fetch_assoc($resultado)) {
                $pedidos[] = $fila;
            }

            mysqli_stmt_close($stmt);
        }
    }

    if ($mensaje_estado === "" && count($pedidos) === 0) {
        $mensaje_estado = "No encontramos pedidos con esos datos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cliente - Genesis Bar</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<div class="contenedor">

    <h1>Cliente Genesis Bar</h1>
    <p>Crea tu perfil y consulta el estado de tu pedido.</p>

    <div class="panel-grid">
        <section class="panel-simple">
            <h2>Mi perfil</h2>

            <?php if ($mensaje_perfil !== ""): ?>
                <p><strong><?php echo htmlspecialchars($mensaje_perfil); ?></strong></p>
            <?php endif; ?>

            <form action="index_cliente.php" method="post">
                <input type="hidden" name="accion" value="perfil">

                <label for="nombre">Nombre</label>
                <input id="nombre" type="text" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required>

                <label for="telefono">Telefono</label>
                <input id="telefono" type="tel" name="telefono" value="<?php echo htmlspecialchars($telefono); ?>">

                <label for="email">Mail</label>
                <input id="email" type="email" name="email" value="<?php echo htmlspecialchars($email); ?>">

                <label for="direccion_cliente">Direccion</label>
                <input id="direccion_cliente" type="text" name="direccion" value="<?php echo htmlspecialchars($direccion); ?>">

                <button type="submit">Guardar perfil</button>
            </form>
        </section>

        <section class="panel-simple">
            <h2>Estado del pedido</h2>

            <?php if ($mensaje_estado !== ""): ?>
                <p><strong><?php echo htmlspecialchars($mensaje_estado); ?></strong></p>
            <?php endif; ?>

            <form action="index_cliente.php" method="post">
                <input type="hidden" name="accion" value="estado">

                <label for="consulta">Telefono o mail</label>
                <input id="consulta" type="text" name="consulta" value="<?php echo htmlspecialchars($consulta); ?>">

                <label for="id_pedido">Numero de comanda</label>
                <input id="id_pedido" type="number" name="id_pedido" min="1" value="<?php echo $id_pedido > 0 ? htmlspecialchars($id_pedido) : ''; ?>">

                <button type="submit">Ver estado</button>
            </form>
        </section>
    </div>

    <?php if (count($pedidos) > 0): ?>
        <div class="estado-lista">
            <?php foreach ($pedidos as $pedido): ?>
                <div class="comanda">
                    <h2>Comanda #<?php echo htmlspecialchars($pedido["id"]); ?></h2>
                    <p><strong>Estado:</strong> <?php echo htmlspecialchars(estado_cliente($pedido["estado"], $pedido["tipo_pedido"] ?? "salon")); ?></p>
                    <p><strong>Pedido:</strong> <?php echo htmlspecialchars(nombre_producto($pedido["producto"])); ?> x <?php echo htmlspecialchars($pedido["cantidad"]); ?></p>
                    <?php if (descripcion_producto($pedido["producto"]) !== ""): ?>
                        <p><strong>Detalle:</strong> <?php echo htmlspecialchars(descripcion_producto($pedido["producto"])); ?></p>
                    <?php endif; ?>
                    <p><strong>Tipo:</strong> <?php echo htmlspecialchars(texto_tipo_pedido($pedido["tipo_pedido"] ?? "salon")); ?></p>
                    <p><strong>Destino:</strong> <?php echo htmlspecialchars(detalle_entrega($pedido)); ?></p>
                    <p><strong>Hora:</strong> <?php echo htmlspecialchars($pedido["hora"]); ?></p>
                    <p><strong>Total:</strong> $<?php echo number_format($pedido["total"], 0, ',', '.'); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="acciones">
        <a href="index.php">Nueva comanda</a>
    </div>

</div>

</body>
</html>
