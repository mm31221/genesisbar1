//=========================================
// GENESISBAR 1.0 - Listado de Pedidos
//=========================================

let filtroEstado = "Pendiente";

const monedaPedidos = new Intl.NumberFormat("es-AR", {
    style: "currency",
    currency: "ARS",
    maximumFractionDigits: 0
});

document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".filtro-estado").forEach(function (boton) {
        boton.addEventListener("click", function () {
            filtroEstado = boton.dataset.estado;
            document.querySelectorAll(".filtro-estado").forEach(function (item) {
                item.classList.remove("activo");
            });
            boton.classList.add("activo");
            cargarPedidos();
        });
    });

    cargarPedidos();
});

function escaparPedido(texto) {
    const div = document.createElement("div");
    div.textContent = texto == null ? "" : String(texto);
    return div.innerHTML;
}

function mostrarMensajeListado(texto, tipo) {
    const mensaje = document.getElementById("mensajeListadoPedidos");
    mensaje.textContent = texto;
    mensaje.className = "mensaje-pedido " + (tipo || "info");
    mensaje.hidden = texto === "";
}

function destinoPedido(pedido) {
    if (pedido.tipo_pedido === "Mesa") {
        return pedido.mesa ? "Mesa " + pedido.mesa : "";
    }

    if (pedido.tipo_pedido === "Delivery") {
        return pedido.direccion_entrega || "";
    }

    const datos = [];

    if (pedido.nombre_cliente) {
        datos.push(pedido.nombre_cliente);
    }

    if (pedido.telefono_cliente) {
        datos.push(pedido.telefono_cliente);
    }

    return datos.join(" - ");
}

function datosPorTipo(pedido) {
    const datos = [];

    datos.push("<strong>Tipo:</strong> " + escaparPedido(pedido.tipo_pedido));

    if (pedido.tipo_pedido === "Mesa" && pedido.mesa) {
        datos.push("<strong>Mesa:</strong> " + escaparPedido(pedido.mesa));
    }

    if ((pedido.tipo_pedido === "Take Away" || pedido.tipo_pedido === "Delivery") && pedido.nombre_cliente) {
        datos.push("<strong>Cliente:</strong> " + escaparPedido(pedido.nombre_cliente));
    }

    if ((pedido.tipo_pedido === "Take Away" || pedido.tipo_pedido === "Delivery") && pedido.telefono_cliente) {
        datos.push("<strong>Telefono:</strong> " + escaparPedido(pedido.telefono_cliente));
    }

    if (pedido.tipo_pedido === "Delivery" && pedido.direccion_entrega) {
        datos.push("<strong>Direccion:</strong> " + escaparPedido(pedido.direccion_entrega));
    }

    return datos.join("<br>");
}

function pagoPedido(pedido) {
    if (pedido.tipo_pedido === "Mesa" || !pedido.forma_pago) {
        return "";
    }

    return `<span class="pago-pedido">Pago: ${escaparPedido(pedido.forma_pago)}</span>`;
}

function productosPedido(pedido) {
    if (!pedido.productos || pedido.productos.length === 0) {
        return "-";
    }

    return pedido.productos.map(function (producto) {
        const observacion = producto.observaciones
            ? `<small>Obs: ${escaparPedido(producto.observaciones)}</small>`
            : "";
        return `<li><strong>${producto.cantidad} x ${escaparPedido(producto.nombre)}</strong>${observacion}</li>`;
    }).join("");
}

function opcionesEstado(pedido) {
    const estados = ["Pendiente", "Preparando", "Listo", "Entregado"];
    const entregado = pedido.estado === "Entregado";

    if (entregado) {
        return `<span class="estado entregado">Entregado</span>`;
    }

    return `
        <select class="selector-estado estado-${pedido.estado.toLowerCase()}" data-id="${pedido.id_pedido}">
            ${estados.map(function (estado) {
                return `<option value="${estado}" ${estado === pedido.estado ? "selected" : ""}>${estado}</option>`;
            }).join("")}
        </select>
    `;
}

