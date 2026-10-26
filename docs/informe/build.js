// Genera docs/informe/informe.html y el PDF final (Informe_Laboratorio_OpenSSL.pdf).
// Uso: node docs/informe/build.js   (requiere: cd herramientas && npm i puppeteer-core)
const fs = require('fs');
const path = require('path');
const puppeteer = require(path.join(__dirname, '../../herramientas/node_modules/puppeteer-core'));

const ROOT = path.resolve(__dirname, '../..');
const EDGE = 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe';
const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

let nFig = 0;
const fig = (file, titulo, desc, estilo = '') => {
  nFig++;
  return `<div class="bloque"><figure><img src="../../capturas/${file}" style="${estilo}"><figcaption><b>Figura ${nFig}.</b> ${titulo}</figcaption></figure>
  <p class="nota">${desc}</p></div>`;
};
const figPar = (a, b) => `<div class="par">${[a, b].map(([f, t]) => { nFig++; return `<figure><img src="../../capturas/${f}"><figcaption><b>Figura ${nFig}.</b> ${t}</figcaption></figure>`; }).join('')}</div>`;

// Tabla de documentación de una función (sección 1.5: 1.5.1 a 1.5.5)
const fn = (nombre, proposito, params, ret, proceso) => `<table class="fn">
<tr><th colspan="2" class="nombre">${nombre}</th></tr>
<tr><th>Propósito general</th><td>${proposito}</td></tr>
<tr><th>Parámetros recibidos</th><td>${params}</td></tr>
<tr><th>Valor devuelto</th><td>${ret}</td></tr>
<tr><th>Proceso criptográfico aplicado</th><td>${proceso}</td></tr>
</table>`;

