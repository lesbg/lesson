<?php
/**
 * ***************************************************************
 * admin/class_term/modify.php (c) 2008 Jonathan Dieter
 *
 * Change class report options
 * ***************************************************************
 */

/* Get variables */
if (! isset($_GET['next']))
	$_GET['next'] = dbfuncString2Int($backLink);
$classname = dbfuncInt2String($_GET['keyname']);
$title = "Change report options for $classname";
$classindex = safe(dbfuncInt2String($_GET['key']));
$termindex = safe(dbfuncInt2String($_GET['key2']));
$link = "index.php?location=" .
		 dbfuncString2Int("admin/class_term/modify_action.php") . "&amp;key=" .
		 $_GET['key'] . "&amp;key2=" . $_GET['key2'] . "&amp;keyname=" .
		 $_GET['keyname'] . "&amp;next=" . $_GET['next'];

include "header.php"; // Show header

/* Check whether user is authorized to change subject */
if (! $is_admin) {
	log_event($LOG_LEVEL_ERROR, "admin/class_term/modify.php", 
			$LOG_DENIED_ACCESS, 
			"Attempted to change report options for $classname.");
	
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
	
	include "footer.php";
	exit(0);
}

$query = "SELECT classterm.AverageType, classterm.ClassTermIndex, " .
		 "       classterm.EffortType, classterm.ConductType, " .
		 "       classterm.AbsenceType, " .
		 "       classterm.CTCommentType, classterm.HODCommentType, " .
		 "       classterm.PrincipalCommentType, classterm.CanDoReport, " .
		 "       classterm.ReportTemplateType " .
		 "       FROM class LEFT OUTER JOIN classterm ON " .
		 "            (class.ClassIndex          = $classindex " .
		 "             AND classterm.ClassIndex = $classindex " .
		 "             AND classterm.TermIndex  = $termindex) " .
		 "WHERE class.ClassIndex = $classindex ";
$res = & $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query
if (! $row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
	echo "      <p align='center'>Can't find subject.  Have you deleted it?</p>\n";
	echo "      <p align='center'><a href='$backLink'>Click here to go back</a></p>\n";
	include "footer.php";
	exit(0);
}

if (isset($errorlist))
	echo $errorlist;

if (isset($_POST['report_enabled'])) {
	$_POST['report_enabled'] = intval($_POST['report_enabled']);
	if ($_POST['report_enabled'] > 1)
		$_POST['report_enabled'] = 0;
} else {
	$_POST['report_enabled'] = intval($row['CanDoReport']);
}

if (isset($_POST['ct_comment_type'])) {
	$_POST['ct_comment_type'] = intval($_POST['ct_comment_type']);
	if ($_POST['ct_comment_type'] >= $COMMENT_TYPE_MAX)
		$_POST['ct_comment_type'] = $COMMENT_TYPE_NONE;
} else {
	$_POST['ct_comment_type'] = intval($row['CTCommentType']);
}

if (isset($_POST['hod_comment_type'])) {
	$_POST['hod_comment_type'] = intval($_POST['hod_comment_type']);
	if ($_POST['hod_comment_type'] >= $COMMENT_TYPE_MAX)
		$_POST['hod_comment_type'] = $COMMENT_TYPE_NONE;
} else {
	$_POST['hod_comment_type'] = intval($row['HODCommentType']);
}

if (isset($_POST['pr_comment_type'])) {
	$_POST['pr_comment_type'] = intval($_POST['pr_comment_type']);
	if ($_POST['pr_comment_type'] >= $COMMENT_TYPE_MAX)
		$_POST['pr_comment_type'] = $COMMENT_TYPE_NONE;
} else {
	$_POST['pr_comment_type'] = intval($row['PrincipalCommentType']);
}

if (isset($_POST['conduct_type'])) {
	$_POST['conduct_type'] = intval($_POST['conduct_type']);
	if ($_POST['conduct_type'] >= $CLASS_CONDUCT_TYPE_MAX)
		$_POST['conduct_type'] = $CLASS_CONDUCT_TYPE_NONE;
} else {
	$_POST['conduct_type'] = intval($row['ConductType']);
}

