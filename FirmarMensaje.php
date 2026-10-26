<?php
/*
 * FirmarMensaje — Firmar un mensaje con la llave privada y verificarlo con el certificado público.
 * (En el repositorio original el archivo se llama FirmarMesaje.php.)
 *
 * Paso previo: ejecutar firmaOtra.php para crear keysCert/privkey.pem y keysCert/certout.crt.
 * Paso 1: el emisor firma el mensaje con su llave privada y envía mensaje + firma en Base64.
 * Paso 2: el receptor, que solo tiene el certificado público, verifica la firma.
 */
require __DIR__ . '/includes/bootstrap.php';

$rutaPrivada = DIR_KEYS_CERT . '/privkey.pem';
$rutaCertificado = DIR_KEYS_CERT . '/certout.crt';
$hayArchivos = is_file($rutaPrivada) && is_file($rutaCertificado);

$mensaje = post('mensaje', 'Este es un mensaje confidencial');
$firmaBase64 = trim(post('firma'));
$accion = post('accion');
$resultado = null;
$error = null;

if (esPost() && !csrfValido()) {
    $error = 'Token CSRF inválido. Recargue la página.';
} elseif (esPost() && !$hayArchivos) {
    $error = 'Faltan la llave o el certificado. Ejecute primero firmaOtra.';
} elseif ($accion === 'firmar') {
    // Cargar la clave privada desde el archivo generado por firmaOtra
    $privKeyResource = openssl_pkey_get_private(file_get_contents($rutaPrivada));

    if (!openssl_sign($mensaje, $firmaBinaria, $privKeyResource, OPENSSL_ALGO_SHA256)) {
        $error = 'openssl_sign() falló: ' . opensslErrores();
    } else {
        // Convertir la firma a Base64 para almacenarla o enviarla
        $firmaBase64 = base64_encode($firmaBinaria);
        $resultado = ['tipo' => 'firmar', 'bytes' => strlen($firmaBinaria)];
    }
} elseif ($accion === 'verificar') {
    // Cargar el certificado público y extraer su llave pública
    $cert = file_get_contents($rutaCertificado);
    $pubKeyResource = openssl_pkey_get_public($cert);
    $firmaBinaria = base64_decode($firmaBase64, true);

    if ($firmaBinaria === false || $firmaBinaria === '') {
        $error = 'La firma debe estar en Base64.';
    } else {
        $ok = openssl_verify($mensaje, $firmaBinaria, $pubKeyResource, OPENSSL_ALGO_SHA256);
        $resultado = ['tipo' => 'verificar', 'ok' => $ok, 'errores' => opensslErrores()];
    }
}

$infoCert = $hayArchivos ? openssl_x509_parse(file_get_contents($rutaCertificado)) : null;
$titulo = 'FirmarMensaje — Firmar y verificar con certificado';
require __DIR__ . '/includes/header.php';
?>
<?php if (!$hayArchivos): ?>
    <div class="alerta aviso">⚠ No existe keysCert/certout.crt. <a href="firmaOtra.php">Ejecute primero firmaOtra</a> para generar la llave y el certificado.</div>
<?php else: ?>
    <div class="tarjeta info">
        <strong>Certificado en uso:</strong> <?= h($infoCert['subject']['CN'] ?? '') ?> —
        <?= h($infoCert['subject']['O'] ?? '') ?> · válido hasta <?= h(date('d/m/Y', $infoCert['validTo_time_t'])) ?>
        · serie <code><?= h(abreviar($infoCert['serialNumber'], 24)) ?></code>
    </div>
<?php endif; ?>

<?php if ($error): ?><div class="alerta error"><?= h($error) ?></div><?php endif; ?>

<div class="dos-columnas">
    <form method="post" class="tarjeta">
        <h2>Paso 1: Firmar (emisor)</h2>
        <?= campoCsrf() ?>
        <input type="hidden" name="accion" value="firmar">
        <label for="mensaje">Mensaje</label>
        <textarea id="mensaje" name="mensaje" required><?= h($mensaje) ?></textarea>
        <p class="ayuda">Se firma con keysCert/privkey.pem (RSA + SHA-256).</p>
        <button type="submit">Firmar mensaje</button>
    </form>

    <form method="post" class="tarjeta">
        <h2>Paso 2: Verificar (receptor)</h2>
        <?= campoCsrf() ?>
        <input type="hidden" name="accion" value="verificar">
        <label for="mensaje2">Mensaje recibido (modifíquelo para simular una alteración)</label>
        <textarea id="mensaje2" name="mensaje" required><?= h($mensaje) ?></textarea>
        <label for="firma">Firma en Base64</label>
        <textarea id="firma" name="firma" required><?= h($firmaBase64) ?></textarea>
        <button type="submit">Verificar con certout.crt</button>
    </form>
</div>

<?php if ($resultado && $resultado['tipo'] === 'firmar'): ?>
<div class="tarjeta">
    <h2>Mensaje firmado</h2>
    <table>
        <tr><th>Mensaje</th><td><?= h($mensaje) ?></td></tr>
        <tr><th>Firma (<?= (int) $resultado['bytes'] ?> bytes) en Base64</th><td class="mono"><?= h($firmaBase64) ?></td></tr>
    </table>
    <p class="ayuda">El mensaje y la firma se copiaron al formulario del Paso 2. Verifique tal cual y luego cambie una letra del mensaje.</p>
</div>
<?php elseif ($resultado && $resultado['tipo'] === 'verificar'): ?>
<div class="tarjeta">
    <h2>Resultado de la verificación</h2>
    <?php if ($resultado['ok'] === 1): ?>
        <div class="alerta ok">✅ Firma válida. El mensaje no fue alterado.</div>
    <?php elseif ($resultado['ok'] === 0): ?>
        <div class="alerta error">❌ Firma inválida. El mensaje pudo haber sido modificado.</div>
    <?php else: ?>
        <div class="alerta error">⚠ Error al verificar la firma: <?= h($resultado['errores']) ?></div>
    <?php endif; ?>
    <table><tr><th>Mensaje verificado</th><td><?= h($mensaje) ?></td></tr></table>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
