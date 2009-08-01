<?php
	/*****************************************************************
	 * admin/category/delete_confirm.php  (c) 2005 Jonathan Dieter
	 *
	 * Confirm deletion of a category type from database
	 *****************************************************************/

	 /* Get variables */
	$category   = dbfuncInt2String($_GET['keyname']);

	$title         = "LESSON - Confirm to delete $category";
	$noJS          = true;
	$noHeaderLinks = true;
	
	include "core/settermandyear.php";
	include "header.php";
	
	/* Check whether user is authorized to delete category type */
	if($is_admin) {
		$link     = "index.php?location=" . dbfuncString2Int("admin/category/delete.php") .
					"&amp;key=" .           $_GET['key'] .
					"&amp;keyname=" .       $_GET['keyname'] .
					"&amp;next=" .          $_GET['next'];
		
		echo "      <p align=\"center\">Are you <b>sure</b> you want to delete $category?  In order to succeed in " . 
		                               "deleting it, you must make sure no subjects are using this type.</p>\n";
		echo "      <form action=\"$link\" method=\"post\">\n";
		echo "         <p align=\"center\">";
		echo "            <input type=\"submit\" name=\"action\" value=\"Yes, delete category type\" \>&nbsp; \n";
		echo "            <input type=\"submit\" name=\"action\" value=\"No, I changed my mind\" \>&nbsp; \n";
		echo "         </p>";
		echo "      </form>\n";
	} else {
		log_event($LOG_LEVEL_ERROR, "admin/category/delete_confirm.php", $LOG_DENIED_ACCESS,
				"Tried to delete category type $category.");
		$nextLink = dbfuncInt2String($_GET['next']);
		echo "      <p>You do not have the authority to remove this category type.  <a href=\"$nextLink\">" .
		              "Click here to continue</a>.</p>\n";
	}
	
	include "footer.php";
?>