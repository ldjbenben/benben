<?php
define('APPLICATION_PATH', str_replace('\\','/', dirname(__FILE__)).'/protected');
define('BENBEN_DEBUG', true);

$config_file = APPLICATION_PATH.'/config/main.php';

require_once APPLICATION_PATH.'/classes/benben/Benben.php';

benben\Benben::createWebApplication($config_file)->run();