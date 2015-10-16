<?php
/**
 * ***************************************************************
 * admin/subject/modify_list_action.php (c) 2005 Jonathan Dieter
 *
 * Add or remove students from a subject, as well as changing
 * subject information
 * ***************************************************************
 */

/* Get variables */
$nextLink = dbfuncInt2String($_GET['next']); // Link to next page
$subjectindex = dbfuncInt2String($_GET['key']); // Index of subject to add and remove students from
$subject = dbfuncInt2String($_GET['keyname']);

if (! isset($_POST["action"]))
	$_POST["action"] = "";
if (! isset($_POST["actiont"]))
	$_POST["actiont"] = "";
	/* Check whether user is authorized to change subject */
if (dbfuncGetPermission($permissions, $PERM_ADMIN)) {
	/* Check which button was pressed */
	
	// If > was pressed, remove students from subject
	if ($_POST["action"] == ">" and isset($_POST['removefromsubject'])) {
		foreach ( $_POST['removefromsubject'] as $remUserName ) {
			if (substr($remUserName, 0, 1) == "!") {
				$remUserName = substr($remUserName, 1);
				$forceRemove = true;
			}
			$res = &  $db->query(
							"SELECT user.FirstName, user.Surname, mark.Username FROM mark, assignment, user " .
							 "WHERE mark.Username = '$remUserName' " .
							 "AND   mark.AssignmentIndex = assignment.AssignmentIndex " .
							 "AND   user.Username = mark.Username " .
							 "AND   assignment.SubjectIndex = $subjectindex " .
							 "AND   mark.Score > 0");
			if (DB::isError($res))
				die($res->getDebugInfo()); // Check for errors in query
			
			if ($res->numRows() > 0 && ! $forceRemove) { // If there's at least one mark with a score or comment,
				$row = & $res->fetchRow(DB_FETCHMODE_ASSOC); // and we're not force the removal, pop up an error
				$errorlist[$remUserName] = "{$row['FirstName']} {$row['Surname']} ($remUserName)"; // message
			} else { // Remove all null score and comment marks, then remove user from subject
				$nRes = & $db->query(
						"SELECT AssignmentIndex FROM assignment WHERE SubjectIndex = $subjectindex");
				if (DB::isError($nRes))
					die($nRes->getDebugInfo()); // Check for errors in query
				while ( $nRow = & $nRes->fetchRow(DB_FETCHMODE_ASSOC) ) { // This is a work-around for early (<4.0) versions
					$res = &  $db->query(
						"DELETE FROM mark " . // of MySQL
						 "WHERE mark.Username = '$remUserName' " .
						 "AND   mark.AssignmentIndex = {$nRow['AssignmentIndex']}");
					if (DB::isError($res))
						die($res->getDebugInfo()); // Check for errors in query
				}
				$res = &  $db->query(
								"DELETE FROM subjectstudent " .
								 "WHERE Username     = \"$remUserName\" " .
								 "AND   SubjectIndex = $subjectindex");
				if (DB::isError($res))
					die($res->getDebugInfo()); // Check for errors in query
				log_event($LOG_LEVEL_ADMIN, "admin/subject/modify_list_action.php", 
			$LOG_ADMIN, "Removed $remUserName from subject $subject.");
			}
		}
		include "admin/subject/modify_list.php";
	} elseif ($_POST["action"] == ">>") { // If < was pressed, add students to
		$ares = & $db->query(
					"SELECT user.FirstName, user.Surname, user.Username FROM " .
							 "       user, subjectstudent " .
							 "WHERE subjectstudent.Username = user.Username " .
							 "AND   subjectstudent.SubjectIndex = $subjectindex " .
							 "ORDER BY user.Username");
		if (DB::isError($ares))
			die($ares->getDebugInfo()); // Check for errors in query
		while ( $arow = & $ares->fetchRow(DB_FETCHMODE_ASSOC) ) {
			$remUserName = $arow['Username'];
			$res = &  $db->query(
							"SELECT user.FirstName, user.Surname, mark.Username FROM mark, assignment, user " .
							 "WHERE mark.Username = '$remUserName' " .
							 "AND   mark.AssignmentIndex = assignment.AssignmentIndex " .
							 "AND   user.Username = mark.Username " .
							 "AND   assignment.SubjectIndex = $subjectindex " .
							 "AND   mark.Score > 0");
			if (DB::isError($res))
				die($res->getDebugInfo()); // Check for errors in query
			
			if ($res->numRows() > 0) { // If there's at least one mark with a score or comment,
				$row = & $res->fetchRow(DB_FETCHMODE_ASSOC); // and we're not force the removal, pop up an error
				$errorlist[$remUserName] = "{$row['FirstName']} {$row['Surname']} ($remUserName)"; // message
			} else { // Remove all null score and comment marks, then remove user from subject
				$nRes = & $db->query(
						"SELECT AssignmentIndex FROM assignment WHERE SubjectIndex = $subjectindex");
				if (DB::isError($nRes))
					die($nRes->getDebugInfo()); // Check for errors in query
				while ( $nRow = & $nRes->fetchRow(DB_FETCHMODE_ASSOC) ) { // This is a work-around for early (<4.0) versions
					$res = &  $db->query(
						"DELETE FROM mark " . // of MySQL
						 "WHERE mark.Username = '$remUserName' " .
						 "AND   mark.AssignmentIndex = {$nRow['AssignmentIndex']}");
					if (DB::isError($res))
						die($res->getDebugInfo()); // Check for errors in query
				}
				$res = &  $db->query(
								"DELETE FROM subjectstudent " .
								 "WHERE Username     = \"$remUserName\" " .
								 "AND   SubjectIndex = $subjectindex");
				if (DB::isError($res))
					die($res->getDebugInfo()); // Check for errors in query
				log_event($LOG_LEVEL_ADMIN, "admin/subject/modify_list_action.php", 
			$LOG_ADMIN, "Removed $remUserName from subject $subject.");
			}
		}
		include "admin/subject/modify_list.php";
	} elseif ($_POST["action"] == "<") { // If < was pressed, add students to
		foreach ( $_POST['addtosubject'] as $addUserName ) { // subject
			$res = &  $db->query(
					"SELECT Username FROM subjectstudent " .
							 "WHERE Username     = \"$addUserName\" " .
							 "AND   SubjectIndex = $subjectindex");
			if (DB::isError($res))
				die($res->getDebugInfo()); // Check for errors in query
			if ($res->numRows() == 0) {
				$res = & $db->query(
								"INSERT INTO subjectstudent (Username, SubjectIndex) VALUES " .
						 "                           (\"$addUserName\", $subjectindex)");
				if (DB::isError($res))
					die($res->getDebugInfo()); // Check for errors in query
				log_event($LOG_LEVEL_ADMIN, "admin/subject/modify_list_action.php", 
			$LOG_ADMIN, "Added $addUserName to subject $subject.");
			}
		}
		include "admin/subject/modify_list.php";
	} elseif ($_POST["action"] == "<<") { // If << was pressed, add all students in
		if (isset($_POST['show'])) { // class to subject
			if ($_POST['show'] == "new")
				$showNew = "checked";
			elseif ($_POST['show'] == "old")
				$showOld = "checked";
			elseif ($_POST['show'] == "spec")
				$showSpec = "checked";
			elseif ($_POST['show'] == "reg")
				$showReg = "checked";
			else
				$showAll = "checked";
		} else {
			$showAll = "checked";
		}
		/* Get list of students who are in the active class */
		if ($_POST['class'] != "") {
			$query = "SELECT user.FirstName, user.Surname, user.Username FROM " .
				 "       user, classterm, classlist LEFT JOIN subjectstudent ON classlist.Username=subjectstudent.Username AND " .
				 "       subjectstudent.SubjectIndex = $subjectindex " .
				 "WHERE  user.Username = classlist.Username " .
				 "AND    subjectstudent.Username IS NULL " .
				 "AND    classlist.ClassTermIndex = classterm.ClassTermIndex " .
				 "AND    classterm.TermIndex = $termindex " .
				 "AND    classterm.ClassIndex = {$_POST['class']} ";
			if ($showNew == "checked") // Add appropriate filter according to radio button that has been selected
				$query .= "AND user.User1 = 1 ";
			elseif ($showOld == "checked")
				$query .= "AND (user.User1 IS NULL OR user.User1 = 0) ";
			elseif ($showSpec == "checked")
				$query .= "AND user.User2 = 1 ";
			elseif ($showReg == "checked")
				$query .= "AND (user.User2 IS NULL OR user.User2 = 0) ";
			$query .= "ORDER BY user.Username";
			$nres = &  $db->query($query);
			if (DB::isError($nres))
				die($nres->getDebugInfo()); // Check for errors in query
			
			while ( $nrow = & $nres->fetchRow(DB_FETCHMODE_ASSOC) ) {
				$addUserName = $nrow['Username'];
				$res = &  $db->query(
								"SELECT Username FROM subjectstudent " .
								 "WHERE Username     = \"$addUserName\" " .
								 "AND   SubjectIndex = $subjectindex");
				if (DB::isError($res))
					die($res->getDebugInfo()); // Check for errors in query
				if ($res->numRows() == 0) {
					$res = & $db->query(
									"INSERT INTO subjectstudent (Username, SubjectIndex) VALUES " .
							 "                           (\"$addUserName\", $subjectindex)");
					if (DB::isError($res))
						die($res->getDebugInfo()); // Check for errors in query
					log_event($LOG_LEVEL_ADMIN, "admin/subject/modify_list_action.php", 
			$LOG_ADMIN, "Added $addUserName to subject $subject.");
				}
			}
		}
		include "admin/subject/modify_list.php";
	} elseif ($_POST["actiont"] == ">") { // If > was pressed, remove students from
		foreach ( $_POST['removefromteacherlist'] as $remUserName ) { // subject
			$res = &  $db->query(
					"DELETE FROM subjectteacher " .
							 "WHERE Username     = \"$remUserName\" " .
							 "AND   SubjectIndex = $subjectindex");
			if (DB::isError($res))
				die($res->getDebugInfo()); // Check for errors in query
			log_event($LOG_LEVEL_ADMIN, "admin/subject/modify_list_action.php", 
			$LOG_ADMIN, "Removed $remUserName from teaching subject $subject.");
		}
		include "admin/subject/modify_list.php";
	} elseif ($_POST["actiont"] == "<") {
		foreach ( $_POST['addtoteacherlist'] as $addUserName ) { // class
			$res = &  $db->query(
					"SELECT Username FROM subjectteacher " .
							 "WHERE Username     = \"$addUserName\" " .
							 "AND   SubjectIndex = $subjectindex");
			if (DB::isError($res))
				die($res->getDebugInfo()); // Check for errors in query
			if ($res->numRows() == 0) {
				$res = & $db->query(
								"INSERT INTO subjectteacher (Username, SubjectIndex) VALUES " .
						 "                           (\"$addUserName\", $subjectindex)");
				if (DB::isError($res))
					die($res->getDebugInfo()); // Check for errors in query
			}
			log_event($LOG_LEVEL_ADMIN, "admin/subject/modify_list_action.php", 
					$LOG_ADMIN, 
					"Set $addUserName as a teacher for subject $subject.");
		}
		include "admin/subject/modify_list.php";
	} elseif ($_POST["action"] == "Done") {
		$extraMeta = "      <meta http-equiv=\"REFRESH\" content=\"0;url=$nextLink\">\n";
		$noJS = true;
		$noHeaderLinks = true;
		$title = "LESSON - Redirecting...";
		
		include "header.php";
		
		echo "      <p align=\"center\">Redirecting you to <a href=\"$nextLink\">$nextLink</a></p>\n";
		
		include "footer.php";
	} else {
		include "admin/subject/modify_list.php";
	}
} else {
	/* Log unauthorized access attempt */
	log_event($LOG_LEVEL_ERROR, "admin/subject/modify_list_action.php", 
			$LOG_DENIED_ACCESS, "Attempted to modify subject $subject.");
	
	$noJS = true;
	$noHeaderLinks = true;
	$title = "LESSON - Unauthorized access!";
	
	include "header.php";
	
	echo "      <p align=\"center\">You do not have permission to access this page. <a href=" .
		 "\"$nextLink\">Click here to continue.</a></p>\n";
	
	include "footer.php";
}

?>