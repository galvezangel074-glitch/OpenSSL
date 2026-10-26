<?php
/*
 * Ejemplo práctico con Libsodium (ext-sodium, incluida en PHP desde 7.2).
 *
 *  A. Cifrado autenticado con contraseña: Argon2id (sodium_crypto_pwhash) + XSalsa20-Poly1305 (sodium_crypto_secretbox).
 *  B. Firma digital Ed25519 (sodium_crypto_sign_detached / sodium_crypto_sign_verify_detached).
 *  C. Hash de contraseñas Argon2id (sodium_crypto_pwhash_str / sodium_crypto_pwhash_str_verify).
 *  D. Cifrado asimétrico anónimo X25519 (sodium_crypto_box_seal / sodium_crypto_box_seal_open).
 *  E. Comparación de rendimiento Sodium vs. OpenSSL.
 */
require __DIR__ . '/includes/bootstrap.php';

if (!extension_loaded('sodium')) {
    exit('La extensión sodium no está habilitada. Active extension=sodium en php.ini y reinicie Apache.');
}

$seccion = post('seccion');
$accion = post('accion');
$alterar = post('alterar') === '1';
$res = [];
$error = null;

if (esPost() && !csrfValido()) {
    $error = 'Token CSRF inválido. Recargue la página.';
    $seccion = '';
}

/** Deriva una clave de 32 bytes desde una contraseña con Argon2id. */
function claveDesdeContrasena(string $contrasena, string $sal): string
{
    return sodium_crypto_pwhash(
        SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
        $contrasena,
        $sal,
        SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
        SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE,
        SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13
    );
}

/* ---------- A. Cifrado autenticado (secretbox) ---------- */
$aTexto = post('a_texto', 'Expediente médico #4471: diagnóstico confidencial');
$aContrasena = post('a_contrasena', 'MiFrase$egura2026');
$aPaquete = post('a_paquete');

