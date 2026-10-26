<?php
/*
 * firma7 — Firma digital con un par de llaves RSA (versión con formulario).
 *
 * 1. Crea el par de llaves (pública/privada) con openssl_pkey_new() y las guarda en keys/.
 * 2. Firma los datos con la llave privada (openssl_sign, SHA-256).
 * 3. Verifica la firma con la llave pública (openssl_verify).
 *
 * Corrección: la ruta fija 'C:\OpenSSL-Win64\bin\cnf\openssl.cnf' del original no existe en
 * WampServer y provocaba "error:80000003:system library::No such process". Ahora la ruta se
 * calcula en includes/bootstrap.php (opensslConfig()).
 */
require __DIR__ . '/includes/bootstrap.php';

$rutaPrivada = DIR_KEYS . '/private_key.pem';
$rutaPublica = DIR_KEYS . '/public_key.pem';
$rutaFirma = DIR_KEYS . '/signature.dat';

$datos = post('datos', 'Este texto será firmado. Thanks for your attention :)');
$bits = (int) post('bits', '2048');
if (!in_array($bits, [2048, 3072, 4096], true)) {
    $bits = 2048;
}
$hayLlaves = is_file($rutaPrivada) && is_file($rutaPublica);
$regenerar = esPost() ? post('regenerar') === '1' : !$hayLlaves;
$accion = post('accion');
$resultado = null;
$error = null;

if (esPost() && !csrfValido()) {
    $error = 'Token CSRF inválido. Recargue la página.';
} elseif ($accion === 'firmar') {
    $pasos = [];
    if ($regenerar || !$hayLlaves) {
        $configArgs = opensslConfig($bits);
        $resourceNewKeyPair = openssl_pkey_new($configArgs);
        if (!$resourceNewKeyPair) {
            $error = 'No se pudo crear el par de llaves. Revise la ruta de openssl.cnf: ' . opensslErrores();
        } else {
            // obtengo del recurso la clave pública como string
            $details = openssl_pkey_get_details($resourceNewKeyPair);
            $publicKeyPem = $details['key'];
            // obtengo la clave privada como string (pasada por referencia)
            if (!openssl_pkey_export($resourceNewKeyPair, $privateKeyPem, null, $configArgs)) {
                $error = 'openssl_pkey_export() falló: ' . opensslErrores();
            } else {
                file_put_contents($rutaPrivada, $privateKeyPem);
                file_put_contents($rutaPublica, $publicKeyPem);
                $pasos[] = "Par de llaves RSA-{$details['bits']} generado y guardado en keys/.";
            }
        }
    } else {
        $pasos[] = 'Se reutilizó el par de llaves existente en keys/.';
    }

    if ($error === null) {
        // se lee de disco para comprobar que private_key.pem se guardó correctamente
        $privateKeyPem = file_get_contents($rutaPrivada);
        $publicKeyPem = file_get_contents($rutaPublica);
        $resourcePrivateKey = openssl_pkey_get_private($privateKeyPem); // alias moderno de openssl_get_privatekey()

        if (!openssl_sign($datos, $firma, $resourcePrivateKey, OPENSSL_ALGO_SHA256)) {
            $error = 'openssl_sign() falló: ' . opensslErrores();
        } else {
            file_put_contents($rutaFirma, $firma);
            $pasos[] = 'Firma de ' . strlen($firma) . ' bytes guardada en keys/signature.dat.';
            $verificacion = openssl_verify($datos, $firma, $publicKeyPem, 'sha256WithRSAEncryption');
            $detalles = openssl_pkey_get_details(openssl_pkey_get_public($publicKeyPem));
            $resultado = [
                'tipo'         => 'firmar',
                'pasos'        => $pasos,
                'publica'      => $publicKeyPem,
                'privada'      => $privateKeyPem,
                'bits'         => $detalles['bits'],
                'firma'        => base64_encode($firma),
                'hash'         => hash('sha256', $datos),
                'verificacion' => $verificacion,
            ];
        }
    }
} elseif ($accion === 'verificar') {
    $firma = base64_decode(trim(post('firma')), true);
    if (!$hayLlaves) {
        $error = 'Primero firme algo para generar las llaves.';
    } elseif ($firma === false || $firma === '') {
        $error = 'La firma debe estar en Base64.';
    } else {
        $verificacion = openssl_verify($datos, $firma, file_get_contents($rutaPublica), OPENSSL_ALGO_SHA256);
        $resultado = ['tipo' => 'verificar', 'verificacion' => $verificacion, 'errores' => opensslErrores()];
    }
}

