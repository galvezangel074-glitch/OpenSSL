<?php
require __DIR__ . '/includes/bootstrap.php';
$titulo = 'Inicio';

$cnf = localizarOpensslCnf();
$scripts = [
    ['Encriptacion.php', 'Encriptación simple', 'Cifrado simétrico AES (CBC / GCM) con openssl_encrypt() y openssl_decrypt().', 'Requerido'],
    ['firma7.php', 'firma7', 'Genera el par de llaves RSA, firma datos con la llave privada y verifica con la pública.', 'Requerido'],
    ['firmaOtra.php', 'firmaOtra', 'Genera llave privada, CSR y certificado X.509 autofirmado.', 'Requerido · ejecutar antes de FirmarMensaje'],
    ['FirmarMensaje.php', 'FirmarMensaje', 'Firma un mensaje con la llave de firmaOtra y lo verifica con el certificado.', 'Requerido'],
    ['sodium.php', 'Libsodium', 'Cifrado autenticado, firmas Ed25519, Argon2id, sealed box y rendimiento.', 'Requerido (1.6)'],
    ['encriptacionOpenssl_2.php', 'Cifrado + HMAC', 'AES-256-CBC + HMAC-SHA3-512 (Encrypt-then-MAC) con detección de alteraciones.', 'Complementario'],
    ['firmaDiprena.php', 'firmaDiprena', 'Firma de un trámite de personal (versión del flujo DIPRENA sin base de datos).', 'Complementario · usa llaves de firma7'],
    ['firma4.php', 'firma4', 'Creación de una solicitud de firma de certificado (CSR).', 'Complementario'],
    ['firmaDigital.php', 'firmaDigital', 'Exporta llaves a archivo y cifra/descifra con RSA-OAEP.', 'Complementario'],
    ['Informacion.php', 'Información', 'Estado de OpenSSL/Sodium en el servidor y phpinfo().', 'Verificación'],
];

require __DIR__ . '/includes/header.php';
?>
<div class="tarjeta info">
    <strong>Objetivo:</strong> aplicar la extensión OpenSSL y la librería Libsodium en PHP para proteger datos sensibles
    (confidencialidad e integridad) según OWASP Top 10 — <em>A02: Cryptographic Failures</em>.
</div>

<div class="tarjeta">
    <h2>Estado del entorno</h2>
    <table>
        <tr><th>OpenSSL</th><td><span class="etiqueta ok">habilitado</span> <?= h(OPENSSL_VERSION_TEXT) ?></td></tr>
        <tr><th>Sodium</th><td><?= extension_loaded('sodium')
            ? '<span class="etiqueta ok">habilitado</span> libsodium ' . h(SODIUM_LIBRARY_VERSION)
            : '<span class="etiqueta error">no habilitado</span> active extension=sodium en php.ini' ?></td></tr>
        <tr><th>openssl.cnf</th><td class="mono"><?= $cnf !== null
            ? h($cnf) . ' <span class="etiqueta ok">Solución 2: ruta detectada</span>'
            : 'no encontrado <span class="etiqueta error">Solución 1: se omite \'config\'</span>' ?></td></tr>
    </table>
</div>

<div class="rejilla">
    <?php foreach ($scripts as [$archivo, $nombre, $descripcion, $tipo]): ?>
        <div class="tarjeta">
            <h2><a href="<?= h($archivo) ?>"><?= h($nombre) ?></a></h2>
            <p><?= h($descripcion) ?></p>
            <p class="ayuda"><?= h($tipo) ?></p>
        </div>
    <?php endforeach; ?>
</div>

<div class="tarjeta">
    <h2>Archivos originales</h2>
    <p>Los scripts entregados en el repositorio de la docente se conservan sin cambios en <code>originales/</code> como referencia.
    <code>DiprenaGuardarAprobacion.php</code> no es ejecutable fuera del sistema para el que fue escrito: depende de archivos que no
    están en el repositorio (<code>Barricada.php</code>, <code>clases/…</code>) y de la extensión <code>mysql_*</code>, eliminada en PHP 7.
    Su parte criptográfica está reproducida en <a href="firmaDiprena.php">firmaDiprena</a>.</p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
