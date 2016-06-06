<?php
/**
 * ***************************************************************
 * teacher/casenote/new.php (c) 2006 Jonathan Dieter
 *
 * Create new casenote
 * ***************************************************************
 */

/* Get variables */
$student = dbfuncInt2String($_GET['keyname']);
$studentusername = safe(dbfuncInt2String($_GET['key']));
$studentfirstname = dbfuncInt2String($_GET['keyname2']);

$title = "New Casenote for $student";

$link = "index.php?location=" .
		 dbfuncString2Int("teacher/casenote/new_action.php") . "&amp;key=" .
		 $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] . "&amp;keyname2=" .
		 $_GET['keyname2'];

include "core/settermandyear.php";

/* Check whether current user is principal */
$res = &  $db->query(
				"SELECT Username FROM principal " .
				 "WHERE Username=\"$username\" AND Level=1");
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
	$is_principal = true;
} else {
	$is_principal = false;
}

/* Check whether current user is head of department for student */
$query = "SELECT hod.Username FROM hod, class, classterm, classlist " .
		 "WHERE hod.Username = '$username' " .
		 "AND   hod.DepartmentIndex = class.DepartmentIndex " .
		 "AND   classlist.Username = '$studentusername' " .
		 "AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
		 "AND   classterm.TermIndex = $currentterm " .
		 "AND   class.ClassIndex = classterm.ClassIndex " .
		 "AND   class.YearIndex = $currentyear";
$res = &  $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
	$is_hod = true;
} else {
	$is_hod = false;
}

/* Check whether current user is a counselor */
$res = &  $db->query(
				"SELECT Username FROM counselorlist " .
				 "WHERE Username=\"$username\"");
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
	$is_counselor = true;
} else {
	$is_counselor = false;
}

/* Check whether current user is class teacher for this student this year */
$query = "SELECT class.ClassTeacherUsername FROM class, classterm, classlist " .
		 "WHERE class.ClassTeacherUsername = '$username' " .
		 "AND   classlist.Username = '$studentusername' " .
		 "AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
		 "AND   classterm.TermIndex = $currentterm " .
		 "AND   class.ClassIndex = classterm.ClassIndex " .
		 "AND   class.YearIndex = $currentyear";
$res = &  $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
	$is_classteacher = true;
} else {
	$is_classteacher = false;
}

/* Check whether current user is a support teacher for this student */
$query = "SELECT user.FirstName, user.Surname, user.Username FROM " .
		 "       user INNER JOIN groupgenmem ON (user.Username=groupgenmem.Username) " .
		 "            INNER JOIN groups USING (GroupID) " .
		 "WHERE user.Username='$username' " .
		 "AND   groups.GroupTypeID='supportteacher' " .
		 "AND   groups.YearIndex=$yearindex " .
		 "ORDER BY user.Username";
$res = &  $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query
if ($res->numRows() > 0) {
	$is_supportteacher = true;
} else {
	$is_supportteacher = false;
}

/* Check whether current user is a teacher for this student */
$query = "SELECT user.FirstName, user.Surname, user.Username FROM " .
		 "       user INNER JOIN groupgenmem ON (user.Username=groupgenmem.Username) " .
		 "            INNER JOIN groups USING (GroupID) " .
		 "WHERE user.Username='$username' " .
		 "AND   groups.GroupTypeID='activeteacher' " .
		 "AND   groups.YearIndex=$yearindex " .
		 "ORDER BY user.Username";
$res = &  $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query
if ($res->numRows() > 0) {
	$is_teacher = true;
} else {
	$is_teacher = false;
}


/* Disabled at Steve's request */
/* if($is_principal or $is_hod or $is_counselor) { */
$extra_js = "casenotes.js";
/* } */

include "header.php"; // Show header

