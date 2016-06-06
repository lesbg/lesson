<?php
/**
 * ***************************************************************
 * admin/proofreader/modify.php (c) 2008 Jonathan Dieter
 *
 * List all teacher as choices for proofreader
 * ***************************************************************
 */
if (! isset($_GET['next']))
	$_GET['next'] = dbfuncString2Int($backLink);
$choice_depindex = safe(dbfuncInt2String($_GET['key']));
$choice_department = dbfuncInt2String($_GET['keyname']);
$proof_username = safe(dbfuncInt2String($_GET['key2']));

$link = "index.php?location=" .
		 dbfuncString2Int("admin/proofreader/modify_action.php") . "&amp;key=" .
		 $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] . "&amp;next=" .
		 $_GET['next'];
$title = "Choose teacher to be proofreader for $choice_department department";
include "header.php"; // Show header

if ($is_admin or $is_principal) {
	include "core/settermandyear.php";
	$showyear = false;
	$showterm = false;
	include "core/titletermyear.php";
	
	/* Get teacher list */
	$query =	"SELECT user.FirstName, user.Surname, user.Username FROM " .
				"       user INNER JOIN groupgenmem ON (user.Username=groupgenmem.Username) " .
				"            INNER JOIN groups USING (GroupID) " .
				"WHERE user.DepartmentIndex = $depindex " .
				"AND   groups.GroupTypeID='activeteacher' " .
				"AND   groups.YearIndex=$yearindex " .
				"ORDER BY user.Username";
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
		
	/* Print all teachers and the subjects they teach */
	if ($res->numRows() > 0) {
		echo "      <form action='$link' method='post'>\n";
		echo "      <p align='center'>";
		echo "         <input type='submit' name='action' value='Ok' \>&nbsp; \n";
		echo "         <input type='submit' name='action' value='Cancel' \>&nbsp; \n";
		echo "      </p>";
		
		echo "      <table align='center' border='1'>\n";
		echo "         <tr>\n";
		echo "            <th>Teacher</th>\n";
		echo "         </tr>\n";
		
		$alt_count = 1;
		echo "         <tr class='std'>\n";
		
		$default = "";
		if ($proof_username == "")
			$default = "checked";
		
		echo "            <td><label for='teachernone' id='lbl_teachernone'><input type='radio' name='teacher' value='!none' id='teachernone' $default><i>None</i></label></td>\n";
		echo "         </tr>\n";
		
		/* For each teacher, print a row with the teacher's name */
		while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
			$alt_count += 1;
			if ($alt_count % 2 == 0) {
				$alt = " class='alt'";
			} else {
				$alt = " class='std'";
			}
			echo "         <tr$alt>\n";
			
			$default = "";
			if ($row['Username'] == $proof_username)
				$default = "checked";
				/* Generate view and edit buttons */
			echo "            <td><label for='teacher_{$row['Username']}' id='lbl_teacher_{$row['Username']}'><input type='radio' name='teacher' value='{$row['Username']}' id='teacher_{$row['Username']}' $default> {$row['Title']} {$row['FirstName']} {$row['Surname']} ({$row['Username']})</label></td>\n";
			echo "         </tr>\n";
		}
		echo "      </table>\n"; // End of table
		echo "      <p align='center'>";
		echo "         <input type='submit' name='action' value='Ok' \>&nbsp; \n";
		echo "         <input type='submit' name='action' value='Cancel' \>&nbsp; \n";
		echo "      </p>";
		echo "      </form>\n";
	} else {
		echo "      <p>There are no active teachers</p>\n";
	}
} else {
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";
?>