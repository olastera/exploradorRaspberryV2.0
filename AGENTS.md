# AGENTS.md

## Proyecto

Explorador de medios desarrollado en PHP 8 sin framework ni proceso de compilación (sin Composer, sin build de assets). Permite navegar, reproducir, subir, mover y administrar tres bibliotecas: películas, música y documentos, desde una Raspberry Pi u otro servidor Linux con Apache. La interfaz usa Bootstrap 5, Bootstrap Icons (servidos en local) y estilos propios inspirados en Petify (solo el lenguaje visual; no se comparte su arquitectura Flutter).

## Arquitectura y flujo de arranque

Todo script de entrada (`index.php`, `dashboard.php`, `conversiones.php`, `convert.php`, `serve.php`, `imdb_search.php`, `login.php`) empieza con `require_once __DIR__ . '/config/bootstrap.php'` y llama a `Auth::requireAuth()` salvo `imdb_search.php`, que no requiere sesión.

`config/bootstrap.php`:
1. Registra el autoload PSR-4 simplificado (`config/autoload.php`, prefijo `App\` → `src/`).
2. Parsea `.env` línea a línea con `putenv()` (sin librerías externas).
3. Define `APP_ROOT` y `STORAGE_DIR`.
4. Lee `storage/settings.json` (si existe) y calcula `MEDIA_ROOT`, `MEDIA_MUSIC`, `MEDIA_DOCS`: las rutas guardadas en `settings.json` (vía Administración) **prevalecen** sobre las variables `.env`. `MEDIA_MOVIES` queda fijo en `'.'`.
5. Define `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY`, `OMDB_API_KEY`.
6. Arranca la sesión PHP.

No hay `.env` de ejemplo único: conviven `.env_default` (plantilla principal, documentada en README) y `.env.example`; ambos deben mantenerse sincronizados si se añade una variable nueva.

## Estructura de archivos

- **index.php**: explorador principal. Resuelve la biblioteca activa (`lib`), fuerza `movies` para no-admins, gestiona todas las acciones POST (borrar, renombrar, portapapeles copiar/cortar dentro de la misma biblioteca, agrupar carpetas de vídeo, extraer vídeos, subir archivos, mover entre bibliotecas, CRUD de usuarios) y delega el listado a `FileExplorer`. Incluye funciones locales `deleteRecursive`, `uniqueDestinationPath`, `removeEmptyDirectories`, `handleUpload`. El pegado de portapapeles (`clipboard_action`/`clipboard_items`/`clipboard_source`) resuelve siempre el destino con `uniqueDestinationPath()` sobre el directorio actual, para evitar sobrescrituras o bucles al pegar sobre el mismo origen.
- **dashboard.php**: panel de administración (solo admin). Tres pestañas: Biblioteca (contadores, gráfico de distribución SVG, archivos recientes), Sistema (CPU/RAM/discos/temperatura/procesos vía `Dashboard`), Administración (rutas de bibliotecas vía `Settings`, gestión de usuarios vía `UserManager`). Expone endpoints AJAX propios en el mismo archivo: `?ajax=1` (stats), `?processes=1` (procesos), `?recent=1` (archivos recientes); son de solo lectura y no llevan token CSRF, pero requieren sesión de admin.
- **conversiones.php**: vista de la cola de conversiones FFmpeg (solo admin); toda la lógica de refresco/cancelación es JS que llama a `convert.php`. Ese JS vive en `$pageScript`, una única cadena PHP con comillas simples escapadas (`\'`) que main-footer.php vuelca dentro de un `<script>`. Es un patrón frágil: un `php -l` correcto no garantiza que el JS generado sea válido — un `'` sin escapar dentro de un atributo `onclick` (p. ej. `href='algo'`) rompe el `<script>` entero de forma silenciosa (sin excepción capturable), dejando la página bloqueada en "Cargando…" porque ni `refreshList()` ni el listener de `DOMContentLoaded` llegan a definirse. Si se toca este bloque, preferir entidades HTML (`&#39;`) para comillas anidadas en vez de escaparlas como comilla literal.
- **convert.php**: API JSON de conversiones (`list`, `status`, `start`, `delete`, `cancel`, `gentoken`). `start`/`delete`/`cancel` exigen POST + CSRF.
- **serve.php**: entrega archivos con soporte HTTP Range (streaming de vídeo/audio) y `Content-Disposition` según tipo. Acepta autenticación por sesión o por `token` (ver más abajo).
- **imdb_search.php**: API pública (sin auth) de metadatos de películas, usada por las portadas y el botón Info. No mutar: solo GET, sin CSRF por diseño.
- **login.php**: fuerza `Auth::logout()` y redirige al formulario de login (usado como salida "forzar reautenticación").
- **config/bootstrap.php**, **config/autoload.php**: ver arriba.
- **src/Auth/Auth.php**: login, logout, timeout de sesión, formulario de login inline (HTML embebido en PHP).
- **src/Auth/Csrf.php**: token de sesión (`token()`/`verify()`) y tokens de archivo de un solo propósito (`tokenGenerate()`/`tokenVerify()`), guardados en `storage/tokens/*.json` con expiración de 2 horas.
- **src/Auth/Turnstile.php**: verifica Cloudflare Turnstile. **Falla abierto** (`return true`) si `TURNSTILE_SECRET_KEY` está vacío; es intencional para instalaciones sin Turnstile configurado, pero debe tenerse en cuenta en cualquier revisión de seguridad.
- **src/Auth/UserManager.php**: CRUD de usuarios en `storage/.users.json` (contraseñas con `password_hash`/bcrypt). `migrate()` crea el primer admin desde `BOOTSTRAP_ADMIN_USER`/`BOOTSTRAP_ADMIN_PASSWORD` solo si el archivo de usuarios no existe. Impide borrar o degradar al último admin.
- **src/Media/FileExplorer.php**: resolución de raíz por biblioteca, listado de directorio (oculta dotfiles y `.php`), extensiones reconocidas (vídeo/audio/imagen/documento), formateo de tamaños, construcción de URL de `serve.php`.
- **src/Media/Clipboard.php**: copiar/cortar archivos y directorios (recursivo); `cut()` intenta `rename()` y cae a copiar+borrar si falla (por ejemplo, entre distintos discos).
- **src/Media/Converter.php**: cola de conversión FFmpeg. `processQueue()` lanza hasta `maxConcurrent` (2) procesos `ffmpeg` en segundo plano vía `nohup ... & echo $!`, capturando el PID. `updateProgress()` parsea el log de FFmpeg (`time=HH:MM:SS.CS`) para estimar el progreso y detecta procesos muertos comprobando `/proc/$pid`.
- **src/Security/PathValidator.php**: confinamiento de rutas. `validate()` calcula la ruta real y cae al root si la petición se sale de límites; `validateIn()` devuelve `null` en ese caso. `isWithin()` compara con `realpath` + separador de directorio final, no con un simple `strpos` de prefijo sin separador (importante no regresar a esa forma insegura).
- **src/System/Dashboard.php**: métricas de sistema vía `/proc`, `shell_exec` (`free`, `df`, `uptime`, `vcgencmd`, `ps`). Específico de Linux; cada método degrada a valores neutros si el comando no está disponible.
- **src/System/Settings.php**: persiste `storage/settings.json` (rutas de bibliotecas) de forma atómica (`tmp` + `rename`), validando que cada ruta sea absoluta, exista y sea legible.
- **src/Imdb/ImdbSearch.php**: limpia el nombre de archivo (`cleanQuery`, quita extensión, corchetes, paréntesis, tags de release), busca en el endpoint de sugerencias de IMDb (no oficial, sin API key) y enriquece con OMDb (`enrichFromOmdb`, requiere `OMDB_API_KEY`) incluyendo traducción de la sinopsis en→ca vía MyMemory (sin key, cuota diaria gratuita muy baja y compartida por toda la app). Todas las llamadas HTTP externas usan `@file_get_contents` con timeout de 5 s y degradan a resultados parciales. Cuando MyMemory agota su cuota diaria, devuelve HTTP 200 con el aviso de límite metido en `responseData.translatedText` (no un error HTTP); `enrichFromOmdb()` lo detecta (`responseStatus` ≠ 200 o el texto contiene "MYMEMORY WARNING") y en ese caso conserva la sinopsis en inglés marcando `plot_lang = 'en'` en vez de guardar el aviso como si fuera la traducción.
- **src/Imdb/FileCache.php**: caché en `storage/cache/imdb/*.json` con TTL variable: 30 días si el resultado está completo, 6 horas si es incompleto, 1 hora si no se encontró (`MISS_TTL`). `isComplete()` exige además `plot_lang === 'ca'` (o ausente, para no invalidar cachés antiguas ya traducidas): un resultado con sinopsis en inglés por cuota de MyMemory agotada nunca se considera "completo", así que reintenta la traducción en la próxima ventana de 6 h en vez de quedar fijado 30 días con el inglés (o, como pasaba antes del fix, con el aviso de límite de MyMemory guardado como si fuera la sinopsis).
- **views/**: layouts (`main-header.php`, `main-footer.php`), vistas por biblioteca (`admin-movies.php`, `user-cards.php`, `music/album-grid.php`, `docs/file-list.php`) y modales parciales (`poster-modal.php`, `rename-modal.php`, `upload-modal.php`, `new-folder-modal.php`).
- **assets/css/app.css**: tema oscuro/claro (variable `data-theme` en `<html>`), sidebar responsive.
- **assets/js/app.js**: sin dependencias externas aparte de Bootstrap. Gestiona sidebar/tema (persistidos en `localStorage`), paginación y búsqueda del explorador en cliente (`initExplorerBrowser`, invocado para las tres bibliotecas, no solo `movies`), carga escalonada de carátulas (`loadPosterBatch`, lotes de 5 cada 250 ms, solo `movies`), modal de información de película (`showMovieInfo` → `imdb_search.php` → `showPosterModal`), selección múltiple y acciones en lote (`deleteSelected`, `moveSelected`, `openMergeVideoFolders`), portapapeles copiar/cortar/pegar dentro de la misma biblioteca (`clipboardCopySelected`, `clipboardCutSelected`, `pasteClipboard`, `clearClipboard`, estado en `localStorage['fileClipboard']` con `{library, action, items}`; el botón de pegar solo se muestra si `data.library` coincide con la biblioteca activa, porque el backend no soporta pegado entre bibliotecas), y envío de formularios ocultos con `csrf_token` para cualquier acción mutante.
- **assets/vendor/**: Bootstrap y Bootstrap Icons servidos localmente (no depender de CDN salvo el script de Cloudflare Turnstile, que sí se carga desde `challenges.cloudflare.com` en el formulario de login).
- **storage/**: `.users.json`, `settings.json`, `cache/imdb/`, `conversions/`, `tokens/`. Todo su contenido (salvo `.gitkeep`) está excluido de Git.
- **docs/superpowers/** y **.superpowers/sdd/**: artefactos de planificación de una tarea anterior de rediseño (specs, plan y reports de tareas). Son historial de una sesión de trabajo pasada, no especificaciones vivas ni instrucciones a seguir; no editar como si fueran documentación activa del proyecto.

## Modelo de autenticación y sesión

- `Auth::requireAuth()` primero comprueba `$_GET['token']`: si `Csrf::tokenVerify()` lo resuelve, concede acceso de rol `user` a un único archivo (`token_file`) sin necesidad de sesión ni contraseña. Este mecanismo es la base de los enlaces temporales de reproducción (usado por `conversiones.php` → `playConverted()` → `convert.php?action=gentoken` → `serve.php?...&token=...`).
- Si no hay token válido, procesa `?logout=1`, luego el formulario POST de login (usuario/contraseña + Turnstile), y si no, exige `$_SESSION['auth_user']` o muestra el formulario de login y corta la ejecución (`exit`).
- Sesión: `auth_user` (datos del usuario), `last_activity` (timeout deslizante de 7200 s comprobado en cada petición autenticada), `csrf_token` (regenerado en cada login).
- Roles: `admin` (acceso completo a las tres bibliotecas y a administración/conversiones) y `user` (solo lectura de `movies`, vista `views/user-cards.php`; `index.php` fuerza `lib=movies` si un no-admin pide otra biblioteca).
- `Auth::requireAuth()` conserva una variable `$authEnabled = true` sin uso real (siempre verdadera): no es un interruptor funcional, es código muerto — no depender de ella para "desactivar" la autenticación.

## Modelo de seguridad

1. No mostrar, copiar ni versionar valores de **.env**.
2. No introducir credenciales, claves API o contraseñas predeterminadas en el código.
3. Toda operación que modifique estado mediante POST debe verificar `Csrf::verify()` (patrón ya usado en `index.php`, `dashboard.php`, `convert.php`).
4. Toda ruta procedente del usuario debe pasar por `PathValidator::validate()`/`validateIn()` antes de tocar el filesystem.
5. No sustituir la comprobación de límites de ruta (`realpath` + comparación con separador final) por comparaciones de prefijo de cadena simples.
6. Mantener Bootstrap e iconos en local. No volver a depender exclusivamente de CDN (excepción ya existente y deliberada: el script de Cloudflare Turnstile en el login).
7. Si se añade un tipo de recurso estático, actualizar de forma restrictiva **.htaccess**.
8. No editar ni eliminar archivos de medios reales durante pruebas.
9. Preservar el diseño oscuro/claro, el sidebar responsive y la compatibilidad móvil.
10. Escapar HTML con `htmlspecialchars()` y datos JavaScript con `json_encode()`.
11. Los tokens de un solo archivo (`Csrf::tokenGenerate`/`tokenVerify`) son capacidad de acceso, no simple CSRF: validar siempre el formato (`/^[a-f0-9]{32}$/`), la expiración y que apunten a un archivo ya validado con `PathValidator` antes de generarlos.
12. Al tocar `Turnstile::verify()`, recordar que el comportamiento "sin secret key configurada = verificación pasa" es intencional; no lo trates como un bug a menos que se pida explícitamente cambiarlo.

## Bibliotecas y almacenamiento persistente

- Las tres raíces (`movies`, `music`, `docs`) se resuelven en `FileExplorer::getLibraryRoot()`; las rutas configuradas en Administración (`storage/settings.json`, gestionado por `Settings::savePaths()`) prevalecen sobre `.env`.
- `storage/.users.json`: lista de usuarios; puede contener cuentas ya existentes en una instalación real — no reemplazarlo ni regenerarlo automáticamente.
- `storage/tokens/*.json`: tokens de acceso temporal a un archivo (2 h de validez), generados por `Csrf::tokenGenerate()`.
- `storage/cache/imdb/*.json`: caché de metadatos de películas (clave `md5('ca|' . strtolower(cleanQuery))`).
- `storage/conversions/*.json` + `*.log`: estado y log de cada trabajo de conversión FFmpeg.

## Conversión de vídeo

- Cola gestionada por `App\Media\Converter`, con un máximo de 2 conversiones concurrentes (`processQueue`).
- Cada trabajo se lanza con `nohup ffmpeg ... & echo $!` vía `shell_exec`, siempre con `escapeshellarg()` sobre las rutas; nunca construir el comando concatenando entradas de usuario sin escapar.
- El progreso se estima parseando `time=HH:MM:SS.CS` del log de FFmpeg contra `ffprobe`-duration total; el estado pasa a `completed`/`failed` comprobando si el proceso sigue vivo (`/proc/$pid`) y si el archivo de salida existe con tamaño > 0.
- `cancel` mata el proceso (`exec("kill $pid")`) y borra la salida parcial; `gentoken` emite un token de un solo archivo para previsualizar el resultado sin exigir sesión completa.

## Metadatos de películas (IMDb/OMDb)

- `imdb_search.php` es la única API sin autenticación del proyecto: solo hace lecturas externas (IMDb suggestion endpoint + OMDb) y cachea en disco; no añadir aquí ninguna operación de escritura sobre el filesystem gestionado.
- El flujo "botón Info" es: tarjeta/lista de película → `showMovieInfo()` (JS) → `imdb_search.php?q=...` → `showPosterModal()` → `views/partials/poster-modal.php`. Conservar este flujo al modificar tarjetas o vista de lista.
- El TTL de caché depende de si el resultado está completo (`FileCache::isComplete`): 30 días completo, 6 h incompleto, 1 h sin resultados.

## Verificación mínima

Ejecutar después de cualquier cambio:

~~~bash
for f in $(find . -type f -name '*.php' -not -path './assets/vendor/*' | sort); do php -l "$f" || exit 1; done
node --check assets/js/app.js
~~~

Si se toca `$pageScript` (JS embebido en PHP) en `index.php`, `dashboard.php` o `conversiones.php`, `php -l` **no detecta errores de sintaxis en el JS generado**. Renderizar la página (por ejemplo incluyéndola desde CLI con una sesión simulada), extraer el contenido entre `<script>...</script>` y pasarlo por `node --check` antes de dar el cambio por bueno.

Comprobar además, según el cambio:

- Inicio y cierre de sesión, y timeout de sesión si se toca `Auth`.
- Navegación entre películas, música, documentos, dashboard y conversiones, con usuario `admin` y con usuario `user` (comprobando que este último no puede salir de `movies` ni ver acciones de administración).
- Carga de `app.css`, Bootstrap, iconos y fuentes sin respuestas 403 (afectado por cambios en `.htaccess`).
- Sidebar expandido, colapsado y vista móvil; tema claro/oscuro.
- Formularios POST con token CSRF válido e inválido (debe rechazar el inválido).
- Reproducción parcial con rangos HTTP válidos e inválidos (`serve.php`), incluyendo acceso vía token temporal.
- Imposibilidad de acceder fuera de la raíz de cada biblioteca (`../` y rutas absolutas ajenas).
- Si se toca la cola de conversiones: arrancar, cancelar y borrar un trabajo, y comprobar que no se disparan más de `maxConcurrent` procesos simultáneos.
- Si se toca `imdb_search.php`/`ImdbSearch`: comprobar el comportamiento sin `OMDB_API_KEY` configurada (debe degradar, no fallar).

## Configuración

Variables (documentadas en **.env_default** y **.env.example**, deben mantenerse sincronizadas): `MEDIA_ROOT`, `MEDIA_MOVIES`, `MEDIA_MUSIC`, `MEDIA_DOCS`, `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY`, `OMDB_API_KEY`, `BOOTSTRAP_ADMIN_USER`, `BOOTSTRAP_ADMIN_PASSWORD`.

- `BOOTSTRAP_ADMIN_*` solo se usan para inicializar una instalación sin `storage/.users.json`.
- `storage/settings.json` (rutas elegidas en Administración) prevalece sobre las rutas de medios de `.env` en tiempo de ejecución.
- Requiere en el servidor: PHP 8+, extensiones JSON/Zip/OpenSSL, Apache con `mod_rewrite` y `AllowOverride All`, FFmpeg + FFprobe en `PATH`, permisos de lectura/escritura sobre las bibliotecas y sobre `storage/`. No depender de la extensión `intl`: no está instalada en el servidor real y su uso (`IntlDateFormatter`) provocaba un error fatal no capturado que rompía la vista de Documentos entera (ver nota abajo). Usar `date()` nativo para formatear fechas.

## Notas importantes

- Petify es una plantilla Flutter; este proyecto no comparte su arquitectura. Solo se replica su lenguaje visual mediante PHP, Bootstrap y CSS.
- **.htaccess** bloquea archivos directos salvo PHP y recursos estáticos permitidos en `assets/css|js|vendor` (extensiones css/js/woff/woff2/ttf/svg/png/jpg/jpeg/gif/webp).
- **storage/.users.json** puede contener cuentas existentes. No reemplazarlo ni regenerarlo automáticamente.
- **storage/settings.json** contiene las rutas elegidas en Administración y prevalece sobre las rutas de medios de `.env`.
- En Películas, el botón Info de carpetas y archivos consulta **imdb_search.php** y abre **views/partials/poster-modal.php** mediante `showMovieInfo()`; conservar este flujo al modificar las tarjetas o la vista de lista.
- El sistema de tokens de un solo archivo (`Csrf::tokenGenerate`/`tokenVerify`, usado por el login vía `?token=` y por `convert.php?action=gentoken`) es un mecanismo de acceso independiente de la sesión; tenerlo en cuenta en cualquier cambio de autenticación o de `serve.php`.
- `Turnstile::verify()` falla abierto si no hay `TURNSTILE_SECRET_KEY`; comportamiento intencional para instalaciones sin Turnstile.
- `docs/superpowers/` y `.superpowers/sdd/` son artefactos de una sesión de planificación/ejecución anterior (rediseño de la interfaz); son historial, no documentación viva a mantener actualizada salvo que se esté continuando esa misma tarea.
- El proyecto no dispone actualmente de una suite automatizada completa; lint (`php -l`, `node --check`) y pruebas manuales focalizadas son obligatorios.
- `initExplorerBrowser()` (búsqueda, paginación y contador de elementos) solo se invocaba cuando `$library === 'movies'`; en Música y Documentos el buscador y los botones de paginación se renderizaban pero no hacían nada. Corregido para invocarla siempre; solo la carga de carátulas (`loadPosterBatch`) sigue siendo exclusiva de `movies`. El copiar/cortar/pegar entre bibliotecas fue una petición explícita del usuario: el backend (`Clipboard::copy()`/`cut()`) ya existía pero no tenía ningún botón conectado; ahora sí, limitado a la misma biblioteca (mover entre bibliotecas ya se cubre con `move_item`/`move_selected`, que es distinto).
- `views/docs/file-list.php` usaba `IntlDateFormatter` para formatear fechas; como la extensión `intl` no está instalada en el servidor real, esto lanzaba un `Error` no capturado en cuanto la biblioteca de Documentos tenía algún archivo o carpeta, cortando la página a mitad de render (nunca se llegaba a `main-footer.php`, así que ni el tema claro/oscuro ni ninguna otra acción JS funcionaban en esa vista). Sustituido por `date()` nativo; no reintroducir `IntlDateFormatter` ni otra dependencia de `intl` sin confirmar antes que la extensión está disponible.
- Despliegue real (Apache, `/etc/apache2/sites-enabled/000-default-le-ssl.conf`): la ruta **`/explorador/`** es un `Alias` directo a este directorio (`/mnt/disco/explorador/`) — es el despliegue que corresponde a este código, y sus peticiones quedan en `/var/log/apache2/access.log` y `error.log` (log general del vhost, no uno propio). Las rutas **`/peliculas/`** y **`/transmision/`** son `ProxyPass` hacia `http://127.0.0.1:8060/`, un proceso/instancia **distinto**, con su propio log (`/var/log/apache2/file-explorer-access.log`); no asumir que ese tráfico corresponde a cambios hechos aquí. Al depurar en producción, comprobar siempre `/explorador/` y su log general antes de sacar conclusiones.
