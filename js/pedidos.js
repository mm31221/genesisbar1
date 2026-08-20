//=========================================
// GENESISBAR 1.0 - Modulo Pedidos
//=========================================

let carrito = [];
let productosActuales = new Map();
let categoriaActiva = null;
let categoriaNombreActiva = "";

const extrasPorCategoria = {
    Pizza: ["Extra muzza", "Extra salsa"],
    Sushi: ["Extra palitos", "Extra salsa de soja", "Extra wasabi"]
};

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
    document.getElementById("tipo_reserva").addEventListener("change", actualizarCamposEntrega);
    document.getElementById("confirmarPedido").addEventListener("click", confirmarPedido);

    const buscarProducto = document.getElementById("buscarProducto");
    const abrirCarritoMobile = document.getElementById("abrirCarritoMobile");

    if (buscarProducto) {
        buscarProducto.addEventListener("input", filtrarProductosVisibles);
    }

    if (abrirCarritoMobile) {
        abrirCarritoMobile.addEventListener("click", function () {
            document.getElementById("panelCarritoPedido").classList.toggle("abierto");
        });
    }
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

function direccionEntregaTexto() {
    const calle = document.getElementById("direccion_calle");
    const altura = document.getElementById("direccion_altura");
    return [calle ? calle.value.trim() : "", altura ? altura.value.trim() : ""]
        .filter(Boolean)
        .join(" ");
}

function actualizarCamposEntrega() {
    const tipo = document.getElementById("tipo_pedido").value;
    const tipoReserva = document.getElementById("tipo_reserva").value;
    const grupoMesa = document.getElementById("grupoMesa");
    const grupoDireccion = document.getElementById("grupoDireccion");
    const gruposCliente = document.querySelectorAll(".grupoCliente");
    const mesa = document.getElementById("mesa");
    const direccionCalle = document.getElementById("direccion_calle");
    const direccionAltura = document.getElementById("direccion_altura");
    const nombreCliente = document.getElementById("nombre_cliente");
    const telefonoCliente = document.getElementById("telefono_cliente");
    const formaPago = document.getElementById("id_forma_pago");
    const horarioEntrega = document.getElementById("horario_entrega");
    const botonConfirmar = document.getElementById("confirmarPedido");
    const esReserva = tipoReserva !== "Ninguna";
    const requiereCliente = tipo === "Take Away" || tipo === "Delivery" || esReserva;

    grupoMesa.hidden = tipo !== "Mesa";
    grupoDireccion.hidden = tipo !== "Delivery";
    mesa.disabled = tipo !== "Mesa";
    direccionCalle.disabled = tipo !== "Delivery";
    direccionAltura.disabled = tipo !== "Delivery";

    gruposCliente.forEach(function (grupo) {
        grupo.hidden = !requiereCliente;
    });

    nombreCliente.disabled = !requiereCliente;
    telefonoCliente.disabled = !requiereCliente;
    formaPago.disabled = !requiereCliente;
    horarioEntrega.required = esReserva;

    if (tipoReserva === "Mesa" && tipo !== "Mesa") {
        document.getElementById("tipo_pedido").value = "Mesa";
        actualizarCamposEntrega();
        return;
    }

    if (botonConfirmar) {
        botonConfirmar.textContent = tipoReserva === "Mesa" && carrito.length === 0 ? "Guardar reserva" : "Enviar a cocina";
    }

    if (tipo !== "Mesa") {
        mesa.value = "";
    }

    if (tipo !== "Delivery") {
        direccionCalle.value = "";
        direccionAltura.value = "";
        document.getElementById("direccion_entrega").value = "";
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
    const ordenPermitido = ["Pizza", "Sushi", "Empanadas", "Bebidas", "Tragos"];

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

            datos.categorias
                .filter(function (categoria) {
                    return ordenPermitido.includes(categoria.nombre);
                })
                .sort(function (a, b) {
                    return ordenPermitido.indexOf(a.nombre) - ordenPermitido.indexOf(b.nombre);
                })
                .forEach(function (categoria, index) {
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
                    categoriaActiva = categoria.id_categoria;
                    categoriaNombreActiva = categoria.nombre;
                    cargarProductos(categoria.id_categoria);
                });

                contenedor.appendChild(boton);

                if (index === 0) {
                    boton.classList.add("activa");
                    categoriaActiva = categoria.id_categoria;
                    categoriaNombreActiva = categoria.nombre;
                    cargarProductos(categoria.id_categoria);
                }
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
                producto.categoria_nombre = categoriaNombreActiva;
                productosActuales.set(String(producto.id_producto), producto);
                const agotado = Number(producto.stock) <= 0;

                const tarjeta = document.createElement("div");
                tarjeta.className = "producto" + (agotado ? " producto-agotado" : "");
                tarjeta.dataset.nombre = producto.nombre || "";
                tarjeta.dataset.descripcion = producto.descripcion || "";
                tarjeta.innerHTML = `
                    <img class="producto-img-pedido" src="${escaparHtml(producto.imagen_url || "/genesisbar1/assets/img/productos/producto-default.svg")}" alt="">
                    <h4>${escaparHtml(producto.nombre)}</h4>
                    <p>${escaparHtml(producto.descripcion || "")}</p>
                    <strong>${formatoMoneda.format(producto.precio)}</strong>
                    <button class="boton-agregar" type="button" data-id="${producto.id_producto}" ${agotado ? "disabled" : ""}>
                        ${agotado ? "Agotado" : "Agregar"}
                    </button>
                `;

                contenedor.appendChild(tarjeta);
            });
            filtrarProductosVisibles();
        })
        .catch(function (error) {
            contenedor.innerHTML = "<p>No se pudieron cargar los productos.</p>";
            mostrarMensaje(error.message, "error");
        });
}

