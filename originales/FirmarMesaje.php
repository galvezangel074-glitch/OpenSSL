<?PHP 

/*¿Próximo paso?
El primer archivo que se debe crear es FirmaOtra para crear 
el certificado y la llave privada.
Paso 1: Verificar la firma
Un ejemplo fuera del contexto web sería:
Firmar un mensaje (o archivo) con la clave privada.
Verificar esa firma con el certificado público.
✅ Ejemplo práctico: Firmar y verificar un mensaje en PHP
*/

$mensaje = "Este es un mensaje confidencial";
//Código para firmarlo:

// Cargar la clave privada desde el archivo generado
$privateKey = file_get_contents('keysCert/privkey.pem');
$privKeyResource = openssl_pkey_get_private($privateKey);

// Firmar el mensaje
openssl_sign($mensaje, $firmaBinaria, $privKeyResource, OPENSSL_ALGO_SHA256);

// Convertir la firma a base64 para almacenarla o enviarla
$firmaBase64 = base64_encode($firmaBinaria);

echo "Firma en base64:\n" . $firmaBase64;



/*
¿Próximo paso?
El primer archivo que se debe crear es FirmaOtra para crear 
el certificado y la llave privada.
El segundo archivo que se debe crear es Firmar Mensaje
Paso 2: Verificar la firma
Ahora, del otro lado, alguien tiene tu certificado público (certout.csr) y quiere verificar la firma:
*/
// Cargar el certificado generado
$cert = file_get_contents('keysCert/certout.csr');
$pubKeyResource = openssl_pkey_get_public($cert);

// Convertir la firma desde base64
$firmaBinaria = base64_decode($firmaBase64);

// Verificar la firma
$ok = openssl_verify($mensaje, $firmaBinaria, $pubKeyResource, OPENSSL_ALGO_SHA256);

if ($ok === 1) {
    echo "✅ Firma válida. El mensaje no fue alterado.";
} elseif ($ok === 0) {
    echo "❌ Firma inválida. El mensaje pudo haber sido modificado.";
} else {
    echo "⚠ Error al verificar la firma: " . openssl_error_string();
}


?>