if (isset($_POST['effort_type'])) {
	$_POST['effort_type'] = intval($_POST['effort_type']);
	if ($_POST['effort_type'] >= $CLASS_EFFORT_TYPE_MAX)
		$_POST['effort_type'] = $CLASS_EFFORT_TYPE_NONE;
} else {
	$_POST['effort_type'] = intval($row['EffortType']);
}

if (isset($_POST['average_type'])) {
	$_POST['average_type'] = intval($_POST['average_type']);
	if ($_POST['average_type'] >= $CLASS_AVG_TYPE_MAX)
		$_POST['average_type'] = $CLASS_AVG_TYPE_NONE;
} else {
	$_POST['average_type'] = intval($row['AverageType']);
}

if (isset($_POST['absence_type'])) {
	$_POST['absence_type'] = intval($_POST['absence_type']);
	if ($_POST['absence_type'] >= $ABSENCE_TYPE_MAX)
		$_POST['absence_type'] = $ABSENCE_TYPE_NONE;
} else {
	$_POST['absence_type'] = intval($row['AbsenceType']);
}

if (is_null($row['ReportTemplateType'])) {
	$report_type = "<i>None</i>";
} elseif ($row['ReportTemplateType'] == "application/vnd.oasis.opendocument.text") {
	$nlink = "index.php?location=" .
			 dbfuncString2Int("admin/class_term/show.php") . "&amp;key=" .
			 dbfuncString2Int($row['ClassTermIndex']);
	$report_type = "<a href='$nlink'>OpenDocument Text</a>";
	print $row['ClassTermIndex'];
} else {
	$report_type = "<b>Unknown</b>";
}
echo "      <form action='$link' enctype='multipart/form-data' name='modSubj' method='post'>\n";
echo "         <table align='center' border='1'>\n"; // Table headers
/* Average type for subject */
echo "            <tr class='std'>\n";
echo "               <td>Marks</td>\n";
if ($_POST['average_type'] == $CLASS_AVG_TYPE_NONE) {
	$anul_checked = "checked";
} else {
	$anul_checked = "";
}
if ($_POST['average_type'] == $CLASS_AVG_TYPE_PERCENT) {
	$aper_checked = "checked";
} else {
	$aper_checked = "";
}
if ($_POST['average_type'] == $CLASS_AVG_TYPE_INDEX) {
	$aind_checked = "checked";
} else {
	$aind_checked = "";
}
if ($_POST['average_type'] == $CLASS_AVG_TYPE_CALC) {
	$aclc_checked = "checked";
} else {
	$aclc_checked = "";
}
if ($_POST['average_type'] == $CLASS_AVG_TYPE_GRADE) {
	$agrd_checked = "checked";
} else {
	$agrd_checked = "";
}
echo "               <td>\n";
echo "                   <label for='average_none'>\n";
echo "                      <input type='radio' name='average_type' id='average_none' value='$CLASS_AVG_TYPE_NONE' $anul_checked>No total mark for students\n";
echo "                   </label><br>\n";
echo "                   <label for='average_percent'>\n";
echo "                      <input type='radio' name='average_type' id='average_percent' value='$CLASS_AVG_TYPE_PERCENT' $aper_checked>Student mark is a percentage\n";
echo "                   </label><br>\n";
echo "                   <label for='average_index'>\n";
echo "                      <input type='radio' name='average_type' id='average_index' value='$CLASS_AVG_TYPE_INDEX' $aind_checked>Student mark is non-numeric\n";
echo "                   </label><br>\n";
echo "                   <label for='average_calc'>\n";
echo "                      <input type='radio' name='average_type' id='average_calc' value='$CLASS_AVG_TYPE_CALC' $aclc_checked>Student mark is calculated from subject marks\n";
echo "                   </label><br>\n";
echo "                   <label for='average_grade'>\n";
echo "                      <input type='radio' name='average_type' id='average_grade' value='$CLASS_AVG_TYPE_GRADE' $agrd_checked>Student mark is a grade given based on subject marks\n";
echo "                   </label><br>\n";
echo "               </td>\n";
echo "            </tr>\n";

