<?php
/**
 * ***************************************************************
 * admin/subjecttype/new.php (c) 2005 Jonathan Dieter
 *
 * Create new subject type
 * ***************************************************************
 */

/* Get variables */
$title = "New Subject";
$link = "index.php?location=" .
		 dbfuncString2Int("admin/subjecttype/new_or_modify_action.php") .
		 "&amp;next=" . dbfuncString2Int($backLink);

include "header.php"; // Show header

/* Check whether user is authorized to change subject */
if (dbfuncGetPermission($permissions, $PERM_ADMIN)) {
	if (isset($errorlist)) { // If there were errors, print them, and reset fields
		echo $errorlist;
		$_POST['title'] = htmlspecialchars($_POST['title']);
		$_POST['descr'] = htmlspecialchars($_POST['descr']);
	} else {
		$_POST['title'] = "";
		$_POST['descr'] = "";
	}
	
	echo "      <form action=\"$link\" method=\"post\">\n"; // Form method
	echo "         <table class=\"transparent\" align=\"center\">\n"; // Table headers
	
	/* Show subject type name */
	echo "            <tr>\n";
	echo "               <td>Name of subject type</td>\n";
	echo "               <td><input type=\"text\" name=\"title\" value=\"{$_POST['title']}\" size=35></td>\n";
	echo "            </tr>\n";
	
	/* Show subject type description */
	echo "            <tr>\n";
	echo "               <td>Description</td>\n";
	echo "               <td><input type=\"text\" name=\"descr\" value=\"{$_POST['descr']}\" size=35></td>\n";
	echo "            </tr>\n";
	
	echo "         </table>\n"; // End of table
	echo "         <p align=\"center\">\n";
	echo "            <input type=\"submit\" name=\"action\" value=\"Save\" \>\n";
	echo "            <input type=\"submit\" name=\"action\" value=\"Cancel\" \>\n";
	echo "         </p>\n";
	echo "      </form>\n";
} else { // User isn't authorized to view or change scores.
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
}

include "footer.php";
?>