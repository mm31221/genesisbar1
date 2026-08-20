<?php

function puntos_por_importe($importe)
{
    $base = defined("PUNTOS_POR_PESOS") ? (int) PUNTOS_POR_PESOS : 100;

    if ($base < 1) {
        $base = 100;
    }

    return (int) floor((float) $importe / $base);
}

function puntos_tipo_valido($tipo)
{
    return in_array($tipo, ["acreditacion", "canje", "ajuste", "anulacion"], true);
}

function puntos_saldo_cliente($conexion, $id_cliente)
{
    $stmt = mysqli_prepare($conexion, "SELECT puntos FROM clientes WHERE id_cliente = ? LIMIT 1");

    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, "i", $id_cliente);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $cliente = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);

    return (int) ($cliente["puntos"] ?? 0);
}

function puntos_registrar_movimiento($conexion, $id_cliente, $tipo, $puntos, $descripcion, $id_pedido = null, $id_usuario = null)
{
    $id_cliente = (int) $id_cliente;
    $id_pedido = $id_pedido ? (int) $id_pedido : null;
    $id_usuario = $id_usuario ? (int) $id_usuario : null;
    $puntos = (int) $puntos;
    $tipo = (string) $tipo;
    $descripcion = trim((string) $descripcion);
    $fecha = date("Y-m-d H:i:s");

    if ($id_cliente < 1 || !puntos_tipo_valido($tipo) || $puntos === 0) {
        return false;
    }

    $stmt = mysqli_prepare($conexion, "INSERT INTO puntos_movimientos
        (id_cliente, id_pedido, tipo, puntos, descripcion, fecha, id_usuario)
        VALUES (?, ?, ?, ?, NULLIF(?, ''), ?, ?)");

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "iisissi", $id_cliente, $id_pedido, $tipo, $puntos, $descripcion, $fecha, $id_usuario);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

?>