const html = `<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Informe técnico – Seguridad en PHP con OpenSSL</title>
<style>
@page { size: Letter; margin: 2.2cm 2cm 2.2cm 2cm; }
@page portada { margin: 0; @bottom-center { content: none; } }
@page cuerpo { @bottom-center { content: "Página " counter(page); font: 10pt Arial, sans-serif; color: #444; }
               @top-right { content: "Laboratorio: Seguridad en PHP con OpenSSL"; font: 9pt Arial, sans-serif; color: #777; } }
body { font: 11pt/1.5 "Segoe UI", Arial, sans-serif; color: #1b1b1b; margin: 0; text-align: justify; }
.portada { page: portada; height: 27.4cm; box-sizing: border-box; padding: 1.5cm 2.2cm 0; overflow: hidden; text-align: center; page-break-after: always; border-top: 18px solid #5b2a86; }
.portada .logos { display: flex; justify-content: space-between; align-items: center; margin-bottom: .7cm; }
.portada .logos img { height: 3.2cm; }
.portada h1 { font-size: 20pt; margin: .2cm 0; color: #5b2a86; }
.portada h2 { font-size: 13pt; margin: .1cm 0; font-weight: 600; }
.portada .titulo { margin: .9cm 0; padding: .5cm; border: 2px solid #5b2a86; border-radius: 8px; }
.portada .titulo h2 { font-size: 18pt; color: #1b1b1b; }
.portada table { margin: 0 auto; text-align: left; border-collapse: collapse; font-size: 12pt; }
.portada td { padding: 3px 14px; border-bottom: 1px solid #ddd; }
.portada td:first-child { font-weight: 700; color: #5b2a86; }
.cuerpo { page: cuerpo; counter-reset: page 1; }
h1.sec { font-size: 16pt; color: #5b2a86; border-bottom: 2px solid #5b2a86; padding-bottom: 3px; margin: 0 0 .4cm; break-before: page; }
h1.sec.primera { break-before: auto; }
h2 { font-size: 13pt; color: #3d1d5c; margin: .6cm 0 .2cm; break-after: avoid; }
h3 { font-size: 11.5pt; margin: .4cm 0 .15cm; break-after: avoid; }
p { margin: .15cm 0 .25cm; }
table { border-collapse: collapse; width: 100%; margin: .25cm 0 .45cm; font-size: 10pt; text-align: left; }
th, td { border: 1px solid #b9b9c4; padding: 5px 8px; vertical-align: top; }
thead th, tr.cab th { background: #5b2a86; color: #fff; }
table.fn { break-inside: avoid; }
table.fn th.nombre { background: #5b2a86; color: #fff; font-family: Consolas, monospace; font-size: 10.5pt; }
table.fn th { width: 4.3cm; background: #efe9f6; }
table.fn td { font-size: 9.5pt; }
code, .mono { font-family: Consolas, "Courier New", monospace; font-size: 9.2pt; }
pre { background: #1f2430; color: #e8e8e8; padding: 10px 12px; border-radius: 6px; font: 8.8pt/1.4 Consolas, monospace; white-space: pre-wrap; text-align: left; break-inside: avoid; }
.bloque { break-inside: avoid; }
figure { margin: .3cm 0 .1cm; text-align: center; break-inside: avoid; }
figure img { max-width: 100%; max-height: 16.5cm; border: 1px solid #ccc; }
figcaption { font-size: 9.5pt; color: #444; margin-top: 3px; }
.par { display: flex; gap: .4cm; align-items: flex-start; break-inside: avoid; }
.par figure { flex: 1; min-width: 0; }
.par figure img { max-height: 11cm; }
p.nota { font-size: 10pt; background: #f4f0f9; border-left: 4px solid #5b2a86; padding: 6px 10px; margin: .1cm 0 .5cm; break-inside: avoid; }
.indice { line-height: 1.9; font-size: 11.5pt; } .indice div { display: flex; } .indice span.d { flex: 1; border-bottom: 1px dotted #999; margin: 0 6px 6px; }
ul, ol { margin: .1cm 0 .3cm; padding-left: 1.1cm; }
</style></head><body>

<section class="portada">
  <div class="logos"><img src="logo-utp.png"><img src="logo-fisc.png"></div>
  <h1>UNIVERSIDAD TECNOLÓGICA DE PANAMÁ</h1>
  <h2>FACULTAD DE INGENIERÍA DE SISTEMAS COMPUTACIONALES</h2>
  <h2>CAMPUS VÍCTOR LEVIS SASSO</h2>
  <h2>DESARROLLO WEB VII: PHP Y MYSQL</h2>
  <div class="titulo"><h2>INFORME TÉCNICO DE LABORATORIO</h2>
    <h1>Seguridad en PHP con OpenSSL</h1>
    <h2>Programación segura según OWASP – Protege los datos</h2></div>
  <table>
    <tr><td>Estudiante</td><td>Angel Gálvez</td></tr>
    <tr><td>Cédula</td><td>8-1038-857</td></tr>
    <tr><td>Correo</td><td>angel.galvez2@utp.ac.pa</td></tr>
    <tr><td>Asignatura</td><td>Desarrollo Web VII: PHP y MySQL</td></tr>
    <tr><td>Grupo</td><td>IS3122</td></tr>
    <tr><td>Docente</td><td>Ing. Irina Fong</td></tr>
    <tr><td>Período</td><td>II Semestre 2026</td></tr>
    <tr><td>Fecha de entrega</td><td>30 de octubre de 2026</td></tr>
    <tr><td>Repositorio</td><td>github.com/galvezangel074-glitch/OpenSSL</td></tr>
  </table>
</section>

<section class="cuerpo">
<h1 class="sec primera">Contenido</h1>
<div class="indice">
${['1. Introducción al tema', '2. Objetivo del laboratorio', '3. Entorno y estructura del proyecto', '4. Capturas de pantalla con descripciones', '5. Documentación técnica de funciones', '6. Investigación y ejemplo práctico con Libsodium (Sodium)', '7. Dificultades encontradas y soluciones', '8. Conclusiones personales', '9. Referencias'].map((t) => `<div>${t}</div>`).join('')}
</div>

<h1 class="sec primera" style="margin-top:.8cm">1. Introducción al tema</h1>
<p>La criptografía aplicada permite que un sistema web proteja la información que maneja aunque un atacante llegue a tocar el servidor, la base de datos o la red. La guía del laboratorio parte de la categoría <b>A02:2021 – Cryptographic Failures</b> del OWASP Top 10: los datos sensibles (contraseñas, tokens, tarjetas, registros de salud, secretos del negocio) <b>nunca deben almacenarse ni viajar en texto plano</b>, y lo mejor es evitar guardar datos sensibles cuando no es necesario.</p>
<p><b>OpenSSL</b> es una biblioteca de código abierto que implementa los protocolos TLS/SSL y ofrece funciones para generar llaves, administrar certificados X.509, cifrar y descifrar, y firmar datos. PHP la expone mediante la extensión <code>openssl</code> (funciones <code>openssl_*</code>). Con ella se resuelven los dos pilares que pide el laboratorio:</p>
<table><thead><tr><th>Pilar</th><th>Qué garantiza</th><th>Técnicas usadas en el laboratorio</th></tr></thead>
<tr><td><b>Confidencialidad</b></td><td>Solo quien posee la llave puede leer el dato, en reposo y en tránsito.</td><td>Cifrado simétrico (AES-128-CBC, AES-256-CBC, AES-256-GCM, XSalsa20-Poly1305) y asimétrico (RSA-OAEP, sealed box X25519).</td></tr>
<tr><td><b>Integridad y autenticidad</b></td><td>El dato no fue alterado por terceros y proviene de quien dice haberlo enviado.</td><td>Firmas digitales (RSA con SHA-256, Ed25519), HMAC-SHA3-512 y etiquetas de autenticación AEAD (GCM, Poly1305).</td></tr></table>
<p>La guía recomienda además revisar <b>Libsodium (Sodium)</b>, incluida por defecto en PHP desde la versión 7.2, que ofrece criptografía moderna pensada para ser segura por defecto. El laboratorio ejecuta los scripts entregados por la docente, les agrega formularios, documenta cada función y complementa el trabajo con una investigación y un ejemplo ejecutable de Sodium.</p>

<h1 class="sec primera" style="margin-top:.8cm">2. Objetivo del laboratorio</h1>
<h2>Objetivo general</h2>
<p>Aplicar la extensión OpenSSL y librerías criptográficas modernas (Libsodium) en PHP, comprendiendo su propósito para proteger datos sensibles según los lineamientos de OWASP, mediante la ejecución de ejemplos prácticos y el análisis de su documentación técnica.</p>
<h2>Objetivos específicos y metas alcanzadas</h2>
<table><thead><tr><th>Meta</th><th>Resultado</th></tr></thead>
<tr><td>Verificar y habilitar OpenSSL y Sodium en WampServer</td><td>Ambas extensiones activas (OpenSSL 3.0.18, libsodium 1.0.18). Figura 2.</td></tr>
<tr><td>Ejecutar localmente los scripts entregados</td><td>Los 10 scripts funcionan; se corrigieron la ruta de <code>openssl.cnf</code> y el uso del CSR (sección 7).</td></tr>
<tr><td>Agregar formularios para recibir los datos (E.2.2)</td><td>Todos los scripts reciben sus datos por formulario con token CSRF y salida escapada.</td></tr>
<tr><td>Probar <code>firma7</code>, <code>firmaOtra</code>, <code>FirmarMensaje</code> y la encriptación simple (E.2.3)</td><td>Probados con entrada válida y con datos alterados; evidencias en la sección 4.</td></tr>
<tr><td>Documentar las funciones criptográficas</td><td>Tablas con nombre, propósito, parámetros, valor devuelto y proceso (sección 5).</td></tr>
<tr><td>Investigar e implementar Libsodium</td><td>Investigación teórica y cinco demostraciones ejecutables (sección 6).</td></tr></table>

<h1 class="sec">3. Entorno y estructura del proyecto</h1>
<h2>3.1 Entorno utilizado</h2>
<table><thead><tr><th>Componente</th><th>Versión / detalle</th></tr></thead>
<tr><td>Sistema operativo</td><td>Windows 11 Home</td></tr>
<tr><td>Servidor local</td><td>WampServer 64 bits · Apache 2.4.65</td></tr>
<tr><td>PHP</td><td>8.3.28 (extensiones <code>openssl</code> y <code>sodium</code> habilitadas)</td></tr>
<tr><td>OpenSSL</td><td>3.0.18 (30 Sep 2025)</td></tr>
<tr><td>Libsodium</td><td>1.0.18</td></tr>
<tr><td>Editor y control de versiones</td><td>Visual Studio Code · Git y GitHub</td></tr></table>
<h2>3.2 Estructura de archivos</h2>
<table><thead><tr><th>Archivo</th><th>Función</th><th>Tipo</th></tr></thead>
<tr><td><code>Informacion.php</code></td><td>Estado de OpenSSL y Sodium, algoritmos disponibles</td><td>Verificación</td></tr>
<tr><td><code>Encriptacion.php</code></td><td>Encriptación simple: AES-128-CBC, AES-256-CBC, AES-256-GCM</td><td>Requerido</td></tr>
<tr><td><code>firma7.php</code></td><td>Par de llaves RSA, firma y verificación</td><td>Requerido</td></tr>
<tr><td><code>firmaOtra.php</code></td><td>Llave privada, CSR y certificado X.509 autofirmado</td><td>Requerido</td></tr>
<tr><td><code>FirmarMensaje.php</code></td><td>Firma con la llave de firmaOtra y verificación con su certificado</td><td>Requerido</td></tr>
<tr><td><code>sodium.php</code></td><td>Libsodium: secretbox, Ed25519, Argon2id, sealed box, rendimiento</td><td>Sección 1.6</td></tr>
<tr><td><code>encriptacionOpenssl_2.php</code></td><td>AES-256-CBC + HMAC-SHA3-512 (Encrypt-then-MAC)</td><td>Complementario</td></tr>
<tr><td><code>firmaDiprena.php</code></td><td>Firma de un trámite de personal (sin base de datos)</td><td>Complementario</td></tr>
<tr><td><code>firma4.php</code></td><td>Solicitud de certificado (CSR)</td><td>Complementario</td></tr>
<tr><td><code>firmaDigital.php</code></td><td>Cifrado RSA-OAEP con llaves exportadas a archivo</td><td>Complementario</td></tr>
<tr><td><code>includes/bootstrap.php</code></td><td>Configuración común: localizar <code>openssl.cnf</code>, CSRF, escape XSS</td><td>Soporte</td></tr>
<tr><td><code>originales/</code></td><td>Scripts entregados por la docente, sin modificar</td><td>Referencia</td></tr></table>
<h2>3.3 Medidas de seguridad aplicadas (OWASP)</h2>
<ul><li><b>CSRF:</b> cada formulario incluye un token de sesión que se valida en el servidor.</li>
<li><b>XSS:</b> toda salida se escapa con <code>htmlspecialchars</code> (función <code>h()</code>).</li>
<li><b>Llaves privadas:</b> se generan en tiempo de ejecución, están en <code>.gitignore</code> y sus carpetas tienen <code>.htaccess</code> con <code>Require all denied</code>.</li>
<li><b>Claves derivadas:</b> las contraseñas de usuario se convierten en llaves con PBKDF2-SHA256 (OpenSSL) o Argon2id (Sodium), con sal aleatoria.</li>
<li><b>Aleatoriedad segura:</b> IV y nonces generados con <code>openssl_random_pseudo_bytes</code> o <code>random_bytes</code>.</li></ul>

<h1 class="sec">4. Capturas de pantalla con descripciones</h1>
<p>Cada captura se tomó ejecutando el formulario en el navegador (Edge) sobre <code>http://localhost/OPENSSL/</code>; el pie de página de cada captura muestra el nombre del estudiante, la versión de PHP, OpenSSL y libsodium, y la fecha de ejecución.</p>

<h2>4.1 Inicio y verificación del entorno</h2>
${fig('01_inicio.png', 'Página de inicio del laboratorio', 'Menú con los diez scripts y el estado del entorno: OpenSSL y Sodium habilitados, y la ruta de <code>openssl.cnf</code> detectada automáticamente (Solución 2 de la guía).')}
${fig('02_informacion.png', 'Informacion.php – estado de las extensiones', 'Confirma que la extensión OpenSSL está activa, la versión de PHP, el <code>php.ini</code> cargado, los algoritmos AES/SHA disponibles y que Sodium está habilitada. Es la verificación pedida en E.1.2.')}

<h2>4.2 Encriptación simple con OpenSSL (Encriptacion.php)</h2>
${fig('03_encriptacion_cifrar_aes128cbc.png', 'Cifrado con AES-128-CBC', 'Entrada: texto y contraseña. El script deriva la llave con PBKDF2, genera un IV aleatorio y muestra el paquete cifrado en Base64 (sal + IV + cifrado). El texto original ya no es legible.')}
${fig('04_encriptacion_descifrar.png', 'Descifrado del paquete', 'Se pega el paquete y la misma contraseña; <code>openssl_decrypt</code> recupera el texto original, lo que demuestra que el cifrado es reversible solo con la llave.')}
${fig('05_encriptacion_cifrar_aes256gcm.png', 'Cifrado con AES-256-GCM', 'GCM es un modo autenticado: además del cifrado produce una etiqueta (tag) de 16 bytes que se guarda en el paquete.')}
${fig('06_encriptacion_gcm_alterado.png', 'GCM con el paquete alterado', 'Al modificar el paquete antes de descifrar, el tag de autenticación no coincide y <code>openssl_decrypt</code> rechaza el mensaje. Demuestra la integridad que CBC por sí solo no ofrece.')}

<h2>4.3 Cifrado con HMAC (encriptacionOpenssl_2.php)</h2>
${fig('07_cifrado_hmac_cifrar.png', 'AES-256-CBC + HMAC-SHA3-512 – cifrar', 'Dos llaves independientes (cifrado y HMAC). La salida es <code>base64(iv . hmac . cifrado)</code>.')}
${fig('08_cifrado_hmac_verificar.png', 'Verificación del HMAC y descifrado', 'El HMAC se recalcula y se compara en tiempo constante; si es válido, se descifra el texto.')}
${fig('09_cifrado_hmac_alterado.png', 'Texto alterado: HMAC inválido', 'Un cambio en el cifrado produce un HMAC distinto y el script se niega a descifrar (Encrypt-then-MAC).')}

<h2>4.4 firma7.php – par de llaves RSA y firma digital</h2>
${fig('10_firma7_firmar.png', 'Generación de llaves RSA-2048 y firma', 'Entrada: datos a firmar y tamaño de llave. Resultado: llaves guardadas en <code>keys/</code>, firma de 256 bytes (Base64), huella SHA-256 de los datos y las llaves PEM generadas. Las llaves son de práctica y no se usan en ningún sistema.')}
${fig('11_firma7_verificar_valida.png', 'Verificación con los datos originales', '<code>openssl_verify</code> devuelve 1: la firma es válida y los datos son confiables.')}
${fig('12_firma7_verificar_alterado.png', 'Verificación con los datos alterados', 'Cambiar un solo carácter hace que <code>openssl_verify</code> devuelva 0: la firma es inválida. Demuestra el principio de integridad.')}

<h2>4.5 firmaOtra.php y FirmarMensaje.php – certificado X.509</h2>
${fig('13_firmaOtra_certificado.png', 'Llave privada, CSR y certificado autofirmado', 'Entrada: datos del titular (país, provincia, organización, unidad, nombre común, días de validez). Resultado: certificado <code>certout.crt</code>, solicitud <code>request.csr</code> y llave <code>privkey.pem</code>, con sujeto, emisor, número de serie, vigencia (365 días), algoritmo sha256WithRSAEncryption, huella SHA-256 y la comprobación de que la llave privada corresponde al certificado.')}
${fig('14_FirmarMensaje_firmar.png', 'Firma de un mensaje con la llave de firmaOtra', 'El mensaje se firma con <code>keysCert/privkey.pem</code> y la firma se muestra en Base64.')}
${fig('15_FirmarMensaje_verificar_valida.png', 'Verificación válida con el certificado', 'La llave pública se extrae del certificado y la firma se comprueba: el mensaje no fue alterado.')}
${fig('16_FirmarMensaje_verificar_alterado.png', 'Mensaje alterado: firma inválida', 'Con un mensaje distinto al firmado, la verificación falla.')}

<h2>4.6 Scripts complementarios</h2>
${fig('17_firmaDiprena_firmar.png', 'firmaDiprena – firma de un trámite', 'Los campos del trámite se serializan con <code>json_encode</code> y se firman con las llaves de firma7.')}
${fig('18_firmaDiprena_salario_alterado.png', 'firmaDiprena – salario alterado', 'Modificar el salario invalida la firma: la verificación detecta la manipulación del dato sensible.')}
${fig('19_firma4_csr.png', 'firma4 – solicitud de certificado (CSR)', 'Muestra la solicitud generada, su sujeto y la llave pública incluida.')}
${fig('20_firmaDigital_rsa_oaep.png', 'firmaDigital – cifrado RSA-OAEP', 'Cifra con la llave pública y descifra con la privada, usando el relleno OAEP.')}

<h2>4.7 Libsodium (sodium.php)</h2>
${fig('21_sodium_pagina.png', 'Página de Sodium', 'Resumen de las cinco demostraciones y de las versiones de libsodium y PHP.')}
${figPar(['22_sodium_secretbox_cifrar.png', 'secretbox – cifrar'], ['23_sodium_secretbox_descifrar.png', 'secretbox – descifrar'])}
<p class="nota">La llave se deriva de la contraseña con Argon2id; <code>sodium_crypto_secretbox</code> cifra y autentica en una sola llamada y el descifrado devuelve el texto original.</p>
${fig('24_sodium_secretbox_alterado.png', 'secretbox con el cifrado alterado', '<code>sodium_crypto_secretbox_open</code> devuelve <code>false</code>: el MAC Poly1305 detecta la alteración.')}
${figPar(['25_sodium_ed25519_firmar.png', 'Ed25519 – firmar'], ['26_sodium_ed25519_verificar.png', 'Ed25519 – verificar'])}
<p class="nota">Firma separada de 64 bytes con llave pública de 32 bytes.</p>
${fig('27_sodium_ed25519_alterado.png', 'Ed25519 con el mensaje alterado', 'La verificación falla al cambiar el mensaje.')}
${figPar(['28_sodium_argon2id_hash.png', 'Argon2id – hash'], ['29_sodium_argon2id_correcta.png', 'Contraseña correcta'])}
${fig('30_sodium_argon2id_incorrecta.png', 'Argon2id – contraseña incorrecta', 'El hash almacenado incluye algoritmo, parámetros y sal; <code>sodium_crypto_pwhash_str_verify</code> acepta solo la contraseña original.')}
${fig('31_sodium_sealed_box.png', 'Sealed box (cifrado asimétrico anónimo)', 'Cualquiera cifra con la llave pública del destinatario; solo el dueño de la llave privada puede abrir el mensaje.')}
${fig('32_sodium_rendimiento.png', 'Comparativa de rendimiento OpenSSL vs Sodium', 'Medición en este equipo: AES-256-GCM de OpenSSL (con aceleración por hardware) cifra 1 MB en 0.201 ms, frente a 1.689 ms de secretbox; en cambio, Ed25519 genera llaves en 0.064 ms frente a 58.020 ms de RSA-2048 y firma 5.6 veces más rápido.')}

<h1 class="sec">5. Documentación técnica de funciones</h1>
<p>Cada función se documenta con los cinco elementos exigidos: nombre, propósito general, parámetros recibidos, valor devuelto y explicación del proceso criptográfico.</p>

<h2>5.1 Scripts del laboratorio (pruebas requeridas)</h2>
${fn('firma7.php – firma y verificación RSA', 'Generar un par de llaves RSA, firmar un texto y comprobar su integridad.', '<code>datos</code> (texto), <code>bits</code> (2048/4096), <code>regenerar</code> (checkbox), <code>firma</code> (Base64) y <code>csrf</code>.', 'Página con la firma en Base64 (guardada en <code>keys/signature.dat</code>), SHA-256 de los datos, llaves PEM y el resultado «firma válida / inválida».', '<code>openssl_pkey_new</code> → <code>openssl_pkey_export</code> / <code>openssl_pkey_get_details</code> → <code>openssl_sign</code> (SHA-256 + RSA PKCS#1 v1.5) → <code>openssl_verify</code> con la llave pública.')}
${fn('firmaOtra.php – certificado X.509 autofirmado', 'Crear la identidad digital (llave, CSR y certificado) de una organización.', 'Campos del nombre distinguido (país, provincia, localidad, organización, unidad, nombre común, correo), días de validez, bits de la llave y <code>csrf</code>.', '<code>keysCert/privkey.pem</code>, <code>request.csr</code>, <code>certout.crt</code> y el detalle del certificado (sujeto, emisor, serie, vigencia, huella).', '<code>openssl_pkey_new</code> → <code>openssl_csr_new</code> → <code>openssl_csr_sign</code> (autofirmado) → <code>openssl_x509_export</code>; <code>openssl_x509_check_private_key</code> confirma que la llave corresponde al certificado.')}
${fn('FirmarMensaje.php – firma con certificado', 'Firmar un mensaje con la llave de firmaOtra y verificarlo con el certificado.', '<code>mensaje</code>, <code>firma</code> (Base64) y <code>csrf</code>.', 'Firma en Base64 y resultado de <code>openssl_verify</code> (1, 0 o -1).', '<code>openssl_pkey_get_private</code> → <code>openssl_sign</code> (SHA-256) → <code>base64_encode</code>. Para verificar: <code>base64_decode</code> → <code>openssl_pkey_get_public</code> (desde el certificado) → <code>openssl_verify</code>.')}
${fn('Encriptacion.php – cifrado simétrico', 'Proteger un texto en reposo con AES y recuperarlo con la contraseña.', '<code>texto</code>, <code>clave</code> (contraseña), <code>algoritmo</code> (AES-128-CBC, AES-256-CBC, AES-256-GCM), <code>paquete</code> (para descifrar) y <code>csrf</code>.', 'Paquete Base64 (sal ‖ IV ‖ tag [solo GCM] ‖ cifrado) y, al descifrar, el texto original o el error.', '<code>derivarClave()</code> aplica PBKDF2-SHA256 con sal aleatoria; el IV se genera con <code>openssl_random_pseudo_bytes</code>; después <code>openssl_encrypt</code> / <code>openssl_decrypt</code>. En GCM, el tag detecta cualquier alteración.')}

<h2>5.2 Scripts complementarios</h2>
${fn('encriptacionOpenssl_2.php – AES-256-CBC + HMAC', 'Cifrar con confidencialidad e integridad (Encrypt-then-MAC).', 'Texto, <code>first_key</code> (cifrado), <code>second_key</code> (HMAC), <code>salida</code> y <code>csrf</code>.', 'Cadena Base64 <code>iv ‖ hmac ‖ cifrado</code>; al descifrar, el texto o «HMAC inválido».', 'Cifra con <code>openssl_encrypt</code> (AES-256-CBC), calcula <code>hash_hmac(sha3-512)</code> sobre el cifrado y lo compara con <code>hash_equals</code> antes de descifrar.')}
${fn('firmaDiprena.php – firma de un trámite', 'Firmar y verificar los datos de un trámite de personal.', '<code>idCargo</code> (id del trámite), los diez campos del trámite (posición, salario, gasto de representación, ocupación, resuelto, identificación, nombres y apellidos), <code>accion</code> (firmar / verificar) y <code>csrf</code>.', 'Firma Base64 guardada para el trámite y resultado de la verificación contra ella.', 'Serializa los campos con <code>json_encode</code> (evita ambigüedad entre campos concatenados), firma con <code>openssl_sign</code> (SHA-256) y verifica con <code>openssl_verify</code>.')}
${fn('firma4.php – solicitud de certificado (CSR)', 'Generar una CSR con una llave nueva.', '<code>commonName</code> (obligatorio), <code>organizationName</code> y <code>csrf</code>.', 'CSR en PEM, su sujeto y el tamaño de la llave pública incluida.', '<code>openssl_pkey_new</code> → <code>openssl_csr_new</code> → <code>openssl_csr_export</code>; <code>openssl_csr_get_subject</code> y <code>openssl_csr_get_public_key</code> para inspeccionarla.')}
${fn('firmaDigital.php – RSA-OAEP', 'Cifrar con la llave pública y descifrar con la privada.', '<code>mensaje</code> y <code>csrf</code>.', 'Llave pública, mensaje cifrado en Base64 y el texto descifrado con la llave privada para comprobar el ciclo completo.', '<code>openssl_pkey_export_to_file</code> guarda las llaves; <code>openssl_public_encrypt</code> y <code>openssl_private_decrypt</code> con <code>OPENSSL_PKCS1_OAEP_PADDING</code>.')}

<h2>5.3 Funciones de la extensión OpenSSL</h2>
${fn('openssl_pkey_new($config)', 'Generar un par de llaves.', '<code>array $config</code>: <code>private_key_bits</code>, <code>private_key_type</code> (<code>OPENSSL_KEYTYPE_RSA</code>), <code>config</code> (ruta a openssl.cnf).', '<code>OpenSSLAsymmetricKey</code> o <code>false</code>.', 'Generación de dos primos grandes y cálculo del par RSA (módulo, exponente público y privado).')}
${fn('openssl_pkey_export($key, &$salida, $clave, $config)', 'Exportar la llave privada a PEM.', 'Llave, variable de salida por referencia, contraseña opcional y configuración.', '<code>bool</code>.', 'Codifica la llave en PKCS#8/Base64; con contraseña, cifra el PEM.')}
${fn('openssl_pkey_get_details($key)', 'Obtener datos de una llave, incluida la pública.', 'Recurso de llave.', '<code>array</code> con <code>bits</code>, <code>key</code> (PEM público) y <code>type</code>.', 'Extrae el módulo y el exponente público de la llave.')}
${fn('openssl_sign($datos, &$firma, $llavePrivada, $algoritmo)', 'Firmar datos.', 'Datos, firma (por referencia), llave privada, <code>OPENSSL_ALGO_SHA256</code>.', '<code>bool</code>; la firma queda en <code>$firma</code>.', 'Calcula SHA-256 de los datos y cifra el resumen con la llave privada RSA (PKCS#1 v1.5).')}
${fn('openssl_verify($datos, $firma, $llavePublica, $algoritmo)', 'Verificar una firma digital.', 'Datos recibidos, firma binaria, llave pública, algoritmo.', '<code>1</code> válida, <code>0</code> inválida, <code>-1</code> error.', 'Descifra la firma con la llave pública, recalcula el hash de los datos y compara ambos resúmenes.')}
${fn('openssl_encrypt($datos, $metodo, $clave, $opciones, $iv, &$tag)', 'Cifrado simétrico.', 'Texto, método (<code>AES-128-CBC</code>, <code>AES-256-GCM</code>…), clave, opciones (<code>OPENSSL_RAW_DATA</code>), IV y tag (solo GCM).', 'Texto cifrado o <code>false</code>.', 'AES con bloques de 128 bits. CBC encadena bloques con el IV; GCM usa modo contador y genera un tag de autenticación.')}
${fn('openssl_decrypt($datos, $metodo, $clave, $opciones, $iv, $tag)', 'Descifrado simétrico.', 'Mismos parámetros que <code>openssl_encrypt</code> (más el tag en GCM).', 'Texto original o <code>false</code>.', 'Operación inversa; en GCM verifica el tag antes de devolver el texto.')}
${fn('openssl_cipher_iv_length($metodo)', 'Conocer el tamaño del IV de un algoritmo.', 'Nombre del método.', '<code>int</code> (16 para AES-CBC, 12 para GCM).', 'No cifra: asegura generar un IV del tamaño correcto.')}
${fn('openssl_random_pseudo_bytes($largo)', 'Generar bytes aleatorios para IV y sales.', 'Número de bytes.', 'Cadena binaria.', 'Generador pseudoaleatorio criptográficamente seguro.')}
${fn('openssl_public_encrypt() / openssl_private_decrypt()', 'Cifrado asimétrico RSA.', 'Datos, salida por referencia, llave, relleno <code>OPENSSL_PKCS1_OAEP_PADDING</code>.', '<code>bool</code>.', 'RSA con relleno OAEP: se cifra con la pública y solo la privada descifra; el tamaño máximo depende de la llave.')}
${fn('openssl_csr_new($dn, &$llave, $config)', 'Crear una solicitud de certificado (CSR).', 'Arreglo del nombre distinguido, llave privada, configuración.', 'Recurso CSR o <code>false</code>.', 'Empaqueta el nombre y la llave pública y los firma con la llave privada (prueba de posesión).')}
${fn('openssl_csr_sign($csr, $ca, $llave, $dias, $config)', 'Firmar la CSR y emitir el certificado.', 'CSR, certificado de la CA (<code>null</code> = autofirmado), llave privada, días de validez, configuración.', 'Recurso X.509 o <code>false</code>.', 'El emisor firma el certificado con su llave privada; en un autofirmado el emisor es el propio sujeto.')}
${fn('openssl_x509_parse() / openssl_x509_fingerprint()', 'Leer los campos de un certificado y obtener su huella.', 'Certificado y, para la huella, el algoritmo (<code>sha256</code>).', '<code>array</code> con sujeto, emisor, serie y vigencia / cadena hexadecimal.', 'Decodifica la estructura X.509 y calcula el hash del certificado en formato DER.')}
${fn('openssl_error_string()', 'Leer errores de OpenSSL.', 'Ninguno.', '<code>string</code> o <code>false</code>.', 'Diagnóstico; sirvió para detectar el problema de <code>openssl.cnf</code> (sección 7).')}

<h2>5.4 Funciones de Libsodium</h2>
${fn('sodium_crypto_secretbox($mensaje, $nonce, $clave)', 'Cifrado simétrico autenticado.', 'Mensaje, nonce (24 bytes), clave (32 bytes).', 'Cifrado con MAC de 16 bytes.', 'XSalsa20 para confidencialidad y Poly1305 para integridad en una sola llamada.')}
${fn('sodium_crypto_secretbox_open($cifrado, $nonce, $clave)', 'Descifrar y verificar.', 'Cifrado, nonce, clave.', 'Mensaje o <code>false</code> si fue alterado.', 'Verifica el MAC antes de descifrar.')}
${fn('sodium_crypto_sign_keypair() / sodium_crypto_sign_detached()', 'Crear un par de llaves y firmar.', 'Mensaje y llave secreta.', 'Par de llaves / firma de 64 bytes.', 'Ed25519 (curva Edwards25519), firma determinista.')}
${fn('sodium_crypto_sign_verify_detached($firma, $mensaje, $llavePublica)', 'Verificar una firma Ed25519.', 'Firma, mensaje, llave pública.', '<code>bool</code>.', 'Verificación Ed25519.')}
${fn('sodium_crypto_pwhash_str($clave, $ops, $mem)', 'Hash seguro de contraseñas.', 'Contraseña, límites de operaciones y de memoria.', 'Cadena con algoritmo, parámetros, sal y hash.', 'Argon2id: algoritmo de memoria dura que resiste ataques con GPU.')}
${fn('sodium_crypto_pwhash_str_verify($hash, $clave)', 'Comprobar una contraseña contra su hash.', 'Hash guardado, contraseña.', '<code>bool</code>.', 'Recalcula Argon2id con los parámetros contenidos en el hash.')}
${fn('sodium_crypto_pwhash($largo, $clave, $sal, $ops, $mem, $alg)', 'Derivar una llave desde una contraseña.', 'Largo, contraseña, sal de 16 bytes, límites, algoritmo.', 'Llave binaria.', 'Argon2id con sal aleatoria.')}
${fn('sodium_crypto_box_seal() / sodium_crypto_box_seal_open()', 'Cifrado asimétrico anónimo.', 'Mensaje y llave pública (al abrir, el par de llaves).', 'Cifrado / mensaje original.', 'X25519 con llave efímera + XSalsa20-Poly1305; solo el dueño de la llave privada puede abrirlo.')}
${fn('sodium_memzero(&$variable)', 'Borrar secretos de la memoria.', 'Variable por referencia.', '<code>void</code>.', 'Sobrescribe el valor para no dejar llaves en RAM.')}

<h1 class="sec">6. Investigación y ejemplo práctico con Libsodium (Sodium)</h1>
<h2>6.1 Investigación teórica</h2>
<p><b>Libsodium</b> es una biblioteca criptográfica moderna, derivada de NaCl y mantenida por Frank Denis, que PHP incluye de forma nativa desde la versión 7.2 (<code>ext-sodium</code>). A diferencia de OpenSSL —potente pero amplia, compleja y orientada a protocolos de red y certificados— Sodium se diseñó con enfoque en el desarrollador: hacer que la criptografía moderna sea <b>segura por defecto y muy difícil de usar mal</b>. No hay que elegir modos de operación ni rellenos; la biblioteca selecciona algoritmos robustos y entrega siempre cifrado autenticado.</p>
<h3>Principales algoritmos</h3>
<table><thead><tr><th>Operación</th><th>Algoritmo</th><th>Funciones PHP</th></tr></thead>
<tr><td>Cifrado simétrico autenticado</td><td>XSalsa20-Poly1305 / XChaCha20-Poly1305</td><td><code>sodium_crypto_secretbox</code>, <code>sodium_crypto_aead_xchacha20poly1305_ietf_encrypt</code></td></tr>
<tr><td>Cifrado asimétrico</td><td>X25519 + XSalsa20-Poly1305</td><td><code>sodium_crypto_box</code>, <code>sodium_crypto_box_seal</code></td></tr>
<tr><td>Firmas digitales</td><td>Ed25519</td><td><code>sodium_crypto_sign_*</code></td></tr>
<tr><td>Hash de contraseñas</td><td>Argon2id (recomendado por OWASP)</td><td><code>sodium_crypto_pwhash_str</code></td></tr>
<tr><td>Hash genérico</td><td>BLAKE2b</td><td><code>sodium_crypto_generichash</code></td></tr></table>
<h3>Ventajas de seguridad</h3>
<ul><li><b>Resistente al mal uso:</b> gestiona nonces, aleatoriedad y autenticación, evitando errores comunes como repetir un IV o cifrar sin verificar integridad.</li>
<li><b>Argon2id</b> para contraseñas: memoria dura, resiste la fuerza bruta con GPU.</li>
<li><b>Cifrado autenticado</b> en una sola llamada (confidencialidad e integridad).</li>
<li><b>Operaciones en tiempo constante</b> y borrado de secretos (<code>sodium_memzero</code>).</li>
<li><b>Cero dependencias extra:</b> viene incluida en WampServer y XAMPP, sin archivos <code>openssl.cnf</code>.</li></ul>
<h3>Ventajas de rendimiento (medidas en este laboratorio, captura 32)</h3>
<table><thead><tr><th>Operación</th><th>OpenSSL</th><th>Sodium</th><th>Observación</th></tr></thead>
<tr><td>Generar par de llaves</td><td>RSA-2048: 58.020 ms</td><td>Ed25519: 0.064 ms</td><td>Sodium ≈ 900 veces más rápido</td></tr>
<tr><td>Firmar un mensaje</td><td>RSA-2048 + SHA-256: 0.440 ms</td><td>Ed25519: 0.079 ms</td><td>Sodium ≈ 5.6 veces más rápido</td></tr>
<tr><td>Cifrar 1 MB</td><td>AES-256-GCM: 0.201 ms</td><td>secretbox: 1.689 ms</td><td>AES-GCM de OpenSSL es más rápido porque el procesador lo acelera por hardware (AES-NI)</td></tr>
<tr><td>Tamaño de llave pública / firma</td><td>294 B / 256 B</td><td>32 B / 64 B</td><td>Ed25519 usa llaves y firmas mucho más pequeñas</td></tr></table>
<p>La conclusión es matizada: Sodium gana claramente en llaves y firmas, mientras que para cifrar grandes volúmenes con AES-GCM OpenSSL aprovecha el hardware. La ventaja principal de Sodium es la seguridad y simplicidad, no siempre la velocidad.</p>
<h3>OpenSSL frente a Sodium: ¿cuándo usar cada uno?</h3>
<table><thead><tr><th>Característica</th><th>OpenSSL</th><th>Libsodium (ext-sodium)</th></tr></thead>
<tr><td><b>Propósito principal</b></td><td>Protocolos de red, certificados digitales y criptografía asimétrica (RSA).</td><td>Criptografía moderna simétrica, firmas y derivación/hashing de alta seguridad.</td></tr>
<tr><td><b>Complejidad</b></td><td>Alta: múltiples parámetros (modos, rellenos, longitudes, IV).</td><td>Baja: funciones simples y seguras desde la primera línea.</td></tr>
<tr><td><b>Uso recomendado</b></td><td>Conexiones HTTPS, llaves públicas/privadas y certificados.</td><td>Contraseñas y cifrado de datos sensibles en la base de datos.</td></tr></table>

<h2>6.2 Ejemplo implementado (sodium.php)</h2>
<p>El script <code>sodium.php</code> integra cinco demostraciones con formulario (figuras 21 a 32): <b>(A)</b> cifrado autenticado con contraseña (Argon2id + secretbox), <b>(B)</b> firma digital Ed25519, <b>(C)</b> almacenamiento de contraseñas con Argon2id, <b>(D)</b> sealed box y <b>(E)</b> comparativa de rendimiento contra OpenSSL. Núcleo del ejemplo de cifrado autenticado:</p>
<pre>${esc(`// Derivar una llave de 32 bytes desde la contraseña (Argon2id)
$sal   = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
$clave = sodium_crypto_pwhash(
    SODIUM_CRYPTO_SECRETBOX_KEYBYTES, $contrasena, $sal,
    SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
    SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE,
    SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13
);

