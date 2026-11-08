/**
 * ESS-Lavadero - Ayudas de la caja (RF-05).
 *
 *  - Cobro: muestra la calculadora de vueltas para efectivo o el campo de
 *    comprobante para Nequi/transferencia y tarjeta.
 *  - Cierre: calcula en vivo la diferencia entre el efectivo contado y el
 *    esperado (faltante en rojo, sobrante en ámbar, exacto en verde).
 *
 * Son solo ayudas visuales: el servidor vuelve a calcular todo al guardar.
 */
(function () {
    'use strict';

    const aNumero = (texto) => Number(String(texto).replace(/\D/g, '')) || 0;
    const pesos = (v) => (v < 0 ? '- ' : '') + '$ ' + Math.abs(Math.round(v)).toLocaleString('es-CO');

    // ---------- Cobro ----------
    const formCobro = document.getElementById('formCobro');
    if (formCobro) {
        const bloqueEfectivo = document.getElementById('bloqueEfectivo');
        const bloqueReferencia = document.getElementById('bloqueReferencia');
        const recibido = document.getElementById('recibido');
        const vueltas = document.getElementById('vueltas');
        const total = Number(vueltas.dataset.total);

        const actualizarMetodo = () => {
            const metodo = (formCobro.querySelector('input[name="metodo"]:checked') || {}).value;
            bloqueEfectivo.hidden = metodo !== 'efectivo';
            bloqueReferencia.hidden = metodo === 'efectivo';
        };

        const actualizarVueltas = () => {
            const valor = aNumero(recibido.value);
            if (!valor) {
                vueltas.textContent = '$ 0';
                vueltas.className = 'fs-4 fw-bold';
                return;
            }
            const cambio = valor - total;
            vueltas.textContent = cambio >= 0 ? pesos(cambio) : 'Faltan ' + pesos(-cambio);
            vueltas.className = 'fs-4 fw-bold ' + (cambio >= 0 ? 'text-success' : 'text-danger');
        };

        formCobro.querySelectorAll('input[name="metodo"]').forEach((r) => r.addEventListener('change', actualizarMetodo));
        recibido.addEventListener('input', actualizarVueltas);
        actualizarMetodo();

        // Evita cobrar dos veces por doble clic
        formCobro.addEventListener('submit', () => {
            const boton = formCobro.querySelector('button[type="submit"]');
            setTimeout(() => { boton.disabled = true; boton.textContent = 'Registrando cobro…'; }, 0);
        });
    }

    // ---------- Cierre ----------
    const contado = document.getElementById('efectivo_contado');
    const caja = document.getElementById('diferencia');
    if (contado && caja) {
        const esperado = Number(contado.dataset.esperado);
        const valor = caja.querySelector('.valor');

        const actualizar = () => {
            if (contado.value.trim() === '') {
                valor.textContent = '—';
                caja.className = 'diferencia mt-3';
                return;
            }
            const dif = aNumero(contado.value) - esperado;
            valor.textContent = pesos(dif) + (dif < 0 ? ' (faltante)' : dif > 0 ? ' (sobrante)' : ' (cuadra)');
            caja.className = 'diferencia mt-3 ' + (dif < 0 ? 'faltante' : dif > 0 ? 'sobrante' : 'exacta');
        };

        contado.addEventListener('input', actualizar);
        actualizar();
    }
})();
