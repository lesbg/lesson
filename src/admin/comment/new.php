<?php
/**
 * ***************************************************************
 * admin/comment/new.php (c) 2008 Jonathan Dieter
 *
 * Create new comment
 * ***************************************************************
 */

/* Get variables */
$title = "New Comment";
$link = "index.php?location=" .
		 dbfuncString2Int("admin/comment/new_or_modify_action.php") .
		 "&amp;next=" . $_GET['next'];

include "header.php"; // Show header

/* Check whether user is authorized to change subject */
if ($is_admin) {
	$hidden_val = "";
	
	if (isset($errorlist)) {
		echo $errorlist;
	}
	if (! isset($_POST['comment'])) {
		$_POST['comment'] = "";
	} else {
		$_POST['comment'] = htmlspecialchars($_POST['comment']);
	}
	if (! isset($_POST['strength'])) {
		$_POST['strength'] = "NULL";
	}
	if (! isset($_POST['number'])) {
		$_POST['number'] = "";
	}
	
	echo "      <form action='$link' method='post'>\n"; // Form method
	echo "         <input type='hidden' name='type' value='new'>\n";
	echo "         <table class='transparent' align='center' width=405px>\n"; // Table headers
	
	/* Show comment */
	echo "            <tr>\n";
	echo "               <td colspan='2'><p><b>Comment</b></p>\n";
	echo "                  <p>The following substitutions may be made:<br>\n";
	echo "                  <ul>\n";
	echo "                     <li><b>[FullName]</b> = Student's full name</b></li>\n";
	echo "                     <li><b>[Name]</b> = Student's first name</b></li>\n";
	echo "                     <li><b>[his/her]</b> = his or her depending on student's gender</b></li>\n";
	echo "                     <li><b>[him/her]</b> = him or her depending on student's gender</b></li>\n";
	echo "                     <li><b>[he/she]</b> = he or she depending on student's gender</b></li>\n";
	echo "                  </ul>\n";
	echo "                  Capitalizations will be handled automatically if you capitalize the first letter of the substitution (i.e. <b>[He/she]</b> = He or She)</p>\n";
	echo "                  <p><textarea rows=4 cols=54 name='comment'>{$_POST['comment']}</textarea></p>\n";
	echo "               </td>\n";
	echo "            </tr>\n";
	
	/* Comment strength */
	echo "            <tr>\n";
	echo "               <td><b>Comment value</b><br>\n";
	if ($_POST['strength'] == "NULL" or ! isset($_POST['strength'])) {
		$null_checked = "checked";
	} else {
		$null_checked = "";
	}
	if ($_POST['strength'] == "3") {
		$pos_checked = "checked";
	} else {
		$pos_checked = "";
	}
	if ($_POST['strength'] == "2") {
		$neutral_checked = "checked";
	} else {
		$neutral_checked = "";
	}
	if ($_POST['strength'] == "1") {
		$neg_checked = "checked";
	} else {
		$neg_checked = "";
	}
	echo "                   <label for='comment_null'>\n";
	echo "                      <input type='radio' name='strength' id='comment_null' value='' $null_checked>Comment has no value\n";
	echo "                   </label><br>\n";
	echo "                   <label for='comment_3'>\n";
	echo "                      <input type='radio' name='strength' id='comment_3' value='3' $pos_checked>Comment is positive\n";
	echo "                   </label><br>\n";
	echo "                   <label for='comment_2'>\n";
	echo "                      <input type='radio' name='strength' id='comment_2' value='2' $neutral_checked>Comment is neutral\n";
	echo "                   </label><br>\n";
	echo "                   <label for='comment_1'>\n";
	echo "                      <input type='radio' name='strength' id='comment_1' value='1' $neg_checked>Comment is negative\n";
	echo "                   </label>\n";
	echo "               </td>\n";
	echo "               <td><b>Comment Number</b><br>\n";
	echo "                   <i>This must be a unique number</i><br>\n";
	echo "                   <input type='text' name='number' value='{$_POST['number']}' size=5><br><br><br>\n";
	echo "               </td>\n";
	echo "            </tr>\n";
	echo "            <tr><td colspan='2'>&nbsp;</td></tr>\n";
	echo "            <tr><td colspan='2'><b>Allowed subject types</b>&nbsp;&nbsp; <i>If none are selected, all will be allowed</i></td></tr>\n";
	echo "            <tr>\n";
	
	/* Get list of subjects for student and store in option list */
	echo "               <td>\n";
	echo "                  <select name='removesubjecttype[]' style='width: 200px;' multiple size=8>\n";
	if (isset($values)) {
		foreach ( $values as $subject_type ) {
			$subject_type = intval($subject_type);
			$query = "SELECT Title FROM subjecttype " .
					 "WHERE SubjectTypeIndex = $subject_type";
			$res = &  $db->query($query);
			if (DB::isError($res))
				die($res->getDebugInfo());
			
			if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				echo "                     <option value='{$subject_type}'>{$row['Title']}\n";
				$hidden_val .= "{$subject_type},";
			}
		}
	}
	echo "                  </select>\n";
	echo "               </td>\n";
	echo "               <td>\n";
	$query = "SELECT subjecttype.Title, subjecttype.SubjectTypeIndex FROM subjecttype " .
			 "ORDER BY subjecttype.Title, subjecttype.SubjectTypeIndex";
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	
	echo "                  <select name='addsubjecttype[]' style='width: 200px;' multiple size=8>\n";
	while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
		if (! in_array($row['SubjectTypeIndex'], $values)) {
			echo "                     <option value='{$row['SubjectTypeIndex']}'>{$row['Title']}\n";
		}
	}
	echo "                  </select>\n";
	echo "               </td>\n";
	echo "            </tr>\n";
	echo "            <tr>\n";
	echo "               <td align='center'><input type='submit' name='action' value='>' /></td>\n";
	echo "               <td align='center'><input type='submit' name='action' value='<' /></td>\n";
	echo "            </tr>\n";
	echo "         </table>\n"; // End of table
	echo "         <p align='center'>\n";
	echo "            <input type='submit' name='action' value='Save' />\n";
	echo "            <input type='submit' name='action' value='Cancel' />\n";
	echo "            <input type='hidden' name='value'  value='$hidden_val' />\n";
	echo "         </p>\n";
	echo "      </form>\n";
} else { // User isn't authorized to view or change scores.
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";
?>