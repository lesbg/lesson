<?php
/**
 * ***************************************************************
 * admin/user/modify.php (c) 2005, 2006, 2015 Jonathan Dieter
 *
 * Show fields to fill in for a new user
 * ***************************************************************
 */

/* Get variables */
$title = "Modify " . dbfuncInt2String($_GET['keyname']);
$uname = safe(dbfuncInt2String($_GET['key']));
$link = "index.php?location=" .
		 dbfuncString2Int("admin/user/new_or_modify_action.php") . "&amp;key=" .
		 $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] . "&amp;next=" .
		 dbfuncString2Int($backLink);

include "header.php"; // Show header

if ($is_admin) {
	$res = &  $db->query(
					"SELECT Username, FirstName, Surname, Gender, DOB, Permissions, DepartmentIndex, " .
					 "       Title, DateType, DateSeparator, Password2, PhoneNumber, ActiveStudent, " .
					 "       ActiveTeacher, SupportTeacher, User1, User2 FROM user " .
					 "WHERE Username = '$uname'");
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	
	if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$sexM = "";
		$sexF = ""; // Check sex of user and set appropriate
		if ($row['Gender'] == 'M') // variables
			$sexM = "checked";
		else
			$sexF = "checked";
		
		$dateTypeD = "";
		$dateType0 = "";
		$dateType1 = ""; // Check date type of user and set appropriate
		if (! is_null($row['DateType']) && $row['DateType'] == 0) // variables
			$dateType0 = "checked";
		elseif ($row['DateType'] == 1)
			$dateType1 = "checked";
		else
			$dateTypeD = "checked";
		
		$dateSepD = "";
		$dateSep0 = ""; // Check date separator of user and set appropriate
		$dateSep1 = "";
		$dateSep2 = ""; // variables
		if ($row['DateSep'] == "/")
			$dateSep0 = "checked";
		elseif ($row['DateSep'] == "-")
			$dateSep1 = "checked";
		elseif ($row['DateSep'] == ".")
			$dateSep2 = "checked";
		else
			$dateSepD = "checked";
		
		$activeStudent = "";
		$activeTeacher = "";
		$supportTeacher = "";
		$check1 = "";
		$check2 = "";
		if ($row['ActiveStudent'] == 1)
			$activeStudent = "checked";
		if ($row['ActiveTeacher'] == 1)
			$activeTeacher = "checked";
		if ($row['SupportTeacher'] == 1)
			$supportTeacher = "checked";
		
		if ($row['User1'] == 1)
			$check1 = "checked";
		if ($row['User2'] == 1)
			$check2 = "checked";
		
		echo "      <form action='$link' method='post'>\n"; // Form method
		
		echo "         <table class='transparent' align='center'>\n";
		echo "            <tr>\n";
		echo "               <td colspan='1'><b>Username:</b></td>\n";
		echo "               <td colspan='2'>$uname</td>\n";
		echo "            </tr>\n";
		echo "            <tr>\n";
		echo "               <td colspan='1'><b>Title:</b></td>\n";
		echo "               <td colspan='2'><input type='text' name='title' size=35 value='{$row['Title']}'></td>\n";
		echo "            </tr>\n";
		echo "            <tr>\n";
		echo "               <td colspan='1'><b>First Name:</b></td>\n";
		echo "               <td colspan='2'><input type='text' name='fname' size=35 value='{$row['FirstName']}'></td>\n";
		echo "            </tr>\n";
		echo "            <tr>\n";
		echo "               <td colspan='1'><b>Surname:</b></td>\n";
		echo "               <td colspan='2'><input type='text' name='sname' size=35 value='{$row['Surname']}'></td>\n";
		echo "            </tr>\n";
		echo "            <tr><td colspan='3'>&nbsp;</td></tr>\n";
		echo "            <tr>\n";
		echo "               <td colspan='1'><b>Gender:</b><br>\n";
		echo "                   <input type='radio' name='gender' value='M' $sexM>Male<br>\n";
		echo "                   <input type='radio' name='gender' value='F' $sexF>Female</td>\n";
		/*
		 * echo " <td colspan='2'><b>Date of Birth:</b><br>\n";
		 * echo " <input type='text' name='DOB' size=35 value='{$row['DOB']}'><br>&nbsp;</td>\n";
		 */
		echo "            </tr>\n";
		
		if ($row['PhoneNumber'] != "") {
			$row['PhoneNumber'] = "+{$row['PhoneNumber']}";
		}
		echo "            <tr>\n";
		echo "               <td colspan='1'><b>Phone Number:</b></td>\n";
		echo "               <td colspan='2'><input type='text' name='phone' size=35 value='{$row['PhoneNumber']}'></td>\n";
		echo "            </tr>\n";
		echo "            <tr>\n";
		echo "               <td colspan='1'><b>Date Type:</b><br>\n";
		echo "                   <input type='radio' name='datetype' value='D' $dateTypeD><i>LESSON default</i><br>\n";
		echo "                   <input type='radio' name='datetype' value='0' $dateType0>American<br>\n";
		echo "                   <input type='radio' name='datetype' value='1' $dateType1>European<br>&nbsp;<br>&nbsp;</td>\n";
		echo "               <td colspan='1'><b>Date Separator:</b><br>\n";
		echo "                   <input type='radio' name='datesep' value='D' $dateSepD><i>LESSON default</i><br>\n";
		echo "                   <input type='radio' name='datesep' value='/' $dateSep0>/ (ex. 1/1/2000)<br>\n";
		echo "                   <input type='radio' name='datesep' value='-' $dateSep1>- (ex. 1-1-2000)<br>\n";
		echo "                   <input type='radio' name='datesep' value='.' $dateSep2>. (ex. 1.1.2000)<br>&nbsp;</td>\n";
		echo "               <td colspan='1'><b>User Status:</b><br>\n";
		echo "                   <input type='checkbox' name='activestudent' $activeStudent>Active student<br>\n";
		echo "                   <input type='checkbox' name='activeteacher' $activeTeacher>Active teacher<br>\n";
		echo "                   <input type='checkbox' name='supportteacher' $supportTeacher>Support teacher<br>\n";
		echo "                   <input type='checkbox' name='user1' $check1>New student<br>\n";
		echo "                   <input type='checkbox' name='user2' $check2>Special student<br></td>\n";
		echo "            </tr>\n";
		$nres = &  $db->query(
				"SELECT familylist.Username, family.FamilyCode FROM family LEFT OUTER JOIN familylist " . 
				"       ON familylist.FamilyCode=family.FamilyCode AND familylist.Username='$uname' " .
				"ORDER BY FamilyCode");
		if (DB::isError($nres))
			die($nres->getDebugInfo()); // Check for errors in query
		
		if ($nres->numRows() > 0) {
			echo "            <tr>\n";
			echo "               <td><b>Family Code</b></td>\n";
			echo "               <td colspan='2'>\n";
			echo "                  <select multiple name='fcode[]'>\n";
			while ( $nrow = & $nres->fetchRow(DB_FETCHMODE_ASSOC) ) {
				if( $nrow['Username'] == $uname ) {
					$selected = "selected";
				} else {
					$selected = "";
				}
				echo "                     <option value='{$nrow['FamilyCode']}' $selected>{$nrow['FamilyCode']}</option>\n";
			}
			echo "                  </select>\n";
			echo "                  <br/>\n";
			echo "            </tr>\n";
		}
		
		$nres = &  $db->query(
							"SELECT Department, DepartmentIndex FROM department " .
							 "ORDER BY DepartmentIndex");
		if (DB::isError($nres))
			die($nres->getDebugInfo()); // Check for errors in query
		if ($nres->numRows() > 0) {
			echo "            <tr>\n";
			echo "               <td><b>Department</b></td>\n";
			echo "               <td colspan='2'>\n";
			echo "                  <select name='department'>\n";
			if (is_null($row['DepartmentIndex']))
				$default = "selected";
			else
				$default = "";
			
			echo "                     <option value='NULL'>None</option>\n";
			while ( $nrow = & $nres->fetchRow(DB_FETCHMODE_ASSOC) ) {
				if ($row['DepartmentIndex'] == $nrow['DepartmentIndex'])
					$default = "selected";
				else
					$default = "";
				echo "                     <option value='{$nrow['DepartmentIndex']}' $default>{$nrow['Department']}</option>\n";
			}
			echo "                  </select>\n";
			echo "                  <br/>\n";
			echo "            </tr>\n";
		}
		
		$nres = &  $db->query(
				"SELECT groups.GroupIndex, groups.GroupName, groupmem.Member FROM groups LEFT OUTER JOIN groupmem ON " .
				"       groupmem.GroupIndex=groups.GroupIndex AND groupmem.Member='$uname' " .
				"ORDER BY GroupName");
		if (DB::isError($nres))
			die($nres->getDebugInfo()); // Check for errors in query
		
		if ($nres->numRows() > 0) {
			echo "            <tr>\n";
			echo "               <td><b>Groups</b></td>\n";
			echo "               <td colspan='2'>\n";
			while ( $nrow = & $nres->fetchRow(DB_FETCHMODE_ASSOC) ) {
				if( $nrow['Member'] == $uname ) {
					$in_group = " checked";
				} else {
					$in_group = "";
				}
				echo "					<label><input type='checkbox' name='groups[]' value='{$nrow['GroupIndex']}' $in_group>{$nrow['GroupName']}</label><br>\n";
			}
			echo "                  </td>\n";
			echo "            </tr>\n";
		}
		echo "            <tr>\n";
		echo "               <td colspan='3'><i>Note: if you leave the passwords blank, they will not be changed.</i></td>\n";
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
		if (is_null($row['Password2'])) {
			echo "            <tr>\n";
			echo "               <td colspan='3'><i>Secondary password is currently not set</i></td>\n";
			echo "            </tr>\n";
		}
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
		echo "               <td colspan='2'><input type='text' name='perms' size=35 " .
			 "value='{$row['Permissions']}'></td>\n";
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
		echo "      <p align='center'>Error finding user $uname.  Have you already removed them?<p>\n";
		echo "      <p align='center'><a href='$backLink'>Click here to go back</a></p>\n";
	}
} else { // User isn't authorized to view or change users.
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";
?>