/* Conduct type for subject */
echo "            <tr class='alt'>\n";
echo "               <td>Conduct</td>\n";
if ($_POST['conduct_type'] == $CLASS_CONDUCT_TYPE_NONE) {
	$cnul_checked = "checked";
} else {
	$cnul_checked = "";
}
if ($_POST['conduct_type'] == $CLASS_CONDUCT_TYPE_PERCENT) {
	$cper_checked = "checked";
} else {
	$cper_checked = "";
}
if ($_POST['conduct_type'] == $CLASS_CONDUCT_TYPE_INDEX) {
	$cind_checked = "checked";
} else {
	$cind_checked = "";
}
if ($_POST['conduct_type'] == $CLASS_CONDUCT_TYPE_CALC) {
	$cclc_checked = "checked";
} else {
	$cclc_checked = "";
}
if ($_POST['conduct_type'] == $CLASS_CONDUCT_TYPE_PUN) {
	$cpun_checked = "checked";
} else {
	$cpun_checked = "";
}
echo "               <td>\n";
echo "                   <label for='conduct_none'>\n";
echo "                      <input type='radio' name='conduct_type' id='conduct_none' value='$CLASS_CONDUCT_TYPE_NONE' $cnul_checked>No total conduct mark for students\n";
echo "                   </label><br>\n";
echo "                   <label for='conduct_percent'>\n";
echo "                      <input type='radio' name='conduct_type' id='conduct_percent' value='$CLASS_CONDUCT_TYPE_PERCENT' $cper_checked>Conduct mark is a percentage\n";
echo "                   </label><br>\n";
echo "                   <label for='conduct_index'>\n";
echo "                      <input type='radio' name='conduct_type' id='conduct_index' value='$CLASS_CONDUCT_TYPE_INDEX' $cind_checked>Conduct mark is non-numeric\n";
echo "                   </label><br>\n";
echo "                   <label for='conduct_calc'>\n";
echo "                      <input type='radio' name='conduct_type' id='conduct_calc' value='$CLASS_CONDUCT_TYPE_CALC' $cclc_checked>Conduct mark is calculated from subject conduct marks\n";
echo "                   </label><br>\n";
echo "                   <label for='conduct_pun'>\n";
echo "                      <input type='radio' name='conduct_type' id='conduct_pun' value='$CLASS_CONDUCT_TYPE_PUN' $cpun_checked>Conduct mark is calculated from punishments\n";
echo "                   </label><br>\n";
echo "               </td>\n";
echo "            </tr>\n";

/* Effort type for subject */
echo "            <tr class='std'>\n";
echo "               <td>Effort</td>\n";
if ($_POST['effort_type'] == $CLASS_EFFORT_TYPE_NONE) {
	$enul_checked = "checked";
} else {
	$enul_checked = "";
}
if ($_POST['effort_type'] == $CLASS_EFFORT_TYPE_PERCENT) {
	$eper_checked = "checked";
} else {
	$eper_checked = "";
}
if ($_POST['effort_type'] == $CLASS_EFFORT_TYPE_INDEX) {
	$eind_checked = "checked";
} else {
	$eind_checked = "";
}
if ($_POST['effort_type'] == $CLASS_EFFORT_TYPE_CALC) {
	$eclc_checked = "checked";
} else {
	$eclc_checked = "";
}

echo "               <td>\n";
echo "                   <label for='effort_none'>\n";
echo "                      <input type='radio' name='effort_type' id='effort_none' value='$CLASS_EFFORT_TYPE_NONE' $enul_checked>No total effort mark for students\n";
echo "                   </label><br>\n";
echo "                   <label for='effort_percent'>\n";
echo "                      <input type='radio' name='effort_type' id='effort_percent' value='$CLASS_EFFORT_TYPE_PERCENT' $eper_checked>Effort mark is a percentage\n";
echo "                   </label><br>\n";
echo "                   <label for='effort_index'>\n";
echo "                      <input type='radio' name='effort_type' id='effort_index' value='$CLASS_EFFORT_TYPE_INDEX' $eind_checked>Effort mark is non-numeric\n";
echo "                   </label><br>\n";
echo "                   <label for='effort_calc'>\n";
echo "                      <input type='radio' name='effort_type' id='effort_calc' value='$CLASS_EFFORT_TYPE_CALC' $eclc_checked>Effort mark is calculated from subject effort marks\n";
echo "                   </label><br>\n";
echo "               </td>\n";
echo "            </tr>\n";

