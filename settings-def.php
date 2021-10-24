<?php

define("TOKEN_LENGTH", 20);
define("TOKEN_CRYPTKEY", "--insert random chars--");

define('DB_NAME', 'restman');
define('DB_USER', 'root');
define('DB_PASSWORD', 'raspidb');
define('DB_HOST', 'localhost');

define('REST_NAME', 'La Locanda del Pescatore');
define('REST_HEADER', '<font face="Segoe UI" color="#0077BE"><center><h1>' . REST_NAME . '</center></h1></font>');
define('REST_FOOTER', '<font face="Segoe UI" color="#0077BE" size=2><center>Restman 2<br>Powered by Giovanni Vella</center></font>');
date_default_timezone_set('Europe/Rome');

define('THERMAL_DEVICE', "/dev/usb/lp0");

define('BEEP_ENABLED', TRUE);
define('BEEP_PIN', 14);

define('COVER_PRICE', 2);

?>