if ($is_principal or $is_hod or $is_counselor or $is_classteacher or
	 $is_supportteacher or $is_teacher) {
	log_event($LOG_LEVEL_EVERYTHING, "teacher/casenote/new.php", $LOG_TEACHER, 
			"Starting new casenote for $student.");
	if ($is_principal or $is_hod or $is_counselor) {
		echo "      <script language=\"JavaScript\" type=\"text/javascript\">\n";
		echo "         window.onload = check_counselor_list;\n";
		echo "      </script>\n";
	}
	echo "      <form action=\"$link\" method=\"post\" name=\"casenote\">\n"; // Form method
	echo "         <table border=\"0\" class=\"transparent\" align=\"center\" width=\"600px\">\n";
	echo "            <tr>\n";
	echo "               <td>\n";
	echo "                  Who should be allowed to see this casenote?<br>\n";
	/*
	 * Private comments are now disabled
	 * echo " <label for=\"level0\">\n";
	 * echo " <input type=\"radio\" name=\"level\" value=\"0\" id=\"level0\" onchange=\"check_counselor_list();\">\n";
	 * echo " <span class=\"cn-level0\">Only myself (Private)</span>\n";
	 * echo " </label><br>\n";
	 */
	/* Disable Level 5 if people aren't in right position - disabled at Steve's request */
	/*
	 * if($is_principal or $is_hod or $is_counselor) {
	 * $disabled = "";
	 * } else {
	 * $disabled = "disabled=\"true\"";
	 * }
	 */
	echo "                  <label for=\"level5\">\n";
	echo "                  <input type=\"radio\" name=\"level\" value=\"5\" id=\"level5\" onchange=\"check_counselor_list();\" $disabled>\n";
	echo "                  <span class=\"cn-level5\">Myself and the principal (Level 5)</span>\n";
	echo "                  </label><br>\n";
	/* Disable Level 4 if people aren't in right position - disabled at Steve's request */
	/*
	 * if($is_principal or $is_hod or $is_counselor or $is_classteacher) {
	 * $disabled = "";
	 * } else {
	 * $disabled = "disabled=\"true\"";
	 * }
	 */
	echo "                  <label for=\"level4\">\n";
	echo "                  <input type=\"radio\" name=\"level\" value=\"4\" id=\"level4\" onchange=\"check_counselor_list();\" $disabled>\n";
	echo "                  <span class=\"cn-level4\">As above and the head of department (Level 4)</span>\n";
	echo "                  </label><br>\n";
	/* Disable Level 3 if people aren't in right position - disabled at Steve's request */
	/*
	 * if($is_principal or $is_hod or $is_counselor or $is_classteacher) {
	 * $disabled = "";
	 * } else {
	 * $disabled = "disabled=\"true\"";
	 * }
	 */
	echo "                  <label for=\"level3\">\n";
	echo "                  <input type=\"radio\" name=\"level\" value=\"3\" id=\"level3\" onchange=\"check_counselor_list();\" $disabled>\n";
	echo "                  <span class=\"cn-level3\">As above and specified counselors (Level 3)</span>\n";
	echo "                  </label><br>\n";
	
	echo "                  <label for=\"level2\">\n";
	echo "                  <input type=\"radio\" name=\"level\" value=\"2\" id=\"level2\" onchange=\"check_counselor_list();\" checked>\n";
	echo "                  <span class=\"cn-level2\">As above and $studentfirstname's class teacher (Level 2)</span>\n";
	echo "                  </label><br>\n";
	echo "                  <label for=\"level1\">\n";
	echo "                  <input type=\"radio\" name=\"level\" value=\"1\" id=\"level1\" onchange=\"check_counselor_list();\">\n";
	echo "                  <span class=\"cn-level1\">As above and all of $studentfirstname's teachers (Level 1)</span>\n";
	echo "                  </label><br>\n";
	echo "               </td>\n";
	/* Disable counselor list if people aren't in right position - disabled at Steve's request */
	/*
	 * if($is_principal or $is_hod or $is_counselor) {
	 * $disabled = "";
	 * } else {
	 * $disabled = "disabled=\"true\"";
	 * }
	 */
	$disabled = "disabled=\"true\"";
	echo "               <td>\n";
	echo "                  Counselors:<br>\n";
	echo "                  <select name=\"counselor_list[]\" width=\"600px\" multiple size=7 id=\"counselor_list\" $disabled>\n";
	$res = &  $db->query(
					"SELECT user.FirstName, user.Surname, user.Username FROM " .
					 "       user, counselorlist " .
					 "WHERE counselorlist.Username = user.Username " .
					 "ORDER BY user.Username");
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
		echo "                     <option value=\"{$row['Username']}\" selected>{$row['FirstName']} " .
			 "{$row['Surname']} ({$row['Username']})\n";
	}
	echo "                  </select>\n";
	echo "               </td>\n";
	echo "            </tr>\n";
	echo "            <tr>\n";
	echo "               <td colspan=\"2\">\n";
	echo "                  Casenote:<br>\n";
	echo "                  <textarea rows=\"10\" cols=\"78\" name=\"note\">" .
		 "</textarea>\n";
	echo "               </td>\n";
	echo "            </tr>\n";
	echo "         </table>\n";
	echo "         <p align=\"center\">\n";
	echo "            <input type=\"submit\" name=\"action\" value=\"Save\">&nbsp;\n";
	echo "            <input type=\"submit\" name=\"action\" value=\"Cancel\">&nbsp;\n";
	echo "         </p>\n";
	echo "         <p align=\"center\">WARNING: Once you have saved a casenote, there is no way to change or delete it.</p>\n";
	echo "      </form>\n";
} else { // User isn't authorized to create casenotes
	/* Log unauthorized access attempt */
	log_event($LOG_LEVEL_ERROR, "teacher/casenote/new.php", $LOG_DENIED_ACCESS, 
			"Tried to create\n casenote for $student.");
	
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
}

include "footer.php";
?>