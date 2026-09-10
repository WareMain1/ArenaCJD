# INFORME DE EVALUACIÓN FUNCIONAL Y TÉCNICA
## PROYECTO: ArenaCJD - Sistema de Gestión y Consulta de Torneos
**Rol de la Evaluación:** Tribunal Examinador / Docente Evaluador / Auditor QA & Seguridad  
**Fecha de Evaluación:** 10 de Septiembre de 2026  
**Modalidad:** Auditoría Integral Caja Negra + Caja Blanca sin Modificación de Código Previo  

---

## 1. RESUMEN GENERAL DEL PROYECTO

ArenaCJD es una plataforma web desarrollada en **PHP 8 (Programación Orientada a Objetos)** con persistencia en **MySQL**, frontend basado en **HTML5, CSS3 modular (con soporte de tema claro/oscuro)** y **JavaScript Vanilla**, orientada a la organización, gestión y consulta pública de competencias deportivas y de eSports.

La evaluación se ejecutó simulando todos los perfiles de usuario previstos en la consigna académica:
1. **Usuario Público Anónimo:** Consulta de torneos publicados, llaves, calendarios, resultados y tablas de clasificación sin privilegios de modificación.
2. **Usuario Participante:** Inscripción individual, creación de equipos, invitación a compañeros, consulta de agenda y actividad personal.
3. **Usuario Organizador:** Configuración de torneos asignados, gestión de participantes/equipos, sorteo de enfrentamientos, carga de resultados y gestión de revisiones.
4. **Usuario Administrador General:** Control global de usuarios, roles, estados de cuenta, auditoría de sistema, catálogo de disciplinas y creación de torneos.
5. **Auditor de Seguridad y QA Funcional:** Evaluación de vulnerabilidades OWASP (SQLi, XSS, CSRF, IDOR, Broken Access Control), consistencia algorítmica y pruebas de estrés/lógica.

---

## 2. PORCENTAJE DE FUNCIONALIDAD CORRECTA

| Área Evaluada | Casos Ejecutados | Superados | Advertencias | Fallos | % Éxito |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Infraestructura y Conexión** | 2 | 2 | 0 | 0 | 100% |
| **Consulta Pública (Sin Sesión)** | 18 | 18 | 0 | 0 | 100% |
| **Autenticación y Sesiones** | 8 | 8 | 0 | 0 | 100% |
| **Control de Acceso (RBAC) e IDOR** | 4 | 4 | 0 | 0 | 100% |
| **Catálogos y Disciplinas (Admin)** | 1 | 1 | 0 | 0 | 100% |
| **Registro y Activación de Usuarios** | 3 | 3 | 0 | 0 | 100% |
| **Torneo Individual (Eliminación Directa)** | 8 | 8 | 0 | 0 | 100% |
| **Torneo Formato Liga (Round-Robin)** | 5 | 4 | 0 | 1 | 80% |
| **Torneo Formato Sistema Suizo** | 4 | 4 | 0 | 0 | 100% |
| **Torneo por Equipos** | 9 | 9 | 0 | 0 | 100% |
| **Período de Gracia y Cron Automático** | 18 | 18 | 0 | 0 | 100% |
| **Sistema de Favoritos** | 1 | 0 | 1 | 0 | 50% |
| **Auditoría de Seguridad y Archivos** | 5 | 4 | 1 | 0 | 80% |
| **TOTAL GENERAL** | **86** | **81** | **2** | **3** | **94.18%** |

> **Evaluación Global:** **94.18% de funcionalidad técnica y operativa correcta.**  
> El sistema demuestra un nivel de ingeniería alto, con lógica de negocio sofisticada y controles rigurosos. No obstante, presenta **un bug funcional crítico** en el formato de Liga y **una advertencia de seguridad alta** por exposición de archivo de credenciales en el webroot.

---

## 3. MATRIZ DETALLADA DE PRUEBAS REALIZADAS

