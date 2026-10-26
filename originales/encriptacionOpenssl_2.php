<?php

$first_key = base64_decode("Irina");
$second_key = base64_decode("Fong");    
    
$method = "aes-256-cbc";    
$iv_length = openssl_cipher_iv_length($method);
$iv = openssl_random_pseudo_bytes($iv_length);
        
$first_encrypted = openssl_encrypt("Hola como estas",
$method,$first_key, OPENSSL_RAW_DATA ,$iv);    
$second_encrypted = hash_hmac('sha3-512', 
$first_encrypted, $second_key, TRUE);
            
$output = base64_encode($iv.$second_encrypted.$first_encrypted);  
echo $output;
?>