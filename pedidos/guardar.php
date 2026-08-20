<?php
date_default_timezone_set("America/Argentina/Buenos_Aires");

require_once("../config/config.php");
require_once("../php/seguridad.php");

if (!empleado_tiene_permiso(empleado_actual($conexion), "pedidos")) {
    responder_guardado(false, "No autorizado.", [], 403);
}

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

function pedidos_bind_parametros($stmt, $tipos, &$params)
{
    $referencias = [$tipos];

    foreach ($params as $indice => &$valor) {
        $referencias[] = &$valor;
    }

    return call_user_func_array([$stmt, "bind_param"], $referencias);
}

function buscar_o_crear_cliente_pedido($conexion, $nombre, $telefono, $direccion, $direccion_calle, $direccion_altura, $fecha)
{
    $id_cliente = null;
    $usa_direccion_partes = columna_existe($conexion, "clientes", "direccion_calle")
        && columna_existe($conexion, "clientes", "direccion_altura");

    $stmt = mysqli_prepare($conexion, "SELECT id_cliente FROM clientes WHERE telefono = ? LIMIT 1");

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $telefono);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $id_cliente);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    }

    if ($id_cliente) {
        if ($usa_direccion_partes) {
            $stmt = mysqli_prepare($conexion, "UPDATE clientes SET nombre = ?, direccion = NULLIF(?, ''), direccion_calle = NULLIF(?, ''), direccion_altura = NULLIF(?, '') WHERE id_cliente = ?");
        } else {
            $stmt = mysqli_prepare($conexion, "UPDATE clientes SET nombre = ?, direccion = NULLIF(?, '') WHERE id_cliente = ?");
        }

        if ($stmt) {
            if ($usa_direccion_partes) {
                mysqli_stmt_bind_param($stmt, "ssssi", $nombre, $direccion, $direccion_calle, $direccion_altura, $id_cliente);
            } else {
                mysqli_stmt_bind_param($stmt, "ssi", $nombre, $direccion, $id_cliente);
            }

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

    $columnas_direccion = $usa_direccion_partes ? "direccion_calle,
            direccion_altura," : "";
    $valores_direccion = $usa_direccion_partes ? ",
            NULLIF(?, ''),
            NULLIF(?, '')" : "";

    $stmt = mysqli_prepare($conexion, "INSERT INTO clientes (
            nombre,
            apellido,
            telefono,
            email,
            password,
            direccion,
            $columnas_direccion
            fecha_registro,
            puntos,
            puntos_mes,
            observaciones,
            estado
        ) VALUES (?, ?, ?, ?, ?, NULLIF(?, '')$valores_direccion, ?, ?, ?, NULL, ?)");

    if (!$stmt) {
        return null;
    }

    $tipos = $usa_direccion_partes ? "sssssssssiis" : "sssssssiis";
    $params = $usa_direccion_partes ? [
        $nombre,
        $apellido,
        $telefono,
        $email,
        $password,
        $direccion,
        $direccion_calle,
        $direccion_altura,
        $fecha,
        $puntos,
        $puntos_mes,
        $estado_cliente
    ] : [
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
    ];

    pedidos_bind_parametros($stmt, $tipos, $params);

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

if (!validar_csrf($datos["csrf_token"] ?? "")) {
    responder_guardado(false, "La sesion vencio. Actualiza la pagina.", [], 403);
}

$tipos_validos = ["Mesa", "Take Away", "Delivery"];
$reservas_validas = ["Ninguna", "Mesa", "Pedido"];
$origenes_validos = ["Salon", "Pagina web", "Aplicacion", "Codigo QR"];
$tipo_pedido = trim($datos["tipo_pedido"] ?? "");
$tipo_reserva = trim($datos["tipo_reserva"] ?? "Ninguna");
$origen = trim($datos["origen"] ?? "Pagina web");
$id_mesa = isset($datos["id_mesa"]) && $datos["id_mesa"] !== "" ? (int) $datos["id_mesa"] : null;
$mesa = trim($datos["mesa"] ?? "");
$nombre_cliente = trim($datos["nombre_cliente"] ?? "");
$telefono_cliente = trim($datos["telefono_cliente"] ?? "");
$id_forma_pago = isset($datos["id_forma_pago"]) && $datos["id_forma_pago"] !== "" ? (int) $datos["id_forma_pago"] : null;
$direccion_calle = trim($datos["direccion_calle"] ?? "");
$direccion_altura = trim($datos["direccion_altura"] ?? "");
$direccion_entrega = trim($datos["direccion_entrega"] ?? "");
$direccion_entrega = direccion_compuesta($direccion_calle, $direccion_altura, $direccion_entrega);
$horario_entrega_input = trim($datos["horario_entrega"] ?? "");
$observaciones = trim($datos["observaciones"] ?? "");
$carrito = $datos["carrito"] ?? [];
$fecha = date("Y-m-d H:i:s");
$empleado = empleado_actual($conexion);
$id_usuario = $empleado ? (int) $empleado["id_usuario"] : null;

if (!in_array($tipo_pedido, $tipos_validos, true)) {
    responder_guardado(false, "Selecciona un tipo de pedido valido.", [], 422);
}

if (!in_array($tipo_reserva, $reservas_validas, true)) {
    responder_guardado(false, "Selecciona un tipo de reserva valido.", [], 422);
}

if ($tipo_reserva === "Mesa" && $tipo_pedido !== "Mesa") {
    responder_guardado(false, "La reserva de mesa debe ser un pedido de mesa.", [], 422);
}

if (!in_array($origen, $origenes_validos, true)) {
    $origen = "Pagina web";
}

if ($tipo_pedido === "Mesa" && (!$id_mesa || $mesa === "")) {
    responder_guardado(false, "Selecciona una mesa.", [], 422);
}

if (($tipo_pedido === "Take Away" || $tipo_pedido === "Delivery" || $tipo_reserva !== "Ninguna") && $nombre_cliente === "") {
    responder_guardado(false, "Ingresa el nombre del cliente.", [], 422);
}

if (($tipo_pedido === "Take Away" || $tipo_pedido === "Delivery" || $tipo_reserva !== "Ninguna") && $telefono_cliente === "") {
    responder_guardado(false, "Ingresa el telefono del cliente.", [], 422);
}

if (($tipo_pedido === "Take Away" || $tipo_pedido === "Delivery") && !$id_forma_pago) {
    responder_guardado(false, "Selecciona la forma de pago.", [], 422);
}

if ($tipo_pedido === "Delivery" && ($direccion_calle === "" || $direccion_altura === "")) {
    responder_guardado(false, "Ingresa calle y altura para la direccion de entrega.", [], 422);
}

if (!is_array($carrito)) {
    responder_guardado(false, "El carrito contiene datos invalidos.", [], 422);
}

if (count($carrito) === 0 && $tipo_reserva !== "Mesa") {
    responder_guardado(false, "Agrega al menos un producto al carrito.", [], 422);
}

$horario_entrega = null;

if ($horario_entrega_input !== "") {
    $timestamp_horario = strtotime($horario_entrega_input);

    if (!$timestamp_horario) {
        responder_guardado(false, "Ingresa un horario de entrega valido.", [], 422);
    }

    $horario_entrega = date("Y-m-d H:i:s", $timestamp_horario);
}

if ($tipo_reserva !== "Ninguna" && !$horario_entrega) {
    responder_guardado(false, "Ingresa fecha y hora de la reserva.", [], 422);
}

$observaciones_reserva = "";

if ($tipo_reserva === "Mesa") {
    $observaciones_reserva = "Reserva de mesa para " . date("d/m/Y H:i", strtotime($horario_entrega));
} elseif ($tipo_reserva === "Pedido") {
    $observaciones_reserva = "Pedido programado para " . date("d/m/Y H:i", strtotime($horario_entrega));
}

if ($observaciones_reserva !== "") {
    $observaciones = trim($observaciones_reserva . ($observaciones !== "" ? "\n" . $observaciones : ""));
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

    if ($tipo_pedido === "Take Away" || $tipo_pedido === "Delivery" || $tipo_reserva !== "Ninguna") {
        $id_cliente = buscar_o_crear_cliente_pedido($conexion, $nombre_cliente, $telefono_cliente, $direccion_entrega, $direccion_calle, $direccion_altura, $fecha);

        if (!$id_cliente) {
            throw new Exception("No se pudo guardar el cliente del pedido.");
        }
    }

    $usa_horario_entrega = columna_existe($conexion, "pedidos", "horario_entrega");
    $usa_tipo_reserva = columna_existe($conexion, "pedidos", "tipo_reserva");
    $usa_direccion_partes_pedido = columna_existe($conexion, "pedidos", "direccion_calle")
        && columna_existe($conexion, "pedidos", "direccion_altura");

    $columnas = [
        "id_cliente",
        "id_mesa",
        "id_usuario",
        "id_forma_pago",
        "origen",
        "tipo_pedido",
        "mesa",
        "direccion_entrega"
    ];
    $valores = ["?", "?", "?", "?", "NULLIF(?, '')", "?", "NULLIF(?, '')", "NULLIF(?, '')"];
    $tipos_pedido = "iiiissss";
    $params_pedido = [
        $id_cliente,
        $id_mesa,
        $id_usuario,
        $id_forma_pago,
        $origen,
        $tipo_pedido,
        $mesa,
        $direccion_entrega
    ];

    if ($usa_direccion_partes_pedido) {
        $columnas[] = "direccion_calle";
        $columnas[] = "direccion_altura";
        $valores[] = "NULLIF(?, '')";
        $valores[] = "NULLIF(?, '')";
        $tipos_pedido .= "ss";
        $params_pedido[] = $direccion_calle;
        $params_pedido[] = $direccion_altura;
    }

    $columnas = array_merge($columnas, [
        "estado",
        "total",
        "observaciones",
        "fecha_hora_inicio",
        "fecha_hora_entrega",
        "tiempo_estimado",
        "detalle_pedido"
    ]);
    $valores = array_merge($valores, [
        "'Pendiente'",
        "?",
        "NULLIF(?, '')",
        "?",
        "'0000-00-00 00:00:00'",
        "0",
        "''"
    ]);
    $tipos_pedido .= "dss";
    $params_pedido[] = $total;
    $params_pedido[] = $observaciones;
    $params_pedido[] = $fecha;

    if ($usa_horario_entrega) {
        $columnas[] = "horario_entrega";
        $valores[] = "?";
        $tipos_pedido .= "s";
        $params_pedido[] = $horario_entrega;
    }

    if ($usa_tipo_reserva) {
        $columnas[] = "tipo_reserva";
        $valores[] = "?";
        $tipos_pedido .= "s";
        $params_pedido[] = $tipo_reserva;
    }

    $sql_pedido = "INSERT INTO pedidos (" . implode(", ", $columnas) . ") VALUES (" . implode(", ", $valores) . ")";

    $stmt_pedido = mysqli_prepare($conexion, $sql_pedido);

    if (!$stmt_pedido) {
        throw new Exception("No se pudo preparar el pedido.");
    }

    pedidos_bind_parametros($stmt_pedido, $tipos_pedido, $params_pedido);

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

    if (count($items) > 0) {
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
    }

    if ($tipo_pedido === "Mesa" && $id_mesa && $tipo_reserva !== "Mesa") {
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
