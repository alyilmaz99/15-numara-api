<?php

require_once 'vendor/autoload.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

use Api\Database;
use Api\Rooms\RoomController;
use Api\Rooms\RoomGateway;
use Api\User\UserController;
use Api\User\UserGateway;

header("Content-type: application/json; charset=UTF-8");
//set_error_handler("Api\ErrorHandler::handleError");
//set_exception_handler("Api\ErrorHandler::handleException");

$parts = explode("/", $_SERVER["REQUEST_URI"]);

if ($parts[2] != "rooms" && $parts[2] != "user") {
    http_response_code(404);
    exit;
}

$id = $parts[3] ?? null;
$database = new Database("localhost", "15_numara", "root", "2901");

if ($parts[2] == 'rooms') {
    $gateway = new RoomGateway($database);
    $controller = new RoomController($gateway);
} else if ($parts[2] == 'user') {
    $userGateway = new UserGateway($database);
    $controller = new UserController($userGateway);
}

$controller->processRequest($_SERVER["REQUEST_METHOD"], $id);
