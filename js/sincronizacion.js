(function () {
  'use strict';

  if (window.ArenaCJDSync) return;

  const tareas = new Map();
  let intervalo = null;

  function ahora() {
    return Date.now();
  }

  async function ejecutarTarea(tarea, forzar) {
    if (!tarea || tarea.ejecutando) return;
    if (!forzar && document.visibilityState !== 'visible') return;
    const marca = ahora();
    if (!forzar && marca - tarea.ultimaEjecucion < tarea.cada) return;

    tarea.ejecutando = true;
    tarea.ultimaEjecucion = marca;
    try {
      await tarea.funcion();
    } catch (error) {
      console.error('No se pudo sincronizar ' + tarea.nombre + '.');
    } finally {
      tarea.ejecutando = false;
    }
  }

  function ciclo() {
    tareas.forEach(function (tarea) {
      ejecutarTarea(tarea, false);
    });
  }

  function asegurarIntervalo() {
    if (intervalo !== null) return;
    intervalo = window.setInterval(ciclo, 1000);
  }

  function registrar(nombre, funcion, cada, opciones) {
    if (!nombre || typeof funcion !== 'function') return function () {};
    const configuracion = opciones || {};
    tareas.set(nombre, {
      nombre: nombre,
      funcion: funcion,
      cada: Math.max(3000, Number(cada) || 10000),
      ultimaEjecucion: configuracion.ejecutarAhora === false ? ahora() : 0,
      ejecutando: false
    });
    asegurarIntervalo();
    if (configuracion.ejecutarAhora !== false) ejecutarTarea(tareas.get(nombre), true);
    return function () {
      tareas.delete(nombre);
    };
  }

  function ejecutar(nombre) {
    const tarea = tareas.get(nombre);
    if (tarea) ejecutarTarea(tarea, true);
  }

  function ejecutarTodas() {
    tareas.forEach(function (tarea) {
      ejecutarTarea(tarea, true);
    });
  }

  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'visible') ejecutarTodas();
  });

  window.ArenaCJDSync = {
    registrar: registrar,
    ejecutar: ejecutar,
    ejecutarTodas: ejecutarTodas
  };
}());
