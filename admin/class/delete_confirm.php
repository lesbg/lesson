<?php
/**
 * ***************************************************************
 * admin/class/delete_confirm.php (c) 2005 Jonathan Dieter
 *
 * Confirm deletion of class from database
 * ***************************************************************
 */

/* Get variables */
$classname = dbfuncInt2String($_GET['keyname']);

$title = "LESSON - Confirm to delete $classname";
$noJS = true;
$noHeaderLinks = true;

include "core/settermandyear.php";
include "header.php";

/* Check whether user is authorized to change scores */
if (dbfuncGetPermission($permissions, $PERM_ADMIN)) {
	$dateinfo = date($dateformat, strtotime($row['Date']));
	$link = "index.php?location=" . dbfuncString2Int("admin/class/delete.php") .
			 "&amp;keyname=" . $_GET['keyname'] . "&amp;key=" . $_GET['key'] .
			 "&amp;next=" . dbfuncString2Int($backLink);
	
	echo "      <p align=\"center\">Are you <b>sure</b> you want to delete $classname? " .
		 "All of its students will be listed as not in any classes.</p>\n";
	echo "      <form action=\"$link\" method=\"post\">\n";
	echo "         <p align=\"center\">";
	echo "            <input type=\"submit\" name=\"action\" value=\"Yes, delete class\" \>&nbsp; \n";
	echo "            <input type=\"submit\" name=\"action\" value=\"No, I changed my mind\" \>&nbsp; \n";
	echo "         </p>";
	echo "      </form>\n";
} else {
	log_event($LOG_LEVEL_ERROR, "admin/class/delete_confirm.php", 
			$LOG_DENIED_ACCESS, "Tried to delete class $classname.");
	echo "      <p>You do not have the authority to remove this class.  <a href=\"$nextLink\">" .
		 "Click here to continue</a>.</p>\n";
}

include "footer.php";
?>