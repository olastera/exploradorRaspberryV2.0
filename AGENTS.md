# AGENTS.md

## Proyecto

Explorador de medios desarrollado en PHP sin framework. Permite navegar, reproducir, subir, mover y administrar películas, música y documentos. La interfaz usa Bootstrap 5, Bootstrap Icons y estilos propios inspirados en Petify.

## Estructura

- **index.php**: explorador principal y acciones sobre archivos.
- **dashboard.php**: panel de administración.
- **conversiones.php** / **convert.php**: interfaz y API de conversiones con FFmpeg.
- **serve.php**: entrega de archivos y soporte para HTTP Range.
- **config/**: carga de entorno, constantes y autoload.
- **src/Auth/**: autenticación, usuarios, CSRF y Turnstile.
- **src/Media/**: exploración, portapapeles y conversiones.
- **src/Security/PathValidator.php**: confinamiento de rutas.
- **src/System/Settings.php**: persistencia y validación de las rutas configurables.
- **views/**: layouts, vistas y modales.
- **assets/css/app.css**: tema y layout responsive.
- **assets/js/app.js**: comportamiento del cliente.
- **assets/vendor/**: Bootstrap e iconos locales.
- **storage/**: usuarios, caché, tokens y estados; no versionar su contenido.

## Reglas de trabajo

1. No mostrar, copiar ni versionar valores de **.env**.
2. No introducir credenciales, claves API o contraseñas predeterminadas en el código.
3. Toda operación que modifique estado mediante POST debe verificar Csrf::verify().
4. Toda ruta procedente del usuario debe pasar por PathValidator.
5. No sustituir la comprobación de límites de ruta por comparaciones de prefijo simples.
6. Mantener Bootstrap e iconos en local. No volver a depender exclusivamente de CDN.
7. Si se añade un tipo de recurso estático, actualizar de forma restrictiva **.htaccess**.
8. No editar ni eliminar archivos de medios reales durante pruebas.
9. Preservar el diseño oscuro, el sidebar responsive y la compatibilidad móvil.
10. Escapar HTML con htmlspecialchars() y datos JavaScript con json_encode().

## Verificación mínima

Ejecutar después de cualquier cambio:

~~~bash
for f in $(find . -type f -name '*.php' | sort); do php -l "$f" || exit 1; done
node --check assets/js/app.js
~~~

Comprobar además, según el cambio:

- Inicio y cierre de sesión.
- Navegación entre películas, música, documentos, dashboard y conversiones.
- Carga de app.css, Bootstrap, iconos y fuentes sin respuestas 403.
- Sidebar expandido, colapsado y vista móvil.
- Formularios POST con token CSRF.
- Reproducción parcial con rangos HTTP válidos e inválidos.
- Imposibilidad de acceder fuera de MEDIA_ROOT.

## Configuración

Consultar **.env.example**. Las variables relevantes son MEDIA_ROOT, MEDIA_MOVIES, MEDIA_MUSIC, MEDIA_DOCS, TURNSTILE_SITE_KEY, TURNSTILE_SECRET_KEY, OMDB_API_KEY, BOOTSTRAP_ADMIN_USER y BOOTSTRAP_ADMIN_PASSWORD.

Las variables BOOTSTRAP_ADMIN_* solo se usan para inicializar una instalación sin archivo de usuarios.

## Notas importantes

- Petify es una plantilla Flutter; este proyecto no comparte su arquitectura. Solo se replica su lenguaje visual mediante PHP, Bootstrap y CSS.
- **.htaccess** bloquea archivos directos salvo PHP y recursos estáticos permitidos en assets.
- **storage/.users.json** puede contener cuentas existentes. No reemplazarlo ni regenerarlo automáticamente.
- **storage/settings.json** contiene las rutas elegidas en Administración y prevalece sobre las rutas de medios de .env.
- El proyecto no dispone actualmente de una suite automatizada completa; lint y pruebas manuales focalizadas son obligatorios.
