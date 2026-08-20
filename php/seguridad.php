<?php

function iniciar_sesion_segura()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $session_path = session_save_path();

    if (PHP_SAPI === "cli" || ($session_path !== "" && !is_writable($session_path))) {
        session_save_path(sys_get_temp_dir());
    }

    session_set_cookie_params([
        "lifetime" => 0,
        "path" => defined("GENESIS_BASE_URL") ? GENESIS_BASE_URL : "/",
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    session_start();
}

function rol_normalizado($rol)
{
    $rol = trim((string) $rol);

    if ($rol === "Caja") {
        return "Cajero";
    }

    return $rol;
}

function token_csrf()
{
    iniciar_sesion_segura();

    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["csrf_token"];
}

function validar_csrf($token)
{
    iniciar_sesion_segura();

    return isset($_SESSION["csrf_token"]) && hash_equals($_SESSION["csrf_token"], (string) $token);
}

function normalizar_telefono($telefono)
{
    return preg_replace("/[^0-9]/", "", (string) $telefono);
}

function email_normalizado($email)
{
    return strtolower(trim((string) $email));
}

function direccion_compuesta($calle, $altura, $fallback = "")
{
    $calle = trim((string) $calle);
    $altura = trim((string) $altura);
    $fallback = trim((string) $fallback);

    if ($calle !== "" && $altura !== "") {
        return trim($calle . " " . $altura);
    }

    if ($calle !== "") {
        return $calle;
    }

    if ($altura !== "") {
        return $altura;
    }

    return $fallback;
}

function password_segura($password)
{
    return strlen($password) >= 8 && preg_match("/[A-Za-z]/", $password) && preg_match("/[0-9]/", $password);
}

function cliente_actual_id()
{
    iniciar_sesion_segura();

    return isset($_SESSION["cliente_id"]) ? (int) $_SESSION["cliente_id"] : 0;
}

function admin_actual_id()
{
    iniciar_sesion_segura();

    return isset($_SESSION["admin_id"]) ? (int) $_SESSION["admin_id"] : 0;
}

function cliente_actual($conexion)
{
    $id_cliente = cliente_actual_id();

    if ($id_cliente < 1) {
        return null;
    }

    $stmt = mysqli_prepare($conexion, "SELECT * FROM clientes WHERE id_cliente = ? AND estado = 'Activo' LIMIT 1");

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, "i", $id_cliente);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $cliente = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);

    return $cliente ?: null;
}

function admin_actual($conexion)
{
    $id_admin = admin_actual_id();

    if ($id_admin < 1) {
        return null;
    }

    $stmt = mysqli_prepare($conexion, "SELECT * FROM admin_usuarios WHERE id_admin = ? AND activo = 1 LIMIT 1");

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, "i", $id_admin);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $admin = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);

    return $admin ?: null;
}

function empleado_permiso_por_ruta_actual()
{
    $ruta = strtolower((string) ($_SERVER["SCRIPT_NAME"] ?? $_SERVER["REQUEST_URI"] ?? ""));

    if (strpos($ruta, "/cocina/") !== false) {
        return "cocina";
    }

    if (strpos($ruta, "/caja/") !== false) {
        return "caja";
    }

    if (strpos($ruta, "/pedidos/") !== false) {
        return "pedidos";
    }

    if (strpos($ruta, "/clientes/admin") !== false) {
        return "clientes";
    }

    if (strpos($ruta, "/productos/") !== false) {
        return "productos";
    }

    if (strpos($ruta, "/estadisticas/") !== false) {
        return "estadisticas";
    }

    if (strpos($ruta, "/empleados/") !== false) {
        return "empleados";
    }

    return "";
}

function empleado_ids_sesion()
{
    iniciar_sesion_segura();

    $ids = [];

    if (!empty($_SESSION["empleados_por_rol"]) && is_array($_SESSION["empleados_por_rol"])) {
        foreach ($_SESSION["empleados_por_rol"] as $id_empleado) {
            $id_empleado = (int) $id_empleado;

            if ($id_empleado > 0 && !in_array($id_empleado, $ids, true)) {
                $ids[] = $id_empleado;
            }
        }
    }

    if (!empty($_SESSION["empleado_id"])) {
        $id_empleado = (int) $_SESSION["empleado_id"];

        if ($id_empleado > 0 && !in_array($id_empleado, $ids, true)) {
            $ids[] = $id_empleado;
        }
    }

    return $ids;
}

