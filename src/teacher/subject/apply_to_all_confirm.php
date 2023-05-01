<?php
/**
 * ***************************************************************
 * teacher/subject/apply_to_all_confirm.php (c) 2007 Jonathan Dieter
 *
 * Confirm application of subject options to *all* teacher's
 * subjects.
 * ***************************************************************
 */

/* Get variables */
$title = "LESSON - Confirm to apply options to all subjects";
$noJS = true;
$noHeaderLinks = true;

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
if ($is_teacher or $is_admin) {
	$res = &  $db->query(
					"SELECT SubjectTypeIndex, CanModify, " .
						 "TeacherCanChangeCategories FROM subject " .
						 "WHERE SubjectIndex = $subjectindex");
	if (DB::isError($res))
		die($res->getDebugInfo());
	$row = & $res->fetchRow(DB_FETCHMODE_ASSOC);
	if ($row['TeacherCanChangeCategories'] == 1) {
		$teacher_can_modify = True;
	} else {
		$teacher_can_modify = False;
	}
	
	$link = "index.php?location=" .
			 dbfuncString2Int("teacher/subject/apply_to_all.php") . "&amp;key=" .
			 $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] . "&amp;next=" .
			 $_GET['next'];
	
	if ($is_teacher) {
		echo "      <p align=\"center\">Are you <strong>sure</strong> you want to apply {$subject}'s options to <strong>all</strong> of your subjects?</p>\n";
	} else {
		echo "      <p align=\"center\">Are you <strong>sure</strong> you want to apply {$subject}'s options to <strong>all</strong> of the teacher's subjects?</p>\n";
	}
	if ($teacher_can_modify or $is_admin) {
		echo "      <p align=\"center\"><strong>WARNING: All the other subjects' categories will be replaced with {$subject}'s categories.</strong></p>\n";
	}
	echo "      <form action=\"$link\" method=\"post\">\n";
	echo "         <p align=\"center\">";
	echo "            <input type=\"submit\" name=\"action\" value=\"Yes, apply options\" \>&nbsp; \n";
	echo "            <input type=\"submit\" name=\"action\" value=\"No, I changed my mind\" \>&nbsp; \n";
	echo "         </p>";
	echo "      </form>\n";
} else {
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";
?>