function filtrarProductosVisibles() {
    const buscador = document.getElementById("buscarProducto");
    const texto = buscador ? buscador.value.trim().toLowerCase() : "";

    document.querySelectorAll("#productos .producto").forEach(function (tarjeta) {
        const contenido = ((tarjeta.dataset.nombre || "") + " " + (tarjeta.dataset.descripcion || "")).toLowerCase();
        tarjeta.hidden = texto !== "" && !contenido.includes(texto);
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
            categoria: producto.categoria_nombre || "",
            cantidad: 1,
            extras: [],
            observaciones: ""
        });
    }

    mostrarMensaje("Producto agregado al carrito.", "exito");
    actualizarCarrito();
    actualizarCamposEntrega();
}

function extrasDisponibles(item) {
    const categoria = String(item.categoria || "").trim();

    if (extrasPorCategoria[categoria]) {
        return extrasPorCategoria[categoria];
    }

    const nombre = String(item.nombre || "").toLowerCase();

    if (nombre.includes("pizza")) {
        return extrasPorCategoria.Pizza;
    }

    if (nombre.includes("sushi") || nombre.includes("roll") || nombre.includes("nigiri")) {
        return extrasPorCategoria.Sushi;
    }

    return [];
}

function observacionCompleta(item) {
    const partes = [];

    if (Array.isArray(item.extras) && item.extras.length > 0) {
        partes.push("Extras: " + item.extras.join(", "));
    }

    if (item.observaciones) {
        partes.push(item.observaciones);
    }

    return partes.join(" | ");
}

function actualizarCarrito() {
    const contenedor = document.getElementById("carrito");
    const totalCarrito = document.getElementById("totalCarrito");
    let total = 0;

    if (carrito.length === 0) {
        contenedor.innerHTML = "<div class=\"carrito-vacio\">No hay productos.</div>";
        totalCarrito.textContent = formatoMoneda.format(0);
        actualizarBarraCarritoMobile(0, 0);
        return;
    }

    let html = "";

    carrito.forEach(function (item, index) {
        const subtotal = item.precio * item.cantidad;
        const extras = extrasDisponibles(item);
        total += subtotal;

        html += `
            <div class="item-carrito">
                <div class="item-carrito__top">
                    <h4>${escaparHtml(item.nombre)}</h4>
                    <button class="eliminar" type="button" data-accion="eliminar" data-index="${index}" aria-label="Eliminar ${escaparHtml(item.nombre)}">&times;</button>
                </div>
                <div class="item-carrito__meta">
                    <span>${formatoMoneda.format(item.precio)} c/u</span>
                    <strong>${formatoMoneda.format(subtotal)}</strong>
                </div>
                <div class="cantidad">
                    <button type="button" data-accion="restar" data-index="${index}" aria-label="Restar">-</button>
                    <input type="number" min="1" max="99" value="${item.cantidad}" data-accion="cantidad" data-index="${index}">
                    <button type="button" data-accion="sumar" data-index="${index}" aria-label="Sumar">+</button>
                </div>
                <label class="label-observacion-item">Observacion del producto</label>
                ${extras.length > 0 ? `
                    <div class="extras-item">
                        ${extras.map(function (extra) {
                            const checked = Array.isArray(item.extras) && item.extras.includes(extra) ? "checked" : "";
                            return `
                                <label>
                                    <input type="checkbox" data-accion="extra" data-index="${index}" value="${escaparHtml(extra)}" ${checked}>
                                    <span>${escaparHtml(extra)}</span>
                                </label>
                            `;
                        }).join("")}
                    </div>
                ` : ""}
                <input class="observacion-item" type="text" maxlength="255" value="${escaparHtml(item.observaciones)}" data-accion="observacion" data-index="${index}">
            </div>
        `;
    });

    contenedor.innerHTML = html;
    totalCarrito.textContent = formatoMoneda.format(total);
    actualizarBarraCarritoMobile(carrito.reduce(function (cantidad, item) {
        return cantidad + item.cantidad;
    }, 0), total);
}

