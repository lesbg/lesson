<?php
/**
 * ***************************************************************
 * teacher/subject/modify.php (c) 2005-2007 Jonathan Dieter
 *
 * Modify subject options
 * ***************************************************************
 */

/* Get variables */
if (! isset($nextLink))
	$nextLink = $backLink;

$subject = dbfuncInt2String($_GET['keyname']);
$title = "Subject Options for $subject";
$subjectindex = safe(dbfuncInt2String($_GET['key']));
$link = "index.php?location=" .
		 dbfuncString2Int("teacher/subject/modify_action.php") . "&amp;key=" .
		 $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] . "&amp;next=" .
		 dbfuncString2Int($nextLink);

include "core/settermandyear.php";
include "header.php";

/* Check whether user is authorized to change subject options */
$res = & $db->query(
				"SELECT subjectteacher.Username FROM subjectteacher " .
				 "WHERE subjectteacher.SubjectIndex = $subjectindex " .
				 "AND   subjectteacher.Username     = '$username'");
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query
if ($res->numRows() > 0) {
	$is_teacher = True;
} else {
	$is_teacher = False;
}

/* Check whether user is authorized to change subject */
if ($is_teacher or $is_admin) {
	$nochangeyt = true;
	
	/* Get subject information */
	$res = &  $db->query(
					"SELECT ShowAverage, RenameUploads, CanModify, SubjectTypeIndex, " .
					 "TeacherCanChangeCategories FROM subject " .
					 "WHERE SubjectIndex = $subjectindex");
	if (DB::isError($res))
		die($res->getDebugInfo());
	if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		if ($row['TeacherCanChangeCategories'] == 1) {
			$teacher_can_modify = True;
		} else {
			$teacher_can_modify = False;
		}
		if ($row['CanModify'] == 1) {
			$can_modify = True;
		} else {
			$can_modify = False;
		}
		
		if (! isset($errorlist)) { // If there were errors, print them, and reset fields
			if ($row['ShowAverage'] == 1) {
				$_POST['showaverage'] = "on";
			} else {
				$_POST['showaverage'] = "off";
			}
			if ($row['RenameUploads'] == 1) {
				$_POST['renameuploads'] = "on";
			} else {
				$_POST['renameuploads'] = "off";
			}
		}
		
		if ($_POST['showaverage'] == "on") {
			$show_average = "checked";
		} else {
			$show_average = "";
		}
		if ($_POST['renameuploads'] == "on") {
			$rename_uploads = "checked";
		} else {
			$rename_uploads = "";
		}
		
		$subjecttypeindex = $row['SubjectTypeIndex'];
		
		echo "      <form action='$link' name='modSubj' method='post'>\n";
		echo "         <table class='transparent' align='center'>\n";
		
		/* Show subject average */
		echo "            <tr>\n";
		echo "               <td colspan='4'>\n";
		echo "                  <label for='showaverage'>\n";
		echo "                     <input type='checkbox' id='showaverage' name='showaverage' $show_average>Show subject average to students.\n";
		echo "                  </label>\n";
		echo "               </td>\n";
		echo "            </tr>\n";
		echo "            <tr>\n";
		echo "               <td colspan='4'>\n";
		echo "                  <label for='renameuploads'>\n";
		echo "                     <input type='checkbox' id='renameuploads' name='renameuploads' $rename_uploads>Rename any uploaded files to student's username.\n";
		echo "                  </label>\n";
		echo "               </td>\n";
		echo "            </tr>\n";
		if ($is_admin or $teacher_can_modify) {
			echo "            <tr><td colspan='4'>&nbsp;</td></tr>\n";
			if (isset($errorlist)) {
				echo "            <tr><td colspan='4' align='center'><span class='error'>$errorlist</span></td></tr>\n";
			}
			echo "            <tr><td colspan='4'><b>Categories</b></td></tr>\n";
			/* Get all available categories for this subject and store in $options */
			$query = "SELECT category.CategoryIndex, category.CategoryName " .
					 "       FROM category LEFT OUTER JOIN categorytype USING (CategoryIndex) " .
					 "WHERE  categorytype.SubjectTypeIndex IS NULL " .
					 "OR     categorytype.SubjectTypeIndex=$subjecttypeindex " .
					 "ORDER BY category.CategoryName, category.CategoryIndex";
			$res = &  $db->query($query);
			if (DB::isError($res))
				die($res->getDebugInfo());
			
			$options = "";
			while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
				$options .= "<option value='{$row['CategoryIndex']}'>{$row['CategoryName']}</option>";
			}
			
			/* Show current categories */
			$query = "SELECT categorylist.CategoryListIndex, category.CategoryName, " .
					 "       categorylist.Weight, categorylist.TotalWeight, category.CategoryIndex " .
					 "       FROM category, categorylist " .
					 "WHERE categorylist.SubjectIndex = $subjectindex " .
					 "AND   category.CategoryIndex = categorylist.CategoryIndex " .
					 "ORDER BY category.CategoryName, categorylist.CategoryListIndex";
			$res = &  $db->query($query);
			if (DB::isError($res))
				die($res->getDebugInfo());
			
			if ($res->numRows() > 0) {
				while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
					$cat_list_index = $row['CategoryListIndex'];
					
					if ($row['TotalWeight'] > 0) {
						$percentage = sprintf("%01.1f", 
											$row['Weight'] / $row['TotalWeight'] *
												 100) . "%";
					} else {
						$percentage = "N/A";
					}
					echo "            <tr>\n";
					echo "               <td>{$row['CategoryName']}</td>\n";
					if (! isset($_POST["weight_$cat_list_index"])) {
						$val = $row['Weight'];
					} else {
						$val = $_POST["weight_$cat_list_index"];
					}
					echo "               <td><input type='text' name='weight_{$row['CategoryListIndex']}' value='{$val}' size='5' /></td>\n";
					echo "               <td><label name='percentage_{$row['CategoryListIndex']}'> {$percentage}</label></td>\n";
					if ($is_admin or $can_modify) {
						echo "               <td><input type='submit' name='{$row['CategoryListIndex']}' value='Remove' \></td>\n";
					} else {
						echo "               <td>&nbsp;</td>\n";
					}
					echo "            </tr>\n";
				}
			} else {
				echo "            <tr><td colspan='4' align='center'><i>None</i></td></tr>\n";
			}
			if ($is_admin or $can_modify) {
				echo "            <tr><td colspan='4'><b>Add category</b></td></tr>\n";
				echo "            <tr>\n";
				if (! isset($_POST['name_add'])) {
					$_POST['name_add'] = "";
				} else {
					$_POST['name_add'] = htmlspecialchars($_POST['name_add']);
				}
				$this_option = str_replace(
										"<option value='{$_POST['name_add']}'>", 
										"<option value='{$_POST['name_add']}'}' selected>", 
										$options);
				echo "               <td><select name='name_add'>\n";
				echo "                      <option value=''></option>\n";
				echo "                      $this_option";
				echo "                   </select>\n";
				echo "               </td>\n";
				if (! isset($_POST['weight_add']))
					$_POST['weight_add'] = "";
				echo "               <td><input type='text' name='weight_add' size='5' value='{$_POST['weight_add']}'/></td>\n";
				echo "               <td><label name='percentage_add'>&nbsp;</label></td>\n";
				echo "               <td><input type='submit' name='action' value='Add' \></td>\n";
				echo "            </tr>\n";
			}
		}
		echo "         </table>\n";
		echo "         <p align='center'>\n";
		if ($is_admin or $can_modify) {
			echo "            <input type='submit' name='action' value='Update' \>\n";
			echo "            <input type='submit' name='action' value='Apply to all my subjects' \>\n";
			echo "            <input type='submit' name='action' value='Cancel' \>\n";
		} else {
			echo "            <input type='submit' name='action' value='Close' \>\n";
		}
		echo "         </p>\n";
		echo "      </form>\n";
	} else { // Couldn't find $subjectindex in subject table
		echo "      <p align='center'>Can't find subject.  Have you deleted it?</p>\n";
		echo "      <p align='center'><a href='$backLink'>Click here to go back</a></p>\n";
	}
	log_event($LOG_LEVEL_EVERYTHING, "teacher/subject/modify.php", $LOG_ADMIN, 
			"Opened options for subject $subject.");
} else {
	/* Log unauthorized access attempt */
	log_event($LOG_LEVEL_ERROR, "teacher/subject/modify.php", 
			$LOG_DENIED_ACCESS, 
			"Attempted to open options for subject $subject.");
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";
?>