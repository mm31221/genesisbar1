<?php
date_default_timezone_set("America/Argentina/Buenos_Aires");

require_once("../../config/config.php");
require_once("../../php/seguridad.php");

$cliente = cliente_actual($conexion);

if (!$cliente) {
    responder_json(false, "Inicia sesion para confirmar el pedido.", [], 401);
}

if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
    responder_json(false, "Metodo no permitido.", [], 405);
}

$datos = json_decode(file_get_contents("php://input"), true);

if (!is_array($datos)) {
    responder_json(false, "No se recibieron datos validos.", [], 400);
}

if (!validar_csrf($datos["csrf_token"] ?? "")) {
    responder_json(false, "La sesion vencio. Actualiza la pagina.", [], 403);
}

$tipo_pedido = trim($datos["tipo_pedido"] ?? "");
$direccion_entrega = trim($datos["direccion_entrega"] ?? "");
$id_forma_pago = isset($datos["id_forma_pago"]) ? (int) $datos["id_forma_pago"] : 0;
$observaciones = trim($datos["observaciones"] ?? "");
$carrito = $datos["carrito"] ?? [];
$fecha = date("Y-m-d H:i:s");
$tipos_validos = ["Take Away", "Delivery"];

if (!in_array($tipo_pedido, $tipos_validos, true)) {
    responder_json(false, "Selecciona un tipo de pedido valido.", [], 422);
}

if ($tipo_pedido === "Delivery" && $direccion_entrega === "") {
    responder_json(false, "Ingresa la direccion para Delivery.", [], 422);
}

if ($id_forma_pago < 1) {
    responder_json(false, "Selecciona una forma de pago.", [], 422);
}

if (!is_array($carrito) || count($carrito) === 0) {
    responder_json(false, "Agrega al menos un producto.", [], 422);
}

$items = [];
$total = 0;

foreach ($carrito as $item) {
    $id_producto = isset($item["id_producto"]) ? (int) $item["id_producto"] : 0;
    $cantidad = isset($item["cantidad"]) ? (int) $item["cantidad"] : 0;
    $observacion_item = trim($item["observaciones"] ?? "");

    if ($id_producto < 1 || $cantidad < 1 || $cantidad > 99) {
        responder_json(false, "El carrito contiene datos invalidos.", [], 422);
    }

    $stmt = mysqli_prepare($conexion, "SELECT id_producto, precio FROM productos WHERE id_producto = ? AND activo = 1 LIMIT 1");

    if (!$stmt) {
        responder_json(false, "No se pudo validar el producto.", [], 500);
    }

    mysqli_stmt_bind_param($stmt, "i", $id_producto);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $producto = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);

    if (!$producto) {
        responder_json(false, "Uno de los productos no esta disponible.", [], 422);
    }

    if (!isset($items[$id_producto])) {
        $items[$id_producto] = [
            "id_producto" => $id_producto,
            "precio" => (float) $producto["precio"],
            "cantidad" => 0,
            "observaciones" => $observacion_item
        ];
    }

    $items[$id_producto]["cantidad"] += $cantidad;

    if ($observacion_item !== "") {
        $items[$id_producto]["observaciones"] = $observacion_item;
    }
}

foreach ($items as $id_producto => $item) {
    $items[$id_producto]["subtotal"] = $item["precio"] * $item["cantidad"];
    $total += $items[$id_producto]["subtotal"];
}

mysqli_begin_transaction($conexion);

try {
    $id_usuario = null;
    $id_mesa = null;
    $mesa = "";
    $origen = "Pagina web";

    $stmt_pedido = mysqli_prepare($conexion, "INSERT INTO pedidos (
            id_cliente,
            id_mesa,
            id_usuario,
            id_forma_pago,
            origen,
            tipo_pedido,
            mesa,
            direccion_entrega,
            estado,
            total,
            total_final,
            observaciones,
            fecha_hora_inicio,
            fecha_hora_entrega,
            tiempo_estimado,
            detalle_pedido
        ) VALUES (?, ?, ?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), 'Pendiente', ?, ?, NULLIF(?, ''), ?, '0000-00-00 00:00:00', 0, '')");

    if (!$stmt_pedido) {
        throw new Exception("No se pudo preparar el pedido.");
    }

    mysqli_stmt_bind_param(
        $stmt_pedido,
        "iiiissssddss",
        $cliente["id_cliente"],
        $id_mesa,
        $id_usuario,
        $id_forma_pago,
        $origen,
        $tipo_pedido,
        $mesa,
        $direccion_entrega,
        $total,
        $total,
        $observaciones,
        $fecha
    );

    if (!mysqli_stmt_execute($stmt_pedido)) {
        throw new Exception("No se pudo guardar el pedido.");
    }

    $id_pedido = mysqli_insert_id($conexion);
    mysqli_stmt_close($stmt_pedido);

    $numero_pedido = "GB-" . date("Ymd") . "-" . str_pad((string) $id_pedido, 4, "0", STR_PAD_LEFT);
    $stmt_numero = mysqli_prepare($conexion, "UPDATE pedidos SET numero_pedido = ? WHERE id_pedido = ?");

    if ($stmt_numero) {
        mysqli_stmt_bind_param($stmt_numero, "si", $numero_pedido, $id_pedido);
        mysqli_stmt_execute($stmt_numero);
        mysqli_stmt_close($stmt_numero);
    }

    $stmt_detalle = mysqli_prepare($conexion, "INSERT INTO detalle_pedido
        (id_pedido, id_producto, cantidad, precio_unitario, subtotal, observaciones)
        VALUES (?, ?, ?, ?, ?, NULLIF(?, ''))");

    if (!$stmt_detalle) {
        throw new Exception("No se pudo preparar el detalle del pedido.");
    }

    foreach ($items as $item) {
        mysqli_stmt_bind_param(
            $stmt_detalle,
            "iiidds",
            $id_pedido,
            $item["id_producto"],
            $item["cantidad"],
            $item["precio"],
            $item["subtotal"],
            $item["observaciones"]
        );

        if (!mysqli_stmt_execute($stmt_detalle)) {
            throw new Exception("No se pudo guardar uno de los productos.");
        }
    }

    mysqli_stmt_close($stmt_detalle);
    mysqli_commit($conexion);

    responder_json(true, "Pedido confirmado.", [
        "id_pedido" => $id_pedido,
        "numero_pedido" => $numero_pedido,
        "total" => $total
    ]);
} catch (Exception $e) {
    mysqli_rollback($conexion);
    responder_json(false, $e->getMessage(), [], 500);
}
?>
