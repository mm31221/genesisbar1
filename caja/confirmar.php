<?php
date_default_timezone_set("America/Argentina/Buenos_Aires");

require_once("../config/config.php");

if (!isset($_POST["id_pedido"], $_POST["id_forma_pago"])) {
    die("Datos incompletos.");
}

$id_pedido = (int) $_POST["id_pedido"];
$id_forma_pago = (int) $_POST["id_forma_pago"];
$descuento = max(0, (float) ($_POST["descuento"] ?? 0));
$recargo = max(0, (float) ($_POST["recargo"] ?? 0));
$id_usuario_cobro = 1;
$fecha_cobro = date("Y-m-d H:i:s");

if ($id_pedido < 1 || $id_forma_pago < 1) {
    die("Datos invalidos.");
}

mysqli_begin_transaction($conexion);

try {
    $stmt = mysqli_prepare($conexion, "SELECT id_pedido, id_mesa, estado, total FROM pedidos WHERE id_pedido = ? FOR UPDATE");

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

    if ($pedido["estado"] === "Cobrado") {
        throw new Exception("Este pedido ya fue cobrado.");
    }

    if (!in_array($pedido["estado"], ["Listo", "Entregado"], true)) {
        throw new Exception("Solo se pueden cobrar pedidos listos o entregados.");
    }

    $subtotal = (float) $pedido["total"];
    $total_final = max(0, $subtotal - $descuento + $recargo);

    $stmt = mysqli_prepare($conexion, "UPDATE pedidos SET
            estado = 'Cobrado',
            id_forma_pago = ?,
            descuento = ?,
            recargo = ?,
            total_final = ?,
            fecha_hora_cobro = ?,
            id_usuario_cobro = ?
        WHERE id_pedido = ? AND estado <> 'Cobrado'");

    if (!$stmt) {
        throw new Exception("No se pudo preparar el cobro.");
    }

    mysqli_stmt_bind_param(
        $stmt,
        "idddsii",
        $id_forma_pago,
        $descuento,
        $recargo,
        $total_final,
        $fecha_cobro,
        $id_usuario_cobro,
        $id_pedido
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("No se pudo registrar el cobro.");
    }

    mysqli_stmt_close($stmt);

    $concepto = "Cobro de pedido #" . $id_pedido;
    $tipo = "Ingreso";
    $stmt = mysqli_prepare($conexion, "INSERT INTO movimientos_caja (
            id_pedido,
            tipo,
            concepto,
            monto,
            id_forma_pago,
            id_usuario,
            fecha_hora,
            observaciones
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NULL)");

    if ($stmt) {
        mysqli_stmt_bind_param(
            $stmt,
            "issdiis",
            $id_pedido,
            $tipo,
            $concepto,
            $total_final,
            $id_forma_pago,
            $id_usuario_cobro,
            $fecha_cobro
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    if (!empty($pedido["id_mesa"])) {
        $id_mesa = (int) $pedido["id_mesa"];
        $stmt = mysqli_prepare($conexion, "UPDATE mesas SET estado = 'Libre' WHERE id_mesa = ?");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $id_mesa);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    mysqli_commit($conexion);
    header("Location: ticket.php?id=" . $id_pedido);
    exit;
} catch (Exception $e) {
    mysqli_rollback($conexion);
    die($e->getMessage());
}
?>