/* Absence type for class */
echo "            <tr class='alt'>\n";
echo "               <td>Absence</td>\n";
if ($_POST['absence_type'] == $ABSENCE_TYPE_NONE) {
	$bnul_checked = "checked";
} else {
	$bnul_checked = "";
}
if ($_POST['absence_type'] == $ABSENCE_TYPE_NUM) {
	$bper_checked = "checked";
} else {
	$bper_checked = "";
}
if ($_POST['absence_type'] == $ABSENCE_TYPE_CALC) {
	$bclc_checked = "checked";
} else {
	$bclc_checked = "";
}

echo "               <td>\n";
echo "                   <label for='absence_none'>\n";
echo "                      <input type='radio' name='absence_type' id='absence_none' value='$ABSENCE_TYPE_NONE' $bnul_checked>No tracking of absences for students\n";
echo "                   </label><br>\n";
echo "                   <label for='absence_percent'>\n";
echo "                      <input type='radio' name='absence_type' id='absence_percent' value='$ABSENCE_TYPE_NUM' $bper_checked>Absence mark is a value\n";
echo "                   </label><br>\n";
echo "                   <label for='absence_calc'>\n";
echo "                      <input type='radio' name='absence_type' id='absence_calc' value='$ABSENCE_TYPE_CALC' $bclc_checked>Absence mark is derived from attendance\n";
echo "                   </label><br>\n";
echo "               </td>\n";
echo "            </tr>\n";

/* Class teacher comment for class */
echo "            <tr class='std'>\n";
echo "               <td>Class<br>Teacher<br>Comment</td>\n";
if ($_POST['ct_comment_type'] == $COMMENT_TYPE_NONE) {
	$ct_com_nul_checked = "checked";
} else {
	$ct_com_nul_checked = "";
}
if ($_POST['ct_comment_type'] == $COMMENT_TYPE_OPTIONAL) {
	$ct_com_opt_checked = "checked";
} else {
	$ct_com_opt_checked = "";
}
if ($_POST['ct_comment_type'] == $COMMENT_TYPE_MANDATORY) {
	$ct_com_man_checked = "checked";
} else {
	$ct_com_man_checked = "";
}

echo "               <td>\n";
echo "                   <label for='ct_comment_none'>\n";
echo "                      <input type='radio' name='ct_comment_type' id='ct_comment_none' value='$COMMENT_TYPE_NONE' $ct_com_nul_checked>No class teacher comment\n";
echo "                   </label><br>\n";
echo "                   <label for='ct_comment_opt'>\n";
echo "                      <input type='radio' name='ct_comment_type' id='ct_comment_opt' value='$COMMENT_TYPE_OPTIONAL' $ct_com_opt_checked>Optional class teacher comment\n";
echo "                   </label><br>\n";
echo "                   <label for='ct_comment_man'>\n";
echo "                      <input type='radio' name='ct_comment_type' id='ct_comment_man' value='$COMMENT_TYPE_MANDATORY' $ct_com_man_checked>Mandatory class teacher comment\n";
echo "                   </label><br>\n";
echo "               </td>\n";
echo "            </tr>\n";

/* HOD comment for class */
echo "            <tr class='alt'>\n";
echo "               <td>Head of<br>Department<br>Comment</td>\n";
if ($_POST['hod_comment_type'] == $COMMENT_TYPE_NONE) {
	$hod_com_nul_checked = "checked";
} else {
	$hod_com_nul_checked = "";
}
if ($_POST['hod_comment_type'] == $COMMENT_TYPE_OPTIONAL) {
	$hod_com_opt_checked = "checked";
} else {
	$hod_com_opt_checked = "";
}
if ($_POST['hod_comment_type'] == $COMMENT_TYPE_MANDATORY) {
	$hod_com_man_checked = "checked";
} else {
	$hod_com_man_checked = "";
}

