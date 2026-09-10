// Instrumentación temporal de QA: registra solo estado HTTP y campos no sensibles.
(function () {
  const original = window.fetch;
  window.fetch = async function (recurso, opciones) {
    const url = String(recurso);
    const metodo = opciones?.method || 'GET';
    const respuesta = await original.apply(this, arguments);
    if (url.includes('api/')) {
      const evento = {url:url.split('?')[0],metodo,estado:respuesta.status};
      if (url.includes('torneo_actualizar.php') && opciones?.body) {
        const datos = JSON.parse(opciones.body);
        evento.publicado = datos.publicado;
        evento.tipoPublicado = typeof datos.publicado;
        evento.id = datos.id_torneo;
        evento.estadoTorneo = datos.estado;
      }
      console.info('QA_TEMA ' + JSON.stringify(evento));
    }
    return respuesta;
  };
}());
