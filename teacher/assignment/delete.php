<?php
	/*****************************************************************
	 * teacher/assignment/delete.php  (c) 2004-2007 Jonathan Dieter
	 *
	 * Delete assignment from database
	 *****************************************************************/

	 /* Get variables */
	$assignmentindex = safe(dbfuncInt2String($_GET['key']));
	$nextLink        = dbfuncInt2String($_GET['next']);
	
	if($_POST['action'] == "Yes, delete assignment") {
		$title         = "LESSON - Deleting Assignment";
		$noJS          = true;
		$noHeaderLinks = true;
		
		include "core/settermandyear.php";
		include "header.php";

		/* Check whether user is authorized to change scores */
		$res =& $db->query("SELECT subjectteacher.Username FROM subjectteacher, assignment " .
						   "WHERE subjectteacher.SubjectIndex = assignment.SubjectIndex " .
						   "AND   assignment.AssignmentIndex  = $assignmentindex " .
						   "AND   subjectteacher.Username     = '$username'");
		if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
	
		if($res->numRows() > 0 or $is_admin) {
			$asr =&  $db->query("SELECT subject.Name, assignment.Title, subject.SubjectIndex " .
								"       FROM assignment, subject " .
								"WHERE assignment.AssignmentIndex  = $assignmentindex " .
								"AND   subject.SubjectIndex        = assignment.SubjectIndex");
			if(DB::isError($asr)) die($asr->getDebugInfo());           // Check for errors in query
			$aRow =& $asr->fetchRow(DB_FETCHMODE_ASSOC);

			$res =&  $db->query("DELETE FROM mark " .                  // Delete all marks for assignment
								"WHERE AssignmentIndex  = $assignmentindex ");
			if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
			
			$res =&  $db->query("DELETE FROM assignment " .            // Delete assignment
								"WHERE AssignmentIndex  = $assignmentindex ");
			if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
			
			echo "      <p align='center'>Assignment successfully deleted.</p>\n";
			echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n";
			
			update_subject($aRow['SubjectIndex']);
			
			log_event($LOG_LEVEL_TEACHER, "teacher/deleteassignment", $LOG_TEACHER,
				"Deleted assignment ({$aRow['Title']}) in {$aRow['Name']}.");
	
		} else {
			/* Get subject name and log unauthorized access attempt */
			$asr =&  $db->query("SELECT subject.Name, assignment.Title FROM assignment, subject " .
								"WHERE assignment.AssignmentIndex  = $assignmentindex " .
								"AND   subject.SubjectIndex        = assignment.SubjectIndex");
			if(DB::isError($asr)) die($asr->getDebugInfo());           // Check for errors in query
			if($aRow =& $asr->fetchRow(DB_FETCHMODE_ASSOC)) {
				log_event($LOG_LEVEL_ERROR, "teacher/assignment/delete.php", $LOG_DENIED_ACCESS,
							"Tried to remove assignment ({$aRow['Title']} in {$aRow['Name']}.");
				echo "      <p>You do not have the authority to remove this assignment.  " .
							"<a href='$nextLink'>Click here to continue</a>.</p>\n";
			} else {
				log_event($LOG_LEVEL_EVERYTHING, "teacher/assignment/delete.php", $LOG_ERROR,
							"Tried to remove non-existent assignment.");
				echo "      <p>This assignment has already been deleted.  " .
							"<a href='$nextLink'>Click here to continue</a>.</p>\n";
			}
		}
	} else {
		$title         = "LESSON - Cancelling";
		$noJS          = true;
		$noHeaderLinks = true;
		$extraMeta     = "      <meta http-equiv='REFRESH' content='0;url=$nextLink'>\n";
		
		include "header.php";
		
		echo "      <p align='center'>Cancelling and redirecting you to <a href='$nextLink'>$nextLink</a>." . 
					"</p>\n";
	}
	
	include "footer.php";
?>