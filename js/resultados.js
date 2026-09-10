(function () {
  'use strict';

  const lista = document.getElementById('listaResultados');
  if (!lista) return;

  const controles = {
    buscar: document.getElementById('buscarResultado'),
    torneo: document.getElementById('filtroTorneoResultado'),
    disciplina: document.getElementById('filtroDisciplinaResultado'),
    estado: document.getElementById('filtroEstadoResultado'),
    ronda: document.getElementById('filtroRondaResultado'),
    fecha: document.getElementById('filtroFechaResultado')
  };
  const sinResultados = document.getElementById('mensajeSinResultados');
  const contador = document.getElementById('contadorResultados');
  const exportar = document.querySelector('.boton-exportar-resultados');

  let datos = [];
  let csrf = '';
  let usuarioId = 0;
  let esAdministrador = false;
  let esOrganizador = false;
  let torneoUrlAplicado = false;
  let primeraCargaResultados = true;

  function escapar(valor) {
    return String(valor == null ? '' : valor)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  }

  function normalizar(valor) {
    return String(valor || '').toLocaleLowerCase('es')
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
  }

  function iniciales(valor) {
    return String(valor || '?').split(/\s+/).slice(0, 2)
      .map(function (parte) { return parte[0] || ''; }).join('').toUpperCase();
  }

  function formatearFecha(valor) {
    if (!valor) return 'Sin programar';
    const fecha = new Date(String(valor).replace(' ', 'T'));
    return Number.isNaN(fecha.getTime())
      ? valor
      : new Intl.DateTimeFormat('es-UY', {dateStyle: 'short', timeStyle: 'short'}).format(fecha);
  }

  function ganador(enfrentamiento) {
    if (enfrentamiento.estado !== 'finalizado' || enfrentamiento.puntaje_a == null || enfrentamiento.puntaje_b == null) return 'Pendiente';
    if (enfrentamiento.ganador) return enfrentamiento.ganador;
    if (Number(enfrentamiento.puntaje_a) === Number(enfrentamiento.puntaje_b)) return 'Empate';
    return Number(enfrentamiento.puntaje_a) > Number(enfrentamiento.puntaje_b)
      ? enfrentamiento.participante_a
      : enfrentamiento.participante_b;
  }

  function ganadorConPuntajes(enfrentamiento, puntajeA, puntajeB) {
    if (puntajeA === '' || puntajeB === '') return 'Ingresa ambos puntajes para calcular el ganador.';
    const a = Number(puntajeA);
    const b = Number(puntajeB);
    if (!Number.isInteger(a) || !Number.isInteger(b) || a < 0 || b < 0) return 'Los puntajes deben ser enteros positivos o cero.';
    if (a === b) return enfrentamiento.es_eliminacion ? 'En eliminación directa debe existir un ganador.' : 'Resultado: empate.';
    return 'Ganador automático: ' + (a > b ? enfrentamiento.participante_a : enfrentamiento.participante_b);
  }

  function puedeGestionar(enfrentamiento) {
    return esAdministrador || (esOrganizador && Number(enfrentamiento.id_organizador) === usuarioId);
  }

  function poblarFiltro(select, valorBase, textoBase, valores) {
    if (!select) return;
    const anterior = select.value;
    const unicos = Array.from(new Set(valores.filter(Boolean))).sort(function (a, b) {
      const na = Number(String(a).match(/\d+/)?.[0] || 0);
      const nb = Number(String(b).match(/\d+/)?.[0] || 0);
      return (na && nb ? na - nb : 0) || String(a).localeCompare(String(b), 'es', {numeric: true});
    });
    select.innerHTML = '<option value="' + escapar(valorBase) + '">' + escapar(textoBase) + '</option>' +
      unicos.map(function (valor) { return '<option value="' + escapar(valor) + '">' + escapar(valor) + '</option>'; }).join('');
    if (Array.from(select.options).some(function (opcion) { return opcion.value === anterior; })) select.value = anterior;
  }

  function poblarFiltroTorneos() {
    if (!controles.torneo) return;
    const anterior = controles.torneo.value;
    const mapa = new Map();
    datos.forEach(function (e) {
      if (e.id_torneo != null && e.torneo) mapa.set(String(e.id_torneo), e.torneo);
    });
    controles.torneo.innerHTML = '<option value="todos">Todos los torneos</option>' +
      Array.from(mapa.entries()).sort(function (a, b) { return String(a[1]).localeCompare(String(b[1]), 'es'); })
        .map(function (par) { return '<option value="' + escapar(par[0]) + '">' + escapar(par[1]) + '</option>'; }).join('');
    if (Array.from(controles.torneo.options).some(function (opcion) { return opcion.value === anterior; })) controles.torneo.value = anterior;
  }

  function actualizarFiltros() {
    poblarFiltroTorneos();
    poblarFiltro(controles.disciplina, 'todas', 'Todas las disciplinas', datos.map(function (e) { return e.disciplina; }));
    poblarFiltro(controles.ronda, 'todas', 'Todas las rondas', datos.map(function (e) { return e.ronda; }));
  }

  function coincide(enfrentamiento) {
    const texto = normalizar(controles.buscar && controles.buscar.value);
    const busqueda = normalizar(enfrentamiento.participante_a + ' ' + enfrentamiento.participante_b + ' ' + enfrentamiento.torneo);
    const estadoVista = enfrentamiento.estado === 'finalizado'
      ? 'confirmado'
      : (enfrentamiento.estado === 'cancelado' ? 'cancelado' : 'pendiente');
    return (!texto || busqueda.includes(texto))
      && (!controles.torneo || controles.torneo.value === 'todos' || String(enfrentamiento.id_torneo) === String(controles.torneo.value))
      && (!controles.disciplina || controles.disciplina.value === 'todas' || enfrentamiento.disciplina === controles.disciplina.value)
      && (!controles.estado || controles.estado.value === 'todos' || controles.estado.value === estadoVista)
      && (!controles.ronda || controles.ronda.value === 'todas' || enfrentamiento.ronda === controles.ronda.value)
      && (!controles.fecha || !controles.fecha.value || String(enfrentamiento.fecha_hora || '').slice(0, 10) === controles.fecha.value);
  }

  function resultadosVisibles() {
    return datos.filter(coincide);
  }

  function render() {
    const visibles = resultadosVisibles();
    lista.innerHTML = visibles.map(function (e) {
      const finalizado = e.estado === 'finalizado';
      const cancelado = e.estado === 'cancelado';
      const marcadorA = finalizado && e.puntaje_a != null ? e.puntaje_a : '–';
      const marcadorB = finalizado && e.puntaje_b != null ? e.puntaje_b : '–';
      let etiqueta = 'Pendiente';
      let claseEstado = '';
      if (finalizado) {
        etiqueta = 'Confirmado';
        claseEstado = 'estado-confirmado';
      } else if (cancelado) {
        etiqueta = 'Cancelado';
        claseEstado = 'estado-suspendido';
      } else if (e.estado === 'pendiente_revision' || (e.gracia_vencida && !finalizado)) {
        etiqueta = 'Requiere revisión';
        claseEstado = 'estado-suspendido';
      } else if (e.en_gracia || e.estado === 'en_periodo_gracia') {
        const minRest = e.minutos_restantes_gracia ? ' (' + e.minutos_restantes_gracia + ' min)' : '';
        etiqueta = 'Período de gracia' + minRest;
        claseEstado = 'estado-advertencia';
      }
      const avatarA = e.tipo_participante === 'individual' && window.ArenaCJDAvatar
        ? window.ArenaCJDAvatar.html(e.id_usuario_a, e.participante_a, 'avatar-mediano')
        : (e.tipo_participante === 'equipo' && window.ArenaCJDMedia
          ? window.ArenaCJDMedia.html('equipo', e.id_equipo_a, e.participante_a, {clase: 'media-mediana'})
          : '<span class="avatar-competidor avatar-violeta">' + escapar(iniciales(e.participante_a)) + '</span>');
      const avatarB = e.tipo_participante === 'individual' && e.id_usuario_b && window.ArenaCJDAvatar
        ? window.ArenaCJDAvatar.html(e.id_usuario_b, e.participante_b, 'avatar-mediano')
        : (e.tipo_participante === 'equipo' && e.id_equipo_b && window.ArenaCJDMedia
          ? window.ArenaCJDMedia.html('equipo', e.id_equipo_b, e.participante_b, {clase: 'media-mediana'})
          : '<span class="avatar-competidor avatar-rosa">' + escapar(iniciales(e.participante_b)) + '</span>');
      const mediaTorneo = window.ArenaCJDMedia
        ? window.ArenaCJDMedia.html('torneo', e.id_torneo, e.torneo, {clase: 'media-mediana'})
        : '<span class="icono-disciplina icono-violeta"><span data-icono="medalla" aria-hidden="true"></span></span>';
      return '<article class="tarjeta-resultado" data-torneo="' + Number(e.id_torneo) + '">' +
        '<div class="datos-torneo-resultado">' + mediaTorneo + '<div><h2>' + escapar(e.torneo) + '</h2><p>' + escapar(e.disciplina) + ' · ' + escapar(e.ronda) + '</p><span class="etiqueta-ronda etiqueta-violeta">' + escapar(etiqueta) + '</span></div></div>' +
        '<div class="marcador-enfrentamiento"><div class="competidor-resultado">' + avatarA + '<strong>' + escapar(e.participante_a) + '</strong></div><div class="marcador-principal"><span>' + escapar(marcadorA) + '</span><b>-</b><span>' + escapar(marcadorB) + '</span></div><div class="competidor-resultado">' + avatarB + '<strong>' + escapar(e.participante_b) + '</strong></div></div>' +
        '<div class="resumen-ganador"><span class="texto-ganador">' + (finalizado ? 'Ganador' : 'Estado') + '</span><strong>' + escapar(cancelado ? 'Enfrentamiento cancelado' : ganador(e)) + '</strong><span class="etiqueta-estado ' + claseEstado + '">' + escapar(etiqueta) + '</span><small>' + escapar(formatearFecha(e.fecha_hora)) + '</small></div>' +
        '<div class="acciones-resultado">' +
        (puedeGestionar(e) && !cancelado ? '<button class="boton boton-principal" type="button" data-editar-resultado="' + Number(e.id_enfrentamiento) + '">' + (finalizado ? 'Editar resultado' : 'Registrar resultado') + '</button>' : '') +
        '<a class="boton boton-claro" href="partidos.php?torneo=' + Number(e.id_torneo) + '">Ver enfrentamientos</a></div></article>';
    }).join('');
    if (window.ArenaCJDAvatar) window.ArenaCJDAvatar.activar(lista);

    if (sinResultados) {
      sinResultados.hidden = visibles.length !== 0;
      sinResultados.textContent = datos.length
        ? 'No hay resultados que coincidan con los filtros.'
        : 'No hay enfrentamientos disponibles para registrar o consultar resultados.';
    }
    if (contador) contador.textContent = 'Mostrando ' + visibles.length + (visibles.length === 1 ? ' registro' : ' registros');
  }

  function mostrarAviso(texto, tipo) {
    if (window.ArenaCJDToast) {
      window.ArenaCJDToast(texto, tipo || 'info');
      return;
    }
    let aviso = document.getElementById('avisoResultados');
    if (!aviso) {
      aviso = document.createElement('div');
      aviso.id = 'avisoResultados';
      aviso.className = 'aviso-flotante-ah';
      aviso.setAttribute('role', 'status');
      document.body.appendChild(aviso);
    }
    aviso.textContent = texto;
    aviso.dataset.tipo = tipo || 'info';
    aviso.classList.add('visible');
    window.clearTimeout(aviso._temporizador);
    aviso._temporizador = window.setTimeout(function () { aviso.classList.remove('visible'); }, 3200);
  }

  function crearModal() {
    let fondo = document.getElementById('modalResultado');
    if (fondo) return fondo;
    fondo = document.createElement('div');
    fondo.id = 'modalResultado';
    fondo.className = 'fondo-modal-perfil';
    fondo.hidden = true;
    fondo.innerHTML = '<section class="modal-perfil-usuario" role="dialog" aria-modal="true" aria-labelledby="tituloModalResultado">' +
      '<div class="cabecera-modal-perfil"><div><h2 id="tituloModalResultado">Registrar resultado</h2><p id="detalleModalResultado"></p></div><button class="cerrar-modal-perfil" type="button" data-cerrar-resultado aria-label="Cerrar"><span data-icono="peligro" aria-hidden="true"></span></button></div>' +
      '<form id="formularioResultado" class="form-seguridad-perfil">' +
      '<input type="hidden" id="resultadoId">' +
      '<div class="grupo-formulario"><label for="resultadoPuntajeA" id="labelResultadoA">Participante A</label><input class="campo-formulario" id="resultadoPuntajeA" type="number" min="0" step="1" required></div>' +
      '<div class="grupo-formulario"><label for="resultadoPuntajeB" id="labelResultadoB">Participante B</label><input class="campo-formulario" id="resultadoPuntajeB" type="number" min="0" step="1" required></div>' +
      '<p class="mensaje-seguridad-perfil" id="ganadorResultadoVista" aria-live="polite">Ingresa ambos puntajes para calcular el ganador.</p>' +
      '<p class="mensaje-seguridad-perfil" id="mensajeResultado" aria-live="polite"></p>' +
      '<div class="acciones-foto-perfil"><button class="boton boton-claro" type="button" data-cerrar-resultado>Cancelar</button><button class="boton boton-principal" type="submit">Guardar resultado</button></div></form></section>';
    document.body.appendChild(fondo);
    fondo.querySelectorAll('[data-cerrar-resultado]').forEach(function (boton) { boton.addEventListener('click', cerrarModal); });
    fondo.addEventListener('click', function (evento) { if (evento.target === fondo) cerrarModal(); });
    fondo.querySelector('#formularioResultado').addEventListener('submit', guardarResultado);
    ['resultadoPuntajeA', 'resultadoPuntajeB'].forEach(function (idCampo) {
      fondo.querySelector('#' + idCampo).addEventListener('input', actualizarVistaGanador);
    });
    return fondo;
  }

  function actualizarVistaGanador() {
    const id = Number(document.getElementById('resultadoId') && document.getElementById('resultadoId').value);
    const enfrentamiento = datos.find(function (item) { return Number(item.id_enfrentamiento) === id; });
    const vista = document.getElementById('ganadorResultadoVista');
    if (!enfrentamiento || !vista) return;
    const pa = document.getElementById('resultadoPuntajeA').value;
    const pb = document.getElementById('resultadoPuntajeB').value;
    vista.textContent = ganadorConPuntajes(enfrentamiento, pa, pb);
    vista.className = 'mensaje-seguridad-perfil' + (enfrentamiento.es_eliminacion && pa !== '' && pb !== '' && Number(pa) === Number(pb) ? ' error' : ' informativo');
  }

  function cerrarModal() {
    const fondo = document.getElementById('modalResultado');
    if (fondo) fondo.hidden = true;
    document.body.classList.remove('sin-scroll');
  }

  function abrirResultado(id) {
    const e = datos.find(function (item) { return Number(item.id_enfrentamiento) === Number(id); });
    if (!e || !puedeGestionar(e)) return;
    const fondo = crearModal();
    document.getElementById('resultadoId').value = String(e.id_enfrentamiento);
    document.getElementById('tituloModalResultado').textContent = e.estado === 'finalizado' ? 'Editar resultado' : 'Registrar resultado';
    document.getElementById('detalleModalResultado').textContent = e.torneo + ' · ' + e.ronda;
    document.getElementById('labelResultadoA').textContent = e.participante_a;
    document.getElementById('labelResultadoB').textContent = e.participante_b;
    document.getElementById('resultadoPuntajeA').value = e.puntaje_a == null ? '' : e.puntaje_a;
    document.getElementById('resultadoPuntajeB').value = e.puntaje_b == null ? '' : e.puntaje_b;
    const mensaje = document.getElementById('mensajeResultado');
    mensaje.textContent = e.estado === 'finalizado' ? 'El resultado puede corregirse mientras no haya generado una ronda posterior.' : 'El ganador se calcula automáticamente a partir del marcador.';
    mensaje.className = 'mensaje-seguridad-perfil';
    actualizarVistaGanador();
    fondo.hidden = false;
    document.body.classList.add('sin-scroll');
  }

  async function guardarResultado(evento) {
    evento.preventDefault();
    const id = Number(document.getElementById('resultadoId').value);
    const e = datos.find(function (item) { return Number(item.id_enfrentamiento) === id; });
    if (!e) return;
    const pa = document.getElementById('resultadoPuntajeA').value;
    const pb = document.getElementById('resultadoPuntajeB').value;
    const mensaje = document.getElementById('mensajeResultado');
    const boton = evento.currentTarget.querySelector('[type="submit"]');
    const npa = Number(pa);
    const npb = Number(pb);
    if (!Number.isInteger(npa) || !Number.isInteger(npb) || npa < 0 || npb < 0) {
      mensaje.textContent = 'Ingresa dos puntajes enteros válidos.';
      mensaje.className = 'mensaje-seguridad-perfil error';
      return;
    }
    if (e.es_eliminacion && npa === npb) {
      mensaje.textContent = 'En eliminación directa debe existir un ganador; el marcador no puede terminar empatado.';
      mensaje.className = 'mensaje-seguridad-perfil error';
      return;
    }
    boton.disabled = true;
    mensaje.textContent = 'Guardando resultado...';
    mensaje.className = 'mensaje-seguridad-perfil';
    try {
      const respuesta = await fetch('api/enfrentamiento_actualizar.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf},
        body: JSON.stringify({
          id_enfrentamiento: id,
          fecha_hora: e.fecha_hora ? String(e.fecha_hora).replace(' ', 'T').slice(0, 16) : '',
          estado: 'finalizado',
          puntaje_a: pa,
          puntaje_b: pb
        })
      });
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudo guardar el resultado.');
      mensaje.textContent = resultado.mensaje || 'Resultado guardado.';
      mensaje.className = 'mensaje-seguridad-perfil exito';
      await cargar();
      mostrarAviso(resultado.mensaje || 'Resultado guardado y clasificación actualizada.', 'exito');
      window.dispatchEvent(new CustomEvent('arenacjd:resultados-actualizados'));
      window.setTimeout(cerrarModal, 700);
    } catch (error) {
      mensaje.textContent = error.message;
      mensaje.className = 'mensaje-seguridad-perfil error';
    } finally {
      boton.disabled = false;
    }
  }

  async function cargar() {
    if (primeraCargaResultados && window.ArenaCJDSkeleton) window.ArenaCJDSkeleton.mostrar(lista, 6);
    try {
      const respuesta = await fetch('api/enfrentamientos.php?_=' + Date.now(), {cache: 'no-store'});
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudieron cargar los resultados.');
      datos = resultado.enfrentamientos || [];
      csrf = resultado.csrf_token || csrf;
      usuarioId = Number(resultado.usuario_id || 0);
      esAdministrador = Boolean(resultado.es_administrador);
      esOrganizador = Boolean(resultado.es_organizador);
      actualizarFiltros();
      const idUrl = new URLSearchParams(location.search).get('torneo');
      if (idUrl && controles.torneo && Array.from(controles.torneo.options).some(function (opcion) { return opcion.value === idUrl; })) controles.torneo.value = idUrl;
      render();
    } catch (error) {
      lista.innerHTML = '';
      if (sinResultados) {
        if (window.ArenaCJDEstadoError) window.ArenaCJDEstadoError(sinResultados, error.message, cargar);
        else { sinResultados.hidden = false; sinResultados.textContent = error.message; }
      }
      if (contador) contador.textContent = 'Mostrando 0 registros';
    } finally {
      if (window.ArenaCJDSkeleton) window.ArenaCJDSkeleton.ocultar(lista);
      primeraCargaResultados = false;
    }
  }

  Object.keys(controles).forEach(function (clave) {
    const control = controles[clave];
    if (control) control.addEventListener(clave === 'buscar' ? 'input' : 'change', render);
  });

  lista.addEventListener('click', function (evento) {
    const boton = evento.target.closest('[data-editar-resultado]');
    if (boton) abrirResultado(boton.dataset.editarResultado);
  });

  document.addEventListener('keydown', function (evento) {
    if (evento.key === 'Escape') cerrarModal();
  });

  if (exportar) {
    exportar.addEventListener('click', function () {
      const visibles = resultadosVisibles().filter(function (e) { return e.estado === 'finalizado'; });
      if (!visibles.length) {
        mostrarAviso('No hay resultados confirmados visibles para exportar.', 'info');
        return;
      }
      const filas = [['Torneo', 'Disciplina', 'Ronda', 'Fecha', 'Participante A', 'Puntaje A', 'Puntaje B', 'Participante B', 'Ganador']];
      visibles.forEach(function (e) {
        filas.push([e.torneo, e.disciplina, e.ronda, e.fecha_hora || '', e.participante_a, e.puntaje_a, e.puntaje_b, e.participante_b, ganador(e)]);
      });
      const csv = filas.map(function (fila) {
        return fila.map(function (valor) { return '"' + String(valor == null ? '' : valor).replace(/"/g, '""') + '"'; }).join(',');
      }).join('\n');
      const url = URL.createObjectURL(new Blob(['\uFEFF' + csv], {type: 'text/csv;charset=utf-8'}));
      const enlace = document.createElement('a');
      enlace.href = url;
      enlace.download = 'resultados-arenacjd.csv';
      document.body.appendChild(enlace);
      enlace.click();
      enlace.remove();
      URL.revokeObjectURL(url);
      mostrarAviso('Exportación de resultados generada correctamente.', 'exito');
    });
  }

  window.addEventListener('arenacjd:torneos-cargados', function () {
    const id = new URLSearchParams(location.search).get('torneo');
    if (id && controles.torneo && Array.from(controles.torneo.options).some(function (opcion) { return opcion.value === id; })) controles.torneo.value = id;
    render();
  });

  cargar();
  if (window.ArenaCJDSync) window.ArenaCJDSync.registrar('resultados', function () {
    const modal = document.getElementById('modalResultado');
    if (!modal || modal.hidden) return cargar();
  }, 5000, {ejecutarAhora:false});
}());
