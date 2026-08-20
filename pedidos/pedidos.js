//=========================================
// GENESISBAR 1.0 - Listado de Pedidos
//=========================================

let filtroEstado = "Todos";

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

function nombreClientePedido(pedido) {
    return pedido.nombre_cliente || pedido.nombre_manual || pedido.cliente || "Cliente no identificado";
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

function textoTipoReserva(pedido) {
    if (pedido.tipo_reserva === "Mesa") {
        return "Reserva de mesa";
    }

    if (pedido.tipo_reserva === "Pedido") {
        return "Pedido programado";
    }

    return "";
}

function pagoPedido(pedido) {
    if (pedido.tipo_pedido === "Mesa" || !pedido.forma_pago) {
        return "";
    }

    return `<span class="pago-pedido">Pago: ${escaparPedido(pedido.forma_pago)}</span>`;
}

function textoTiempoPedido(minutos) {
    minutos = Number(minutos) || 0;

    if (minutos < 1) {
        return "Hace menos de 1 minuto";
    }

    if (minutos === 1) {
        return "Hace 1 minuto";
    }

    if (minutos < 60) {
        return "Hace " + minutos + " minutos";
    }

    const horas = Math.floor(minutos / 60);
    const resto = minutos % 60;

    if (resto === 0) {
        return "Hace " + horas + (horas === 1 ? " hora" : " horas");
    }

    return "Hace " + horas + (horas === 1 ? " hora" : " horas") + " y " + resto + " min";
}

function resumenProductosPedido(pedido) {
    if (!pedido.productos || pedido.productos.length === 0) {
        return "";
    }

    const visibles = pedido.productos.slice(0, 3).map(function (producto) {
        return producto.cantidad + " x " + producto.nombre;
    });

    if (pedido.productos.length > 3) {
        visibles.push("+" + (pedido.productos.length - 3) + " mas");
    }

    return visibles.join(", ");
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
    const estados = Array.isArray(pedido.estados_permitidos) && pedido.estados_permitidos.length > 0
        ? pedido.estados_permitidos
        : [pedido.estado];
    const estadoCerrado = estados.length <= 1 && !pedido.siguiente_estado;

    if (estadoCerrado) {
        return `<span class="estado estado-${escaparPedido(String(pedido.estado).toLowerCase())}">${escaparPedido(pedido.estado_texto || pedido.estado)}</span>`;
    }

    return `
        <select class="selector-estado estado-${pedido.estado.toLowerCase()}" data-id="${pedido.id_pedido}">
            ${estados.map(function (estado) {
                return `<option value="${estado}" ${estado === pedido.estado ? "selected" : ""}>${estado}</option>`;
            }).join("")}
        </select>
    `;
}

function renderPedidos(pedidos) {
    const cuerpo = document.getElementById("pedidosGrid");

    if (pedidos.length === 0) {
        cuerpo.innerHTML = "<div class='pedido-card'>No hay pedidos para este estado.</div>";
        return;
    }

    cuerpo.innerHTML = pedidos.map(function (pedido) {
        const destino = destinoPedido(pedido);
        const reserva = textoTipoReserva(pedido);
        return `
            <article class="pedido-card" tabindex="0" data-url="ver.php?id=${pedido.id_pedido}">
                <div class="pedido-card__top">
                    <h3>${escaparPedido(pedido.numero_pedido)}</h3>
                    <span class="estado estado-${escaparPedido(String(pedido.estado).toLowerCase())}">${escaparPedido(pedido.estado)}</span>
                </div>
                <p><strong>Cliente:</strong> ${escaparPedido(nombreClientePedido(pedido))}</p>
                <p><strong>Tipo:</strong> ${escaparPedido(pedido.tipo_pedido)}</p>
                ${reserva ? `<p><strong>Reserva:</strong> ${escaparPedido(reserva)}</p>` : ""}
                <p><strong>Destino:</strong> ${escaparPedido(destino || "-")}</p>
                <p><strong>Hora:</strong> ${escaparPedido(pedido.hora)}</p>
                ${pedido.horario_entrega_texto ? `<p class="horario-entrega"><strong>Entrega:</strong> ${escaparPedido(pedido.horario_entrega_texto)}</p>` : ""}
                <p><strong>Tiempo:</strong> ${escaparPedido(textoTiempoPedido(pedido.minutos_transcurridos))}</p>
                ${resumenProductosPedido(pedido) ? `<p><strong>Comanda:</strong> ${escaparPedido(resumenProductosPedido(pedido))}</p>` : ""}
                ${pedido.observaciones ? `<p><strong>Obs:</strong> ${escaparPedido(pedido.observaciones)}</p>` : ""}
                <p><strong>Pago:</strong> ${escaparPedido(pedido.estado_pago || "Pendiente")}</p>
                <div class="pedido-card__total">
                    <strong>${monedaPedidos.format(pedido.total)}</strong>
                    <div class="pedido-card__acciones">
                        ${opcionesEstado(pedido)}
                        <a class="btn-ver" href="ver.php?id=${pedido.id_pedido}">Ver</a>
                    </div>
                </div>
            </article>
        `;
    }).join("");
}

function actualizarContadores(contadores) {
    document.querySelector("[data-contador='Todos']").textContent = contadores.Todos || 0;
    document.querySelector("[data-contador='Pendiente']").textContent = contadores.Pendiente || 0;
    document.querySelector("[data-contador='Preparando']").textContent = contadores.Preparando || 0;
    document.querySelector("[data-contador='Listo']").textContent = contadores.Listo || 0;
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
            renderPedidos(datos.pedidos || []);
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
            estado: estado,
            csrf_token: document.getElementById("csrfPedidosListado").value
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

document.addEventListener("click", function (event) {
    const controlInterno = event.target.closest("a, button, select, input");
    const tarjeta = event.target.closest(".pedido-card[data-url]");

    if (!tarjeta || controlInterno) {
        return;
    }

    window.location.href = tarjeta.dataset.url;
});

document.addEventListener("keydown", function (event) {
    if (event.key !== "Enter") {
        return;
    }

    const tarjeta = event.target.closest(".pedido-card[data-url]");

    if (tarjeta) {
        window.location.href = tarjeta.dataset.url;
    }
});
