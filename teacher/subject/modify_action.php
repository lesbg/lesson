<?php
	/*****************************************************************
	 * teacher/subject/modify_action.php  (c) 2005-2007 Jonathan Dieter
	 *
	 * Run query to change subject options in database
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
		if($row['TeacherCanChangeCategories'] == 1) {
			$teacher_can_modify = True;
		} else {
			$teacher_can_modify = False;
		}
		if($row['CanModify'] == 1) {
			$can_modify = True;
		} else {
			$can_modify = False;
		}
			
		/* Check which button was pressed */
		if($_POST["action"] == "Update" or $_POST["action"] == "Apply to all my subjects") {
			if($_POST['showaverage'] == "on") {                      // Make sure ShowAverage is right type.
				$_POST['showaverage'] = "1";
			} else {
				$_POST['showaverage'] = "0";
			}
			if($_POST['renameuploads'] == "on") {                    // Make sure ShowAverage is right type.
				$_POST['renameuploads'] = "1";
			} else {
				$_POST['renameuploads'] = "0";
			}
			/* Change subject */
			$_POST['showaverage'] = intval($_POST['showaverage']);
			$_POST['renameuploads'] = intval($_POST['renameuploads']);
			
			if($can_modify or $is_admin) {
				$aRes =& $db->query("UPDATE subject SET " .
									"  ShowAverage={$_POST['showaverage']}, " .
									"  RenameUploads={$_POST['renameuploads']} " .
									"WHERE SubjectIndex = $subjectindex");
				if(DB::isError($aRes)) die($aRes->getDebugInfo());
			}

			if(($teacher_can_modify and $can_modify) or $is_admin) {
				$query =	"SELECT CategoryListIndex FROM categorylist " .
							"WHERE SubjectIndex=$subjectindex";
				$aRes =&  $db->query($query);
				if(DB::isError($aRes)) die($aRes->getDebugInfo());
	
				while($aRow =& $aRes->fetchRow(DB_FETCHMODE_ASSOC)) {
					$cat_list_index = $aRow['CategoryListIndex'];
	
					if(isset($_POST["weight_$cat_list_index"])) {
						$weight = floatval($_POST["weight_$cat_list_index"]);
	
						$query =	"UPDATE categorylist " .
									"SET Weight=$weight " .
									"WHERE CategoryListIndex=$cat_list_index";
						$res =&  $db->query($query);
						if(DB::isError($res)) die($res->getDebugInfo());
					}
				}
				recalc_weight($subjectindex);
			}

			if($_POST["action"] == "Apply to all my subjects") {
				include "teacher/subject/apply_to_all_confirm.php";
			} else {
				$title         = "LESSON - Saving changes...";
				$noHeaderLinks = true;
				$noJS          = true;
				
				include "header.php";                                          // Print header
				
				echo "      <p align='center'>Saving changes...";
	
				log_event($LOG_LEVEL_TEACHER, "teacher/subject/modify_action.php", $LOG_TEACHER,
						"Modified options for subject $subject.");
			
				echo "done.</p>\n";
				
				echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n";  // Link to next page
				
				include "footer.php";
			}
		} elseif($_POST["action"] == "Cancel" or $_POST["action"] == "Close") {
			$extraMeta     = "      <meta http-equiv='REFRESH' content='0;url=$nextLink'>\n";
			$noJS          = true;
			$noHeaderLinks = true;
			$title         = "LESSON - Cancelling...";
			
			include "header.php";
			
			echo "      <p align='center'>Cancelling and redirecting you to <a href='$nextLink'>$nextLink</a>." .
						"</p>\n";
			
			include "footer.php";
		} else {
			if(($teacher_can_modify == "1" and $can_modify) or $is_admin) {
				if($_POST["action"] == "Add") {  // Check for added categories
					if(!isset($_POST['name_add'])) $_POST['name_add'] = "";
					if(!isset($_POST['weight_add'])) $_POST['weight_add'] = 0;

					$error = add_category_to_subject($subjectindex, $_POST['name_add'], floatval($_POST['weight_add']));
			
					if($error) {
						if($error == $CAT_ERR_NOTHING) {
							$errorlist = "You must choose a category";
						} elseif($error == $CAT_ERR_UNAVAILABLE) {
							$errorlist = "The chosen category isn't available.  Please choose another.";
							unset($_POST['name_add']);
						} elseif($error == $CAT_ERR_ALREADY_IN_SUBJECT) {
							$errorlist = "You already have a category of that type.  Please choose another type.";
							unset($_POST['name_add']);
						}
					} else {
						/* Reset variables */
						unset($_POST['name_add']);
						unset($_POST['weight_add']);
					}
				} else { // Check for removed categories
					$query =	"SELECT CategoryListIndex FROM categorylist " .
								"WHERE SubjectIndex=$subjectindex";
					$aRes =&  $db->query($query);
					if(DB::isError($aRes)) die($aRes->getDebugInfo());

					while($row =& $aRes->fetchRow(DB_FETCHMODE_ASSOC)) {
						if(isset($_POST[$row['CategoryListIndex']]) and $_POST[$row['CategoryListIndex']] == "Remove") {
							$messages = remove_category_from_subject($subjectindex, $row['CategoryListIndex']);
							$errorlist = $messages;
							break;
						}
					}
				}
				update_subject($subjectindex);
			} else {
				$errorlist = "You don't have permission to modify the categories for this subject.";
			}
			include "teacher/subject/modify.php";
		}
	} else {  // User isn't authorized to view or change scores.
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "teacher/subject/modify_action.php", $LOG_DENIED_ACCESS,
				"Attempted to change options for subject $subject.");
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
	}
?>
