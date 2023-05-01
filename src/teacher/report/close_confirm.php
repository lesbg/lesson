<?php
/**
 * ***************************************************************
 * teacher/report/close_confirm.php (c) 2008, 2018 Jonathan Dieter
 *
 * Confirm that teacher is finished with reports
 * ***************************************************************
 */

/* Get variables */

$student_name = "";
$student_username = "";

/* Get variables */
$subject_index = dbfuncInt2String($_GET['key']);
$subject = dbfuncInt2String($_GET['keyname']);
if (isset($_GET['key2'])) {
    $student_username = dbfuncInt2String($_GET['key2']);
    $student_name = dbfuncInt2String($_GET['keyname2']);
}

$title = "LESSON - Confirm";
$noJS = true;
$noHeaderLinks = true;

include "header.php";

$is_principal = check_principal($username);
$is_hod = check_hod_subject($username, $subject_index);
$is_teacher = check_teacher_subject($username, $subject_index);
$is_ct = false;
if(isset($student_username) and $student_username != "")
    $is_ct = check_class_teacher_student($username, $student_username, $yearindex, $termindex);

if (!$is_ct and !$is_teacher and !$is_admin and !$is_hod and !$is_principal) {
    log_event($LOG_LEVEL_ERROR, "teacher/report/close_confirm.php",
            $LOG_DENIED_ACCESS, "Tried to close reports for $subject.");
    echo "      <p>You do not have the authority to close these reports.  <a href='$nextLink'>" .
         "Click here to continue</a>.</p>\n";
}

$link = "index.php?location=" .
         dbfuncString2Int("teacher/report/close_action.php") . "&amp;key=" .
         $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] . "&amp;next=" .
         $_GET['next'];
if (isset($_GET['key2'])) {
    $link .= "&amp;key2=" . $_GET['key2'] . "&amp;keyname2=" . $_GET['keyname2'];
}

echo "      <p align='center'>Are you <b>sure</b> you are finished working on your reports for $subject</p>\n";
echo "      <form action='$link' method='post'>\n";
echo "         <p align='center'>";
echo "            <input type='submit' name='action' value='Yes, I&#039;m finished' \>&nbsp; \n";
echo "            <input type='submit' name='action' value='No, I changed my mind' \>&nbsp; \n";
echo "         </p>";
echo "      </form>\n";

include "footer.php";
?>
