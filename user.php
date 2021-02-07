<?php
/* START FROM: https://developer.okta.com/blog/2019/03/08/simple-rest-api-php */
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

function removeIfEmpty($str) {
    // this function removes all spaces from $str
    // and returns true if there is anything left
    return preg_replace('/\s+/', '', $str) != '';
}

if (isset($_SERVER['REQUEST_URI'])) {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = array_filter(explode( '/', $uri ), "removeIfEmpty");
    $uriCount = count($uri);
}
/* END FROM: https://developer.okta.com/blog/2019/03/08/simple-rest-api-php */

$requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER["REQUEST_METHOD"] : $requestMethod;

// we use require_once to include the relevant scripts
// depending on which HTTP method was requested, and/or
// the makeup of the request URI
switch ($requestMethod) {
    case 'GET':
        if ($uriCount == 2) {
            require_once('listUsers.php');
            echo $output;
        } else if ($uriCount == 3) {
            require_once('getUser.php');
        } else if ($uriCount == 4) {
            $lastPart = strtolower($uri[3]);
            $requestData = [];
            if (isset($_GET['searchField'])) {
                $requestData['searchField'] = $_GET['searchField'];
            }
            if (isset($_GET['query'])) {
                $requestData['query'] = $_GET['query'];
            }
            require_once('searchUsers.php');
            list($output, $searchResultCount) = searchUsers($requestData, $dbConn);
            echo $output;
        }
        break;
    case 'PUT':
        if (isset($uriCount) && $uriCount == 4) {
            $lastPart = strtolower($uri[4]);
            if ($lastPart == 'toggledarkmode') {
                $myEntireBody = file_get_contents('php://input'); //Be aware that the stream can only be read once
                parse_str($myEntireBody, $requestData);
                require_once('toggleDarkMode.php');
            }
        } else {
            $myEntireBody = file_get_contents('php://input');
            parse_str($myEntireBody, $requestData);
            require_once('updateUser.php');
        }
        break;
    case 'POST':
        $requestData = [];
        foreach ($_POST as $key => $value) {
            $requestData[$key] = $value;
        }
        require_once('createUser.php');
        createUser($requestData, $dbConn);
        break;
    case 'DELETE':
        $myEntireBody = file_get_contents('php://input'); //Be aware that the stream can only be read once
        parse_str($myEntireBody, $requestData);
        $requestData['id'] = $_REQUEST['id'];
        require_once('deleteUser.php');
        break;
}
?>