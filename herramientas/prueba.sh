#!/bin/bash
# Prueba funcional de todos los formularios del laboratorio
B=http://localhost/OPENSSL; J=$(mktemp); O=$(mktemp)
curl -s -c $J -b $J $B/index.php > /dev/null
T=$(curl -s -c $J -b $J $B/firma7.php | grep -o 'name="csrf" value="[a-f0-9]*"' | head -1 | sed 's/.*value="//;s/"//')
p() { local f=$1; shift; curl -s -c $J -b $J -X POST "$B/$f" --data-urlencode "csrf=$T" "$@" > $O; }
chk() { if grep -q "$2" $O; then echo "OK   $1"; else echo "FAIL $1"; grep -oE 'alerta [a-z]+">[^<]*' $O | head -3; fi; grep -iE '<b>(warning|fatal|deprecated|notice)' $O; }
campo() { grep -o "$1[^<\"]*" $O | head -1 | sed "s/^$1//"; }

for alg in aes-128-cbc aes-256-cbc aes-256-gcm; do
  p Encriptacion.php -d accion=cifrar --data-urlencode "texto=Cédula 8-1038-857" -d clave=secreta -d algoritmo=$alg; chk "Encriptacion cifrar $alg" 'coincide'
  PK=$(campo 'name="paquete" required>')
  p Encriptacion.php -d accion=descifrar --data-urlencode "paquete=$PK" -d clave=secreta -d algoritmo=$alg; chk "Encriptacion descifrar $alg" 'Texto descifrado correctamente'
  p Encriptacion.php -d accion=descifrar --data-urlencode "paquete=$PK" -d clave=otra -d algoritmo=$alg; chk "Encriptacion clave incorrecta $alg" 'No se pudo descifrar\|Se descifr'
done
p Encriptacion.php -d accion=descifrar --data-urlencode "paquete=$PK" -d clave=secreta -d algoritmo=aes-256-gcm -d alterar=1; chk "Encriptacion GCM alterado" 'el tag'

p encriptacionOpenssl_2.php -d accion=cifrar --data-urlencode "texto=Hola como estas" --data-urlencode "first_key=$(head -c32 /dev/urandom|base64 -w0)" --data-urlencode "second_key=$(head -c64 /dev/urandom|base64 -w0)"
chk "Cifrado+HMAC cifrar" 'base64(iv . hmac'
K1=$(campo 'name="first_key" value="'); K2=$(campo 'name="second_key" value="'); S=$(campo 'name="salida">')
p encriptacionOpenssl_2.php -d accion=descifrar --data-urlencode "first_key=$K1" --data-urlencode "second_key=$K2" --data-urlencode "salida=$S"; chk "Cifrado+HMAC verificar" 'HMAC válido'
p encriptacionOpenssl_2.php -d accion=descifrar --data-urlencode "first_key=$K1" --data-urlencode "second_key=$K2" --data-urlencode "salida=$S" -d alterar=1; chk "Cifrado+HMAC alterado" 'HMAC inválido'

p firma7.php -d accion=firmar --data-urlencode "datos=Este texto será firmado" -d bits=2048 -d regenerar=1; chk "firma7 firmar" 'la firma es válida'
F=$(campo 'name="firma" required>')
p firma7.php -d accion=verificar --data-urlencode "datos=Este texto será firmado" --data-urlencode "firma=$F"; chk "firma7 verificar" 'la firma es válida'
p firma7.php -d accion=verificar --data-urlencode "datos=Este texto sera firmado" --data-urlencode "firma=$F"; chk "firma7 alterado" 'la firma es inválida'
p firma7.php -d accion=firmar --data-urlencode "datos=reuso" -d bits=2048; chk "firma7 reutiliza llaves" 'Se reutiliz'

p FirmarMensaje.php -d accion=firmar -d mensaje=x; chk "FirmarMensaje sin certificado" 'Ejecute primero firmaOtra'
p firmaOtra.php -d countryName=pa -d localityName=PA-8 -d organizationalUnitName=FISC -d commonName="Angel Galvez" -d emailAddress= -d dias=365 -d bits=2048; chk "firmaOtra certificado" 'Certificado creado'
grep -q 'corresponde al certificado?</th><td><span class="etiqueta ok">s' $O && echo "OK   firmaOtra llave corresponde al certificado"
printf "stateOrProvinceName=Panam�" > /tmp/latin1; p firmaOtra.php -d countryName=PA -d commonName=x -d dias=365 -d bits=2048 --data-binary @/tmp/latin1 -d x=1; chk "firmaOtra rechaza no-UTF8" "UTF-8"
p firmaOtra.php -d countryName=PAN -d commonName=x -d dias=365 -d bits=2048; chk "firmaOtra validacion pais" 'digo ISO'
p FirmarMensaje.php -d accion=firmar --data-urlencode "mensaje=Este es un mensaje confidencial"; chk "FirmarMensaje firmar" 'Mensaje firmado'
F=$(campo 'name="firma" required>')
p FirmarMensaje.php -d accion=verificar --data-urlencode "mensaje=Este es un mensaje confidencial" --data-urlencode "firma=$F"; chk "FirmarMensaje verificar" 'Firma válida'
p FirmarMensaje.php -d accion=verificar --data-urlencode "mensaje=Este es un mensaje confidencial!" --data-urlencode "firma=$F"; chk "FirmarMensaje alterado" 'Firma inválida'

