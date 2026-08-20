document.addEventListener("DOMContentLoaded", function () {
    const periodo = document.getElementById("periodoEstadisticas");
    const fechas = document.querySelectorAll(".fecha-personalizada");
    const datosElemento = document.getElementById("estadisticasData");
    const paleta = ["#1f7a3f", "#2f80ed", "#d99a00", "#9b51e0", "#eb5757", "#00a0a0", "#7a8490"];

    function actualizarFechas() {
        if (!periodo) {
            return;
        }

        const mostrar = periodo.value === "personalizado";
        fechas.forEach(function (campo) {
            campo.classList.toggle("is-hidden", !mostrar);
        });
    }

    function moneda(valor) {
        return "$" + Number(valor || 0).toLocaleString("es-AR", {
            maximumFractionDigits: 0
        });
    }

    function leerDatos() {
        if (!datosElemento) {
            return {};
        }

        try {
            return JSON.parse(datosElemento.textContent || "{}");
        } catch (error) {
            return {};
        }
    }

    function prepararCanvas(canvas) {
        if (typeof canvas.getContext !== "function") {
            return null;
        }

        const rect = canvas.getBoundingClientRect();
        const ratio = window.devicePixelRatio || 1;
        const ancho = Math.max(320, Math.floor(rect.width));
        const alto = Number(canvas.getAttribute("height")) || 240;
        canvas.width = Math.floor(ancho * ratio);
        canvas.height = Math.floor(alto * ratio);
        const ctx = canvas.getContext("2d");
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        ctx.clearRect(0, 0, ancho, alto);
        return {ctx, ancho, alto};
    }

    function svgTexto(svg, contenido, x, y, opciones) {
        const config = Object.assign({size: 11, color: "#303942", anchor: "start", weight: "700"}, opciones || {});
        const text = document.createElementNS("http://www.w3.org/2000/svg", "text");
        text.textContent = String(contenido);
        text.setAttribute("x", x);
        text.setAttribute("y", y);
        text.setAttribute("fill", config.color);
        text.setAttribute("font-size", config.size);
        text.setAttribute("font-weight", config.weight);
        text.setAttribute("text-anchor", config.anchor);
        svg.appendChild(text);
    }

    function dibujarSvgFallback(canvas, items, opciones) {
        const cfg = Object.assign({campo: "valor", modo: "barras"}, opciones || {});
        const vacio = datosVacios(items.map(function (item) {
            return {valor: item[cfg.campo] ?? item.total ?? item.cantidad ?? 0};
        }));
        actualizarVacio(canvas, vacio);

        const ancho = Math.max(320, Math.floor(canvas.getBoundingClientRect().width || 640));
        const alto = Number(canvas.getAttribute("height")) || 240;
        const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
        svg.setAttribute("class", "estadisticas-svg-chart");
        svg.setAttribute("viewBox", "0 0 " + ancho + " " + alto);
        svg.setAttribute("role", "img");

        if (vacio) {
            svgTexto(svg, "Sin datos", 18, 32, {color: "#66717d", size: 14});
            canvas.replaceWith(svg);
            return;
        }

        const margen = {top: 18, right: 18, bottom: 54, left: 42};
        const areaAncho = ancho - margen.left - margen.right;
        const areaAlto = alto - margen.top - margen.bottom;
        const valores = items.map(function (item) {
            return Number(item[cfg.campo] ?? item.total ?? item.cantidad ?? 0);
        });
        const maximo = Math.max.apply(null, valores);

        if (cfg.modo === "linea") {
            const puntos = items.map(function (item, index) {
                const x = margen.left + (items.length === 1 ? areaAncho / 2 : (areaAncho / (items.length - 1)) * index);
                const valor = Number(item[cfg.campo] ?? item.total ?? 0);
                const y = margen.top + areaAlto - ((valor / maximo) * areaAlto);
                return x + "," + y;
            }).join(" ");
            const polyline = document.createElementNS("http://www.w3.org/2000/svg", "polyline");
            polyline.setAttribute("fill", "none");
            polyline.setAttribute("stroke", "#1f7a3f");
            polyline.setAttribute("stroke-width", "3");
            polyline.setAttribute("points", puntos);
            svg.appendChild(polyline);
        } else {
            const paso = areaAncho / Math.max(1, items.length);
            const barraAncho = Math.max(16, Math.min(42, paso * .62));
            items.forEach(function (item, index) {
                const valor = Number(item[cfg.campo] ?? item.total ?? item.cantidad ?? 0);
                const altoBarra = maximo > 0 ? (valor / maximo) * areaAlto : 0;
                const x = margen.left + paso * index + (paso - barraAncho) / 2;
                const y = margen.top + areaAlto - altoBarra;
                const rect = document.createElementNS("http://www.w3.org/2000/svg", "rect");
                rect.setAttribute("x", x);
                rect.setAttribute("y", y);
                rect.setAttribute("width", barraAncho);
                rect.setAttribute("height", altoBarra);
                rect.setAttribute("fill", paleta[index % paleta.length]);
                svg.appendChild(rect);
                svgTexto(svg, String(item.label || "").slice(0, 10), x + barraAncho / 2, alto - 20, {
                    color: "#66717d",
                    size: 10,
                    anchor: "middle"
                });
            });
        }

        canvas.replaceWith(svg);
    }

    function texto(ctx, contenido, x, y, opciones) {
        const config = Object.assign({
            color: "#303942",
            size: 12,
            weight: "700",
            align: "left"
        }, opciones || {});
        ctx.fillStyle = config.color;
        ctx.font = config.weight + " " + config.size + "px Arial, sans-serif";
        ctx.textAlign = config.align;
        ctx.fillText(String(contenido), x, y);
    }

    function datosVacios(items) {
        return !items || items.length === 0 || items.every(function (item) {
            return Number(item.valor ?? item.total ?? item.cantidad ?? 0) === 0;
        });
    }

    function actualizarVacio(canvas, visible) {
        const aviso = document.querySelector("[data-empty-for='" + canvas.dataset.chart + "']");
        if (aviso) {
            aviso.classList.toggle("is-visible", visible);
        }
    }

    function dibujarBarras(canvas, items, opciones) {
        const vacio = datosVacios(items);
        actualizarVacio(canvas, vacio);
        const preparado = prepararCanvas(canvas);

        if (!preparado) {
            dibujarSvgFallback(canvas, items, {campo: (opciones || {}).campo || "valor"});
            return;
        }

        const {ctx, ancho, alto} = preparado;

        if (vacio) {
            texto(ctx, "Sin datos", 16, 32, {color: "#66717d", size: 14});
            return;
        }

        const cfg = Object.assign({campo: "valor", formato: "numero"}, opciones || {});
        const margen = {top: 18, right: 18, bottom: 58, left: 48};
        const areaAncho = ancho - margen.left - margen.right;
        const areaAlto = alto - margen.top - margen.bottom;
        const maximo = Math.max.apply(null, items.map(function (item) { return Number(item[cfg.campo] || 0); }));
        const paso = areaAncho / Math.max(1, items.length);
        const barraAncho = Math.max(16, Math.min(42, paso * .62));

        ctx.strokeStyle = "#e8edf2";
        ctx.lineWidth = 1;
        for (let i = 0; i <= 4; i++) {
            const y = margen.top + areaAlto - (areaAlto / 4) * i;
            ctx.beginPath();
            ctx.moveTo(margen.left, y);
            ctx.lineTo(ancho - margen.right, y);
            ctx.stroke();
        }

        items.forEach(function (item, index) {
            const valor = Number(item[cfg.campo] || 0);
            const altoBarra = maximo > 0 ? (valor / maximo) * areaAlto : 0;
            const x = margen.left + paso * index + (paso - barraAncho) / 2;
            const y = margen.top + areaAlto - altoBarra;

            ctx.fillStyle = paleta[index % paleta.length];
            ctx.fillRect(x, y, barraAncho, altoBarra);

            texto(ctx, cfg.formato === "moneda" ? moneda(valor) : valor, x + barraAncho / 2, y - 6, {
                color: "#303942",
                size: 11,
                align: "center"
            });

            const label = String(item.label || item.fecha || "").slice(0, 12);
            texto(ctx, label, x + barraAncho / 2, alto - 28, {
                color: "#66717d",
                size: 10,
                align: "center"
            });
        });
    }

    function dibujarLinea(canvas, items) {
        const vacio = datosVacios(items.map(function (item) {
            return {valor: item.total};
        }));
        actualizarVacio(canvas, vacio);
        const preparado = prepararCanvas(canvas);

        if (!preparado) {
            dibujarSvgFallback(canvas, items, {campo: "total", modo: "linea"});
            return;
        }

        const {ctx, ancho, alto} = preparado;

        if (vacio) {
            texto(ctx, "Sin datos", 16, 32, {color: "#66717d", size: 14});
            return;
        }

        const margen = {top: 22, right: 20, bottom: 42, left: 56};
        const areaAncho = ancho - margen.left - margen.right;
        const areaAlto = alto - margen.top - margen.bottom;
        const maximo = Math.max.apply(null, items.map(function (item) { return Number(item.total || 0); }));
        const puntos = items.map(function (item, index) {
            const x = margen.left + (items.length === 1 ? areaAncho / 2 : (areaAncho / (items.length - 1)) * index);
            const y = margen.top + areaAlto - ((Number(item.total || 0) / maximo) * areaAlto);
            return {x, y, item};
        });

        ctx.strokeStyle = "#e8edf2";
        for (let i = 0; i <= 4; i++) {
            const y = margen.top + areaAlto - (areaAlto / 4) * i;
            ctx.beginPath();
            ctx.moveTo(margen.left, y);
            ctx.lineTo(ancho - margen.right, y);
            ctx.stroke();
        }

        ctx.strokeStyle = "#1f7a3f";
        ctx.lineWidth = 3;
        ctx.beginPath();
        puntos.forEach(function (punto, index) {
            if (index === 0) {
                ctx.moveTo(punto.x, punto.y);
            } else {
                ctx.lineTo(punto.x, punto.y);
            }
        });
        ctx.stroke();

        puntos.forEach(function (punto) {
            ctx.fillStyle = "#fff";
            ctx.strokeStyle = "#1f7a3f";
            ctx.lineWidth = 3;
            ctx.beginPath();
            ctx.arc(punto.x, punto.y, 4, 0, Math.PI * 2);
            ctx.fill();
            ctx.stroke();
        });

        puntos.forEach(function (punto, index) {
            if (index % Math.ceil(items.length / 8) === 0 || index === items.length - 1) {
                texto(ctx, punto.item.label, punto.x, alto - 16, {
                    color: "#66717d",
                    size: 10,
                    align: "center"
                });
            }
        });

        texto(ctx, moneda(maximo), margen.left, 14, {color: "#66717d", size: 11});
    }

    function dibujarDona(canvas, items, campo) {
        const vacio = datosVacios(items.map(function (item) {
            return {valor: item[campo]};
        }));
        actualizarVacio(canvas, vacio);
        const preparado = prepararCanvas(canvas);

        if (!preparado) {
            dibujarSvgFallback(canvas, items, {campo: campo});
            return;
        }

        const {ctx, ancho, alto} = preparado;

        if (vacio) {
            texto(ctx, "Sin datos", 16, 32, {color: "#66717d", size: 14});
            return;
        }

        const total = items.reduce(function (sum, item) {
            return sum + Number(item[campo] || 0);
        }, 0);
        const centroX = Math.min(ancho * .36, 150);
        const centroY = alto / 2;
        const radio = Math.min(82, alto * .32, ancho * .25);
        let inicio = -Math.PI / 2;

        items.forEach(function (item, index) {
            const valor = Number(item[campo] || 0);
            const angulo = total > 0 ? (valor / total) * Math.PI * 2 : 0;
            ctx.beginPath();
            ctx.moveTo(centroX, centroY);
            ctx.arc(centroX, centroY, radio, inicio, inicio + angulo);
            ctx.closePath();
            ctx.fillStyle = paleta[index % paleta.length];
            ctx.fill();
            inicio += angulo;
        });

        ctx.beginPath();
        ctx.arc(centroX, centroY, radio * .54, 0, Math.PI * 2);
        ctx.fillStyle = "#fff";
        ctx.fill();
        texto(ctx, "Total", centroX, centroY - 4, {align: "center", color: "#66717d", size: 11});
        texto(ctx, total, centroX, centroY + 14, {align: "center", color: "#303942", size: 15});

        const leyendaX = centroX + radio + 28;
        items.forEach(function (item, index) {
            const y = 34 + index * 24;
            ctx.fillStyle = paleta[index % paleta.length];
            ctx.fillRect(leyendaX, y - 10, 12, 12);
            texto(ctx, String(item.label).slice(0, 22), leyendaX + 18, y, {size: 12});
        });
    }

    function renderizarGraficos() {
        const datos = leerDatos();
        document.querySelectorAll(".estadisticas-chart").forEach(function (canvas) {
            const tipo = canvas.dataset.chart;

            if (tipo === "ventasPorDia") {
                dibujarLinea(canvas, datos.ventasPorDia || []);
            } else if (tipo === "formasPago") {
                dibujarDona(canvas, datos.formasPago || [], "valor");
            } else if (tipo === "tiposPedido") {
                dibujarDona(canvas, datos.tiposPedido || [], "valor");
            } else if (tipo === "productos") {
                dibujarBarras(canvas, datos.productos || [], {campo: "valor"});
            } else if (tipo === "horas") {
                dibujarBarras(canvas, datos.horas || [], {campo: "valor"});
            }
        });
    }

    if (periodo) {
        periodo.addEventListener("change", actualizarFechas);
        actualizarFechas();
    }

    renderizarGraficos();
    window.addEventListener("resize", function () {
        window.clearTimeout(window.estadisticasResizeTimer);
        window.estadisticasResizeTimer = window.setTimeout(renderizarGraficos, 120);
    });
});
