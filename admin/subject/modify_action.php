<?php
	/*****************************************************************
	 * admin/subject/modify_action.php  (c) 2005-2008 Jonathan Dieter
	 *
	 * Run query to change a subject into the database.
	 *****************************************************************/

	/* Get variables */
	$error        = false;        // Boolean to store any errors
	$subjectindex = dbfuncInt2String($_GET['key']);
	$subject      = dbfuncInt2String($_GET['keyname']);
	
	include "core/settermandyear.php";
	
	 /* Check whether user is authorized to change subject */
	if($is_admin) {
		$_POST['name']        = htmlspecialchars($_POST['name']);
		$_POST['subjecttype'] = intval($_POST['subjecttype']);

		if(!is_null($_POST['conduct_type'])) {
			$_POST['conduct_type'] = intval($_POST['conduct_type']);
			if($_POST['conduct_type'] >= $CONDUCT_TYPE_MAX) $_POST['conduct_type'] = $CONDUCT_TYPE_NONE;
		} else {
			$_POST['conduct_type'] = $CONDUCT_TYPE_NONE;
		}
		if(!is_null($_POST['effort_type'])) {
			$_POST['effort_type'] = intval($_POST['effort_type']);
			if($_POST['effort_type'] >= $EFFORT_TYPE_MAX) $_POST['effort_type'] = $EFFORT_TYPE_NONE;
		} else {
			$_POST['effort_type'] = $EFFORT_TYPE_NONE;
		}
		if(!is_null($_POST['average_type'])) {
			$_POST['average_type'] = intval($_POST['average_type']);
			if($_POST['average_type'] >= $AVG_TYPE_MAX) $_POST['average_type'] = $AVG_TYPE_NONE;
		} else {
			$_POST['average_type'] = $AVG_TYPE_NONE;
		}
		if($_POST['categories'] == "on" or $_POST['categories'] == 1) {
			$_POST['categories'] = 1;
		} else {
			$_POST['categories'] = 0;
		}
		if(!is_null($_POST['period'])) {
			$_POST['period'] = intval($_POST['period']);
		} else {
			$_POST['period'] = "NULL";
		}
		if(!is_null($_POST['class']) and $_POST['class'] != "NULL") {
			$_POST['class'] = intval($_POST['class']);
		} else {
			$_POST['class'] = "NULL";
		}
		if(!is_null($_POST['grade']) and $_POST['grade'] != "NULL") {
			$_POST['grade'] = intval($_POST['grade']);
		} else {
			$_POST['grade'] = "NULL";
		}

		if(!isset($_POST['period']) or is_null($_POST['period']) or $_POST['period'] == 0) {
			$_POST['period'] = "NULL";
		}
		/* Change subject */
		$query =	"UPDATE subject SET " .
					"  Name='{$_POST['name']}', " .
					"  Period={$_POST['period']}, " .
					"  SubjectTypeIndex={$_POST['subjecttype']}, " .
					"  ClassIndex={$_POST['class']}, " .
					"  Grade={$_POST['grade']}, " .
					"  ConductType={$_POST['conduct_type']}, " .
					"  EffortType={$_POST['effort_type']}, " .
					"  AverageType={$_POST['average_type']}, " .
					"  TeacherCanChangeCategories={$_POST['categories']} " .
					"WHERE SubjectIndex = $subjectindex";
		$aRes =& $db->query($query);
		if(DB::isError($aRes)) die($aRes->getDebugInfo());           // Check for errors in query
		$_GET['keyname'] = dbfuncString2Int($_POST['name']);
		log_event($LOG_LEVEL_ADMIN, "admin/subject/modify_action.php", $LOG_ADMIN,
				"Modified information about {$_POST['name']}.");
	} else {  // User isn't authorized to view or change scores.
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "admin/subject/modify_action.php", $LOG_DENIED_ACCESS,
				"Attempted to change information about $subject.");
		$errorlist .= "      <p>You do not have permission to change subject information.</p>\n";
		$error     =  true;
	}
?>
