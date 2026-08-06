<?php
date_default_timezone_set("America/Argentina/Buenos_Aires");

require_once("../php/conexion.php");

header("Content-Type: application/json; charset=utf-8");

function cocina_tiene_columna($conexion, $tabla, $columna)
{
    $tabla = mysqli_real_escape_string($conexion, $tabla);
    $columna = mysqli_real_escape_string($conexion, $columna);
    $resultado = mysqli_query($conexion, "SHOW COLUMNS FROM `$tabla` LIKE '$columna'");

    return $resultado && mysqli_num_rows($resultado) > 0;
}

function cocina_texto_estado($estado)
{
    $estados = [
        "Pendiente" => "Pendiente",
        "Preparando" => "En preparacion",
        "Listo" => "Listo",
        "Entregado" => "Entregado"
    ];

    return $estados[$estado] ?? $estado;
}

$sql_pedidos = "SELECT
        pedidos.id_pedido,
        pedidos.numero_pedido,
        pedidos.estado,
        pedidos.observaciones,
        pedidos.fecha_hora_inicio
    FROM pedidos
    WHERE pedidos.estado IN ('Pendiente', 'Preparando', 'Listo')
    ORDER BY pedidos.fecha_hora_inicio ASC, pedidos.id_pedido ASC";

$resultado_pedidos = mysqli_query($conexion, $sql_pedidos);
$pedidos = [];
$ids = [];

if ($resultado_pedidos) {
    while ($pedido = mysqli_fetch_assoc($resultado_pedidos)) {
        $id_pedido = (int) $pedido["id_pedido"];
        $numero_pedido = trim($pedido["numero_pedido"] ?? "");

        $pedidos[$id_pedido] = [
            "id_pedido" => $id_pedido,
            "numero_pedido" => $numero_pedido !== "" ? $numero_pedido : "#" . $id_pedido,
            "estado" => $pedido["estado"],
            "estado_texto" => cocina_texto_estado($pedido["estado"]),
            "observaciones" => $pedido["observaciones"] ?? "",
            "fecha_hora_inicio" => $pedido["fecha_hora_inicio"],
            "hora" => date("H:i", strtotime($pedido["fecha_hora_inicio"])),
            "minutos_transcurridos" => max(0, (int) floor((time() - strtotime($pedido["fecha_hora_inicio"])) / 60)),
            "productos" => []
        ];

        $ids[] = $id_pedido;
    }
}

if (count($ids) > 0) {
    $ids_sql = implode(",", array_map("intval", $ids));
    $campo_observaciones = cocina_tiene_columna($conexion, "detalle_pedido", "observaciones")
        ? "detalle_pedido.observaciones"
        : "''";

    $sql_detalle = "SELECT
            detalle_pedido.id_pedido,
            detalle_pedido.cantidad,
            detalle_pedido.precio_unitario,
            detalle_pedido.subtotal,
            $campo_observaciones AS observaciones_producto,
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
                "observaciones" => $detalle["observaciones_producto"] ?? ""
            ];
        }
    }
}

$lista_pedidos = array_values($pedidos);
$contadores = [
    "total" => count($lista_pedidos),
    "pendientes" => 0,
    "preparando" => 0,
    "listos" => 0
];

foreach ($lista_pedidos as $pedido) {
    if ($pedido["estado"] === "Pendiente") {
        $contadores["pendientes"]++;
    } elseif ($pedido["estado"] === "Preparando") {
        $contadores["preparando"]++;
    } elseif ($pedido["estado"] === "Listo") {
        $contadores["listos"]++;
    }
}

echo json_encode([
    "ok" => true,
    "pedidos" => $lista_pedidos,
    "contadores" => $contadores,
    "actualizado" => date("H:i:s")
], JSON_UNESCAPED_UNICODE);
?>
