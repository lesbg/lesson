<?php
	/*****************************************************************
	 * admin/book/new_title.php  (c) 2010 Jonathan Dieter
	 *
	 * Create new book title
	 *****************************************************************/

	/* Get variables */
	$title            = "New Book Title";
	$link             = "index.php?location=" . dbfuncString2Int("admin/book/new_or_modify_title_action.php") .
						"&amp;next=" .          $_GET['next'];
	
	include "header.php";                                              // Show header
	
	/* Check whether user is authorized to change subject */	
	if($is_admin) {
		if(isset($errorlist)) {
			echo $errorlist;
		}
		if(!isset($_POST['title'])) {
			$_POST['title'] = "";
		} else {
			$_POST['title'] = htmlspecialchars($_POST['title']);
		}
		if(!isset($_POST['id'])) {
			$_POST['id'] = "";
		} else {
			$_POST['id'] = htmlspecialchars($_POST['id']);
		}
		if(!isset($_POST['cost'])) {
			$_POST['cost'] = "";
		} else {
			$_POST['cost'] = floatval($_POST['id']);
		}

		echo "      <form action='$link' method='post'>\n";                          // Form method
		echo "         <input type='hidden' name='type' value='new'>\n";
		echo "         <table class='transparent' align='center'>\n";   // Table headers
		
		/* Show book type name */
		echo "            <tr>\n";
		echo "               <td><b>Book Title</b></td>\n";
		echo "               <td><input type='text' name='title' value='{$_POST['title']}' size=20></td>\n";
		echo "            </tr>\n";
		echo "            <tr>\n";
		echo "               <td><b>Book ID</b></td>\n";
		echo "               <td><input type='text' name='id' value='{$_POST['id']}' size=20></td>\n";
		echo "            </tr>\n";
		echo "            <tr>\n";
		echo "               <td><b>Cost (\$)</b></td>\n";
		echo "               <td><input type='text' name='cost' value='{$_POST['cost']}' size=20></td>\n";
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