<?php
/*
 * firmaDiprena — Firma digital de un trámite de personal (flujo DIPRENA) sin base de datos.
 *
 * En el sistema original, DiprenaGuardarAprobacion.php leía el trámite de MySQL, concatenaba los
 * campos sensibles en $datos e incluía firmaDiprena.php para firmarlos con keys/private_key.pem.
 * Esa versión no es ejecutable en PHP 8 (usa mysql_*, eliminada en PHP 7, y archivos ausentes).
 * Aquí los datos llegan por formulario y se conserva el mismo proceso criptográfico.
 *
 * Mejoras de seguridad:
 *  - Los campos se serializan con json_encode() en vez de concatenarlos: "Ana"+"Maria" y "AnaM"+"aria"
 *    producirían la misma cadena y, por lo tanto, la misma firma.
 *  - El id del trámite se valida como entero (el original lo usaba en rutas y SQL sin validar:
 *    riesgo de path traversal e inyección SQL).
 *  - Se muestra la consulta preparada que reemplazaría al UPDATE vulnerable.
 */
require __DIR__ . '/includes/bootstrap.php';

$rutaPrivada = DIR_KEYS . '/private_key.pem';
$rutaPublica = DIR_KEYS . '/public_key.pem';
$hayLlaves = is_file($rutaPrivada) && is_file($rutaPublica);

$campos = [
    'Posicion'            => ['Posición', '1024'],
    'Salario'             => ['Salario', '1850.00'],
    'Gast_Representacion' => ['Gasto de representación', '0.00'],
    'Ocupacion'           => ['Ocupación', 'Analista de Sistemas'],
    'ResueltoPersonal'    => ['Resuelto de personal', 'RP-2026-0457'],
    'Identificacion'      => ['Identificación', '8-888-8888'],
    'Nombre'              => ['Nombre', 'Juan'],
    'SegundoNombre'       => ['Segundo nombre', 'Carlos'],
    'Apellido'            => ['Apellido', 'Pérez'],
    'SegundoApellido'     => ['Segundo apellido', 'Rodríguez'],
];

$datosfirmados = [];
foreach ($campos as $clave => [, $porDefecto]) {
    $datosfirmados[$clave] = trim(post($clave, $porDefecto));
}
$idCargoActual = post('idCargo', '1');
$accion = post('accion');
$resultado = null;
$error = null;

if (esPost()) {
    if (!csrfValido()) {
        $error = 'Token CSRF inválido. Recargue la página.';
    } elseif (!$hayLlaves) {
        $error = 'No existen keys/private_key.pem ni keys/public_key.pem. Ejecute primero firma7.';
    } elseif (!ctype_digit($idCargoActual) || (int) $idCargoActual < 1) {
        $error = 'El id del trámite debe ser un número entero positivo.';
    } else {
        $datos = json_encode($datosfirmados, JSON_UNESCAPED_UNICODE);
        $archivo = 'keys/signature' . (int) $idCargoActual . '.dat';
        $rutaFirma = LAB_ROOT . '/' . $archivo;

        if ($accion === 'firmar') {
            $resourcePrivateKey = openssl_pkey_get_private(file_get_contents($rutaPrivada));
            if (!openssl_sign($datos, $firma, $resourcePrivateKey, OPENSSL_ALGO_SHA256)) {
                $error = 'openssl_sign() falló: ' . opensslErrores();
            } else {
                file_put_contents($rutaFirma, $firma);
            }
        } elseif (!is_file($rutaFirma)) {
            $error = "No hay firma guardada para el trámite $idCargoActual. Fírmelo primero.";
        } else {
            $firma = file_get_contents($rutaFirma);
        }

        if ($error === null) {
            $resultado = [
                'tipo'     => $accion,
                'datos'    => $datos,
                'archivo'  => $archivo,
                'firma'    => base64_encode($firma),
                'valida'   => openssl_verify($datos, $firma, file_get_contents($rutaPublica), 'sha256WithRSAEncryption'),
            ];
        }
    }
}

$titulo = 'firmaDiprena — Firma de un trámite de personal';
require __DIR__ . '/includes/header.php';
?>
<div class="tarjeta info">
    <strong>Caso real:</strong> al aprobar un trámite, se firman los datos sensibles del funcionario (posición, salario,
    identificación…). Si alguien cambia luego el salario en la base de datos, la firma deja de ser válida.
    Usa las llaves generadas por <a href="firma7.php">firma7</a>.
</div>

<?php if (!$hayLlaves): ?><div class="alerta aviso">⚠ Ejecute primero <a href="firma7.php">firma7</a> para generar keys/private_key.pem.</div><?php endif; ?>
<?php if ($error): ?><div class="alerta error"><?= h($error) ?></div><?php endif; ?>

<form method="post" class="tarjeta">
    <h2>Datos del trámite</h2>
    <?= campoCsrf() ?>
    <label for="idCargo">Id del trámite (idCargo)</label>
    <input type="number" id="idCargo" name="idCargo" min="1" value="<?= h($idCargoActual) ?>" required>
    <div class="dos-columnas">
        <?php foreach ($campos as $clave => [$etiqueta]): ?>
            <div>
                <label for="<?= h($clave) ?>"><?= h($etiqueta) ?></label>
                <input type="text" id="<?= h($clave) ?>" name="<?= h($clave) ?>" value="<?= h($datosfirmados[$clave]) ?>">
            </div>
        <?php endforeach; ?>
    </div>
    <button type="submit" name="accion" value="firmar">Aprobar y firmar</button>
    <button type="submit" name="accion" value="verificar" class="secundario">Verificar contra la firma guardada</button>
    <p class="ayuda">Para simular una alteración: firme, cambie el salario y pulse «Verificar contra la firma guardada».</p>
</form>

<?php if ($resultado): ?>
<div class="tarjeta">
    <h2><?= $resultado['tipo'] === 'firmar' ? 'Trámite firmado' : 'Verificación del trámite' ?></h2>
    <?php if ($resultado['valida'] === 1): ?>
        <div class="alerta ok">✅ la firma es válida y los datos son confiables</div>
    <?php else: ?>
        <div class="alerta error">❌ la firma es inválida y/o los datos fueron alterados</div>
    <?php endif; ?>
    <table>
        <tr><th>$datos (JSON firmado)</th><td class="mono"><?= h($resultado['datos']) ?></td></tr>
        <tr><th>Archivo de firma</th><td class="mono"><?= h($resultado['archivo']) ?></td></tr>
        <tr><th>Firma (Base64)</th><td class="mono"><?= h($resultado['firma']) ?></td></tr>
    </table>
    <?php if ($resultado['tipo'] === 'firmar'): ?>
        <h3>Actualización en BD (consulta preparada que reemplaza al UPDATE con variables concatenadas)</h3>
        <pre>$stmt = $pdo->prepare('UPDATE tramitescargo SET firma = :archivo WHERE id = :id');
$stmt->execute([':archivo' => '<?= h($resultado['archivo']) ?>', ':id' => <?= (int) $idCargoActual ?>]);</pre>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
