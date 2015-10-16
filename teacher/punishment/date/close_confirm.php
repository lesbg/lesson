<?php
/**
 * ***************************************************************
 * teacher/punishment/date/close_confirm.php (c) 2006 Jonathan Dieter
 *
 * Confirm punishment attendance closure
 * ***************************************************************
 */

/* Get variables */
$title = "LESSON - Confirm to close punishment";
$noJS = true;
$noHeaderLinks = true;

include "header.php";

$query = "SELECT Username, DisciplineDateIndex FROM disciplinedate " .
		 "WHERE DisciplineDateIndex = $pindex " .
		 "AND   Username = \"$username\" " . "AND   Done=0";
$res = &  $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query

if (dbfuncGetPermission($permissions, $PERM_ADMIN) or $res->numRows() > 0) {
	$link = "index.php?location=" .
		 dbfuncString2Int("teacher/punishment/date/close.php") . "&amp;type=" .
		 $_GET['type'] . "&amp;ptype=" . $_GET['ptype'] . "&amp;next=" .
		 $_GET['next'];
	
	echo "      <p align=\"center\">Are you sure you want to close this punishment?</p>\n";
	echo "      <form action=\"$link\" method=\"post\">\n";
	echo "         <p align=\"center\">";
	echo "            <input type=\"submit\" name=\"action\" value=\"Yes, close punishment\" \>&nbsp; \n";
	echo "            <input type=\"submit\" name=\"action\" value=\"No, I changed my mind\" \>&nbsp; \n";
	echo "         </p>";
	echo "      </form>\n";
} else {
	echo "      <p>This punishment date no longer exists or is closed</p>\n";
	echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
}

include "footer.php";
?>