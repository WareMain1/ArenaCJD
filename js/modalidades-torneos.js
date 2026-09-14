(function () {
  'use strict';

  const claveAlmacenamiento = 'arenacjd_modalidades_torneos';
  const clavesAnteriores = ['arenacjd_modalidades_realizacion_v1'];

  function esModalidadValida(valor) {
    return valor === 'presencial' || valor === 'virtual';
  }

  function normalizarMapa(datos) {
    if (!datos || typeof datos !== 'object' || Array.isArray(datos)) return {};
    return Object.keys(datos).reduce(function (resultado, idTorneo) {
      if (esModalidadValida(datos[idTorneo])) resultado[String(idTorneo)] = datos[idTorneo];
      return resultado;
    }, {});
  }

  function leerClave(clave) {
    try {
      return normalizarMapa(JSON.parse(localStorage.getItem(clave) || '{}'));
    } catch (error) {
      return {};
    }
  }

  function leer() {
    const modalidades = leerClave(claveAlmacenamiento);
    if (Object.keys(modalidades).length) return modalidades;

    for (let indice = 0; indice < clavesAnteriores.length; indice += 1) {
      const anteriores = leerClave(clavesAnteriores[indice]);
      if (!Object.keys(anteriores).length) continue;
      try { localStorage.setItem(claveAlmacenamiento, JSON.stringify(anteriores)); } catch (error) { /* El dato sigue disponible desde la clave anterior. */ }
      return anteriores;
    }
    return {};
  }

  function obtener(idTorneo) {
    if (idTorneo === null || idTorneo === undefined || idTorneo === '') return '';
    return leer()[String(idTorneo)] || '';
  }

  function guardar(idTorneo, modalidad) {
    if (idTorneo === null || idTorneo === undefined || idTorneo === '' || !esModalidadValida(modalidad)) return false;
    const modalidades = leer();
    modalidades[String(idTorneo)] = modalidad;
    try {
      localStorage.setItem(claveAlmacenamiento, JSON.stringify(modalidades));
      return true;
    } catch (error) {
      return false;
    }
  }

  function etiqueta(modalidad) {
    return modalidad === 'virtual' ? 'Virtual' : modalidad === 'presencial' ? 'Presencial' : 'Sin definir';
  }

  function icono(modalidad) {
    return esModalidadValida(modalidad) ? modalidad : '';
  }

  function indicadorHTML(modalidad, clase) {
    const valor = esModalidadValida(modalidad) ? modalidad : '';
    const iconoHTML = valor ? '<span data-icono="' + icono(valor) + '" aria-hidden="true"></span>' : '';
    return '<span class="' + clase + (valor ? '' : ' sin-definir') + '" data-realizacion="' + (valor || 'sin-definir') + '">' + iconoHTML + '<span>' + etiqueta(valor) + '</span></span>';
  }

  window.ArenaCJDModalidadesTorneos = {
    clave: claveAlmacenamiento,
    leer: leer,
    guardar: guardar,
    obtener: obtener,
    etiqueta: etiqueta,
    icono: icono,
    indicadorHTML: indicadorHTML
  };
}());
