<?php
/** @var string $titulo  Título de la página actual */
$paginaActual = basename($_SERVER['SCRIPT_NAME'] ?? '');
$menu = [
    'index.php'                 => 'Inicio',
    'Informacion.php'           => 'Información',
    'Encriptacion.php'          => 'Encriptación',
    'encriptacionOpenssl_2.php' => 'Cifrado + HMAC',
    'firma7.php'                => 'firma7',
    'firmaOtra.php'             => 'firmaOtra',
    'FirmarMensaje.php'         => 'FirmarMensaje',
    'firmaDiprena.php'          => 'firmaDiprena',
    'firma4.php'                => 'firma4',
    'firmaDigital.php'          => 'firmaDigital',
    'sodium.php'                => 'Sodium',
];
?><!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($titulo) ?> · Lab OpenSSL</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="barra">
    <div class="marca">
        <span class="logo">🔐</span>
        <div>
            <strong>Laboratorio: Seguridad en PHP con OpenSSL</strong>
            <small>Desarrollo Web VII · UTP · OWASP – Protege los Datos</small>
        </div>
    </div>
    <nav>
        <?php foreach ($menu as $menuArchivo => $menuTexto): ?>
            <a href="<?= h($menuArchivo) ?>"<?= $menuArchivo === $paginaActual ? ' class="activo"' : '' ?>><?= h($menuTexto) ?></a>
        <?php endforeach; ?>
    </nav>
</header>
<main>
    <h1><?= h($titulo) ?></h1>
