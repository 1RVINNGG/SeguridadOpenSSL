<?php
/* Genera la llave privada y un certificado X.509 autofirmado (keysCert/) */

// Ruta del openssl.cnf de WampServer (se usa solo si el archivo existe)
$rutaConf = 'C:\wamp64\bin\php\php8.3.28\extras\ssl\openssl.cnf';

define('OPEN_SSL_CERT_DAYS_VALID', 365);

$configArgs = array(
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA
);
if (file_exists($rutaConf)) {
    $configArgs['config'] = $rutaConf;
}

$dn = array(
    'countryName'      => 'PA',
    'localityName'     => 'PA-8',
    'organizationName' => 'MEF',
    'commonName'       => 'Irina Fong',
    'emailAddress'     => 'dreamsweb7@gmail.com'
);
$errores = array();
$certout = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dn['countryName']      = strtoupper(trim($_POST['countryName'] ?? ''));
    $dn['localityName']     = trim($_POST['localityName'] ?? '');
    $dn['organizationName'] = trim($_POST['organizationName'] ?? '');
    $dn['commonName']       = trim($_POST['commonName'] ?? '');
    $dn['emailAddress']     = trim($_POST['emailAddress'] ?? '');

    // Validaciones de lista blanca en el servidor
    if (!preg_match('/^[A-Z]{2}$/', $dn['countryName'])) {
        $errores[] = 'El país debe ser un código de 2 letras (ej. PA).';
    }
    foreach (array('localityName', 'organizationName', 'commonName') as $campo) {
        if ($dn[$campo] === '' || strlen($dn[$campo]) > 64) {
            $errores[] = "El campo $campo es obligatorio (máximo 64 caracteres).";
        }
    }
    if (!filter_var($dn['emailAddress'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo electrónico no es válido.';
    }

    if (empty($errores)) {
        $resourceNewKeyPair = openssl_pkey_new($configArgs);
        if (!$resourceNewKeyPair) {
            $errores[] = 'Error al generar la clave: ' . openssl_error_string();
        } elseif (!openssl_pkey_export($resourceNewKeyPair, $privkey, null, $configArgs)) {
            $errores[] = 'Error al exportar la clave privada: ' . openssl_error_string();
        } else {
            $csr = openssl_csr_new($dn, $resourceNewKeyPair, $configArgs);
            if (!$csr) {
                $errores[] = 'Error al crear la CSR: ' . openssl_error_string();
            } else {
                $sscert = openssl_csr_sign($csr, null, $resourceNewKeyPair, OPEN_SSL_CERT_DAYS_VALID, $configArgs);
                if (!$sscert) {
                    $errores[] = 'Error al firmar la CSR: ' . openssl_error_string();
                } else {
                    openssl_x509_export($sscert, $certout);
                    if (!is_dir(__DIR__ . '/keysCert')) {
                        mkdir(__DIR__ . '/keysCert');
                    }
                    file_put_contents(__DIR__ . '/keysCert/certout.csr', $certout);
                    file_put_contents(__DIR__ . '/keysCert/privkey.pem', $privkey);
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
    <title>Certificado X.509</title>
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
    <h1>Crear certificado y llave privada</h1>

    <form method="post" action="">
        <p>
            <label for="countryName">País (2 letras):</label><br>
            <input type="text" id="countryName" name="countryName" maxlength="2" size="4"
                   value="<?= htmlspecialchars($dn['countryName']) ?>" required>
        </p>
        <p>
            <label for="localityName">Localidad:</label><br>
            <input type="text" id="localityName" name="localityName" maxlength="64"
                   value="<?= htmlspecialchars($dn['localityName']) ?>" required>
        </p>
        <p>
            <label for="organizationName">Organización:</label><br>
            <input type="text" id="organizationName" name="organizationName" maxlength="64"
                   value="<?= htmlspecialchars($dn['organizationName']) ?>" required>
        </p>
        <p>
            <label for="commonName">Nombre común:</label><br>
            <input type="text" id="commonName" name="commonName" maxlength="64"
                   value="<?= htmlspecialchars($dn['commonName']) ?>" required>
        </p>
        <p>
            <label for="emailAddress">Correo electrónico:</label><br>
            <input type="email" id="emailAddress" name="emailAddress" maxlength="64" size="30"
                   value="<?= htmlspecialchars($dn['emailAddress']) ?>" required>
        </p>
        <button type="submit">Generar certificado</button>
    </form>

    <?php foreach ($errores as $e): ?>
        <p class="msg error"><strong><?= htmlspecialchars($e) ?></strong></p>
    <?php endforeach; ?>

    <?php if ($certout !== ''): ?>
        <pre class="cert"><?= htmlspecialchars($certout) ?></pre>
    <?php endif; ?>
</main>
</body>
</html>
