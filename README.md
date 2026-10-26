<div align="center">

<img src="docs/img/logo-utp.png" alt="UTP" height="110">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src="docs/img/logo-fisc.png" alt="FISC" height="110">

# UNIVERSIDAD TECNOLÓGICA DE PANAMÁ

**FACULTAD DE INGENIERÍA DE SISTEMAS COMPUTACIONALES**<br>
**CAMPUS VÍCTOR LEVIS SASSO**<br>
**DESARROLLO WEB**

## 🔐 Laboratorio – Seguridad en PHP con OpenSSL

**Tema:** Programación segura según OWASP – Protege los datos (A02: Cryptographic Failures)<br>
**Lenguaje de programación:** PHP · **II Semestre 2026**<br>
**Instructor:** Ing. Irina Fong

<br>

![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)
![OpenSSL](https://img.shields.io/badge/OpenSSL-3.0.18-721412?style=for-the-badge&logo=openssl&logoColor=white)
![Libsodium](https://img.shields.io/badge/libsodium-1.0.18-1EA362?style=for-the-badge&logo=letsencrypt&logoColor=white)
![Apache](https://img.shields.io/badge/Apache-2.4-D22128?style=for-the-badge&logo=apache&logoColor=white)
![WampServer](https://img.shields.io/badge/WampServer-64_bits-FF0040?style=for-the-badge&logo=windows&logoColor=white)
![OWASP](https://img.shields.io/badge/OWASP-Top_10-000000?style=for-the-badge&logo=owasp&logoColor=white)

</div>

---

## 📑 Tabla de contenido
1. [Requisitos previos](#1-requisitos-previos)
2. [Introducción y objetivo](#2-introducción-y-objetivo)
3. [Estructura del proyecto](#3-estructura-del-proyecto)
4. [Capturas de ejecución](#4-capturas-de-ejecución)
5. [Documentación técnica de funciones](#5-documentación-técnica-de-funciones)
6. [Investigación y ejemplo práctico con Libsodium](#6-investigación-y-ejemplo-práctico-con-libsodium)
7. [Dificultades y soluciones](#7-dificultades-y-soluciones)
8. [Conclusiones personales](#8-conclusiones-personales)
9. [Referencias](#9-referencias)
10. [Fecha de ejecución del laboratorio](#10-fecha-de-ejecución-del-laboratorio)

---

## 1. Requisitos previos

### 1.1 Prerrequisitos (ecosistema de desarrollo del laboratorio)

|  | Tecnología | Requisito | Versión utilizada |
|:-:|---|---|---|
| <img src="https://skillicons.dev/icons?i=php" width="36"> | **PHP** | 7.2 o superior (incluye Sodium) con `extension=openssl` | 8.3.28 |
| 🔐 | **OpenSSL** | Extensión `openssl` habilitada en `php.ini` | OpenSSL 3.0.18 |
| 🧂 | **Libsodium** | Extensión `sodium` (incluida desde PHP 7.2) | libsodium 1.0.18 |
| <img src="https://skillicons.dev/icons?i=apache" width="36"> | **Servidor web** | Apache | Apache 2.4.65 |
| <img src="https://skillicons.dev/icons?i=windows" width="36"> | **Paquete de servidor local** | XAMPP, WampServer o Laragon | WampServer (64 bits) |
| <img src="https://skillicons.dev/icons?i=vscode" width="36"> | **Editor de código** | Visual Studio Code (recomendado) | Visual Studio Code |
| <img src="https://skillicons.dev/icons?i=windows" width="36"> | **Sistema operativo** | — | Windows 11 Home |
| <img src="https://skillicons.dev/icons?i=git" width="36"> | **Git / GitHub** | Control de versiones y entrega | Git · GitHub |

### 1.2 Instalación desde este repositorio

```bash
# Clonar dentro de la carpeta publicada por Apache (WampServer: C:\wamp64\www)
cd C:\wamp64\www
git clone https://github.com/galvezangel074-glitch/OpenSSL.git OPENSSL

# Iniciar WampServer y abrir en el navegador:
# http://localhost/OPENSSL/
```

> Las carpetas `keys/` y `keysCert/` **no contienen llaves en el repositorio**: se generan al ejecutar `firma7.php` y `firmaOtra.php`. Están en el `.gitignore` y protegidas con `.htaccess` (`Require all denied`) para que ninguna llave privada se publique ni se descargue desde el navegador.

### 1.3 Verificar y habilitar OpenSSL / Sodium

```bash
php -m | findstr /i "openssl sodium"      # deben aparecer las dos
```

Si falta alguna: WampServer → clic en el ícono → *PHP* → *PHP extensions* → marcar `php_openssl` y `php_sodium`, o quitar el `;` de `extension=openssl` / `extension=sodium` en `php.ini` y reiniciar Apache. La página [Información](Informacion.php) del laboratorio muestra el estado de ambas.

---

## 2. Introducción y objetivo

### 🎯 Objetivo del laboratorio
Aplicar la extensión **OpenSSL** y la librería **Libsodium** en PHP para proteger datos sensibles, ejecutando ejemplos prácticos con formularios y analizando su documentación técnica, de acuerdo con los lineamientos de OWASP.

### 📘 Contexto teórico
**OpenSSL** es una biblioteca criptográfica de código abierto que implementa TLS/SSL y ofrece funciones para generar llaves, administrar certificados X.509, cifrar/descifrar y firmar. Según **OWASP Top 10 (A02: Cryptographic Failures)**, los datos sensibles (contraseñas, tokens, registros médicos, secretos de negocio) nunca deben almacenarse ni viajar en texto plano. Este laboratorio resuelve dos pilares:

| Pilar | Qué garantiza | Mecanismo usado en el laboratorio |
|---|---|---|
| **Confidencialidad** | Solo quien tenga la llave puede leer el dato | Cifrado simétrico (AES-128/256-CBC, AES-256-GCM, XSalsa20-Poly1305) y asimétrico (RSA-OAEP, sealed box) |
| **Integridad y autenticidad** | El dato no fue alterado y proviene de quien dice | Firmas digitales (RSA-SHA256, Ed25519), HMAC-SHA3-512, tags de autenticación AEAD |

### 🛡️ Buenas prácticas OWASP aplicadas a todos los formularios
- **CSRF:** cada formulario lleva un token de sesión (`csrfToken()` / `csrfValido()`).
- **XSS:** toda salida pasa por `h()` (`htmlspecialchars`).
- **Llaves privadas:** fuera del control de versiones y con acceso web denegado.
- **IV/nonce aleatorios** generados con una fuente criptográficamente segura y comparación de firmas/HMAC en tiempo constante.

---

## 3. Estructura del proyecto

| Archivo / carpeta | Función |
|---|---|
| [`index.php`](index.php) | Menú principal y estado del entorno (OpenSSL, Sodium, `openssl.cnf`) |
| [`Informacion.php`](Informacion.php) | Verificación de extensiones y `phpinfo()` |
| [`Encriptacion.php`](Encriptacion.php) | **(Requerido)** Cifrado simple: AES-128-CBC, AES-256-CBC y AES-256-GCM |
| [`firma7.php`](firma7.php) | **(Requerido)** Par de llaves RSA, firma y verificación |
| [`firmaOtra.php`](firmaOtra.php) | **(Requerido)** Llave privada, CSR y certificado X.509 autofirmado |
| [`FirmarMensaje.php`](FirmarMensaje.php) | **(Requerido)** Firma de un mensaje con la llave de `firmaOtra` y verificación con su certificado |
| [`sodium.php`](sodium.php) | **(Sección 1.6)** Libsodium: secretbox, Ed25519, Argon2id, sealed box, rendimiento |
| [`encriptacionOpenssl_2.php`](encriptacionOpenssl_2.php) | Complementario: AES-256-CBC + HMAC-SHA3-512 |
| [`firmaDiprena.php`](firmaDiprena.php) | Complementario: firma de un trámite de personal |
| [`firma4.php`](firma4.php) | Complementario: solicitud de certificado (CSR) |
| [`firmaDigital.php`](firmaDigital.php) | Complementario: RSA-OAEP con llaves exportadas a archivo |
| [`includes/`](includes) | `bootstrap.php` (config común, `openssl.cnf`, CSRF, XSS), `header.php`, `footer.php` |
| [`assets/style.css`](assets/style.css) | Estilos de los formularios |
| [`originales/`](originales) | Scripts entregados por la docente, sin modificar, como referencia |
| [`capturas/`](capturas) | Evidencias de ejecución (34 imágenes) |
| [`herramientas/`](herramientas) | `capturas.js` (genera las capturas con Edge) y `prueba.sh` (prueba funcional de los formularios con `curl`) |

Orden recomendado de ejecución: `firma7` → `firmaOtra` → `FirmarMensaje` (necesita el certificado de `firmaOtra`) → `firmaDiprena` (usa las llaves de `firma7`).

---

## 4. Capturas de ejecución

> Los scripts originales solo tenían variables fijas; se les agregaron **formularios** (E.2.2) y botones que simulan una alteración de los datos para comprobar que la verificación falla.

### 4.1 Inicio e información del entorno

| 1. Inicio (`index.php`) | 2. Información (`Informacion.php`) |
|---|---|
| ![](capturas/01_inicio.png) | ![](capturas/02_informacion.png) |

### 4.2 Encriptación simple con OpenSSL (`Encriptacion.php`)

| 3. Cifrar con AES-128-CBC | 4. Descifrar |
|---|---|
| ![](capturas/03_encriptacion_cifrar_aes128cbc.png) | ![](capturas/04_encriptacion_descifrar.png) |
| **5. Cifrar con AES-256-GCM** | **6. GCM con paquete alterado → rechazado** |
| ![](capturas/05_encriptacion_cifrar_aes256gcm.png) | ![](capturas/06_encriptacion_gcm_alterado.png) |

CBC solo da confidencialidad; **GCM** añade un *tag* de autenticación, así que si alguien modifica un solo bit del cifrado el descifrado falla (captura 6).

### 4.3 Cifrado + HMAC (`encriptacionOpenssl_2.php`)

| 7. Cifrar | 8. Verificar HMAC y descifrar | 9. Texto alterado → HMAC inválido |
|---|---|---|
| ![](capturas/07_cifrado_hmac_cifrar.png) | ![](capturas/08_cifrado_hmac_verificar.png) | ![](capturas/09_cifrado_hmac_alterado.png) |

### 4.4 `firma7.php` – par de llaves RSA y firma digital

| 10. Generar llaves y firmar | 11. Verificación válida | 12. Datos alterados → firma inválida |
|---|---|---|
| ![](capturas/10_firma7_firmar.png) | ![](capturas/11_firma7_verificar_valida.png) | ![](capturas/12_firma7_verificar_alterado.png) |

> Las llaves mostradas son de práctica, generadas solo para este laboratorio y no se usan en ningún sistema.

### 4.5 `firmaOtra.php` y `FirmarMensaje.php` – certificado X.509

| 13. Llave, CSR y certificado autofirmado | 14. Firmar un mensaje |
|---|---|
| ![](capturas/13_firmaOtra_certificado.png) | ![](capturas/14_FirmarMensaje_firmar.png) |
| **15. Verificación válida** | **16. Mensaje alterado → firma inválida** |
| ![](capturas/15_FirmarMensaje_verificar_valida.png) | ![](capturas/16_FirmarMensaje_verificar_alterado.png) |

### 4.6 Scripts complementarios

| 17. `firmaDiprena` – firmar trámite | 18. `firmaDiprena` – salario alterado |
|---|---|
| ![](capturas/17_firmaDiprena_firmar.png) | ![](capturas/18_firmaDiprena_salario_alterado.png) |
| **19. `firma4` – CSR** | **20. `firmaDigital` – RSA-OAEP** |
| ![](capturas/19_firma4_csr.png) | ![](capturas/20_firmaDigital_rsa_oaep.png) |

### 4.7 Libsodium (`sodium.php`)

| 21. Página de Sodium | 22. secretbox – cifrar |
|---|---|
| ![](capturas/21_sodium_pagina.png) | ![](capturas/22_sodium_secretbox_cifrar.png) |
| **23. secretbox – descifrar** | **24. secretbox – alterado → rechazado** |
| ![](capturas/23_sodium_secretbox_descifrar.png) | ![](capturas/24_sodium_secretbox_alterado.png) |
| **25. Ed25519 – firmar** | **26. Ed25519 – verificar** |
| ![](capturas/25_sodium_ed25519_firmar.png) | ![](capturas/26_sodium_ed25519_verificar.png) |
| **27. Ed25519 – mensaje alterado** | **28. Argon2id – hash de contraseña** |
| ![](capturas/27_sodium_ed25519_alterado.png) | ![](capturas/28_sodium_argon2id_hash.png) |
| **29. Argon2id – contraseña correcta** | **30. Argon2id – contraseña incorrecta** |
| ![](capturas/29_sodium_argon2id_correcta.png) | ![](capturas/30_sodium_argon2id_incorrecta.png) |
| **31. Sealed box (cifrado asimétrico anónimo)** | **32. Rendimiento OpenSSL vs Sodium** |
| ![](capturas/31_sodium_sealed_box.png) | ![](capturas/32_sodium_rendimiento.png) |

---

## 5. Documentación técnica de funciones

### 5.1 Funciones de OpenSSL usadas en el laboratorio

| Función | Propósito general | Parámetros recibidos | Valor devuelto | Proceso criptográfico aplicado |
|---|---|---|---|---|
| `openssl_pkey_new($config)` | Generar un par de llaves | `array $config`: `private_key_bits` (2048/4096), `private_key_type` (`OPENSSL_KEYTYPE_RSA`), `config` (ruta a `openssl.cnf`) | `OpenSSLAsymmetricKey` o `false` | Generación de números primos grandes y cálculo del par RSA (n, e, d) |
| `openssl_pkey_export($key, &$out, $pass, $config)` | Exportar la llave privada a texto PEM | Llave, variable de salida por referencia, contraseña opcional, config | `bool` | Codificación PKCS#8 en Base64; con contraseña se cifra el PEM |
| `openssl_pkey_get_details($key)` | Obtener datos de la llave (incluida la pública) | Llave | `array`: `bits`, `key` (PEM público), `type` | Extrae la llave pública (n, e) de la privada |
| `openssl_pkey_get_private()` / `openssl_pkey_get_public()` | Cargar una llave desde PEM/archivo | Cadena PEM o `file://ruta`, contraseña opcional | Recurso de llave o `false` | Decodifica el PEM a la estructura interna de OpenSSL |
| `openssl_sign($datos, &$firma, $llavePrivada, $algoritmo)` | **Firmar** datos | Datos a firmar, firma (por referencia), llave privada, `OPENSSL_ALGO_SHA256` | `bool` | Calcula el hash SHA-256 de los datos y lo cifra con la **llave privada** RSA (PKCS#1 v1.5) |
| `openssl_verify($datos, $firma, $llavePublica, $algoritmo)` | **Verificar** una firma | Datos recibidos, firma binaria, llave pública, algoritmo | `1` válida · `0` inválida · `-1` error | Descifra la firma con la **llave pública**, recalcula el hash de los datos y compara ambos |
| `openssl_encrypt($datos, $metodo, $clave, $opciones, $iv, &$tag)` | Cifrado simétrico | Texto, método (`AES-128-CBC`, `AES-256-GCM`…), clave, opciones (`OPENSSL_RAW_DATA`), IV/nonce, tag (solo GCM) | Texto cifrado o `false` | AES por bloques de 128 bits. CBC encadena bloques con el IV; GCM usa modo contador y genera un tag de autenticación |
| `openssl_decrypt(...)` | Descifrado simétrico | Mismos parámetros que `openssl_encrypt` (más el tag en GCM) | Texto original o `false` | Operación inversa; en GCM verifica el tag antes de entregar el texto |
| `openssl_cipher_iv_length($metodo)` | Saber cuántos bytes mide el IV | Nombre del método | `int` (16 para AES-CBC, 12 para GCM) | — (dato necesario para generar un IV del tamaño correcto) |
| `openssl_random_pseudo_bytes($largo)` | Generar IV/llaves aleatorios | Número de bytes | `string` binario | Generador pseudoaleatorio criptográficamente seguro del sistema |
| `openssl_public_encrypt()` / `openssl_private_decrypt()` | Cifrado asimétrico RSA | Datos, salida por referencia, llave, relleno `OPENSSL_PKCS1_OAEP_PADDING` | `bool` | RSA con relleno **OAEP**: cifra con la pública y solo la privada descifra (el tamaño máximo depende de la llave) |
| `openssl_csr_new($dn, &$llave, $config)` | Crear una solicitud de certificado (CSR) | Arreglo DN (país, localidad, organización, CN…), llave privada, config | Recurso CSR o `false` | Empaqueta el nombre distinguido y la llave pública y los firma con la llave privada (prueba de posesión) |
| `openssl_csr_sign($csr, $ca, $llave, $dias, $config)` | Firmar el CSR y emitir el certificado | CSR, certificado CA (`null` = autofirmado), llave privada, días de validez | Recurso X.509 | La CA firma el certificado con su llave privada; autofirmado: el emisor es el propio sujeto |
| `openssl_x509_export()` / `openssl_x509_parse()` / `openssl_x509_fingerprint()` | Exportar, leer y obtener la huella del certificado | Certificado, salida / algoritmo (`sha256`) | `bool` / `array` / huella hexadecimal | Lectura de campos X.509 (sujeto, emisor, validez) y hash del certificado DER |
| `openssl_error_string()` | Leer errores de OpenSSL | — | `string` o `false` | — (diagnóstico) |

### 5.2 Funciones de OpenSSL: scripts requeridos

#### `firma7.php` – firma digital

| Campo | Descripción |
|---|---|
| **Nombre** | Formulario de firma y verificación (`firma7.php`) |
| **Propósito general** | Generar un par RSA, guardarlo en `keys/`, firmar un texto y comprobar su integridad |
| **Parámetros recibidos** | `datos` (texto), `bits` (2048/4096), `regenerar` (checkbox), `firma` (Base64) y `csrf` |
| **Valor devuelto** | Página con la firma en Base64 (guardada en `keys/signature.dat`), huella SHA-256 y el resultado *firma válida / inválida* |
| **Proceso criptográfico** | `openssl_pkey_new` → `openssl_pkey_export` / `openssl_pkey_get_details` → `openssl_sign` (SHA-256 + RSA) → `openssl_verify` con la llave pública. Cambiar un solo carácter hace que `openssl_verify` devuelva `0` |

#### `firmaOtra.php` – llave, CSR y certificado

| Campo | Descripción |
|---|---|
| **Nombre** | Generador de certificado autofirmado (`firmaOtra.php`) |
| **Propósito general** | Crear la identidad digital (llave + CSR + certificado X.509) de una organización |
| **Parámetros recibidos** | Campos del DN: país, localidad, organización, nombre común, correo; días de validez; `csrf` |
| **Valor devuelto** | `keysCert/privkey.pem`, `request.csr`, `certout.crt` y el detalle del certificado (sujeto, emisor, vigencia, huella SHA-256) |
| **Proceso criptográfico** | `openssl_pkey_new` → `openssl_csr_new` → `openssl_csr_sign` (autofirmado) → `openssl_x509_export`; se comprueba con `openssl_x509_check_private_key` que la llave corresponde al certificado |

#### `FirmarMensaje.php` – firma con el certificado

| Campo | Descripción |
|---|---|
| **Nombre** | Firma y verificación con certificado (`FirmarMensaje.php`) |
| **Propósito general** | Firmar un mensaje con la llave de `firmaOtra` y verificarlo con la llave pública del certificado |
| **Parámetros recibidos** | `mensaje`, `firma` (Base64) y `csrf` |
| **Valor devuelto** | Firma en Base64 y resultado de `openssl_verify` (1 / 0 / -1) |
| **Proceso criptográfico** | `openssl_pkey_get_private` → `openssl_sign` (SHA-256) → `base64_encode`; para verificar: `base64_decode` → `openssl_pkey_get_public` (extraída del certificado) → `openssl_verify` |

> **Nota de corrección:** el original cargaba `certout.csr` (la *solicitud*) con `openssl_pkey_get_public`, que no devuelve la llave pública de un CSR. Se usa el certificado `certout.crt`.

#### `Encriptacion.php` – cifrado simple

| Campo | Descripción |
|---|---|
| **Nombre** | Cifrado/descifrado simétrico (`Encriptacion.php`) |
| **Propósito general** | Proteger un texto en reposo con AES |
| **Parámetros recibidos** | `texto`, `clave` (contraseña), `algoritmo` (AES-128-CBC / AES-256-CBC / AES-256-GCM), `paquete` (para descifrar) y `csrf` |
| **Valor devuelto** | Paquete en Base64 (`sal ‖ IV ‖ [tag] ‖ cifrado`) y, al descifrar, el texto original o el error |
| **Proceso criptográfico** | La clave del usuario no se usa directa: `derivarClave()` aplica **PBKDF2-SHA256** con sal aleatoria; el IV se genera con `openssl_random_pseudo_bytes`; luego `openssl_encrypt`/`openssl_decrypt`. En GCM el tag detecta alteraciones |

> **Nota de corrección:** el original usaba la clave literal `"clave123456789012"` y `openssl_random_pseudo_bytes` solo para el IV; se agrega derivación de clave, y GCM como opción autenticada.

### 5.3 Funciones de Libsodium

| Función | Propósito general | Parámetros recibidos | Valor devuelto | Proceso criptográfico aplicado |
|---|---|---|---|---|
| `sodium_crypto_secretbox($msg, $nonce, $clave)` | Cifrado **autenticado** simétrico | Mensaje, nonce (24 bytes), clave (32 bytes) | Cifrado + MAC (16 bytes extra) | XSalsa20 para confidencialidad y Poly1305 para integridad en una sola llamada |
| `sodium_crypto_secretbox_open($cifrado, $nonce, $clave)` | Descifrar y verificar | Cifrado, nonce, clave | Mensaje o `false` si fue alterado | Verifica el MAC antes de descifrar |
| `sodium_crypto_sign_keypair()` | Par de llaves de firma | — | Par de llaves Ed25519 | Curva elíptica Edwards25519 |
| `sodium_crypto_sign_detached($msg, $llaveSecreta)` | Firmar (firma separada) | Mensaje, llave secreta | Firma de 64 bytes | Ed25519 (determinista, sin necesidad de aleatoriedad por firma) |
| `sodium_crypto_sign_verify_detached($firma, $msg, $llavePublica)` | Verificar firma | Firma, mensaje, llave pública | `bool` | Verificación Ed25519 |
| `sodium_crypto_pwhash_str($pass, $ops, $mem)` | Hash de contraseñas | Contraseña, límites de operaciones y memoria | Cadena con algoritmo, parámetros, sal y hash | **Argon2id** (memoria-dura; resiste GPU) |
| `sodium_crypto_pwhash_str_verify($hash, $pass)` | Comprobar contraseña | Hash guardado, contraseña | `bool` | Recalcula Argon2id con los parámetros del hash |
| `sodium_crypto_pwhash(...)` | Derivar una clave desde contraseña | Largo, contraseña, sal, límites, algoritmo | Clave binaria | Argon2id con sal de 16 bytes |
| `sodium_crypto_box_seal($msg, $llavePublica)` / `..._seal_open()` | Cifrado asimétrico anónimo | Mensaje, llave pública (o par de llaves al abrir) | Cifrado / mensaje | X25519 + XSalsa20-Poly1305 con llave efímera: solo el dueño de la privada abre |
| `sodium_memzero(&$var)` | Borrar secretos de memoria | Variable por referencia | `void` | Sobrescribe el valor para no dejar claves en RAM |

---

## 6. Investigación y ejemplo práctico con Libsodium

### 6.1 Investigación teórica
**Libsodium** es una biblioteca criptográfica moderna, derivada de NaCl, mantenida por Frank Denis. Está integrada en PHP desde la **7.2** (`ext-sodium`). Su filosofía es que la criptografía sea **segura por defecto y difícil de usar mal**: elige los algoritmos, genera el nonce correcto y entrega siempre cifrado autenticado.

| Operación | Algoritmo de Libsodium | Función en PHP |
|---|---|---|
| Cifrado simétrico autenticado | XSalsa20-Poly1305 / XChaCha20-Poly1305 | `sodium_crypto_secretbox`, `sodium_crypto_aead_xchacha20poly1305_ietf_encrypt` |
| Cifrado asimétrico | X25519 + XSalsa20-Poly1305 | `sodium_crypto_box`, `sodium_crypto_box_seal` |
| Firmas digitales | Ed25519 | `sodium_crypto_sign_*` |
| Hash de contraseñas | Argon2id (recomendado por OWASP) | `sodium_crypto_pwhash_str` |
| Hash genérico | BLAKE2b | `sodium_crypto_generichash` |

**Ventajas de seguridad:** funciones resistentes al mal uso, tiempo constante frente a ataques de canal lateral, Argon2id contra fuerza bruta con GPU y llaves cortas (32 bytes) con seguridad equivalente a RSA mucho más largo.
**Ventajas de rendimiento:** Ed25519 es mucho más rápido que RSA-2048 al generar llaves (0.064 ms vs 58.020 ms) y al firmar (0.079 ms vs 0.440 ms); en cambio, AES-256-GCM de OpenSSL cifra 1 MB más rápido que `secretbox` (0.201 ms vs 1.689 ms) porque el procesador lo acelera por hardware (ver captura 32).

### 6.2 OpenSSL vs. Libsodium

| Característica | OpenSSL | Libsodium (ext-sodium) |
|---|---|---|
| **Propósito principal** | Protocolos de red, certificados X.509 y criptografía asimétrica (RSA) | Criptografía moderna: cifrado, firmas y hashing de alta seguridad |
| **Complejidad** | Alta: paddings, longitudes, IV, modos, `openssl.cnf` | Baja: funciones simples y seguras |
| **Errores comunes** | IV repetido, CBC sin MAC, relleno PKCS#1 v1.5 | Difíciles de cometer: el nonce y el MAC van incluidos |
| **Uso recomendado** | HTTPS, llaves públicas/privadas, certificados | Contraseñas y cifrado de datos sensibles en base de datos |

### 6.3 Ejemplo implementado (`sodium.php`)
La página integra cinco demostraciones ejecutables con formularios (capturas 21-32):

1. **Cifrado autenticado** (`secretbox`): la clave se deriva de la contraseña con Argon2id; si el cifrado se altera, `secretbox_open` devuelve `false`.
2. **Firma digital Ed25519**: firmar, verificar y detectar alteración.
3. **Hash de contraseñas con Argon2id**: guardar y comprobar contraseña correcta/incorrecta.
4. **Sealed box**: cifrado con la llave pública del destinatario.
5. **Comparativa de rendimiento** OpenSSL (RSA-2048, AES) frente a Sodium (Ed25519, secretbox).

```php
// Núcleo del ejemplo de cifrado autenticado
$nonce   = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
$cifrado = sodium_crypto_secretbox($mensaje, $nonce, $clave);

$abierto = sodium_crypto_secretbox_open($cifrado, $nonce, $clave);
if ($abierto === false) {
    echo 'Mensaje alterado o clave incorrecta';
}
sodium_memzero($clave);
```

---

## 7. Dificultades y soluciones

- **Dificultad 1:** `firma7.php` y `firmaOtra.php` originales fallaban porque `firmaOtra` apunta a `C:\OpenSSL-Win64\bin\cnf\openssl.cnf`, una ruta que no existe en WampServer (capturas `00a` y `00b`).

  | 00a. Error original `firma7` | 00b. Error original `firmaOtra` |
  |---|---|
  | ![](capturas/00a_error_original_firma7.png) | ![](capturas/00b_error_original_firmaOtra.png) |

  - **Solución:** se aplicaron las soluciones 1 y 2 de la guía en `includes/bootstrap.php`: `localizarOpensslCnf()` busca `openssl.cnf` en `OPENSSL_CONF`, en la carpeta de PHP de WampServer, en `C:/xampp/...` y en rutas de OpenSSL independiente; si no encuentra ninguna, no se envía `config` y PHP usa el predeterminado.
- **Dificultad 2:** `FirmarMensaje.php` original intentaba obtener la llave pública desde `certout.csr` (una solicitud, no un certificado).
  - **Solución:** `firmaOtra.php` ahora genera también `certout.crt` y `FirmarMensaje.php` lo usa.
- **Dificultad 3:** `DiprenaGuardarAprobacion.php` no se puede ejecutar fuera de su sistema: requiere `Barricada.php`, `clases/…` y las funciones `mysql_*`, eliminadas en PHP 7.
  - **Solución:** su parte criptográfica se reprodujo en `firmaDiprena.php` sin base de datos; el original se conserva en `originales/`.
- **Dificultad 4:** los originales usaban datos fijos en el código.
  - **Solución:** se agregaron formularios con token CSRF y escape de salida.
- **Dificultad 5:** no subir llaves privadas a GitHub.
  - **Solución:** `.gitignore` excluye `keys/*` y `keysCert/*`; los `.htaccess` impiden descargarlas por HTTP.

---

## 8. Conclusiones personales

Este laboratorio me mostró que la criptografía en PHP se reduce a pocas funciones, pero un detalle mal configurado la deja sin protección: un IV repetido, una clave escrita en el código o un cifrado sin verificación de integridad. Al alterar el texto en los formularios comprobé la diferencia entre **cifrar** (confidencialidad) y **autenticar** (integridad): AES-CBC sin MAC descifra basura sin avisar, mientras que AES-GCM y `secretbox` rechazan el mensaje alterado.

También entendí el papel de cada pieza de la firma digital: la llave privada firma, la pública verifica, y el certificado X.509 asocia esa llave a una identidad. Resolver la falta de `openssl.cnf` y la confusión entre CSR y certificado me enseñó a leer los errores de `openssl_error_string()` y a no confiar en rutas fijas.

Con Libsodium vi que hacer lo correcto puede ser más sencillo: menos parámetros, nonce y MAC incluidos y Argon2id para contraseñas. Mi conclusión es usar **OpenSSL cuando se necesitan certificados y compatibilidad** y **Sodium para cifrar datos sensibles y guardar contraseñas**, alineado con A02 de OWASP, y no subir nunca llaves privadas al repositorio.

---

## 9. Referencias

1. Fong, I. (2026). *Guía para la entrega del laboratorio: Seguridad en PHP con OpenSSL*. Universidad Tecnológica de Panamá.
2. Salomon2514. *SeguridadOpenSSL* [Repositorio]. GitHub. Scripts originales del laboratorio.
3. PHP Group. *OpenSSL – Manual de PHP*. https://www.php.net/manual/es/book.openssl.php
4. PHP Group. *Sodium – Manual de PHP*. https://www.php.net/manual/es/book.sodium.php
5. Libsodium. *Documentación de libsodium*. https://doc.libsodium.org/
6. OWASP. (2021). *A02:2021 – Cryptographic Failures*. https://owasp.org/Top10/A02_2021-Cryptographic_Failures/
7. OWASP. *Password Storage Cheat Sheet*. https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html
8. OpenSSL Project. *OpenSSL documentation*. https://docs.openssl.org/

---

## 10. Fecha de ejecución del laboratorio

📅 **23 de octubre de 2026**

<div align="center">

Este laboratorio ha sido desarrollado por el estudiante de la **Universidad Tecnológica de Panamá**:

**Nombre:** Angel Gálvez<br>
**Cédula:** 8-1038-857<br>
**Correo:** [angel.galvez2@utp.ac.pa](mailto:angel.galvez2@utp.ac.pa)<br>
**Curso:** Desarrollo Web (Grupo IS3122)<br>
**Instructor del laboratorio:** Irina Fong

</div>