function empleado_cargar_por_id($conexion, $id_empleado)
{
    $id_empleado = (int) $id_empleado;

    if ($id_empleado < 1) {
        return null;
    }

    $stmt = mysqli_prepare($conexion, "SELECT id_usuario, nombre, usuario, rol, activo FROM usuarios WHERE id_usuario = ? AND activo = 1 LIMIT 1");

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, "i", $id_empleado);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $empleado = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);

    if ($empleado) {
        $empleado["rol"] = rol_normalizado($empleado["rol"] ?? "");
    }

    return $empleado ?: null;
}

function empleado_actual($conexion, $permiso_preferido = "")
{
    iniciar_sesion_segura();

    $permiso_preferido = trim((string) $permiso_preferido);

    if ($permiso_preferido === "") {
        $permiso_preferido = empleado_permiso_por_ruta_actual();
    }

    foreach (empleado_ids_sesion() as $id_empleado) {
        $empleado = empleado_cargar_por_id($conexion, $id_empleado);

        if ($empleado && $permiso_preferido !== "" && empleado_tiene_permiso($empleado, $permiso_preferido)) {
            $_SESSION["empleado_id"] = (int) $empleado["id_usuario"];
            $_SESSION["empleado_rol_activo"] = $empleado["rol"];
            return $empleado;
        }
    }

    $id_empleado_activo = isset($_SESSION["empleado_id"]) ? (int) $_SESSION["empleado_id"] : 0;
    $empleado_activo = empleado_cargar_por_id($conexion, $id_empleado_activo);

    if ($empleado_activo) {
        return $empleado_activo;
    }

    $ids = empleado_ids_sesion();
    return count($ids) > 0 ? empleado_cargar_por_id($conexion, $ids[0]) : null;
}

function permisos_por_rol($rol)
{
    $rol = rol_normalizado($rol);

    $permisos = [
        "Administrador" => ["productos", "pedidos", "clientes", "cocina", "caja", "estadisticas", "empleados", "configuracion"],
        "Mozo" => ["pedidos", "clientes"],
        "Cocina" => ["cocina"],
        "Cajero" => ["caja"]
    ];

    return $permisos[$rol] ?? [];
}

function empleado_tiene_permiso($empleado, $permiso)
{
    if (!$empleado) {
        return false;
    }

    return in_array($permiso, permisos_por_rol($empleado["rol"] ?? ""), true);
}

function empleado_inicio_url($empleado)
{
    $rol = rol_normalizado($empleado["rol"] ?? "");

    $rutas = [
        "Administrador" => "index.php",
        "Mozo" => "pedidos/index.php",
        "Cocina" => "cocina/index.php",
        "Cajero" => "caja/index.php"
    ];

    return genesis_url($rutas[$rol] ?? "empleados/login.php");
}

function requerir_permiso($conexion, $permiso)
{
    $empleado = empleado_actual($conexion, $permiso);

    if (!$empleado) {
        header("Location: /genesisbar1/empleados/login.php");
        exit;
    }

    if (!empleado_tiene_permiso($empleado, $permiso)) {
        http_response_code(403);
        header("Location: /genesisbar1/empleados/acceso-denegado.php");
        exit;
    }
}

function requerir_cliente($conexion)
{
    if (!cliente_actual($conexion)) {
        header("Location: index.php");
        exit;
    }
}

function requerir_admin($conexion)
{
    if (!admin_actual($conexion)) {
        header("Location: login.php");
        exit;
    }
}

function responder_json($ok, $mensaje, $datos = [], $codigo = 200)
{
    http_response_code($codigo);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(array_merge([
        "ok" => $ok,
        "mensaje" => $mensaje
    ], $datos), JSON_UNESCAPED_UNICODE);
    exit;
}

?>
