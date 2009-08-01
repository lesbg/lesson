<?php
	/*****************************************************************
	 * admin/subjecttype/delete_confirm.php  (c) 2005 Jonathan Dieter
	 *
	 * Confirm deletion of a subject type from database
	 *****************************************************************/

	 /* Get variables */
	$subjecttype   = dbfuncInt2String($_GET['keyname']);

	$title         = "LESSON - Confirm to delete $subjecttype";
	$noJS          = true;
	$noHeaderLinks = true;
	
	include "core/settermandyear.php";
	include "header.php";
	
	/* Check whether user is authorized to delete subject type */
	if(dbfuncGetPermission($permissions, $PERM_ADMIN)) {
		$link     = "index.php?location=" . dbfuncString2Int("admin/subjecttype/delete.php") .
					"&amp;key=" .           $_GET['key'] .
					"&amp;keyname=" .       $_GET['keyname'] .
					"&amp;next=" .          $_GET['next'];
		
		echo "      <p align=\"center\">Are you <b>sure</b> you want to delete $subjecttype?  In order to succeed in " . 
		                               "deleting it, you must make sure no subjects are using this type.</p>\n";
		echo "      <form action=\"$link\" method=\"post\">\n";
		echo "         <p align=\"center\">";
		echo "            <input type=\"submit\" name=\"action\" value=\"Yes, delete subject type\" \>&nbsp; \n";
		echo "            <input type=\"submit\" name=\"action\" value=\"No, I changed my mind\" \>&nbsp; \n";
		echo "         </p>";
		echo "      </form>\n";
	} else {
		log_event($LOG_LEVEL_ERROR, "admin/subjecttype/delete_confirm.php", $LOG_DENIED_ACCESS,
				"Tried to delete subject type $subjecttype.");
		$nextLink = dbfuncInt2String($_GET['next']);
		echo "      <p>You do not have the authority to remove this subject.  <a href=\"$nextLink\">" .
		              "Click here to continue</a>.</p>\n";
	}
	
	include "footer.php";
?>