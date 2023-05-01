<?php
/**
 * ***************************************************************
 * user/logout.php (c) 2004 Jonathan Dieter
 *
 * Logs user out of LESSON
 * ***************************************************************
 */

/* Reset all session variables and logout */
log_event($LOG_LEVEL_ACCESS, "user/logout.php", $LOG_LOGOUT);

session_unset();
session_destroy();

/* Show logout message */
$title = "Logged out of LESSON";
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
echo "         <p>You have successfully logged out of LESSON</p>\n";
echo "         <p><a href='index.php'>Click here to login again</a></p>\n";
echo "      </div>\n";

include "footer.php";
?>