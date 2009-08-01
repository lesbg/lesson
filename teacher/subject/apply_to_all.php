<?php
	/*****************************************************************
	 * teacher/subject/apply_to_all.php  (c) 2007 Jonathan Dieter
	 *
	 * Run query to apply subject options to all this teacher's
	 * subjects.
	 *****************************************************************/

	/* Get variables */
	$subjectindex = safe(dbfuncInt2String($_GET['key']));
	$subject      = dbfuncInt2String($_GET['keyname']);
	$nextLink     = dbfuncInt2String($_GET['next']);                // Link to next page
	
	if(!isset($_POST['action'])) $_POST['action'] = "";

	include "core/category.php";
	include "core/settermandyear.php";
	
	/* Check whether user is authorized to change subject options */
	$res =& $db->query("SELECT subjectteacher.Username FROM subjectteacher " .
					   "WHERE subjectteacher.SubjectIndex = $subjectindex " .
					   "AND   subjectteacher.Username     = '$username'");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
	if($res->numRows() > 0) {
		$is_teacher = True;
	} else {
		$is_teacher = False;
	}
	
	if($is_teacher or $is_admin) {
		/* Get subject information */
		$res =&  $db->query("SELECT SubjectTypeIndex, CanModify, " .
							"TeacherCanChangeCategories FROM subject " .
							"WHERE SubjectIndex = $subjectindex");
		if(DB::isError($res)) die($res->getDebugInfo());
		$row =& $res->fetchRow(DB_FETCHMODE_ASSOC);
		$subjecttypeindex   = $row['SubjectTypeIndex'];
		$teacher_can_modify = $row['TeacherCanChangeCategories'];
		if($row['CanModify'] == 1) {
			$can_modify = True;
		} else {
			$can_modify = False;
		}
			
		/* Check which button was pressed */
		if($_POST["action"] == "Yes, apply options") {
			$title         = "LESSON - Saving changes...";
			$noHeaderLinks = true;
			$noJS          = true;
			
			include "header.php";                                          // Print header
			
			echo "      <p align='center'>Saving changes...";

			$query =	"SELECT new_subject.SubjectIndex, new_subject.CanModify, " .
						"       new_subject.TeacherCanChangeCategories " .
						"       FROM subjectteacher, subject, " .
						"       subject AS new_subject, subjectteacher AS new_teacher " .
						"WHERE subject.SubjectIndex = $subjectindex " .
						"AND   subjectteacher.SubjectIndex = subject.SubjectIndex " .
						"AND   subjectteacher.Username = new_teacher.Username " .
						"AND   new_subject.SubjectIndex = new_teacher.SubjectIndex " .
						"AND   new_subject.SubjectIndex != $subjectindex " .
						"AND   new_subject.YearIndex = subject.YearIndex " .
						"AND   new_subject.TermIndex = subject.TermIndex " .
						"AND   new_subject.NoMarks = 0";
			$res =&  $db->query($query);
			if(DB::isError($res)) die($res->getDebugInfo());

			while($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$new_subjectindex = $row['SubjectIndex'];
				if($row['CanModify'] == 1) {
					$new_can_modify = True;
				} else {
					$new_can_modify = False;
				}
				if($row['TeacherCanChangeCategories'] == 1) {
					$new_teacher_can_modify = True;
				} else {
					$new_teacher_can_modify = False;
				}

				// Set RenameUploads and ShowAverage
				if($new_can_modify or $is_admin) {
					$query =	"UPDATE subject, subject AS new_subject " .
								"SET new_subject.RenameUploads = subject.RenameUploads, " .
								"    new_subject.ShowAverage = subject.ShowAverage " .
								"WHERE subject.SubjectIndex = $subjectindex " .
								"AND   new_subject.SubjectIndex = $new_subjectindex";
					$aRes =&  $db->query($query);
					if(DB::isError($aRes)) die($aRes->getDebugInfo());
				}
			
				// Set categories
				if(($new_teacher_can_modify and $new_can_modify) or $is_admin) {
					// Remove categories not in base subject
					$query =	"SELECT new_category.CategoryListIndex FROM " .
								"   (SELECT CategoryListIndex, CategoryIndex FROM categorylist " .
								"    WHERE SubjectIndex = $new_subjectindex) AS new_category " .
								"  LEFT OUTER JOIN " .
								"   (SELECT CategoryListIndex, CategoryIndex FROM categorylist " .
								"    WHERE SubjectIndex = $subjectindex) AS base_category " .
								"  USING (CategoryIndex) " .
								"WHERE base_category.CategoryListIndex IS NULL";
					$aRes =&  $db->query($query);
					if(DB::isError($aRes)) die($aRes->getDebugInfo());
					while($aRow =& $aRes->fetchRow(DB_FETCHMODE_ASSOC)) {
						remove_category_from_subject($new_subjectindex, $aRow['CategoryListIndex']);
					}

					// Add categories from base subject
					$query =	"SELECT base_category.CategoryIndex FROM " .
								"   (SELECT CategoryListIndex, CategoryIndex FROM categorylist " .
								"    WHERE SubjectIndex = $subjectindex) AS base_category " .
								"  LEFT OUTER JOIN " .
								"   (SELECT CategoryListIndex, CategoryIndex FROM categorylist " .
								"    WHERE SubjectIndex = $new_subjectindex) AS new_category " .
								"  USING (CategoryIndex) " .
								"WHERE new_category.CategoryListIndex IS NULL";
					$aRes =&  $db->query($query);
					if(DB::isError($aRes)) die($aRes->getDebugInfo());
					while($aRow =& $aRes->fetchRow(DB_FETCHMODE_ASSOC)) {
						add_category_to_subject($new_subjectindex, $aRow['CategoryIndex'], 0);
					}

					// Make all weights equal to base subject category weights
					$query =	"UPDATE categorylist, categorylist AS new_categorylist " .
								"SET new_categorylist.Weight = categorylist.Weight, " .
								"    new_categorylist.TotalWeight = categorylist.TotalWeight " .
								"WHERE categorylist.CategoryIndex = new_categorylist.CategoryIndex " .
								"AND   categorylist.SubjectIndex = $subjectindex " .
								"AND   new_categorylist.SubjectIndex = $new_subjectindex";
					$aRes =&  $db->query($query);
					if(DB::isError($aRes)) die($aRes->getDebugInfo());
				}
			}
			log_event($LOG_LEVEL_TEACHER, "teacher/subject/apply_to_all.php", $LOG_TEACHER,
					"Applied options from subject $subject to all teacher's subjects.");
		
			echo "done.</p>\n";
			
			echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n";  // Link to next page
			
			include "footer.php";
		} else {
			$extraMeta     = "      <meta http-equiv='REFRESH' content='0;url=$nextLink'>\n";
			$noJS          = true;
			$noHeaderLinks = true;
			$title         = "LESSON - Cancelling...";
			
			include "header.php";
			
			echo "      <p align='center'>Cancelling and redirecting you to <a href='$nextLink'>$nextLink</a>." .
						"</p>\n";
			
			include "footer.php";
		}
	} else {  // User isn't authorized to view or change scores.
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "teacher/subject/apply_to_all.php", $LOG_DENIED_ACCESS,
				"Attempted to change options for subject $subject.");
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
	}
?>
