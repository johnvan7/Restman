<?php
/* * *
 * Copyright (C) 2017-2021    Giovanni Vella
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

    require $_SERVER['DOCUMENT_ROOT'].'/restman/main.php';
    if(isset($_COOKIE['auth_key'])){
        $me = getMe();
        if(is_a($me, User::class)){
            if($me->Priv==1)
                echo '<meta http-equiv="refresh" content="0;URL=/restman/take/">';
            elseif($me->Priv==2)
                echo '<meta http-equiv="refresh" content="0;URL=/restman/touch/">';
        }
        else if(is_a($me, Response::class)){
            echo '<meta http-equiv="refresh" content="0;URL=/restman/logout/">';
        }
    }
    else if(!empty($_POST['username'])){
        $username = $_POST['username'];
        $password = $_POST['password'];
        $res = login($username, $password);
        echo '<meta http-equiv="refresh" content="0;URL=/restman/login/">';
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
        <br />
        <a href="<?php echo "https://".$_SERVER['SERVER_NAME']."/restman/login/qr.php"; ?>"><img src="QRCode.png" width="15%" /></a>
        <br />
        <br />
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required />
            <br />
            <input type="password" name="password" placeholder="Password" required />
            <br />
            <br />
            <input type="submit" value="Login" />
        </form>
    </body>
</html>
