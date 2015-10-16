<?php
/**
 * ***************************************************************
 * user/start.php (c) 2005 Jonathan Dieter
 *
 * Start page that redirects to main. Used so that user can
 * press back key to get to main without needing to repost
 * data.
 * ***************************************************************
 */
$nextLink = "index.php?location=" . dbfuncString2Int("user/main.php");
$noJS = true;
$noHeaderLinks = true;

$title = "LESSON - Redirecting...";
$extraMeta = "      <meta http-equiv=\"REFRESH\" content=\"0;url=$nextLink\">\n";
include "header.php";

echo "      <p align=\"center\">Redirecting you to <a href=\"$nextLink\">$nextLink</a>.</p>\n";

include "footer.php";
?>
