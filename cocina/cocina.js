//=========================================
// GENESISBAR 1.0 - Modulo Cocina
//=========================================

const pedidosVistos = new Set();
let primeraCarga = true;
let actualizando = false;

document.addEventListener("DOMContentLoaded", function () {
    cargarPedidosCocina();
    setInterval(cargarPedidosCocina, 5000);
});

function escaparHtml(texto) {
    const div = document.createElement("div");
    div.textContent = texto == null ? "" : String(texto);
    return div.innerHTML;
}

function mostrarMensajeCocina(texto, tipo) {
    const mensaje = document.getElementById("mensajeCocina");
    mensaje.textContent = texto;
    mensaje.className = "mensaje-pedido " + (tipo || "info");
    mensaje.hidden = texto === "";
}

function textoTiempo(minutos) {
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

    if (horas === 1 && resto === 0) {
        return "Hace 1 hora";
    }

    if (horas === 1) {
        return "Hace 1 hora y " + resto + " min";
    }

    return resto === 0 ? "Hace " + horas + " horas" : "Hace " + horas + " horas y " + resto + " min";
}

function claseTiempo(minutos) {
    if (minutos <= 10) {
        return "tiempo-normal";
    }

    if (minutos <= 25) {
        return "tiempo-demorado";
    }

    return "tiempo-muy-demorado";
}

function renderProductos(productos) {
    if (!productos || productos.length === 0) {
        return "<p>No hay productos cargados.</p>";
    }

    return `
        <ul class="cocina-productos">
            ${productos.map(function (producto) {
                const observaciones = producto.observaciones
                    ? `<small>${escaparHtml(producto.observaciones)}</small>`
                    : "";

                return `
                    <li>
                        <strong>${producto.cantidad} x ${escaparHtml(producto.nombre)}</strong>
                        ${observaciones}
                    </li>
                `;
            }).join("")}
        </ul>
    `;
}

function renderAccionCocina(pedido) {
    if (pedido.estado === "Pendiente") {
        return `<div class="cocina-aviso cocina-aviso-pendiente">Avisar al mozo para pasar a preparacion</div>`;
    }

    if (pedido.estado === "Preparando") {
        return `<div class="cocina-aviso cocina-aviso-listo">Cuando este listo, avisar al mozo</div>`;
    }

    return `<div class="cocina-aviso cocina-aviso-espera">Esperando entrega por mozo</div>`;
}

function renderPedido(pedido) {
    const esNuevo = !primeraCarga && !pedidosVistos.has(pedido.id_pedido);
    pedidosVistos.add(pedido.id_pedido);

    const observaciones = pedido.observaciones
        ? `<div class="cocina-observaciones"><strong>Obs. general</strong><p>${escaparHtml(pedido.observaciones).replace(/\n/g, "<br>")}</p></div>`
        : "";
    const horarioEntrega = pedido.horario_entrega_texto
        ? `<p class="cocina-entrega"><strong>Entrega:</strong> ${escaparHtml(pedido.horario_entrega_texto)}</p>`
        : "";
    const claseDemora = claseTiempo(pedido.minutos_transcurridos);

    return `
        <article class="comanda cocina-card cocina-compacta estado-${pedido.estado.toLowerCase()} ${claseDemora} ${esNuevo ? "pedido-nuevo" : ""}" tabindex="0" data-url="/genesisbar1/pedidos/ver.php?id=${pedido.id_pedido}">
            <div class="comanda-header cocina-card-header">
                <div>
                    <h2>${escaparHtml(pedido.numero_pedido)}</h2>
                    <p class="cocina-destino">${escaparHtml(pedido.destino_produccion || pedido.tipo_pedido || "")}</p>
                    <p class="cocina-meta">${escaparHtml(pedido.tipo_pedido || "")} - ${escaparHtml(pedido.hora || "-")}</p>
                    ${horarioEntrega}
                    <span class="cocina-tiempo">${textoTiempo(pedido.minutos_transcurridos)}</span>
                </div>
                <span class="estado-cocina">${escaparHtml(pedido.estado_texto)}</span>
            </div>

            ${renderProductos(pedido.productos)}

            ${observaciones}

            <div class="cocina-card-acciones">
                ${renderAccionCocina(pedido)}
            </div>
        </article>
    `;
}

document.addEventListener("click", function (event) {
    const tarjeta = event.target.closest(".cocina-card[data-url]");

    if (tarjeta) {
        window.location.href = tarjeta.dataset.url;
    }
});

document.addEventListener("keydown", function (event) {
    if (event.key !== "Enter") {
        return;
    }

    const tarjeta = event.target.closest(".cocina-card[data-url]");

    if (tarjeta) {
        window.location.href = tarjeta.dataset.url;
    }
});

function actualizarContadores(contadores) {
    document.getElementById("contadorPendientes").textContent = contadores.pendientes || 0;
    document.getElementById("contadorPreparando").textContent = contadores.preparando || 0;
    document.getElementById("contadorListos").textContent = contadores.listos || 0;
    document.getElementById("contadorTotal").textContent = contadores.total || 0;
}

function cargarPedidosCocina() {
    if (actualizando) {
        return;
    }

    actualizando = true;

    fetch("api_pedidos.php", {
        headers: { "Accept": "application/json" }
    })
        .then(function (respuesta) { return respuesta.json(); })
        .then(function (datos) {
            if (!datos.ok) {
                throw new Error(datos.mensaje || "No se pudieron cargar los pedidos.");
            }

            const contenedor = document.getElementById("comandas");
            actualizarContadores(datos.contadores || {});
            document.getElementById("ultimaActualizacion").textContent = datos.actualizado || "--:--:--";

            if (datos.pedidos.length === 0) {
                contenedor.innerHTML = "<div class='cocina-vacio'>No hay pedidos activos para preparar.</div>";
            } else {
                contenedor.innerHTML = datos.pedidos.map(renderPedido).join("");
            }

            primeraCarga = false;
            mostrarMensajeCocina("", "info");
        })
        .catch(function (error) {
            mostrarMensajeCocina(error.message, "error");
        })
        .finally(function () {
            actualizando = false;
        });
}
