<?php
/* Libsodium (ext-sodium): cifrado autenticado (secretbox) y firma digital Ed25519 */

$texto = 'Dato sensible de prueba';
$error = '';
$salida = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $texto = trim($_POST['texto'] ?? '');

    if ($texto === '' || strlen($texto) > 200) {
        $error = 'El texto es obligatorio y debe tener máximo 200 caracteres.';
    } elseif (!function_exists('sodium_crypto_secretbox')) {
        $error = 'La extensión sodium no está habilitada en php.ini.';
    } else {
        $lonNonce = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES; // 24 bytes

        // 1) Cifrado autenticado (XSalsa20-Poly1305)
        $clave   = sodium_crypto_secretbox_keygen();     // 32 bytes aleatorios
        $nonce   = random_bytes($lonNonce);              // único por mensaje
        $cifrado = sodium_crypto_secretbox($texto, $nonce, $clave);
        $paquete = base64_encode($nonce . $cifrado);     // nonce + texto cifrado

        // Descifrado: se separa el nonce del texto cifrado
        $bin      = base64_decode($paquete, true);
        $nonce2   = substr($bin, 0, $lonNonce);
        $cifrado2 = substr($bin, $lonNonce);
        $claro    = sodium_crypto_secretbox_open($cifrado2, $nonce2, $clave);

        // Prueba de integridad: se altera un solo bit del paquete
        $alterado = $bin;
        $ult = strlen($alterado) - 1;
        $alterado[$ult] = chr(ord($alterado[$ult]) ^ 1);
        $claroAlterado = sodium_crypto_secretbox_open(substr($alterado, $lonNonce), $nonce2, $clave);

        // 2) Firma digital Ed25519
        $par          = sodium_crypto_sign_keypair();
        $clavePrivada = sodium_crypto_sign_secretkey($par);
        $clavePublica = sodium_crypto_sign_publickey($par);
        $firma        = sodium_crypto_sign_detached($texto, $clavePrivada);
        $firmaOk      = sodium_crypto_sign_verify_detached($firma, $texto, $clavePublica);
        $firmaAlt     = sodium_crypto_sign_verify_detached($firma, $texto . 'x', $clavePublica);

        $salida = array(
            'Paquete cifrado (nonce + texto cifrado, Base64)' => $paquete,
            'Texto descifrado' => ($claro === false ? 'ERROR: no se pudo descifrar' : $claro),
            'Descifrado con un bit alterado' => ($claroAlterado === false
                ? 'Rechazado: la etiqueta de autenticación (MAC) no coincide'
                : 'Aceptado (esto no debería ocurrir)'),
            'Firma Ed25519 (hexadecimal)' => bin2hex($firma),
            'Verificación de la firma original' => ($firmaOk ? 'Firma válida' : 'Firma inválida'),
            'Verificación con el texto modificado' => ($firmaAlt ? 'Firma válida (no debería ocurrir)' : 'Firma inválida: el texto fue alterado'),
        );

        // Se borra la clave de la memoria cuando ya no se necesita
        sodium_memzero($clave);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Libsodium: cifrado autenticado y firma Ed25519</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 32px 16px; font-family: "Segoe UI", Arial, sans-serif;
               background: linear-gradient(135deg, #e8edf9 0%, #f7f3fb 100%); color: #1f2937; }
        .card { max-width: 760px; margin: 0 auto; background: #ffffff; padding: 32px 36px;
                border-radius: 14px; box-shadow: 0 10px 30px rgba(44, 62, 117, 0.15);
                border-top: 6px solid #4f46e5; }
        h1 { margin: 0 0 24px; font-size: 1.6rem; color: #2c3e75; }
        label { font-weight: 600; font-size: 0.95rem; color: #374151; }
        input[type="text"], input[type="email"], textarea {
            width: 100%; margin-top: 6px; padding: 10px 12px; font-size: 1rem; font-family: inherit;
            border: 1px solid #cbd5e1; border-radius: 8px; background: #f8fafc; transition: all .15s; }
        input:focus, textarea:focus { outline: none; border-color: #4f46e5; background: #fff;
                                      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.18); }
        p { margin: 0 0 16px; line-height: 1.5; }
        button { padding: 11px 26px; font-size: 1rem; font-weight: 600; color: #fff; cursor: pointer;
                 border: none; border-radius: 8px; background: linear-gradient(135deg, #4f46e5, #7c3aed);
                 box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35); transition: transform .1s; }
        button:hover { transform: translateY(-1px); }
        .msg { padding: 12px 16px; border-radius: 8px; border-left: 5px solid; margin-top: 20px; }
        .msg.ok { background: #ecfdf5; border-color: #10b981; color: #065f46; }
        .msg.error { background: #fef2f2; border-color: #ef4444; color: #991b1b; }
        .msg.info { background: #eff6ff; border-color: #3b82f6; color: #1e3a8a; }
        pre.cert { margin-top: 20px; padding: 16px; background: #0f172a; color: #a7f3d0; border-radius: 10px;
                   font-family: Consolas, "Courier New", monospace; font-size: 0.8rem;
                   overflow-x: auto; white-space: pre-wrap; word-break: break-all; }
    </style>
</head>
<body>
<main class="card">
    <h1>Libsodium: cifrado autenticado y firma Ed25519</h1>

    <form method="post" action="">
        <p>
            <label for="texto">Texto a proteger:</label><br>
            <input type="text" id="texto" name="texto" maxlength="200" size="50"
                   value="<?= htmlspecialchars($texto) ?>" required>
        </p>
        <button type="submit">Cifrar, firmar y verificar</button>
    </form>

    <?php if ($error !== ''): ?>
        <p class="msg error"><strong><?= htmlspecialchars($error) ?></strong></p>
    <?php elseif (!empty($salida)): ?>
        <?php foreach ($salida as $titulo => $valor): ?>
            <p class="msg info"><strong><?= htmlspecialchars($titulo) ?>:</strong><br>
               <span style="word-break: break-all;"><?= htmlspecialchars($valor) ?></span></p>
        <?php endforeach; ?>
    <?php endif; ?>
</main>
</body>
</html>
