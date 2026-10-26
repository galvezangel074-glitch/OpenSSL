



## ![Seguridad](https://img.shields.io/badge/Código-Seguro-green?style=flat&logo=github&logoColor=white) Seguridad con OpenSSL en PHP
OpenSSL es una biblioteca criptográfica que permite una implementación de código abierto de los protocolos de seguridad de la capa de transporte (TLS) y capa de sockets seguros (SSL). Proporciona funciones para generar claves privadas, administrar certificados y equipar aplicaciones cliente con cifrado y descifrado.
<br>
## 🌐 Tecnologías utilizadas  

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white) 
![Apache](https://img.shields.io/badge/Apache-D22128?style=for-the-badge&logo=apache&logoColor=white) 
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white) 
![XAMPP](https://img.shields.io/badge/XAMPP-F37623?style=for-the-badge&logo=xampp&logoColor=white) 
![WampServer](https://img.shields.io/badge/WampServer-FF0040?style=for-the-badge&logo=windows&logoColor=white)
![OWASP](https://img.shields.io/badge/OWASP-1EA362?style=for-the-badge&logo=letsencrypt&logoColor=white)
![OpenSSL](https://img.shields.io/badge/OpenSSL-771C2E?style=for-the-badge&logo=letsencrypt&logoColor=white)

<br>

## 🛡️ ¿Cómo usar OpenSSL para BLINDAR los datos? (Confidencialidad)


Se puede usar OpenSSL (y las herramientas criptográficas en general) perfectamente para cumplir con el principio de Protección de Datos de OWASP, logrando exactamente los dos objetivos: blindar la información y darnos cuenta si alguien la vulneró o alteró.


Cuando OWASP habla de proteger datos (especialmente en su categoría de fallos criptográficos o exposición de datos sensibles), la herramienta por excelencia es el cifrado. Con OpenSSL puedes hacer dos tipos de cifrado principales:

Cifrado Simétrico (AES): Ideal para cuando quieres guardar información secreta en la base de datos o en archivos (por ejemplo, tarjetas de crédito, números de cédula, datos médicos). La misma clave secreta que cifra el dato es la que lo descifra.

Cifrado Asimétrico (RSA con Par de Llaves - Pública y Privada): OpenSSL es maestro en esto. Usas una llave pública para cifrar un mensaje (que cualquiera puede enviar) y solo la otra persona con la llave privada puede abrirlo y leerlo. Esto protege los datos mientras viajan por redes inseguras.

<br>


## ⚙️ Otras Aplicaciones de OpenSSL en Ciberseguridad 
OpenSSL se utiliza ampliamente en el campo de la ciberseguridad para una amplia gama de aplicaciones, entre las que se incluyen:

1. Seguridad del servidor web: OpenSSL se utiliza comúnmente para habilitar HTTPS (HTTP sobre SSL/TLS) en servidores web, proporcionando cifrado y autenticación para una comunicación segura entre clientes y servidores.<br>

2. Seguridad del correo electrónico: OpenSSL se utiliza para proteger las comunicaciones de correo electrónico, permitiendo el uso de S/MIME (Secure/Multipurpose Internet Mail Extensions) y PGP (Pretty Good Privacy) para el cifrado de correo electrónico y firmas digitales.



## 🛠️ Configuración
 ¿Tienes que poner la ubicación del archivo de WampServer o puedes dejarlo sin eso?
1. Si estás usando WampServer en lugar de XAMPP:
A diferencia de XAMPP (que a veces no encuentra automáticamente el archivo de configuración y por eso obliga a declararlo manualmente con la ruta), WampServer suele tener OpenSSL y su archivo openssl.cnf ya configurados correctamente en las rutas por defecto de PHP.
2. En la  versión de Xampp, verificar que tenga habilitada la extensión de extension=openssl
   El php.ini deben abrirlo con WordPad o Bloc de Notas y buscar la línea ;extension=openssl --> si tiene ";" eliminarlo y bajar los servicios y luego levantarlos  
3. Si necesitas poner la ruta en WampServer:
Si por alguna razón te llega a dar error y necesitas indicárselo explícitamente, tienes que usar la ruta donde WampServer tiene su propio PHP/OpenSSL, no la de XAMPP ni la de C:\OpenSSL-Win64 (a menos que hayas instalado OpenSSL por separado).
Por lo general, en WampServer la ruta apunta a la carpeta de bin de la versión de PHP que estés usando, por ejemplo algo así:'config' => 'C:\wamp64\bin\php\php8.x.x\extras\ssl\openssl.cnf' (o dentro de la carpeta donde esté instalado tu Wamp).
4. En algunos ejemlos deben asegurarse de establecer la ruta dónde instalarón el OpenSSL --> openssl.cnf (Ubicación de OpenSSL en su PC)
Ejemplo dónde se ubica l rut del openssl.cnf:

```bash
   $configArgs = array(
    'config' => 'C:\OpenSSL-Win64\bin\cnf\openssl.cnf', //<-- esta ruta es necesaria si trabajas con XAMPP
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA ); 
```
<br>

### 🔍 Verificar si la versión WampServer o XAMPP tienen habilitado openSSL

```bash
<?PHP 
phpinfo();
?>
```
<img width="1337" height="318" alt="image" src="https://github.com/user-attachments/assets/9c841a86-0abd-45df-b4d0-e613728604bc" />


Verificar el archivo [PHP.ini], que no tenga el punto y coma que represent un comentario.

```bash
extension=openssl
```


### 🚀 Ejecutar los siguientes ejemplos y recrearlos
1. Encriptacion.php (Crear un formulario)
2. FirmaMensaje.php (Crear un formulario)
3. Firma7.php  (Crear un formulario)
4. FirmaOtra.php  (Crear un formulario)
   <br>


<br>

## 🔢 Estadísticas

 ![Creado](https://img.shields.io/badge/Creado-08--04--2025-blue)
![GitHub watchers](https://img.shields.io/github/watchers/Salomon2514/EjemplosJson.svg?style=social)
![Visitas](https://visitor-badge.laobi.icu/badge?page_id=Salomon2514.SeguridadOpenSSL)

## 📚 Cómo usar este repositorio

1. Descarga o clona el repositorio, ubica una carpeta en www -> para WampServer o htdocs  si es Xampp
  ```bash
   [https://github.com/Salomon2514/SeguridadOpenSSL.git]
2. Para correrlos edita el URL de su navegador:  http://127.1.1.1/Carpeta/ o  http://localhost/Carpeta/
```

## 👨‍🏫 Autor

**Irina Fong**  
Docente de Programación  
Universidad Tecnológica de Panamá  

📧 **Email:** irina.fong@utp.ac.pa  
📧 **Email:** irinafong@gmail.com<br>
🌐 **GitHub:**(https://github.com/Salomon2514)  


## 📖 Referencias

- [https://www.youtube.com/watch?v=cBa87N_BZ4s&t=40s]
- [https://slproweb.com/products/Win32OpenSSL.html]