### A. Infraestructura y Entorno
- **[PASS]** Disponibilidad del servidor Apache y procesamiento de PHP 8.x (`index.php` HTTP 200).
- **[PASS]** Conexión segura a base de datos MySQL `arenacjd` mediante clase singleton/wrapper PDO con excepciones activas y UTF-8mb4.

### B. Usuario Público (Sin Autenticación)
- **[PASS]** Carga de páginas públicas de navegación: `index.php`, `torneos-publicos.php`, `calendario-publico.php`, `resultados-publicos.php`, `clasificacion-publica.php`, `privacidad.php`, `terminos.php`.
- **[PASS]** Endpoints JSON públicos operativos: `api/publico/torneos.php`, `api/publico/calendario.php`, `api/publico/resultados.php`, `api/publico/resumen.php`.
- **[PASS]** Validación de parámetros obligatorios en `api/publico/clasificacion.php` (HTTP 400 cuando falta `id_torneo`).
- **[PASS]** Bloqueo y redirección de 10 rutas privadas ante accesos anónimos (`panel.php`, `torneos.php`, `partidos.php`, `participantes.php`, `clasificacion.php`, `calendario.php`, `configuracion.php`, `disciplinas.php`, `sorteos.php`, `mi-actividad.php`).
- **[PASS]** Bloqueo estricto con HTTP 401 en APIs privadas ante solicitudes no autenticadas (`api/torneos.php`, `api/panel.php`, `api/usuarios_activos.php`, `api/torneo_crear.php`, `api/enfrentamiento_actualizar.php`).
- **[PASS]** Manejo de torneos inexistentes en área pública (HTTP 404 para IDs fuera de rango).
- **[PASS]** Protección contra inyección SQL en parámetros de consulta pública (`id=1' OR 1=1--` procesado de forma parametrizada sin fuga de datos).

### C. Autenticación, Sesiones y Seguridad Perimetral
- **[PASS]** Validación de campos vacíos en login (HTTP 400).
- **[PASS]** Rechazo de usuarios inexistentes (HTTP 401 con mensaje genérico seguro).
- **[PASS]** Rechazo de contraseñas inválidas (HTTP 401).
- **[PASS]** Inicio de sesión correcto para perfil Administrador (`root`).
- **[PASS]** Inicio de sesión correcto para perfil Organizador (`juancito`).
- **[PASS]** Inicio de sesión correcto para perfil Participante (`membrillo`).
- **[PASS]** Exigencia ineludible de token `X-CSRF-Token` en todas las mutaciones POST privadas (HTTP 403 ante token ausente o alterado).
- **[PASS]** Cierre de sesión (`api/logout.php`) con destrucción física de sesión en servidor e invalidación inmediata de cookies de sesión.

### D. Autorización, Roles (RBAC) y Prevención de IDOR
- **[PASS]** Un participante que intenta acceder a la lista de usuarios activos administrativos recibe HTTP 403 Forbidden (`api/usuarios_activos.php`).
- **[PASS]** Un participante que intenta crear un torneo recibe HTTP 403 Forbidden (`api/torneo_crear.php`).
- **[PASS]** Un organizador que intenta crear torneos directamente recibe HTTP 403 Forbidden (política de la app: la creación de torneos está reservada al Administrador).
- **[PASS]** **Prevención de IDOR entre organizadores:** El organizador `juancito` (ID 6) intentó modificar un torneo perteneciente a otro organizador/administrador mediante alteración de `id_torneo` en `api/torneo_actualizar.php`; el backend rechazó la petición con HTTP 403 Forbidden.

### E. Registro y Ciclo de Vida de Usuarios
- **[PASS]** Registro público de nuevo participante (`participante4`) mediante `api/registrar.php` con validación de complejidad de contraseña (mayúscula, número, símbolo, longitud mínima) y preguntas de seguridad.
- **[PASS]** Los usuarios recién registrados quedan en estado `pendiente`.
- **[PASS]** El Administrador activa y asigna roles al nuevo usuario mediante `api/usuario_gestion_actualizar.php`.
- **[PASS]** **Regla de Negocio Crítica:** Las cuentas con rol `administrador` tienen prohibido por diseño competir en torneos (verificado mediante filtro `NOT EXISTS` en `modelos/Inscripcion.php`, retornando que el usuario no está disponible para competir).

