<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once "./lib/socket.php";

$server = new Server();
$server->run();