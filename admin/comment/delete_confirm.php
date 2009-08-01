<?php
	/*****************************************************************
	 * admin/comment/delete_confirm.php  (c) 2008 Jonathan Dieter
	 *
	 * Confirm deletion of a comment from database
	 *****************************************************************/

	$title         = "LESSON - Confirm to delete comment";
	$noJS          = true;
	$noHeaderLinks = true;
	
	include "core/settermandyear.php";
	include "header.php";
	
	/* Check whether user is authorized to delete comment */
	if($is_admin) {
		$link     = "index.php?location=" . dbfuncString2Int("admin/comment/delete.php") .
					"&amp;key=" .           $_GET['key'] .
					"&amp;keyname=" .       $_GET['keyname'] .
					"&amp;next=" .          $_GET['next'];
		
		echo "      <p align=\"center\">Are you <b>sure</b> you want to delete this comment?</p>\n";
		echo "      <form action=\"$link\" method=\"post\">\n";
		echo "         <p align=\"center\">";
		echo "            <input type=\"submit\" name=\"action\" value=\"Yes, delete comment\" \>&nbsp; \n";
		echo "            <input type=\"submit\" name=\"action\" value=\"No, I changed my mind\" \>&nbsp; \n";
		echo "         </p>";
		echo "      </form>\n";
	} else {
		log_event($LOG_LEVEL_ERROR, "admin/comment/delete_confirm.php", $LOG_DENIED_ACCESS,
				"Tried to delete a comment.");
		$nextLink = dbfuncInt2String($_GET['next']);
		echo "      <p>You do not have the authority to remove this comment.  <a href=\"$nextLink\">" .
		              "Click here to continue</a>.</p>\n";
	}
	
	include "footer.php";
?>