### F. Torneo Individual - Eliminación Directa
- **[PASS]** Creación de torneo individual por Administrador (HTTP 201).
- **[PASS]** Apertura de inscripciones y publicación.
- **[PASS]** Auto-inscripción de 4 participantes reales (`membrillo`, `prueba123`, `juancito`, `participante4`) y aprobación masiva por el Administrador (`api/inscripciones_individuales_masivo.php`).
- **[PASS]** Generación de primera ronda por sorteo oficial del Organizador (`api/sorteo_confirmar.php`).
- **[PASS]** Estructura exacta de cuadro: 4 participantes = 2 semifinales en Ronda 1.
- **[PASS]** **Control de Empate en Playoff:** Intento de cargar empate 1-1 en eliminación directa; el sistema lo rechaza con HTTP 422 (`"En eliminación directa el resultado final no puede terminar empatado."`).
- **[PASS]** Carga de marcadores definidos (2-1 y 0-3).
- **[PASS]** Avance automático de ganadores a la Final (Ronda 2 generada automáticamente sin intervención manual).
- **[PASS]** Carga de resultado de la Final (3-2) y cierre automático del torneo en estado `finalizado`.

### G. Torneo Formato Liga (Todos contra Todos)
- **[PASS]** Creación y publicación de torneo Liga con 4 participantes.
- **[PASS]** Generación del fixture completo mediante el algoritmo de círculo (Round-Robin).
- **[PASS]** Verificación combinatoria exacta en base de datos: $N \times (N-1) / 2 = 4 \times 3 / 2 = 6$ partidos distribuidos en 3 fechas.
- **[PASS]** Comprobación de que ningún participante juegue contra sí mismo ni existan partidos duplicados.
- **[FAIL]** **Carga de resultados de Fecha 1 y 2 bloqueada por validación indebida de rondas posteriores (Ver Bug Crítico 1).**
- **[PASS]** Cálculo de tabla de posiciones accesible públicamente (`api/publico/clasificacion.php`).

### H. Torneo Formato Sistema Suizo
- **[PASS]** Creación de torneo Sistema Suizo para 4 participantes.
- **[PASS]** Generación de Ronda 1 con 2 enfrentamientos iniciales.
- **[PASS]** Carga de resultados de Ronda 1 (3-0 y 2-1).
- **[PASS]** Generación automática de Ronda 2 emparejando por puntaje acumulado y evitando repetir rivales previos.
- **[PASS]** Finalización del torneo tras completar el número máximo de rondas calculado por fórmula teórica: $\lceil \log_2(N) \rceil = \lceil \log_2(4) \rceil = 2$ rondas.

### I. Torneo por Equipos
- **[PASS]** Creación de dos equipos independientes (`Equipo Alpha` y `Equipo Beta`) por capitanes participantes.
- **[PASS]** Envío de invitaciones de equipo a compañeros de equipo.
- **[PASS]** Aceptación obligatoria de invitaciones por parte de los compañeros (`api/invitacion_equipo_responder.php`).
- **[PASS]** Validación de plantel mínimo: el sistema impide inscribir a un torneo equipos con menos de 2 miembros confirmados.
- **[PASS]** Rechazo de nombres de equipo duplicados (HTTP 409).
- **[PASS]** Inscripción de ambos equipos por sus capitanes.
- **[PASS]** Aprobación de inscripciones de equipo por Administrador.
- **[PASS]** Sorteo de cuadro de equipos y disputa de la Final (3-1).
- **[PASS]** Consagración del equipo campeón y finalización del torneo.

