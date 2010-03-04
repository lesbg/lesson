<?php
	/*****************************************************************
	 * teacher/assignment/modify_action.php  (c) 2004-2007 Jonathan Dieter
	 *
	 * Run query to change grades
	 *****************************************************************/

	/* Get variables */
	$assignmentindex = safe(dbfuncInt2String($_GET['key']));
	$error           = false;    // Boolean to store any errors
	
	/* Check whether user is authorized to change scores */
	$res =& $db->query("SELECT subjectteacher.Username FROM subjectteacher, assignment " .
					   "WHERE subjectteacher.SubjectIndex = assignment.SubjectIndex " .
					   "AND   assignment.AssignmentIndex = $assignmentindex " .
					   "AND   subjectteacher.Username     = '$username' ");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	include "core/settermandyear.php";

	/* Check whether user is authorized to change scores */
	if($res->numRows() > 0 or $is_admin) {
		$query =	"SELECT subject.SubjectIndex, subject.AverageType, subject.AverageTypeIndex " .
					"       FROM subject INNER JOIN assignment USING (SubjectIndex) " .
					"WHERE assignment.AssignmentIndex = $assignmentindex";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
		$row =& $res->fetchRow(DB_FETCHMODE_ASSOC);

		$subjectindex     = $row['SubjectIndex'];
		$average_type      = $row['AverageType'];
		$average_type_index = $row['AverageTypeIndex'];

		if($_POST['action'] == "Move this assignment to next term") {
			$next_subjectindex = intval($_POST['next_subject']);
			$query =	"UPDATE assignment SET SubjectIndex=$next_subjectindex " .
						"WHERE AssignmentIndex = $assignmentindex";
			$res =&  $db->query($query);
			if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

			update_subject($subjectindex);

			$subjectindex = $next_subjectindex;
		}
		
		if(($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) and (!isset($_POST['category']) or $_POST['category'] == "NULL")) {
			$res =&  $db->query("SELECT categorylist.CategoryListIndex FROM assignment, category, " .
								"       categorylist " .
								"WHERE assignment.AssignmentIndex = $assignmentindex " .
								"AND   categorylist.SubjectIndex = assignment.SubjectIndex " .
								"AND   category.CategoryIndex = categorylist.CategoryIndex " .
								"ORDER BY category.CategoryName");
			if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
			if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$_POST['category'] = $row['CategoryListIndex'];
			} else {
				$_POST['category'] = "NULL";
			}
		}
		if($_POST['uploadable'] == 1) {
			$upload_name = "$upload_name ($assignmentindex)";
			$res =& $db->query("SELECT UploadName, Uploadable FROM assignment WHERE AssignmentIndex = $assignmentindex");
			if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
			if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				if($row['Uploadable'] == 0) {
					dbfuncMkDir($assignmentindex, $upload_name);
				} else {
					if($row['UploadName'] != $upload_name) {
						dbfuncMoveDir($assignmentindex, $row['UploadName'], $upload_name);
					}
				}
			}
			$upload_name = "'$upload_name'";
		}

		/* Set assignment information */
		$query =		"UPDATE assignment SET Title = '$title', Description = $descr, " .
						"       DescriptionData = $descr_data, DescriptionFileType = $descr_file_type, " .
						"       Date = {$_POST['date']}, DueDate = {$_POST['duedate']}, " .
						"       UploadName = {$upload_name}, Uploadable = {$_POST['uploadable']}, ";
		if(($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) and $agenda == "0") {
			$query .=	"       CurveType = {$_POST['curve_type']}, TopMark = {$_POST['top_mark']}, " .
						"       BottomMark = {$_POST['bottom_mark']}, Weight = {$_POST['weight']}, " .
						"       CategoryListIndex = {$_POST['category']}, Max = {$_POST['max']}, ";
		}
		$query .=		"       Hidden = {$_POST['hidden']}, Agenda = $agenda " .
						"WHERE AssignmentIndex = $assignmentindex";
		$aRes =& $db->query($query);
		if(DB::isError($aRes)) die($aRes->getMessage());           // Check for errors in query
		
		if($_POST['action'] == "Convert to agenda item") {
			$query =	"UPDATE assignment SET Agenda=1 " .
						"WHERE AssignmentIndex = $assignmentindex";
			$nres =&  $db->query($query);
			if(DB::isError($nres)) die($nres->getDebugInfo());         // Check for errors in query
		}

		if($_POST['action'] == "Convert to assignment" and $average_type != $AVG_TYPE_NONE) {
			$query =	"UPDATE assignment SET Agenda=0 " .
						"WHERE AssignmentIndex = $assignmentindex";
			$nres =&  $db->query($query);
			if(DB::isError($nres)) die($nres->getDebugInfo());         // Check for errors in query
		}
		
		if($agenda == "0") {
			$res =& $db->query("SELECT subjectstudent.Username FROM " .        // Get list of students
							   "       subjectstudent LEFT OUTER JOIN mark ON (mark.AssignmentIndex = $assignmentindex " .
							   "       AND mark.Username = subjectstudent.Username), assignment " .
							   "WHERE assignment.AssignmentIndex = $assignmentindex " .
							   "AND   subjectstudent.SubjectIndex = assignment.SubjectIndex " .
							   "ORDER BY subjectstudent.Username");
			if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
			
			/* For each student, check whether there's already a mark, then either insert or update mark as needed */
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				if($average_type != $AVG_TYPE_NONE) {
					$score = $_POST["score_{$row['Username']}"];          // Get score for username from POST data
				} else {
					$score = "NULL";
				}
				
				$comment = $_POST["comment_{$row['Username']}"];        // Get comment for username from POST data
				
				if($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) {
					if(strtoupper($score) == 'A') {
						$score   = "$MARK_ABSENT";                   // Change "A" for absent to $MARK_ABSENT.
					} elseif(strtoupper($score) == 'E') {
						$score   = "$MARK_EXEMPT";
					} elseif(strtoupper($score) == 'L') {
						$score   = "$MARK_LATE";
					} elseif($score == '' or !isset($_POST["score_{$row['Username']}"])) { // If score is blanks, set to NULL
						$score   = "NULL";
					} else {
						if($score != "0") {
							$max = $_POST['max'];
							settype($max, "double");
							settype($score, "double");
							if($score < 0) {    // If score is less than 0, print error message and set to 0.
								echo "</p>\n      <p>Score for {$row['Username']} must be at least 0...setting to 0.</p>\n      <p>";
								$score = 0;
							}
							if($score > $max) { // If score is greater than $max, print error message, but don't do anything.
								echo "</p>\n      <p>Warning!  Score for {$row['Username']} is greater than $max.</p>\n      <p>";
							}
							if($score == 0) {   // If score started with a letter, print error message and set to 0.
								echo "</p>\n      <p>Score for {$row['Username']} must be a number or A (for absent)...clearing. " .
									"</p>\n      <p>";
								$score = "NULL";
							}
							settype($score, "string");
						}
					}
				} elseif($average_type == $AVG_TYPE_INDEX) {
					$inval = safe($_POST["score_{$row['Username']}"]);
					$inval = strtoupper($inval);
					$query = "SELECT NonmarkIndex FROM nonmark_index WHERE NonmarkTypeIndex=$average_type_index AND Input = '$inval'";
					$sRes =& $db->query($query);
					if(DB::isError($sRes)) die($sRes->getDebugInfo());      // Check for errors in query
		
					if($sRow =& $sRes->fetchRow(DB_FETCHMODE_ASSOC)) {
						$score = $sRow['NonmarkIndex'];
					} else {
						if(isset($inval) and $inval != "") {
							echo "</p>\n      <p>Mark for {$row['Username']} is invalid...clearing. " .
								"</p>\n      <p>";
						}
						$score = "NULL";
					}
				} else {
					$score = "NULL";
				}
				if($comment == '' or !isset($_POST["comment_{$row['Username']}"]))  {   // If comment is blank, set to NULL
					$comment = "NULL";
				} else {
					$comment = safe(htmlize_comment($comment));
					$comment = "'$comment'";     // If comment is not blank, put quotes around it
				}
				
				$sRes =& $db->query("SELECT mark.MarkIndex FROM assignment, mark " .
									"WHERE assignment.AssignmentIndex = $assignmentindex " .
									"AND   mark.AssignmentIndex       = assignment.AssignmentIndex " .
									"AND   mark.Username              = '{$row['Username']}'");
				if(DB::isError($sRes)) die($sRes->getDebugInfo());      // Check for errors in query
	
				if($sRow =& $sRes->fetchRow(DB_FETCHMODE_ASSOC)) {
					$update =& $db->query("UPDATE mark SET Score = $score, Comment = $comment " .
										"WHERE mark.MarkIndex  = {$sRow['MarkIndex']} ");
					if(DB::isError($update)) {
						echo "</p>\n      <p>Update: " . $update->getMessage() . "</p>\n      <p>";
						$error = true;
					}
				} else {
					$update =& $db->query("INSERT INTO mark (MarkIndex, Username, AssignmentIndex, " .
										"Score, Comment) VALUES ('', '{$row['Username']}', " .
										"$assignmentindex, $score, $comment);"); 
					if(DB::isError($update)) {
						echo "</p>\n      <p>Insert: " . $update->getDebugInfo() . "</p>\n      <p>"; // Print any errors
						$error = true;
					}
				}
			}
		}
		if($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) {
			update_marks($assignmentindex);
		}

		$asr =&  $db->query("SELECT subject.Name FROM assignment, subject " .
							"WHERE assignment.AssignmentIndex  = $assignmentindex " .
							"AND   subject.SubjectIndex        = assignment.SubjectIndex");
		if(DB::isError($asr)) die($asr->getDebugInfo());           // Check for errors in query
		$aRow =& $asr->fetchRow(DB_FETCHMODE_ASSOC);
		log_event($LOG_LEVEL_TEACHER, "teacher/assignment/modify_action.php", $LOG_TEACHER,
				"Modified assignment ($title) for {$aRow['Name']}.");
	} else {  // User isn't authorized to modify marks.
		/* Get subject name and log unauthorized access attempt */
		$asr =&  $db->query("SELECT subject.Name FROM assignment, subject " .
							"WHERE assignment.AssignmentIndex  = $assignmentindex " .
							"AND   subject.SubjectIndex        = assignment.SubjectIndex");
		if(DB::isError($asr)) die($asr->getDebugInfo());           // Check for errors in query
		$aRow =& $asr->fetchRow(DB_FETCHMODE_ASSOC);
		log_event($LOG_LEVEL_ERROR, "teacher/assignment/modify_action.php", $LOG_DENIED_ACCESS,
					"Tried to modify marks for {$aRow['Name']}.");
		
		echo "</p>\n      <p>You do not have permission to change these grades.</p>\n      <p>";
		$error = true;
	}
?>