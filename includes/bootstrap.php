<?php
/**
 * Configuración común del laboratorio "Seguridad en PHP con OpenSSL".
 *
 * - Verifica que las extensiones openssl y sodium estén cargadas.
 * - Localiza el archivo openssl.cnf (Soluciones 1 y 2 de la guía, adaptadas a WampServer y XAMPP).
 * - Ofrece funciones auxiliares de salida segura (anti-XSS) y token CSRF para los formularios.
 */
declare(strict_types=1);

if (!extension_loaded('openssl')) {
    http_response_code(500);
    exit('La extensión OpenSSL no está habilitada. Quite el ";" de la línea extension=openssl en php.ini y reinicie Apache.');
}

session_start();

define('LAB_ROOT', dirname(__DIR__));
define('DIR_KEYS', LAB_ROOT . '/keys');
define('DIR_KEYS_CERT', LAB_ROOT . '/keysCert');

/**
 * Busca openssl.cnf en las ubicaciones habituales de Windows.
 * Devuelve null si no lo encuentra; en ese caso se aplica la Solución 1 (omitir 'config').
 */
function localizarOpensslCnf(): ?string
{
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $candidatas = [
        getenv('OPENSSL_CONF') ?: null,
        // WampServer: cada versión de PHP trae su propio openssl.cnf
        'C:/wamp64/bin/php/php' . PHP_VERSION . '/extras/ssl/openssl.cnf',
        $docRoot !== '' ? dirname($docRoot) . '/bin/php/php' . PHP_VERSION . '/extras/ssl/openssl.cnf' : null,
        // PHP ejecutado por consola: carpeta del ejecutable php.exe
        PHP_BINARY !== '' ? dirname(PHP_BINARY) . '/extras/ssl/openssl.cnf' : null,
        // XAMPP (Solución 2 de la guía), incluso si se instaló en otra unidad
        'C:/xampp/php/extras/ssl/openssl.cnf',
        $docRoot !== '' ? dirname($docRoot) . '/php/extras/ssl/openssl.cnf' : null,
        // OpenSSL instalado por separado (slproweb.com)
        'C:/Program Files/OpenSSL-Win64/bin/cnf/openssl.cnf',
        'C:/OpenSSL-Win64/bin/cnf/openssl.cnf',
    ];
    foreach (array_filter($candidatas) as $ruta) {
        if (is_file($ruta)) {
            return $ruta;
        }
    }
    return null;
}

/**
 * Arreglo de configuración para openssl_pkey_new(), openssl_csr_new(), openssl_csr_sign(), etc.
 */
function opensslConfig(int $bits = 2048): array
{
    $config = [
        'digest_alg'       => 'sha256',
        'private_key_bits' => $bits,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ];
    $cnf = localizarOpensslCnf();
    if ($cnf !== null) {
        $config['config'] = $cnf; // Solución 2: ruta calculada dinámicamente
    }
    return $config;
}

/** Vacía la cola de errores de OpenSSL y la devuelve como texto. */
function opensslErrores(): string
{
    $errores = [];
    while (($msg = openssl_error_string()) !== false) {
        $errores[] = $msg;
    }
    return implode("\n", array_unique($errores));
}

/** Escapa texto para HTML (prevención de XSS, OWASP A03). */
function h(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

/** Lee un campo POST como texto, sin modificarlo (las firmas dependen del contenido exacto). */
function post(string $campo, string $porDefecto = ''): string
{
    return isset($_POST[$campo]) && is_string($_POST[$campo]) ? $_POST[$campo] : $porDefecto;
}

function esPost(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function campoCsrf(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrfToken()) . '">';
}

/** Comprueba el token CSRF en comparación de tiempo constante. */
function csrfValido(): bool
{
    return hash_equals(csrfToken(), post('csrf'));
}

/** Corta una cadena larga para mostrarla (p. ej. claves privadas). */
function abreviar(string $texto, int $max = 60): string
{
    return strlen($texto) > $max ? substr($texto, 0, $max) . '…' : $texto;
}

/** Ruta relativa al laboratorio, para mostrar dónde se guardó un archivo. */
function rutaRelativa(string $ruta): string
{
    return str_replace('\\', '/', substr($ruta, strlen(LAB_ROOT) + 1));
}
