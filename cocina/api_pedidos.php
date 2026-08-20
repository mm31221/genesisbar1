<?php
date_default_timezone_set("America/Argentina/Buenos_Aires");

require_once("../config/config.php");
require_once("../php/seguridad.php");
require_once("../php/pedidos_estados.php");

header("Content-Type: application/json; charset=utf-8");

if (!empleado_tiene_permiso(empleado_actual($conexion), "cocina")) {
    http_response_code(403);
    echo json_encode(["ok" => false, "mensaje" => "No autorizado."], JSON_UNESCAPED_UNICODE);
    exit;
}

$campo_horario_entrega = columna_existe($conexion, "pedidos", "horario_entrega")
    ? "pedidos.horario_entrega"
    : "NULL";
$usa_tipo_reserva = columna_existe($conexion, "pedidos", "tipo_reserva");
$campo_tipo_reserva = $usa_tipo_reserva ? "pedidos.tipo_reserva" : "'Ninguna'";
$filtro_reservas = $usa_tipo_reserva
    ? "AND (
        pedidos.tipo_reserva = 'Ninguna'
        OR pedidos.tipo_reserva IS NULL
        OR pedidos.estado IN ('Preparando', 'Listo')
        OR (
            pedidos.tipo_reserva = 'Pedido'
            AND (
                pedidos.horario_entrega IS NULL
                OR pedidos.horario_entrega <= DATE_ADD(NOW(), INTERVAL 60 MINUTE)
            )
        )
    )"
    : "";

$sql_pedidos = "SELECT
        pedidos.id_pedido,
        pedidos.numero_pedido,
        pedidos.tipo_pedido,
        pedidos.mesa,
        pedidos.estado,
        pedidos.observaciones,
        pedidos.fecha_hora_inicio,
        $campo_horario_entrega AS horario_entrega,
        $campo_tipo_reserva AS tipo_reserva,
        mesas.numero AS numero_mesa
    FROM pedidos
    LEFT JOIN mesas ON mesas.id_mesa = pedidos.id_mesa
    WHERE pedidos.estado IN ('Pendiente', 'Preparando', 'Listo')
    $filtro_reservas
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
            "tipo_pedido" => $pedido["tipo_pedido"],
            "destino_produccion" => pedido_destino_produccion($pedido),
            "estado" => $pedido["estado"],
            "tipo_reserva" => $pedido["tipo_reserva"] ?? "Ninguna",
            "estado_texto" => pedido_estado_etiqueta($pedido["estado"]),
            "observaciones" => $pedido["observaciones"] ?? "",
            "fecha_hora_inicio" => $pedido["fecha_hora_inicio"],
            "horario_entrega" => $pedido["horario_entrega"] ?? null,
            "horario_entrega_texto" => !empty($pedido["horario_entrega"]) ? date("d/m H:i", strtotime($pedido["horario_entrega"])) : "",
            "hora" => date("H:i", strtotime($pedido["fecha_hora_inicio"])),
            "minutos_transcurridos" => pedido_minutos_transcurridos($pedido["fecha_hora_inicio"]),
            "productos" => []
        ];

        $ids[] = $id_pedido;
    }
}

if (count($ids) > 0) {
    $ids_sql = implode(",", array_map("intval", $ids));
    $campo_observaciones = columna_existe($conexion, "detalle_pedido", "observaciones")
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
