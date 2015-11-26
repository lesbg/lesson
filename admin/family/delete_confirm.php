<?php
/**
 * ***************************************************************
 * admin/family/delete_confirm.php (c) 2015 Jonathan Dieter
 *
 * Confirm deletion of family code from database
 * ***************************************************************
 */

/* Get variables */
$delfullname = dbfuncInt2String($_GET['keyname']);
$fcode = safe(dbfuncInt2String($_GET['key']));

$title = "LESSON - Confirm to delete $delfullname";
$noJS = true;
$noHeaderLinks = true;

include "core/settermandyear.php";
include "header.php";

/* Check whether user is authorized to change scores */
if ($is_admin) {
	$link = "index.php?location=" . dbfuncString2Int("admin/family/delete.php") .
			 "&amp;key=" . $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] .
			 "&amp;next=" . dbfuncString2Int($backLink);
	
	echo "      <p align=\"center\">Are you <b>sure</b> you want to delete the $delfullname family ($fcode)? " .
		 "The family code cannot be assigned to any users.</p>\n";
	echo "      <form action=\"$link\" method=\"post\">\n";
	echo "         <p align=\"center\">";
	echo "            <input type=\"submit\" name=\"action\" value=\"Yes, delete family code\" \>&nbsp; \n";
	echo "            <input type=\"submit\" name=\"action\" value=\"No, I changed my mind\" \>&nbsp; \n";
	echo "         </p>";
	echo "      </form>\n";
} else {
	log_event($LOG_LEVEL_ERROR, "admin/family/delete_confirm.php", 
			$LOG_DENIED_ACCESS, "Tried to delete the $delfullname family ($fcode).");
	echo "      <p>You do not have the authority to remove this family code.  <a href=\"$nextLink\">" .
		 "Click here to continue</a>.</p>\n";
}

include "footer.php";