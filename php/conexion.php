<?php

$servidor = "localhost";
$usuario = "root";
$password = "";
$base_datos = "genesisbar1";

mysqli_report(MYSQLI_REPORT_OFF);

$conexion = @mysqli_connect(
    $servidor,
    $usuario,
    $password,
    $base_datos
);

if (!$conexion) {
    die("Error de conexion: " . mysqli_connect_error());
}

mysqli_set_charset($conexion, "utf8mb4");

function tabla_existe($conexion, $tabla)
{
    $tabla = mysqli_real_escape_string($conexion, $tabla);
    $resultado = mysqli_query($conexion, "SHOW TABLES LIKE '$tabla'");

    return $resultado && mysqli_num_rows($resultado) > 0;
}

function columna_existe($conexion, $tabla, $columna)
{
    $tabla = mysqli_real_escape_string($conexion, $tabla);
    $columna = mysqli_real_escape_string($conexion, $columna);
    $resultado = mysqli_query($conexion, "SHOW COLUMNS FROM `$tabla` LIKE '$columna'");

    return $resultado && mysqli_num_rows($resultado) > 0;
}

function asegurar_columna($conexion, $tabla, $columna, $definicion)
{
    if (!columna_existe($conexion, $tabla, $columna)) {
        mysqli_query($conexion, "ALTER TABLE `$tabla` ADD COLUMN `$columna` $definicion");
    }
}

function preparar_base_genesis($conexion)
{
    mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS clientes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        telefono VARCHAR(40) NULL,
        email VARCHAR(120) NULL,
        direccion VARCHAR(180) NULL,
        fecha_alta DATETIME NOT NULL,
        UNIQUE KEY clientes_telefono_unico (telefono),
        UNIQUE KEY clientes_email_unico (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    if (tabla_existe($conexion, "pedidos")) {
        if (columna_existe($conexion, "pedidos", "id_pedido")) {
            asegurar_columna($conexion, "pedidos", "numero_pedido", "VARCHAR(30) NULL AFTER id_pedido");
            asegurar_columna($conexion, "pedidos", "origen", "VARCHAR(30) NOT NULL DEFAULT 'Pagina web' AFTER numero_pedido");
            asegurar_columna($conexion, "pedidos", "direccion_entrega", "VARCHAR(180) NULL AFTER mesa");
            asegurar_columna($conexion, "pedidos", "descuento", "DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER total");
            asegurar_columna($conexion, "pedidos", "recargo", "DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER descuento");
            asegurar_columna($conexion, "pedidos", "total_final", "DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER recargo");
            asegurar_columna($conexion, "pedidos", "fecha_hora_cobro", "DATETIME NULL AFTER fecha_hora_entrega");
            asegurar_columna($conexion, "pedidos", "id_usuario_cobro", "INT NULL AFTER id_usuario");
        } else {
            asegurar_columna($conexion, "pedidos", "cliente_id", "INT NULL AFTER id");
            asegurar_columna($conexion, "pedidos", "numero_pedido", "VARCHAR(30) NULL AFTER cliente_id");
            asegurar_columna($conexion, "pedidos", "origen", "VARCHAR(30) NOT NULL DEFAULT 'Pagina web' AFTER numero_pedido");
            asegurar_columna($conexion, "pedidos", "nombre_cliente", "VARCHAR(100) NOT NULL DEFAULT '' AFTER cliente_id");
            asegurar_columna($conexion, "pedidos", "telefono_cliente", "VARCHAR(40) NOT NULL DEFAULT '' AFTER nombre_cliente");
            asegurar_columna($conexion, "pedidos", "email_cliente", "VARCHAR(120) NULL AFTER telefono_cliente");
            asegurar_columna($conexion, "pedidos", "direccion", "VARCHAR(180) NULL AFTER email_cliente");
            asegurar_columna($conexion, "pedidos", "direccion_entrega", "VARCHAR(180) NULL AFTER mesa");
            asegurar_columna($conexion, "pedidos", "tipo_pedido", "VARCHAR(20) NOT NULL DEFAULT 'salon' AFTER direccion");
            asegurar_columna($conexion, "pedidos", "mesa", "VARCHAR(30) NULL AFTER tipo_pedido");
        }
    }

    if (tabla_existe($conexion, "detalle_pedido")) {
        asegurar_columna($conexion, "detalle_pedido", "observaciones", "TEXT NULL AFTER subtotal");
    }

    if (tabla_existe($conexion, "categorias")) {
        preparar_categorias_pedidos($conexion);
    }

    mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS movimientos_caja (
        id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
        id_pedido INT NULL,
        tipo ENUM('Ingreso','Egreso','Apertura','Cierre') NOT NULL DEFAULT 'Ingreso',
        concepto VARCHAR(150) NOT NULL,
        monto DECIMAL(10,2) NOT NULL DEFAULT 0,
        id_forma_pago INT NULL,
        id_usuario INT NULL,
        fecha_hora DATETIME NOT NULL,
        observaciones TEXT NULL,
        KEY movimientos_pedido (id_pedido),
        KEY movimientos_fecha (fecha_hora)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function id_categoria_por_nombre($conexion, $nombre)
{
    $stmt = mysqli_prepare($conexion, "SELECT id_categoria FROM categorias WHERE nombre = ? LIMIT 1");
    $id_categoria = null;

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $nombre);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $id_categoria);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    }

    return $id_categoria ? (int) $id_categoria : null;
}

