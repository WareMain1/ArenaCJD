(function () {
  'use strict';

  let preferenciaTorneoAplicada = false;
  let torneoPredeterminado = '';

  const configuracionesSelectores = {
    filtroTorneoCalendario: { valor: 'todos', texto: 'Todos los torneos' },
    filtroTorneoClasificacion: { valor: '', texto: 'Seleccionar torneo' },
    torneoSorteo: { valor: '', texto: 'Seleccionar torneo' },
    torneoPredeterminado: { valor: '', texto: 'Seleccionar torneo' }
  };

  function etiquetaModalidad(valor) {
    return valor === 'equipo' ? 'Por equipos' : 'Individual';
  }

  function etiquetaEstado(valor) {
    const estados = {
      borrador: 'Borrador',
      inscripciones: 'Inscripciones',
      en_curso: 'En curso',
      finalizado: 'Finalizado',
      cancelado: 'Cancelado'
    };

    return estados[valor] || valor;
  }

  function normalizarTorneo(torneo) {
    return {
      id: String(torneo.id_torneo),
      nombre: torneo.nombre,
      disciplina: torneo.disciplina,
      categoria: torneo.categoria,
      modalidad: etiquetaModalidad(torneo.modalidad),
      modalidadBD: torneo.modalidad,
      tipo: torneo.tipo_torneo,
      estado: etiquetaEstado(torneo.estado),
      estadoBD: torneo.estado,
      fechaInicio: torneo.fecha_inicio,
      fechaFin: torneo.fecha_fin,
      cupoMaximo: torneo.cupo_maximo == null ? null : Number(torneo.cupo_maximo),
      cantidadParticipantes: Number(torneo.cantidad_inscritos || 0),
      idOrganizador: Number(torneo.id_organizador || 0),
      organizador: torneo.organizador,
      organizadorUsuario: torneo.organizador_usuario
    };
  }

  function poblarSelector(selector, configuracion, torneos) {
    const valorAnterior = selector.value;
    selector.innerHTML = '';

    if (configuracion) {
      const opcionBase = document.createElement('option');
      opcionBase.value = configuracion.valor;
      opcionBase.textContent = configuracion.texto;
      selector.appendChild(opcionBase);
    }

    torneos.forEach(function (torneo) {
      const opcion = document.createElement('option');
      opcion.value = torneo.id;
      opcion.textContent = selector.id === 'torneoSorteo'
        ? torneo.nombre + ' — ' + torneo.disciplina
        : torneo.nombre;
      selector.appendChild(opcion);
    });

    const existeAnterior = Array.from(selector.options).some(function (opcion) {
      return opcion.value === valorAnterior;
    });

    if (existeAnterior) {
      selector.value = valorAnterior;
    }
  }

  function torneosDisponiblesParaSelector(id, torneos) {
    if (id === 'torneoSorteo') {
      return torneos.filter(function (torneo) {
        return torneo.estadoBD === 'inscripciones' || torneo.estadoBD === 'en_curso';
      });
    }

    if (id === 'filtroTorneoClasificacion') {
      return torneos.filter(function (torneo) {
        return torneo.estadoBD !== 'cancelado';
      });
    }

    return torneos;
  }

  function actualizarSelectores(torneos) {
    Object.keys(configuracionesSelectores).forEach(function (id) {
      const selector = document.getElementById(id);
      if (!selector) return;
      poblarSelector(
        selector,
        configuracionesSelectores[id],
        torneosDisponiblesParaSelector(id, torneos)
      );
    });
  }


  async function cargarTorneoPredeterminado() {
    if (preferenciaTorneoAplicada) return torneoPredeterminado;
    preferenciaTorneoAplicada = true;

    try {
      const respuesta = await fetch('api/preferencias.php?_=' + Date.now(), {
        cache: 'no-store',
        headers: { Accept: 'application/json' }
      });
      const resultado = await respuesta.json();
      if (respuesta.ok && resultado.exito && resultado.preferencias) {
        torneoPredeterminado = String(resultado.preferencias.torneoPredeterminado || '');
      }
    } catch (error) {
      torneoPredeterminado = '';
    }

    return torneoPredeterminado;
  }

  function aplicarTorneoPredeterminado() {
    if (!torneoPredeterminado) return;
    if (new URLSearchParams(window.location.search).get('torneo')) return;

    ['torneoSorteo', 'filtroTorneoClasificacion'].forEach(function (id) {
      const selector = document.getElementById(id);
      if (!selector) return;
      const existe = Array.from(selector.options).some(function (opcion) {
        return opcion.value === torneoPredeterminado;
      });
      if (!existe) return;

      const configuracion = configuracionesSelectores[id];
      const valorBase = configuracion ? configuracion.valor : '';
      if (selector.value === valorBase || selector.value === '') {
        selector.value = torneoPredeterminado;
      }
    });
  }

  async function cargarTorneos() {
    try {
      const respuesta = await fetch('api/torneos.php', {
        cache: 'no-store',
        headers: {
          Accept: 'application/json'
        }
      });

      if (!respuesta.ok) {
        return [];
      }

      const resultado = await respuesta.json();

      if (!resultado.exito || !Array.isArray(resultado.torneos)) {
        return [];
      }

      const torneos = resultado.torneos.map(normalizarTorneo);
      window.ArenaCJDDatos.torneos = torneos;
      window.ArenaCJDDatos.cargado = true;
      actualizarSelectores(torneos);
      await cargarTorneoPredeterminado();
      aplicarTorneoPredeterminado();

      window.dispatchEvent(new CustomEvent('arenacjd:torneos-cargados', {
        detail: { torneos: torneos }
      }));

      return torneos;
    } catch (error) {
      return [];
    }
  }

  window.ArenaCJDDatos = {
    torneos: [],
    cargado: false,
    obtenerTorneo: function (id) {
      return this.torneos.find(function (torneo) {
        return String(torneo.id) === String(id);
      }) || null;
    },
    cargarTorneos: cargarTorneos
  };

  document.addEventListener('DOMContentLoaded', function () {
    if (window.ArenaCJDSync) window.ArenaCJDSync.registrar('torneos-globales', cargarTorneos, 5000);
    else cargarTorneos();
  });
}());
