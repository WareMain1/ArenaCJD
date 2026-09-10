# Revisión móvil de ArenaCJD — 10 de septiembre de 2026

## Resultado

La aplicación responde a los tamaños examinados, pero no se certifica como «100 % mobile first» ni libre de errores. El CSS combina bases móviles y consultas `min-width` con reglas de escritorio corregidas mediante `max-width`. Una conversión estricta requiere reorganizar la cascada y comparar cada componente; no basta con añadir un breakpoint.

## Comprobaciones realizadas

- Navegador integrado, anchos de 320, 390, 768 y 1280 px; no son dispositivos físicos.
- Lectura de las páginas internas disponibles con Organizador y de Disciplinas/Configuración con Administrador.
- Comprobación de ancho de documento y elementos con `hidden` que permanecían visibles.
- 39 combinaciones de 13 páginas y anchos de 390/768/1280 px sin desbordamiento de documento ni elementos `hidden` indebidamente visibles en esa muestra.
- Configuración y Disciplinas comprobadas adicionalmente en los cuatro anchos.
- Páginas públicas de torneos, enfrentamientos, resultados, clasificación y calendario; inicio, privacidad, términos, recuperación, registro y detalle de un torneo público revisados a 320 px.
- Inspección visual de registro, menú lateral, Categorías y Perfil; uso de tema claro y oscuro. Preferencia Sistema restaurada al terminar.
- Tablas y pestañas con desplazamiento horizontal local distinguidas del desbordamiento de toda la página.
- Comprobación de sintaxis: 122 archivos PHP y 23 archivos JavaScript, sin errores.

## Correcciones de esta revisión

- `css/paginas/participantes.css`: la paginación respeta `hidden`.
- `css/paginas/area-publica.css`: el resumen de clasificación pública respeta `hidden`.
- `css/formularios.css`: tamaño de texto de 16 px para campos de entrada en pantallas de hasta 640 px. Verificado en registro.
- Versiones de recursos actualizadas en las páginas que los cargan para invalidar caché.

## Límites y trabajo pendiente

- No se probaron Safari/iOS, Chrome/Android físicos, teclado virtual, rotación, lectores de pantalla ni todos los niveles de zoom.
- No se enviaron formularios ni se ejecutaron operaciones destructivas para comprobar sus estados finales.
- Los torneos sin enfrentamientos no permiten evaluar tablas, cuadros y marcadores con muchos datos; hacen falta escenarios de prueba representativos.
- No se ha realizado una auditoría de seguridad, carga ni compatibilidad exhaustiva.
- `css/experiencia.css` y `css/paginas/area-publica.css`, entre otros, conservan componentes cuya base es de escritorio y que se reducen con `max-width`. Conviene migrarlos por componentes, manteniendo comparaciones visuales, si se requiere una arquitectura estrictamente mobile first.
