<?php
/*
 * Cifrado AES-256-CBC + HMAC-SHA3-512 (Encrypt-then-MAC) — versión con formulario.
 *
 * Corrección respecto al original: base64_decode("Irina") y base64_decode("Fong") producían
 * claves de 3 bytes (no son Base64 de una clave real). Aquí se generan claves aleatorias de
 * 32 y 64 bytes y se codifican en Base64. Además se implementa el descifrado, que verifica
 * el HMAC con hash_equals() ANTES de descifrar.
 */
require __DIR__ . '/includes/bootstrap.php';

const METODO = 'aes-256-cbc';
const LARGO_HMAC = 64; // sha3-512 en binario

$primeraClave = post('first_key', base64_encode(openssl_random_pseudo_bytes(32)));
$segundaClave = post('second_key', base64_encode(openssl_random_pseudo_bytes(64)));
$texto = post('texto', 'Hola como estas');
$salida = post('salida');
$accion = post('accion');
$alterar = post('alterar') === '1';
$resultado = null;
$error = null;

if (esPost()) {
    $first_key = base64_decode($primeraClave, true);
    $second_key = base64_decode($segundaClave, true);
    if (!csrfValido()) {
        $error = 'Token CSRF inválido. Recargue la página.';
    } elseif ($first_key === false || strlen($first_key) !== 32 || $second_key === false || strlen($second_key) < 32) {
        $error = 'La primera clave debe ser Base64 de 32 bytes y la segunda de al menos 32 bytes.';
    } elseif ($accion === 'cifrar') {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(METODO));
        $first_encrypted = openssl_encrypt($texto, METODO, $first_key, OPENSSL_RAW_DATA, $iv);
        $second_encrypted = hash_hmac('sha3-512', $first_encrypted, $second_key, true);
        $salida = base64_encode($iv . $second_encrypted . $first_encrypted);
        $resultado = [
            'tipo'    => 'cifrar',
            'iv'      => bin2hex($iv),
            'hmac'    => bin2hex($second_encrypted),
            'cifrado' => base64_encode($first_encrypted),
        ];
    } elseif ($accion === 'descifrar') {
        $mix = base64_decode(trim($salida), true);
        $largoIv = openssl_cipher_iv_length(METODO);
        if ($mix === false || strlen($mix) <= $largoIv + LARGO_HMAC) {
            $error = 'La salida no es Base64 válido o está incompleta.';
        } else {
            if ($alterar) {
                $mix[strlen($mix) - 1] = chr(ord($mix[strlen($mix) - 1]) ^ 0x01);
            }
            $iv = substr($mix, 0, $largoIv);
            $hmacRecibido = substr($mix, $largoIv, LARGO_HMAC);
            $first_encrypted = substr($mix, $largoIv + LARGO_HMAC);
            $hmacCalculado = hash_hmac('sha3-512', $first_encrypted, $second_key, true);
            $integro = hash_equals($hmacRecibido, $hmacCalculado); // comparación en tiempo constante
            $resultado = [
                'tipo'       => 'descifrar',
                'integro'    => $integro,
                'descifrado' => $integro ? openssl_decrypt($first_encrypted, METODO, $first_key, OPENSSL_RAW_DATA, $iv) : null,
            ];
        }
    }
}

$titulo = 'Cifrado AES-256-CBC + HMAC-SHA3-512';
require __DIR__ . '/includes/header.php';
?>
<div class="tarjeta info">
    <strong>Encrypt-then-MAC:</strong> AES garantiza la <em>confidencialidad</em> y el HMAC la <em>integridad</em>.
    Si un atacante modifica un solo bit, el HMAC no coincide y el mensaje se rechaza sin descifrarlo.
</div>

<?php if ($error): ?><div class="alerta error"><?= h($error) ?></div><?php endif; ?>

<form method="post" class="tarjeta">
    <h2>Claves (Base64)</h2>
    <?= campoCsrf() ?>
    <label for="first_key">first_key — clave de cifrado AES-256 (32 bytes)</label>
    <input type="text" id="first_key" name="first_key" value="<?= h($primeraClave) ?>">
    <label for="second_key">second_key — clave del HMAC (64 bytes)</label>
    <input type="text" id="second_key" name="second_key" value="<?= h($segundaClave) ?>">
    <p class="ayuda">Generadas con openssl_random_pseudo_bytes(). Recargue la página sin enviar para obtener claves nuevas.</p>

    <div class="dos-columnas">
        <div>
            <h3>1. Cifrar</h3>
            <label for="texto">Texto</label>
            <textarea id="texto" name="texto"><?= h($texto) ?></textarea>
            <button type="submit" name="accion" value="cifrar">Cifrar y firmar con HMAC</button>
        </div>
        <div>
            <h3>2. Verificar y descifrar</h3>
            <label for="salida">Salida Base64 (IV | HMAC | cifrado)</label>
            <textarea id="salida" name="salida"><?= h($salida) ?></textarea>
            <label class="check"><input type="checkbox" name="alterar" value="1"<?= $alterar ? ' checked' : '' ?>> Simular alteración (cambiar 1 bit)</label>
            <button type="submit" name="accion" value="descifrar">Verificar y descifrar</button>
        </div>
    </div>
</form>

<?php if ($resultado && $resultado['tipo'] === 'cifrar'): ?>
<div class="tarjeta">
    <h2>Resultado</h2>
    <table>
        <tr><th>IV (hex, 16 bytes)</th><td class="mono"><?= h($resultado['iv']) ?></td></tr>
        <tr><th>HMAC-SHA3-512 (hex, 64 bytes)</th><td class="mono"><?= h($resultado['hmac']) ?></td></tr>
        <tr><th>Texto cifrado (Base64)</th><td class="mono"><?= h($resultado['cifrado']) ?></td></tr>
        <tr><th>$output = base64(iv . hmac . cifrado)</th><td class="mono"><?= h($salida) ?></td></tr>
    </table>
</div>
<?php elseif ($resultado && $resultado['tipo'] === 'descifrar'): ?>
<div class="tarjeta">
    <h2>Resultado</h2>
    <?php if (!$resultado['integro']): ?>
        <div class="alerta error">❌ HMAC inválido: los datos fueron alterados o la clave HMAC es incorrecta. No se descifró nada.</div>
    <?php elseif ($resultado['descifrado'] === false): ?>
        <div class="alerta error">❌ HMAC válido pero el descifrado falló (clave AES incorrecta).</div>
    <?php else: ?>
        <div class="alerta ok">✅ HMAC válido: el mensaje es íntegro. Texto descifrado:</div>
        <pre><?= h($resultado['descifrado']) ?></pre>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
