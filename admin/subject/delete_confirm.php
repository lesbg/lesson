<?php
	/*****************************************************************
	 * admin/subject/delete_confirm.php  (c) 2005 Jonathan Dieter
	 *
	 * Confirm deletion of a subject from database
	 *****************************************************************/

	 /* Get variables */
	$subject    = dbfuncInt2String($_GET['keyname']);

	$title         = "LESSON - Confirm to delete $subject";
	$noJS          = true;
	$noHeaderLinks = true;
	
	include "core/settermandyear.php";
	include "header.php";
	
	/* Check whether user is authorized to change scores */
	if(dbfuncGetPermission($permissions, $PERM_ADMIN)) {
		$link     = "index.php?location=" . dbfuncString2Int("admin/subject/delete.php") .
					"&amp;key=" .           $_GET['key'] .
					"&amp;keyname=" .       $_GET['keyname'] .
					"&amp;next=" .          $_GET['next'];
		
		echo "      <p align=\"center\">Are you <b>sure</b> you want to delete $subject? " .
		                               "All students must be removed from the subject.</p>\n";
		echo "      <form action=\"$link\" method=\"post\">\n";
		echo "         <p align=\"center\">";
		echo "            <input type=\"submit\" name=\"action\" value=\"Yes, delete subject\" \>&nbsp; \n";
		echo "            <input type=\"submit\" name=\"action\" value=\"No, I changed my mind\" \>&nbsp; \n";
		echo "         </p>";
		echo "      </form>\n";
	} else {
		log_event($LOG_LEVEL_ERROR, "admin/subject/delete_confirm.php", $LOG_DENIED_ACCESS,
				"Tried to delete subject $subject.");
		echo "      <p>You do not have the authority to remove this subject.  <a href=\"$nextLink\">" .
		              "Click here to continue</a>.</p>\n";
	}
	
	include "footer.php";
?>