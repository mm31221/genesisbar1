<?php
date_default_timezone_set("America/Argentina/Buenos_Aires");

require_once("../config/config.php");
require_once("funciones.php");
require_once("../php/seguridad.php");
require_once("../php/puntos.php");
requerir_permiso($conexion, "caja");

if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
    die("Metodo no permitido.");
}

if (!validar_csrf($_POST["csrf_token"] ?? "")) {
    die("La sesion vencio. Volve a intentar.");
}

$id_pedido = isset($_POST["id_pedido"]) ? (int) $_POST["id_pedido"] : 0;
$id_forma_pago_1 = isset($_POST["id_forma_pago_1"]) ? (int) $_POST["id_forma_pago_1"] : 0;
$id_forma_pago_2 = isset($_POST["id_forma_pago_2"]) ? (int) $_POST["id_forma_pago_2"] : 0;
$monto_pago_2 = isset($_POST["monto_pago_2"]) && $_POST["monto_pago_2"] !== "" ? round((float) $_POST["monto_pago_2"], 2) : 0;
$dinero_recibido = isset($_POST["dinero_recibido"]) && $_POST["dinero_recibido"] !== "" ? (float) $_POST["dinero_recibido"] : 0;
$empleado = empleado_actual($conexion);
$id_usuario_cobro = $empleado ? (int) $empleado["id_usuario"] : null;
$fecha_cobro = date("Y-m-d H:i:s");

if ($id_pedido < 1) {
    die("Datos invalidos.");
}

if ($dinero_recibido < 0 || $monto_pago_2 < 0) {
    die("Los importes no pueden ser negativos.");
}

mysqli_begin_transaction($conexion);