// Cifrar y autenticar en una sola llamada
$nonce   = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
$cifrado = sodium_crypto_secretbox($mensaje, $nonce, $clave);

// Descifrar: devuelve false si el mensaje fue alterado o la clave es incorrecta
$abierto = sodium_crypto_secretbox_open($cifrado, $nonce, $clave);
if ($abierto === false) { echo 'Mensaje alterado o clave incorrecta'; }
sodium_memzero($clave);`)}</pre>
<p><b>Resultado observado:</b> con la contraseña correcta se recupera el mensaje (figura 23); si se altera el cifrado, <code>secretbox_open</code> devuelve <code>false</code> (figura 24); la firma Ed25519 se verifica solo con el mensaje original (figuras 26 y 27); y la contraseña errónea es rechazada por Argon2id (figura 30). Así se protege la confidencialidad y la integridad, como pide OWASP.</p>

<h1 class="sec">7. Dificultades encontradas y soluciones</h1>
<table><thead><tr><th>#</th><th>Dificultad</th><th>Solución aplicada</th></tr></thead>
<tr><td>1</td><td><code>firma7.php</code> y <code>firmaOtra.php</code> originales fallaban: <code>firmaOtra</code> apunta a <code>C:\\OpenSSL-Win64\\bin\\cnf\\openssl.cnf</code>, ruta inexistente en WampServer (figuras 33 y 34).</td><td>Se aplicaron las Soluciones 1 y 2 de la guía en <code>includes/bootstrap.php</code>: <code>localizarOpensslCnf()</code> busca el archivo en <code>OPENSSL_CONF</code>, en la carpeta de PHP de WampServer, en XAMPP (cualquier unidad) y en OpenSSL independiente; si no lo halla, omite el parámetro <code>config</code>.</td></tr>
<tr><td>2</td><td><code>FirmarMensaje.php</code> original tomaba la llave pública de <code>certout.csr</code>, que es una solicitud y no un certificado.</td><td><code>firmaOtra.php</code> ahora emite <code>certout.crt</code> y <code>FirmarMensaje.php</code> extrae la llave pública de ese certificado.</td></tr>
<tr><td>3</td><td><code>DiprenaGuardarAprobacion.php</code> no se ejecuta fuera de su sistema: requiere <code>Barricada.php</code>, <code>clases/…</code> y funciones <code>mysql_*</code> (eliminadas en PHP 7).</td><td>Su parte criptográfica se reprodujo en <code>firmaDiprena.php</code> sin base de datos; el original se conserva en <code>originales/</code>.</td></tr>
<tr><td>4</td><td>Los scripts originales usaban datos fijos en el código y no tenían formularios.</td><td>Se agregaron formularios con token CSRF, escape de salida y botones para simular alteraciones.</td></tr>
<tr><td>5</td><td>Riesgo de publicar llaves privadas en GitHub.</td><td><code>.gitignore</code> excluye <code>keys/*</code> y <code>keysCert/*</code>; los <code>.htaccess</code> impiden su descarga por HTTP.</td></tr></table>
${fig('00a_error_original_firma7.png', 'Error del script original firma7 (sin openssl.cnf)', 'Con la ruta de configuración del original, OpenSSL responde <code>error:12000079:random number generator::Cannot open file</code> y no genera el par de llaves.', 'height:1.2cm;object-fit:cover;object-position:top left;width:100%')}
${fig('00b_error_original_firmaOtra.png', 'Error del script original firmaOtra (ruta de openssl.cnf)', 'El script original responde <code>error:10000080:BIO routines::no such file</code> porque la ruta fija a <code>C:\\OpenSSL-Win64</code> no existe en este equipo; con <code>localizarOpensslCnf()</code> el script funciona (figura 13).', 'height:1.2cm;object-fit:cover;object-position:top left;width:100%')}

<h1 class="sec">8. Conclusiones personales</h1>
<p>Este laboratorio me mostró que la criptografía en PHP se reduce a pocas funciones, pero un detalle mal configurado la deja sin protección: un IV repetido, una clave escrita en el código o un cifrado sin verificación de integridad. Al alterar los datos en los formularios comprobé la diferencia entre <b>cifrar</b> (confidencialidad) y <b>autenticar</b> (integridad): AES-CBC sin MAC puede descifrar basura sin avisar, mientras que AES-GCM, HMAC y <code>secretbox</code> rechazan el mensaje modificado.</p>
<p>También entendí el papel de cada pieza de la firma digital: la llave privada firma, la pública verifica y el certificado X.509 asocia esa llave a una identidad. Resolver la ausencia de <code>openssl.cnf</code> y la confusión entre CSR y certificado me enseñó a leer los errores con <code>openssl_error_string()</code> y a no depender de rutas fijas, lo que coincide con la proactividad ante errores que valora la guía.</p>
<p>Con Libsodium vi que hacer lo correcto puede ser más sencillo: menos parámetros, nonce y MAC integrados, y Argon2id para contraseñas. La medición de rendimiento me dejó una lección crítica: Sodium no siempre es más rápido (AES-256-GCM de OpenSSL ganó al cifrar 1 MB gracias al hardware), pero sí es más difícil de usar mal. Mi conclusión es usar <b>OpenSSL cuando se necesitan certificados y compatibilidad</b> y <b>Sodium para cifrar datos sensibles y guardar contraseñas</b>, aplicando los principios de A02 de OWASP y evitando publicar llaves privadas en el repositorio.</p>

<h1 class="sec primera" style="margin-top:.8cm">9. Referencias</h1>
<ol>
<li>Fong, I. (2026). <i>Guía para la entrega del laboratorio: Seguridad en PHP con OpenSSL</i>. Universidad Tecnológica de Panamá.</li>
<li>Salomon2514. <i>SeguridadOpenSSL</i> [Repositorio]. GitHub. Scripts originales del laboratorio.</li>
<li>PHP Group. <i>OpenSSL – Manual de PHP</i>. https://www.php.net/manual/es/book.openssl.php</li>
<li>PHP Group. <i>Sodium – Manual de PHP</i>. https://www.php.net/manual/es/book.sodium.php</li>
<li>Libsodium. <i>Documentación de libsodium</i>. https://doc.libsodium.org/</li>
<li>OWASP. (2021). <i>A02:2021 – Cryptographic Failures</i>. https://owasp.org/Top10/A02_2021-Cryptographic_Failures/</li>
<li>OWASP. <i>Password Storage Cheat Sheet</i>. https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html</li>
<li>OpenSSL Project. <i>OpenSSL documentation</i>. https://docs.openssl.org/</li>
</ol>
</section></body></html>`;

