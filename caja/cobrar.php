<?php
require_once("../config/config.php");
require_once("../includes/header.php");

if (!isset($_GET["id"])) {
    die("Pedido inexistente.");
}

$id_pedido = (int) $_GET["id"];

$sql = "SELECT
        pedidos.*,
        clientes.nombre AS nombre_cliente,
        clientes.telefono AS telefono_cliente,
        formas_pago.nombre AS forma_pago,
        mesas.numero AS numero_mesa
    FROM pedidos
    LEFT JOIN clientes ON clientes.id_cliente = pedidos.id_cliente
    LEFT JOIN formas_pago ON formas_pago.id_forma_pago = pedidos.id_forma_pago
    LEFT JOIN mesas ON mesas.id_mesa = pedidos.id_mesa
    WHERE pedidos.id_pedido = ?
    LIMIT 1";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_pedido);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$pedido = mysqli_fetch_assoc($resultado);
mysqli_stmt_close($stmt);

if (!$pedido) {
    die("Pedido no encontrado.");
}

if ($pedido["estado"] === "Cobrado") {
    die("Este pedido ya fue cobrado.");
}

$formas_pago = mysqli_query($conexion, "SELECT * FROM formas_pago ORDER BY nombre ASC");
$total = (float) $pedido["total"];
?>

<h2>Cobrar Pedido</h2>

<div class="tarjeta-pedido caja-cobro">

    <h3>Pedido <?= htmlspecialchars($pedido["numero_pedido"] ?: "#" . $pedido["id_pedido"]); ?></h3>

    <p><b>Tipo:</b> <?= htmlspecialchars($pedido["tipo_pedido"]); ?></p>

    <?php if ($pedido["tipo_pedido"] === "Mesa" && trim($pedido["numero_mesa"] ?: $pedido["mesa"]) !== "") { ?>
        <p><b>Mesa:</b> <?= htmlspecialchars($pedido["numero_mesa"] ?: $pedido["mesa"]); ?></p>
    <?php } ?>

    <?php if ($pedido["tipo_pedido"] !== "Mesa" && trim($pedido["nombre_cliente"] ?? "") !== "") { ?>
        <p><b>Cliente:</b> <?= htmlspecialchars($pedido["nombre_cliente"]); ?></p>
    <?php } ?>

    <?php if ($pedido["tipo_pedido"] !== "Mesa" && trim($pedido["telefono_cliente"] ?? "") !== "") { ?>
        <p><b>Telefono:</b> <?= htmlspecialchars($pedido["telefono_cliente"]); ?></p>
    <?php } ?>

    <hr>

    <h3>Productos</h3>

    <table class="tabla-caja-detalle">
        <tr>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Precio</th>
            <th>Subtotal</th>
        </tr>

        <?php
        $sql_detalle = "SELECT detalle_pedido.*, productos.nombre
            FROM detalle_pedido
            INNER JOIN productos ON productos.id_producto = detalle_pedido.id_producto
            WHERE detalle_pedido.id_pedido = ?
            ORDER BY detalle_pedido.id_detalle ASC";
        $stmt_detalle = mysqli_prepare($conexion, $sql_detalle);
        mysqli_stmt_bind_param($stmt_detalle, "i", $id_pedido);
        mysqli_stmt_execute($stmt_detalle);
        $detalle = mysqli_stmt_get_result($stmt_detalle);
        ?>

        <?php while ($item = mysqli_fetch_assoc($detalle)) { ?>
            <tr>
                <td><?= htmlspecialchars($item["nombre"]); ?></td>
                <td><?= (int) $item["cantidad"]; ?></td>
                <td>$<?= number_format((float) $item["precio_unitario"], 0, ",", "."); ?></td>
                <td>$<?= number_format((float) $item["subtotal"], 0, ",", "."); ?></td>
            </tr>
        <?php } ?>

        <?php mysqli_stmt_close($stmt_detalle); ?>
    </table>

    <hr>

    <form action="confirmar.php" method="POST" oninput="total_final.value = Math.max(0, Number(subtotal.value || 0) - Number(descuento.value || 0) + Number(recargo.value || 0)).toFixed(2)">

        <input type="hidden" name="id_pedido" value="<?= (int) $pedido["id_pedido"]; ?>">
        <input type="hidden" name="subtotal" value="<?= htmlspecialchars($total); ?>">

        <label>Subtotal</label>
        <input type="number" value="<?= htmlspecialchars($total); ?>" disabled>

        <label>Descuento</label>
        <input type="number" name="descuento" min="0" step="0.01" value="0">

        <label>Recargo</label>
        <input type="number" name="recargo" min="0" step="0.01" value="0">

        <label>Total final</label>
        <output name="total_final"><?= number_format($total, 2, ".", ""); ?></output>

        <label>Forma de Pago</label>
        <select name="id_forma_pago" required>
            <option value="">Seleccionar...</option>
            <?php while ($forma = mysqli_fetch_assoc($formas_pago)) { ?>
                <option value="<?= (int) $forma["id_forma_pago"]; ?>" <?= (int) $pedido["id_forma_pago"] === (int) $forma["id_forma_pago"] ? "selected" : ""; ?>>
                    <?= htmlspecialchars($forma["nombre"]); ?>
                </option>
            <?php } ?>
        </select>

        <button type="submit" class="boton" onclick="return confirm('Confirmar el cobro del pedido.')">
            Confirmar Cobro
        </button>

        <a href="index.php" class="boton boton-secundario-caja">Cancelar</a>

    </form>

</div>

<?php
require_once("../includes/footer.php");
?>
