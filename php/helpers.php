<?php

function texto_tipo_pedido($tipo)
{
    $tipos = [
        "delivery" => "Delivery",
        "salon" => "Mesa en salon",
        "take_away" => "Take away"
    ];

    return $tipos[$tipo] ?? "Mesa en salon";
}

function detalle_entrega($fila)
{
    $tipo = $fila["tipo_pedido"] ?? "salon";

    if ($tipo === "delivery") {
        return trim($fila["direccion"] ?? "") !== "" ? $fila["direccion"] : "Sin direccion";
    }

    if ($tipo === "salon") {
        return trim($fila["mesa"] ?? "") !== "" ? "Mesa " . $fila["mesa"] : "Salon";
    }

    return "Retira en mostrador";
}

function estado_cliente($estado, $tipo)
{
    if ($estado === "Entregado") {
        return "Entregado";
    }

    if ($tipo === "delivery") {
        return "En cocina, preparando para delivery";
    }

    if ($tipo === "take_away") {
        return "En cocina, preparando para retirar";
    }

    return "En cocina, preparando para la mesa";
}

?>
