<?php
require $_SERVER['DOCUMENT_ROOT'] . '/restman/main.php';
if (!empty($_GET['token'])) {
    setcookie("auth_key", encryptToken($_GET['token']), time() + (86400 * 1825), "/");
    echo '<meta http-equiv="refresh" content="0;URL=/restman/take/">';
}
if (isset($_COOKIE['auth_key'])) {
    $me = getMe();
    if (is_a($me, User::class)) {
        echo '<meta http-equiv="refresh" content="0;URL=/restman/take/">';
    } else if (is_a($me, Response::class)) {
        echo '<meta http-equiv="refresh" content="0;URL=/restman/logout/">';
    }
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="author" content="Giovanni Vella">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Restman login</title>
        <link rel="stylesheet" type="text/css" href="/restman/style.css" />
        <!--<link rel="stylesheet" type="text/css" href="/restman/login/style.css" />-->
    </head>
    <body>
        <p class="title">
            <?php echo getRestName(); ?>
        </p>
        Inquadra il codice QR
        <br />
        <br />
        <video id="preview" width="90%"></video>

        <script type="text/javascript" src="/restman/login/instascan.min.js"></script>
        <script type="text/javascript">
            let scanner = new Instascan.Scanner({video: document.getElementById('preview'), mirror: false});
            scanner.addListener('scan', function (content) {
                console.log(content);
                window.location.href = window.location.pathname + "?token=" + content;
            });
            Instascan.Camera.getCameras().then(function (cameras) {
                if (cameras.length > 0) {
                    if (cameras.length > 1) {
                        scanner.start(cameras[1]);
                    } else {
                        scanner.start(cameras[0]);
                    }
                    console.log("started camera");
                    document.getElementById('res').innerHTML = "start camera";
                } else {
                    console.error('No cameras found.');
                }
            }).catch(function (e) {
                console.error(e);
            });
        </script>
    </body>
</html>
