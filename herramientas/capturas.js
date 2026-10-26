// Genera las capturas de evidencia del laboratorio llenando y enviando cada formulario en Edge.
const puppeteer = require('puppeteer-core');
const fs = require('fs');

const BASE = 'http://localhost/OPENSSL/';
const SALIDA = 'C:/wamp64/www/OPENSSL/capturas/';
const EDGE = 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe';

(async () => {
  fs.mkdirSync(SALIDA, { recursive: true });
  const browser = await puppeteer.launch({ executablePath: EDGE, headless: true, args: ['--lang=es-PA'] });
  const page = await browser.newPage();
  await page.emulateMediaFeatures([{ name: 'prefers-color-scheme', value: 'light' }]);
  await page.setViewport({ width: 1280, height: 900, deviceScaleFactor: 1 });

  const ir = (ruta) => page.goto(BASE + ruta, { waitUntil: 'load' });
  const llenar = async (valores) => {
    for (const [sel, valor] of Object.entries(valores)) {
      await page.$eval(sel, (el, v) => {
        if (el.type === 'checkbox') el.checked = !!v; else el.value = v;
      }, valor);
    }
  };
  const enviar = async (selBoton) => {
    await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.click(selBoton)]);
  };
  const captura = async (nombre, selector) => {
    const ruta = SALIDA + nombre + '.png';
    if (selector) await (await page.$(selector)).screenshot({ path: ruta });
    else await page.screenshot({ path: ruta, fullPage: true });
    console.log('✔', nombre);
  };

  // Dificultad: scripts originales con la ruta C:\OpenSSL-Win64 inexistente
  await ir('originales/firma7.php'); await captura('00a_error_original_firma7');
  await ir('originales/firmaOtra.php'); await captura('00b_error_original_firmaOtra');

  await ir('index.php'); await captura('01_inicio');
  await ir('Informacion.php'); await captura('02_informacion');

  // Encriptación
  await ir('Encriptacion.php');
  await llenar({ '#texto': 'Cédula: 8-1038-857 · Tarjeta 4111-1111-1111-1111', '#clave': 'clave1234567890', '#algoritmo': 'aes-128-cbc' });
  await enviar('form:has(input[value="cifrar"]) button'); await captura('03_encriptacion_cifrar_aes128cbc');
  await enviar('form:has(input[value="descifrar"]) button'); await captura('04_encriptacion_descifrar');
  await llenar({ '#algoritmo': 'aes-256-gcm' });
  await enviar('form:has(input[value="cifrar"]) button'); await captura('05_encriptacion_cifrar_aes256gcm');
  await llenar({ 'input[name="alterar"]': true });
  await enviar('form:has(input[value="descifrar"]) button'); await captura('06_encriptacion_gcm_alterado');

  // Cifrado + HMAC
  await ir('encriptacionOpenssl_2.php');
  await enviar('button[value="cifrar"]'); await captura('07_cifrado_hmac_cifrar');
  await enviar('button[value="descifrar"]'); await captura('08_cifrado_hmac_verificar');
  await llenar({ 'input[name="alterar"]': true });
  await enviar('button[value="descifrar"]'); await captura('09_cifrado_hmac_alterado');

  // firma7
  await ir('firma7.php');
  await llenar({ '#datos': 'Este texto será firmado. Thanks for your attention :)', 'input[name="regenerar"]': true });
  await enviar('form:has(input[value="firmar"]) button'); await captura('10_firma7_firmar');
  await enviar('form:has(input[value="verificar"]) button'); await captura('11_firma7_verificar_valida');
  await llenar({ '#datos2': 'Este texto será firmado. Thanks for your attention :( ' });
  await enviar('form:has(input[value="verificar"]) button'); await captura('12_firma7_verificar_alterado');

  // firmaOtra -> FirmarMensaje
  await ir('firmaOtra.php');
  await llenar({ '#emailAddress': '' });
  await enviar('form button'); await captura('13_firmaOtra_certificado');
  await ir('FirmarMensaje.php');
  await llenar({ '#mensaje': 'Este es un mensaje confidencial' });
  await enviar('form:has(input[value="firmar"]) button'); await captura('14_FirmarMensaje_firmar');
  await enviar('form:has(input[value="verificar"]) button'); await captura('15_FirmarMensaje_verificar_valida');
  await llenar({ '#mensaje2': 'Este es un mensaje confidencial modificado' });
  await enviar('form:has(input[value="verificar"]) button'); await captura('16_FirmarMensaje_verificar_alterado');

  // firmaDiprena
  await ir('firmaDiprena.php');
  await enviar('button[value="firmar"]'); await captura('17_firmaDiprena_firmar');
  await llenar({ '#Salario': '9850.00' });
  await enviar('button[value="verificar"]'); await captura('18_firmaDiprena_salario_alterado');

  // firma4 y firmaDigital
  await ir('firma4.php'); await enviar('form button'); await captura('19_firma4_csr');
  await ir('firmaDigital.php'); await enviar('form button'); await captura('20_firmaDigital_rsa_oaep');

  // Sodium
  await ir('sodium.php'); await captura('21_sodium_pagina');
  await enviar('#secretbox button[value="cifrar"]'); await captura('22_sodium_secretbox_cifrar', '#secretbox');
  await enviar('#secretbox button[value="descifrar"]'); await captura('23_sodium_secretbox_descifrar', '#secretbox');
  await llenar({ '#secretbox input[name="alterar"]': true });
  await enviar('#secretbox button[value="descifrar"]'); await captura('24_sodium_secretbox_alterado', '#secretbox');
  await enviar('#firma button[value="firmar"]'); await captura('25_sodium_ed25519_firmar', '#firma');
  await enviar('#firma button[value="verificar"]'); await captura('26_sodium_ed25519_verificar', '#firma');
  await llenar({ '#b_mensaje': 'Transferencia autorizada: B/. 9,500.00 a la cuenta 04-123-456' });
  await enviar('#firma button[value="verificar"]'); await captura('27_sodium_ed25519_alterado', '#firma');
  await enviar('#pwhash button[value="hash"]'); await captura('28_sodium_argon2id_hash', '#pwhash');
  await enviar('#pwhash button[value="verificar"]'); await captura('29_sodium_argon2id_correcta', '#pwhash');
  await llenar({ '#c_intento': 'contraseña-del-usuario-2026' });
  await enviar('#pwhash button[value="verificar"]'); await captura('30_sodium_argon2id_incorrecta', '#pwhash');
  await enviar('#sealed button'); await captura('31_sodium_sealed_box', '#sealed');
  await enviar('#rendimiento button'); await captura('32_sodium_rendimiento', '#rendimiento');

  await browser.close();
})().catch((e) => { console.error(e); process.exit(1); });