try {
    $stmt = mysqli_prepare($conexion, "SELECT
            id_pedido,
            id_cliente,
            id_mesa,
            estado,
            estado_pago,
            tipo_pedido,
            total,
            total_final,
            fecha_hora_entrega
        FROM pedidos
        WHERE id_pedido = ?
        FOR UPDATE");

    if (!$stmt) {
        throw new Exception("No se pudo consultar el pedido.");
    }

    mysqli_stmt_bind_param($stmt, "i", $id_pedido);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $pedido = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);

    if (!$pedido) {
        throw new Exception("Pedido inexistente.");
    }

    if ($pedido["estado"] === "Cobrado" || $pedido["estado_pago"] === "Pagado") {
        mysqli_commit($conexion);
        header("Location: ticket.php?id=" . $id_pedido);
        exit;
    }

    if (!caja_pedido_cobrable($pedido)) {
        throw new Exception("Este pedido no esta disponible para cobrar.");
    }

    $total_final = round(caja_total_pedido($pedido), 2);
    $forma_pago_1 = caja_forma_pago_por_id($conexion, $id_forma_pago_1);

    if (!$forma_pago_1) {
        throw new Exception("Selecciona una forma de pago valida.");
    }

    if ($id_forma_pago_2 > 0 && $monto_pago_2 <= 0) {
        throw new Exception("Carga el importe de la segunda forma de pago.");
    }

    if ($monto_pago_2 > 0 && $id_forma_pago_2 < 1) {
        throw new Exception("Selecciona la segunda forma de pago.");
    }

    if ($monto_pago_2 >= $total_final) {
        throw new Exception("La segunda forma debe ser menor al total para dejar importe en la forma principal.");
    }

    $pagos_recibidos = [
        [
            "id_forma_pago" => $id_forma_pago_1,
            "monto" => round($total_final - $monto_pago_2, 2),
            "forma" => $forma_pago_1
        ]
    ];

    if ($monto_pago_2 > 0) {
        $forma_pago_2 = caja_forma_pago_por_id($conexion, $id_forma_pago_2);

        if (!$forma_pago_2) {
            throw new Exception("Selecciona la segunda forma de pago.");
        }

        $pagos_recibidos[] = [
            "id_forma_pago" => $id_forma_pago_2,
            "monto" => $monto_pago_2,
            "forma" => $forma_pago_2
        ];
    }

    $movimientos_pago = [];
    $total_pagado = 0.0;
    $monto_efectivo = 0.0;
    $id_forma_pago_principal = $id_forma_pago_1;

    foreach ($pagos_recibidos as $pago_recibido) {
        $monto = round((float) $pago_recibido["monto"], 2);
        $id_forma_pago = (int) $pago_recibido["id_forma_pago"];
        $forma = $pago_recibido["forma"];

        if ($monto <= 0) {
            throw new Exception("Carga al menos un importe de pago.");
        }

        if (caja_forma_pago_es_efectivo_nombre($forma["nombre"])) {
            $monto_efectivo += $monto;
        }

        $total_pagado += $monto;
        $movimientos_pago[] = [
            "concepto" => "Cobro " . $forma["nombre"] . " de pedido #" . $id_pedido,
            "monto" => $monto,
            "id_forma_pago" => $id_forma_pago,
            "forma_pago" => $forma["nombre"]
        ];
    }

    $total_pagado = round($total_pagado, 2);
    $vuelto = $monto_efectivo > 0 ? round($dinero_recibido - $monto_efectivo, 2) : null;

    if ($total_pagado <= 0) {
        throw new Exception("Carga al menos un importe de pago.");
    }

    if (abs($total_pagado - round($total_final, 2)) > 0.01) {
        throw new Exception("El pago combinado debe coincidir con el total del pedido.");
    }

    if ($monto_efectivo > 0 && $dinero_recibido < $monto_efectivo) {
        throw new Exception("El dinero recibido en efectivo no alcanza el monto en efectivo.");
    }

    $fecha_entrega_sql = "fecha_hora_entrega";
    $fecha_entrega = $pedido["fecha_hora_entrega"];

    if ($pedido["estado"] === "Entregado" && (!$fecha_entrega || $fecha_entrega === "0000-00-00 00:00:00")) {
        $fecha_entrega_sql = "?";
        $fecha_entrega = $fecha_cobro;
    }

    $sql_update = "UPDATE pedidos SET
            estado_pago = 'Pagado',
            id_forma_pago = ?,
            total_final = ?,
            fecha_hora_entrega = $fecha_entrega_sql,
            fecha_hora_cobro = ?,
            id_usuario_cobro = ?
        WHERE id_pedido = ? AND estado_pago = 'Pendiente'";

    $stmt_update = mysqli_prepare($conexion, $sql_update);

    if (!$stmt_update) {
        throw new Exception("No se pudo preparar el cobro.");
    }

    if ($fecha_entrega_sql === "?") {
        mysqli_stmt_bind_param(
            $stmt_update,
            "idssii",
            $id_forma_pago_principal,
            $total_final,
            $fecha_entrega,
            $fecha_cobro,
            $id_usuario_cobro,
            $id_pedido
        );
    } else {
        mysqli_stmt_bind_param(
            $stmt_update,
            "idsii",
            $id_forma_pago_principal,
            $total_final,
            $fecha_cobro,
            $id_usuario_cobro,
            $id_pedido
        );
    }

    if (!mysqli_stmt_execute($stmt_update)) {
        throw new Exception("No se pudo registrar el cobro.");
    }

    if (mysqli_stmt_affected_rows($stmt_update) !== 1) {
        throw new Exception("El pedido ya fue cobrado o no se pudo actualizar.");
    }

    mysqli_stmt_close($stmt_update);

    $puntos_ganados = puntos_por_importe($total_final);
    $puntos_insertados = false;

    if (!empty($pedido["id_cliente"]) && $puntos_ganados > 0) {
        $id_cliente = (int) $pedido["id_cliente"];
        if (tabla_existe($conexion, "puntos_movimientos")) {
            $tipo_puntos = "acreditacion";
            $descripcion_puntos = "Acreditacion por pedido #" . $id_pedido;
            $stmt_mov_puntos = mysqli_prepare($conexion, "INSERT INTO puntos_movimientos (
                    id_cliente,
                    id_pedido,
                    tipo,
                    puntos,
                    descripcion,
                    fecha,
                    id_usuario
                ) VALUES (?, ?, ?, ?, ?, ?, ?)");

            if (!$stmt_mov_puntos) {
                throw new Exception("No se pudo registrar el movimiento de puntos.");
            }

            mysqli_stmt_bind_param(
                $stmt_mov_puntos,
                "iisissi",
                $id_cliente,
                $id_pedido,
                $tipo_puntos,
                $puntos_ganados,
                $descripcion_puntos,
                $fecha_cobro,
                $id_usuario_cobro
            );
            if (!mysqli_stmt_execute($stmt_mov_puntos)) {
                if (mysqli_errno($conexion) === 1062) {
                    $puntos_insertados = false;
                } else {
                    throw new Exception("No se pudo registrar el movimiento de puntos.");
                }
            } else {
                $puntos_insertados = mysqli_stmt_affected_rows($stmt_mov_puntos) === 1;
            }

            mysqli_stmt_close($stmt_mov_puntos);

            if ($puntos_insertados) {
                $stmt_puntos = mysqli_prepare($conexion, "UPDATE clientes
                    SET puntos = puntos + ?, puntos_mes = puntos_mes + ?
                    WHERE id_cliente = ?");

                if (!$stmt_puntos) {
                    throw new Exception("No se pudieron sumar los puntos del cliente.");
                }

                mysqli_stmt_bind_param($stmt_puntos, "iii", $puntos_ganados, $puntos_ganados, $id_cliente);
                mysqli_stmt_execute($stmt_puntos);
                mysqli_stmt_close($stmt_puntos);
            }
        } else {
            $stmt_puntos = mysqli_prepare($conexion, "UPDATE clientes
                SET puntos = puntos + ?, puntos_mes = puntos_mes + ?
                WHERE id_cliente = ?");

            if (!$stmt_puntos) {
                throw new Exception("No se pudieron sumar los puntos del cliente.");
            }

            mysqli_stmt_bind_param($stmt_puntos, "iii", $puntos_ganados, $puntos_ganados, $id_cliente);
            mysqli_stmt_execute($stmt_puntos);
            mysqli_stmt_close($stmt_puntos);
        }
    }

    $tipo = "Ingreso";
    $observaciones = $puntos_ganados > 0 ? "Puntos generados: " . $puntos_ganados : null;
    $stmt_mov = mysqli_prepare($conexion, "INSERT INTO movimientos_caja (
            id_pedido,
            tipo,
            concepto,
            monto,
            dinero_recibido,
            vuelto,
            id_forma_pago,
            id_usuario,
            fecha_hora,
            observaciones
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt_mov) {
        throw new Exception("No se pudo registrar el movimiento de caja.");
    }

    foreach ($movimientos_pago as $movimiento_pago) {
        $concepto_movimiento = $movimiento_pago["concepto"];
        $monto_movimiento = $movimiento_pago["monto"];
        $id_forma_pago_movimiento = $movimiento_pago["id_forma_pago"];
        $es_efectivo_movimiento = caja_forma_pago_es_efectivo_nombre($movimiento_pago["forma_pago"] ?? "");
        $recibido_movimiento = $es_efectivo_movimiento ? $dinero_recibido : null;
        $vuelto_movimiento = $es_efectivo_movimiento ? $vuelto : null;

        mysqli_stmt_bind_param(
            $stmt_mov,
            "issdddiiss",
            $id_pedido,
            $tipo,
            $concepto_movimiento,
            $monto_movimiento,
            $recibido_movimiento,
            $vuelto_movimiento,
            $id_forma_pago_movimiento,
            $id_usuario_cobro,
            $fecha_cobro,
            $observaciones
        );

        if (!mysqli_stmt_execute($stmt_mov)) {
            throw new Exception("No se pudo registrar el movimiento de caja. Revisa la migracion de pagos mixtos.");
        }
    }

    mysqli_stmt_close($stmt_mov);

    if (!empty($pedido["id_mesa"])) {
        $id_mesa = (int) $pedido["id_mesa"];
        $stmt_mesa = mysqli_prepare($conexion, "UPDATE mesas SET estado = 'Libre' WHERE id_mesa = ?");

        if ($stmt_mesa) {
            mysqli_stmt_bind_param($stmt_mesa, "i", $id_mesa);
            mysqli_stmt_execute($stmt_mesa);
            mysqli_stmt_close($stmt_mesa);
        }
    }

    mysqli_commit($conexion);
    header("Location: ticket.php?id=" . $id_pedido . "&cobrado=1");
    exit;
} catch (Exception $e) {
    mysqli_rollback($conexion);
    die($e->getMessage());
}
?>
