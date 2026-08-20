<?php
date_default_timezone_set("America/Argentina/Buenos_Aires");

require_once("../config/config.php");
require_once("../php/seguridad.php");
require_once("../php/imagenes.php");
require_once("../php/clientes_auth.php");

iniciar_sesion_segura();

$mensaje = "";
$tipo_mensaje = "mensaje-pedido";
$accion = $_POST["accion"] ?? "";

if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    if (!validar_csrf($_POST["csrf_token"] ?? "")) {
        $mensaje = "La sesion vencio. Actualiza la pagina e intenta nuevamente.";
        $tipo_mensaje = "mensaje-pedido error";
    } elseif ($accion === "registro") {
        $id_cliente = cliente_registrar_cuenta($conexion, $_POST, $mensaje);

        if ($id_cliente > 0) {
            $_SESSION["cliente_id"] = $id_cliente;
            session_regenerate_id(true);
            header("Location: index.php");
            exit;
        }

        $tipo_mensaje = "mensaje-pedido error";
    } elseif ($accion === "login") {
        $email = email_normalizado($_POST["email_login"] ?? "");
        $password = $_POST["password_login"] ?? "";

        $stmt = mysqli_prepare($conexion, "SELECT id_cliente, password, estado FROM clientes WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        $cliente_login = mysqli_fetch_assoc($resultado);
        mysqli_stmt_close($stmt);

        if (!$cliente_login || $cliente_login["estado"] !== "Activo" || !password_verify($password, $cliente_login["password"] ?? "")) {
            $mensaje = "Correo o contrasena incorrectos.";
            $tipo_mensaje = "mensaje-pedido error";
        } else {
            $_SESSION["cliente_id"] = (int) $cliente_login["id_cliente"];
            session_regenerate_id(true);
            $fecha = date("Y-m-d H:i:s");
            $stmt = mysqli_prepare($conexion, "UPDATE clientes SET ultimo_acceso = ? WHERE id_cliente = ?");
            mysqli_stmt_bind_param($stmt, "si", $fecha, $_SESSION["cliente_id"]);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            header("Location: index.php");
            exit;
        }
    }
}

