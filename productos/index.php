<?php

require_once "../php/conexion.php";

// Consulta todos los productos junto con su categoría
$sql = "SELECT productos.*, categorias.nombre AS categoria
        FROM productos
        LEFT JOIN categorias
        ON productos.id_categoria = categorias.id_categoria
        ORDER BY productos.nombre";

$resultado = mysqli_query($conexion, $sql);

?>

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<title>Productos | GenesisBar</title>

<style>

body{
    font-family:Arial;
    background:#f2f2f2;
    margin:30px;
}

h1{
    text-align:center;
}

table{
    width:100%;
    border-collapse:collapse;
    background:white;
}

th{
    background:#222;
    color:white;
    padding:10px;
}

td{
    padding:10px;
    border-bottom:1px solid #ddd;
}

tr:hover{
    background:#f7f7f7;
}

.boton{

    display:inline-block;

    padding:10px 20px;

    background:#28a745;

    color:white;

    text-decoration:none;

    border-radius:5px;

    margin-bottom:20px;

}

.acciones a{

    text-decoration:none;

    margin-right:10px;

}

</style>

</head>

<body>

<h1>Productos</h1>

<a class="boton" href="agregar.php">

➕ Nuevo Producto

</a>

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

<?php while($fila=mysqli_fetch_assoc($resultado)){ ?>

<tr>

<td><?= $fila['id_producto']; ?></td>

<td><?= $fila['nombre']; ?></td>

<td><?= $fila['categoria']; ?></td>

<td>$ <?= number_format($fila['precio'],2,',','.'); ?></td>

<td><?= $fila['stock']; ?></td>

<td>

<?php

if($fila['activo']){

    echo "Activo";

}else{

    echo "Inactivo";

}

?>

</td>

<td class="acciones">

<a href="editar.php?id=<?= $fila['id_producto']; ?>">✏️</a>

<a href="eliminar.php?id=<?= $fila['id_producto']; ?>">🗑️</a>

</td>

</tr>

<?php } ?>

</table>

<br>

<a href="../index.php">

⬅ Volver al menú

</a>

</body>

</html>