(function () {
  'use strict';

  const formularioIdentificar = document.getElementById('formRecuperacionIdentificar');
  const formularioRestaurar = document.getElementById('formRecuperacionRestaurar');
  const mensaje = document.getElementById('mensajeRecuperacionPublica');
  const pregunta = document.getElementById('preguntaRecuperacionPublica');
  let tokenRecuperacion = '';

  function mostrarMensaje(texto, esError) {
    if (!mensaje) return;
    mensaje.textContent = texto;
    mensaje.className = 'mensaje-registro' + (esError ? ' mensaje-registro-error' : '');
  }

  if (formularioIdentificar) {
    formularioIdentificar.addEventListener('submit', async function (evento) {
      evento.preventDefault();
      mostrarMensaje('Buscando cuenta...');
      try {
        const respuesta = await fetch('api/recuperacion_identificar.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({identificador: document.getElementById('identificadorRecuperacion').value})
        });
        const datos = await respuesta.json();
        if (!respuesta.ok || !datos.exito) throw new Error(datos.mensaje);
        pregunta.textContent = datos.pregunta;
        tokenRecuperacion = datos.token_proceso || '';
        formularioIdentificar.hidden = true;
        formularioRestaurar.hidden = false;
        mostrarMensaje(datos.mensaje);
      } catch (error) {
        mostrarMensaje(error.message || 'No se pudo iniciar la recuperación.', true);
      }
    });
  }

  if (formularioRestaurar) {
    formularioRestaurar.addEventListener('submit', async function (evento) {
      evento.preventDefault();
      mostrarMensaje('Actualizando contraseña...');
      try {
        const respuesta = await fetch('api/recuperacion_restaurar.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            respuesta: document.getElementById('respuestaRecuperacionPublica').value,
            nueva: document.getElementById('nuevaRecuperacionPublica').value,
            confirmar: document.getElementById('confirmarRecuperacionPublica').value,
            token_proceso: tokenRecuperacion
          })
        });
        const datos = await respuesta.json();
        if (!respuesta.ok || !datos.exito) throw new Error(datos.mensaje);
        mostrarMensaje(datos.mensaje);
        tokenRecuperacion = '';
        formularioRestaurar.reset();
        formularioRestaurar.hidden = true;
        formularioIdentificar.hidden = false;
        window.setTimeout(function () {
          window.location.href = 'index.php#acceso';
        }, 900);
      } catch (error) {
        mostrarMensaje(error.message || 'No se pudo cambiar la contraseña.', true);
      }
    });
  }

  const reiniciar = document.getElementById('reiniciarRecuperacion');
  if (reiniciar) {
    reiniciar.addEventListener('click', function (evento) {
      evento.preventDefault();
      if (formularioRestaurar) {
        formularioRestaurar.hidden = true;
        formularioRestaurar.reset();
      }
      if (formularioIdentificar) {
        formularioIdentificar.hidden = false;
        formularioIdentificar.reset();
      }
      tokenRecuperacion = '';
      mostrarMensaje('');
    });
  }
}());
