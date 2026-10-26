<?php
/*Con openssl_encrypt() / openssl_decrypt()*/

$clave = "clave123456789012"; // 16 caracteres para AES-128
$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length("AES-128-CBC"));

$texto = "Mensaje secreto";
$cifrado = openssl_encrypt($texto, "AES-128-CBC", $clave, 0, $iv);

echo "el resultado del cifrado es el siguiente ".$cifrado."<br>";
$descifrado = openssl_decrypt($cifrado, "AES-128-CBC", $clave, 0, $iv);

echo "el resultado del descifrado es: ".$descifrado."<br>";


?>

