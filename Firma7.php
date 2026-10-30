<?php
/* Crea un par de claves, firma unos datos, guarda todo en keys/ y verifica la firma */

// Ruta del openssl.cnf de WampServer (se usa solo si el archivo existe)
$rutaConf = 'C:\wamp64\bin\php\php8.3.28\extras\ssl\openssl.cnf';

$configArgs = array(
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA
);
if (file_exists($rutaConf)) {
    $configArgs['config'] = $rutaConf;
}

$datos = 'Este texto será firmado. Thanks for your attention :)';
$error = '';
$resultado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = trim($_POST['datos'] ?? '');

    if ($datos === '' || strlen($datos) > 500) {
        $error = 'El texto es obligatorio y debe tener máximo 500 caracteres.';
    } else {
        // Se crean dos claves aparejadas: una pública y otra privada
        $resourceNewKeyPair = openssl_pkey_new($configArgs);
        if (!$resourceNewKeyPair) {
            $error = 'Puede que tengas problemas con la ruta del openssl.cnf. ' . openssl_error_string();
        } else {
            // Clave pública como string
            $details = openssl_pkey_get_details($resourceNewKeyPair);
            $publicKeyPem = $details['key'];

            // Clave privada como string
            if (!openssl_pkey_export($resourceNewKeyPair, $privateKeyPem, null, $configArgs)) {
                $error = openssl_error_string();
            } else {
                // Guardar las claves en disco
                if (!is_dir(__DIR__ . '/keys')) {
                    mkdir(__DIR__ . '/keys');
                }
                file_put_contents(__DIR__ . '/keys/private_key.pem', $privateKeyPem);
                file_put_contents(__DIR__ . '/keys/public_key.pem', $publicKeyPem);

                // Leer la clave privada desde el archivo generado
                $privateKeyPem = file_get_contents(__DIR__ . '/keys/private_key.pem');
                $resourcePrivateKey = openssl_get_privatekey($privateKeyPem);

                // Crear la firma
                if (!openssl_sign($datos, $firma, $resourcePrivateKey, OPENSSL_ALGO_SHA256)) {
                    $error = openssl_error_string();
                } else {
                    file_put_contents(__DIR__ . '/keys/signature.dat', $firma);

                    // Comprobar la firma
                    if (openssl_verify($datos, $firma, $publicKeyPem, 'sha256WithRSAEncryption') === 1) {
                        $resultado = 'la firma es valida y los datos son confiables';
                    } else {
                        $resultado = 'la firma es invalida y/o los datos fueron alterados';
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Firma digital con claves RSA</title>
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
    <h1>Firma digital con claves RSA</h1>

    <form method="post" action="">
        <p>
            <label for="datos">Datos que se quieren firmar:</label><br>
            <textarea id="datos" name="datos" rows="4" cols="60" maxlength="500" required><?= htmlspecialchars($datos) ?></textarea>
        </p>
        <button type="submit">Firmar y verificar</button>
    </form>

    <?php if ($error !== ''): ?>
        <p class="msg error"><strong><?= htmlspecialchars($error) ?></strong></p>
    <?php elseif ($resultado !== ''): ?>
        <p class="msg <?= strpos($resultado, 'invalida') !== false ? 'error' : 'ok' ?>"><?= htmlspecialchars($resultado) ?></p>
    <?php endif; ?>
</main>
</body>
</html>
