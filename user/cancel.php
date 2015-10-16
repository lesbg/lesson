<?php
/**
 * ***************************************************************
 * user/cancel.php (c) 2006 Jonathan Dieter
 *
 * Cancel whatever is being done and got to $_GET['next']
 * ***************************************************************
 */
$nextLink = dbfuncInt2String($_GET['next']); // Link to next page
$extraMeta = "      <meta http-equiv=\"REFRESH\" content=\"0;url=$nextLink\">\n";
$noJS = true;
$noHeaderLinks = true;
$title = "LESSON - Cancelling...";

include "header.php";

echo "      <p align=\"center\">Cancelling and redirecting you to <a href=\"$nextLink\">$nextLink</a>." .
	 "</p>\n";

include "footer.php";
?>