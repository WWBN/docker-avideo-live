<?php

/**
 * This file intent to control some features from NGINX based on the control module https://github.com/arut/nginx-rtmp-module/wiki/Control-module
 * 
 * This file suppose to sit on the same server as the live stream, and for security reasons you may want to setup your control module in a different port, listning only localhost o port 8080
 * 
  http {
    ...
    server {
        listen       8080;
        server_name  localhost;
        location /control {
            rtmp_control all;
        }
    }
  }
 */
$streamerURL = '{webSiteRootURL}'; // change it to your streamer URL

$server_name = "localhost";
$port = "8080";
/*
 * DO NOT EDIT AFTER THIS LINE
 */


header('Content-Type: application/json');
$configFile = '../../../videos/configuration.php';

if (file_exists($configFile)) {
    include_once $configFile;
    $streamerURL = $global['webSiteRootURL'];
}

error_log("Control.json.php start");

$obj = new stdClass();
$obj->error = true;
$obj->msg = "";
$obj->streamerURL = $streamerURL;
$obj->token = $_REQUEST['token'];
$obj->command = $_REQUEST['command'];
$obj->app = $_REQUEST['app'];
$obj->name = $_REQUEST['name'];
$obj->response = "";
$obj->requestedURL = "";

// check the token
if (empty($obj->token)) {
    $obj->msg = "Token is empty";
    error_log("Control.json.php ERROR {$obj->msg}");
    die(json_encode($obj));
}
if (empty($obj->command)) {
    $obj->msg = "command is empty";
    error_log("Control.json.php ERROR {$obj->msg}");
    die(json_encode($obj));
}
if (empty($obj->name)) {
    $obj->msg = "name is empty";
    error_log("Control.json.php ERROR {$obj->msg}");
    die(json_encode($obj));
}

$verifyTokenURL = "{$obj->streamerURL}plugin/Live/verifyToken.json.php?token={$obj->token}";

error_log("Control.json.php verifying token {$verifyTokenURL}");
$content = file_get_contents($verifyTokenURL);
error_log("Control.json.php verification respond content {$content}");
$json = json_decode($content);

if (empty($json)) {
    $obj->msg = "Could not verify token";
    error_log("Control.json.php ERROR {$obj->msg} ({$verifyTokenURL}) ");
    die(json_encode($obj));
} else if (!empty($json->error)) {
    $obj->msg = "Token is invalid";
    error_log("Control.json.php ERROR {$obj->msg} ({$verifyTokenURL}) " . json_encode($json));
    die(json_encode($obj));
}
error_log("Control.json.php token is correct");
/*
ignore_user_abort(true);
ob_start();
header("Connection: close");
@header("Content-Length: " . ob_get_length());
ob_end_flush();
flush();
*/

switch ($obj->command) {
    case "record_start":
        //http://server.com/control/record/start|stop?srv=SRV&app=APP&name=NAME&rec=REC
        $obj->requestedURL = "http://{$server_name}:{$port}/control/record/start?app={$obj->app}&name={$obj->name}";
        $obj->response = @file_get_contents($obj->requestedURL);
        $obj->error = false;
        break;
    case "record_stop":
        //http://server.com/control/record/start|stop?srv=SRV&app=APP&name=NAME&rec=REC
        $obj->requestedURL = "http://{$server_name}:{$port}/control/record/stop?app={$obj->app}&name={$obj->name}";
        $obj->response = @file_get_contents($obj->requestedURL);
        $obj->error = false;
        break;
    case "drop_publisher":
        //http://server.com/control/drop/publisher|subscriber|client?srv=SRV&app=APP&name=NAME&addr=ADDR&clientid=CLIENTID
        $obj->requestedURL = "http://{$server_name}:{$port}/control/drop/publisher?app={$obj->app}&name={$obj->name}";
        $obj->response = @file_get_contents($obj->requestedURL);
        $obj->error = false;
        break;

    default:

        $obj->msg = "Command is invalid ($obj->command)";
        die(json_encode($obj));
        break;
}


error_log("Control.json.php finish " . json_encode($obj));
die(json_encode($obj));
