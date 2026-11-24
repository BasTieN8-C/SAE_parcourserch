<?php
// config/config.php

if ( PHP_OS_FAMILY === 'Linux' ) 
{
    define('DB_HOST', 'woody');
    define('DB_NAME', 'de230866');
    define('DB_USER', 'de230866');
    define('DB_PASS', '260120');
}
else
{
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'sae_parcoursup');
    define('DB_USER', 'postgres');
    define('DB_PASS', '260120');
}
// Autres configurations globales
define('DB_PORT', '5432');
define('APP_NAME', 'Analyse Parcoursup');
define('APP_ENV', 'developpement');

?>