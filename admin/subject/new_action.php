<?php
	/*****************************************************************
	 * admin/subject/new_action.php  (c) 2005 Jonathan Dieter
	 *
	 * Run query to insert a new subject into the database.
	 *****************************************************************/

	/* Get variables */
	$error = false;        // Boolean to store any errors
	$showalldeps = true;                                     //  edit subjects
	include "core/settermandyear.php";

	 /* Check whether user is authorized to change scores */
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
			if($_POST['average_type'] >= $EFFORT_TYPE_MAX) $_POST['average_type'] = $AVG_TYPE_NONE;
		} else {
			$_POST['average_type'] = $AVG_TYPE_NONE;
		}
		if($_POST['categories'] == "on" or $_POST['categories'] == 1) {
			$_POST['categories'] = 1;
		} else {
			$_POST['categories'] = 0;
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
		if(!is_null($_POST['period'])) {
			$_POST['period'] = intval($_POST['period']);
		} else {
			$_POST['period'] = "NULL";
		}

		/* Check whether a subject already exists with the same name in the same year and term */
		$query =    "SELECT SubjectIndex FROM subject " .
					"WHERE Name      = '{$_POST['name']}' " .
					"AND   YearIndex =  $yearindex " .
					"AND   TermIndex =  $termindex ";
		if(!isset($_POST['period']) or is_null($_POST['period']) or $_POST['period'] == 0) {
			$query .= "AND  Period   IS NULL";
			$_POST['period'] = "NULL";
		} else {
			$query .= "AND  Period   =  {$_POST['period']}";
		}
		
		$res  =& $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$errorlist .= "      <p>There is already a subject in the same year and term with that name.  " .
			                       "Press \"Back\" to fix the problem.</p>\n";
			$error     =  true;
		} else {
			/* Add new subject */
			$aRes =& $db->query("INSERT INTO subject (Name, YearIndex, TermIndex, Period, SubjectTypeIndex, " .
								"                     DepartmentIndex, ClassIndex, Grade, ConductType, " .
								"                     AverageType, EffortType, TeacherCanChangeCategories) " .
								"VALUES ('{$_POST['name']}', $yearindex, $termindex, {$_POST['period']}, " .
								"        {$_POST['subjecttype']}, $depindex, {$_POST['class']}, " .
								"        {$_POST['grade']}, {$_POST['conduct_type']}, " .
								"        {$_POST['average_type']}, {$_POST['effort_type']}, " .
								"        {$_POST['categories']})");
			if(DB::isError($aRes)) die($aRes->getDebugInfo());           // Check for errors in query
			$aRes =& $db->query("SELECT SubjectIndex, Name FROM subject " .
								"WHERE  SubjectIndex = LAST_INSERT_ID()");
			if(DB::isError($aRes)) die($aRes->getDebugInfo());           // Check for errors in query
			if($aRow =& $aRes->fetchRow(DB_FETCHMODE_ASSOC) and $aRow['SubjectIndex'] != 0) {
				$_GET['key']     = dbfuncString2Int($aRow['SubjectIndex']);
				$_GET['keyname'] = dbfuncString2Int($aRow['Name']);
			} else {
				$errorlist .= "      <p>There was an unknown error when attempting to save the new subject.  " .
			                           "Press \"Back\" to try again.</p>\n";
				$error     =  true;
			}
		}
	} else {  // User isn't authorized to view or change scores.
		$errorlist .= "      <p>You do not have permission to add a subject.</p>\n";
		$error     =  true;
	}
?>
