<?php
/**
 * ***************************************************************
 * user/login.php (c) 2015 Jonathan Dieter
 *
 * Login screen
 * ***************************************************************
 */

$title = "Welcome to LESSON";
$noJS = true;
$noHeaderLinks = true;

include "header.php";

echo "      <style type='text/css'>\n";
echo "         #center {width:400px; position:absolute; top:20%; left:50%; min-top: 0%; min-bottom: 100%; margin:auto auto auto -200px; text-align: center;}\n";
echo "      </style>\n";
echo "      <div id='center' class='button'>\n";
$useragent = $_SERVER['HTTP_USER_AGENT'];
if (preg_match('|MSIE ([0-6].[0-9]{1,2})|', $useragent, $matched)) {
	// Can't handle transparent png's, so we'll give them transparent gif's
	echo "         <p><img height='100' width='400' alt='LESSON Logo' src='images/lesson_logo.gif'></p>\n";
} else {
	echo "         <p><img height='100' width='400' alt='LESSON Logo' src='images/lesson_logo.png'></p>\n";
}
echo "         <p>&nbsp;</p>\n";

echo "         <form method='post' action='" . $_SERVER['PHP_SELF'] . "'>\n";
echo "            <p>NewUsername: <input type='text' name='username'></p>\n";
echo "            <p>Password: <input type='password' name='password'></p>\n";
echo "            <p><input type='submit' value='Login'></p>\n";
echo "         </form>\n";
if ($error) {
	echo "         <p class='error'>Incorrect username or password.  Please try again!</p>\n";
}
echo "      </div>\n";

include "footer.php";