<?php
date_default_timezone_set("America/Argentina/Buenos_Aires");

require_once("../php/conexion.php");

header("Content-Type: application/json; charset=utf-8");

function responder_guardado($ok, $mensaje, $datos = [], $codigo_http = 200)
{
    http_response_code($codigo_http);
    echo json_encode(array_merge([
        "ok" => $ok,
        "mensaje" => $mensaje
    ], $datos), JSON_UNESCAPED_UNICODE);
    exit;
}

function buscar_o_crear_cliente_pedido($conexion, $nombre, $telefono, $direccion, $fecha)
{
    $id_cliente = null;
    $stmt = mysqli_prepare($conexion, "SELECT id_cliente FROM clientes WHERE telefono = ? LIMIT 1");

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $telefono);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $id_cliente);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    }

    if ($id_cliente) {
        $stmt = mysqli_prepare($conexion, "UPDATE clientes SET nombre = ?, direccion = NULLIF(?, '') WHERE id_cliente = ?");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssi", $nombre, $direccion, $id_cliente);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        return (int) $id_cliente;
    }

    $apellido = "";
    $email = null;
    $password = null;
    $puntos = 0;
    $puntos_mes = 0;
    $estado_cliente = "Activo";

    $stmt = mysqli_prepare($conexion, "INSERT INTO clientes (
            nombre,
            apellido,
            telefono,
            email,
            password,
            direccion,
            fecha_registro,
            puntos,
            puntos_mes,
            observaciones,
            estado
        ) VALUES (?, ?, ?, ?, ?, NULLIF(?, ''), ?, ?, ?, NULL, ?)");

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "sssssssiis",
        $nombre,
        $apellido,
        $telefono,
        $email,
        $password,
        $direccion,
        $fecha,
        $puntos,
        $puntos_mes,
        $estado_cliente
    );

    mysqli_stmt_execute($stmt);
    $id_cliente = mysqli_insert_id($conexion);
    mysqli_stmt_close($stmt);

    return $id_cliente ? (int) $id_cliente : null;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder_guardado(false, "Metodo no permitido.", [], 405);
}

$datos = json_decode(file_get_contents("php://input"), true);

if (!is_array($datos)) {
    responder_guardado(false, "No se recibieron datos validos.", [], 400);
}

$tipos_validos = ["Mesa", "Take Away", "Delivery"];
$origenes_validos = ["Pagina web", "Aplicacion", "Codigo QR"];
$tipo_pedido = trim($datos["tipo_pedido"] ?? "");
$origen = trim($datos["origen"] ?? "Pagina web");
$id_mesa = isset($datos["id_mesa"]) && $datos["id_mesa"] !== "" ? (int) $datos["id_mesa"] : null;
$mesa = trim($datos["mesa"] ?? "");
$nombre_cliente = trim($datos["nombre_cliente"] ?? "");
$telefono_cliente = trim($datos["telefono_cliente"] ?? "");
$id_forma_pago = isset($datos["id_forma_pago"]) && $datos["id_forma_pago"] !== "" ? (int) $datos["id_forma_pago"] : null;
$direccion_entrega = trim($datos["direccion_entrega"] ?? "");
$observaciones = trim($datos["observaciones"] ?? "");
$carrito = $datos["carrito"] ?? [];
$fecha = date("Y-m-d H:i:s");
$id_usuario = 1;

if (!in_array($tipo_pedido, $tipos_validos, true)) {
    responder_guardado(false, "Selecciona un tipo de pedido valido.", [], 422);
}

if (!in_array($origen, $origenes_validos, true)) {
    $origen = "Pagina web";
}

if ($tipo_pedido === "Mesa" && (!$id_mesa || $mesa === "")) {
    responder_guardado(false, "Selecciona una mesa.", [], 422);
}

if (($tipo_pedido === "Take Away" || $tipo_pedido === "Delivery") && $nombre_cliente === "") {
    responder_guardado(false, "Ingresa el nombre del cliente.", [], 422);
}

if (($tipo_pedido === "Take Away" || $tipo_pedido === "Delivery") && $telefono_cliente === "") {
    responder_guardado(false, "Ingresa el telefono del cliente.", [], 422);
}

if (($tipo_pedido === "Take Away" || $tipo_pedido === "Delivery") && !$id_forma_pago) {
    responder_guardado(false, "Selecciona la forma de pago.", [], 422);
}

if ($tipo_pedido === "Delivery" && $direccion_entrega === "") {
    responder_guardado(false, "Ingresa la direccion de entrega.", [], 422);
}

if (!is_array($carrito) || count($carrito) === 0) {
    responder_guardado(false, "Agrega al menos un producto al carrito.", [], 422);
}

$items = [];
$total = 0;

