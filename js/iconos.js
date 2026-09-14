(function () {
  'use strict';
  const trazos = {
    clasificacion: '<path d="M3 21V12h6v9m0 0V5h6v16m0 0v-6h6v6M2 21h20M11 8h1v3"/>',
    menu: '<path d="M4 6h16M4 12h16M4 18h16"/>',
    salir: '<path d="M10 17l5-5-5-5M15 12H3M14 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5"/>',
    circulo: '<circle cx="12" cy="12" r="7"/>',
    pausa: '<path d="M8 4v16M16 4v16"/>',
    ayuda: '<circle cx="12" cy="12" r="9"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-2.9 2-2.9 4"/><path d="M12 17h.01"/>',
    buscar: '<circle cx="10.5" cy="10.5" r="7"/><path d="m16 16 5 5"/>',
    notificaciones: '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
    alerta: '<path d="m12 3 10 18H2Z M12 9v5m0 3h.01"/>',
    info: '<circle cx="12" cy="12" r="9"/><path d="M12 11v6m0-10h.01"/>',
    reloj: '<circle cx="12" cy="12" r="9"/><path d="M12 6v6l4 2"/>',
    agregar: '<path d="M12 4v16M4 12h16"/>',
    editar: '<path d="m16 3 5 5-12 12-6 1 1-6ZM13 6l5 5"/>',
    eliminar: '<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7"/>',
    actualizar: '<path d="M20 7a9 9 0 0 0-16 2m0-5v5h5m-5 8a9 9 0 0 0 16-2m0 5v-5h-5"/>',
    derecha: '<path d="M4 12h16m-6-6 6 6-6 6"/>',
    izquierda: '<path d="M20 12H4m6-6-6 6 6 6"/>',
    abajo: '<path d="m6 9 6 6 6-6"/>',
    arriba: '<path d="m6 15 6-6 6 6"/>',
    mas: '<circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/>',
    escudo: '<path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6Z"/>',
    base: '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 4 18 4 18 0V5M3 12c0 4 18 4 18 0"/>',
    medalla: '<circle cx="12" cy="15" r="6"/><path d="m5 3 4 7m10-7-4 7M9 3l3 6 3-6"/>',
    juego: '<path d="M7 7h10c5 0 7 14 3 14l-5-4H9l-5 4C0 21 2 7 7 7ZM5 12h6m-3-3v6m8-3h.01m3 2h.01"/>',
    ajedrez: '<path d="M8 21h8l-2-9h-4ZM7 21h10M9 12h6"/><circle cx="12" cy="7" r="4"/>',
    balon: '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3v18M5 5c9 2 12 5 14 14M19 5C10 7 7 10 5 19"/>',
    raqueta: '<ellipse cx="14" cy="9" rx="7" ry="6" transform="rotate(-45 14 9)"/><path d="m9 14-6 7m7-16 9 8"/>',
    cartas: '<rect x="6" y="3" width="14" height="18" rx="2"/><path d="M6 6H3v14m10-13 3 5-3 5-3-5Z"/>',
    usuario: '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-5 4-8 8-8s8 3 8 8"/>',
    participantes: '<circle cx="9" cy="8" r="3"/><path d="M2 21v-2a7 7 0 0 1 14 0v2M17 5a3 3 0 0 1 0 6M18 15a5 5 0 0 1 4 4v2"/>',
    candado: '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
    ojo: '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
    exportar: '<path d="M12 3v12m-5-5 5 5 5-5M4 16v5h16v-5"/>',
    torneo: '<path d="M8 3h8v6a4 4 0 0 1-8 0V3Zm4 10v6m-5 2h10M8 5H4v2a4 4 0 0 0 4 4m8-6h4v2a4 4 0 0 1-4 4"/>',
    panel: '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
    sorteo: '<path d="m17 3 4 4-4 4M3 7h3c6 0 6 10 12 10h3m-4-4 4 4-4 4M3 17h3c2 0 3-2 4-4m4-4c1-1 2-2 4-2h3"/>',
    resultados: '<path d="M4 21V11h4v10m4 0V3h4v18m4 0V7h2v14M2 21h20"/>',
    calendario: '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 11h18m-14 4h2m4 0h2m-8 3h2"/>',
    configuracion: '<path d="M4 6h16M4 12h16M4 18h16"/><circle cx="8" cy="6" r="2"/><circle cx="16" cy="12" r="2"/><circle cx="10" cy="18" r="2"/>',
    actividad: '<path d="M3 12h4l3-8 4 16 3-8h4"/>',
    enfrentamientos: '<path d="m4 4 16 16m0-16L4 20M4 4v5m0-5h5m11 0v5m0-5h-5M2 18l4 4m12 0 4-4"/>',
    sistema: '<rect x="3" y="3" width="18" height="13" rx="2"/><path d="M12 16v5m-5 0h10"/>',
    presencial: '<path d="M12 21s7-6 7-12a7 7 0 1 0-14 0c0 6 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/>',
    virtual: '<rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8m-4-4v4M7 9h2m6 0h2m-8 4h6"/>',
    foto: '<path d="M3 7h4l2-3h6l2 3h4v14H3Z"/><circle cx="12" cy="13" r="4"/>',
    mensaje: '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 5 9 8 9-8"/>',
    exito: '<path d="m4 12 5 5L20 6"/>',
    peligro: '<path d="m6 6 12 12M6 18 18 6"/>',
    sol: '<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1 1m12 12 1 1M5 19l1-1M18 6l1-1"/>',
    luna: '<path d="M20 15A9 9 0 0 1 9 4a9 9 0 1 0 11 11Z"/>'
  };
  function svg(nombre) {
    return '<svg class="icono-ui" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' + (trazos[nombre] || trazos.actividad) + '</svg>';
  }
  window.ArenaCJDIcono = svg;
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.boton-exportar-ui').forEach(function (boton) {
      boton.querySelectorAll('[data-icono="exportar"]').forEach(el => el.remove());
      boton.querySelectorAll('svg').forEach(el => el.remove());
      boton.childNodes.forEach(n => { if (n.nodeType === 3) n.textContent = n.textContent.replace('⇩', ''); });
      boton.insertAdjacentHTML('afterbegin', svg('exportar'));
    });
    function completarIconos(raiz) {
      const pendientes = raiz.matches && raiz.matches('[data-icono]') ? [raiz] : [];
      if (raiz.querySelectorAll) pendientes.push(...raiz.querySelectorAll('[data-icono]'));
      pendientes.forEach(el => { if (el.dataset.iconoListo === el.dataset.icono) return; el.innerHTML = svg(el.dataset.icono); el.dataset.iconoListo = el.dataset.icono; });
    }
    completarIconos(document);
    new MutationObserver(registros => registros.forEach(r => r.addedNodes.forEach(completarIconos))).observe(document.body, {childList:true,subtree:true});
    document.querySelectorAll('[data-boton-tema] .icono-tema-perfil').forEach(el => {
      const tema = el.closest('[data-boton-tema]').dataset.botonTema;
      el.innerHTML = svg(tema === 'claro' ? 'sol' : tema === 'oscuro' ? 'luna' : 'sistema');
    });
  });
}());
