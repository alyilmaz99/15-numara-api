<?php

declare (strict_types = 1);

spl_autoload_register(function ($class) {

    require __DIR__ . "/src/$class.php";
    require __DIR__ . "/src/rooms/$class.php";
});

header("Content-type: application/json; charset=UTF-8");
set_error_handler("ErrorHandler::handleError");
set_exception_handler("ErrorHandler::handleException");

$parts = explode("/", $_SERVER["REQUEST_URI"]);

if ($parts[2] != "rooms") {
    http_response_code(404);
    exit;
}

$id = $parts[3] ?? null;
$database = new Database("localhost", "15_numara", "root", "2901");
$gateway = new RoomGateway($database);
$controller = new RoomController($gateway);

$controller->processRequest($_SERVER["REQUEST_METHOD"], $id);
