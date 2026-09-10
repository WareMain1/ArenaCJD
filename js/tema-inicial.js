(function () {
  'use strict';

  const clave = 'temaArenaCJD';
  const permitidos = ['claro', 'oscuro', 'sistema'];
  let preferencia = localStorage.getItem(clave) || 'claro';
  if (!permitidos.includes(preferencia)) preferencia = 'claro';

  const tema = preferencia === 'sistema'
    ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'oscuro' : 'claro')
    : preferencia;

  document.documentElement.dataset.tema = tema;
  document.documentElement.dataset.preferenciaTema = preferencia;
  document.documentElement.style.colorScheme = tema === 'oscuro' ? 'dark' : 'light';
}());
