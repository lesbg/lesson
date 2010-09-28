<?php
	/*****************************************************************
	 * admin/book/new_copy.php  (c) 2010 Jonathan Dieter
	 *
	 * Create new copy of a book
	 *****************************************************************/

	/* Get variables */
	$booktitleindex   = dbfuncInt2String($_GET['key']);
	$title            = "New copy of " . dbfuncInt2String($_GET['keyname']);
	$link             = "index.php?location=" . dbfuncString2Int("admin/book/new_or_modify_copy_action.php") .
						"&amp;key=" .           $_GET['key'] .
						"&amp;keyname=" .       $_GET['keyname'] .
						"&amp;next=" .          $_GET['next'];
	
	include "header.php";                                              // Show header
	
	/* Check whether user is authorized to change subject */	
	if($is_admin) {
		if(isset($errorlist)) {
			echo $errorlist;
		}
		if(!isset($_POST['number'])) {
			$_POST['number'] = "";
		} else {
			$_POST['number'] = htmlspecialchars($_POST['title'], ENT_QUOTES);
		}

		echo "      <form action='$link' method='post'>\n";                          // Form method
		echo "         <input type='hidden' name='type' value='new'>\n";
		echo "         <table class='transparent' align='center'>\n";   // Table headers
		
		/* Show book type name */
		echo "            <tr>\n";
		echo "               <td><b>Copy number</b></td>\n";
		echo "               <td><input type='text' name='number' value='{$_POST['number']}' size=20></td>\n";
		echo "            </tr>\n";
		echo "         </table>\n";               // End of table
		echo "         <p align='center'>\n";
		echo "            <input type='submit' name='action' value='Save' />\n";
		echo "            <input type='submit' name='action' value='Cancel' />\n";
		echo "         </p>\n";
		echo "      </form>\n";
	} else {  // User isn't authorized to view or change scores.
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>