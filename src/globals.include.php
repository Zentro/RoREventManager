<?php 

define('LOG_LEVEL_ERROR',   0);
define('LOG_LEVEL_WARNING', 1);
define('LOG_LEVEL_INFO',    2);
define('LOG_LEVEL_DETAIL',  3);
define('LOG_LEVEL_DEBUG',   4);

define('EVENT_DISQUALIFIED_TRUCK_HASH_ERROR', 1);
define('EVENT_DISQUALIFIED_VERSION_ERROR', 2);
define('EVENT_DISQUALIFIED_TERRAIN_ERROR', 3);
define('EVENT_DISQUALIFIED_CUSTOM_RULE_ERROR', 4); // For "other" reasons


// Uncomment this to set the timezone to something other than your server or PC's timezone
date_default_timezone_set('America/Chicago');

function log_msg($level, $msg)
{
    global $config;
    
    if ($level > $config['logging']['verbosity']) {
        return;
    }
    $d    = new DateTime();
    $line = $d->format('[Y-m-d H:i:s]') . " " . $msg . "\n";
    file_put_contents($config['logging']['filename'], $line, FILE_APPEND);
}

function log_error($msg)
{
    log_msg(LOG_LEVEL_ERROR, $msg);
}

function log_warn($msg)
{
    log_msg(LOG_LEVEL_WARNING, $msg);
}

function log_info($msg)
{
    log_msg(LOG_LEVEL_INFO, $msg);
}

function log_detail($msg)
{
    log_msg(LOG_LEVEL_DETAIL, $msg);
}

function log_debug($msg)
{
    log_msg(LOG_LEVEL_DEBUG, $msg);
}

function die_json($http_code, $message)
{
    header('content-type: application/json; charset: utf-8');
    http_response_code($http_code);
    $answer = array(
        'result' => ($http_code == 200),
        'message' => $message
    );
    log_info("Response: HTTP {$http_code}, message: {$message}");
    log_detail("JSON:\n" . json_encode($answer, JSON_PRETTY_PRINT));
    die(json_encode($answer));
}

function check_args_or_die($_args, $req_args)
{
    $missing = array();
    foreach ($req_args as $key) {
        if (!isset($_args[$key])) {
            $missing[] = $key;
        }
    }
    
    if (count($missing) > 0) {
        die_json(400, 'Missing required argument(s): ' . implode(", ", $missing));
    }
}

function connect_mysqli_or_die($config)
{
    $db_config = $config['database'];
    $mysqli    = new MySQLi($db_config['host'], $db_config['user'], $db_config['password'], $db_config['name']);
    if ($mysqli->connect_errno != 0) {
        log_error("Cannot connect to DB: {$mysqli->error}");
        die_json(500, 'Server error, cannot connect to database.');
    }
    return $mysqli;
}

function get_json_input()
{
    $in_json = trim(file_get_contents('php://input'));
    log_detail("JSON input:" . ($in_json != "" ? "\n{$in_json}" : " ~EMPTY~"));
    return json_decode($in_json, true);
}

function get_ipv4() {
    $ip = $_SERVER["REMOTE_ADDR"];
    if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
    {
        $ip=$_SERVER["HTTP_X_FORWARDED_FOR"];
    }

    return $ip;
}