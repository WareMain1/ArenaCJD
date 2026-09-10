# Revisión de iconografía — 9 de septiembre de 2026

Se sustituyeron los emojis y símbolos decorativos de interfaz detectados en PHP, HTML y JavaScript por el catálogo SVG local. No se añadieron dependencias externas.

## Implementación

- `js/iconos.js`: catálogo de 46 iconos con `currentColor`, viewBox 24 × 24, trazo 1.8, extremos redondeados y atributos decorativos accesibles. Los marcadores `data-icono` se completan también cuando se insertan modales o tarjetas dinámicas. No se recorre ni reemplaza texto arbitrario del usuario.
- `css/componentes.css`: tamaños, centrado, foco y colores compartidos. Correcciones de contraste en las estadísticas de Torneos y el trofeo de Sorteos. Búsqueda, ayuda y notificaciones comparten área de 44 × 44 e iconos de 22 px.
- `js/experiencia.js` y `js/componentes.js`: ayuda, búsqueda, notificaciones, favoritos, avisos, cierres y representaciones alternativas de torneos usan el catálogo.
- Scripts de Panel, Participantes, Torneos, Sorteos, Enfrentamientos, Resultados, Clasificación, Calendario, Disciplinas, Configuración, registro y área pública: sustitución de iconos estáticos y generados.
- Vistas PHP/HTML y `publico/layout.php`: marcadores SVG y versiones de los recursos actualizadas. `index.html` carga el catálogo también para su enlace alternativo a la portada.

Se conservaron logos, avatares, imágenes de torneos y contenido introducido por usuarios. Los cambios se limitan a presentación; se mantienen las consultas, APIs, permisos, rutas, IDs y lógica deportiva.

## Verificación

- Inspección visual en el navegador integrado, en claro y oscuro: Panel, Torneos, Participantes, Sorteos, Enfrentamientos, Resultados, Clasificación, Mi actividad, Calendario, Disciplinas y Configuración; portada/login, registro y vistas públicas de torneos, detalle, enfrentamientos, resultados, clasificación y calendario. Revisión adicional de las páginas legales y recuperación.
- Comprobación de ayuda contextual, apertura de modal de torneo y favoritos con restauración del estado original. No se enviaron formularios de creación o edición durante estas comprobaciones.
- Validación del catálogo y de los nombres de iconos referenciados. Búsqueda de símbolos decorativos restantes en las fuentes; se preservan signos de texto como dimensiones de imágenes y máscaras de contraseñas.
- Sintaxis de todos los archivos PHP y JavaScript: correcta.
- `node tests/tema-fechas.cjs`: correcto, incluido tema tras login claro/oscuro/sistema.
- `php tests/orientacion-torneo.php`: 13 casos correctos.
- Las consultas de consola realizadas en las vistas comprobadas no mostraron errores.

## Límite de la comprobación visual

La base disponible no tenía enfrentamientos ni resultados: esos módulos se comprobaron en sus estados vacíos. Las plantillas de estados deportivos poblados, incluido campeón, se revisaron en código; no se crearon competiciones ni resultados para forzar todos esos estados.

Al finalizar se restauró el tema claro y se dejó abierta la vista Enfrentamientos con la sesión disponible.
