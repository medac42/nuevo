<?php
/**
 * Steam OpenID Login Handler for Impact Point
 */

$steam_openid_url = 'https://steamcommunity.com/openid/login';

// Determine protocol and host
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . "://" . $host;
$return_to = $base_url . $_SERVER['SCRIPT_NAME'];

// Validation Mode
if (isset($_GET['openid_mode']) && $_GET['openid_mode'] == 'id_res') {
    $params = [
        'openid.assoc_handle' => $_GET['openid_assoc_handle'] ?? '',
        'openid.signed'       => $_GET['openid_signed'] ?? '',
        'openid.sig'          => $_GET['openid_sig'] ?? '',
        'openid.ns'           => 'http://specs.openid.net/auth/2.0',
        'openid.mode'         => 'check_authentication',
    ];

    $signed = explode(',', $_GET['openid_signed'] ?? '');
    foreach ($signed as $item) {
        $val = $_GET['openid_' . str_replace('.', '_', $item)] ?? '';
        $params['openid.' . $item] = $val;
    }

    $ch = curl_init($steam_openid_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.82 Safari/537.36');
    $response = curl_exec($ch);
    curl_close($ch);

    if (strpos($response, 'is_valid:true') !== false) {
        $openid_claimed_id = $_GET['openid_claimed_id'];
        preg_match('/^https:\/\/steamcommunity\.com\/openid\/id\/(7[0-9]{15,25}+)$/', $openid_claimed_id, $matches);
        $steamid64 = $matches[1];
        header("Location: index.html?steamid=" . $steamid64);
        exit;
    } else {
        die("Error de validación. Steam dice que los datos no son válidos o la conexión ha fallado.");
    }
} 
// Redirect Mode: Using POST instead of GET to avoid length/WAF issues
else {
    $auth_params = [
        'openid.ns'         => 'http://specs.openid.net/auth/2.0',
        'openid.mode'       => 'checkid_setup',
        'openid.return_to'  => $return_to,
        'openid.realm'      => $base_url,
        'openid.identity'   => 'http://specs.openid.net/auth/2.0/identifier_select',
        'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
    ];
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Autenticando con Steam...</title>
        <style>
            body { background: #05040a; color: #fbff00; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Rajdhani', sans-serif; text-align: center; margin: 0; }
            .box { padding: 40px; border: 1px solid #fbff00; background: rgba(251, 255, 0, 0.05); }
            .loader { border: 4px solid #333; border-top: 4px solid #fbff00; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 20px; }
            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            .btn { background: #fbff00; color: #000; border: none; padding: 10px 20px; font-weight: 800; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="box">
            <div class="loader"></div>
            <h3>CONECTANDO CON STEAM</h3>
            <p style="color: #fff; margin-top: 10px;">Si no eres redirigido automáticamente, haz clic en el botón.</p>
            
            <form id="steamform" action="https://steamcommunity.com/openid/login" method="post">
                <?php foreach ($auth_params as $k => $v): ?>
                    <input type="hidden" name="<?php echo htmlspecialchars($k); ?>" value="<?php echo htmlspecialchars($v); ?>">
                <?php endforeach; ?>
                <input type="submit" class="btn" value="ENTRAR CON MI CUENTA DE STEAM">
            </form>
            
            <script>
                // Solo auto-enviar si no hay errores previos
                setTimeout(() => {
                    document.getElementById('steamform').submit();
                }, 500);
            </script>
        </div>
    </body>
    </html>
    <?php
    exit;
}
