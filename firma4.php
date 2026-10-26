<?php
/*
 * firma4 — Crear una solicitud de firma de certificado (CSR).
 *
 * El original fallaba en PHP 8 por dos motivos:
 *  - openssl_csr_new('MyCRS', ...) recibía un string; el primer parámetro debe ser un arreglo (Distinguished Name).
 *  - La ruta 'C:\openssl\bin\cnf\openssl.cnf' no existe en WampServer.
 */
require __DIR__ . '/includes/bootstrap.php';

$commonName = trim(post('commonName', 'MyCRS'));
$organizationName = trim(post('organizationName', 'Universidad Tecnológica de Panamá'));
$resultado = null;
$error = null;

if (esPost()) {
    if (!csrfValido()) {
        $error = 'Token CSRF inválido. Recargue la página.';
    } elseif ($commonName === '') {
        $error = 'El nombre común es obligatorio.';
    } else {
        $config = opensslConfig();
        $pkey = openssl_pkey_new($config);
        $dn = array_filter(['commonName' => $commonName, 'organizationName' => $organizationName]);
        $csr = $pkey ? openssl_csr_new($dn, $pkey, $config) : false;
        if (!$csr) {
            $error = 'No se pudo crear la CSR: ' . opensslErrores();
        } else {
            openssl_csr_export($csr, $csrPem);
            $resultado = [
                'csr'    => $csrPem,
                'sujeto' => openssl_csr_get_subject($csr),
                'bits'   => openssl_pkey_get_details(openssl_csr_get_public_key($csr))['bits'],
            ];
        }
    }
}

$titulo = 'firma4 — Solicitud de firma de certificado (CSR)';
require __DIR__ . '/includes/header.php';
?>
<div class="tarjeta info">
    Una <strong>CSR</strong> contiene la llave pública y los datos del solicitante, firmados con su llave privada.
    Se envía a una Autoridad Certificadora para que emita el certificado.
</div>

<?php if ($error): ?><div class="alerta error"><?= h($error) ?></div><?php endif; ?>

<form method="post" class="tarjeta">
    <?= campoCsrf() ?>
    <div class="dos-columnas">
        <div>
            <label for="commonName">Nombre común (commonName)</label>
            <input type="text" id="commonName" name="commonName" value="<?= h($commonName) ?>" required>
        </div>
        <div>
            <label for="organizationName">Organización (organizationName)</label>
            <input type="text" id="organizationName" name="organizationName" value="<?= h($organizationName) ?>">
        </div>
    </div>
    <button type="submit">Crear CSR</button>
</form>

<?php if ($resultado): ?>
<div class="tarjeta">
    <h2>CSR generada</h2>
    <div class="alerta ok">✅ openssl_csr_new() creó la solicitud con una llave RSA-<?= (int) $resultado['bits'] ?>.</div>
    <table>
        <?php foreach ($resultado['sujeto'] as $campo => $valor): ?>
            <tr><th><?= h($campo) ?></th><td><?= h(is_array($valor) ? implode(', ', $valor) : $valor) ?></td></tr>
        <?php endforeach; ?>
    </table>
    <pre><?= h($resultado['csr']) ?></pre>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
