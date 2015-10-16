<?php
/**
 * ***************************************************************
 * admin/subject/new.php (c) 2005-2008 Jonathan Dieter
 *
 * Create new subject
 * ***************************************************************
 */

/* Get variables */
$title = "New Subject";
$link = "index.php?location=" .
		 dbfuncString2Int("admin/subject/new_or_modify_action.php") .
		 "&amp;next=" . $_GET['next'];

include "header.php"; // Show header
$showalldeps = true; // edit subjects
include "core/settermandyear.php";

/* Check whether user is authorized to change subject */
if ($is_admin) {
	if (isset($errorlist)) { // If there were errors, print them, and reset fields
		echo $errorlist;
		$_POST['name'] = htmlspecialchars($_POST['name']);
		$_POST['subjecttype'] = intval($_POST['subjecttype']);
		
		if (! is_null($_POST['conduct_type'])) {
			$_POST['conduct_type'] = intval($_POST['conduct_type']);
			if ($_POST['conduct_type'] >= $CONDUCT_TYPE_MAX)
				$_POST['conduct_type'] = $CONDUCT_TYPE_NONE;
		} else {
			$_POST['conduct_type'] = $CONDUCT_TYPE_NONE;
		}
		if (! is_null($_POST['effort_type'])) {
			$_POST['effort_type'] = intval($_POST['effort_type']);
			if ($_POST['effort_type'] >= $EFFORT_TYPE_MAX)
				$_POST['effort_type'] = $EFFORT_TYPE_NONE;
		} else {
			$_POST['effort_type'] = $EFFORT_TYPE_NONE;
		}
		if (! is_null($_POST['average_type'])) {
			$_POST['average_type'] = intval($_POST['average_type']);
			if ($_POST['average_type'] >= $EFFORT_TYPE_MAX)
				$_POST['average_type'] = $AVG_TYPE_NONE;
		} else {
			$_POST['average_type'] = $AVG_TYPE_NONE;
		}
		if ($_POST['categories'] == "on" or $_POST['categories'] == 1) {
			$_POST['categories'] = 1;
		} else {
			$_POST['categories'] = 0;
		}
		if (! is_null($_POST['class'])) {
			$_POST['class'] = intval($_POST['class']);
		} else {
			$_POST['class'] = NULL;
		}
		if (! is_null($_POST['grade'])) {
			$_POST['grade'] = intval($_POST['grade']);
		} else {
			$_POST['grade'] = NULL;
		}
	} else {
		$_POST['name'] = "";
		$_POST['conduct_type'] = $CONDUCT_TYPE_NONE;
		$_POST['effort_type'] = $EFFORT_TYPE_NONE;
		$_POST['average_type'] = $AVG_TYPE_NONE;
		$_POST['subjecttype'] = 0;
		$_POST['class'] = NULL;
		$_POST['grade'] = NULL;
	}
	
	echo "      <form action='$link' method='post'>\n"; // Form method
	echo "         <table class='transparent' align='center'>\n"; // Table headers
	
	/* Show subject name */
	echo "            <tr>\n";
	echo "               <td>Name</td>\n";
	echo "               <td><input type='text' name='name' value='{$_POST['name']}' size=35></td>\n";
	echo "            </tr>\n";
	
	/* Show list of subject types */
	$res = &  $db->query(
					"SELECT SubjectTypeIndex, Title FROM subjecttype ORDER BY Title");
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	echo "            <tr>\n";
	echo "               <td>Type</td>\n";
	echo "               <td><select name='subjecttype'>\n";
	while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
		echo "                  <option value='{$row['SubjectTypeIndex']}'";
		if ($row['SubjectTypeIndex'] == intval($_POST['subjecttype']))
			echo " selected";
		echo ">{$row['Title']}\n";
	}
	echo "               </select></td>\n";
	echo "            </tr>\n";
	
	/* Show list of classes */
	$res = &  $db->query(
					"SELECT ClassName, ClassIndex FROM class WHERE YearIndex=$yearindex AND DepartmentIndex=$depindex ORDER BY Grade");
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	echo "            <tr>\n";
	echo "               <td>Class</td>\n";
	echo "               <td><select name='class'>\n";
	echo "                  <option value='NULL'";
	if (is_null($_POST['class']))
		echo " selected";
	echo ">(No specific class)\n";
	while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
		echo "                  <option value='{$row['ClassIndex']}'";
		if ($row['ClassIndex'] == $_POST['class'])
			echo " selected";
		echo ">{$row['ClassName']}\n";
	}
	echo "               </select></td>\n";
	echo "            </tr>\n";
	
	/* Show list of grades */
	$res = &  $db->query(
					"SELECT GradeName, Grade FROM grade WHERE DepartmentIndex=$depindex ORDER BY Grade");
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	echo "            <tr>\n";
	echo "               <td>Grade</td>\n";
	echo "               <td><select name='grade'>\n";
	echo "                  <option value='NULL'";
	if (is_null($_POST['grade']))
		echo " selected";
	echo ">(No specific grade)\n";
	while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
		echo "                  <option value='{$row['Grade']}'";
		if ($row['Grade'] == $_POST['grade'])
			echo " selected";
		echo ">{$row['GradeName']}\n";
	}
	echo "               </select></td>\n";
	echo "            </tr>\n";
	
	/* Average type for subject */
	echo "            <tr>\n";
	echo "               <td>Marks</td>\n";
	if ($_POST['average_type'] == $AVG_TYPE_NONE) {
		$anul_checked = "checked";
	} else {
		$anul_checked = "";
	}
	if ($_POST['average_type'] == $AVG_TYPE_PERCENT) {
		$aper_checked = "checked";
	} else {
		$aper_checked = "";
	}
	if ($_POST['average_type'] == $AVG_TYPE_INDEX) {
		$aind_checked = "checked";
	} else {
		$aind_checked = "";
	}
	if ($_POST['average_type'] == $AVG_TYPE_GRADE) {
		$agrd_checked = "checked";
	} else {
		$agrd_checked = "";
	}
	
	echo "               <td>\n";
	echo "                   <label for='average_none'>\n";
	echo "                      <input type='radio' name='average_type' id='average_none' value='$AVG_TYPE_NONE' $anul_checked>No marks for students\n";
	echo "                   </label><br>\n";
	echo "                   <label for='average_percent'>\n";
	echo "                      <input type='radio' name='average_type' id='average_percent' value='$AVG_TYPE_PERCENT' $aper_checked>Student mark is a percentage\n";
	echo "                   </label><br>\n";
	echo "                   <label for='average_index'>\n";
	echo "                      <input type='radio' name='average_type' id='average_index' value='$AVG_TYPE_INDEX' $aind_checked>Student mark is non-numeric\n";
	echo "                   </label><br>\n";
	echo "                   <label for='average_grade'>\n";
	echo "                      <input type='radio' name='average_type' id='average_grade' value='$AVG_TYPE_GRADE' $agrd_checked>Student mark is grade based on their percentage\n";
	echo "                   </label><br>\n";
	echo "               </td>\n";
	echo "            </tr>\n";
	
	/* Conduct type for subject */
	echo "            <tr>\n";
	echo "               <td>Conduct</td>\n";
	if ($_POST['conduct_type'] == $CONDUCT_TYPE_NONE) {
		$cnul_checked = "checked";
	} else {
		$cnul_checked = "";
	}
	if ($_POST['conduct_type'] == $CONDUCT_TYPE_PERCENT) {
		$cper_checked = "checked";
	} else {
		$cper_checked = "";
	}
	if ($_POST['conduct_type'] == $CONDUCT_TYPE_INDEX) {
		$cind_checked = "checked";
	} else {
		$cind_checked = "";
	}
	echo "               <td>\n";
	echo "                   <label for='conduct_none'>\n";
	echo "                      <input type='radio' name='conduct_type' id='conduct_none' value='$CONDUCT_TYPE_NONE' $cnul_checked>No per-subject conduct marks for students\n";
	echo "                   </label><br>\n";
	echo "                   <label for='conduct_percent'>\n";
	echo "                      <input type='radio' name='conduct_type' id='conduct_percent' value='$CONDUCT_TYPE_PERCENT' $cper_checked>Conduct mark is a percentage\n";
	echo "                   </label><br>\n";
	echo "                   <label for='conduct_index'>\n";
	echo "                      <input type='radio' name='conduct_type' id='conduct_index' value='$CONDUCT_TYPE_INDEX' $cind_checked>Conduct mark is non-numeric\n";
	echo "                   </label><br>\n";
	echo "               </td>\n";
	echo "            </tr>\n";
	
	/* Effort type for subject */
	echo "            <tr>\n";
	echo "               <td>Effort</td>\n";
	if ($_POST['effort_type'] == $EFFORT_TYPE_NONE) {
		$enul_checked = "checked";
	} else {
		$enul_checked = "";
	}
	if ($_POST['effort_type'] == $EFFORT_TYPE_PERCENT) {
		$eper_checked = "checked";
	} else {
		$eper_checked = "";
	}
	if ($_POST['effort_type'] == $EFFORT_TYPE_INDEX) {
		$eind_checked = "checked";
	} else {
		$eind_checked = "";
	}
	echo "               <td>\n";
	echo "                   <label for='effort_none'>\n";
	echo "                      <input type='radio' name='effort_type' id='effort_none' value='$EFFORT_TYPE_NONE' $enul_checked>No effort marks for students\n";
	echo "                   </label><br>\n";
	echo "                   <label for='effort_percent'>\n";
	echo "                      <input type='radio' name='effort_type' id='effort_percent' value='$EFFORT_TYPE_PERCENT' $eper_checked>Effort mark is a percentage\n";
	echo "                   </label><br>\n";
	echo "                   <label for='effort_index'>\n";
	echo "                      <input type='radio' name='effort_type' id='effort_index' value='$EFFORT_TYPE_INDEX' $eind_checked>Effort mark is non-numeric\n";
	echo "                   </label><br>\n";
	echo "               </td>\n";
	echo "            </tr>\n";
	
	/* Is teacher allowed to set up categories for this subject? */
	echo "            <tr>\n";
	echo "               <td>&nbsp;</td>\n";
	if ($_POST['categories'] == 1) {
		$cgchecked = "checked";
	} else {
		$cgchecked = "";
	}
	echo "               <td>\n";
	echo "                  <label for='categories'>";
	echo "                     <input name='categories' type='checkbox' id='categories' $cgchecked>";
	echo "The teacher is allowed to set their own categories for this subject.\n";
	echo "                  </label>\n";
	echo "               </td>\n";
	echo "            </tr>\n";
	
	echo "         </table>\n"; // End of table
	echo "         <p align='center'>\n";
	echo "            <input type='submit' name='action' value='Save' \>\n";
	echo "            <input type='submit' name='action' value='Cancel' \>\n";
	echo "         </p>\n";
	echo "      </form>\n";
} else { // User isn't authorized to view or change scores.
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";
?>