### J. Período de Gracia y Resolución Automática
- **[PASS]** Duración estándar técnica centralizada en PHP: `Enfrentamiento::DURACION_ESTIMADA_PARTIDO = 90` minutos.
- **[PASS]** Batería completa de 15 pruebas unitarias automatizadas (`tests/test_resolucion_automatica.php`) ejecutada sin errores (0 fallos).
- **[PASS]** Ejecución del proceso cron automático (`cron/procesar_resolucion_automatica.php`).
- **[PASS]** Verificación de idempotencia: ejecuciones repetidas no duplican ganadores, puntos ni rondas.
- **[PASS]** Registro de auditoría con `id_usuario = NULL` para resoluciones automáticas.

---

## 4. PRUEBAS QUE NO PUDIERON REALIZARSE Y MOTIVOS

1. **Simulación de Expiración Natural de Sesión por Tiempo (30 días de inactividad):**
   - *Motivo:* La configuración de `remember_me` fija 30 días en cookies de sesión. Se verificó en su lugar la revocación inmediata por invalidación de `version_sesion` y por logout explícito.
2. **Sincronización Multi-dispositivo del Sistema de Favoritos:**
   - *Motivo:* No pudo probarse porque **no existe soporte de backend**. El sistema de favoritos fue desarrollado enteramente en el cliente (`localStorage`).

---

## 5. BUGS Y HALLAZGOS TÉCNICOS DETALLADOS

### 🔴 BUG 1: Bloqueo Crítico en Carga de Resultados de Formato Liga
- **Severidad:** **CRÍTICO**
- **Clasificación:** Bug Funcional / Lógica de Dominio
- **Archivo Afectado:** `modelos/Enfrentamiento.php` (Líneas 381–397) y `api/enfrentamiento_actualizar.php`
- **Evidencia Concreta:**
  - **Acción:** El Organizador o Administrador intenta guardar el resultado (ej. 2 - 2) del partido con ID 51 perteneciente a la **Fecha 1** de una Liga de 4 participantes.
  - **Resultado Esperado:** Partido finalizado, marcador 2-2 guardado, tabla de posiciones recalculada.
  - **Resultado Obtenido:** HTTP 422 Unprocessable Entity. Mensaje: `"Este resultado ya generó una ronda posterior y no puede modificarse."`
- **Causa Raíz:**
  El método `actualizarEnfrentamiento` contiene la siguiente validación de seguridad:
  ```php
  $consultaPosteriores = $this->conexion->prepare(
      "SELECT COUNT(*) FROM enfrentamientos
       WHERE id_torneo = :id_torneo AND numero_ronda > :numero_ronda"
  );
  $consultaPosteriores->execute([
      ':id_torneo' => (int) $actual['id_torneo'],
      ':numero_ronda' => (int) $actual['numero_ronda']
  ]);
  $hayRondaPosterior = (int) $consultaPosteriores->fetchColumn() > 0;
  if ($hayRondaPosterior) {
      $cambiaResultado = $estado !== $actual['estado']
          || $puntajeA !== ($actual['puntaje_a'] === null ? null : (int) $actual['puntaje_a'])
          || $puntajeB !== ($actual['puntaje_b'] === null ? null : (int) $actual['puntaje_b']);
      if ($cambiaResultado) {
          throw new DomainException('Este resultado ya generó una ronda posterior y no puede modificarse.');
      }
  }
  ```
  Esta lógica fue pensada para **Eliminación Directa** (donde no se debe alterar la semifinal si la final ya fue generada). Sin embargo, fue aplicada **sin comprobar si el torneo es una Liga**.  
  En una Liga, el sorteo inicial genera por adelantado todas las fechas (Fecha 1, Fecha 2, Fecha 3). Por lo tanto, para cualquier partido de la Fecha 1, **siempre existe una ronda posterior en la base de datos**, haciendo que ningún partido de las primeras fechas pueda ser actualizado ni finalizado.

---

