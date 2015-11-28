<?php
/**
 * ***************************************************************
 * admin/family/modify.php (c) 2015 Jonathan Dieter
 *
 * Show fields to fill in for changing a family's information
 * ***************************************************************
 */

/* Get variables */
$fcode = safe(dbfuncInt2String($_GET['key']));
$title = "Modify " . dbfuncInt2String($_GET['keyname']) . " family ($fcode)";
$link = "index.php?location=" .
		 dbfuncString2Int("admin/family/new_or_modify_action.php") . "&amp;key=" .
		 $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] . "&amp;next=" .
		 dbfuncString2Int($backLink);

include "header.php"; // Show header

if ($is_admin) {
	$res = &  $db->query(
					"SELECT FamilyCode, FamilyName FROM family " .
					 "WHERE FamilyCode = '$fcode'");
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	
	if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		echo "      <form action='$link' method='post'>\n"; // Form method
		echo "         <table class='transparent' align='center'>\n";
		echo "            <tr>\n";
		echo "               <td colspan='1'><b>Family Code:</b></td>\n";
		echo "               <td colspan='2'>\n";
		echo "                   <input type='hidden' name='fcode' value='$fcode' />$fcode\n";
		echo "               </td>\n";
		echo "            </tr>\n";
		echo "            <tr>\n";
		echo "               <td colspan='1'><b>Surname:</b></td>\n";
		echo "               <td colspan='2'><input type='text' name='sname' value='{$row['FamilyName']}' size=35></td>\n";
		echo "            </tr>\n";
		echo "         </table>\n";
		echo "         <p></p>\n";
		
		echo "         <p align='center'>\n";
		echo "            <input type='submit' name='action' value='Update' />&nbsp; \n";
		echo "            <input type='submit' name='action' value='Delete' />&nbsp; \n";
		echo "            <input type='submit' name='action' value='Cancel' />&nbsp; \n";
		echo "         </p>\n";
		echo "      </form>";
	} else {
		echo "      <p align='center'>Error finding family code $fcode.  Have you already removed them?<p>\n";
		echo "      <p align='center'><a href='$backLink'>Click here to go back</a></p>\n";
	}
} else { // User isn't authorized to view or change family codes.
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";