<?php
	/*****************************************************************
	 * admin/newquarter.php  (c) 2005-2008 Jonathan Dieter
	 *
	 * Move to the next quarter and generate appropriate classes.
	 *****************************************************************/

	$title           = "New Quarter";
	
	include "header.php";                                    // Show header
	
	$showalldeps = true;
	include "core/settermandyear.php";

	if(!$is_admin) {
		log_event($LOG_LEVEL_ERROR, "admin/newquarter.php", $LOG_DENIED_ACCESS,
					"Tried to create new quarter with subjects from current quarter.");
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
		include "footer.php";
		exit(0);
	}

	$ttyres =& $db->query("SELECT TermName, TermIndex FROM term " .  // Run query to get term
							"WHERE DepartmentIndex=$depindex " .
							"ORDER BY TermNumber");
	if(DB::isError($ttyres)) die($ttyres->getMessage());             // Check for errors in query
	while($ttyrow =& $ttyres->fetchRow(DB_FETCHMODE_ASSOC)) {        // If there is a year, print it
		if($ttyrow['TermIndex'] == $currentterm) {
			if($ttyrow =& $ttyres->fetchRow(DB_FETCHMODE_ASSOC)) {
				$newterm = $ttyrow['TermIndex'];
			} else {
				$newterm = NULL;
			}
			break;
		}
	}
	
	/* Check that there is at least one term left in the year */
	if(is_null($newterm)) {
		echo "      <p>There are no more terms this year.  Please use the <i>Create new year</i> link instead.</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to continue</a></p>\n";
		include "footer.php";
		exit(0);
	}

	$query =	"SELECT SubjectIndex, SubjectTypeIndex, Name FROM subject " .
				"WHERE  YearIndex = $currentyear " .
				"AND    TermIndex = $currentterm " .
				"ORDER BY Name";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		
	if($res->numRows() == 0) {
		echo "      <p>No subjects to add to new quarter.</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to continue</a></p>\n";
		include "footer.php";
		exit(0);
	}

	/* For each subject, create a new subject with all the information identical to current one
	except the term which will be  advanced by one */
	echo "      <p><b>Generating subjects for new quarter.</b></p>\n";
	while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		echo "      <p><i>{$row['Name']}</i>...";
		
		/* Check whether subject already exists */
		$chkRes =&   $db->query("SELECT SubjectIndex FROM subject " .
								"WHERE Name             = '{$row['Name']}' " .
								"AND   SubjectTypeIndex = {$row['SubjectTypeIndex']} " .
								"AND   YearIndex        = $currentyear " .
								"AND   TermIndex        = $newterm");
		if(DB::isError($chkRes)) die($chkRes->getDebugInfo());           // Check for errors in query
		
		if(!($chkRow =& $chkRes->fetchRow(DB_FETCHMODE_ASSOC))) {
			/* Create new subject */
			$query =	"INSERT INTO subject (Name, ShortName, SubjectTypeIndex, YearIndex, " .
						"                     TermIndex, ShowAverage, DepartmentIndex, " .
						"                     ClassIndex, Grade, RenameUploads, " .
						"                     CanModify, ShowInList, AverageType, " .
						"                     AverageTypeIndex, ConductType, ConductTypeIndex, " .
						"                     EffortType, EffortTypeIndex, " .
						"                     TeacherCanChangeCategories, CommentType) " .
						"SELECT Name, ShortName, SubjectTypeIndex, YearIndex, $newterm AS TermIndex, " .
						"       ShowAverage, DepartmentIndex, ClassIndex, Grade, " .
						"       RenameUploads, CanModify, ShowInList, AverageType, AverageTypeIndex, " .
						"       ConductType, ConductTypeIndex, EffortType, EffortTypeIndex, " .
						"       TeacherCanChangeCategories, CommentType FROM subject " .
						"WHERE  SubjectIndex = {$row['SubjectIndex']} ";
			$newRes =& $db->query($query);
			if(DB::isError($newRes)) die($newRes->getDebugInfo());           // Check for errors in query
			echo "..";
			
			/* Get key of newly created subject */
			$newRes =& $db->query("SELECT LAST_INSERT_ID() AS SubjectIndex");
			if(DB::isError($newRes)) die($newRes->getDebugInfo());           // Check for errors in query
			echo "..";
			
			if(!$newRow =& $newRes->fetchRow(DB_FETCHMODE_ASSOC) or $newRow['SubjectIndex'] == 0) {
				echo "<b>failed!</b></p>\n";
				continue;
			}

			/* Add students from old term's subject to new term's subject */
			$frmRes =&   $db->query("SELECT SubjectIndex, Username FROM subjectstudent " .
									"WHERE SubjectIndex = {$row['SubjectIndex']}");
			if(DB::isError($frmRes)) die($frmRes->getDebugInfo());       // Check for errors in query
			while($frmRow =& $frmRes->fetchRow(DB_FETCHMODE_ASSOC)) {
				$query =	"INSERT INTO subjectstudent (SubjectIndex, Username) " .
							"VALUES ({$newRow['SubjectIndex']}, '{$frmRow['Username']}')";
				$stdRes =&   $db->query($query);
				if(DB::isError($stdRes)) die($stdRes->getDebugInfo());   // Check for errors in query
				echo ".";
			}
			
			/* Add teacher(s) from old term's subject to new term's subject */
			$query =	"SELECT SubjectIndex, Username, ShowTeacher FROM subjectteacher " .
						"WHERE SubjectIndex = {$row['SubjectIndex']}";
			$frmRes =&   $db->query($query);
			if(DB::isError($frmRes)) die($frmRes->getDebugInfo());       // Check for errors in query
			while($frmRow =& $frmRes->fetchRow(DB_FETCHMODE_ASSOC)) {
				if(!isset($frmRow['ShowTeacher'])) $frmRow['ShowTeacher'] = "NULL";
				$query =	"INSERT INTO subjectteacher (SubjectIndex, Username, ShowTeacher) " .
							"VALUES ({$newRow['SubjectIndex']}, '{$frmRow['Username']}', " .
							"        {$frmRow['ShowTeacher']})";
				$stdRes =&   $db->query($query);
				if(DB::isError($stdRes)) die($stdRes->getDebugInfo());   // Check for errors in query
				echo ".";
			}

			/* Create timetable from old term's timetable */
			$query =	"SELECT ClassIndex, DayIndex, PeriodIndex, TTNextIndex FROM timetable " .
						"WHERE  SubjectIndex = {$row['SubjectIndex']} ";
			$frmRes =&   $db->query($query);
			if(DB::isError($frmRes)) die($frmRes->getDebugInfo());       // Check for errors in query
			while($frmRow =& $frmRes->fetchRow(DB_FETCHMODE_ASSOC)) {
				if(!isset($frmRow['ClassIndex']))  $frmRow['ClassIndex'] = "NULL";
				if(!isset($frmRow['TTNextIndex'])) $frmRow['TTNextIndex'] = "NULL";
				$query =	"INSERT INTO timetable (SubjectIndex, ClassIndex, DayIndex, " .
							"                       PeriodIndex, TTNextIndex) " .
							"VALUES ({$newRow['SubjectIndex']}, {$frmRow['ClassIndex']}, " .
							"        {$frmRow['DayIndex']}, {$frmRow['PeriodIndex']}, " .
							"        {$frmRow['TTNextIndex']})";
				$stdRes =&   $db->query($query);
				if(DB::isError($stdRes)) die($stdRes->getDebugInfo());   // Check for errors in query
				echo ".";
			}

			/* Create categories from old term's categories for subject */
			$query =	"SELECT CategoryIndex, Weight, TotalWeight FROM categorylist " .
						"WHERE  SubjectIndex = {$row['SubjectIndex']} ";
			$frmRes =&   $db->query($query);
			if(DB::isError($frmRes)) die($frmRes->getDebugInfo());       // Check for errors in query
			while($frmRow =& $frmRes->fetchRow(DB_FETCHMODE_ASSOC)) {
				$query =	"INSERT INTO categorylist (SubjectIndex, CategoryIndex, Weight, " .
							"                       TotalWeight) " .
							"VALUES ({$newRow['SubjectIndex']}, {$frmRow['CategoryIndex']}, " .
							"        {$frmRow['Weight']}, {$frmRow['TotalWeight']})";
				$stdRes =&   $db->query($query);
				if(DB::isError($stdRes)) die($stdRes->getDebugInfo());   // Check for errors in query
				echo ".";
			}
			echo "done.</p>\n";
		} else { // Subject already exists in new term
			echo "<b>already exists!</b></p>\n";
		}
	}
	$res =&  $db->query("SELECT DisciplineWeight, DisciplineTypeIndex " .
						"       FROM disciplineweight " .
						"WHERE  YearIndex = $currentyear " .
						"AND    TermIndex = $currentterm ");
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	
	while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$nres =& $db->query("SELECT DisciplineWeightIndex " .
							"       FROM disciplineweight " .
							"WHERE  YearIndex = $currentyear " .
							"AND    TermIndex = $newterm " .
							"AND    DisciplineTypeIndex = {$row['DisciplineTypeIndex']}");
		if(DB::isError($nres)) die($nres->getDebugInfo());           // Check for errors in query
		if($nres->numRows() == 0) {
			$query = 	"INSERT INTO disciplineweight (DisciplineWeight, DisciplineTypeIndex, " .
						"                              YearIndex, TermIndex) " .
						"VALUES  ({$row['DisciplineWeight']}, {$row['DisciplineTypeIndex']}, " .
						"         $currentyear, $newterm) ";
			$nres =&  $db->query($query);
			if(DB::isError($nres)) die($nres->getDebugInfo());           // Check for errors in query
		}
	}

	/* Update class_term */
	$query =	"INSERT INTO classterm (ClassIndex, TermIndex, AverageType, ConductType, " .
				"                        EffortType, AbsenceType, AverageTypeIndex, ConductTypeIndex, " .
				"                        EffortTypeIndex, CTCommentType, HODCommentType, " .
				"                        PrincipalCommentType, ReportTemplate, ReportTemplateType) " .
				"SELECT ClassIndex, $newterm, AverageType, ConductType, EffortType, AbsenceType, " .
				"       AverageTypeIndex, ConductTypeIndex, EffortTypeIndex, " .
				"       CTCommentType, HODCommentType, PrincipalCommentType, " .
				"       ReportTemplate, ReportTemplateType " .
				"       FROM classterm " .
				"WHERE classterm.TermIndex = $currentterm ";
	$nres =&  $db->query($query);
	if(DB::isError($nres)) die($nres->getDebugInfo());           // Check for errors in query

	/* Get all old classterms and their new classterms */
	$chkRes =&   $db->query("SELECT classterm.ClassTermIndex, newclassterm.ClassTermIndex AS " .
							"       NewClassTermIndex FROM classterm, class, classterm AS newclassterm " .
							"WHERE class.YearIndex      = $currentyear " .
							"AND   classterm.ClassIndex = class.ClassIndex " .
							"AND   classterm.TermIndex  = $currentterm " .
							"AND   newclassterm.ClassIndex = class.ClassIndex " .
							"AND   newclassterm.TermIndex = $newterm ");
	if(DB::isError($chkRes)) die($chkRes->getDebugInfo());           // Check for errors in query
	
	while($chkRow =& $chkRes->fetchRow(DB_FETCHMODE_ASSOC)) {
		$old_ctindex = $chkRow['ClassTermIndex'];
		$new_ctindex = $chkRow['NewClassTermIndex'];
		
		$query =	"INSERT INTO classlist (Username, ClassTermIndex, ClassOrder) " .
					"SELECT Username, $new_ctindex, ClassOrder FROM classlist " .
					"WHERE  ClassTermIndex = $old_ctindex";
		$nres =&  $db->query($query);
		if(DB::isError($nres)) die($nres->getDebugInfo());           // Check for errors in query
	}
	
	/* Insert conduct marks */
	update_conduct_year_term($currentyear, $newterm);

	/* Change current term */
	$nwCurRes =& $db->query("UPDATE currentterm SET TermIndex = $newterm " .
							"WHERE DepartmentIndex = $depindex");
	if(DB::isError($nwCurRes)) die($nwCurRes->getDebugInfo());          // Check for errors in query

	$currentterm           = $newterm;
	$termindex             = $newterm;
	$_SESSION['termindex'] = $newterm;
	log_event($LOG_LEVEL_ADMIN, "admin/newquarter.php", $LOG_ADMIN,
				"Created new quarter with subjects duplicated from current quarter.");

	echo "      <p><a href=\"$backLink\">Click here to continue</a></p>\n";
	
	include "footer.php";
?>