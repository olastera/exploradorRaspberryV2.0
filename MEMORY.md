# Memoria del proyecto

## Objetivo

Mantener un explorador web privado para una biblioteca multimedia alojada en disco. La aplicación ofrece una experiencia visual de panel moderno, administración de archivos y reproducción directa desde el navegador.

## Estado actual

- Aplicación PHP funcional y sin dependencias de framework.
- Diseño oscuro responsive inspirado visualmente en Petify.
- Bootstrap 5.3.0 y Bootstrap Icons 1.10.0 servidos localmente.
- Navegación lateral para películas, música, documentos, dashboard y conversiones.
- Autenticación por sesión, roles de administrador/usuario, Turnstile y CSRF.
- Búsqueda de metadatos cinematográficos con IMDb y OMDb.
- Las fichas completas de películas se conservan en caché durante 30 días; los fallos y fichas incompletas se reintentan antes.
- Conversión de vídeo en cola mediante FFmpeg.
- Streaming con soporte para solicitudes HTTP Range.

## Decisiones adoptadas

### Recursos locales

Bootstrap se cargaba inicialmente desde un CDN. Cuando el CDN no estaba disponible, todas las utilidades de maquetación desaparecían. Los recursos se copiaron a **assets/vendor** para que la interfaz no dependa de Internet.

### Configuración de Apache

El **.htaccess** bloqueaba cualquier archivo que no fuese PHP, lo que producía respuestas 403 para CSS, JavaScript y fuentes. Se añadió una excepción limitada a extensiones estáticas dentro de assets.

### Diseño

Petify está implementado en Flutter y no puede trasladarse directamente a este proyecto. Se mantienen sus ideas visuales —sidebar, tarjetas, tema oscuro, espacios y panel administrativo— mediante Bootstrap y CSS propio.

### Seguridad

- Las rutas se validan con límites de directorio completos.
- Las acciones mutables requieren POST y CSRF.
- Los tokens compartidos tienen formato hexadecimal estricto y caducidad.
- Las claves y credenciales se obtienen del entorno.
- TLS permanece verificado en las peticiones externas.
- Los rangos HTTP se validan y los inválidos devuelven 416.

## Incidencias resueltas

1. **conversiones.php** dejó de compilar por comillas incorrectas dentro de JavaScript incrustado.
2. El sidebar generaba enlaces relativos incorrectos desde dashboard y conversiones.
3. Faltaba comportamiento responsive para el menú lateral.
4. Bootstrap, iconos y fuentes no cargaban sin acceso al CDN.
5. **.htaccess** bloqueaba los recursos estáticos locales.
6. Existían acciones sin CSRF y validaciones de ruta insuficientes.
7. Había credenciales y una clave de OMDb incrustadas en el código.

## Riesgos y trabajo pendiente

- Crear pruebas automatizadas para autenticación, CSRF, rutas y HTTP Range.
- Separar el JavaScript incrustado de **conversiones.php** en un archivo propio.
- Añadir manejo visible de errores cuando fallen IMDb, OMDb o Turnstile.
- Revisar periódicamente Bootstrap y Bootstrap Icons antes de actualizarlos.
- Verificar el diseño en navegadores móviles reales y resoluciones pequeñas.
- Cambiar desde el panel cualquier contraseña histórica anterior al endurecimiento.

## Comprobación rápida

~~~bash
for f in $(find . -type f -name '*.php' | sort); do php -l "$f" || exit 1; done
node --check assets/js/app.js
~~~

Última actualización: 23 de julio de 2026.
