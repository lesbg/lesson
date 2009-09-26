<?php
	/*****************************************************************
	 * admin/support/modify.php  (c) 2006 Jonathan Dieter
	 *
	 * Setup support teachers with students
	 *****************************************************************/

	$title            = "Learning Support";

	$link             = "index.php?location=" . dbfuncString2Int("admin/support/modify_action.php") .
						"&amp;next=" .          $_GET['next'];

	$query =    "SELECT Username FROM counselorlist WHERE Username=\"$username\"";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$is_counselor = true;
	} else {
		$is_counselor = false;
	}

	$showalldeps = true;
	include "core/settermandyear.php";
	include "header.php";                                    // Show header
	$showyear = false;
	$showterm = false;
	include "core/titletermyear.php";
	
	
	if($is_admin or $is_counselor) {
		if(!isset($punish_list)) {
			$punish_list = array();
			$punish_str = "";
		} else {
			$punish_str = htmlspecialchars(dbfuncArray2String($punish_list));
		}
		if(isset($errorlist)) {
			echo "      <p class=\"error\" align=\"center\">";
			foreach($errorlist as $error_text) {            // If there were errors, print them.
				echo "         $error_text<br>\n";
			}
			echo "      </p>\n";
		}
		echo "      <form action=\"$link\" method=\"post\" name=\"support\">\n"; // Form method
		echo "         <table align=\"center\" border=\"1\">\n";
		echo "            <tr>\n";
		echo "               <th style=\"width: 33%\">Students supported by teachers:</th>\n";
		echo "               <th style=\"width: 33%\">All students</th>\n";
		echo "               <th style=\"width: 33%\">All support teachers</th>\n";
		echo "            </tr>\n";
		echo "            <tr class=\"std\">\n";
		
		/* Get list of students in subject and store in option list */
		echo "               <td>\n";
		echo "                  <select name=\"removefromsupport[]\" style=\"width: 100%;\" multiple size=16>\n";
		$res =&  $db->query("SELECT support.SupportIndex, tuser.Title AS TTitle, tuser.Surname AS TSurname, " .
							"       suser.FirstName, suser.Surname, suser.Username, class.ClassName FROM " .
							"       support, user AS tuser, user AS suser, class, classterm, classlist " .
							"WHERE  support.WorkerUsername   = tuser.Username " .
							"AND    support.StudentUsername  = suser.Username " .
							"AND    classlist.Username       = suser.Username " .
							"AND    classterm.ClassTermIndex = classlist.ClassTermIndex " .
							"AND    classterm.TermIndex      = $termindex " .
							"AND    class.ClassIndex         = classterm.ClassIndex " .
							"AND    class.YearIndex          = $yearindex "  .
							"ORDER BY tuser.Username, class.Grade, class.ClassName, suser.Username");
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				echo "                     <option value=\"{$row['SupportIndex']}\">{$row['TTitle']} {$row['TSurname']} - {$row['FirstName']} {$row['Surname']} ({$row['Username']}) - {$row['ClassName']}\n";
		}
		echo "                  </select>\n";
		echo "               </td>\n";
		
		/* Create listboxes with classes */
		echo "               <td>\n";
		echo "                  List Class: <select name=\"class\" onchange=\"support.submit()\">\n";
		$res =&  $db->query("SELECT ClassIndex, ClassName FROM class " .
							"WHERE YearIndex = $yearindex " .
							"AND   DepartmentIndex = $depindex " .
							"ORDER BY Grade, ClassName");
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			if(!isset($_POST['class'])) $_POST['class'] = $row['ClassIndex'];
			echo "                     <option value=\"{$row['ClassIndex']}\"";
			if($_POST['class'] == $row['ClassIndex']) echo " selected";
			echo ">{$row['ClassName']}\n";
		}
		echo "                  </select>\n";
		echo "                  <noscript>\n"; // No javascript compatibility
		echo "                     <input type=\"submit\" name=\"action\" value=\"Update\" \>\n";
		echo "                  </noscript><br>\n";
		
		/* Get list of students who are in the active class */
		echo "                  <select name=\"addtosupport[]\" style=\"width: 100%;\" multiple size=14>\n";
		if ($_POST['class'] != "") {
			$_POST['class'] = intval($_POST['class']);
			$query =        "SELECT user.FirstName, user.Surname, user.Username FROM " .
							"       user, classlist, classterm " .
							"WHERE  user.Username = classlist.Username " .
							"AND    classlist.ClassTermIndex = classterm.ClassTermIndex " .
							"AND    classterm.ClassIndex = {$_POST['class']} " .
							"ORDER BY user.Username";
			$res =&  $db->query($query);
			if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
			
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				echo "                     <option value=\"{$row['Username']}\">{$row['Username']} - {$row['FirstName']} " .
														"{$row['Surname']}\n";
			}
		}
		echo "                  </select>\n";
		echo "               </td>\n";

		/* Create listbox with teachers */
		echo "               <td>\n";
		echo "                  <select name=\"teacher\" style=\"width: 100%;\" size=16>\n";

		$query =        "SELECT Title, FirstName, Surname, Username FROM " .
						"       user " .
						"WHERE  user.ActiveTeacher = 1 " .
						"AND    user.SupportTeacher = 1 " .
						//"AND    user.DepartmentIndex = $depindex " .
						"ORDER BY user.Username";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		
		while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$selected = "";
			if(isset($_POST['teacher'])) {
				if($_POST['teacher'] == $row['Username']) {
					$selected = "selected";
				}
			}
			echo "                     <option value=\"{$row['Username']}\" $selected>{$row['Username']} - {$row['Title']} " .
															"{$row['FirstName']} {$row['Surname']}\n";
		}
		echo "                  </select>\n";
		echo "               </td>\n";
		echo "            </tr>\n";
		echo "            <tr class=\"alt\">\n";
		echo "               <td align=\"center\">\n";
		echo "                  <input type=\"submit\" name=\"action\" value=\"&gt;\">\n";
		echo "               </td>\n";
		echo "               <td align=\"center\" colspan=\"2\">\n";
		echo "                  <input type=\"submit\" name=\"action\" value=\"&lt;\">\n";
		echo "               </td>\n";
		echo "            </tr>\n";
		echo "         </table>\n";
		echo "         <p align=\"center\">\n";
		echo "            <input type=\"submit\" name=\"action\" value=\"Done\">&nbsp; \n";
		echo "         </p>\n";
		echo "      </form>\n";
		//foreach($punish_list as $studentusername=>$student) {
		//	echo "      <p>$studentusername = $student</p>\n";
		//}
	} else {  // User isn't authorized to create a punishment
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "admin/support/modify.php", $LOG_DENIED_ACCESS,
					"Tried to issue punishment on behalf of another teacher.");

		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>