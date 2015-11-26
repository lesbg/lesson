<?php
/**
 * ***************************************************************
 * admin/user/new.php (c) 2005, 2015 Jonathan Dieter
 *
 * Show fields to fill in for a new user
 * ***************************************************************
 */

/* Get variables */
$title = "Create New User";
$link = "index.php?location=" .
		 dbfuncString2Int("admin/user/new_or_modify_action.php") . "&amp;next=" .
		 dbfuncString2Int($backLink);
if(isset($_GET['key4']))
	$fcode = safe(dbfuncInt2String($_GET['key4']));
else 
	unset($fcode)
	
include "header.php"; // Show header

if ($is_admin) {
	echo "      <form action='$link' method='post'>\n"; // Form method
	echo "         <table class='transparent' align='center'>\n";
	echo "            <tr>\n";
	echo "               <td colspan='1'><b>Username:</b></td>\n";
	echo "               <td colspan='2'>\n";
	echo "                   <input type='radio' name='autouname' value='Y' checked>Automatic<br>\n";
	echo "                   <input type='radio' name='autouname' value='N'><input type='text' name='uname' size=35>\n";
	echo "               </td>\n";
	echo "            </tr>\n";
	echo "            <tr>\n";
	echo "               <td colspan='1'><b>Title:</b></td>\n";
	echo "               <td colspan='2'><input type='text' name='title' size=35></td>\n";
	echo "            </tr>\n";
	echo "            <tr>\n";
	echo "               <td colspan='1'><b>First Name:</b></td>\n";
	echo "               <td colspan='2'><input type='text' name='fname' size=35></td>\n";
	echo "            </tr>\n";
	echo "            <tr>\n";
	echo "               <td colspan='1'><b>Surname:</b></td>\n";
	echo "               <td colspan='2'><input type='text' name='sname' size=35></td>\n";
	echo "            </tr>\n";
	echo "            <tr><td colspan='3'>&nbsp;</td></tr>\n";
	echo "            <tr>\n";
	echo "               <td colspan='1'><b>Gender:</b><br>\n";
	echo "                   <input type='radio' name='gender' value='M' checked>Male<br>\n";
	echo "                   <input type='radio' name='gender' value='F'>Female</td>\n";
	/*
	 * echo " <td colspan='2'><b>Date of Birth:</b><br>\n";
	 * echo " <input type='text' name='DOB' size=35><br>&nbsp;</td>\n";
	 */
	echo "            </tr>\n";
	echo "            <tr>\n";
	echo "               <td colspan='1'><b>Phone Number:</b></td>\n";
	echo "               <td colspan='2'><input type='text' name='phone' size=35></td>\n";
	echo "            </tr>\n";
	echo "            <tr>\n";
	echo "               <td colspan='1'><b>Date Type:</b><br>\n";
	echo "                   <input type='radio' name='datetype' value='D' checked><i>LESSON default</i><br>\n";
	echo "                   <input type='radio' name='datetype' value='0'>American<br>\n";
	echo "                   <input type='radio' name='datetype' value='1'>European<br>&nbsp;<br>&nbsp;</td>\n";
	echo "               <td colspan='1'><b>Date Separator:</b><br>\n";
	echo "                   <input type='radio' name='datesep' value='D' checked><i>LESSON default</i><br>\n";
	echo "                   <input type='radio' name='datesep' value='/'>/ (ex. 1/1/2000)<br>\n";
	echo "                   <input type='radio' name='datesep' value='-'>- (ex. 1-1-2000)<br>\n";
	echo "                   <input type='radio' name='datesep' value='.'>. (ex. 1.1.2000)<br>&nbsp;</td>\n";
	echo "               <td colspan='1'><b>User Status:</b><br>\n";
	echo "                   <input type='checkbox' name='activestudent' checked>Active student<br>\n";
	echo "                   <input type='checkbox' name='activeteacher'>Active teacher<br>\n";
	echo "                   <input type='checkbox' name='supportteacher'>Support teacher<br>\n";
	echo "                   <input type='checkbox' name='user1'>New student<br>\n";
	echo "                   <input type='checkbox' name='user2'>Special student<br></td>\n";
	echo "            </tr>\n";
	$res = &  $db->query(
			"SELECT FamilyCode FROM family " .
			"ORDER BY FamilyCode");
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	
	if ($res->numRows() > 0) {
		echo "            <tr>\n";
		echo "               <td><b>Family Code</b></td>\n";
		echo "               <td colspan='2'>\n";
		echo "                  <select name='fcode'>\n";
		echo "                     <option value=''>None</option>\n";
		while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
			if(isset($fcode) && $fcode == $row['FamilyCode']) {
				$selected = " selected";
			} else {
				$selected = "";
			}
			echo "                     <option value='{$row['FamilyCode']}' $selected>{$row['FamilyCode']}</option>\n";
		}
		echo "                  </select>\n";
		echo "                  <br/>\n";
		echo "            </tr>\n";
	}
	
	$res = &  $db->query(
					"SELECT Department, DepartmentIndex FROM department " .
					 "ORDER BY DepartmentIndex");
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	
	if ($res->numRows() > 0) {
		echo "            <tr>\n";
		echo "               <td><b>Department</b></td>\n";
		echo "               <td colspan='2'>\n";
		echo "                  <select name='department'>\n";
		echo "                     <option value='NULL'>None</option>\n";
		while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
			echo "                     <option value='{$row['DepartmentIndex']}'>{$row['Department']}</option>\n";
		}
		echo "                  </select>\n";
		echo "                  <br/>\n";
		echo "            </tr>\n";
	}
	echo "            <tr>\n";
	echo "               <td colspan='3'><i>Note: if you leave the primary password blank, it will default to the user's username.</i></td>\n";
	echo "            </tr>\n";
	echo "            <tr>\n";
	echo "               <td colspan='1'><b>New Primary Password:</b></td>\n";
	echo "               <td colspan='2'><input type='password' name='password' size=35></td>\n";
	echo "            </tr>\n";
	echo "            <tr>\n";
	echo "               <td colspan='1'><b>Confirm New Primary Password:</b></td>\n";
	echo "               <td colspan='2'><input type='password' name='confirmpassword' size=35></td>\n";
	echo "            </tr>\n";
	echo "            <tr><td colspan='3'>&nbsp;</td></tr>\n";
	echo "            <tr>\n";
	echo "               <td colspan='3'><i>Note: if you leave the secondary password blank, it will not be set.</i></td>\n";
	echo "            </tr>\n";
	echo "            <tr>\n";
	echo "               <td colspan='1'><b>New Secondary Password:</b></td>\n";
	echo "               <td colspan='2'><input type='password' name='password2' size=35></td>\n";
	echo "            </tr>\n";
	echo "            <tr>\n";
	echo "               <td colspan='1'><b>Confirm Secondary Primary Password:</b></td>\n";
	echo "               <td colspan='2'><input type='password' name='confirmpassword2' size=35></td>\n";
	echo "            </tr>\n";
	echo "            <tr><td colspan='3'>&nbsp;</td></tr>\n";
	echo "            <tr>\n";
	echo "               <td colspan='1'><b>Permissions:</b></td>\n";
	echo "               <td colspan='2'><input type='text' name='perms' size=35></td>\n";
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
?>