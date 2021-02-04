<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

function removeIfEmpty($str) {
    return preg_replace('/\s+/', '', $str) != '';
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = array_filter(explode( '/', $uri ), "removeIfEmpty");
$uriCount = count($uri);

$requestMethod = $_SERVER["REQUEST_METHOD"];
switch ($requestMethod) {
    case 'GET':
        if ($uriCount == 2) {
            require_once('listUsers.php');
        } else if ($uriCount == 3) {
            require_once('getUser.php');
        } else if ($uriCount == 4) {
            $lastPart = strtolower($uri[3]);
            require_once('searchUsers.php');
        }
        break;
    case 'PUT':
        if ($uriCount == 4) {
            $lastPart = strtolower($uri[4]);
            if ($lastPart == 'toggledarkmode') {
                require_once('toggleDarkMode.php');
            }
        } else {
            require_once('updateUser.php');
        }
        break;
    case 'POST':
        require_once('createUser.php');
        break;
    case 'DELETE':
        require_once('deleteUser.php');
        break;
}
?>