$firmaGuardada = is_file($rutaFirma) ? base64_encode((string) file_get_contents($rutaFirma)) : '';
$titulo = 'firma7 — Firma digital con par de llaves RSA';
require __DIR__ . '/includes/header.php';
?>
<div class="tarjeta info">
    <strong>Integridad y autenticidad:</strong> solo quien posee la llave privada puede firmar; cualquiera con la llave
    pública puede verificar que los datos no fueron alterados.
</div>

<?php if ($error): ?><div class="alerta error"><?= h($error) ?></div><?php endif; ?>

<div class="dos-columnas">
    <form method="post" class="tarjeta">
        <h2>1. Generar llaves y firmar</h2>
        <?= campoCsrf() ?>
        <input type="hidden" name="accion" value="firmar">
        <label for="datos">Datos a firmar</label>
        <textarea id="datos" name="datos" required><?= h($datos) ?></textarea>
        <label for="bits">Tamaño de la llave RSA</label>
        <select id="bits" name="bits">
            <?php foreach ([2048, 3072, 4096] as $opcion): ?>
                <option value="<?= $opcion ?>"<?= $opcion === $bits ? ' selected' : '' ?>><?= $opcion ?> bits</option>
            <?php endforeach; ?>
        </select>
        <label class="check"><input type="checkbox" name="regenerar" value="1"<?= $regenerar ? ' checked' : '' ?>> Generar un par de llaves nuevo</label>
        <button type="submit">Firmar</button>
    </form>

    <form method="post" class="tarjeta">
        <h2>2. Verificar firma</h2>
        <?= campoCsrf() ?>
        <input type="hidden" name="accion" value="verificar">
        <label for="datos2">Datos recibidos (modifíquelos para simular una alteración)</label>
        <textarea id="datos2" name="datos" required><?= h($datos) ?></textarea>
        <label for="firma">Firma (Base64)</label>
        <textarea id="firma" name="firma" required><?= h($resultado['firma'] ?? post('firma', $firmaGuardada)) ?></textarea>
        <button type="submit">Verificar con public_key.pem</button>
    </form>
</div>

<?php if ($resultado && $resultado['tipo'] === 'firmar'): ?>
<div class="tarjeta">
    <h2>Resultado</h2>
    <?php foreach ($resultado['pasos'] as $paso): ?><div class="ayuda">✔ <?= h($paso) ?></div><?php endforeach; ?>
    <?php if ($resultado['verificacion'] === 1): ?>
        <div class="alerta ok">✅ la firma es válida y los datos son confiables</div>
    <?php else: ?>
        <div class="alerta error">❌ la firma es inválida y/o los datos fueron alterados</div>
    <?php endif; ?>
    <table>
        <tr><th>Datos firmados</th><td><?= h($datos) ?></td></tr>
        <tr><th>SHA-256 de los datos</th><td class="mono"><?= h($resultado['hash']) ?></td></tr>
        <tr><th>Algoritmo</th><td>RSA-<?= (int) $resultado['bits'] ?> con SHA-256 (sha256WithRSAEncryption, PKCS#1 v1.5)</td></tr>
        <tr><th>Firma (Base64)</th><td class="mono"><?= h($resultado['firma']) ?></td></tr>
    </table>
    <h3>keys/public_key.pem</h3>
    <pre><?= h($resultado['publica']) ?></pre>
    <h3>keys/private_key.pem <span class="etiqueta error">secreta — protegida con .htaccess</span></h3>
    <pre><?= h($resultado['privada']) ?></pre>
</div>
<?php elseif ($resultado && $resultado['tipo'] === 'verificar'): ?>
<div class="tarjeta">
    <h2>Resultado de la verificación</h2>
    <?php if ($resultado['verificacion'] === 1): ?>
        <div class="alerta ok">✅ la firma es válida y los datos son confiables</div>
    <?php elseif ($resultado['verificacion'] === 0): ?>
        <div class="alerta error">❌ la firma es inválida y/o los datos fueron alterados</div>
    <?php else: ?>
        <div class="alerta error">⚠ Error al verificar: <?= h($resultado['errores']) ?></div>
    <?php endif; ?>
    <table><tr><th>Datos verificados</th><td><?= h($datos) ?></td></tr></table>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
