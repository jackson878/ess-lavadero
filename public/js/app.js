/**
 * ESS-Lavadero - JavaScript común a todas las páginas.
 *
 * 1. Búsqueda en tablas:
 *      <input data-buscar="#tablaUsuarios">
 *    filtra las filas del <tbody> de esa tabla mientras se escribe.
 *
 * 2. Confirmación antes de enviar un formulario:
 *      <form data-confirmar="¿Seguro que desea desactivar este usuario?">
 */
(function () {
    'use strict';

    // Quita tildes y pasa a minúsculas: "Rodríguez" -> "rodriguez"
    const normalizar = (texto) =>
        texto.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');

    // ---------- 1. Búsqueda en tablas ----------
    document.querySelectorAll('[data-buscar]').forEach((campo) => {
        const tabla = document.querySelector(campo.dataset.buscar);
        if (!tabla) return;

        const filas = Array.from(tabla.querySelectorAll('tbody tr'));
        const columnas = tabla.querySelectorAll('thead th').length || 1;

        // Fila que se muestra cuando no hay coincidencias
        const sinResultados = document.createElement('tr');
        sinResultados.innerHTML = '<td colspan="' + columnas + '" class="text-center text-secondary py-4">' +
            'No hay resultados para la búsqueda.</td>';
        sinResultados.hidden = true;
        tabla.querySelector('tbody').appendChild(sinResultados);

        campo.addEventListener('input', () => {
            const buscado = normalizar(campo.value.trim());
            let visibles = 0;
            filas.forEach((fila) => {
                const coincide = normalizar(fila.textContent).includes(buscado);
                fila.hidden = !coincide;
                if (coincide) visibles++;
            });
            sinResultados.hidden = visibles > 0;
        });
    });

    // ---------- 2. Confirmaciones ----------
    document.querySelectorAll('form[data-confirmar]').forEach((formulario) => {
        formulario.addEventListener('submit', (evento) => {
            if (!window.confirm(formulario.dataset.confirmar)) {
                evento.preventDefault();
            }
        });
    });
})();
