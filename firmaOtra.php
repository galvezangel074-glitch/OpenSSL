<?php
/*
 * firmaOtra — Llave privada + CSR + certificado X.509 autofirmado (versión con formulario).
 * Debe ejecutarse ANTES de FirmarMensaje.php, que usa la llave y el certificado generados aquí.
 *
 * Correcciones respecto al original:
 *  - Ruta de openssl.cnf calculada dinámicamente (opensslConfig()) en lugar de C:\OpenSSL-Win64.
 *  - El original guardaba el CERTIFICADO con el nombre certout.csr; un .csr es una solicitud de firma.
 *    Ahora se guardan por separado: keysCert/request.csr (CSR) y keysCert/certout.crt (certificado).
 *  - Número de serie aleatorio y validación de los campos del formulario.
 */
require __DIR__ . '/includes/bootstrap.php';

$campos = [
    'countryName'            => ['País (2 letras)', 'PA'],
    'stateOrProvinceName'    => ['Provincia', 'Panamá'],
    'localityName'           => ['Localidad', 'PA-8'],
    'organizationName'       => ['Organización', 'Universidad Tecnológica de Panamá'],
    'organizationalUnitName' => ['Unidad', 'FISC - Desarrollo Web VII'],
    'commonName'             => ['Nombre común (titular)', 'Angel Galvez'],
    'emailAddress'           => ['Correo electrónico (opcional)', ''],
];

$dn = [];
foreach ($campos as $clave => [, $porDefecto]) {
    $dn[$clave] = trim(post($clave, $porDefecto));
}
$dias = (int) post('dias', '365');
$bits = (int) post('bits', '2048');
$resultado = null;
$error = null;

