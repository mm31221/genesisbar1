<?php
date_default_timezone_set("America/Argentina/Buenos_Aires");

require_once("../php/conexion.php");

header("Content-Type: application/json; charset=utf-8");

function responder_estado_pedido($ok, $mensaje, $codigo_http = 200)
{
    http_response_code($codigo_http);
    echo json_encode([
        "ok" => $ok,
        "mensaje" => $mensaje
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder_estado_pedido(false, "Metodo no permitido.", 405);
}

$datos = json_decode(file_get_contents("php://input"), true);

if (!is_array($datos)) {
    $datos = $_POST;
}

$id_pedido = isset($datos["id_pedido"]) ? (int) $datos["id_pedido"] : 0;
$estado_nuevo = trim($datos["estado"] ?? "");
$estados_validos = ["Pendiente", "Preparando", "Listo", "Entregado"];
$orden = [
    "Pendiente" => 1,
    "Preparando" => 2,
    "Listo" => 3,
    "Entregado" => 4
];

if ($id_pedido < 1 || !in_array($estado_nuevo, $estados_validos, true)) {
    responder_estado_pedido(false, "Datos invalidos.", 422);
}

$stmt = mysqli_prepare($conexion, "SELECT estado FROM pedidos WHERE id_pedido = ? LIMIT 1");

if (!$stmt) {
    responder_estado_pedido(false, "No se pudo consultar el pedido.", 500);
}

mysqli_stmt_bind_param($stmt, "i", $id_pedido);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$pedido = mysqli_fetch_assoc($resultado);
mysqli_stmt_close($stmt);

if (!$pedido) {
    responder_estado_pedido(false, "Pedido inexistente.", 404);
}

if ($pedido["estado"] === "Entregado" || $pedido["estado"] === "Cobrado") {
    responder_estado_pedido(false, "Un pedido entregado o cobrado no puede volver a estados anteriores.", 409);
}

if (!isset($orden[$pedido["estado"]])) {
    responder_estado_pedido(false, "Este pedido no admite cambios de estado desde Pedidos.", 409);
}

if (($orden[$estado_nuevo] ?? 0) < $orden[$pedido["estado"]]) {
    responder_estado_pedido(false, "No se puede volver a un estado anterior.", 409);
}

if ($estado_nuevo === "Entregado") {
    $fecha = date("Y-m-d H:i:s");
    $stmt = mysqli_prepare($conexion, "UPDATE pedidos SET estado = ?, fecha_hora_entrega = ? WHERE id_pedido = ?");

    if (!$stmt) {
        responder_estado_pedido(false, "No se pudo preparar el cambio de estado.", 500);
    }

    mysqli_stmt_bind_param($stmt, "ssi", $estado_nuevo, $fecha, $id_pedido);
} else {
    $stmt = mysqli_prepare($conexion, "UPDATE pedidos SET estado = ? WHERE id_pedido = ?");

    if (!$stmt) {
        responder_estado_pedido(false, "No se pudo preparar el cambio de estado.", 500);
    }

    mysqli_stmt_bind_param($stmt, "si", $estado_nuevo, $id_pedido);
}

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    responder_estado_pedido(false, "No se pudo actualizar el estado.", 500);
}

mysqli_stmt_close($stmt);
responder_estado_pedido(true, "Estado actualizado correctamente.");
?>