function actualizarBarraCarritoMobile(cantidad, total) {
    const barra = document.getElementById("abrirCarritoMobile");

    if (!barra) {
        return;
    }

    barra.textContent = "Ver carrito (" + cantidad + ") - " + formatoMoneda.format(total);
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

    if (accion === "extra") {
        const extra = event.target.value;
        carrito[index].extras = Array.isArray(carrito[index].extras) ? carrito[index].extras : [];

        if (event.target.checked && !carrito[index].extras.includes(extra)) {
            carrito[index].extras.push(extra);
        }

        if (!event.target.checked) {
            carrito[index].extras = carrito[index].extras.filter(function (itemExtra) {
                return itemExtra !== extra;
            });
        }

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
    const tipoReserva = document.getElementById("tipo_reserva").value;
    const mesa = document.getElementById("mesa").value;
    const direccionCalle = document.getElementById("direccion_calle").value.trim();
    const direccionAltura = document.getElementById("direccion_altura").value.trim();
    const nombreCliente = document.getElementById("nombre_cliente").value.trim();
    const telefonoCliente = document.getElementById("telefono_cliente").value.trim();
    const formaPago = document.getElementById("id_forma_pago").value;
    const horarioEntrega = document.getElementById("horario_entrega").value;
    const esReserva = tipoReserva !== "Ninguna";

    if (!["Mesa", "Take Away", "Delivery"].includes(tipo)) {
        return "Selecciona un tipo de pedido valido.";
    }

    if (!["Ninguna", "Mesa", "Pedido"].includes(tipoReserva)) {
        return "Selecciona un tipo de reserva valido.";
    }

    if (tipoReserva === "Mesa" && tipo !== "Mesa") {
        return "La reserva de mesa debe ser un pedido de mesa.";
    }

    if (tipo === "Mesa" && mesa === "") {
        return "Selecciona una mesa.";
    }

    if ((tipo === "Take Away" || tipo === "Delivery" || esReserva) && nombreCliente === "") {
        return "Ingresa el nombre del cliente.";
    }

    if ((tipo === "Take Away" || tipo === "Delivery" || esReserva) && telefonoCliente === "") {
        return "Ingresa el telefono del cliente.";
    }

    if (esReserva && horarioEntrega === "") {
        return "Ingresa fecha y hora de la reserva.";
    }

    if ((tipo === "Take Away" || tipo === "Delivery") && formaPago === "") {
        return "Selecciona la forma de pago.";
    }

    if (tipo === "Delivery" && (direccionCalle === "" || direccionAltura === "")) {
        return "Ingresa calle y altura para la direccion de entrega.";
    }

    if (carrito.length === 0 && tipoReserva !== "Mesa") {
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
    const direccionCalle = document.getElementById("direccion_calle").value.trim();
    const direccionAltura = document.getElementById("direccion_altura").value.trim();
    document.getElementById("direccion_entrega").value = direccionEntregaTexto();

    const datos = {
        tipo_pedido: document.getElementById("tipo_pedido").value,
        tipo_reserva: document.getElementById("tipo_reserva").value,
        origen: document.getElementById("origen").value,
        id_mesa: mesaSelect.value,
        mesa: mesaOption ? mesaOption.dataset.numero || "" : "",
        nombre_cliente: document.getElementById("nombre_cliente").value.trim(),
        telefono_cliente: document.getElementById("telefono_cliente").value.trim(),
        id_forma_pago: document.getElementById("id_forma_pago").value,
        direccion_calle: direccionCalle,
        direccion_altura: direccionAltura,
        direccion_entrega: document.getElementById("direccion_entrega").value.trim(),
        horario_entrega: document.getElementById("horario_entrega").value,
        observaciones: document.getElementById("observaciones").value.trim(),
        csrf_token: document.getElementById("csrfPedido").value,
        total: calcularTotal(),
        carrito: carrito.map(function (item) {
            return {
                id_producto: item.id_producto,
                cantidad: item.cantidad,
                observaciones: observacionCompleta(item)
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
            document.getElementById("direccion_calle").value = "";
            document.getElementById("direccion_altura").value = "";
            document.getElementById("horario_entrega").value = "";
            document.getElementById("nombre_cliente").value = "";
            document.getElementById("telefono_cliente").value = "";
            document.getElementById("id_forma_pago").value = "";
            document.getElementById("tipo_reserva").value = "Ninguna";
            actualizarCamposEntrega();
            document.getElementById("panelCarritoPedido").classList.remove("abierto");
            mostrarMensaje("Pedido " + respuesta.numero_pedido + " guardado correctamente. Total: " + formatoMoneda.format(respuesta.total) + ".", "exito");
        })
        .catch(function (error) {
            mostrarMensaje(error.message, "error");
        })
        .finally(function () {
            boton.disabled = false;
            actualizarCamposEntrega();
        });
}