$cliente = cliente_actual($conexion);
$csrf = token_csrf();
$categorias_nav = mysqli_query($conexion, "SELECT id_categoria, nombre FROM categorias WHERE nombre IN ('Pizza','Sushi','Empanadas','Bebidas','Tragos') ORDER BY FIELD(nombre,'Pizza','Sushi','Empanadas','Bebidas','Tragos')");
$categorias = mysqli_query($conexion, "SELECT id_categoria, nombre FROM categorias WHERE nombre IN ('Pizza','Sushi','Empanadas','Bebidas','Tragos') ORDER BY FIELD(nombre,'Pizza','Sushi','Empanadas','Bebidas','Tragos')");
$formas_pago = mysqli_query($conexion, "SELECT id_forma_pago, nombre FROM formas_pago ORDER BY nombre ASC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Clientes - GenesisBar 1.0</title>
    <link rel="stylesheet" href="/genesisbar1/css/estilos.css?v=5">
    <link rel="stylesheet" href="/genesisbar1/css/clientes.css?v=3">
</head>
<body class="portal-clientes">

<header class="portal-navbar">
    <a class="portal-brand" href="/genesisbar1/clientes/index.php" aria-label="GenesisBar inicio">
        <span class="portal-brand-mark">GB</span>
        <span>
            <strong>GenesisBar</strong>
            <small>Portal clientes</small>
        </span>
    </a>

    <button class="portal-nav-toggle" type="button" aria-expanded="false" aria-controls="portalNav" aria-label="Abrir menu">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <nav id="portalNav" class="portal-nav" aria-label="Navegacion principal">
        <a href="/genesisbar1/clientes/index.php#inicio">Inicio</a>

        <div class="portal-productos-menu">
            <button class="portal-productos-toggle" type="button" aria-expanded="false">
                Productos
            </button>
            <div class="portal-productos-dropdown">
                <?php while ($categoria_nav = mysqli_fetch_assoc($categorias_nav)) { ?>
                    <button type="button" data-categoria="<?= (int) $categoria_nav["id_categoria"]; ?>">
                        <?= htmlspecialchars($categoria_nav["nombre"]); ?>
                    </button>
                <?php } ?>
            </div>
        </div>

        <?php if ($cliente) { ?>
            <a href="/genesisbar1/clientes/index.php#mi-pedido">Mi pedido</a>
            <a href="/genesisbar1/clientes/index.php#estado-pedido">Estado del pedido</a>
            <a href="/genesisbar1/clientes/perfil.php">Mi perfil</a>
            <a href="/genesisbar1/clientes/logout.php">Cerrar sesión</a>
        <?php } else { ?>
            <a href="/genesisbar1/clientes/login.php">Iniciar sesión</a>
            <a class="portal-nav-cta" href="/genesisbar1/clientes/registro.php">Registrarme</a>
        <?php } ?>
    </nav>
</header>

<main id="inicio" class="portal-main">
    <section class="portal-hero">
        <div class="portal-hero-copy">
            <span class="portal-eyebrow">Pedidos online GenesisBar 1.0</span>
            <h1>Tu mesa, tu take away o tu delivery desde un solo lugar.</h1>
            <p>
                Explorá pizzas, sushi, empanadas, bebidas y tragos. Creá tu cuenta para guardar tus datos,
                confirmar pedidos y seguir el estado en tiempo real.
            </p>
            <div class="portal-acciones">
                <?php if ($cliente) { ?>
                    <a class="boton portal-boton-principal" href="#mi-pedido">Armar pedido</a>
                    <a class="portal-boton-secundario" href="#estado-pedido">Ver estado</a>
                <?php } else { ?>
                    <a class="boton portal-boton-principal" href="/genesisbar1/clientes/login.php">Iniciar sesión</a>
                    <a class="portal-boton-secundario" href="/genesisbar1/clientes/registro.php">Registrarme</a>
                <?php } ?>
            </div>
        </div>
        <div class="portal-hero-card" aria-label="Resumen del portal">
            <strong>GenesisBar</strong>
            <span>Carta activa</span>
            <ul>
                <li>Pizzas al horno</li>
                <li>Sushi y combinados</li>
                <li>Empanadas</li>
                <li>Bebidas y tragos</li>
            </ul>
        </div>
    </section>

    <?php if ($mensaje !== "") { ?>
        <div class="<?= htmlspecialchars($tipo_mensaje); ?>"><?= htmlspecialchars($mensaje); ?></div>
    <?php } ?>

    <?php if ($cliente) { ?>
        <section id="estado-pedido" class="panel-pedido estado-cliente-panel">
            <h3>Hola, <?= htmlspecialchars($cliente["nombre"]); ?></h3>
            <p>Tu sesion esta iniciada. Los pedidos que confirmes quedan asociados a tu cuenta.</p>
            <div class="portal-puntos-resumen">
                <span>Puntos disponibles</span>
                <strong><?= (int) ($cliente["puntos"] ?? 0); ?> pts</strong>
                <a href="/genesisbar1/clientes/perfil.php">Ver historial</a>
            </div>
            <div id="estadoActivoCliente" class="estado-cliente-activo">Buscando pedidos activos...</div>
        </section>
    <?php } ?>

    <div id="carta" class="portal-grid">
        <section class="panel-pedido">
            <h3>Menu</h3>
            <div class="portal-filtros-categorias" aria-label="Categorias">
                <button type="button" class="portal-filtro-categoria activo" data-categoria="todos">Todos</button>
                <?php
                mysqli_data_seek($categorias, 0);
                while ($categoria_filtro = mysqli_fetch_assoc($categorias)) {
                ?>
                    <button type="button" class="portal-filtro-categoria" data-categoria="<?= (int) $categoria_filtro["id_categoria"]; ?>">
                        <?= htmlspecialchars($categoria_filtro["nombre"]); ?>
                    </button>
                <?php } ?>
            </div>
            <div class="productos-grid-cliente portal-productos-grid">
                <?php mysqli_data_seek($categorias, 0); ?>
                <?php while ($categoria = mysqli_fetch_assoc($categorias)) { ?>
                    <?php
                    $id_categoria = (int) $categoria["id_categoria"];
                    $stmt_productos = mysqli_prepare($conexion, "SELECT id_producto, nombre, descripcion, imagen, precio, stock FROM productos WHERE id_categoria = ? AND activo = 1 ORDER BY nombre ASC");
                    mysqli_stmt_bind_param($stmt_productos, "i", $id_categoria);
                    mysqli_stmt_execute($stmt_productos);
                    $productos = mysqli_stmt_get_result($stmt_productos);
                    ?>
                    <?php while ($producto = mysqli_fetch_assoc($productos)) { ?>
                        <?php $agotado = (int) ($producto["stock"] ?? 0) <= 0; ?>
                        <article class="producto-cliente" data-categoria="<?= $id_categoria; ?>">
                            <img class="producto-cliente-img" src="<?= htmlspecialchars(imagen_url($producto["imagen"] ?? "")); ?>" alt="<?= htmlspecialchars($producto["nombre"]); ?>">
                            <div>
                                <h4><?= htmlspecialchars($producto["nombre"]); ?></h4>
                                <p><?= htmlspecialchars($producto["descripcion"] ?: "Producto de GenesisBar"); ?></p>
                                <strong>$<?= number_format((float) $producto["precio"], 0, ",", "."); ?></strong>
                            </div>
                            <button
                                type="button"
                                class="boton-agregar agregarCliente"
                                data-id="<?= (int) $producto["id_producto"]; ?>"
                                data-nombre="<?= htmlspecialchars($producto["nombre"]); ?>"
                                data-precio="<?= htmlspecialchars($producto["precio"]); ?>"
                                <?= $agotado ? "disabled" : ""; ?>>
                                <?= $agotado ? "Agotado" : "Agregar"; ?>
                            </button>
                        </article>
                    <?php } ?>
                    <?php mysqli_stmt_close($stmt_productos); ?>
                <?php } ?>
            </div>
        </section>

        <aside id="mi-pedido" class="panel-pedido carrito-cliente-panel">
            <div class="portal-carrito-titulo">
                <h3>Mi pedido</h3>
                <strong id="totalCarritoCliente">$0</strong>
            </div>
            <div id="carritoCliente" class="carrito-cliente-vacio">Todavia no agregaste productos.</div>

            <label for="tipoPedidoCliente">Tipo de pedido</label>
            <select id="tipoPedidoCliente">
                <option value="Take Away">Take Away</option>
                <option value="Delivery">Delivery</option>
            </select>

            <label for="direccionEntregaCliente">Direccion para delivery</label>
            <input id="direccionEntregaCliente" value="<?= htmlspecialchars($cliente["direccion"] ?? ""); ?>">

            <label for="formaPagoCliente">Forma de pago</label>
            <select id="formaPagoCliente">
                <option value="">Seleccionar forma de pago</option>
                <?php while ($forma = mysqli_fetch_assoc($formas_pago)) { ?>
                    <option value="<?= (int) $forma["id_forma_pago"]; ?>"><?= htmlspecialchars($forma["nombre"]); ?></option>
                <?php } ?>
            </select>

            <label for="observacionesCliente">Observaciones generales</label>
            <textarea id="observacionesCliente" rows="3"></textarea>

            <input type="hidden" id="csrfCliente" value="<?= htmlspecialchars($csrf); ?>">
            <button id="confirmarPedidoCliente" class="boton" type="button" <?= $cliente ? "" : "disabled"; ?>>
                <?= $cliente ? "Confirmar pedido" : "Inicia sesion para pedir"; ?>
            </button>
            <div id="mensajePedidoCliente" class="mensaje-pedido" hidden></div>
        </aside>
    </div>
</main>

<script>
const clienteLogueado = <?= $cliente ? "true" : "false"; ?>;
const carrito = new Map();
const totalCarrito = document.getElementById("totalCarritoCliente");
const carritoContenedor = document.getElementById("carritoCliente");
const mensajePedido = document.getElementById("mensajePedidoCliente");
const tipoPedido = document.getElementById("tipoPedidoCliente");
const direccionEntrega = document.getElementById("direccionEntregaCliente");
const navToggle = document.querySelector(".portal-nav-toggle");
const portalNav = document.getElementById("portalNav");
const productosMenu = document.querySelector(".portal-productos-menu");
const productosToggle = document.querySelector(".portal-productos-toggle");

function precio(valor) {
    return "$" + Number(valor || 0).toLocaleString("es-AR");
}

function escaparHtml(valor) {
    return String(valor ?? "").replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;").replaceAll('"', "&quot;").replaceAll("'", "&#039;");
}

function mostrarMensaje(texto, tipo = "") {
    mensajePedido.hidden = false;
    mensajePedido.className = "mensaje-pedido " + tipo;
    mensajePedido.textContent = texto;
}

function actualizarDelivery() {
    const esDelivery = tipoPedido.value === "Delivery";
    direccionEntrega.disabled = !esDelivery;
    direccionEntrega.required = esDelivery;
}

function cerrarNavegacion() {
    portalNav.classList.remove("abierto");
    navToggle.setAttribute("aria-expanded", "false");
    productosMenu.classList.remove("abierto");
    productosToggle.setAttribute("aria-expanded", "false");
}

function mostrarCategoria(idCategoria) {
    document.querySelectorAll(".portal-filtro-categoria").forEach((boton) => {
        boton.classList.toggle("activo", boton.dataset.categoria === String(idCategoria));
    });

    document.querySelectorAll(".producto-cliente[data-categoria]").forEach((producto) => {
        producto.hidden = idCategoria !== "todos" && producto.dataset.categoria !== String(idCategoria);
    });

    document.getElementById("carta").scrollIntoView({behavior: "smooth", block: "start"});
    cerrarNavegacion();
}

function renderCarrito() {
    let total = 0;
    carritoContenedor.innerHTML = "";

    if (carrito.size === 0) {
        carritoContenedor.textContent = "Todavia no agregaste productos.";
        totalCarrito.textContent = "$0";
        return;
    }

    carrito.forEach((item) => {
        total += item.precio * item.cantidad;
        const div = document.createElement("div");
        div.className = "item-carrito";
        div.innerHTML = `
            <h4>${escaparHtml(item.nombre)}</h4>
            <p>${precio(item.precio)} x ${item.cantidad}</p>
            <div class="cantidad">
                <button type="button" data-id="${item.id}" data-accion="restar">-</button>
                <span>${item.cantidad}</span>
                <button type="button" data-id="${item.id}" data-accion="sumar">+</button>
            </div>
            <label class="label-observacion-item">Observacion del producto</label>
            <input class="observacion-item-cliente" data-id="${item.id}" value="${escaparHtml(item.observaciones)}">
            <button type="button" class="eliminar" data-id="${item.id}" data-accion="eliminar">Eliminar</button>
        `;
        carritoContenedor.appendChild(div);
    });

    totalCarrito.textContent = precio(total);
}

navToggle.addEventListener("click", () => {
    const abierto = portalNav.classList.toggle("abierto");
    navToggle.setAttribute("aria-expanded", abierto ? "true" : "false");
});

productosToggle.addEventListener("click", () => {
    const abierto = productosMenu.classList.toggle("abierto");
    productosToggle.setAttribute("aria-expanded", abierto ? "true" : "false");
});

document.addEventListener("click", (evento) => {
    if (!productosMenu.contains(evento.target) && !navToggle.contains(evento.target)) {
        productosMenu.classList.remove("abierto");
        productosToggle.setAttribute("aria-expanded", "false");
    }
});

document.querySelectorAll(".portal-productos-dropdown button").forEach((boton) => {
    boton.addEventListener("click", () => {
        mostrarCategoria(boton.dataset.categoria);
    });
});

portalNav.querySelectorAll("a").forEach((enlace) => {
    enlace.addEventListener("click", cerrarNavegacion);
});

document.querySelectorAll(".portal-filtro-categoria").forEach((boton) => {
    boton.addEventListener("click", () => mostrarCategoria(boton.dataset.categoria));
});

document.querySelectorAll(".agregarCliente").forEach((boton) => {
    boton.addEventListener("click", () => {
        const id = Number(boton.dataset.id);
        const item = carrito.get(id);

        if (item) {
            item.cantidad = Math.min(99, item.cantidad + 1);
        } else {
            carrito.set(id, {
                id,
                nombre: boton.dataset.nombre,
                precio: Number(boton.dataset.precio),
                cantidad: 1,
                observaciones: ""
            });
        }

        renderCarrito();
    });
});

carritoContenedor.addEventListener("click", (evento) => {
    const id = Number(evento.target.dataset.id || 0);
    const accion = evento.target.dataset.accion;

    if (!id || !accion || !carrito.has(id)) {
        return;
    }

    const item = carrito.get(id);

    if (accion === "sumar") {
        item.cantidad = Math.min(99, item.cantidad + 1);
    } else if (accion === "restar") {
        item.cantidad = Math.max(1, item.cantidad - 1);
    } else if (accion === "eliminar") {
        carrito.delete(id);
    }

    renderCarrito();
});

carritoContenedor.addEventListener("input", (evento) => {
    if (!evento.target.classList.contains("observacion-item-cliente")) {
        return;
    }

    const item = carrito.get(Number(evento.target.dataset.id));

    if (item) {
        item.observaciones = evento.target.value;
    }
});

document.getElementById("confirmarPedidoCliente").addEventListener("click", async () => {
    if (!clienteLogueado) {
        mostrarMensaje("Inicia sesion para confirmar el pedido.", "error");
        return;
    }

    if (carrito.size === 0) {
        mostrarMensaje("Agrega al menos un producto.", "error");
        return;
    }

    if (tipoPedido.value === "Delivery" && direccionEntrega.value.trim() === "") {
        mostrarMensaje("Ingresa la direccion para Delivery.", "error");
        return;
    }

    if (document.getElementById("formaPagoCliente").value === "") {
        mostrarMensaje("Selecciona la forma de pago.", "error");
        return;
    }

    const boton = document.getElementById("confirmarPedidoCliente");
    boton.disabled = true;
    boton.textContent = "Guardando...";

    try {
        const respuesta = await fetch("ajax/confirmar_pedido.php", {
            method: "POST",
            headers: {"Content-Type": "application/json", "Accept": "application/json"},
            body: JSON.stringify({
                csrf_token: document.getElementById("csrfCliente").value,
                tipo_pedido: tipoPedido.value,
                direccion_entrega: direccionEntrega.value.trim(),
                id_forma_pago: document.getElementById("formaPagoCliente").value,
                observaciones: document.getElementById("observacionesCliente").value.trim(),
                carrito: Array.from(carrito.values()).map((item) => ({
                    id_producto: item.id,
                    cantidad: item.cantidad,
                    observaciones: item.observaciones
                }))
            })
        });
        const datos = await respuesta.json();

        if (!datos.ok) {
            throw new Error(datos.mensaje || "No se pudo guardar el pedido.");
        }

        carrito.clear();
        renderCarrito();
        mostrarMensaje(`Pedido ${datos.numero_pedido} confirmado.`, "exito");
        cargarEstadoActivo();
    } catch (error) {
        mostrarMensaje(error.message, "error");
    } finally {
        boton.disabled = false;
        boton.textContent = "Confirmar pedido";
    }
});

async function cargarEstadoActivo() {
    const contenedor = document.getElementById("estadoActivoCliente");

    if (!contenedor) {
        return;
    }

    try {
        const respuesta = await fetch("ajax/estado_activo.php");
        const datos = await respuesta.json();

        if (!datos.ok || datos.pedidos.length === 0) {
            contenedor.innerHTML = "<p>No tenes pedidos activos.</p>";
            return;
        }

        contenedor.innerHTML = datos.pedidos.map((pedido) => `
            <div class="comanda">
                <h3>${escaparHtml(pedido.numero_pedido)}</h3>
                <p><b>Estado:</b> ${escaparHtml(pedido.estado)}</p>
                <p><b>Tipo:</b> ${escaparHtml(pedido.tipo_pedido)}</p>
                <p><b>Total:</b> ${precio(pedido.total)}</p>
                <p><b>Productos:</b> ${escaparHtml(pedido.productos)}</p>
            </div>
        `).join("");
    } catch (error) {
        contenedor.innerHTML = "<p>No se pudo actualizar el estado.</p>";
    }
}

tipoPedido.addEventListener("change", actualizarDelivery);
actualizarDelivery();
renderCarrito();
cargarEstadoActivo();
setInterval(cargarEstadoActivo, 5000);
</script>

</body>
</html>