D=(-d idCargo=7 -d Posicion=1024 -d Gast_Representacion=0 -d Ocupacion=Analista -d ResueltoPersonal=RP-1 -d Identificacion=8-888-8888 -d Nombre=Juan -d SegundoNombre= -d Apellido=Perez -d SegundoApellido=)
p firmaDiprena.php -d accion=firmar -d Salario=1850.00 "${D[@]}"; chk "firmaDiprena firmar" 'la firma es válida'
p firmaDiprena.php -d accion=verificar -d Salario=1850.00 "${D[@]}"; chk "firmaDiprena verificar" 'la firma es válida'
p firmaDiprena.php -d accion=verificar -d Salario=9850.00 "${D[@]}"; chk "firmaDiprena salario alterado" 'la firma es inválida'
p firmaDiprena.php -d accion=firmar -d Salario=1 -d idCargo=../x; chk "firmaDiprena id invalido" 'entero positivo'

p firma4.php -d commonName=MyCRS -d organizationName=UTP; chk "firma4 CSR" 'BEGIN CERTIFICATE REQUEST'
p firmaDigital.php --data-urlencode "mensaje=Número de tarjeta: 4111-1111-1111-1111"; chk "firmaDigital RSA-OAEP" 'coincide'

p sodium.php -d seccion=secretbox -d accion=cifrar -d a_texto=secreto -d a_contrasena=clave; chk "Sodium secretbox cifrar" 'Cifrado en una sola llamada'
SB=$(campo 'name="a_paquete">')
p sodium.php -d seccion=secretbox -d accion=descifrar --data-urlencode "a_paquete=$SB" -d a_contrasena=clave; chk "Sodium secretbox abrir" 'Autenticado y descifrado'
p sodium.php -d seccion=secretbox -d accion=descifrar --data-urlencode "a_paquete=$SB" -d a_contrasena=clave -d alterar=1; chk "Sodium secretbox alterado" 'devolvi.* false'
p sodium.php -d seccion=firma -d accion=firmar -d b_mensaje=hola; chk "Sodium Ed25519 firmar" 'Firmado. Llave'
BP=$(campo 'name="b_publica" value="'); BF=$(campo 'name="b_firma" value="')
p sodium.php -d seccion=firma -d accion=verificar -d b_mensaje=hola --data-urlencode "b_publica=$BP" --data-urlencode "b_firma=$BF"; chk "Sodium Ed25519 verificar" 'Firma Ed25519 v'
p sodium.php -d seccion=firma -d accion=verificar -d b_mensaje=hola! --data-urlencode "b_publica=$BP" --data-urlencode "b_firma=$BF"; chk "Sodium Ed25519 alterado" 'Firma inv'
p sodium.php -d seccion=pwhash -d accion=hash -d c_contrasena=abc123; chk "Sodium Argon2id hash" 'Hash generado'
H=$(campo 'name="c_hash" value="')
p sodium.php -d seccion=pwhash -d accion=verificar --data-urlencode "c_hash=$H" -d c_intento=abc123; chk "Sodium Argon2id correcta" 'a correcta'
p sodium.php -d seccion=pwhash -d accion=verificar --data-urlencode "c_hash=$H" -d c_intento=abc124; chk "Sodium Argon2id incorrecta" 'a incorrecta'
p sodium.php -d seccion=sealed --data-urlencode "d_mensaje=Tarjeta 4111"; chk "Sodium sealed box" 'coincide'
p sodium.php -d seccion=rendimiento; chk "Sodium rendimiento" 'pido</span>'
grep -oE '<h3>[^<]+</h3>|<th>[^<]+</th><td>[0-9.,]+ ms' $O | sed 's/<[^>]*>/ /g'

T=malo; p firma7.php -d accion=firmar -d datos=x; chk "CSRF rechazado" 'Token CSRF'
for u in keys/private_key.pem keysCert/privkey.pem includes/bootstrap.php; do echo "GET $u -> $(curl -s -o /dev/null -w '%{http_code}' $B/$u)"; done
rm -f $J $O
