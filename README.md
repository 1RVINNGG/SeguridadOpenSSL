# Laboratorio – Seguridad en PHP con OpenSSL y Libsodium (seguridadopenssl1)

## Objetivo

Aplicar la extensión **OpenSSL** y la librería **Libsodium (ext-sodium)** en PHP para proteger datos sensibles según los lineamientos de **OWASP** (*Cryptographic Failures*), implementando mediante formularios web: firma digital con claves RSA, generación de certificados X.509, firma y verificación de mensajes, cifrado simétrico AES-128-CBC y cifrado autenticado con firma Ed25519.

---

## Requisitos Previos

| Tecnología | Versión / Detalle |
|---|---|
| PHP | 8.3.28 |
| WampServer | 64-bit, entorno de desarrollo local |
| Apache | Servidor web de WAMP |
| Extensión OpenSSL | `extension=openssl` habilitada en `php.ini` |
| Extensión Sodium | `extension=sodium` habilitada en `php.ini` (incluida desde PHP 7.2) |
| Visual Studio Code | Editor de código recomendado |
| Navegador | Brave / Chrome / Firefox |
| Windows | Sistema operativo |

---

## Introducción: Confidencialidad e Integridad (OWASP)

Los datos sensibles (contraseñas, tokens, registros médicos, secretos comerciales) nunca deben almacenarse ni viajar en texto plano. OpenSSL y Libsodium ayudan a resolver dos pilares fundamentales:

| Pilar | Descripción | En este laboratorio |
|---|---|---|
| **Confidencialidad (Cifrado)** | Solo quien tiene la clave puede leer los datos | `Encriptacion.php` (AES-128-CBC), `Sodium.php` (secretbox) |
| **Integridad (Firmas y Hashes)** | Garantiza que la información no fue alterada por terceros | `Firma7.php`, `FirmaMensaje.php`, `FirmaOtra.php`, `Sodium.php` (Ed25519) |

Flujo general de una petición:

```
Navegador -> formulario (POST) -> Script PHP -> Validación del servidor -> OpenSSL / Sodium -> Resultado en pantalla
                                                        |
                                                        v
                                         keys/ y keysCert/ (llaves y certificado)
```

---

## Estructura del Proyecto

```
seguridadopenssl1/
├── Encriptacion.php     # Cifrado simétrico AES-128-CBC
├── Firma7.php           # Par de claves RSA, firma y verificación
├── FirmaOtra.php        # Llave privada + certificado X.509 autofirmado
├── FirmaMensaje.php     # Firma de mensaje con llave privada y verificación con certificado
├── Sodium.php           # Libsodium: cifrado autenticado + firma Ed25519
├── caps/                # Capturas de pantalla (evidencias)
│   ├── firma7.png
│   ├── firmaotra.png
│   ├── firmamensaje.png
│   ├── encriptacion.png
│   └── sodium.png
├── keys/                # (se crea automáticamente) private_key.pem, public_key.pem, signature.dat
└── keysCert/            # (se crea automáticamente) privkey.pem, certout.csr
```

> Las carpetas `keys/` y `keysCert/` las crean los propios scripts al ejecutarse.

---

## Instalación y Configuración

### 1. Copiar el proyecto

Colocar la carpeta del proyecto dentro del directorio web de WampServer:

```
C:\wamp64\www\seguridadopenssl1
```

### 2. Verificar las extensiones en PHP

En WampServer: clic en el ícono → **PHP → Extensiones PHP** y confirmar que estén marcadas:

- `openssl`
- `sodium`

Para comprobarlo desde la terminal:

```powershell
php -m | findstr /i "openssl sodium"
```

### 3. Configurar la ruta de `openssl.cnf` (Windows)

`Firma7.php` y `FirmaOtra.php` buscan el archivo de configuración de OpenSSL de WampServer y lo usan **solo si existe**:

```php
$rutaConf = 'C:\wamp64\bin\php\php8.3.28\extras\ssl\openssl.cnf';

$configArgs = array(
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA
);
if (file_exists($rutaConf)) {
    $configArgs['config'] = $rutaConf;
}
```

Si usas otra versión de PHP o XAMPP, ajusta `$rutaConf` (ej. `C:\xampp\php\extras\ssl\openssl.cnf`) o elimínala para que PHP lo resuelva por sí solo.

---

## Ejecución del proyecto

