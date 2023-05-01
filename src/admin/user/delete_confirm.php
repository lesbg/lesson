<?php
/**
 * ***************************************************************
 * admin/user/delete_confirm.php (c) 2005 Jonathan Dieter
 *
 * Confirm deletion of user from database
 * ***************************************************************
 */

/* Get variables */
$delfullname = dbfuncInt2String($_GET['keyname']);

$title = "LESSON - Confirm to delete $delfullname";
$noJS = true;
$noHeaderLinks = true;

include "core/settermandyear.php";
include "header.php";

/* Check whether user is authorized to change scores */
if ($is_admin) {
	$link = "index.php?location=" . dbfuncString2Int("admin/user/delete.php") .
			 "&amp;key=" . $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] .
			 "&amp;next=" . $_GET['next'];
	
	echo "      <p align=\"center\">Are you <b>sure</b> you want to delete $delfullname? " .
		 "The user cannot be in any classes or families.</p>\n";
	echo "      <form action=\"$link\" method=\"post\">\n";
	echo "         <p align=\"center\">";
	echo "            <input type=\"submit\" name=\"action\" value=\"Yes, delete user\" \>&nbsp; \n";
	echo "            <input type=\"submit\" name=\"action\" value=\"No, I changed my mind\" \>&nbsp; \n";
	echo "         </p>";
	echo "      </form>\n";
} else {
	log_event($LOG_LEVEL_ERROR, "admin/user/delete_confirm.php", 
			$LOG_DENIED_ACCESS, "Tried to delete user $delfullname.");
	echo "      <p>You do not have the authority to remove this user.  <a href=\"$nextLink\">" .
		 "Click here to continue</a>.</p>\n";
}

include "footer.php";
?>