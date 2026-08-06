<?php
require_once("../php/conexion.php");

header("Content-Type: application/json; charset=utf-8");

$sql = "SELECT
            categorias.id_categoria,
            categorias.nombre,
            COUNT(productos.id_producto) AS cantidad
        FROM categorias
        LEFT JOIN productos
            ON productos.id_categoria = categorias.id_categoria
            AND productos.activo = 1
        GROUP BY categorias.id_categoria, categorias.nombre
        ORDER BY categorias.nombre ASC";

$resultado = mysqli_query($conexion, $sql);
$categorias = [];

if ($resultado) {
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $categorias[] = [
            "id_categoria" => (int) $fila["id_categoria"],
            "nombre" => $fila["nombre"],
            "cantidad" => (int) $fila["cantidad"]
        ];
    }
}

echo json_encode([
    "ok" => true,
    "categorias" => $categorias
], JSON_UNESCAPED_UNICODE);
?>