Con WampServer en verde (Apache activo), abrir en el navegador:

| Script | URL |
|---|---|
| Firma digital RSA | http://localhost/seguridadopenssl1/Firma7.php |
| Certificado X.509 | http://localhost/seguridadopenssl1/FirmaOtra.php |
| Firmar mensaje | http://localhost/seguridadopenssl1/FirmaMensaje.php |
| Encriptación AES | http://localhost/seguridadopenssl1/Encriptacion.php |
| Libsodium | http://localhost/seguridadopenssl1/Sodium.php |

**Orden recomendado:** `FirmaOtra.php` debe ejecutarse **antes** que `FirmaMensaje.php`, porque este último necesita `keysCert/privkey.pem` y `keysCert/certout.csr`.

---

## Documentación técnica de funciones

### 1. `Firma7.php` – Firma digital con claves RSA

| Campo | Detalle |
|---|---|
| **Nombre de la función** | `openssl_pkey_new()`, `openssl_pkey_export()`, `openssl_sign()`, `openssl_verify()` |
| **Propósito general** | Generar un par de claves RSA, firmar un texto con la clave privada y verificar la firma con la clave pública |
| **Parámetros recibidos** | `datos` (textarea, máx. 500 caracteres) vía POST; `$configArgs` (`private_key_bits` = 2048, `private_key_type` = RSA); algoritmo `OPENSSL_ALGO_SHA256` |
| **Valor devuelto** | `openssl_verify()` devuelve `1` (firma válida), `0` (inválida) o `-1` (error). Se muestra un mensaje en pantalla y se guardan `keys/private_key.pem`, `keys/public_key.pem` y `keys/signature.dat` |
| **Proceso criptográfico** | Se genera el par RSA-2048 → se calcula el hash SHA-256 de los datos → el hash se firma con la clave privada → se verifica con la clave pública. Si el texto cambia, la verificación falla |

### 2. `FirmaOtra.php` – Certificado X.509 autofirmado

| Campo | Detalle |
|---|---|
| **Nombre de la función** | `openssl_pkey_new()`, `openssl_csr_new()`, `openssl_csr_sign()`, `openssl_x509_export()` |
| **Propósito general** | Crear una llave privada y un certificado digital X.509 autofirmado válido por 365 días |
| **Parámetros recibidos** | `countryName` (2 letras), `localityName`, `organizationName`, `commonName`, `emailAddress` (formulario POST); `OPEN_SSL_CERT_DAYS_VALID` = 365 |
| **Valor devuelto** | Certificado en formato PEM mostrado en pantalla; archivos `keysCert/certout.csr` (certificado) y `keysCert/privkey.pem` (llave privada) |
| **Proceso criptográfico** | Se genera un par RSA-2048 → se crea la solicitud de certificado (CSR) con los datos del sujeto (DN) → se autofirma con la propia llave privada → se exporta el certificado X.509 |
| **Validaciones** | País con regex `^[A-Z]{2}$`, campos obligatorios de máx. 64 caracteres y correo con `FILTER_VALIDATE_EMAIL` |

### 3. `FirmaMensaje.php` – Firmar y verificar un mensaje

| Campo | Detalle |
|---|---|
| **Nombre de la función** | `openssl_pkey_get_private()`, `openssl_sign()`, `openssl_pkey_get_public()`, `openssl_verify()` |
| **Propósito general** | Firmar un mensaje con la llave privada generada en `FirmaOtra.php` y verificarlo con el certificado |
| **Parámetros recibidos** | `mensaje` (máx. 500 caracteres) vía POST; archivos `keysCert/privkey.pem` y `keysCert/certout.csr` |
| **Valor devuelto** | Firma en Base64 y mensaje de resultado: ✅ válida / ❌ inválida / ⚠ error |
| **Proceso criptográfico** | Hash SHA-256 del mensaje → firma con la llave privada → codificación Base64 → decodificación y verificación con la clave pública extraída del certificado |

### 4. `Encriptacion.php` – Cifrado simétrico AES-128-CBC

