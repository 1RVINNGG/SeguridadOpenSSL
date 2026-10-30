<?php
/* Firmar un mensaje con la llave privada y verificarlo con el certificado.
   Primero se debe ejecutar FirmaOtra.php para crear keysCert/privkey.pem y keysCert/certout.csr */

$mensaje = 'Este es un mensaje confidencial';
$error = '';
$firmaBase64 = '';
$resultado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensaje = trim($_POST['mensaje'] ?? '');

    $archivoPriv = __DIR__ . '/keysCert/privkey.pem';
    $archivoCert = __DIR__ . '/keysCert/certout.csr';

    if ($mensaje === '' || strlen($mensaje) > 500) {
        $error = 'El mensaje es obligatorio y debe tener máximo 500 caracteres.';
    } elseif (!file_exists($archivoPriv) || !file_exists($archivoCert)) {
        $error = 'No existen la llave privada y el certificado. Ejecute primero FirmaOtra.php.';
    } else {
        // Paso 1: firmar el mensaje con la clave privada
        $privKeyResource = openssl_pkey_get_private(file_get_contents($archivoPriv));
        openssl_sign($mensaje, $firmaBinaria, $privKeyResource, OPENSSL_ALGO_SHA256);
        $firmaBase64 = base64_encode($firmaBinaria);

        // Paso 2: verificar la firma con el certificado público
        $pubKeyResource = openssl_pkey_get_public(file_get_contents($archivoCert));
        $ok = openssl_verify($mensaje, base64_decode($firmaBase64), $pubKeyResource, OPENSSL_ALGO_SHA256);

        if ($ok === 1) {
            $resultado = '✅ Firma válida. El mensaje no fue alterado.';
        } elseif ($ok === 0) {
            $resultado = '❌ Firma inválida. El mensaje pudo haber sido modificado.';
        } else {
            $resultado = '⚠ Error al verificar la firma: ' . openssl_error_string();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Firmar y verificar un mensaje</title>
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
    <h1>Firmar y verificar un mensaje</h1>

    <form method="post" action="">
        <p>
            <label for="mensaje">Mensaje:</label><br>
            <input type="text" id="mensaje" name="mensaje" maxlength="500" size="60"
                   value="<?= htmlspecialchars($mensaje) ?>" required>
        </p>
        <button type="submit">Firmar y verificar</button>
    </form>

    <?php if ($error !== ''): ?>
        <p class="msg error"><strong><?= htmlspecialchars($error) ?></strong></p>
    <?php elseif ($firmaBase64 !== ''): ?>
        <p><strong>Firma en base64:</strong></p>
        <p class="msg info" style="word-break: break-all; font-size: 0.8rem;"><?= htmlspecialchars($firmaBase64) ?></p>
        <p class="msg <?= strpos($resultado, '✅') === 0 ? 'ok' : 'error' ?>"><?= htmlspecialchars($resultado) ?></p>
    <?php endif; ?>
</main>
</body>
</html>
