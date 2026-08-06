//=========================================
// GENESISBAR 1.0 - Modulo Pedidos
//=========================================

let carrito = [];
let productosActuales = new Map();

const formatoMoneda = new Intl.NumberFormat("es-AR", {
    style: "currency",
    currency: "ARS",
    maximumFractionDigits: 0
});

document.addEventListener("DOMContentLoaded", function () {
    actualizarCamposEntrega();
    cargarCategorias();
    actualizarCarrito();

    document.getElementById("tipo_pedido").addEventListener("change", actualizarCamposEntrega);
    document.getElementById("confirmarPedido").addEventListener("click", confirmarPedido);
});

function mostrarMensaje(texto, tipo) {
    const mensaje = document.getElementById("mensajePedido");
    mensaje.textContent = texto;
    mensaje.className = "mensaje-pedido " + (tipo || "info");
    mensaje.hidden = texto === "";
}

function escaparHtml(texto) {
    const div = document.createElement("div");
    div.textContent = texto == null ? "" : String(texto);
    return div.innerHTML;
}

function actualizarCamposEntrega() {
    const tipo = document.getElementById("tipo_pedido").value;
    const grupoMesa = document.getElementById("grupoMesa");
    const grupoDireccion = document.getElementById("grupoDireccion");
    const gruposCliente = document.querySelectorAll(".grupoCliente");
    const mesa = document.getElementById("mesa");
    const direccion = document.getElementById("direccion_entrega");
    const nombreCliente = document.getElementById("nombre_cliente");
    const telefonoCliente = document.getElementById("telefono_cliente");
    const formaPago = document.getElementById("id_forma_pago");
    const requiereCliente = tipo === "Take Away" || tipo === "Delivery";

    grupoMesa.hidden = tipo !== "Mesa";
    grupoDireccion.hidden = tipo !== "Delivery";
    mesa.disabled = tipo !== "Mesa";
    direccion.disabled = tipo !== "Delivery";

    gruposCliente.forEach(function (grupo) {
        grupo.hidden = !requiereCliente;
    });

    nombreCliente.disabled = !requiereCliente;
    telefonoCliente.disabled = !requiereCliente;
    formaPago.disabled = !requiereCliente;

    if (tipo !== "Mesa") {
        mesa.value = "";
    }

    if (tipo !== "Delivery") {
        direccion.value = "";
    }

    if (!requiereCliente) {
        nombreCliente.value = "";
        telefonoCliente.value = "";
        formaPago.value = "";
    }
}

function cargarCategorias() {
    const contenedor = document.getElementById("categorias");
    contenedor.innerHTML = "<p>Cargando categorias...</p>";

    fetch("../ajax/categorias.php")
        .then(function (respuesta) { return respuesta.json(); })
        .then(function (datos) {
            if (!datos.ok) {
                throw new Error(datos.mensaje || "No se pudieron cargar las categorias.");
            }

            if (datos.categorias.length === 0) {
                contenedor.innerHTML = "<p>No hay categorias cargadas.</p>";
                return;
            }

            contenedor.innerHTML = "";

            datos.categorias.forEach(function (categoria) {
                const boton = document.createElement("button");
                boton.type = "button";
                boton.className = "categoria";
                boton.dataset.id = categoria.id_categoria;
                boton.textContent = categoria.nombre + " (" + categoria.cantidad + ")";

                boton.addEventListener("click", function () {
                    document.querySelectorAll(".categoria").forEach(function (item) {
                        item.classList.remove("activa");
                    });

                    boton.classList.add("activa");
                    cargarProductos(categoria.id_categoria);
                });

                contenedor.appendChild(boton);
            });
        })
        .catch(function (error) {
            contenedor.innerHTML = "<p>No se pudieron cargar las categorias.</p>";
            mostrarMensaje(error.message, "error");
        });
}

