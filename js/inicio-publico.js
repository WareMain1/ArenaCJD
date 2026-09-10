(function () {
  'use strict';

  const proximos = document.getElementById('inicioProximosPublicos');
  const resultados = document.getElementById('inicioResultadosPublicos');
  const torneos = document.getElementById('inicioTorneosPublicos');
  if (!proximos || !resultados || !torneos) return;

  function escapar(valor) {
    return String(valor == null ? '' : valor)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  }

  function fecha(valor) {
    if (!valor) return 'Sin fecha';
    const d = new Date(String(valor).replace(' ', 'T'));
    if (Number.isNaN(d.getTime())) return String(valor);
    return d.toLocaleString('es-UY', {day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit'});
  }

  function renderProximos(items) {
    if (!items.length) {
      proximos.innerHTML = '<div class="estado-vacio-recientes-ah">No hay enfrentamientos próximos publicados.</div>';
      return;
    }
    proximos.innerHTML = items.map(function (item) {
      return '<a class="item-espectador-ah" href="enfrentamientos-publicos.php?torneo=' + Number(item.id_torneo) + '"><strong>' + escapar(item.participante_a || 'Por definir') + ' vs ' + escapar(item.participante_b || 'Por definir') + '</strong><span>' + escapar(item.torneo) + ' · ' + escapar(item.ronda || 'Ronda') + ' · ' + escapar(fecha(item.fecha_hora)) + '</span></a>';
    }).join('');
  }

  function renderResultados(items) {
    if (!items.length) {
      resultados.innerHTML = '<div class="estado-vacio-recientes-ah">Todavía no hay resultados públicos recientes.</div>';
      return;
    }
    resultados.innerHTML = items.map(function (item) {
      return '<a class="item-espectador-ah" href="resultados-publicos.php?torneo=' + Number(item.id_torneo) + '"><strong>' + escapar(item.participante_a || 'Participante A') + ' ' + escapar(item.puntaje_a) + ' – ' + escapar(item.puntaje_b) + ' ' + escapar(item.participante_b || 'Participante B') + '</strong><span>' + escapar(item.torneo) + ' · ' + escapar(item.ronda || 'Ronda') + '</span></a>';
    }).join('');
  }

  function renderTorneos(items) {
    if (!items.length) {
      torneos.innerHTML = '<div class="estado-vacio-recientes-ah">No hay torneos activos publicados en este momento.</div>';
      return;
    }
    torneos.innerHTML = items.map(function (item) {
      const estado = item.estado === 'en_curso' ? 'En curso' : 'Inscripciones';
      return '<a class="item-espectador-ah" href="torneo-publico.php?id=' + Number(item.id_torneo) + '"><strong>' + escapar(item.nombre) + '</strong><span>' + escapar(item.disciplina) + ' · ' + escapar(item.categoria) + ' · ' + estado + ' · ' + Number(item.cantidad_inscritos || 0) + ' inscriptos</span></a>';
    }).join('');
  }

  async function cargar() {
    [proximos, resultados, torneos].forEach(function (contenedor) {
      contenedor.innerHTML = '<div class="skeleton-ah skeleton-linea-ah"></div><div class="skeleton-ah skeleton-linea-ah media"></div><div class="skeleton-ah skeleton-linea-ah corta"></div>';
    });
    try {
      const respuesta = await fetch('api/publico/resumen.php?_=' + Date.now(), {cache:'no-store'});
      const datos = await respuesta.json();
      if (!respuesta.ok || !datos.exito) throw new Error(datos.mensaje || 'No se pudo cargar la actividad pública.');
      renderProximos(Array.isArray(datos.proximos) ? datos.proximos : []);
      renderResultados(Array.isArray(datos.resultados) ? datos.resultados : []);
      renderTorneos(Array.isArray(datos.torneos) ? datos.torneos : []);
    } catch (error) {
      const html = '<div class="estado-vacio-recientes-ah"><strong>No pudimos actualizar esta información.</strong><br><button class="boton boton-claro" type="button" data-reintentar-resumen-publico>Reintentar</button></div>';
      [proximos, resultados, torneos].forEach(function (contenedor) { contenedor.innerHTML = html; });
      document.querySelectorAll('[data-reintentar-resumen-publico]').forEach(function (boton) { boton.addEventListener('click', cargar); });
    }
  }

  cargar();
}());
