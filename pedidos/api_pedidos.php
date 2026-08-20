<?php
date_default_timezone_set("America/Argentina/Buenos_Aires");

require_once("../config/config.php");
require_once("../php/seguridad.php");
require_once("../php/pedidos_estados.php");

header("Content-Type: application/json; charset=utf-8");

if (!empleado_tiene_permiso(empleado_actual($conexion), "pedidos")) {
    http_response_code(403);
    echo json_encode(["ok" => false, "mensaje" => "No autorizado."], JSON_UNESCAPED_UNICODE);
    exit;
}

function texto_numero_pedido($pedido)
{
    $numero = trim($pedido["numero_pedido"] ?? "");
    return $numero !== "" ? $numero : "#" . $pedido["id_pedido"];
}

$estados_activos = pedido_estados_activos();
$estados_validos = array_merge(["Todos"], $estados_activos);
$estado = trim($_GET["estado"] ?? "Todos");

if (!in_array($estado, $estados_validos, true)) {
    $estado = "Todos";
}

$contadores = ["Todos" => 0];

foreach ($estados_activos as $estado_contador) {
    $contadores[$estado_contador] = 0;
}

$estados_sql = "'" . implode("','", array_map(function ($item) use ($conexion) {
    return mysqli_real_escape_string($conexion, $item);
}, $estados_activos)) . "'";
$resultado_contadores = mysqli_query($conexion, "SELECT estado, COUNT(*) AS total FROM pedidos WHERE estado IN ($estados_sql) GROUP BY estado");

if ($resultado_contadores) {
    while ($fila = mysqli_fetch_assoc($resultado_contadores)) {
        if (isset($contadores[$fila["estado"]])) {
            $contadores[$fila["estado"]] = (int) $fila["total"];
            $contadores["Todos"] += (int) $fila["total"];
        }
    }
}

$where = "WHERE pedidos.estado IN ($estados_sql)";
$campo_horario_entrega = columna_existe($conexion, "pedidos", "horario_entrega")
    ? "pedidos.horario_entrega"
    : "NULL";
$campo_direccion_calle = columna_existe($conexion, "pedidos", "direccion_calle")
    ? "pedidos.direccion_calle"
    : "NULL";
$campo_direccion_altura = columna_existe($conexion, "pedidos", "direccion_altura")
    ? "pedidos.direccion_altura"
    : "NULL";
$campo_tipo_reserva = columna_existe($conexion, "pedidos", "tipo_reserva")
    ? "pedidos.tipo_reserva"
    : "'Ninguna'";

if ($estado !== "Todos") {
    $where = "WHERE pedidos.estado = ?";
}

$sql = "SELECT
        pedidos.id_pedido,
        pedidos.numero_pedido,
        pedidos.origen,
        pedidos.tipo_pedido,
        $campo_tipo_reserva AS tipo_reserva,
        pedidos.id_mesa,
        pedidos.mesa,
        pedidos.direccion_entrega,
        $campo_direccion_calle AS direccion_calle,
        $campo_direccion_altura AS direccion_altura,
        pedidos.estado,
        pedidos.estado_pago,
        pedidos.total,
        pedidos.observaciones,
        pedidos.fecha_hora_inicio,
        $campo_horario_entrega AS horario_entrega,
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
            "tipo_reserva" => $pedido["tipo_reserva"] ?? "Ninguna",
            "mesa" => trim($pedido["numero_mesa"] ?? "") !== "" ? $pedido["numero_mesa"] : ($pedido["mesa"] ?? ""),
            "direccion_calle" => $pedido["direccion_calle"] ?? "",
            "direccion_altura" => $pedido["direccion_altura"] ?? "",
            "direccion_entrega" => direccion_compuesta($pedido["direccion_calle"] ?? "", $pedido["direccion_altura"] ?? "", $pedido["direccion_entrega"] ?? ""),
            "estado" => $pedido["estado"],
            "estado_pago" => $pedido["estado_pago"] ?? "Pendiente",
            "total" => (float) $pedido["total"],
            "observaciones" => $pedido["observaciones"] ?? "",
            "fecha_hora_inicio" => $pedido["fecha_hora_inicio"],
            "horario_entrega" => $pedido["horario_entrega"] ?? null,
            "horario_entrega_texto" => !empty($pedido["horario_entrega"]) ? date("d/m H:i", strtotime($pedido["horario_entrega"])) : "",
            "hora" => date("H:i", strtotime($pedido["fecha_hora_inicio"])),
            "minutos_transcurridos" => pedido_minutos_transcurridos($pedido["fecha_hora_inicio"]),
            "estado_texto" => pedido_estado_etiqueta($pedido["estado"]),
            "estados_permitidos" => pedido_estados_permitidos_desde($pedido["estado"]),
            "siguiente_estado" => pedido_siguiente_estado($pedido["estado"]),
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
