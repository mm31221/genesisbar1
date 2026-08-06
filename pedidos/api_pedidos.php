<?php
date_default_timezone_set("America/Argentina/Buenos_Aires");

require_once("../php/conexion.php");

header("Content-Type: application/json; charset=utf-8");

function texto_numero_pedido($pedido)
{
    $numero = trim($pedido["numero_pedido"] ?? "");
    return $numero !== "" ? $numero : "#" . $pedido["id_pedido"];
}

function formato_hora_pedido($fecha)
{
    $timestamp = strtotime($fecha);
    return $timestamp ? date("H:i", $timestamp) : "-";
}

$estados_validos = ["Todos", "Pendiente", "Preparando", "Listo", "Entregado"];
$estado = trim($_GET["estado"] ?? "Todos");

if (!in_array($estado, $estados_validos, true)) {
    $estado = "Todos";
}

$contadores = [
    "Pendiente" => 0,
    "Preparando" => 0,
    "Listo" => 0,
    "Entregado" => 0,
    "Todos" => 0
];

$resultado_contadores = mysqli_query($conexion, "SELECT estado, COUNT(*) AS total FROM pedidos GROUP BY estado");

if ($resultado_contadores) {
    while ($fila = mysqli_fetch_assoc($resultado_contadores)) {
        if (isset($contadores[$fila["estado"]])) {
            $contadores[$fila["estado"]] = (int) $fila["total"];
            $contadores["Todos"] += (int) $fila["total"];
        }
    }
}

$where = "";

if ($estado !== "Todos") {
    $where = "WHERE pedidos.estado = ?";
}

$sql = "SELECT
        pedidos.id_pedido,
        pedidos.numero_pedido,
        pedidos.origen,
        pedidos.tipo_pedido,
        pedidos.id_mesa,
        pedidos.mesa,
        pedidos.direccion_entrega,
        pedidos.estado,
        pedidos.total,
        pedidos.observaciones,
        pedidos.fecha_hora_inicio,
        clientes.id_cliente,
        clientes.nombre AS nombre_cliente,
        clientes.telefono AS telefono_cliente,
        formas_pago.nombre AS forma_pago,
        usuarios.id_usuario,
        usuarios.nombre AS nombre_usuario,
        mesas.numero AS numero_mesa
    FROM pedidos
    LEFT JOIN clientes ON clientes.id_cliente = pedidos.id_cliente
    LEFT JOIN formas_pago ON formas_pago.id_forma_pago = pedidos.id_forma_pago
    LEFT JOIN usuarios ON usuarios.id_usuario = pedidos.id_usuario
    LEFT JOIN mesas ON mesas.id_mesa = pedidos.id_mesa
    $where
    ORDER BY pedidos.fecha_hora_inicio DESC, pedidos.id_pedido DESC";

$stmt = mysqli_prepare($conexion, $sql);
$pedidos = [];
$ids = [];

if ($stmt) {
    if ($estado !== "Todos") {
        mysqli_stmt_bind_param($stmt, "s", $estado);
    }

    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    while ($pedido = mysqli_fetch_assoc($resultado)) {
        $id_pedido = (int) $pedido["id_pedido"];

        $pedidos[$id_pedido] = [
            "id_pedido" => $id_pedido,
            "numero_pedido" => texto_numero_pedido($pedido),
            "origen" => $pedido["origen"] ?: "Pagina web",
            "tipo_pedido" => $pedido["tipo_pedido"],
            "mesa" => trim($pedido["numero_mesa"] ?? "") !== "" ? $pedido["numero_mesa"] : ($pedido["mesa"] ?? ""),
            "direccion_entrega" => $pedido["direccion_entrega"] ?? "",
            "estado" => $pedido["estado"],
            "total" => (float) $pedido["total"],
            "observaciones" => $pedido["observaciones"] ?? "",
            "fecha_hora_inicio" => $pedido["fecha_hora_inicio"],
            "hora" => formato_hora_pedido($pedido["fecha_hora_inicio"]),
            "id_cliente" => $pedido["id_cliente"] ? (int) $pedido["id_cliente"] : null,
            "nombre_cliente" => $pedido["nombre_cliente"] ?? "",
            "telefono_cliente" => $pedido["telefono_cliente"] ?? "",
            "forma_pago" => $pedido["forma_pago"] ?? "",
            "id_usuario" => $pedido["id_usuario"] ? (int) $pedido["id_usuario"] : null,
            "nombre_usuario" => $pedido["nombre_usuario"] ?? "",
            "productos" => []
        ];

        $ids[] = $id_pedido;
    }

    mysqli_stmt_close($stmt);
}

if (count($ids) > 0) {
    $ids_sql = implode(",", array_map("intval", $ids));
    $sql_detalle = "SELECT
            detalle_pedido.id_pedido,
            detalle_pedido.cantidad,
            detalle_pedido.precio_unitario,
            detalle_pedido.subtotal,
            detalle_pedido.observaciones,
            productos.nombre
        FROM detalle_pedido
        INNER JOIN productos ON productos.id_producto = detalle_pedido.id_producto
        WHERE detalle_pedido.id_pedido IN ($ids_sql)
        ORDER BY detalle_pedido.id_detalle ASC";
    $resultado_detalle = mysqli_query($conexion, $sql_detalle);

    if ($resultado_detalle) {
        while ($detalle = mysqli_fetch_assoc($resultado_detalle)) {
            $id_pedido = (int) $detalle["id_pedido"];

            if (!isset($pedidos[$id_pedido])) {
                continue;
            }

            $pedidos[$id_pedido]["productos"][] = [
                "nombre" => $detalle["nombre"],
                "cantidad" => (int) $detalle["cantidad"],
                "precio_unitario" => (float) $detalle["precio_unitario"],
                "subtotal" => (float) $detalle["subtotal"],
                "observaciones" => $detalle["observaciones"] ?? ""
            ];
        }
    }
}

echo json_encode([
    "ok" => true,
    "estado" => $estado,
    "contadores" => $contadores,
    "pedidos" => array_values($pedidos),
    "actualizado" => date("H:i:s")
], JSON_UNESCAPED_UNICODE);
?>