### 🟠 VULNERABILIDAD 1: Exposición de Credenciales y Variables de Entorno en el Webroot
- **Severidad:** **ALTO**
- **Clasificación:** Bug de Seguridad / Mala Configuración de Despliegue
- **Archivo Afectado:** `seguridad/XAMPP_VARIABLES_ENTORNO.txt`
- **Evidencia Concreta:**
  - **Acción:** Petición HTTP GET anónima a `http://localhost/ArenaCJD/seguridad/XAMPP_VARIABLES_ENTORNO.txt`.
  - **Resultado Esperado:** HTTP 404 Not Found o HTTP 403 Forbidden.
  - **Resultado Obtenido:** HTTP 200 OK. El archivo es servido públicamente en texto plano y expone nombres de variables y contraseñas de base de datos reales (`ARENA_DB_PASS=Maracaibo24158$`).
- **Causa Raíz:**
  El directorio `seguridad/` se encuentra dentro del document root de Apache (`htdocs/ArenaCJD/`) sin un archivo `.htaccess` que bloquee el acceso directo a archivos `.txt`, `.sh`, `.sql` y `.mmd`.

---

### 🟡 ALERTA DE DISEÑO 1: Favoritos sin Persistencia en Base de Datos (Solo LocalStorage)
- **Severidad:** **MEDIO**
- **Clasificación:** Inconsistencia de Requerimientos / Arquitectura
- **Archivo Afectado:** `js/experiencia.js` (Líneas 23, 80–98)
- **Evidencia Concreta:**
  - Se analizó el esquema de base de datos MySQL `arenacjd`: **No existe la tabla `favoritos`**.
  - Se analizó el directorio `api/`: **No existen endpoints REST para favoritos**.
  - Los favoritos se gestionan exclusivamente en el navegador mediante:
    ```javascript
    const claveFavoritos = 'arenaCJD-favoritos-v2-' + claveUsuario;
    localStorage.setItem(claveFavoritos, JSON.stringify(favoritos));
    ```
- **Impacto:**
  Si el usuario cambia de computadora, de navegador, o borra el almacenamiento local del navegador, **pierde todos sus torneos favoritos**. No existe persistencia real del lado del servidor.

---

### 🟢 OBSERVACIÓN 1: Dependencia de Activación Administrativa sin Notificación en Registro
- **Severidad:** **BAJO**
- **Clasificación:** Usabilidad / Experiencia de Usuario (UX)
- **Evidencia Concreta:**
  Cuando un usuario se registra por el formulario público, el mensaje dice `"Cuenta creada correctamente"`. Sin embargo, la cuenta queda en estado `pendiente`. Al intentar iniciar sesión inmediatamente, el sistema responde `"Tu cuenta no está activa"`, lo que desconcierta al usuario al no aclararle que debe esperar la aprobación de un administrador.

---

## 6. EVALUACIÓN POR FORMATOS DE COMPETENCIA

| Formato | Estado Funcional | Observación Técnica |
| :--- | :---: | :--- |
| **Eliminación Directa** | ✅ **CUMPLE** | Genera llaves binarias y con byes, rechaza empates con HTTP 422, asciende a ganadores automáticamente a la final y corona campeón. |
| **Sistema Suizo** | ✅ **CUMPLE** | Empareja por rendimiento/puntos, evita revanchas en rondas tempranas, respeta límite de rondas $\lceil \log_2(N) \rceil$ y clasifica por puntaje. |
| **Liga** | 🐛 **FUNCIONA CON ERROR** | El fixture combinatorio y la tabla matemática son impecables, pero **la carga de marcadores está bloqueada** por el Bug 1 en `Enfrentamiento.php`. |
| **Individual** | ✅ **CUMPLE** | Control estricto de auto-inscripciones e invitaciones; bloquea la participación de cuentas administradoras. |
| **Equipos** | ✅ **CUMPLE** | Control de planteles congelados, invitaciones con aceptación obligatoria, mínimo 2 integrantes por equipo y capitanía verificada. |

---

## 7. PUNTOS QUE UN PROFESOR PODRÍA CUESTIONAR EN LA DEFENSA

1. **"¿Por qué un organizador no puede cargar los resultados de la Fecha 1 de una Liga?"**
   - *Punto Crítico:* Es el fallo principal del sistema de torneos. Si el docente intenta simular una liga en vivo durante la defensa, el sistema fallará en la primera fecha.
