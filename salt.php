<?php
$chars = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',0,1,2,3,4,5,6,7,8,9];
function randomString($length) {
    $string = '';
    $charsLen = count($GLOBALS['chars']);
	for($charIndex = 0; $charIndex < $length; $charIndex++) {
		$char = $GLOBALS['chars'][rand(0, $charsLen - 1)];
		$string .= rand(0, 10) > 5 ? strtoupper($char) : $char;
	}
	return $string;
}
function getSaltedAndHashedPassword($pwd) {
    $salt = randomString(64);
    return $salt . hash('sha256', $salt . $pwd);
}
echo getSaltedAndHashedPassword($_GET['password']);
?>