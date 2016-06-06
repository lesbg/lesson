<?php
/**
 * ***************************************************************
 * teacher/casenote/new_action.php (c) 2006 Jonathan Dieter
 *
 * Insert new casenote into database
 * ***************************************************************
 */

/* Get variables */
$studentusername = safe(dbfuncInt2String($_GET['key']));
$student = dbfuncInt2String($_GET['keyname']);
$link = "index.php?location=" . dbfuncString2Int("teacher/casenote/list.php") .
		 "&amp;key=" . $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] .
		 "&amp;keyname2=" . $_GET['keyname2'];

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

if ($is_principal or $is_hod or $is_counselor or $is_classteacher or
	 $is_supportteacher or $is_teacher) {
	/* Check which button was pressed */
	if ($_POST["action"] == "Save" or $_POST["action"] == "Update") { // If update or save were pressed, print
		$title = "LESSON - Saving casenote...";
		$noHeaderLinks = true;
		$noJS = true;
		
		include "header.php"; // Print header
		
		echo "      <p align=\"center\">Saving casenote...";
		
		/* Check whether or not a casenote was included and cancel if it wasn't */
		if ($_POST['note'] == "") {
			echo "failed</p>\n";
			echo "      <p align=\"center\">There is not point in saving an empty casenote</p>\n";
		} else {
			$note = str_replace("\n", "<br>\n", $_POST['note']);
			$note = "<p>$note</p>";
			$note = $db->escapeSimple($note);
			$level = $_POST['level'];
			
			// Make sure level is consistent with teacher's position - disabled at Steve's request
			/*
			 * if($level > 5) $level = 5;
			 * if($level > 4 && !$is_principal && !$is_hod && !$is_counselor) $level = 4;
			 * if($level > 2 && !$is_principal && !$is_hod && !$is_counselor && !$is_classteacher) $level = 2;
			 */
			
			if ($_POST["action"] == "Save") {
				/* Insert into casenote table */
				$query = "INSERT INTO casenote (WorkerUsername, StudentUsername, " .
					 "                      Note, Level, Date) " .
					 "       VALUES " .
					 "       ('$username', '$studentusername', '$note', $level, NOW())";
				$res = & $db->query($query);
				if (DB::isError($res))
					die($res->getDebugInfo()); // Check for errors in query
				$cn_index = - 1;
				$query = "SELECT LAST_INSERT_ID() AS CaseNoteIndex";
				$res = & $db->query($query);
				if (DB::isError($res))
					die($res->getDebugInfo()); // Check for errors in query
				if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC) and
		 $row['CaseNoteIndex'] != 0) {
					$cn_index = $row['CaseNoteIndex'];
				}
				if ($level == 3) {
					foreach ( $_POST['counselor_list'] as $counselor ) {
						$counselor = $db->escapeSimple($counselor);
						/* Set counselor as someone who can read casenote */
						$query = "INSERT INTO casenotelist (WorkerUsername, " .
								 "                          CaseNoteIndex) " .
								 "       VALUES " .
								 "       ('$counselor', $cn_index)";
						$nrs = & $db->query($query);
						if (DB::isError($nrs))
							die($nrs->getDebugInfo());
					}
				}
				if ($level > 0) {
					$new_list = array();
					
					/* Build list of principals */
					$query = "SELECT Username FROM principal " .
							 "WHERE Level = 1";
					$res = &  $db->query($query);
					if (DB::isError($res))
						die($res->getDebugInfo());
					while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
						$new_list[] = $row['Username'];
					}
					
					if ($level < 5) {
						/* Build list of relevant head of departments */
						$query = "SELECT user.Username " .
							 "       FROM hod, class, classterm, classlist, user " .
							 "WHERE hod.DepartmentIndex = class.DepartmentIndex " .
							 "AND   class.YearIndex = $currentyear " .
							 "AND   class.ClassIndex = classterm.ClassIndex " .
							 "AND   classterm.TermIndex = $currentterm " .
							 "AND   classterm.ClassTermIndex = classlist.ClassTermIndex " .
							 "AND   classlist.Username = '$studentusername' " .
							 "AND   hod.Username = user.Username";
						$res = &  $db->query($query);
						if (DB::isError($res))
							die($res->getDebugInfo()); // Check for errors in query
						if ($res->numRows() > 0) {
							while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
								$new_list[] = $row['Username'];
							}
						}
					}
					
					/* Specified Counselors */
					if ($level == 3) {
						$query = "SELECT WorkerUsername FROM casenotelist " .
							 "WHERE  CaseNoteIndex = $cn_index";
						$res = &  $db->query($query);
						if (DB::isError($res))
							die($res->getDebugInfo());
						if ($res->numRows() > 0) {
							while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
								$new_list[] = $row['WorkerUsername'];
							}
						}
					}
					
					/* Applicable Counselors */
					if ($level <= 3) {
						$query = "SELECT WorkerUsername FROM casenotewatch " .
							 "WHERE  StudentUsername = \"$studentusername\" ";
						$res = &  $db->query($query);
						if (DB::isError($res))
							die($res->getDebugInfo());
						if ($res->numRows() > 0) {
							while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
								$new_list[] = $row['WorkerUsername'];
							}
						}
					}
					
					/* Class teacher */
					if ($level < 3) {
						/* Build list of this student's class teacher for this term */
						$query = "SELECT class.ClassTeacherUsername " .
							 "       FROM class, classterm, classlist, user " .
							 "WHERE class.ClassTeacherUsername = user.Username " .
							 "AND   class.YearIndex = $currentyear " .
							 "AND   class.ClassIndex = classterm.ClassIndex " .
							 "AND   classterm.TermIndex = $currentterm " .
							 "AND   classterm.ClassTermIndex = classlist.ClassTermIndex " .
							 "AND   classlist.Username = '$studentusername' ";
						$res = &  $db->query($query);
						if (DB::isError($res))
							die($res->getDebugInfo());
						if ($res->numRows() > 0) {
							while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
								$new_list[] = $row['ClassTeacherUsername'];
							}
						}
					}
					if ($level < 2) {
						$query = "(SELECT subjectteacher.Username FROM subject, " .
							 "        subjectteacher, subjectstudent " .
							 " WHERE  subjectteacher.SubjectIndex = " .
							 "        subjectstudent.SubjectIndex " .
							 " AND    subjectstudent.Username = '$studentusername' " .
							 " AND    subject.SubjectIndex = subjectteacher.SubjectIndex " .
							 " AND    subject.YearIndex = $currentyear " .
							 " AND    subject.TermIndex = $currentterm) " .
							 "UNION " .
							 "(SELECT user.Username FROM user, support, groups, groupgenmem " .
							 " WHERE  support.StudentUsername = '$studentusername' " .
							 " AND    support.WorkerUsername  = user.Username " .
							 " AND    groupgenmem.Username    = '$username' " .
							 " AND    groups.GroupID          = groupgenmem.GroupID " .
							 " AND    groups.GroupTypeID      = 'supportteacher' " .
							 " AND    groups.YearIndex        = $currentyear) ";
						$res = &  $db->query($query);
						if (DB::isError($res))
							die($res->getDebugInfo());
						if ($res->numRows() > 0) {
							while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
								$new_list[] = $row['Username'];
							}
						}
					}
					$new_list = array_unique($new_list);
					foreach ( $new_list as $wl_username ) {
						if ($wl_username != $username and $wl_username != "" and
							 $wl_username != NULL) {
							$query = "INSERT INTO casenotenew (CaseNoteIndex, " .
							 "            WorkerUsername) " .
							 "       VALUES ($cn_index, '$wl_username')";
						$nrs = &  $db->query($query);
						if (DB::isError($nrs))
							die($nrs->getDebugInfo());
					}
				}
			}
			echo " done</p>\n";
			log_event($LOG_LEVEL_TEACHER, "teacher/casenote/new_action.php", 
					$LOG_TEACHER, "Created new casenote for $student.");
		} else {
		}
	}
	
	echo "      <p align=\"center\"><a href=\"$link\">Continue</a></p>\n"; // Link to next page
	
	include "footer.php";
}  /*
   * elseif($_POST["action"] == 'Delete') { // If delete was pressed, confirm deletion
   * include "teacher/casenote/confirmdelete";
   * }
   */
else {
	$extraMeta = "      <meta http-equiv=\"REFRESH\" content=\"0;url=$link\">\n";
	$noJS = true;
	$noHeaderLinks = true;
	$title = "LESSON - Cancelling...";
	
	include "header.php";
	
	echo "      <p align=\"center\">Cancelling and redirecting you to <a href=\"$link\">$link</a>." .
		 "</p>\n";
	
	include "footer.php";
}
} else { // User isn't authorized to create casenotes
/* Log unauthorized access attempt */
log_event($LOG_LEVEL_ERROR, "teacher/casenote/new_action.php", 
		$LOG_DENIED_ACCESS, "Tried to create new casenote for $student.");
$title = "LESSON - Unauthorized access";
$noHeaderLinks = true;
$noJS = true;

include "header.php"; // Print header

echo "      <p>You do not have permission to access this page</p>\n";
echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
include "footer.php";
}
?>