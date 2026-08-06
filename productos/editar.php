<?php

require_once "../php/conexion.php";
require_once "../includes/header.php";

if (!isset($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$id = (int) $_GET["id"];

// Si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = trim($_POST["nombre"]);
    $descripcion = trim($_POST["descripcion"]);
    $precio = $_POST["precio"];
    $stock = $_POST["stock"];
    $id_categoria = $_POST["id_categoria"];
    $activo = isset($_POST["activo"]) ? 1 : 0;

    $sql = "UPDATE productos
            SET
                nombre=?,
                descripcion=?,
                precio=?,
                stock=?,
                activo=?,
                id_categoria=?
            WHERE id_producto=?";

    $stmt = mysqli_prepare($conexion, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "ssdiiii",
        $nombre,
        $descripcion,
        $precio,
        $stock,
        $activo,
        $id_categoria,
        $id
    );

    mysqli_stmt_execute($stmt);

    header("Location: index.php");
    exit;
}

// Obtener datos del producto
$sql = "SELECT * FROM productos WHERE id_producto=?";

$stmt = mysqli_prepare($conexion, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);

$producto = mysqli_fetch_assoc($resultado);

if (!$producto) {

    echo "<h2>Producto no encontrado.</h2>";

    require_once "../includes/footer.php";

    exit;
}

// Obtener categorías
$categorias = mysqli_query(
    $conexion,
    "SELECT * FROM categorias ORDER BY nombre"
);

?>

<h2>✏ Editar Producto</h2>

<form method="POST">

<p><strong>Nombre</strong></p>

<input
type="text"
name="nombre"
value="<?= htmlspecialchars($producto["nombre"]) ?>"
required
style="width:100%;padding:10px;"
>

<br><br>

<p><strong>Descripción</strong></p>

<textarea
name="descripcion"
rows="4"
style="width:100%;padding:10px;"
><?= htmlspecialchars($producto["descripcion"]) ?></textarea>

<br><br>

<p><strong>Categoría</strong></p>

<select
name="id_categoria"
style="width:100%;padding:10px;"
required>

<?php

while($cat=mysqli_fetch_assoc($categorias)){

?>

<option
value="<?= $cat["id_categoria"] ?>"

<?php

if($cat["id_categoria"]==$producto["id_categoria"]){

    echo "selected";

}

?>

>

<?= htmlspecialchars($cat["nombre"]) ?>

</option>

<?php

}

?>

</select>

<br><br>

<p><strong>Precio</strong></p>

<input
type="number"
step="0.01"
name="precio"
value="<?= $producto["precio"] ?>"
required
style="width:100%;padding:10px;"
>

<br><br>

<p><strong>Stock</strong></p>

<input
type="number"
name="stock"
value="<?= $producto["stock"] ?>"
style="width:100%;padding:10px;"
>

<br><br>

<label>

<input
type="checkbox"
name="activo"

<?php

if($producto["activo"]){

    echo "checked";

}

?>

>

Producto activo

</label>

<br><br>

<button
type="submit"
class="boton">

💾 Guardar Cambios

</button>

<a
href="index.php"
class="boton">

← Cancelar

</a>

</form>

<?php

require_once "../includes/footer.php";

?>