echo "               <td>\n";
echo "                   <label for='hod_comment_none'>\n";
echo "                      <input type='radio' name='hod_comment_type' id='hod_comment_none' value='$COMMENT_TYPE_NONE' $hod_com_nul_checked>No head of department comment\n";
echo "                   </label><br>\n";
echo "                   <label for='hod_comment_opt'>\n";
echo "                      <input type='radio' name='hod_comment_type' id='hod_comment_opt' value='$COMMENT_TYPE_OPTIONAL' $hod_com_opt_checked>Optional head of department comment\n";
echo "                   </label><br>\n";
echo "                   <label for='hod_comment_man'>\n";
echo "                      <input type='radio' name='hod_comment_type' id='hod_comment_man' value='$COMMENT_TYPE_MANDATORY' $hod_com_man_checked>Mandatory head of department comment\n";
echo "                   </label><br>\n";
echo "               </td>\n";
echo "            </tr>\n";

/* Principal comment for class */
echo "            <tr class='std'>\n";
echo "               <td>Principal<br>Comment</td>\n";
if ($_POST['pr_comment_type'] == $COMMENT_TYPE_NONE) {
	$pr_com_nul_checked = "checked";
} else {
	$pr_com_nul_checked = "";
}
if ($_POST['pr_comment_type'] == $COMMENT_TYPE_OPTIONAL) {
	$pr_com_opt_checked = "checked";
} else {
	$pr_com_opt_checked = "";
}
if ($_POST['pr_comment_type'] == $COMMENT_TYPE_MANDATORY) {
	$pr_com_man_checked = "checked";
} else {
	$pr_com_man_checked = "";
}

echo "               <td>\n";
echo "                   <label for='pr_comment_none'>\n";
echo "                      <input type='radio' name='pr_comment_type' id='pr_comment_none' value='$COMMENT_TYPE_NONE' $pr_com_nul_checked>No principal comment\n";
echo "                   </label><br>\n";
echo "                   <label for='pr_comment_opt'>\n";
echo "                      <input type='radio' name='pr_comment_type' id='pr_comment_opt' value='$COMMENT_TYPE_OPTIONAL' $pr_com_opt_checked>Optional principal comment\n";
echo "                   </label><br>\n";
echo "                   <label for='pr_comment_man'>\n";
echo "                      <input type='radio' name='pr_comment_type' id='pr_comment_man' value='$COMMENT_TYPE_MANDATORY' $pr_com_man_checked>Mandatory principal comment\n";
echo "                   </label><br>\n";
echo "               </td>\n";
echo "            </tr>\n";

/* Reports enabled for class */
echo "            <tr class='alt'>\n";
echo "               <td>Reports</td>\n";
if ($_POST['report_enabled'] == 1) {
	$rpt_enabled_checked = "checked";
	$rpt_disabled_checked = "";
} else {
	$rpt_enabled_checked = "";
	$rpt_disabled_checked = "checked";
}
echo "               <td>\n";
echo "                   <label for='rpt_enabled_true'>\n";
echo "                      <input type='radio' name='report_enabled' id='rpt_enabled_true' value='1' $rpt_enabled_checked>Allow editing of reports\n";
echo "                   </label><br>\n";
echo "                   <label for='rpt_enabled_false'>\n";
echo "                      <input type='radio' name='report_enabled' id='rpt_enabled_false' value='0' $rpt_disabled_checked>Disable editing of reports\n";
echo "                   </label><br>\n";
echo "               </td>\n";
echo "            </tr>\n";

/* Report template */
echo "            <tr class='alt'>\n";
echo "               <td>Report template</td>\n";
echo "               <td>\n";
echo "                   <input name='report_template' type='file'><input type='hidden' name='MAX_FILE_SIZE' value='100000000'><input type='submit' name='action' value='Upload'><br>\n";
echo "                   Current template type: $report_type\n";
echo "               </td>\n";
echo "            </tr>\n";

echo "         </table>\n"; // End of table
echo "         <p align='center'>\n";
echo "            <input type='submit' name='action' value='Update' \>\n";
echo "            <input type='submit' name='action' value='Cancel' \>\n";
echo "         </p>\n";
echo "      </form>\n";

log_event($LOG_LEVEL_EVERYTHING, "admin/class_term/modify.php", $LOG_ADMIN, 
		"Opened $title for editing.");

include "footer.php";
?>