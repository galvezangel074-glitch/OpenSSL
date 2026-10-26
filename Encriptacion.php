<?php
/*
 * Encriptación simple con openssl_encrypt() / openssl_decrypt() — versión con formulario.
 *
 * Mejoras respecto al original (originales/Encriptacion.php):
 *  - La clave del original ("clave123456789012") tenía 17 caracteres, no 16; OpenSSL truncaba en silencio.
 *    Aquí la clave de AES se DERIVA de la contraseña con PBKDF2-SHA256 (100 000 iteraciones y sal aleatoria),
 *    así siempre tiene el tamaño exacto del algoritmo y resiste ataques de diccionario.
 *  - El IV aleatorio se guarda junto al texto cifrado; sin él no se puede descifrar más tarde (datos en reposo).
 *  - Se ofrece AES-256-GCM (cifrado autenticado), que además detecta si el texto cifrado fue alterado.
 */
require __DIR__ . '/includes/bootstrap.php';

const PBKDF2_ITERACIONES = 100000;
const LARGO_SAL = 16;
const LARGO_TAG = 16;

$algoritmos = [
    'aes-128-cbc' => ['AES-128-CBC (como el original)', 16],
    'aes-256-cbc' => ['AES-256-CBC', 32],
    'aes-256-gcm' => ['AES-256-GCM (autenticado, recomendado)', 32],
];

function derivarClave(string $contrasena, string $sal, int $largo): string
{
    return hash_pbkdf2('sha256', $contrasena, $sal, PBKDF2_ITERACIONES, $largo, true);
}

$algoritmo = post('algoritmo', 'aes-128-cbc');
if (!isset($algoritmos[$algoritmo])) {
    $algoritmo = 'aes-128-cbc';
}
$texto = post('texto', 'Mensaje secreto');
$contrasena = post('clave', 'clave1234567890');
$paquete = post('paquete');
$accion = post('accion');
$alterar = post('alterar') === '1';
$resultado = null;
$error = null;

if (esPost()) {
    if (!csrfValido()) {
        $error = 'Token CSRF inválido. Recargue la página.';
    } elseif ($contrasena === '') {
        $error = 'Debe indicar una clave.';
    } elseif ($accion === 'cifrar') {
        $largoClave = $algoritmos[$algoritmo][1];
        $sal = openssl_random_pseudo_bytes(LARGO_SAL);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($algoritmo));
        $clave = derivarClave($contrasena, $sal, $largoClave);
        $tag = '';

        $cifrado = $algoritmo === 'aes-256-gcm'
            ? openssl_encrypt($texto, $algoritmo, $clave, OPENSSL_RAW_DATA, $iv, $tag, '', LARGO_TAG)
            : openssl_encrypt($texto, $algoritmo, $clave, OPENSSL_RAW_DATA, $iv);

        if ($cifrado === false) {
            $error = 'openssl_encrypt() falló: ' . opensslErrores();
        } else {
            // Paquete para guardar en BD: sal | iv | tag (solo GCM) | texto cifrado
            $paquete = base64_encode($sal . $iv . $tag . $cifrado);
            $descifrado = $algoritmo === 'aes-256-gcm'
                ? openssl_decrypt($cifrado, $algoritmo, $clave, OPENSSL_RAW_DATA, $iv, $tag)
                : openssl_decrypt($cifrado, $algoritmo, $clave, OPENSSL_RAW_DATA, $iv);
            $resultado = [
                'tipo'       => 'cifrar',
                'sal'        => bin2hex($sal),
                'iv'         => bin2hex($iv),
                'clave'      => bin2hex($clave),
                'tag'        => bin2hex($tag),
                'cifrado'    => base64_encode($cifrado),
                'paquete'    => $paquete,
                'descifrado' => $descifrado,
            ];
        }
    } elseif ($accion === 'descifrar') {
        $datos = base64_decode(trim($paquete), true);
        $largoIv = openssl_cipher_iv_length($algoritmo);
        $largoTag = $algoritmo === 'aes-256-gcm' ? LARGO_TAG : 0;
        if ($datos === false || strlen($datos) <= LARGO_SAL + $largoIv + $largoTag) {
            $error = 'El paquete no es Base64 válido o está incompleto.';
        } else {
            if ($alterar) {
                $datos[strlen($datos) - 1] = chr(ord($datos[strlen($datos) - 1]) ^ 0x01); // simula un ataque: cambia 1 bit
            }
            $sal = substr($datos, 0, LARGO_SAL);
            $iv = substr($datos, LARGO_SAL, $largoIv);
            $tag = substr($datos, LARGO_SAL + $largoIv, $largoTag);
            $cifrado = substr($datos, LARGO_SAL + $largoIv + $largoTag);
            $clave = derivarClave($contrasena, $sal, $algoritmos[$algoritmo][1]);

            $descifrado = $algoritmo === 'aes-256-gcm'
                ? openssl_decrypt($cifrado, $algoritmo, $clave, OPENSSL_RAW_DATA, $iv, $tag)
                : openssl_decrypt($cifrado, $algoritmo, $clave, OPENSSL_RAW_DATA, $iv);
            $resultado = ['tipo' => 'descifrar', 'descifrado' => $descifrado, 'alterado' => $alterar];
        }
    }
}