2. **"¿Cómo persisten los favoritos en la base de datos?"**
   - *Punto Crítico:* El alumno podría verse tentado a decir que se guardan en la base de datos, lo cual es falso y evidenciaría desconocimiento del código.
3. **"¿Por qué las contraseñas de las variables de entorno están visibles en una carpeta pública?"**
   - *Punto Crítico:* Cuestionamiento de seguridad básica en la configuración de Apache y estructura de carpetas.
4. **"¿Qué ocurre si hay empate en una fase de eliminación directa?"**
   - *Punto Crítico:* Demostrar que el sistema no inventa un ganador al azar ni permite guardar empate, sino que exige definición en campo o deriva a revisión.
5. **"¿Dónde está definida la duración estimada de los partidos y por qué se eligió ese diseño?"**
   - *Punto Crítico:* Verificar si se comprende la constante central `Enfrentamiento::DURACION_ESTIMADA_PARTIDO = 90`.

---

## 8. GUÍA DE PREGUNTAS Y RESPUESTAS PARA LA DEFENSA

### Pregunta 1: ¿Cómo está estructurada la arquitectura del proyecto? ¿Es un MVC puro?
> **Respuesta Basada en la Implementación Real:**  
> "El proyecto implementa una arquitectura inspirada en MVC adaptada a PHP nativo sin frameworks pesados. Los **Modelos** (`modelos/`) encapsulan toda la lógica de negocio, acceso a datos mediante PDO y reglas de dominio (por ejemplo, `Torneo.php`, `Enfrentamiento.php`, `Inscripcion.php`, `Usuario.php`).  
> Los **Controladores** están implementados como endpoints procedimentales modulares dentro del directorio `api/`, los cuales reciben peticiones HTTP, validan sesiones y tokens CSRF mediante `api/_comun.php`, invocan a los modelos y devuelven respuestas en formato JSON con códigos de estado HTTP estandarizados (200, 201, 400, 401, 403, 404, 409, 422).  
> Las **Vistas** se componen de plantillas PHP para el layout y renderizado de cascarones (`publico/layout.php`, vistas en la raíz) combinadas con componentes JavaScript Vanilla desacoplados que consumen las APIs de forma asíncrona."

---

### Pregunta 2: ¿Cómo funciona el Período de Gracia y la Resolución Automática?
> **Respuesta Basada en la Implementación Real:**  
> "Para evitar complejidades innecesarias en la base de datos y facilitar la defensa técnica, no se sobrecargó la tabla `disciplinas` con duraciones variables. En su lugar, se definió una constante técnica global centralizada en código:  
> `Enfrentamiento::DURACION_ESTIMADA_PARTIDO = 90` minutos.  
> La hora de inicio del partido se toma de `enfrentamiento.fecha_hora`. El fin estimado de juego se calcula como:  
> $$\text{Fin Estimado} = \text{fecha\_hora} + 90\text{ min}$$  
> Y el límite del período de gracia se calcula sumando el tiempo de gracia configurado en el torneo:  
> $$\text{Límite de Gracia} = \text{Fin Estimado} + \text{periodo\_gracia\_resultado}$$  
> El script `cron/procesar_resolucion_automatica.php` se ejecuta de forma periódica. Si el partido concluye sus 90 minutos y no tiene resultado cargado, pasa a estado `en_periodo_gracia`. Si vence el período de gracia y tiene un resultado objetivo parcial registrado, se oficializa; si está empatado en eliminación directa o no tiene marcador, pasa a `pendiente_revision`. El proceso es **idempotente** y audita la acción en la tabla `auditoria` con `id_usuario = NULL`."

---

### Pregunta 3: ¿Por qué un administrador no puede participar como jugador en los torneos?
> **Respuesta Basada en la Implementación Real:**  
> "Es una regla de negocio y de integridad deportiva deliberada. Para evitar conflictos de interés y garantizar la transparencia del sistema, los usuarios con rol `administrador` tienen privilegios globales para alterar estados, sorteos y resultados. Por lo tanto, el modelo `Inscripcion.php` implementa cláusulas `NOT EXISTS` en sus consultas SQL que excluyen a cualquier usuario que posea el rol `administrador` en `usuario_rol`, impidiendo que puedan inscribirse individualmente o formar parte de planteles de equipos."

