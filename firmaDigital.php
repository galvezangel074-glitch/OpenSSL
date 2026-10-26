<?php
/*
 * firmaDigital — Exportar el par de llaves a archivos y usarlo para CIFRADO ASIMÉTRICO (RSA-OAEP).
 *
 * Correcciones respecto al original:
 *  - Ruta de openssl.cnf calculada dinámicamente (la de C:\openssl\bin no existe).
 *  - Las llaves se guardan en keys/ (protegida con .htaccess) y no en la raíz pública del sitio.
 *  - openssl_free_key() está obsoleta desde PHP 8.0: las llaves se liberan solas.
 *  - Se completa el ejemplo: cualquiera cifra con public.key y solo el dueño de private.key descifra.
 */
require __DIR__ . '/includes/bootstrap.php';

$rutaPrivada = DIR_KEYS . '/private.key';
$rutaPublica = DIR_KEYS . '/public.key';
$mensaje = post('mensaje', 'Número de tarjeta: 4111-1111-1111-1111');
$resultado = null;
$error = null;

if (esPost()) {
    if (!csrfValido()) {
        $error = 'Token CSRF inválido. Recargue la página.';
    } elseif (strlen($mensaje) > 190) {
        $error = 'RSA-2048 con OAEP solo cifra hasta 214 bytes; use un mensaje corto (máx. 190) o cifrado híbrido.';
    } else {
        $config = opensslConfig();
        $privateKey = openssl_pkey_new($config);
        if (!$privateKey) {
            $error = 'No se pudo crear la llave: ' . opensslErrores();
        } else {
            // Guardar la llave privada en private.key. No compartir este archivo con nadie
            openssl_pkey_export_to_file($privateKey, $rutaPrivada, null, $config);
            // Guardar la llave pública en public.key. Se envía a quien quiera mandarnos datos cifrados
            $a_key = openssl_pkey_get_details($privateKey);
            file_put_contents($rutaPublica, $a_key['key']);

            // Emisor: cifra con la llave pública
            openssl_public_encrypt($mensaje, $cifrado, file_get_contents($rutaPublica), OPENSSL_PKCS1_OAEP_PADDING);
            // Receptor: descifra con la llave privada
            $ok = openssl_private_decrypt($cifrado, $descifrado, file_get_contents($rutaPrivada), OPENSSL_PKCS1_OAEP_PADDING);

            $resultado = [
                'publica'    => $a_key['key'],
                'cifrado'    => base64_encode($cifrado),
                'descifrado' => $ok ? $descifrado : null,
            ];
        }
    }
}

$titulo = 'firmaDigital — Llaves en archivo y cifrado RSA';
require __DIR__ . '/includes/header.php';
?>
<div class="tarjeta info">
    <strong>Cifrado asimétrico:</strong> se cifra con la llave <em>pública</em> (que se puede compartir) y solo la
    llave <em>privada</em> puede descifrar. Protege datos en tránsito por canales inseguros.
</div>

<?php if ($error): ?><div class="alerta error"><?= h($error) ?></div><?php endif; ?>

<form method="post" class="tarjeta">
    <?= campoCsrf() ?>
    <label for="mensaje">Dato sensible a cifrar</label>
    <input type="text" id="mensaje" name="mensaje" maxlength="190" value="<?= h($mensaje) ?>" required>
    <button type="submit">Generar llaves, cifrar y descifrar</button>
</form>

<?php if ($resultado): ?>
<div class="tarjeta">
    <h2>Resultado</h2>
    <div class="alerta ok">✅ Llaves guardadas en <?= h(rutaRelativa($rutaPrivada)) ?> y <?= h(rutaRelativa($rutaPublica)) ?>.</div>
    <table>
        <tr><th>Mensaje original</th><td><?= h($mensaje) ?></td></tr>
        <tr><th>Cifrado con public.key (RSA-OAEP, Base64)</th><td class="mono"><?= h($resultado['cifrado']) ?></td></tr>
        <tr><th>Descifrado con private.key</th><td><?= h($resultado['descifrado'] ?? 'falló') ?>
            <?= $resultado['descifrado'] === $mensaje ? '<span class="etiqueta ok">coincide</span>' : '' ?></td></tr>
    </table>
    <h3>keys/public.key</h3>
    <pre><?= h($resultado['publica']) ?></pre>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
