<?php
date_default_timezone_set("America/Argentina/Buenos_Aires");

require_once("../config/config.php");
require_once("../php/seguridad.php");
require_once("../php/pedidos_estados.php");

header("Content-Type: application/json; charset=utf-8");

$empleado = empleado_actual($conexion);
$puede_gestionar_pedidos = empleado_tiene_permiso($empleado, "pedidos");

if (!$puede_gestionar_pedidos) {
    http_response_code(403);
    echo json_encode(["ok" => false, "mensaje" => "No autorizado."], JSON_UNESCAPED_UNICODE);
    exit;
}

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

if (!validar_csrf($datos["csrf_token"] ?? "")) {
    responder_estado_pedido(false, "La sesion vencio. Actualiza la pagina.", 403);
}

$id_pedido = isset($datos["id_pedido"]) ? (int) $datos["id_pedido"] : 0;
$estado_nuevo = trim($datos["estado"] ?? "");

if ($id_pedido < 1 || !in_array($estado_nuevo, pedido_estados_operativos(), true)) {
    responder_estado_pedido(false, "Datos invalidos.", 422);
}

$stmt = mysqli_prepare($conexion, "SELECT estado, estado_pago FROM pedidos WHERE id_pedido = ? LIMIT 1");

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

$mensaje_transicion = "";

if (!pedido_transicion_valida($pedido["estado"], $estado_nuevo, $mensaje_transicion)) {
    responder_estado_pedido(false, $mensaje_transicion, 409);
}

if ($pedido["estado"] === $estado_nuevo) {
    responder_estado_pedido(true, "El pedido ya estaba en ese estado.");
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
