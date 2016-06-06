<?php
/**
 * ***************************************************************
 * admin/user/modify.php (c) 2005, 2015-2016 Jonathan Dieter
 *
 * Show fields to fill in for a user
 * ***************************************************************
 */
if(isset($_GET['next'])) {
	$backLink = dbfuncInt2String($_GET['next']);
}

$link = "index.php?location=" .
		dbfuncString2Int("admin/user/new_or_modify_action.php") . "&amp;next=" .
		dbfuncString2Int($backLink);

/* Get variables */
if(isset($_GET['key'])) {
	$title = "Modify " . htmlspecialchars(dbfuncInt2String($_GET['keyname']), ENT_QUOTES);
	$uname = safe(dbfuncInt2String($_GET['key']));
	$modify = True;
	$link .= "&amp;key="     . $_GET['key'] .
	         "&amp;keyname=" . $_GET['keyname'];
} else {
	$title = "Create New User";
	if(isset($_GET['keyname'])) {
		$new_fname = dbfuncInt2String($_GET['keyname']);
	}
	$modify = False;
}

if(isset($_GET['key4']))
	$fcode = safe(dbfuncInt2String($_GET['key4']));
else 
	unset($fcode);
	
include "header.php"; // Show header

if ($is_admin) {
	
	if(!isset($_SESSION['post'])) {
		$_SESSION['post'] = array();
	}
	$pval = array();
	foreach($_POST as $key => $value) {
		$_SESSION['post'][$key] = $value;
	}
	
	if(isset($_GET['key2']) && dbfuncInt2String($_GET['key2']) == '1') {
		$_SESSION['post']['show_family'] = '1';
		$show_family = False;
	} else {
		if(isset($_SESSION['post']['show_family']) && $_SESSION['post']['show_family'] == '1') {
			$show_family = False;
		} else {
			$_SESSION['post']['show_family'] = '0';
			$show_family = True;
		}
	}
	if(isset($_GET['type'])) {
		$_SESSION['post']['new_user_type'] = dbfuncInt2String($_GET['type']);
		$new_user_type = $_SESSION['post']['new_user_type'];
	} else {
		if(isset($_SESSION['post']['new_user_type'])) {
			$new_user_type = $_SESSION['post']['new_user_type'];
		} else {
			$new_user_type = 'a';
		}
	}
	
	$pwd2 = NULL;
	
	if($modify) {
		$res = &  $db->query(
				"SELECT Username, FirstName, Surname, Gender, DOB, Permissions, DepartmentIndex, " .
				"       Title, DateType, DateSeparator, Password2, PhoneNumber, ActiveStudent, " .
				"       ActiveTeacher, SupportTeacher, User1, User2 FROM user " .
				"WHERE Username = '$uname'");
		if (DB::isError($res))
			die($res->getDebugInfo()); // Check for errors in query
	
		if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$_SESSION['post']['uname'] = $uname;
			if(!isset($_SESSION['post']['fname'])) $_SESSION['post']['fname'] = $row['FirstName'];
			if(!isset($_SESSION['post']['sname'])) $_SESSION['post']['sname'] = $row['Surname'];
			if(!isset($_SESSION['post']['gender'])) $_SESSION['post']['gender'] = $row['Gender'];
			if(!isset($_SESSION['post']['dob'])) $_SESSION['post']['dob'] = $row['DOB'];
			if(!isset($_SESSION['post']['perms'])) $_SESSION['post']['perms'] = $row['Permissions'];
			if(!isset($_SESSION['post']['department'])) $_SESSION['post']['department'] = $row['DepartmentIndex'];
			if(!isset($_SESSION['post']['title'])) $_SESSION['post']['title'] = $row['Title'];
			if(!isset($_SESSION['post']['datetype'])) {
				if(!is_null($row['DateType']))
					$_SESSION['post']['datetype'] = $row['DateType'];
				else 
					$_SESSION['post']['datetype'] = 'D';
			}
			if(!isset($_SESSION['post']['datesep'])) {
				if(!is_null($row['DateSeparator']))
					$_SESSION['post']['datesep'] = $row['DateSeparator'];
				else 
					$_SESSION['post']['datesep'] = 'D';
			}
			$pwd2 = $row['Password2'];
			if(!isset($_SESSION['post']['activestudent'])) $_SESSION['post']['activestudent'] = $row['ActiveStudent'];
			if(!isset($_SESSION['post']['activeteacher'])) $_SESSION['post']['activeteacher'] = $row['ActiveTeacher'];
			if(!isset($_SESSION['post']['supportteacher'])) $_SESSION['post']['supportteacher'] = $row['SupportTeacher'];
			if(!isset($_SESSION['post']['user1'])) $_SESSION['post']['user1'] = $row['User1'];
			if(!isset($_SESSION['post']['user2'])) $_SESSION['post']['user2'] = $row['User2'];
			
			if($show_family) {
				if(!isset($_SESSION['post']['fcode'])) {
					$_SESSION['post']['fcode'] = array();
					
					$query =	"SELECT FamilyCode, Guardian FROM familylist " .
								"WHERE Username='$uname' " .
								"ORDER BY FamilyCode";
					$res = &  $db->query($query);
					if (DB::isError($res))
						die($res->getDebugInfo()); // Check for errors in query
				
					while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
						$_SESSION['post']['fcode'][] = array($row['FamilyCode'], $row['Guardian']);
					}
				}
			}
			if(!isset($_SESSION['post']['groups'])) {
				$_SESSION['post']['groups'] = array();
				
				$query =	"SELECT GroupID FROM groupgenmem " .
							"WHERE Username='$uname' " .
							"ORDER BY GroupID";
				$res = &  $db->query($query);
				if (DB::isError($res))
					die($res->getDebugInfo()); // Check for errors in query
					
				while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
					$_SESSION['post']['groups'][] = $row['GroupID'];
				}
			}
			if(!isset($_SESSION['post']['phone'])) {
				$_SESSION['post']['phone'] = array();
			
				$query =	"SELECT PhoneIndex, Number, SortOrder, Type, Comment FROM phone " .
							"WHERE Username='$uname' " .
							"ORDER BY SortOrder";
				$res = &  $db->query($query);
				if (DB::isError($res))
					die($res->getDebugInfo()); // Check for errors in query
						
				while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
					$_SESSION['post']['phone'][] = array($row['PhoneIndex'], $row['Number'], $row['Type'], $row['Comment']);
				}
				$_SESSION['post']['phone'][] = array(-1, "", 2, "");
			}
			if(!isset($_SESSION['post']['phone_remove'])) {
				$_SESSION['post']['phone_remove'] = array();
			}				
		}
	} else {
		if($new_user_type != 'a') {
			if(!isset($_SESSION['post']['sname'])) $_SESSION['post']['sname'] = $new_fname;
			if($new_user_type == 'f') {
				if(!isset($_SESSION['post']['gender'])) {
					$_SESSION['post']['title'] = "Mr.";
					$_SESSION['post']['gender'] = "M";
				}
			} elseif ($new_user_type == 'm') {
				if(!isset($_SESSION['post']['gender'])) {
					$_SESSION['post']['title'] = "Mrs.";
					$_SESSION['post']['gender'] = "F";
				}
			} elseif ($new_user_type == 's') {
				if(!isset($_SESSION['post']['activestudent'])) $_SESSION['post']['activestudent'] = '1';
			}
		}
	}
	
	foreach($_SESSION['post'] as $key => $value) {
		if(is_string($value))
			$pval[$key] = "value='" . htmlspecialchars($value, ENT_QUOTES) . "'";
	}
	
	echo "      <form action='$link' method='post'>\n"; // Form method
	echo "         <p align='center'>\n";
	if(!$modify) {
		echo "            <input type='submit' name='action' value='Save'>&nbsp; \n";
	} else {
		echo "            <input type='submit' name='action' value='Update'>&nbsp; \n";
		echo "            <input type='submit' name='action' value='Delete'>&nbsp; \n";
	}
	echo "            <input type='submit' name='action' value='Cancel'>&nbsp; \n";
	echo "         </p>\n";
	echo "         <table class='transparent' align='center'>\n";
	echo "            <tr>\n";
	echo "               <td colspan='1'><b>Username:</b></td>\n";
	echo "               <td colspan='2'>\n";
	if(!$modify) {
		$chcy = "";
		$chcn = "";
		if(!$modify) {
			if(isset($_SESSION['post']['autouname']) && $_SESSION['post']['autouname'] == 'N') {
				$chcn = "checked";
			} else {
				$chcy = "checked";
			}
		}
		echo "                   <input type='radio' name='autouname' value='Y' $chcy>Automatic<br>\n";
		echo "                   <input type='radio' name='autouname' value='N' $chcn><input type='text' name='uname' size=35 {$pval['uname']}>\n";
	} else {
		echo "                   <input type='hidden' name='autouname' value='N'>\n";
		echo "                   <input type='hidden' name='uname' value='$uname'>\n";
		echo "                   $uname\n";
	}
	echo "                   <input type='hidden' name='show_family' value='{$_SESSION['post']['show_family']}'>\n";
	echo "                   <input type='hidden' name='new_user_type' value='{$_SESSION['post']['new_user_type']}'>\n";
	echo "               </td>\n";
	echo "            </tr>\n";
	echo "            <tr>\n";
	echo "               <td colspan='1'><b>Title:</b></td>\n";
	echo "               <td colspan='2'><input type='text' name='title' size=35 {$pval['title']}></td>\n";
	echo "            </tr>\n";
	echo "            <tr>\n";
	echo "               <td colspan='1'><b>First Name:</b></td>\n";
	echo "               <td colspan='2'><input type='text' name='fname' size=35 {$pval['fname']}></td>\n";
	echo "            </tr>\n";
	echo "            <tr>\n";
	echo "               <td colspan='1'><b>Surname:</b></td>\n";
	echo "               <td colspan='2'><input type='text' name='sname' size=35 {$pval['sname']}></td>\n";
	echo "            </tr>\n";
	echo "            <tr><td colspan='3'>&nbsp;</td></tr>\n";
	$chcm = "";
	$chcf = "";
	if(isset($_SESSION['post']['gender']) && $_SESSION['post']['gender'] == 'F') {
		$chcf = "checked";
	} else {
		$chcm = "checked";
	}
	echo "            <tr>\n";
	echo "               <td colspan='1'><b>Gender:</b><br>\n";
	echo "                   <input type='radio' name='gender' value='M' $chcm>Male<br>\n";
	echo "                   <input type='radio' name='gender' value='F' $chcf>Female</td>\n";
	/*
	 * echo " <td colspan='2'><b>Date of Birth:</b><br>\n";
	 * echo " <input type='text' name='DOB' size=35><br>&nbsp;</td>\n";
	 */
	echo "            </tr>\n";
	echo "            <tr>\n";
	echo "               <td><b>Phone number</b></td>\n";
	echo "               <td><b>Comment</b></td>\n";
	echo "               <td>&nbsp;</td>\n";
	echo "            </tr>\n";
	
	if(isset($_SESSION['post']['phone'])) {
		$phone_matrix = array(
				          array(2, 'Mobile'),
				          array(1, 'Home'),
				          array(3, 'Work'),
				          array(4, 'Other')
				        );
		foreach($_SESSION['post']['phone'] as $key => $phone) {
			$pindex = htmlspecialchars($phone[0]);
			$pnum = htmlspecialchars($phone[1]);
			$ptype = intval($phone[2]);
			$pcomment = htmlspecialchars($phone[3]);
			if(isset($fcode[1]) && ($fcode[1] === "on" || intval($fcode[1]) === 1)) {
				$guardian = "checked";
			} else {
				$guardian = "";
			}
			echo "            <tr>\n";
			echo "               <td><input type='hidden' name='phone[$key][0]' value='$pindex'><input type='text' name='phone[$key][1]' value='$pnum'></td>\n";
			echo "               <td><input type='text' name='phone[$key][3]' value='$pcomment'>&nbsp;\n";
			echo "                  <select name='phone[$key][2]'>\n";
			foreach($phone_matrix as $item) {
				if($ptype == $item[0]) 
					$selected = " selected";
				else
					$selected = "";
				echo "                     <option value='${item[0]}' $selected>${item[1]}</option>\n";
			}
			echo "                  </select>\n";
			echo "               </td>\n";
			echo "               <td><input type='submit' name='phoneaction-$pindex' value='▲' /><input type='submit' name='phoneaction-$pindex' value='▼' /><input type='submit' name='phoneaction-$pindex' value='-' /></td>\n";
			echo "            </tr>\n";
		}

	}
	echo "            <tr>\n";
	echo "               <td>&nbsp;</td>\n";
	echo "               <td>&nbsp;</td>\n";
	echo "               <td>\n";
	echo "                  <input type='submit' name='phoneaction' value='+'>\n";
	$count = 0;
	foreach($_SESSION['post']['phone_remove'] as $remove) {
		$count += 1;
		$remove = intval($remove);
		echo "                  <input type='hidden' name='phone_remove[$count]' value='$remove'>\n";
	}
	echo "               </td>\n";
	echo "            </tr>\n";

	$chcd = "";
	$chc0 = "";
	$chc1 = "";
	if(isset($_SESSION['post']['datetype']) && $_SESSION['post']['datetype'] == '0') {
		$chc0 = "checked";
	} elseif(isset($_SESSION['post']['datetype']) && $_SESSION['post']['datetype'] == '1') {
		$chc1 = "checked";
	} else {
		$chcd = "checked";
	}
	echo "            <tr>\n";
	echo "               <td colspan='1'><b>Date Type:</b><br>\n";
	echo "                   <input type='radio' name='datetype' value='D' $chcd><i>LESSON default</i><br>\n";
	echo "                   <input type='radio' name='datetype' value='0' $chc0>American<br>\n";
	echo "                   <input type='radio' name='datetype' value='1' $chc1>European<br>&nbsp;<br>&nbsp;</td>\n";
	$chcd = "";
	$chcslash = "";
	$chcdash = "";
	$chcperiod = "";
	if(isset($_SESSION['post']['datesep']) && $_SESSION['post']['datesep'] == '/') {
		$chcslash = "checked";
	} elseif(isset($_SESSION['post']['datesep']) && $_SESSION['post']['datesep'] == '-') {
		$chcdash = "checked";
	} elseif(isset($_SESSION['post']['datesep']) && $_SESSION['post']['datesep'] == '.') {
		$chcperiod = "checked";
	} else {
		$chcd = "checked";
	}
	echo "               <td colspan='1'><b>Date Separator:</b><br>\n";
	echo "                   <input type='radio' name='datesep' value='D' $chcd><i>LESSON default</i><br>\n";
	echo "                   <input type='radio' name='datesep' value='/' $chcslash>/ (ex. 1/1/2000)<br>\n";
	echo "                   <input type='radio' name='datesep' value='-' $chcdash>- (ex. 1-1-2000)<br>\n";
	echo "                   <input type='radio' name='datesep' value='.' $chcperiod>. (ex. 1.1.2000)<br>&nbsp;</td>\n";
	echo "               <td colspan='1'><b>Groups:</b><br>\n";
	$query =	"SELECT groups.GroupID, groups.GroupName FROM " .
			"       groups, groupmem " .
			"WHERE groupmem.Member=CONCAT('@', groups.GroupTypeID) " .
			"AND   groupmem.GroupID='userinfo' " .
			"AND   groups.YearIndex=$yearindex " .
			"ORDER BY groups.GroupName, groups.YearIndex";
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	
	while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
		$chc = "";
		if(isset($_SESSION['post']['groups']) && in_array($row['GroupID'], $_SESSION['post']['groups'])) {
			$chc = 'checked';
		}

		echo "					<label><input type='checkbox' name='groups[]' value='{$row['GroupID']}' $chc>{$row['GroupName']}</label><br>\n";
	}
	echo "               </td>\n";
	echo "            </tr>\n";

	if($show_family) {
		$res = &  $db->query(
				"SELECT FamilyCode FROM family " .
				"ORDER BY FamilyCode");
		if (DB::isError($res))
			die($res->getDebugInfo()); // Check for errors in query
		
		if ($res->numRows() > 0) {
			echo "            <tr>\n";
			echo "               <td><b>Family Code</b></td>\n";
			echo "               <td><b>Guardian</b></td>\n";
			echo "               <td>&nbsp;</td>\n";
			echo "            </tr>\n";
			if(isset($_SESSION['post']['fcode'])) {
				foreach($_SESSION['post']['fcode'] as $key => $fcode) {
					$fcodep = htmlspecialchars($fcode[0]);
					if(isset($fcode[1]) && ($fcode[1] === "on" || intval($fcode[1]) === 1)) {
						$guardian = "checked";
					} else {
						$guardian = "";
					}
					echo "            <tr>\n";
					echo "               <td><input type='hidden' name='fcode[$key][0]' value='$fcodep'>$fcodep</td>\n";
					echo "               <td><input type='checkbox' name='fcode[$key][1]' $guardian /></td>\n";
					echo "               <td><input type='submit' name='action-$fcodep' value='-' /></td>\n";
					echo "            </tr>\n";
				}
			}
			echo "            <tr>\n";
			echo "               <td>&nbsp;</td>\n";
			echo "               <td>&nbsp;</td>\n";
			echo "               <td><input type='submit' name='action' value='+'></td>\n";
			echo "            </tr>\n";
		}
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
		$chc = "";
		if(!isset($_SESSION['post']['department']) || $_SESSION['post']['department'] == 'NULL') {
			$chc = 'selected';
		}
		echo "                     <option value='NULL'>None</option>\n";
		while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
			$chc = "";
			if(isset($_SESSION['post']['department']) && $_SESSION['post']['department'] == $row['DepartmentIndex']) {
				$chc = 'selected';
			}
			echo "                     <option value='{$row['DepartmentIndex']}' $chc>{$row['Department']}</option>\n";
		}
		echo "                  </select>\n";
		echo "                  <br/>\n";
		echo "            </tr>\n";
	}
	
	echo "            <tr>\n";
	if(!$modify) {
		echo "               <td colspan='3'><i>Note: if you leave the primary password blank, it will default to the user's username.</i></td>\n";
	} else {
		echo "               <td colspan='3'><i>Note: if you leave the passwords blank, they will not be changed.</i></td>\n";
	}
	echo "            </tr>\n";
	echo "            <tr>\n";
	echo "               <td colspan='1'><b>New Primary Password:</b></td>\n";
	echo "               <td colspan='2'><input type='password' name='password' size=35 {$pval['password']}></td>\n";
	echo "            </tr>\n";
	echo "            <tr>\n";
	echo "               <td colspan='1'><b>Confirm New Primary Password:</b></td>\n";
	echo "               <td colspan='2'><input type='password' name='confirmpassword' size=35 {$pval['confirmpassword']}></td>\n";
	echo "            </tr>\n";
	echo "            <tr><td colspan='3'>&nbsp;</td></tr>\n";
	if(!$modify) {
		echo "            <tr>\n";
		echo "               <td colspan='3'><i>Note: if you leave the secondary password blank, it will not be set.</i></td>\n";
		echo "            </tr>\n";
	} elseif (is_null($pwd2) || $pwd2 == "!!") {
		echo "            <tr>\n";
		echo "               <td colspan='3'><i>Secondary password is currently not set</i></td>\n";
		echo "            </tr>\n";
	}
	echo "            <tr>\n";
	echo "               <td colspan='1'><b>New Secondary Password:</b></td>\n";
	echo "               <td colspan='2'><input type='password' name='password2' size=35 {$pval['password2']}></td>\n";
	echo "            </tr>\n";
	echo "            <tr>\n";
	echo "               <td colspan='1'><b>Confirm Secondary Primary Password:</b></td>\n";
	echo "               <td colspan='2'><input type='password' name='confirmpassword2' size=35 {$pval['confirmpassword2']}></td>\n";
	echo "            </tr>\n";
	echo "            <tr><td colspan='3'>&nbsp;</td></tr>\n";
	echo "            <tr>\n";
	echo "               <td colspan='1'><b>Permissions:</b></td>\n";
	echo "               <td colspan='2'><input type='text' name='perms' size=35 {$pval['perms']}></td>\n";
	echo "            </tr>\n";
	echo "         </table>\n";
	echo "         <p></p>\n";
	
	echo "         <p align='center'>\n";
	if(!$modify) {
		echo "            <input type='submit' name='action' value='Save'>&nbsp; \n";
	} else {
		echo "            <input type='submit' name='action' value='Update'>&nbsp; \n";
		echo "            <input type='submit' name='action' value='Delete'>&nbsp; \n";
	}
	echo "            <input type='submit' name='action' value='Cancel'>&nbsp; \n";
	echo "         </p>\n";
	echo "      </form>";
	unset($_SESSION['post']);
} else { // User isn't authorized to view or change scores.
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";
?>