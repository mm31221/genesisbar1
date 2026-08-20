<?php

function pedido_estados_operativos()
{
    return ["Pendiente", "Preparando", "Listo", "Entregado"];
}

function pedido_estados_activos()
{
    return ["Pendiente", "Preparando", "Listo"];
}

function pedido_estados_seguimiento()
{
    return ["Pendiente", "Preparando", "Listo", "Entregado"];
}

function pedido_estado_etiqueta($estado)
{
    $etiquetas = [
        "Pendiente" => "Pendiente",
        "Preparando" => "En preparacion",
        "Listo" => "Listo",
        "Entregado" => "Entregado",
        "Cobrado" => "Cobrado",
        "Cancelado" => "Cancelado"
    ];

    return $etiquetas[$estado] ?? (string) $estado;
}

function pedido_siguiente_estado($estado)
{
    $siguientes = [
        "Pendiente" => "Preparando",
        "Preparando" => "Listo",
        "Listo" => "Entregado"
    ];

    return $siguientes[$estado] ?? "";
}

function pedido_estados_permitidos_desde($estado)
{
    $estado = (string) $estado;
    $siguiente = pedido_siguiente_estado($estado);

    if ($siguiente === "") {
        return [$estado];
    }

    return [$estado, $siguiente];
}

function pedido_estado_bloquea_cambios($estado)
{
    return in_array($estado, ["Entregado", "Cobrado", "Cancelado"], true);
}

function pedido_transicion_valida($estado_actual, $estado_nuevo, &$mensaje = "")
{
    $estado_actual = (string) $estado_actual;
    $estado_nuevo = (string) $estado_nuevo;

    if (!in_array($estado_nuevo, pedido_estados_operativos(), true)) {
        $mensaje = "Estado invalido.";
        return false;
    }

    if ($estado_actual === $estado_nuevo) {
        return true;
    }

    if (pedido_estado_bloquea_cambios($estado_actual)) {
        $mensaje = "Este pedido ya no admite cambios de estado.";
        return false;
    }

    $siguiente = pedido_siguiente_estado($estado_actual);

    if ($siguiente === "" || $estado_nuevo !== $siguiente) {
        $mensaje = "El cambio debe seguir el flujo: Pendiente, Preparando, Listo y Entregado.";
        return false;
    }

    return true;
}

function pedido_destino_produccion($pedido)
{
    $tipo = $pedido["tipo_pedido"] ?? "";

    if ($tipo === "Mesa") {
        $mesa = trim((string) ($pedido["numero_mesa"] ?? ""));

        if ($mesa === "") {
            $mesa = trim((string) ($pedido["mesa"] ?? ""));
        }

        return $mesa !== "" ? "Mesa " . $mesa : "Mesa";
    }

    if ($tipo === "Take Away") {
        return "Retiro";
    }

    if ($tipo === "Delivery") {
        return "Delivery";
    }

    return "";
}

function pedido_minutos_transcurridos($fecha)
{
    $timestamp = strtotime((string) $fecha);

    if (!$timestamp) {
        return 0;
    }

    return max(0, (int) floor((time() - $timestamp) / 60));
}

?>
