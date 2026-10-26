<?PHP

$firma = "";
//si bien ya tengo cargado el string de la clave privada, lo voy a buscar a disco para verificar que el archivo private_key.pem haya sido correctamente generado:
$privateKeyPem = file_get_contents('keys/private_key.pem');
$publicKeyPem = file_get_contents('keys/public_key.pem');
//obtengo la clave privada como resource desde el string
$resourcePrivateKey = openssl_get_privatekey($privateKeyPem);
//crear la firma dentro de la variable $firma (la cual es pasada por referencia)
if (!openssl_sign($datos, $firma, $resourcePrivateKey, OPENSSL_ALGO_SHA256)) {
    echo openssl_error_string(); //en el caso que la función anterior de openssl arrojará algun error, este sería visualizado gracias a esta línea
    exit;
}
// guardar la firma en disco:
$archivo = 'keys/signature'.$idCargoActual.'.dat';

file_put_contents($archivo, $firma);
file_put_contents('../Bethel/'.$archivo, $firma);

echo "la firma es:".$firma."<br>";


					$string =  "firma = '$archivo'";
					$update_str = "idCargo ='$idCargoActual'";
					//$sql->update("cargo","$string","$update_str");
					//echo $update_str;
					//echo "<br>";
					//echo "UPDATE $tbCargo SET '$string'  WHERE $update_str";
					//echo "<br>";
					//echo "<br>";
					
					$consulta1 = "UPDATE $tbCargo SET firma = '$archivo' WHERE $update_str";
					
	 				mysql_query($consulta1);
					//echo "update tramitescargo set firma = '$archivo' where id='$idCargoActual'";
					//echo "<br>";
					//echo "<br>";
					$consulta2 = "UPDATE tramitescargo SET firma = '$archivo' WHERE id ='$idCargoActual'";
					mysql_query($consulta2);
	 	 

// comprobar la firma
if (openssl_verify($datos, $firma, $publicKeyPem, 'sha256WithRSAEncryption') === 1) {
    echo 'la firma es valida y los datos son confiables';
} else {
    echo 'la firma es invalida y/o los datos fueron alterados';
}


					
	 	         