function cargarProductos(idCategoria) {
    const contenedor = document.getElementById("productos");
    contenedor.innerHTML = "<p>Cargando productos...</p>";
    productosActuales = new Map();

    fetch("../ajax/productos.php?categoria=" + encodeURIComponent(idCategoria))
        .then(function (respuesta) { return respuesta.json(); })
        .then(function (datos) {
            if (!datos.ok) {
                throw new Error(datos.mensaje || "No se pudieron cargar los productos.");
            }

            if (datos.productos.length === 0) {
                contenedor.innerHTML = "<p>No hay productos activos en esta categoria.</p>";
                return;
            }

            contenedor.innerHTML = "";

            datos.productos.forEach(function (producto) {
                productosActuales.set(String(producto.id_producto), producto);

                const tarjeta = document.createElement("div");
                tarjeta.className = "producto";
                tarjeta.innerHTML = `
                    <h4>${escaparHtml(producto.nombre)}</h4>
                    <p>${escaparHtml(producto.descripcion || "")}</p>
                    <strong>${formatoMoneda.format(producto.precio)}</strong>
                    <button class="boton-agregar" type="button" data-id="${producto.id_producto}">
                        Agregar
                    </button>
                `;

                contenedor.appendChild(tarjeta);
            });
        })
        .catch(function (error) {
            contenedor.innerHTML = "<p>No se pudieron cargar los productos.</p>";
            mostrarMensaje(error.message, "error");
        });
}

document.addEventListener("click", function (event) {
    if (!event.target.classList.contains("boton-agregar")) {
        return;
    }

    const idProducto = String(event.target.dataset.id);
    const producto = productosActuales.get(idProducto);

    if (!producto) {
        mostrarMensaje("El producto seleccionado no esta disponible.", "error");
        return;
    }

    agregarProducto(producto);
});

function agregarProducto(producto) {
    const existente = carrito.find(function (item) {
        return item.id_producto === producto.id_producto;
    });

    if (existente) {
        existente.cantidad = Math.min(99, existente.cantidad + 1);
    } else {
        carrito.push({
            id_producto: producto.id_producto,
            nombre: producto.nombre,
            precio: Number(producto.precio),
            cantidad: 1,
            observaciones: ""
        });
    }

    mostrarMensaje("Producto agregado al carrito.", "exito");
    actualizarCarrito();
}

function actualizarCarrito() {
    const contenedor = document.getElementById("carrito");
    const totalCarrito = document.getElementById("totalCarrito");
    let total = 0;

    if (carrito.length === 0) {
        contenedor.innerHTML = "No hay productos.";
        totalCarrito.textContent = formatoMoneda.format(0);
        return;
    }

    let html = "";

    carrito.forEach(function (item, index) {
        const subtotal = item.precio * item.cantidad;
        total += subtotal;

        html += `
            <div class="item-carrito">
                <h4>${escaparHtml(item.nombre)}</h4>
                <p>${formatoMoneda.format(item.precio)} c/u</p>
                <div class="cantidad">
                    <button type="button" data-accion="restar" data-index="${index}">-</button>
                    <input type="number" min="1" max="99" value="${item.cantidad}" data-accion="cantidad" data-index="${index}">
                    <button type="button" data-accion="sumar" data-index="${index}">+</button>
                </div>
                <label class="label-observacion-item">Observacion del producto</label>
                <input class="observacion-item" type="text" maxlength="255" value="${escaparHtml(item.observaciones)}" data-accion="observacion" data-index="${index}">
                <p>Subtotal: <strong>${formatoMoneda.format(subtotal)}</strong></p>
                <button class="eliminar" type="button" data-accion="eliminar" data-index="${index}">Eliminar</button>
            </div>
        `;
    });

    contenedor.innerHTML = html;
    totalCarrito.textContent = formatoMoneda.format(total);
}

document.addEventListener("click", function (event) {
    const accion = event.target.dataset.accion;
    const index = Number(event.target.dataset.index);

    if (!accion || !Number.isInteger(index) || !carrito[index]) {
        return;
    }

    if (accion === "sumar") {
        carrito[index].cantidad = Math.min(99, carrito[index].cantidad + 1);
    }

    if (accion === "restar") {
        carrito[index].cantidad--;

        if (carrito[index].cantidad < 1) {
            carrito.splice(index, 1);
        }
    }

    if (accion === "eliminar") {
        carrito.splice(index, 1);
        mostrarMensaje("Producto eliminado del carrito.", "info");
    }

    actualizarCarrito();
});

