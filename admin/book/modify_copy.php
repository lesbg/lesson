<?php
	/*****************************************************************
	 * admin/book/modify_copy.php  (c) 2010 Jonathan Dieter
	 *
	 * Change information about book copy
	 *****************************************************************/

	/* Get variables */
	$title            = "Change information for copy " . dbfuncInt2String($_GET['keyname2']) . " of " . dbfuncInt2String($_GET['keyname']);
	$bookindex        = dbfuncInt2String($_GET['key']);
	$link             = "index.php?location=" . dbfuncString2Int("admin/book/new_or_modify_copy_action.php") .
						"&amp;key=" .           $_GET['key'] .
						"&amp;keyname=" .       $_GET['keyname'] .
						"&amp;keyname2=" .      $_GET['keyname2'] .
						"&amp;next=" .          $_GET['next'];
	
	include "header.php";                                              // Show header
	
	/* Check whether user is authorized to change subject */	
	if($is_admin) {
		/* Get subject information */
		$fRes =& $db->query("SELECT BookNumber FROM book " .
							"WHERE BookIndex = '$bookindex'");
		if(DB::isError($fRes)) die($fRes->getDebugInfo());             // Check for errors in query
		if($fRow =& $fRes->fetchRow(DB_FETCHMODE_ASSOC)) {
			if(isset($errorlist)) {
				echo $errorlist;
			}

			if(!isset($_POST['number'])) {
				$_POST['number'] = htmlspecialchars($fRow['BookNumber']);
			} else {
				$_POST['number'] = htmlspecialchars($_POST['number']);
			}
			
			echo "      <form action='$link' method='post'>\n";         // Form method
			echo "         <input type='hidden' name='type' value='modify'>\n";
			echo "         <table class='transparent' align='center'>\n";   // Table headers
			
			/* Show subject type name */
			echo "            <tr>\n";
			echo "               <td><b>Copy number</b></td>\n";
			echo "               <td><input type='text' name='number' value='{$_POST['number']}' size=20></td>\n";
			echo "            </tr>\n";
			echo "         </table>\n";                                                      // End of table
			echo "         <p align='center'>\n";
			echo "            <input type='submit' name='action' value='Update' \>\n";
			echo "            <input type='submit' name='action' value='Cancel' \>\n";
			echo "         </p>\n";
			echo "      </form>\n";
		} else {  // Couldn't find $booktitleindex in book_title table
			echo "      <p align='center'>Can't find book title.  Have you deleted it?</p>\n";
			echo "      <p align='center'><a href='$backLink'>Click here to go back</a></p>\n";
		}
		log_event($LOG_LEVEL_EVERYTHING, "admin/book/modify_copy.php", $LOG_ADMIN,
				"Opened book title $title for editing.");
	} else {
		log_event($LOG_LEVEL_ERROR, "admin/book/modify_copy.php", $LOG_DENIED_ACCESS,
				"Attempted to change information about the book title $title.");
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>