| Campo | Detalle |
|---|---|
| **Nombre de la función** | `openssl_encrypt()`, `openssl_decrypt()`, `openssl_cipher_iv_length()`, `openssl_random_pseudo_bytes()` |
| **Propósito general** | Cifrar un texto con AES-128-CBC y descifrarlo para comprobar el proceso |
| **Parámetros recibidos** | `texto` (máx. 200 caracteres) y `clave` (exactamente 16 caracteres); cifrado `AES-128-CBC`; `$iv` aleatorio de 16 bytes |
| **Valor devuelto** | Texto cifrado en Base64 y texto descifrado |
| **Proceso criptográfico** | Se genera un vector de inicialización (IV) aleatorio → se cifra con AES en modo CBC usando la clave de 128 bits → se descifra con la misma clave e IV |

### 5. `Sodium.php` – Libsodium: cifrado autenticado y firma Ed25519

| Campo | Detalle |
|---|---|
| **Nombre de la función** | `sodium_crypto_secretbox()`, `sodium_crypto_secretbox_open()`, `sodium_crypto_sign_keypair()`, `sodium_crypto_sign_detached()`, `sodium_crypto_sign_verify_detached()`, `sodium_memzero()` |
| **Propósito general** | Cifrar datos garantizando confidencialidad e integridad en una sola llamada, y firmarlos digitalmente con Ed25519 |
| **Parámetros recibidos** | `texto` (máx. 200 caracteres) vía POST; clave de 32 bytes (`sodium_crypto_secretbox_keygen()`); nonce de 24 bytes (`random_bytes()`) |
| **Valor devuelto** | Paquete cifrado (nonce + texto cifrado en Base64), texto descifrado, resultado con un bit alterado, firma en hexadecimal y verificación de la firma original y modificada |
| **Proceso criptográfico** | **Secretbox:** XSalsa20 (cifrado) + Poly1305 (MAC). Si se altera un solo bit, el descifrado es rechazado. **Firma:** Ed25519 con par de claves generado en cada ejecución; cualquier cambio en el texto invalida la firma. La clave se borra de memoria con `sodium_memzero()` |

---

## Investigación: Libsodium (ext-sodium)

Libsodium está integrada de forma nativa en PHP desde la versión 7.2 y fue diseñada para que la criptografía moderna sea **segura por defecto y difícil de usar mal**.

### Principales ventajas

| Ventaja | Descripción |
|---|---|
| **Resistente a errores de uso** | Gestiona automáticamente aleatoriedad y nonces/IV, evitando errores comunes |
| **Algoritmos modernos** | XSalsa20-Poly1305, Ed25519, Argon2 (`sodium_crypto_pwhash`) |
| **Cifrado autenticado** | Confidencialidad + integridad en una sola llamada (`sodium_crypto_secretbox`) |
| **Cero dependencias extra** | Viene incluida en WampServer y XAMPP; no requiere `openssl.cnf` |
| **Rendimiento** | Implementaciones rápidas y resistentes a ataques de canal lateral |

### OpenSSL vs. Libsodium

| Característica | OpenSSL | Libsodium (ext-sodium) |
|---|---|---|
| **Propósito principal** | Protocolos de red, certificados digitales y criptografía asimétrica (RSA) | Criptografía moderna simétrica, firmas y hashing/derivación de alta seguridad |
| **Complejidad** | Alta: requiere configurar paddings, longitudes, modos, IV | Baja: funciones simples y seguras desde la primera línea |
| **Uso recomendado** | HTTPS, llaves públicas/privadas y certificados X.509 | Contraseñas y cifrado de datos sensibles en base de datos |

---

## Resultado

### Firma7 – Firma digital con claves RSA
Se ingresó el texto a firmar; el sistema generó el par de claves, firmó con SHA-256 y verificó correctamente.

![Firma7](caps/firma7.png)

### FirmaOtra – Creación de certificado y llave privada
Formulario con país, localidad, organización, nombre común y correo; se generó el certificado X.509 en formato PEM.

![FirmaOtra](caps/firmaotra.png)

### FirmaMensaje – Firmar y verificar un mensaje
Se firmó el mensaje con la llave privada, se mostró la firma en Base64 y se verificó con el certificado.

![FirmaMensaje](caps/firmamensaje.png)

### Encriptacion – AES-128-CBC
Se cifró el texto con una clave de 16 caracteres y se descifró obteniendo el texto original.

![Encriptacion](caps/encriptacion.png)

### Sodium – Cifrado autenticado y firma Ed25519
Se cifró y descifró el texto, se rechazó el paquete con un bit alterado (MAC no coincide) y se verificó la firma Ed25519 (válida con el texto original, inválida con el texto modificado).

![Sodium](caps/sodium.png)

