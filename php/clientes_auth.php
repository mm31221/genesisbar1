<?php

function cliente_nombre_completo($nombre, $apellido)
{
    return trim(trim((string) $nombre) . " " . trim((string) $apellido));
}

function cliente_email_disponible($conexion, $email, $id_cliente_ignorar = 0)
{
    $email = email_normalizado($email);

    if ($email === "") {
        return false;
    }

    $sql = "SELECT id_cliente FROM clientes WHERE email = ?";
    $params = [$email];
    $tipos = "s";

    if ((int) $id_cliente_ignorar > 0) {
        $sql .= " AND id_cliente <> ?";
        $params[] = (int) $id_cliente_ignorar;
        $tipos .= "i";
    }

    $sql .= " LIMIT 1";
    $stmt = mysqli_prepare($conexion, $sql);

    if (!$stmt) {
        return false;
    }

    $refs = [$tipos];
    foreach ($params as $i => &$param) {
        $refs[] = &$param;
    }
    call_user_func_array([$stmt, "bind_param"], $refs);

    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $disponible = mysqli_num_rows($resultado) === 0;
    mysqli_stmt_close($stmt);

    return $disponible;
}

function cliente_registrar_cuenta($conexion, $datos, &$mensaje = "")
{
    $nombre = trim($datos["nombre"] ?? "");
    $apellido = trim($datos["apellido"] ?? "");
    $telefono = normalizar_telefono($datos["telefono"] ?? "");
    $email = email_normalizado($datos["email"] ?? "");
    $direccion_calle = trim($datos["direccion_calle"] ?? "");
    $direccion_altura = trim($datos["direccion_altura"] ?? "");
    $direccion = direccion_compuesta($direccion_calle, $direccion_altura, $datos["direccion"] ?? "");
    $referencias = trim($datos["referencias"] ?? "");
    $password = (string) ($datos["password"] ?? "");
    $confirmacion = (string) ($datos["password_confirmacion"] ?? "");

    if ($nombre === "" || $apellido === "") {
        $mensaje = "Nombre y apellido son obligatorios.";
        return 0;
    }

    if (strlen($telefono) < 8) {
        $mensaje = "Ingresa un telefono valido.";
        return 0;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "Ingresa un correo valido.";
        return 0;
    }

    if (!password_segura($password)) {
        $mensaje = "La contrasena debe tener al menos 8 caracteres, letras y numeros.";
        return 0;
    }

    if ($password !== $confirmacion) {
        $mensaje = "La confirmacion de contrasena no coincide.";
        return 0;
    }

    if (!cliente_email_disponible($conexion, $email)) {
        $mensaje = "Ya existe una cuenta con ese correo.";
        return 0;
    }

    $id_cliente_por_telefono = null;
    $stmt = mysqli_prepare($conexion, "SELECT id_cliente, email, password FROM clientes WHERE telefono = ? LIMIT 1");

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $telefono);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        $cliente_telefono = mysqli_fetch_assoc($resultado);
        mysqli_stmt_close($stmt);

        if ($cliente_telefono) {
            $id_cliente_por_telefono = (int) $cliente_telefono["id_cliente"];

            if (trim((string) ($cliente_telefono["email"] ?? "")) !== "" || trim((string) ($cliente_telefono["password"] ?? "")) !== "") {
                $mensaje = "Ya existe una cuenta asociada a ese telefono.";
                return 0;
            }
        }
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $fecha = date("Y-m-d H:i:s");
    $estado = "Activo";
    $puntos = 0;
    $usa_columnas_email = columna_existe($conexion, "clientes", "email_verificado")
        && columna_existe($conexion, "clientes", "token_verificacion")
        && columna_existe($conexion, "clientes", "token_recuperacion")
        && columna_existe($conexion, "clientes", "token_recuperacion_expira");
    $usa_direccion_partes = columna_existe($conexion, "clientes", "direccion_calle")
        && columna_existe($conexion, "clientes", "direccion_altura");

    if ($id_cliente_por_telefono) {
        $sql = "UPDATE clientes SET
                nombre = ?,
                apellido = ?,
                email = ?,
                password = ?,
                direccion = NULLIF(?, '')," .
            ($usa_direccion_partes ? "
                direccion_calle = NULLIF(?, ''),
                direccion_altura = NULLIF(?, '')," : "") . "
                observaciones = NULLIF(?, ''),
                estado = ?" .
            ($usa_columnas_email ? ",
                email_verificado = 0,
                token_verificacion = NULL,
                token_recuperacion = NULL,
                token_recuperacion_expira = NULL" : "") . "
            WHERE id_cliente = ?";
        $stmt = mysqli_prepare($conexion, $sql);

        if (!$stmt) {
            $mensaje = "No se pudo preparar la cuenta.";
            return 0;
        }

        $tipos_update = $usa_direccion_partes ? "sssssssssi" : "sssssssi";
        $params_update = $usa_direccion_partes ? [
            $nombre,
            $apellido,
            $email,
            $hash,
            $direccion,
            $direccion_calle,
            $direccion_altura,
            $referencias,
            $estado,
            $id_cliente_por_telefono
        ] : [
            $nombre,
            $apellido,
            $email,
            $hash,
            $direccion,
            $referencias,
            $estado,
            $id_cliente_por_telefono
        ];

        $refs_update = [$tipos_update];
        foreach ($params_update as $i => &$param_update) {
            $refs_update[] = &$param_update;
        }
        call_user_func_array([$stmt, "bind_param"], $refs_update);

        if (!mysqli_stmt_execute($stmt)) {
            $mensaje = mysqli_errno($conexion) === 1062
                ? "Ya existe una cuenta con ese correo."
                : "No se pudo crear la cuenta.";
            mysqli_stmt_close($stmt);
            return 0;
        }

        mysqli_stmt_close($stmt);
        return $id_cliente_por_telefono;
    }

    $columnas_direccion = $usa_direccion_partes ? ", direccion_calle, direccion_altura" : "";
    $valores_direccion = $usa_direccion_partes ? ", NULLIF(?, ''), NULLIF(?, '')" : "";
    $sql_insert = $usa_columnas_email
        ? "INSERT INTO clientes
            (nombre, apellido, telefono, email, password, direccion$columnas_direccion, fecha_registro, puntos, puntos_mes, observaciones, estado, email_verificado, token_verificacion, token_recuperacion, token_recuperacion_expira)
            VALUES (?, ?, ?, ?, ?, NULLIF(?, '')$valores_direccion, ?, ?, ?, NULLIF(?, ''), ?, 0, NULL, NULL, NULL)"
        : "INSERT INTO clientes
            (nombre, apellido, telefono, email, password, direccion$columnas_direccion, fecha_registro, puntos, puntos_mes, observaciones, estado)
            VALUES (?, ?, ?, ?, ?, NULLIF(?, '')$valores_direccion, ?, ?, ?, NULLIF(?, ''), ?)";

    $stmt = mysqli_prepare($conexion, $sql_insert);

    if (!$stmt) {
        $mensaje = "No se pudo preparar la cuenta.";
        return 0;
    }

    $tipos_insert = $usa_direccion_partes ? "sssssssssiiss" : "sssssssiiss";
    $params_insert = $usa_direccion_partes ? [
        $nombre,
        $apellido,
        $telefono,
        $email,
        $hash,
        $direccion,
        $direccion_calle,
        $direccion_altura,
        $fecha,
        $puntos,
        $puntos,
        $referencias,
        $estado
    ] : [
        $nombre,
        $apellido,
        $telefono,
        $email,
        $hash,
        $direccion,
        $fecha,
        $puntos,
        $puntos,
        $referencias,
        $estado
    ];

    $refs_insert = [$tipos_insert];
    foreach ($params_insert as $i => &$param_insert) {
        $refs_insert[] = &$param_insert;
    }
    call_user_func_array([$stmt, "bind_param"], $refs_insert);

    if (!mysqli_stmt_execute($stmt)) {
        $mensaje = mysqli_errno($conexion) === 1062
            ? "Ya existe una cuenta con ese correo."
            : "No se pudo crear la cuenta.";
        mysqli_stmt_close($stmt);
        return 0;
    }

    $id_cliente = mysqli_insert_id($conexion);
    mysqli_stmt_close($stmt);

    return $id_cliente ? (int) $id_cliente : 0;
}

?>
