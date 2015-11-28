<?php
/**
 * ***************************************************************
 * admin/family/new.php (c) 2015 Jonathan Dieter
 *
 * Show fields to fill in for a new family
 * ***************************************************************
 */

/* Get variables */
$title = "Create New Family Code";
$link = "index.php?location=" .
		 dbfuncString2Int("admin/family/new_or_modify_action.php") . "&amp;next=" .
		 dbfuncString2Int($backLink);

include "header.php"; // Show header

if ($is_admin) {
	echo "      <form action='$link' method='post'>\n"; // Form method
	echo "         <table class='transparent' align='center'>\n";
	echo "            <tr>\n";
	echo "               <td colspan='1'><b>Family Code:</b></td>\n";
	echo "               <td colspan='2'>\n";
	echo "                   <input type='radio' name='autofcode' value='Y' checked>Automatic<br>\n";
	echo "                   <input type='radio' name='autofcode' value='N'><input type='text' name='fcode' size=35>\n";
	echo "               </td>\n";
	echo "            </tr>\n";
	echo "            <tr>\n";
	echo "               <td colspan='1'><b>Surname:</b></td>\n";
	echo "               <td colspan='2'><input type='text' name='sname' size=35></td>\n";
	echo "            </tr>\n";
	echo "         </table>\n";
	echo "         <p></p>\n";
	
	echo "         <p align='center'>\n";
	echo "            <input type='submit' name='action' value='Save'>&nbsp; \n";
	echo "            <input type='submit' name='action' value='Cancel'>&nbsp; \n";
	echo "         </p>\n";
	echo "      </form>";
} else { // User isn't authorized to view or change scores.
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";