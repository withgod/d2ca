<?php
define('APP_ROOT', realpath(__DIR__ . '/../'));
require_once(realpath(APP_ROOT . '/vendor/autoload.php'));

define('IS_DEVELOPMENT', gethostname() == 'ubuntu-xenial' ? TRUE : FALSE);
define('IS_PRODUCTION', IS_DEVELOPMENT ? FALSE : TRUE);

$dotenv = new \Dotenv\Dotenv(APP_ROOT);
$dotenv->load();

ORM::configure(getenv("DB_DSN"));
ORM::configure('username', getenv("DB_USER"));
ORM::configure('password', getenv("DB_PASS"));
Model::$auto_prefix_models = '\\D2ca\\Models\\';
Model::$short_table_names = true;

/*
ORM::configure('logging', true);
ORM::configure('logger', function($log_string, $query_time) {
    echo $log_string . ' in ' . $query_time . "\n";
});*/

//initlized
\D2ca\Helper::provider();
\D2ca\Helper::logger();

