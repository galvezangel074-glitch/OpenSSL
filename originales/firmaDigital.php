<?PHP

 $privateKey = openssl_pkey_new(array(
	    "config" => "C:\openssl\bin\openssl.cnf",	
        'private_key_bits' => 2048,      // Tamaño de la llave
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ));
    // Guardar la llave privada en el archivo private.key. No compartir este archivo con nadie
    openssl_pkey_export_to_file($privateKey, 'private.key' ,null,["config" => "C:\openssl\bin\openssl.cnf"]);

    // Generar la llave pública para la llave privada
    $a_key = openssl_pkey_get_details($privateKey);

    // Guardar la llave pública en un archivo public.key.
    // Envía este archivo a cualquiera que quiera enviarte datos encriptados
    file_put_contents('public.key', $a_key['key']);

    // Libera la llave privada
    openssl_free_key($privateKey);
	
	?>