foreach ($carrito as $item) {
    $id_producto = isset($item["id_producto"]) ? (int) $item["id_producto"] : (int) ($item["id"] ?? 0);
    $cantidad = isset($item["cantidad"]) ? (int) $item["cantidad"] : 0;
    $observacion_producto = trim($item["observaciones"] ?? "");

    if ($id_producto < 1 || $cantidad < 1 || $cantidad > 99) {
        responder_guardado(false, "El carrito contiene productos o cantidades invalidas.", [], 422);
    }

    $stmt_producto = mysqli_prepare($conexion, "SELECT id_producto, nombre, precio FROM productos WHERE id_producto = ? AND activo = 1 LIMIT 1");

    if (!$stmt_producto) {
        responder_guardado(false, "No se pudo validar el producto.", [], 500);
    }

    mysqli_stmt_bind_param($stmt_producto, "i", $id_producto);
    mysqli_stmt_execute($stmt_producto);
    $resultado_producto = mysqli_stmt_get_result($stmt_producto);
    $producto = mysqli_fetch_assoc($resultado_producto);
    mysqli_stmt_close($stmt_producto);

    if (!$producto) {
        responder_guardado(false, "Uno de los productos no esta disponible.", [], 422);
    }

    if (!isset($items[$id_producto])) {
        $items[$id_producto] = [
            "id_producto" => $id_producto,
            "precio" => (float) $producto["precio"],
            "cantidad" => 0,
            "observaciones" => $observacion_producto
        ];
    }

    $items[$id_producto]["cantidad"] += $cantidad;
}

foreach ($items as $id_producto => $item) {
    $items[$id_producto]["subtotal"] = $item["precio"] * $item["cantidad"];
    $total += $items[$id_producto]["subtotal"];
}

mysqli_begin_transaction($conexion);

try {
    $id_cliente = null;

    if ($tipo_pedido === "Take Away" || $tipo_pedido === "Delivery") {
        $id_cliente = buscar_o_crear_cliente_pedido($conexion, $nombre_cliente, $telefono_cliente, $direccion_entrega, $fecha);

        if (!$id_cliente) {
            throw new Exception("No se pudo guardar el cliente del pedido.");
        }
    }

    $sql_pedido = "INSERT INTO pedidos (
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
            observaciones,
            fecha_hora_inicio,
            fecha_hora_entrega,
            tiempo_estimado,
            detalle_pedido
        ) VALUES (
            ?,
            ?,
            ?,
            ?,
            NULLIF(?, ''),
            ?,
            NULLIF(?, ''),
            NULLIF(?, ''),
            'Pendiente',
            ?,
            NULLIF(?, ''),
            ?,
            '0000-00-00 00:00:00',
            0,
            ''
        )";

    $stmt_pedido = mysqli_prepare($conexion, $sql_pedido);

    if (!$stmt_pedido) {
        throw new Exception("No se pudo preparar el pedido.");
    }

    mysqli_stmt_bind_param(
        $stmt_pedido,
        "iiiissssdss",
        $id_cliente,
        $id_mesa,
        $id_usuario,
        $id_forma_pago,
        $origen,
        $tipo_pedido,
        $mesa,
        $direccion_entrega,
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

    if (columna_existe($conexion, "pedidos", "numero_pedido")) {
        $stmt_numero = mysqli_prepare($conexion, "UPDATE pedidos SET numero_pedido = ? WHERE id_pedido = ?");

        if ($stmt_numero) {
            mysqli_stmt_bind_param($stmt_numero, "si", $numero_pedido, $id_pedido);
            mysqli_stmt_execute($stmt_numero);
            mysqli_stmt_close($stmt_numero);
        }
    }

    $usa_observaciones_detalle = columna_existe($conexion, "detalle_pedido", "observaciones");

    if ($usa_observaciones_detalle) {
        $sql_detalle = "INSERT INTO detalle_pedido (
                id_pedido,
                id_producto,
                cantidad,
                precio_unitario,
                subtotal,
                observaciones
            ) VALUES (?, ?, ?, ?, ?, NULLIF(?, ''))";
    } else {
        $sql_detalle = "INSERT INTO detalle_pedido (
                id_pedido,
                id_producto,
                cantidad,
                precio_unitario,
                subtotal
            ) VALUES (?, ?, ?, ?, ?)";
    }

    $stmt_detalle = mysqli_prepare($conexion, $sql_detalle);

    if (!$stmt_detalle) {
        throw new Exception("No se pudo preparar el detalle del pedido.");
    }

    foreach ($items as $item) {
        if ($usa_observaciones_detalle) {
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
        } else {
            mysqli_stmt_bind_param(
                $stmt_detalle,
                "iiidd",
                $id_pedido,
                $item["id_producto"],
                $item["cantidad"],
                $item["precio"],
                $item["subtotal"]
            );
        }

        if (!mysqli_stmt_execute($stmt_detalle)) {
            throw new Exception("No se pudo guardar uno de los productos.");
        }
    }

    mysqli_stmt_close($stmt_detalle);

    if ($tipo_pedido === "Mesa" && $id_mesa) {
        $stmt_mesa = mysqli_prepare($conexion, "UPDATE mesas SET estado = 'Ocupada' WHERE id_mesa = ?");

        if ($stmt_mesa) {
            mysqli_stmt_bind_param($stmt_mesa, "i", $id_mesa);
            mysqli_stmt_execute($stmt_mesa);
            mysqli_stmt_close($stmt_mesa);
        }
    }

    mysqli_commit($conexion);

    responder_guardado(true, "Pedido guardado correctamente.", [
        "id_pedido" => $id_pedido,
        "numero_pedido" => $numero_pedido,
        "total" => $total
    ]);
} catch (Exception $e) {
    mysqli_rollback($conexion);
    responder_guardado(false, $e->getMessage(), [], 500);
}
?>
