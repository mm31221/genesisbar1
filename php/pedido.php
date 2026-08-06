<?php
date_default_timezone_set('America/Argentina/Buenos_Aires');

include("conexion.php");
include("helpers.php");
include("menu.php");

$tipos_pedido = ["delivery", "salon", "take_away"];
$nombre_cliente = trim($_POST['nombre_cliente'] ?? '');
$telefono_cliente = trim($_POST['telefono_cliente'] ?? '');
$email_cliente = trim($_POST['email_cliente'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$tipo_pedido = trim($_POST['tipo_pedido'] ?? 'salon');
$mesa = trim($_POST['mesa'] ?? '');
$producto = $_POST['producto'] ?? '';
$cantidad = isset($_POST['cantidad']) ? (int) $_POST['cantidad'] : 0;
$id_comanda = null;
$cliente_id = null;
$total = 0;
$hora = date("Y-m-d H:i:s");
$mensaje = "";

if ($nombre_cliente === "") {
    $mensaje = "El nombre del cliente es obligatorio.";
} elseif ($telefono_cliente === "") {
    $mensaje = "El telefono del cliente es obligatorio.";
} elseif (!in_array($tipo_pedido, $tipos_pedido, true)) {
    $mensaje = "El tipo de pedido no es valido.";
} elseif ($tipo_pedido === "delivery" && $direccion === "") {
    $mensaje = "La direccion es obligatoria para delivery.";
} elseif ($tipo_pedido === "salon" && $mesa === "") {
    $mensaje = "La mesa es obligatoria para pedidos en salon.";
} elseif (precio_producto($producto) === null) {
    $mensaje = "El producto seleccionado no es valido.";
} elseif ($cantidad < 1) {
    $mensaje = "La cantidad debe ser mayor a cero.";
} else {
    $total = precio_producto($producto) * $cantidad;
    $cliente_id = guardar_cliente($conexion, $nombre_cliente, $telefono_cliente, $email_cliente, $direccion);

    $sql = "INSERT INTO pedidos (
                cliente_id,
                nombre_cliente,
                telefono_cliente,
                email_cliente,
                direccion,
                tipo_pedido,
                mesa,
                producto,
                cantidad,
                hora,
                total,
                estado
            )
            VALUES (?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), ?, NULLIF(?, ''), ?, ?, ?, ?, 'Pendiente')";

    $stmt = mysqli_prepare($conexion, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param(
            $stmt,
            "isssssssisi",
            $cliente_id,
            $nombre_cliente,
            $telefono_cliente,
            $email_cliente,
            $direccion,
            $tipo_pedido,
            $mesa,
            $producto,
            $cantidad,
            $hora,
            $total
        );

        if (mysqli_stmt_execute($stmt)) {
            $mensaje = "Pedido guardado correctamente.";
            $id_comanda = mysqli_insert_id($conexion);
        } else {
            $mensaje = "No se pudo guardar el pedido.";
        }

        mysqli_stmt_close($stmt);
    } else {
        $mensaje = "No se pudo preparar el pedido.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido Registrado</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>

<div class="contenedor">

    <h1>
        <?php if ($id_comanda): ?>
            Comanda N&ordm; <?php echo htmlspecialchars($id_comanda); ?>
        <?php else: ?>
            Pedido no registrado
        <?php endif; ?>
    </h1>

    <p><?php echo htmlspecialchars($mensaje); ?></p>

    <?php if ($id_comanda): ?>
        <p><strong>Cliente:</strong> <?php echo htmlspecialchars($nombre_cliente); ?></p>
        <p><strong>Telefono:</strong> <?php echo htmlspecialchars($telefono_cliente); ?></p>
        <?php if ($email_cliente !== ""): ?>
            <p><strong>Mail:</strong> <?php echo htmlspecialchars($email_cliente); ?></p>
        <?php endif; ?>
        <p><strong>Tipo:</strong> <?php echo htmlspecialchars(texto_tipo_pedido($tipo_pedido)); ?></p>
        <p><strong>Destino:</strong> <?php echo htmlspecialchars(detalle_entrega([
            "tipo_pedido" => $tipo_pedido,
            "direccion" => $direccion,
            "mesa" => $mesa
        ])); ?></p>
        <p><strong>Producto:</strong> <?php echo htmlspecialchars(nombre_producto($producto)); ?></p>
        <?php if (descripcion_producto($producto) !== ""): ?>
            <p><strong>Detalle:</strong> <?php echo htmlspecialchars(descripcion_producto($producto)); ?></p>
        <?php endif; ?>
        <p><strong>Cantidad:</strong> <?php echo htmlspecialchars($cantidad); ?></p>
        <p><strong>Hora:</strong> <?php echo htmlspecialchars($hora); ?></p>
        <p><strong>Total:</strong> $<?php echo number_format($total, 0, ',', '.'); ?></p>
    <?php endif; ?>

    <a href="../index.php">Volver al inicio</a>
    <a href="../index_cliente.php">Index de cliente</a>
    <a href="comandas.php">Ver comandas</a>

</div>

</body>
</html>