(async () => {
  fs.copyFileSync(path.join(ROOT, 'docs/img/logo-utp.png'), path.join(__dirname, 'logo-utp.png'));
  fs.copyFileSync(path.join(ROOT, 'docs/img/logo-fisc.png'), path.join(__dirname, 'logo-fisc.png'));
  // La portada va en su propio PDF para que la numeración del cuerpo empiece en 1 (la portada no se numera).
  const iniPortada = html.indexOf('<section class="portada">');
  const finPortada = html.indexOf('</section>', iniPortada) + '</section>'.length;
  const iniCuerpo = html.indexOf('<section class="cuerpo">');
  const finCuerpo = html.lastIndexOf('</section>') + '</section>'.length;
  const cuerpoHtml = html.slice(0, iniPortada) + html.slice(finPortada);
  const portadaHtml = html.slice(0, iniCuerpo) + html.slice(finCuerpo);
  fs.writeFileSync(path.join(__dirname, 'informe.html'), html);
  fs.writeFileSync(path.join(__dirname, '_cuerpo.html'), cuerpoHtml);
  fs.writeFileSync(path.join(__dirname, '_portada.html'), portadaHtml);
  const browser = await puppeteer.launch({ executablePath: EDGE, headless: true });
  const page = await browser.newPage();
  const imprimir = async (archivo) => {
    await page.goto('file:///' + path.join(__dirname, archivo).split(path.sep).join('/'), { waitUntil: 'load' });
    return page.pdf({ preferCSSPageSize: true, printBackground: true });
  };
  const pdfPortada = await imprimir('_portada.html');
  const pdfCuerpo = await imprimir('_cuerpo.html');
  await browser.close();
  const { PDFDocument } = require(path.join(ROOT, 'herramientas/node_modules/pdf-lib'));
  const final = await PDFDocument.create();
  for (const buf of [pdfPortada, pdfCuerpo]) {
    const doc = await PDFDocument.load(buf);
    (await final.copyPages(doc, doc.getPageIndices())).forEach((p) => final.addPage(p));
  }
  final.setTitle('Informe técnico – Seguridad en PHP con OpenSSL');
  final.setAuthor('Angel Gálvez');
  fs.writeFileSync(path.join(ROOT, 'Informe_Laboratorio_OpenSSL.pdf'), await final.save());
  fs.unlinkSync(path.join(__dirname, '_cuerpo.html'));
  fs.unlinkSync(path.join(__dirname, '_portada.html'));
  console.log('PDF generado: portada ' + (await PDFDocument.load(pdfPortada)).getPageCount() + ' pág. + cuerpo ' + (await PDFDocument.load(pdfCuerpo)).getPageCount() + ' pág.');
})();
