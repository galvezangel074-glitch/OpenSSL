<?php
//Datos que se quieren firmar:
$datos = 'Este texto será firmado. Thanks for your attention :)';
//Se deben crear dos claves aparejadas, una clave pública y otra privada
//A continuación el array de configuración para la creación del juego de claves

$configArgs = array(
    'config' => 'C:\OpenSSL-Win64\bin\cnf\openssl.cnf', //<-- esta ruta es necesaria si trabajas con XAMPP
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA
);
$resourceNewKeyPair = openssl_pkey_new($configArgs);
if (!$resourceNewKeyPair) {
    echo 'Puede que tengas problemas con la ruta indicada en el array de configuración "$configArgs" ';
    echo openssl_error_string(); //en el caso que la función anterior de openssl arrojará algun error, este sería visualizado gracias a esta línea
    exit;
}
//obtengo del recurso $resourceNewKeyPair la clave publica como un string 
$details = openssl_pkey_get_details($resourceNewKeyPair);
$publicKeyPem = $details['key'];
//obtengo la clave privada como string dentro de la variable $privateKeyPem (la cual es pasada por referencia)
if (!openssl_pkey_export($resourceNewKeyPair, $privateKeyPem, NULL, $configArgs)) {
    echo openssl_error_string(); //en el caso que la función anterior de openssl arrojará algun error, este sería visualizado gracias a esta línea
    exit;
}
//guardo la clave publica y privada en disco:
file_put_contents('keys/private_key.pem', $privateKeyPem);
file_put_contents('keys/public_key.pem', $publicKeyPem);
//si bien ya tengo cargado el string de la clave privada, lo voy a buscar a disco para verificar que el archivo private_key.pem haya sido correctamente generado:
$privateKeyPem = file_get_contents('keys/private_key.pem');
//obtengo la clave privada como resource desde el string
$resourcePrivateKey = openssl_get_privatekey($privateKeyPem);
//crear la firma dentro de la variable $firma (la cual es pasada por referencia)
if (!openssl_sign($datos, $firma, $resourcePrivateKey, OPENSSL_ALGO_SHA256)) {
    echo openssl_error_string(); //en el caso que la función anterior de openssl arrojará algun error, este sería visualizado gracias a esta línea
    exit;
}
// guardar la firma en disco:
file_put_contents('keys/signature.dat', $firma);
// comprobar la firma

if (openssl_verify($datos, $firma, $publicKeyPem, 'sha256WithRSAEncryption') === 1) {
    echo 'la firma es valida y los datos son confiables';
} else {
    echo 'la firma es invalida y/o los datos fueron alterados';
}