if ($seccion === 'secretbox' && $aContrasena !== '') {
    if ($accion === 'cifrar') {
        $sal = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $clave = claveDesdeContrasena($aContrasena, $sal);
        $cifrado = sodium_crypto_secretbox($aTexto, $nonce, $clave);
        $res['secretbox'] = [
            'sal'     => bin2hex($sal),
            'nonce'   => bin2hex($nonce),
            'clave'   => bin2hex($clave),
            'bytes'   => strlen($cifrado),
            'texto'   => strlen($aTexto),
        ];
        $aPaquete = sodium_bin2base64($sal . $nonce . $cifrado, SODIUM_BASE64_VARIANT_ORIGINAL);
        sodium_memzero($clave); // borra la clave de la memoria
    } elseif ($accion === 'descifrar') {
        try {
            $datos = sodium_base642bin(trim($aPaquete), SODIUM_BASE64_VARIANT_ORIGINAL);
        } catch (SodiumException) {
            $datos = '';
        }
        $minimo = SODIUM_CRYPTO_PWHASH_SALTBYTES + SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES;
        if (strlen($datos) < $minimo) {
            $error = 'El paquete no es Base64 válido o está incompleto.';
        } else {
            if ($alterar) {
                $datos[strlen($datos) - 1] = chr(ord($datos[strlen($datos) - 1]) ^ 0x01);
            }
            $sal = substr($datos, 0, SODIUM_CRYPTO_PWHASH_SALTBYTES);
            $nonce = substr($datos, SODIUM_CRYPTO_PWHASH_SALTBYTES, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $cifrado = substr($datos, SODIUM_CRYPTO_PWHASH_SALTBYTES + SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $clave = claveDesdeContrasena($aContrasena, $sal);
            $res['secretbox_abrir'] = sodium_crypto_secretbox_open($cifrado, $nonce, $clave); // false si fue alterado
            sodium_memzero($clave);
        }
    }
}

/* ---------- B. Firma digital Ed25519 ---------- */
$bMensaje = post('b_mensaje', 'Transferencia autorizada: B/. 1,500.00 a la cuenta 04-123-456');
$bFirma = trim(post('b_firma'));
$bPublica = trim(post('b_publica'));

if ($seccion === 'firma') {
    if ($accion === 'firmar') {
        $parLlaves = sodium_crypto_sign_keypair();
        $secreta = sodium_crypto_sign_secretkey($parLlaves);
        $publica = sodium_crypto_sign_publickey($parLlaves);
        $firma = sodium_crypto_sign_detached($bMensaje, $secreta);
        $bFirma = base64_encode($firma);
        $bPublica = base64_encode($publica);
        $res['firma'] = ['secreta' => strlen($secreta), 'publica' => strlen($publica), 'firma' => strlen($firma)];
        sodium_memzero($secreta);
        sodium_memzero($parLlaves);
    } elseif ($accion === 'verificar') {
        $firma = base64_decode($bFirma, true);
        $publica = base64_decode($bPublica, true);
        if ($firma === false || strlen($firma) !== SODIUM_CRYPTO_SIGN_BYTES || $publica === false || strlen($publica) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            $error = 'La firma debe ser Base64 de 64 bytes y la llave pública Base64 de 32 bytes.';
        } else {
            $res['firma_verificar'] = sodium_crypto_sign_verify_detached($firma, $bMensaje, $publica);
        }
    }
}

/* ---------- C. Hash de contraseñas Argon2id ---------- */
$cContrasena = post('c_contrasena', 'Contraseña-Del-Usuario-2026');
$cIntento = post('c_intento', 'Contraseña-Del-Usuario-2026');
$cHash = trim(post('c_hash'));

if ($seccion === 'pwhash') {
    if ($accion === 'hash') {
        $inicio = microtime(true);
        $cHash = sodium_crypto_pwhash_str(
            $cContrasena,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );
        $res['pwhash'] = ['ms' => (microtime(true) - $inicio) * 1000, 'mismo' => sodium_crypto_pwhash_str($cContrasena, SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE, SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE)];
    } elseif ($accion === 'verificar') {
        if (!str_starts_with($cHash, '$argon2id$')) {
            $error = 'Genere primero un hash Argon2id.';
        } else {
            $res['pwhash_verificar'] = sodium_crypto_pwhash_str_verify($cHash, $cIntento);
        }
    }
}

/* ---------- D. Sealed box (cifrado con llave pública X25519) ---------- */
$dMensaje = post('d_mensaje', 'Cédula: 8-888-8888 · Tarjeta: 4111 1111 1111 1111');
if ($seccion === 'sealed') {
    $parReceptor = sodium_crypto_box_keypair();
    $publicaReceptor = sodium_crypto_box_publickey($parReceptor);
    $sellado = sodium_crypto_box_seal($dMensaje, $publicaReceptor);       // cualquiera puede cifrar
    $abierto = sodium_crypto_box_seal_open($sellado, $parReceptor);        // solo el receptor descifra
    $res['sealed'] = [
        'publica' => base64_encode($publicaReceptor),
        'sellado' => base64_encode($sellado),
        'abierto' => $abierto,
    ];
    sodium_memzero($parReceptor);
}

/* ---------- E. Rendimiento ---------- */
if ($seccion === 'rendimiento') {
    $medir = function (callable $fn, int $veces): float {
        $inicio = hrtime(true);
        for ($i = 0; $i < $veces; $i++) {
            $fn();
        }
        return (hrtime(true) - $inicio) / 1e6 / $veces; // ms por operación
    };
    $datos = random_bytes(1024 * 1024); // 1 MB
    $claveSim = random_bytes(32);
    $nonceX = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
    $nonceS = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $iv12 = random_bytes(12);
    $iv16 = random_bytes(16);

    $rsa = openssl_pkey_new(opensslConfig());
    $ed = sodium_crypto_sign_keypair();
    $edSecreta = sodium_crypto_sign_secretkey($ed);
    $mensajeFirma = 'Documento a firmar para medir rendimiento';

    $res['rendimiento'] = [
        'Cifrado simétrico de 1 MB' => [
            'OpenSSL AES-256-GCM'                => $medir(fn () => openssl_encrypt($datos, 'aes-256-gcm', $claveSim, OPENSSL_RAW_DATA, $iv12, $tag), 20),
            'OpenSSL AES-256-CBC + HMAC-SHA256'  => $medir(fn () => hash_hmac('sha256', openssl_encrypt($datos, 'aes-256-cbc', $claveSim, OPENSSL_RAW_DATA, $iv16), $claveSim, true), 20),
            'Sodium XSalsa20-Poly1305 (secretbox)' => $medir(fn () => sodium_crypto_secretbox($datos, $nonceS, $claveSim), 20),
            'Sodium XChaCha20-Poly1305 (AEAD)'   => $medir(fn () => sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($datos, '', $nonceX, $claveSim), 20),
        ],
        'Generación de par de llaves' => [
            'OpenSSL RSA-2048'  => $medir(fn () => openssl_pkey_new(opensslConfig()), 5),
            'Sodium Ed25519'    => $medir(fn () => sodium_crypto_sign_keypair(), 200),
        ],
        'Firma de un mensaje' => [
            'OpenSSL RSA-2048 + SHA-256' => $medir(fn () => openssl_sign($mensajeFirma, $f, $rsa, OPENSSL_ALGO_SHA256), 200),
            'Sodium Ed25519'             => $medir(fn () => sodium_crypto_sign_detached($mensajeFirma, $edSecreta), 200),
        ],
    ];
    $res['tamanos'] = [
        'Llave pública' => ['RSA-2048: 294 bytes (DER)', 'Ed25519: ' . SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES . ' bytes'],
        'Firma'         => ['RSA-2048: 256 bytes', 'Ed25519: ' . SODIUM_CRYPTO_SIGN_BYTES . ' bytes'],
    ];
    sodium_memzero($edSecreta);
}

$titulo = 'Libsodium (ext-sodium) — Criptografía moderna';
require __DIR__ . '/includes/header.php';
?>
<div class="tarjeta info">
    <strong>Libsodium</strong> <?= h(SODIUM_LIBRARY_VERSION) ?> está integrada en PHP desde la versión 7.2. Sus funciones eligen
    por defecto algoritmos modernos (XSalsa20-Poly1305, Ed25519, X25519, Argon2id) y no exponen parámetros peligrosos
    como modos de cifrado o paddings: es <em>difícil de usar mal</em>.
</div>

<?php if ($error): ?><div class="alerta error"><?= h($error) ?></div><?php endif; ?>

<!-- A -->
<div class="tarjeta" id="secretbox">
    <h2>A. Cifrado autenticado con contraseña — Argon2id + sodium_crypto_secretbox()</h2>
    <form method="post" action="#secretbox" class="dos-columnas">
        <div>
            <?= campoCsrf() ?>
            <input type="hidden" name="seccion" value="secretbox">
            <label for="a_texto">Dato sensible</label>
            <textarea id="a_texto" name="a_texto"><?= h($aTexto) ?></textarea>
            <label for="a_contrasena">Contraseña</label>
            <input type="text" id="a_contrasena" name="a_contrasena" value="<?= h($aContrasena) ?>" required>
            <button type="submit" name="accion" value="cifrar">Cifrar</button>
        </div>
        <div>
            <label for="a_paquete">Paquete Base64 (sal | nonce | MAC + cifrado)</label>
            <textarea id="a_paquete" name="a_paquete"><?= h($aPaquete) ?></textarea>
            <label class="check"><input type="checkbox" name="alterar" value="1"<?= $alterar && $seccion === 'secretbox' ? ' checked' : '' ?>> Simular alteración (cambiar 1 bit)</label>
            <button type="submit" name="accion" value="descifrar">Descifrar</button>
        </div>
    </form>
    <?php if (isset($res['secretbox'])): $r = $res['secretbox']; ?>
        <div class="alerta ok">✅ Cifrado en una sola llamada. El texto de <?= $r['texto'] ?> bytes ocupa <?= $r['bytes'] ?> bytes: se añadieron <?= SODIUM_CRYPTO_SECRETBOX_MACBYTES ?> bytes del MAC Poly1305.</div>
        <table>
            <tr><th>Sal Argon2id (hex)</th><td class="mono"><?= h($r['sal']) ?></td></tr>
            <tr><th>Clave derivada (hex, 256 bits)</th><td class="mono"><?= h($r['clave']) ?></td></tr>
            <tr><th>Nonce aleatorio (hex, 192 bits)</th><td class="mono"><?= h($r['nonce']) ?></td></tr>
            <tr><th>Paquete a almacenar</th><td class="mono"><?= h($aPaquete) ?></td></tr>
        </table>
    <?php elseif (array_key_exists('secretbox_abrir', $res)): ?>
        <?php if ($res['secretbox_abrir'] === false): ?>
            <div class="alerta error">❌ sodium_crypto_secretbox_open() devolvió false: contraseña incorrecta o datos alterados. El MAC Poly1305 detectó el problema y no se entregó ningún dato.</div>
        <?php else: ?>
            <div class="alerta ok">✅ Autenticado y descifrado:</div>
            <pre><?= h($res['secretbox_abrir']) ?></pre>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- B -->
<div class="tarjeta" id="firma">
    <h2>B. Firma digital Ed25519 — sodium_crypto_sign_detached()</h2>
    <form method="post" action="#firma">
        <?= campoCsrf() ?>
        <input type="hidden" name="seccion" value="firma">
        <label for="b_mensaje">Mensaje (modifíquelo después de firmar para simular una alteración)</label>
        <textarea id="b_mensaje" name="b_mensaje"><?= h($bMensaje) ?></textarea>
        <div class="dos-columnas">
            <div>
                <label for="b_publica">Llave pública Ed25519 (Base64)</label>
                <input type="text" id="b_publica" name="b_publica" value="<?= h($bPublica) ?>">
            </div>
            <div>
                <label for="b_firma">Firma (Base64)</label>
                <input type="text" id="b_firma" name="b_firma" value="<?= h($bFirma) ?>">
            </div>
        </div>
        <button type="submit" name="accion" value="firmar">Generar llaves y firmar</button>
        <button type="submit" name="accion" value="verificar" class="secundario">Verificar firma</button>
    </form>
    <?php if (isset($res['firma'])): $r = $res['firma']; ?>
        <div class="alerta ok">✅ Firmado. Llave secreta de <?= $r['secreta'] ?> bytes (borrada de memoria con sodium_memzero), llave pública de <?= $r['publica'] ?> bytes, firma de <?= $r['firma'] ?> bytes.</div>
    <?php elseif (isset($res['firma_verificar'])): ?>
        <?= $res['firma_verificar']
            ? '<div class="alerta ok">✅ Firma Ed25519 válida: el mensaje es auténtico e íntegro.</div>'
            : '<div class="alerta error">❌ Firma inválida: el mensaje fue alterado o la llave pública no corresponde.</div>' ?>
    <?php endif; ?>
</div>

<!-- C -->
<div class="tarjeta" id="pwhash">
    <h2>C. Almacenamiento de contraseñas — Argon2id (sodium_crypto_pwhash_str)</h2>
    <form method="post" action="#pwhash">
        <?= campoCsrf() ?>
        <input type="hidden" name="seccion" value="pwhash">
        <div class="dos-columnas">
            <div>
                <label for="c_contrasena">Contraseña al registrarse</label>
                <input type="text" id="c_contrasena" name="c_contrasena" value="<?= h($cContrasena) ?>">
                <button type="submit" name="accion" value="hash">Generar hash</button>
            </div>
            <div>
                <label for="c_intento">Contraseña al iniciar sesión</label>
                <input type="text" id="c_intento" name="c_intento" value="<?= h($cIntento) ?>">
                <button type="submit" name="accion" value="verificar" class="secundario">Verificar</button>
            </div>
        </div>
        <label for="c_hash">Hash almacenado en la base de datos</label>
        <input type="text" id="c_hash" name="c_hash" value="<?= h($cHash) ?>">
    </form>
    <?php if (isset($res['pwhash'])): ?>
        <div class="alerta ok">✅ Hash generado en <?= number_format($res['pwhash']['ms'], 0) ?> ms usando 64 MiB de memoria: un costo deliberado que frena los ataques de fuerza bruta con GPU.</div>
        <table>
            <tr><th>Hash 1</th><td class="mono"><?= h($cHash) ?></td></tr>
            <tr><th>Hash 2 (misma contraseña)</th><td class="mono"><?= h($res['pwhash']['mismo']) ?></td></tr>
        </table>
        <p class="ayuda">Los hashes son distintos porque cada uno incluye una sal aleatoria; el formato guarda algoritmo, versión, memoria (m), iteraciones (t) y paralelismo (p).</p>
    <?php elseif (isset($res['pwhash_verificar'])): ?>
        <?= $res['pwhash_verificar']
            ? '<div class="alerta ok">✅ Contraseña correcta.</div>'
            : '<div class="alerta error">❌ Contraseña incorrecta.</div>' ?>
    <?php endif; ?>
</div>

<!-- D -->
<div class="tarjeta" id="sealed">
    <h2>D. Cifrado asimétrico anónimo — sodium_crypto_box_seal() (X25519)</h2>
    <form method="post" action="#sealed">
        <?= campoCsrf() ?>
        <input type="hidden" name="seccion" value="sealed">
        <label for="d_mensaje">Dato que el emisor envía al receptor</label>
        <input type="text" id="d_mensaje" name="d_mensaje" value="<?= h($dMensaje) ?>">
        <button type="submit">Sellar y abrir</button>
    </form>
    <?php if (isset($res['sealed'])): $r = $res['sealed']; ?>
        <table>
            <tr><th>Llave pública del receptor</th><td class="mono"><?= h($r['publica']) ?></td></tr>
            <tr><th>Mensaje sellado (Base64)</th><td class="mono"><?= h($r['sellado']) ?></td></tr>
            <tr><th>Abierto por el receptor</th><td><?= h($r['abierto'] === false ? 'falló' : $r['abierto']) ?>
                <?= $r['abierto'] === $dMensaje ? '<span class="etiqueta ok">coincide</span>' : '' ?></td></tr>
        </table>
        <p class="ayuda">A diferencia de RSA-OAEP (firmaDigital), no hay límite práctico de tamaño ni parámetros de padding que elegir.</p>
    <?php endif; ?>
</div>

<!-- E -->
<div class="tarjeta" id="rendimiento">
    <h2>E. Rendimiento: Sodium vs. OpenSSL</h2>
    <form method="post" action="#rendimiento">
        <?= campoCsrf() ?>
        <input type="hidden" name="seccion" value="rendimiento">
        <button type="submit">Ejecutar medición</button>
        <p class="ayuda">Tiempo promedio por operación en este servidor (PHP <?= h(PHP_VERSION) ?>). Tarda unos segundos.</p>
    </form>
    <?php if (isset($res['rendimiento'])): ?>
        <?php foreach ($res['rendimiento'] as $prueba => $filas): $minimo = min($filas); ?>
            <h3><?= h($prueba) ?></h3>
            <table>
                <?php foreach ($filas as $nombre => $ms): ?>
                    <tr><th><?= h($nombre) ?></th><td><?= number_format($ms, 3) ?> ms
                        <?= $ms === $minimo ? '<span class="etiqueta ok">más rápido</span>' : '(' . number_format($ms / $minimo, 1) . '× más lento)' ?></td></tr>
                <?php endforeach; ?>
            </table>
        <?php endforeach; ?>
        <h3>Tamaños</h3>
        <table>
            <?php foreach ($res['tamanos'] as $nombre => [$rsa, $ed]): ?>
                <tr><th><?= h($nombre) ?></th><td><?= h($rsa) ?> · <?= h($ed) ?></td></tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