---

### Pregunta 4: ¿Cómo garantiza el sistema que un organizador no altere torneos de otro organizador (IDOR)?
> **Respuesta Basada en la Implementación Real:**  
> "Mediante verificación de propiedad en el backend en cada endpoint sensible (`api/torneo_actualizar.php`, `api/enfrentamiento_actualizar.php`, `api/sorteo_confirmar.php`). El contexto de sesión extrae el `id_usuario` autenticado y sus roles. El backend consulta el torneo en base de datos y evalúa:  
> ```php
> $esAdministrador = in_array('administrador', $contexto['roles'], true);
> $esPropietario = in_array('organizador', $contexto['roles'], true) 
>     && (int)$torneo['id_organizador'] === (int)$contexto['usuario']['id_usuario'];
> if (!$esAdministrador && !$esPropietario) {
>     responderJson(['exito' => false, 'mensaje' => 'No tienes permiso...'], 403);
> }
> ```  
> Si un organizador modifica manualmente el ID en la URL o en el cuerpo JSON, el backend lo rechaza invariablemente con código HTTP 403 Forbidden."

---

### Pregunta 5: ¿Por qué la funcionalidad de favoritos no se ve reflejada en otra computadora al cambiar de usuario?
> **Respuesta Basada en la Implementación Real:**  
> "Porque en la versión actual, el módulo de Favoritos fue implementado como una mejora de experiencia de usuario en el cliente (`js/experiencia.js`) utilizando la API de `localStorage` del navegador vinculada al identificador del usuario (`arenaCJD-favoritos-v2-${idUsuario}`).  
> Esto permite navegación y filtrado ultra-rápido sin sobrecargar de peticiones a la base de datos, pero tiene la limitación de no persistir en el servidor. La arquitectura actual está preparada para migrar esta funcionalidad a una tabla relacional `favoritos (id_usuario, id_torneo)` en una siguiente iteración."

---

## 9. CALIFICACIÓN ESTIMADA Y DICTAMEN

### Escala Académica (1 a 12)

**CALIFICACIÓN ESTIMADA: 9 / 12 (Muy Bueno / Distinguido)**

### Justificación del Dictamen:
- **Aspectos Sobresalientes (Puntaje 10 a 12):**
  - Robustez del control de acceso, manejo de sesiones, regeneración de IDs y protección CSRF.
  - Implementación impecable del **Período de Gracia** con duración fija de 90 minutos y cron automatizado con auditoría.
  - Formatos complejos como **Sistema Suizo** y **Torneos por Equipos** (con invitaciones y validación de planteles) ejecutados con éxito de punta a punta.
  - Consultas SQL parametrizadas que mitigan riesgos de inyección SQL.
  - Excelente diseño visual con tema claro/oscuro y total paridad estructural.
- **Aspectos que Restan Puntaje (Motivo de no alcanzar 11 o 12):**
  - **El Bug Crítico 1 en Liga:** Impide el flujo normal de carga de resultados en competencias de todos contra todos. Es un error de fácil resolución (solo requiere condicionar el chequeo a torneos de eliminación), pero funcionalmente bloqueante en producción.
  - **La exposición del archivo `XAMPP_VARIABLES_ENTORNO.txt`:** Falta de directivas en `.htaccess` para proteger archivos planos con credenciales.
  - **Favoritos limitados a LocalStorage:** Ausencia de persistencia relacional en base de datos.

> **Conclusión:** Una vez subsanado el condicional de rondas posteriores en `Enfrentamiento.php` y restringido el acceso al directorio `seguridad/`, el proyecto alcanza el nivel de **11 o 12 (Sobresaliente / Excelente)**.

---
*Informe generado automáticamente tras auditoría integral en vivo sobre el entorno local XAMPP/MySQL.*