document.addEventListener("input", function (event) {
    const accion = event.target.dataset.accion;
    const index = Number(event.target.dataset.index);

    if (!accion || !Number.isInteger(index) || !carrito[index]) {
        return;
    }

    if (accion === "observacion") {
        carrito[index].observaciones = event.target.value.trim();
        return;
    }

    if (accion !== "cantidad") {
        return;
    }

    const cantidad = Number(event.target.value);

    if (!Number.isInteger(cantidad) || cantidad < 1 || cantidad > 99) {
        mostrarMensaje("La cantidad debe estar entre 1 y 99.", "error");
        return;
    }

    carrito[index].cantidad = cantidad;
    actualizarCarrito();
});

function calcularTotal() {
    return carrito.reduce(function (total, item) {
        return total + item.precio * item.cantidad;
    }, 0);
}

function validarPedido() {
    const tipo = document.getElementById("tipo_pedido").value;
    const mesa = document.getElementById("mesa").value;
    const direccion = document.getElementById("direccion_entrega").value.trim();
    const nombreCliente = document.getElementById("nombre_cliente").value.trim();
    const telefonoCliente = document.getElementById("telefono_cliente").value.trim();
    const formaPago = document.getElementById("id_forma_pago").value;

    if (!["Mesa", "Take Away", "Delivery"].includes(tipo)) {
        return "Selecciona un tipo de pedido valido.";
    }

    if (tipo === "Mesa" && mesa === "") {
        return "Selecciona una mesa.";
    }

    if ((tipo === "Take Away" || tipo === "Delivery") && nombreCliente === "") {
        return "Ingresa el nombre del cliente.";
    }

    if ((tipo === "Take Away" || tipo === "Delivery") && telefonoCliente === "") {
        return "Ingresa el telefono del cliente.";
    }

    if ((tipo === "Take Away" || tipo === "Delivery") && formaPago === "") {
        return "Selecciona la forma de pago.";
    }

    if (tipo === "Delivery" && direccion === "") {
        return "Ingresa la direccion de entrega.";
    }

    if (carrito.length === 0) {
        return "Agrega al menos un producto al carrito.";
    }

    return "";
}

function confirmarPedido() {
    const error = validarPedido();
    const boton = document.getElementById("confirmarPedido");

    if (error !== "") {
        mostrarMensaje(error, "error");
        return;
    }

    const mesaSelect = document.getElementById("mesa");
    const mesaOption = mesaSelect.options[mesaSelect.selectedIndex];
    const datos = {
        tipo_pedido: document.getElementById("tipo_pedido").value,
        origen: document.getElementById("origen").value,
        id_mesa: mesaSelect.value,
        mesa: mesaOption ? mesaOption.dataset.numero || "" : "",
        nombre_cliente: document.getElementById("nombre_cliente").value.trim(),
        telefono_cliente: document.getElementById("telefono_cliente").value.trim(),
        id_forma_pago: document.getElementById("id_forma_pago").value,
        direccion_entrega: document.getElementById("direccion_entrega").value.trim(),
        observaciones: document.getElementById("observaciones").value.trim(),
        total: calcularTotal(),
        carrito: carrito.map(function (item) {
            return {
                id_producto: item.id_producto,
                cantidad: item.cantidad,
                observaciones: item.observaciones
            };
        })
    };

    boton.disabled = true;
    boton.textContent = "Guardando...";
    mostrarMensaje("Guardando pedido...", "info");

    fetch("guardar.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "Accept": "application/json"
        },
        body: JSON.stringify(datos)
    })
        .then(function (respuesta) { return respuesta.json(); })
        .then(function (respuesta) {
            if (!respuesta.ok) {
                throw new Error(respuesta.mensaje || "No se pudo guardar el pedido.");
            }

            carrito = [];
            actualizarCarrito();
            document.getElementById("observaciones").value = "";
            document.getElementById("direccion_entrega").value = "";
            document.getElementById("nombre_cliente").value = "";
            document.getElementById("telefono_cliente").value = "";
            document.getElementById("id_forma_pago").value = "";
            mostrarMensaje("Pedido " + respuesta.numero_pedido + " guardado correctamente. Total: " + formatoMoneda.format(respuesta.total) + ".", "exito");
        })
        .catch(function (error) {
            mostrarMensaje(error.message, "error");
        })
        .finally(function () {
            boton.disabled = false;
            boton.textContent = "Confirmar Pedido";
        });
}
