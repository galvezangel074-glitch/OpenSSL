<?php
/*
 * Verificación de que WampServer / XAMPP tienen habilitado OpenSSL (y Sodium).
 * El original solo llamaba a phpinfo(); aquí se muestra primero un resumen y phpinfo() bajo demanda,
 * porque exponer phpinfo() en producción revela información sensible del servidor (OWASP A05).
 */
require __DIR__ . '/includes/bootstrap.php';

if (isset($_GET['phpinfo'])) {
    phpinfo(INFO_GENERAL | INFO_MODULES);
    exit;
}

$titulo = 'Información de OpenSSL y Sodium';
$cnf = localizarOpensslCnf();
$cifrados = openssl_get_cipher_methods();
$digests = openssl_get_md_methods();

require __DIR__ . '/includes/header.php';
?>
<div class="tarjeta">
    <h2>Extensiones criptográficas</h2>
    <table>
        <tr><th>Versión de PHP</th><td><?= h(PHP_VERSION) ?> (<?= h(PHP_SAPI) ?>)</td></tr>
        <tr><th>php.ini cargado</th><td class="mono"><?= h(php_ini_loaded_file() ?: 'ninguno') ?></td></tr>
        <tr><th>extension=openssl</th><td><span class="etiqueta ok">habilitada</span></td></tr>
        <tr><th>Versión OpenSSL</th><td><?= h(OPENSSL_VERSION_TEXT) ?></td></tr>
        <tr><th>openssl.cnf usado</th><td class="mono"><?= h($cnf ?? 'no encontrado (se omite el parámetro config)') ?></td></tr>
        <tr><th>Variable OPENSSL_CONF</th><td class="mono"><?= h(getenv('OPENSSL_CONF') ?: '(no definida)') ?></td></tr>
        <tr><th>Algoritmos de cifrado</th><td><?= count($cifrados) ?> disponibles —
            AES-128-CBC <?= in_array('aes-128-cbc', $cifrados, true) ? '✔' : '✘' ?>,
            AES-256-CBC <?= in_array('aes-256-cbc', $cifrados, true) ? '✔' : '✘' ?>,
            AES-256-GCM <?= in_array('aes-256-gcm', $cifrados, true) ? '✔' : '✘' ?></td></tr>
        <tr><th>Funciones hash</th><td><?= count($digests) ?> disponibles —
            SHA256 <?= in_array('sha256', $digests, true) ? '✔' : '✘' ?>,
            SHA3-512 <?= in_array('sha3-512', $digests, true) ? '✔' : '✘' ?></td></tr>
        <tr><th>extension=sodium</th><td><?= extension_loaded('sodium')
            ? '<span class="etiqueta ok">habilitada</span> libsodium ' . h(SODIUM_LIBRARY_VERSION)
            : '<span class="etiqueta error">deshabilitada</span>' ?></td></tr>
        <?php if (extension_loaded('sodium')): ?>
        <tr><th>AES-256-GCM por hardware (Sodium)</th><td><?= sodium_crypto_aead_aes256gcm_is_available() ? 'sí (AES-NI)' : 'no' ?></td></tr>
        <?php endif; ?>
    </table>
</div>

<div class="tarjeta">
    <h2>Prueba rápida de generación de llaves</h2>
    <?php
    $inicio = microtime(true);
    $llave = openssl_pkey_new(opensslConfig());
    $ms = (microtime(true) - $inicio) * 1000;
    if ($llave !== false): ?>
        <div class="alerta ok">✅ openssl_pkey_new() generó un par RSA-2048 en <?= number_format($ms, 1) ?> ms. OpenSSL está listo.</div>
    <?php else: ?>
        <div class="alerta error">❌ openssl_pkey_new() falló:<pre><?= h(opensslErrores()) ?></pre></div>
    <?php endif; ?>
</div>

<div class="tarjeta">
    <h2>phpinfo()</h2>
    <p>La salida completa de <code>phpinfo()</code> (secciones <em>openssl</em> y <em>sodium</em>) se abre aparte.</p>
    <a href="Informacion.php?phpinfo=1" target="_blank"><button type="button">Ver phpinfo()</button></a>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