function asegurar_categoria($conexion, $nombre)
{
    $id_categoria = id_categoria_por_nombre($conexion, $nombre);

    if ($id_categoria) {
        return $id_categoria;
    }

    $stmt = mysqli_prepare($conexion, "INSERT INTO categorias (nombre) VALUES (?)");

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $nombre);
        mysqli_stmt_execute($stmt);
        $id_categoria = mysqli_insert_id($conexion);
        mysqli_stmt_close($stmt);
    }

    return $id_categoria ? (int) $id_categoria : null;
}

function preparar_categorias_pedidos($conexion)
{
    $categorias_validas = ["Pizza", "Sushi", "Empanadas", "Bebidas", "Tragos"];

    mysqli_query($conexion, "UPDATE categorias SET nombre = 'Pizza' WHERE nombre = 'Pizzas'");

    foreach ($categorias_validas as $categoria) {
        asegurar_categoria($conexion, $categoria);
    }

    $id_sushi = id_categoria_por_nombre($conexion, "Sushi");
    $id_bebidas = id_categoria_por_nombre($conexion, "Bebidas");

    if ($id_sushi) {
        $stmt = mysqli_prepare($conexion, "UPDATE productos SET id_categoria = ? WHERE LOWER(nombre) LIKE '%combinado%'");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $id_sushi);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    if ($id_bebidas) {
        $id_cervezas = id_categoria_por_nombre($conexion, "Cervezas");

        if ($id_cervezas) {
            $stmt = mysqli_prepare($conexion, "UPDATE productos SET id_categoria = ? WHERE id_categoria = ?");

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ii", $id_bebidas, $id_cervezas);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
    }

    $permitidas_sql = "'" . implode("','", array_map(function ($categoria) use ($conexion) {
        return mysqli_real_escape_string($conexion, $categoria);
    }, $categorias_validas)) . "'";

    mysqli_query($conexion, "DELETE categorias FROM categorias
        LEFT JOIN productos ON productos.id_categoria = categorias.id_categoria
        WHERE categorias.nombre NOT IN ($permitidas_sql)
        AND productos.id_producto IS NULL");
}

function guardar_cliente($conexion, $nombre, $telefono = "", $email = "", $direccion = "")
{
    $nombre = trim($nombre);
    $telefono = trim($telefono);
    $email = trim($email);
    $direccion = trim($direccion);

    if ($telefono === "" && $email === "") {
        return null;
    }

    $id_cliente = null;
    $sql_buscar = "SELECT id FROM clientes
        WHERE (telefono = ? AND ? <> '') OR (email = ? AND ? <> '')
        LIMIT 1";
    $stmt = mysqli_prepare($conexion, $sql_buscar);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssss", $telefono, $telefono, $email, $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $id_cliente);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    }

    if ($id_cliente) {
        $sql_actualizar = "UPDATE clientes
            SET nombre = ?, telefono = NULLIF(?, ''), email = NULLIF(?, ''), direccion = NULLIF(?, '')
            WHERE id = ?";
        $stmt = mysqli_prepare($conexion, $sql_actualizar);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssssi", $nombre, $telefono, $email, $direccion, $id_cliente);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        return (int) $id_cliente;
    }

    $email_db = ($email !== "") ? $email : null;
    $direccion_db = ($direccion !== "") ? $direccion : null;
    $fecha_alta = date("Y-m-d H:i:s");

    $sql_insertar = "INSERT INTO clientes (nombre, telefono, email, direccion, fecha_alta)
        VALUES (?, NULLIF(?, ''), ?, ?, ?)";
    $stmt = mysqli_prepare($conexion, $sql_insertar);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssss", $nombre, $telefono, $email_db, $direccion_db, $fecha_alta);

        if (mysqli_stmt_execute($stmt)) {
            $id_cliente = mysqli_insert_id($conexion);
        }

        mysqli_stmt_close($stmt);
    }

    return $id_cliente ? (int) $id_cliente : null;
}

preparar_base_genesis($conexion);

?>