if (esPost()) {
    $dn['countryName'] = strtoupper($dn['countryName']);
    if (!csrfValido()) {
        $error = 'Token CSRF inválido. Recargue la página.';
    } elseif (!preg_match('//u', implode('', $dn))) {
        $error = 'Los datos deben estar codificados en UTF-8 (OpenSSL rechaza otros juegos de caracteres en el certificado).';
    } elseif (!preg_match('/^[A-Z]{2}$/', $dn['countryName'])) {
        $error = 'El país debe ser un código ISO de 2 letras (p. ej. PA).';
    } elseif ($dn['commonName'] === '') {
        $error = 'El nombre común es obligatorio.';
    } elseif ($dn['emailAddress'] !== '' && !filter_var($dn['emailAddress'], FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no es válido.';
    } elseif ($dias < 1 || $dias > 3650 || !in_array($bits, [2048, 3072, 4096], true)) {
        $error = 'Días de validez (1–3650) o tamaño de llave no válidos.';
    } else {
        $dnCertificado = array_filter($dn, fn ($valor) => $valor !== '');
        $configArgs = opensslConfig($bits);

        $resourceNewKeyPair = openssl_pkey_new($configArgs);
        if (!$resourceNewKeyPair) {
            $error = 'Error al generar la clave: ' . opensslErrores();
        } elseif (!openssl_pkey_export($resourceNewKeyPair, $privkey, null, $configArgs)) {
            $error = 'Error al exportar la clave privada: ' . opensslErrores();
        } elseif (!($csr = openssl_csr_new($dnCertificado, $resourceNewKeyPair, $configArgs))) {
            $error = 'Error al crear la CSR: ' . opensslErrores();
        } elseif (!($sscert = openssl_csr_sign($csr, null, $resourceNewKeyPair, $dias, $configArgs, random_int(1, PHP_INT_MAX)))) {
            $error = 'Error al firmar la CSR: ' . opensslErrores();
        } else {
            openssl_csr_export($csr, $csrPem);
            openssl_x509_export($sscert, $certout);

            file_put_contents(DIR_KEYS_CERT . '/privkey.pem', $privkey);
            file_put_contents(DIR_KEYS_CERT . '/request.csr', $csrPem);
            file_put_contents(DIR_KEYS_CERT . '/certout.crt', $certout);

            $resultado = [
                'csr'       => $csrPem,
                'cert'      => $certout,
                'info'      => openssl_x509_parse($sscert),
                'huella'    => openssl_x509_fingerprint($sscert, 'sha256'),
                'parOk'     => openssl_x509_check_private_key($sscert, $resourceNewKeyPair),
                'publica'   => openssl_pkey_get_details($resourceNewKeyPair)['key'],
            ];
        }
    }
}

function dnComoTexto(array $dn): string
{
    return implode(', ', array_map(fn ($k, $v) => "$k=$v", array_keys($dn), $dn));
}

$titulo = 'firmaOtra — Certificado X.509 autofirmado';
require __DIR__ . '/includes/header.php';
?>
<div class="tarjeta info">
    <strong>Certificado X.509:</strong> asocia una llave pública con la identidad de su titular. Aquí la propia llave
    privada firma el certificado (autofirmado); en producción lo firmaría una Autoridad Certificadora (CA).
</div>

<?php if ($error): ?><div class="alerta error"><?= h($error) ?></div><?php endif; ?>

<form method="post" class="tarjeta">
    <h2>Datos del titular (Distinguished Name)</h2>
    <?= campoCsrf() ?>
    <div class="dos-columnas">
        <?php foreach ($campos as $clave => [$etiqueta]): ?>
            <div>
                <label for="<?= h($clave) ?>"><?= h($etiqueta) ?> <code><?= h($clave) ?></code></label>
                <input type="<?= $clave === 'emailAddress' ? 'email' : 'text' ?>" id="<?= h($clave) ?>" name="<?= h($clave) ?>" value="<?= h($dn[$clave]) ?>"<?= $clave === 'commonName' ? ' required' : '' ?>>
            </div>
        <?php endforeach; ?>
        <div>
            <label for="dias">Días de validez</label>
            <input type="number" id="dias" name="dias" min="1" max="3650" value="<?= $dias ?>">
        </div>
        <div>
            <label for="bits">Tamaño de la llave RSA</label>
            <select id="bits" name="bits">
                <?php foreach ([2048, 3072, 4096] as $opcion): ?>
                    <option value="<?= $opcion ?>"<?= $opcion === $bits ? ' selected' : '' ?>><?= $opcion ?> bits</option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <button type="submit">Generar llave, CSR y certificado</button>
</form>

<?php if ($resultado): $info = $resultado['info']; ?>
<div class="tarjeta">
    <h2>Resultado</h2>
    <div class="alerta ok">✅ Certificado creado y firmado. Archivos guardados en keysCert/: privkey.pem, request.csr y certout.crt.
        Ahora puede usar <a href="FirmarMensaje.php">FirmarMensaje</a>.</div>
    <table>
        <tr><th>Sujeto</th><td><?= h(dnComoTexto($info['subject'])) ?></td></tr>
        <tr><th>Emisor</th><td><?= h(dnComoTexto($info['issuer'])) ?> (autofirmado)</td></tr>
        <tr><th>Número de serie</th><td class="mono"><?= h($info['serialNumber']) ?></td></tr>
        <tr><th>Válido desde</th><td><?= h(date('d/m/Y H:i:s', $info['validFrom_time_t'])) ?></td></tr>
        <tr><th>Válido hasta</th><td><?= h(date('d/m/Y H:i:s', $info['validTo_time_t'])) ?></td></tr>
        <tr><th>Algoritmo de firma</th><td><?= h($info['signatureTypeLN']) ?></td></tr>
        <tr><th>Huella SHA-256</th><td class="mono"><?= h(implode(':', str_split(strtoupper($resultado['huella']), 2))) ?></td></tr>
        <tr><th>¿La llave privada corresponde al certificado?</th><td><?= $resultado['parOk'] ? '<span class="etiqueta ok">sí</span>' : '<span class="etiqueta error">no</span>' ?></td></tr>
    </table>
    <h3>keysCert/certout.crt — certificado X.509 (PEM)</h3>
    <pre><?= h($resultado['cert']) ?></pre>
    <h3>keysCert/request.csr — solicitud de firma (CSR)</h3>
    <pre><?= h($resultado['csr']) ?></pre>
    <h3>Llave pública contenida en el certificado</h3>
    <pre><?= h($resultado['publica']) ?></pre>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
