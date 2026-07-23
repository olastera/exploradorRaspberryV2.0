# Explorador Raspberry V2

Explorador web privat per gestionar una biblioteca de pel·lícules, música i documents des d'una Raspberry Pi. Està desenvolupat amb PHP 8, JavaScript i Bootstrap, sense framework ni procés de compilació.

## Funcions

- Navegació per tres biblioteques configurables.
- Vista de graella i llista, cerca i paginació.
- Portades i metadades de pel·lícules mitjançant IMDb i OMDb.
- Caché de fitxes completes durant 30 dies.
- Reproducció de vídeo i àudio amb suport HTTP Range.
- Conversió de vídeos a MP4 web amb FFmpeg.
- Cua de conversions amb progrés i cancel·lació.
- Accions individuals i múltiples: canvi de nom, eliminació i moviment entre biblioteques.
- Agrupació de diverses carpetes de pel·lícules en una carpeta nova.
- Càrrega i previsualització de documents.
- Usuaris amb rols d'administrador i lector.
- Protecció CSRF, confinament de rutes i sessions amb caducitat.
- Tauler amb CPU, RAM, discos, temperatura i processos actius.
- Disseny fosc responsive amb Bootstrap i icones locals.

## Requisits

- Raspberry Pi o servidor Linux.
- Apache 2 amb **mod_rewrite**.
- PHP 8.0 o superior.
- Extensions PHP: JSON, Zip i OpenSSL.
- FFmpeg i FFprobe per a les conversions.
- Permisos de lectura i escriptura sobre les biblioteques administrades.

## Instal·lació

~~~bash
git clone https://github.com/olastera/exploradorRaspberryV2.0.git
cd exploradorRaspberryV2.0
cp .env_default .env
~~~

Edita **.env** amb les rutes i claus pròpies. No publiquis mai aquest fitxer.
En una instal·lació nova també has de definir BOOTSTRAP_ADMIN_USER i una contrasenya robusta abans del primer accés.

Configura Apache perquè el DocumentRoot apunti al projecte i permeti l'ús de **.htaccess**:

~~~apache
<Directory /ruta/al/explorador>
    AllowOverride All
    Require all granted
</Directory>
~~~

El servidor web necessita escriure a **storage**:

~~~bash
sudo chown -R www-data:www-data storage
sudo chmod -R 700 storage
~~~

## Configuració

Les variables disponibles es documenten a **.env_default**:

- MEDIA_ROOT: directori base inicial.
- MEDIA_MOVIES, MEDIA_MUSIC, MEDIA_DOCS: ubicacions inicials.
- TURNSTILE_SITE_KEY, TURNSTILE_SECRET_KEY: protecció de l'accés.
- OMDB_API_KEY: sinopsi, valoració, gènere, durada i portada alternativa.
- BOOTSTRAP_ADMIN_USER, BOOTSTRAP_ADMIN_PASSWORD: compte inicial.

Després del primer accés, les rutes es poden modificar a **Administració**. Es desen localment a **storage/settings.json**.

## Seguretat

- **.env** i tot el contingut privat de **storage** estan exclosos de Git.
- No hi ha credencials predeterminades al codi.
- Totes les operacions d'escriptura requereixen autenticació administrativa i CSRF.
- Els fitxers se serveixen mitjançant **serve.php**; no s'exposen directament.
- Les rutes sol·licitades queden confinades dins de cada biblioteca.

Es recomana utilitzar HTTPS, contrasenyes úniques i una còpia de seguretat abans d'operacions massives.

## Verificació

~~~bash
for file in $(find . -type f -name '*.php' | sort); do php -l "$file" || exit 1; done
node --check assets/js/app.js
~~~

## Estructura principal

~~~text
assets/        CSS, JavaScript, Bootstrap i icones
config/        bootstrap de l'aplicació i autoload
src/           autenticació, seguretat, mitjans i sistema
storage/       dades locals no publicables
views/         layouts, vistes i modals
index.php      explorador principal
dashboard.php  tauler i administració
convert.php    API de conversions
serve.php      servidor autenticat de fitxers
~~~

## Llicència

Projecte d'ús personal. Afegeix una llicència abans de redistribuir-lo.
