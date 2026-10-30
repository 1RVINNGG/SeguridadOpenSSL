<?php
/* Cifrado simétrico con openssl_encrypt() / openssl_decrypt() */

$texto = '';
$clave = 'clave12345678901'; // 16 caracteres para AES-128
$error = '';
$cifrado = '';
$descifrado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $texto = trim($_POST['texto'] ?? '');
    $clave = $_POST['clave'] ?? '';

    if ($texto === '' || strlen($texto) > 200) {
        $error = 'El texto es obligatorio y debe tener máximo 200 caracteres.';
    } elseif (strlen($clave) !== 16) {
        $error = 'La clave debe tener exactamente 16 caracteres (AES-128).';
    } else {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-128-CBC'));
        $cifrado = openssl_encrypt($texto, 'AES-128-CBC', $clave, 0, $iv);
        $descifrado = openssl_decrypt($cifrado, 'AES-128-CBC', $clave, 0, $iv);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Encriptación AES-128-CBC</title>
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
    <h1>Encriptación con OpenSSL (AES-128-CBC)</h1>

    <form method="post" action="">
        <p>
            <label for="texto">Texto a cifrar:</label><br>
            <input type="text" id="texto" name="texto" maxlength="200" size="50"
                   value="<?= htmlspecialchars($texto) ?>" required>
        </p>
        <p>
            <label for="clave">Clave (16 caracteres):</label><br>
            <input type="text" id="clave" name="clave" maxlength="16" size="20"
                   value="<?= htmlspecialchars($clave) ?>" required>
        </p>
        <button type="submit">Cifrar y descifrar</button>
    </form>

    <?php if ($error !== ''): ?>
        <p class="msg error"><strong><?= htmlspecialchars($error) ?></strong></p>
    <?php elseif ($cifrado !== ''): ?>
        <p class="msg info">El resultado del cifrado es el siguiente: <?= htmlspecialchars($cifrado) ?></p>
        <p class="msg ok">El resultado del descifrado es: <?= htmlspecialchars($descifrado) ?></p>
    <?php endif; ?>
</main>
</body>
</html>