function renderPedidosNuevos(pedidos) {
    const contenedor = document.getElementById("pedidosNuevos");
    const nuevos = pedidos.filter(function (pedido) {
        return pedido.estado === "Pendiente";
    }).slice(0, 4);

    if (nuevos.length === 0) {
        contenedor.innerHTML = "<div class='pedido-nuevo-vacio'>No hay pedidos nuevos.</div>";
        return;
    }

    contenedor.innerHTML = nuevos.map(function (pedido) {
        const usuario = pedido.id_usuario
            ? `<p><strong>Usuario:</strong> #${pedido.id_usuario}${pedido.nombre_usuario ? " - " + escaparPedido(pedido.nombre_usuario) : ""}</p>`
            : "";

        return `
            <article class="pedido-nuevo-card">
                <h3>${escaparPedido(pedido.numero_pedido)}</h3>
                <p><strong>Origen:</strong> ${escaparPedido(pedido.origen)}</p>
                ${usuario}
                <p>${datosPorTipo(pedido)}</p>
                <ul>${productosPedido(pedido)}</ul>
                ${pedido.observaciones ? `<p><strong>Obs:</strong> ${escaparPedido(pedido.observaciones)}</p>` : ""}
            </article>
        `;
    }).join("");
}

function renderTabla(pedidos) {
    const cuerpo = document.getElementById("tablaPedidosBody");

    if (pedidos.length === 0) {
        cuerpo.innerHTML = "<tr><td colspan='7'>No hay pedidos para este estado.</td></tr>";
        return;
    }

    cuerpo.innerHTML = pedidos.map(function (pedido) {
        return `
            <tr>
                <td data-label="Pedido">
                    <strong>${escaparPedido(pedido.numero_pedido)}</strong>
                    <small>${escaparPedido(pedido.origen)}</small>
                </td>
                <td data-label="Tipo">${datosPorTipo(pedido)}</td>
                <td data-label="Destino">${escaparPedido(destinoPedido(pedido)) || "-"}</td>
                <td data-label="Estado">${opcionesEstado(pedido)}</td>
                <td data-label="Total">
                    ${monedaPedidos.format(pedido.total)}
                    ${pagoPedido(pedido)}
                </td>
                <td data-label="Hora">${escaparPedido(pedido.hora)}</td>
                <td data-label="Acciones" class="acciones-tabla">
                    <a class="btn-ver" href="ver.php?id=${pedido.id_pedido}">Ver</a>
                </td>
            </tr>
        `;
    }).join("");
}

function actualizarContadores(contadores) {
    document.querySelector("[data-contador='Pendiente']").textContent = contadores.Pendiente || 0;
    document.querySelector("[data-contador='Preparando']").textContent = contadores.Preparando || 0;
    document.querySelector("[data-contador='Listo']").textContent = contadores.Listo || 0;
    document.querySelector("[data-contador='Entregado']").textContent = contadores.Entregado || 0;
}

function cargarPedidos() {
    fetch("api_pedidos.php?estado=" + encodeURIComponent(filtroEstado), {
        headers: { "Accept": "application/json" }
    })
        .then(function (respuesta) { return respuesta.json(); })
        .then(function (datos) {
            if (!datos.ok) {
                throw new Error(datos.mensaje || "No se pudieron cargar los pedidos.");
            }

            actualizarContadores(datos.contadores || {});
            renderTabla(datos.pedidos || []);
            renderPedidosNuevos(datos.pedidos || []);
            document.getElementById("ultimaActualizacionPedidos").textContent = datos.actualizado || "--:--:--";
            mostrarMensajeListado("", "info");
        })
        .catch(function (error) {
            mostrarMensajeListado(error.message, "error");
        });
}

document.addEventListener("change", function (event) {
    if (!event.target.classList.contains("selector-estado")) {
        return;
    }

    const idPedido = Number(event.target.dataset.id);
    const estado = event.target.value;

    if (estado === "Entregado" && !confirm("Confirmar que el pedido fue entregado.")) {
        cargarPedidos();
        return;
    }

    fetch("actualizar_estado.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "Accept": "application/json"
        },
        body: JSON.stringify({
            id_pedido: idPedido,
            estado: estado
        })
    })
        .then(function (respuesta) { return respuesta.json(); })
        .then(function (datos) {
            if (!datos.ok) {
                throw new Error(datos.mensaje || "No se pudo cambiar el estado.");
            }

            mostrarMensajeListado(datos.mensaje, "exito");
            cargarPedidos();
        })
        .catch(function (error) {
            mostrarMensajeListado(error.message, "error");
            cargarPedidos();
        });
});
