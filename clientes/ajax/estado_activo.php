<?php
date_default_timezone_set("America/Argentina/Buenos_Aires");

require_once("../../config/config.php");
require_once("../../php/seguridad.php");

$cliente = cliente_actual($conexion);

if (!$cliente) {
    responder_json(false, "No autorizado.", ["pedidos" => []], 401);
}

function resumen_productos_cliente($conexion, $id_pedido)
{
    $productos = [];
    $stmt = mysqli_prepare($conexion, "SELECT detalle_pedido.cantidad, productos.nombre
        FROM detalle_pedido
        INNER JOIN productos ON productos.id_producto = detalle_pedido.id_producto
        WHERE detalle_pedido.id_pedido = ?
        ORDER BY detalle_pedido.id_detalle ASC");

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id_pedido);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);

        while ($fila = mysqli_fetch_assoc($resultado)) {
            $productos[] = $fila["nombre"] . " x " . (int) $fila["cantidad"];
        }

        mysqli_stmt_close($stmt);
    }

    return count($productos) > 0 ? implode(", ", $productos) : "-";
}

$pedidos = [];
$stmt = mysqli_prepare($conexion, "SELECT id_pedido, numero_pedido, tipo_pedido, estado, total, fecha_hora_inicio
    FROM pedidos
    WHERE id_cliente = ? AND estado IN ('Pendiente', 'Preparando', 'Listo')
    ORDER BY fecha_hora_inicio DESC
    LIMIT 5");

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $cliente["id_cliente"]);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    while ($pedido = mysqli_fetch_assoc($resultado)) {
        $id_pedido = (int) $pedido["id_pedido"];
        $pedidos[] = [
            "id_pedido" => $id_pedido,
            "numero_pedido" => $pedido["numero_pedido"] ?: "#" . $id_pedido,
            "tipo_pedido" => $pedido["tipo_pedido"],
            "estado" => $pedido["estado"],
            "total" => (float) $pedido["total"],
            "fecha_hora_inicio" => $pedido["fecha_hora_inicio"],
            "productos" => resumen_productos_cliente($conexion, $id_pedido)
        ];
    }

    mysqli_stmt_close($stmt);
}

responder_json(true, "Pedidos activos.", ["pedidos" => $pedidos]);
?>