---

## Buenas prácticas de seguridad aplicadas

| Práctica | Dónde se aplica |
|---|---|
| Escape de salida con `htmlspecialchars()` (previene XSS) | Todos los scripts |
| Validación de entradas en el servidor (longitud, formato, regex) | Todos los scripts |
| IV / nonce aleatorio en cada operación | `Encriptacion.php`, `Sodium.php` |
| Firma con SHA-256 (no algoritmos obsoletos) | `Firma7.php`, `FirmaMensaje.php` |
| Detección de manipulación (MAC y firmas) | `Sodium.php` |
| Borrado de la clave de memoria | `sodium_memzero()` en `Sodium.php` |
| Manejo de errores con `openssl_error_string()` | `Firma7.php`, `FirmaOtra.php`, `FirmaMensaje.php` |

> ⚠️ **Advertencia:** las llaves privadas de `keys/` y `keysCert/` son solo para fines académicos. **No deben subirse a un repositorio público**; se recomienda agregarlas a `.gitignore`:
>
> ```
> keys/
> keysCert/
> ```

---

## Dificultades Encontradas y Soluciones

### Dificultad 1: La ruta de `openssl.cnf` no funcionaba
Al generar las llaves, `openssl_pkey_new()` fallaba porque la ruta del archivo `openssl.cnf` (por ejemplo `C:\OpenSSL-Win64\bin\cnf\openssl.cnf` o en *Program Files*) no existía en el equipo.

**Solución:** Verificar la existencia del archivo con `file_exists()` y usar la ruta de WampServer (`C:\wamp64\bin\php\php8.3.28\extras\ssl\openssl.cnf`). Si no existe, se omite el parámetro `config` y PHP usa el valor predeterminado del sistema.

### Dificultad 2: `FirmaMensaje.php` mostraba que no existían la llave privada y el certificado
El script depende de los archivos generados por `FirmaOtra.php`.

**Solución:** Ejecutar primero `FirmaOtra.php` para crear `keysCert/privkey.pem` y `keysCert/certout.csr`. El script ahora muestra un mensaje claro si faltan.

### Dificultad 3: Extensión Sodium no disponible
`Sodium.php` mostraba "La extensión sodium no está habilitada en php.ini".

**Solución:** Habilitar `extension=sodium` desde el menú de WampServer (PHP → Extensiones PHP) y reiniciar los servicios.

### Dificultad 4: No se puede descifrar posteriormente en `Encriptacion.php`
El IV se genera aleatoriamente en cada ejecución y no se guarda, por lo que el descifrado solo es posible dentro de la misma petición.

**Solución (mejora):** Para uso real, concatenar el IV al texto cifrado (como hace `Sodium.php` con el nonce) o usar un modo autenticado como AES-256-GCM / `sodium_crypto_secretbox`.

---

## Conclusiones

- OpenSSL permite implementar firmas digitales, certificados y cifrado simétrico, pero exige configurar correctamente rutas, algoritmos y vectores de inicialización.
- La firma digital garantiza **integridad y autenticidad**: al modificar el texto, la verificación falla.
- AES-128-CBC aporta **confidencialidad**, pero no protege contra alteraciones; por eso Libsodium (`secretbox`) resulta más seguro al incluir autenticación (MAC).
- Libsodium simplifica la criptografía y reduce los errores de implementación, siendo la opción recomendada para proteger datos sensibles en aplicaciones PHP modernas.
- Validar las entradas en el servidor y escapar las salidas es tan importante como el cifrado para una programación segura según OWASP.

---

## Referencias

- [OWASP Top 10 – A02: Cryptographic Failures](https://owasp.org/Top10/)
- [Documentación PHP – Extensión OpenSSL](https://www.php.net/manual/es/book.openssl.php)
- [Documentación PHP – Extensión Sodium](https://www.php.net/manual/es/book.sodium.php)
- [Libsodium – Documentación oficial](https://doc.libsodium.org/)
- Guía de Laboratorio "Seguridad en PHP con OpenSSL", Ing. Irina Fong, UTP.

---

## Fecha de Ejecución

29 de octubre de 2026

Este laboratorio ha sido desarrollado por el estudiante de la Universidad Tecnológica de Panamá:

- **Nombre:** Irving J. Cruz
- **Curso:** Desarrollo Web
- **Instructor del Laboratorio:** Irina Fong