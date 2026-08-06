<?php

require_once "../php/conexion.php";
require_once "../includes/header.php";

// Consulta los productos junto con el nombre de su categoría
$sql = "SELECT
            productos.id_producto,
            productos.nombre,
            categorias.nombre AS categoria,
            productos.precio,
            productos.stock,
            productos.activo
        FROM productos
        LEFT JOIN categorias
        ON productos.id_categoria = categorias.id_categoria
        ORDER BY productos.nombre ASC";

$resultado = mysqli_query($conexion, $sql);

?>

<h2>📋 Productos</h2>

<a href="agregar.php" class="boton">
    ➕ Nuevo Producto
</a>

<br><br>

<table>

    <tr>

        <th>ID</th>

        <th>Producto</th>

        <th>Categoría</th>

        <th>Precio</th>

        <th>Stock</th>

        <th>Estado</th>

        <th>Acciones</th>

    </tr>

<?php

if(mysqli_num_rows($resultado)>0){

    while($producto=mysqli_fetch_assoc($resultado)){

?>

<tr>

    <td><?= $producto["id_producto"] ?></td>

    <td><?= htmlspecialchars($producto["nombre"]) ?></td>

    <td><?= htmlspecialchars($producto["categoria"] ?? "Sin categoría") ?></td>

    <td>$ <?= number_format($producto["precio"],2,",",".") ?></td>

    <td><?= $producto["stock"] ?></td>

    <td>

        <?php

        if($producto["activo"]){

            echo "🟢 Activo";

        }else{

            echo "🔴 Inactivo";

        }

        ?>

    </td>

    <td>

        <a href="editar.php?id=<?= $producto["id_producto"] ?>">✏️ Editar</a>

        |

        <a
        href="eliminar.php?id=<?= $producto["id_producto"] ?>"
        onclick="return confirm('¿Eliminar este producto?')">

        🗑 Eliminar

        </a>

    </td>

</tr>

<?php

    }

}else{

?>

<tr>

<td colspan="7">

No hay productos cargados.

</td>

</tr>

<?php

}

?>

</table>

<?php

require_once "../includes/footer.php";

?>