$titulo = 'Encriptación simple con OpenSSL';
require __DIR__ . '/includes/header.php';
?>
<div class="tarjeta info">
    <strong>Cifrado simétrico:</strong> la misma clave cifra y descifra. Ideal para proteger datos en reposo
    (cédulas, tarjetas, registros médicos) antes de guardarlos en la base de datos.
</div>

<?php if ($error): ?><div class="alerta error"><?= h($error) ?></div><?php endif; ?>

<div class="dos-columnas">
    <form method="post" class="tarjeta">
        <h2>1. Cifrar</h2>
        <?= campoCsrf() ?>
        <input type="hidden" name="accion" value="cifrar">
        <label for="texto">Texto a cifrar</label>
        <textarea id="texto" name="texto" required><?= h($texto) ?></textarea>
        <label for="clave">Clave / contraseña</label>
        <input type="text" id="clave" name="clave" value="<?= h($contrasena) ?>" required>
        <p class="ayuda">Se deriva con PBKDF2-SHA256 al tamaño exacto del algoritmo.</p>
        <label for="algoritmo">Algoritmo</label>
        <select id="algoritmo" name="algoritmo">
            <?php foreach ($algoritmos as $valor => [$nombre]): ?>
                <option value="<?= h($valor) ?>"<?= $valor === $algoritmo ? ' selected' : '' ?>><?= h($nombre) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Cifrar</button>
    </form>

    <form method="post" class="tarjeta">
        <h2>2. Descifrar</h2>
        <?= campoCsrf() ?>
        <input type="hidden" name="accion" value="descifrar">
        <label for="paquete">Paquete cifrado (Base64)</label>
        <textarea id="paquete" name="paquete" required><?= h($paquete) ?></textarea>
        <label for="clave2">Clave / contraseña</label>
        <input type="text" id="clave2" name="clave" value="<?= h($contrasena) ?>" required>
        <label for="algoritmo2">Algoritmo</label>
        <select id="algoritmo2" name="algoritmo">
            <?php foreach ($algoritmos as $valor => [$nombre]): ?>
                <option value="<?= h($valor) ?>"<?= $valor === $algoritmo ? ' selected' : '' ?>><?= h($nombre) ?></option>
            <?php endforeach; ?>
        </select>
        <label class="check"><input type="checkbox" name="alterar" value="1"<?= $alterar ? ' checked' : '' ?>> Simular alteración (cambiar 1 bit del texto cifrado)</label>
        <button type="submit">Descifrar</button>
    </form>
</div>

<?php if ($resultado && $resultado['tipo'] === 'cifrar'): ?>
<div class="tarjeta">
    <h2>Resultado del cifrado (<?= h(strtoupper($algoritmo)) ?>)</h2>
    <table>
        <tr><th>Texto original</th><td><?= h($texto) ?></td></tr>
        <tr><th>Sal PBKDF2 (hex)</th><td class="mono"><?= h($resultado['sal']) ?></td></tr>
        <tr><th>Clave derivada (hex)</th><td class="mono"><?= h($resultado['clave']) ?> (<?= strlen($resultado['clave']) * 4 ?> bits)</td></tr>
        <tr><th>IV aleatorio (hex)</th><td class="mono"><?= h($resultado['iv']) ?></td></tr>
        <?php if ($resultado['tag'] !== ''): ?>
        <tr><th>Tag de autenticación GCM (hex)</th><td class="mono"><?= h($resultado['tag']) ?></td></tr>
        <?php endif; ?>
        <tr><th>Texto cifrado (Base64)</th><td class="mono"><?= h($resultado['cifrado']) ?></td></tr>
        <tr><th>Paquete a almacenar</th><td class="mono"><?= h($resultado['paquete']) ?></td></tr>
        <tr><th>Descifrado de comprobación</th><td><?= h($resultado['descifrado']) ?>
            <?= $resultado['descifrado'] === $texto ? '<span class="etiqueta ok">coincide</span>' : '<span class="etiqueta error">no coincide</span>' ?></td></tr>
    </table>
    <p class="ayuda">El paquete ya se copió al formulario «Descifrar». Ejecute el cifrado otra vez: el resultado cambia porque la sal y el IV son aleatorios.</p>
</div>
<?php elseif ($resultado && $resultado['tipo'] === 'descifrar'): ?>
<div class="tarjeta">
    <h2>Resultado del descifrado</h2>
    <?php if ($resultado['descifrado'] === false): ?>
        <div class="alerta error">❌ No se pudo descifrar: clave incorrecta, algoritmo distinto o datos alterados
            <?= $algoritmo === 'aes-256-gcm' ? '(GCM rechazó el tag de autenticación).' : '(error de padding).' ?></div>
    <?php else: ?>
        <div class="alerta <?= $resultado['alterado'] ? 'aviso' : 'ok' ?>">
            <?= $resultado['alterado'] ? '⚠ Se descifró, pero el contenido puede estar corrupto: CBC no detecta alteraciones de forma confiable.' : '✅ Texto descifrado correctamente.' ?>
        </div>
        <pre><?= h($resultado['descifrado']) ?></pre>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
