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
ORM::configure('caching', true);
ORM::configure('caching_auto_clear', true);
if (getenv('LOG_LEVEL') == 'Logger::DEBUG') {
    ORM::configure('logging', true);
    ORM::configure('logger', function($log_string, $query_time) {
        $fname  = sprintf("%s/logs/sql.%s.log", APP_ROOT, date("Ymd"));
        $remote_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $message = sprintf("%s %s %s %s\n", (new DateTime())->format('Y-m-d H:i:s'), $remote_address, $query_time, $log_string);
        file_put_contents($fname, $message, FILE_APPEND);
    });
}

//initlized
\D2ca\Helper::provider();
\D2ca\Helper::logger();

