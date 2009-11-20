<?php
	/*****************************************************************
	 * teacher/assignment/new_or_modify_action.php  (c) 2004-2007 Jonathan Dieter
	 *
	 * Show common page information for changing or adding new grades
	 * and call appropriate second page.
	 *****************************************************************/

	 /* Get variables */
	$nextLink        = dbfuncInt2String($_GET['next']);              // Link to next page
	include "core/settermandyear.php";
	
	/* Check which button was pressed */
	if($_POST["action"] == "Save" or $_POST["action"] == "Update" or $_POST["action"] == "Move this assignment to next term") { // If update or save were pressed, print  
		$title         = "LESSON - Saving changes...";               //  common info and go to the appropriate page.
		$noHeaderLinks = true;
		$noJS          = true;
		
		include "header.php";                                        // Print header
		
		echo "      <p align='center'>Saving changes...";

		if($_POST["action"] == "Save") {
			$subjectindex = safe(dbfuncInt2String($_GET['key']));
			$query =	"SELECT subject.AverageType, subject.AverageTypeIndex " .
						"       FROM subject " .
						"WHERE subject.SubjectIndex = $subjectindex";
			$res =&  $db->query($query);
			if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
			$row =& $res->fetchRow(DB_FETCHMODE_ASSOC);
	
			$averagetype      = $row['AverageType'];
			$averagetypeindex = $row['AverageTypeIndex'];

		} else {
			$assignmentindex = safe(dbfuncInt2String($_GET['key']));
			
			$query =	"SELECT subject.SubjectIndex, subject.AverageType, subject.AverageTypeIndex " .
						"       FROM subject INNER JOIN assignment USING (SubjectIndex) " .
						"WHERE assignment.AssignmentIndex = $assignmentindex";
			$res =&  $db->query($query);
			if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
			$row =& $res->fetchRow(DB_FETCHMODE_ASSOC);
	
			$subjectindex     = $row['SubjectIndex'];
			$averagetype      = $row['AverageType'];
			$averagetypeindex = $row['AverageTypeIndex'];
		}

		/* Check whether or not a title was included and set title to "No title" if it wasn't included */
		if(!isset($_POST['title']) or $_POST['title'] == "") {
			echo "</p>\n      <p>Title not entered, setting to 'No title'.</p>\n      <p>";  // Print error message
			$_POST['title'] = "No title";
		}
		
		/* Check whether or not a description was included and set it properly if it was */
		if($_POST['descr_type'] == '0') {
			if($_POST['descr'] == "") {
				$descr = "NULL";
			} else {
				$descr = safe(htmlize_comment($_POST['descr']));
				$descr = "'$descr'";
			}
			$descr_data = "NULL";
			$descr_file_type = "NULL";
		} else {
			if(!isset($_FILES['descr_upload']) or $_FILES['descr_upload']['error'] != UPLOAD_ERR_OK) {
				$descr_data = "NULL";
				$descr_file_type = "NULL";

				if(!isset($_FILES['descr_upload'])) {
					$error = "Error when attempting to upload file";
				} elseif($_FILES['descr_upload']['error'] == UPLOAD_ERR_INI_SIZE or
						$_FILES['descr_upload']['error'] == UPLOAD_ERR_FORM_SIZE)  {
					$error = "You have attempted to upload a file that is too large";
				} elseif($_FILES['descr_upload']['error'] == UPLOAD_ERR_PARTIAL) {
					$error = "Only part of the file was uploaded";
				} elseif($_FILES['descr_upload']['error'] == UPLOAD_ERR_NO_FILE) {
					//$error = "You must choose a file to be uploaded";
					$descr_data = "DescriptionData";
					$descr_file_type= "DescriptionFileType";
					$error = false;
				} else {
					$error = "Error when attempting to upload file";
				}
				if($error) {
					print "</p><p align='center' class='error'>$error.  Description will be blank.</p><p align='center'>\n";
				}
			} else {
				$descr_file_type  = safe($_FILES['descr_upload']['type']);
				if($descr_file_type != "application/pdf") {
					print "</p><p align='center' class='error'>Uploaded file is not a PDF document.  Description will be blank.</p><p align='center'>\n";
					$descr_file_type = "NULL";
					$descr_data = "NULL";
				} else {
					$descr_file  = $_FILES['descr_upload']['tmp_name'];
		
					$descr_handle = fopen($descr_file, "r");
					$descr_data = safe(fread($descr_handle, filesize($descr_file)));
					$descr_data = "'$descr_data'";
					$descr_file_type = "'$descr_file_type'";
				}
			}
			$descr = "NULL";
		}

		/* Check whether or not the date was set, and set it to today if it wasn't */
		if(!isset($_POST['date']) or $_POST['date'] == "") {         // Make sure date is in correct format.
			echo "</p>\n      <p align='center'>Date not entered, defaulting to today.</p>\n      <p align='center'>";       // Print error message
			$_POST['date'] =& dbfuncCreateDate(date($dateformat));
		} else {
			$_POST['date'] =& dbfuncCreateDate($_POST['date']);
		}
		$_POST['date'] = "'" . $_POST['date'] . "'";
		
		/* Check whether or not the due date was set, and set it to NULL if it wasn't */
		if(!isset($_POST['duedate']) or $_POST['duedate'] == "") {         // Make sure date is in correct format.
			$_POST['duedate'] = "NULL";
		} else {
			$_POST['duedate'] =& dbfuncCreateDate(safe($_POST['duedate']));
			$_POST['duedate'] = "'" . $_POST['duedate'] . "'";
		}
		
		/* Check whether this assignment should be hidden from students */
		if($_POST['hidden'] == "on") {                      // Make sure ActiveStudent is right type.
			$_POST['hidden'] = "1";
		} else {
			$_POST['hidden'] = "0";
		}
		
		/* Check whether this assignment has been marked */
		if($_POST['marked'] == "on") {                      // Make sure ActiveStudent is right type.
			$_POST['marked'] = "1";
		} else {
			$_POST['marked'] = "0";
		}
		

		/* Check whether this assignment is uploadable */
		if($_POST['uploadable'] == "on") {                      // Make sure ActiveStudent is right type.
			$_POST['uploadable'] = "1";
			/* Set assignment's directory */
			$remove_array = array("!", "#", ":", "/", "\\", "\"", "'", "<", ">", "?", "*", "|", "&", "@", "`");
			$upload_name = str_replace($remove_array, "", safe($_POST["title"]));
		} else {
			$_POST['uploadable'] = "0";
			$upload_name = "NULL";
		}
		
		$title = safe($_POST['title']);
		
		/* Check whether maximum score was included, and set to 0 if it wasn't */
		if($averagetype == $AVG_TYPE_PERCENT) {
			if(!isset($_POST['max']) or $_POST['max'] == "") {
				echo "</p>\n      <p>Maximum score not entered, defaulting to 0.</p>\n      <p>";  // Print error message
				$_POST['max'] = "0";
			} else {
				if($_POST['max'] != "0") {
					settype($_POST['max'], "double");
					if($_POST['max'] <= 0)
						echo "</p>\n      <p>Maximum score must be a number greater than 0...defaulting to 0.</p>\n      <p>";
					settype($_POST['max'], "string");
				}
			}
		
			/* Check whether top mark was included, and set to NULL if it wasn't */
			if(!isset($_POST['top_mark']) or $_POST['top_mark'] == "") {
				if($_POST['curve_type'] == 2) {
					echo "</p>\n      <p>Top mark must be a number between 0 and 100...setting to 100.</p>\n      <p>";
					$_POST['top_mark'] = "100";
				} else {
					$_POST['top_mark'] = "NULL";
				}
			} else {
				if($_POST['top_mark'] != "0") {
					settype($_POST['top_mark'], "double");
					if($_POST['top_mark'] <= 0) {
						echo "</p>\n      <p>Top mark must be a number between 0 and 100...setting to 0.</p>\n      <p>";
						$_POST['top_mark'] = "0";
					} elseif ($_POST['top_mark'] > 100) {
						echo "</p>\n      <p>Top mark must be a number between 0 and 100...setting to 100.</p>\n      <p>";
						$_POST['top_mark'] = "100";
					}
					settype($_POST['top_mark'], "string");
				}
			}
			
			/* Check whether bottom mark was included, and set to NULL if it wasn't */
			if(!isset($_POST['bottom_mark']) or $_POST['bottom_mark'] == "") {
				if($_POST['curve_type'] == 2) {
					echo "</p>\n      <p>Bottom mark must be a number between 0 and 100...setting to 0.</p>\n      <p>";
					$_POST['bottom_mark'] = "0";
				} else {
					$_POST['bottom_mark'] = "NULL";
				}
			} else {
				if($_POST['bottom_mark'] != "0") {
					settype($_POST['bottom_mark'], "double");
					if($_POST['bottom_mark'] <= 0) {
						echo "</p>\n      <p>Bottom mark must be a number between 0 and 100...setting to 0.</p>\n      <p>";
						$_POST['bottom_mark'] = "0";
					} elseif ($_POST['top_mark'] > 100) {
						echo "</p>\n      <p>Bottom mark must be a number between 0 and 100...setting to 100.</p>\n      <p>";
						$_POST['bottom_mark'] = "100";
					}
					settype($_POST['bottom_mark'], "string");
				}
			}
			
			//echo "<p>{$_POST['category']}</p>\n";
			/* Check category */
			if(!isset($_POST['category']) or $_POST['category'] == "") {
				$_POST['category'] = "NULL";	 /* Check whether user is authorized to change scores */
			} else {
				settype($_POST['category'], "double");
				settype($_POST['category'], "string");
			}
	
			/* Check whether weight was included, and set to 0 if it wasn't */
			if(!isset($_POST['weight']) or $_POST['weight'] == "") {
				echo "</p>\n      <p>Weight not entered, defaulting to 1.</p>\n      <p>";  // Print error message
				$_POST['weight'] = "1";
			} else {
				if($_POST['weight'] != "0") {
					settype($_POST['weight'], "double");
					if($_POST['weight'] == 0) {
						echo "</p>\n      <p>Weight must be a number...defaulting to 1.</p>\n      <p>";
						$_POST['weight'] = 1;
					}
					settype($_POST['max'], "string");
				}
			}
		}
		
		if($_POST["action"] == "Save") {          // Add new assignment if "Save" was pressed
			include "teacher/assignment/new_action.php";
		} else {
			include "teacher/assignment/modify_action.php";   // Modify assignment if "Update" was pressed
		}
		
		if($error) {                              // If we ran into any errors, print failed, otherwise print done
			echo "failed!</p>\n";
		} else {
			echo "done.</p>\n";
		}
		echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n";  // Link to next page
		
		include "footer.php";
	} elseif($_POST["action"] == 'Delete') {                        // If delete was pressed, confirm deletion
		include "teacher/assignment/delete_confirm.php";
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
?>