<?php
/**
 * ***************************************************************
 * teacher/assignment/new_agenda.php (c) 2010 Jonathan Dieter
 *
 * Show fields to fill in for new agenda
 * ***************************************************************
 */

/* Get variables */
$title = "New Agenda Item";
$subtitle = dbfuncInt2String($_GET['keyname']);
$subjectindex = safe(dbfuncInt2String($_GET['key']));
$link = "index.php?location=" .
         dbfuncString2Int("teacher/assignment/new_or_modify_action.php") .
         "&amp;key=" . $_GET['key'] . "&amp;next=" .
         dbfuncString2Int($backLink);
$use_extra_css = true;
$extra_js = "assignment.js";

include "core/settermandyear.php";
include "header.php"; // Show header

/* Check whether user is authorized to change scores */
$res = & $db->query(
                "SELECT subjectteacher.Username FROM subjectteacher " .
                 "WHERE subjectteacher.SubjectIndex = $subjectindex " .
                 "AND   subjectteacher.Username     = '$username'");
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0 or $is_admin) {
    log_event($LOG_LEVEL_EVERYTHING, "teacher/assignment/new.php", $LOG_TEACHER,
            "Starting new agenda item for $subtitle.");

    $dateinfo = date($dateformat); // Print assignment information table with empty fields to fill in
    $duedateinfo = date($dateformat, time() + (24 * 60 * 60)); // Print assignment information table with empty fields to fill in
    echo "      <script language='JavaScript' type='text/javascript'>\n";
    echo "         window.onload = check_style;\n";
    echo "      </script>\n";
    echo "      <form action='$link' enctype='multipart/form-data' method='post' name='agenda'>\n"; // Form method
    echo "         <input type='hidden' id='agenda' name='agenda' value='1'>\n";
    echo "         <table class='transparent' align='center'>\n";
    echo "            <tr>\n";
    echo "               <td>Title:</td>\n";
    echo "               <td colspan='2'><input type='text' name='title' " .
         "id='title' tabindex='1' size='50'></td>\n";
    echo "            </tr>\n";
    echo "            <tr>\n";
    echo "               <td>Date:</td>\n";
    echo "               <td colspan='2'><input type='text' name='date' value='{$dateinfo}' " .
         "tabindex='2' size='50'></td>\n";
    echo "            </tr>\n";
    echo "            <tr>\n";
    echo "               <td>Due Date:</td>\n";
    echo "               <td colspan='2'><input type='text' name='duedate' value='{$duedateinfo}' " .
         "tabindex='3' size='50'></td>\n";
    echo "            </tr>\n";
    echo "            <tr>\n";
    echo "               <td>Agenda Options:</td>\n";
    echo "               <td colspan='2'><input type='checkbox' name='hidden' id='hidden' tabindex='6' onchange='check_style();'> " .
         "<label for='hidden'>Hidden</label><br>\n";
    echo "                  <input type='checkbox' name='uploadable' id='uploadable' tabindex='7'> " .
         "<label for='uploadable'>Allow students to upload files so you can access them</label></td>\n";
    echo "            </tr>\n";
    echo "            <tr>\n";
    echo "               <td>Description:</td>\n";
    echo "               <td colspan='2'>\n";
    echo "                  <input type='radio' name='descr_type' id='descr_type0' value='0' tabindex='9' onChange='descr_check();' checked>\n";
    echo "                  <textarea style='vertical-align: top' rows='10' cols='50' id='descr' name='descr' tabindex='10'></textarea><br>\n";
    echo "                  <input type='radio' name='descr_type' id='descr_type1' value='1' onChange='descr_check();' tabindex='11'>\n";
    echo "                  <input type='file' name='descr_upload' id='descr_upload' tabindex='12' accept='application/pdf'><input type='hidden' name='MAX_FILE_SIZE' value='10240000'><br>\n";
    echo "                  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Current file: <i>None</i>\n";
    echo "               </td>\n";
    echo "            </tr>\n";
    echo "         </table>\n";
    echo "         <p align='center'>\n";
    echo "            <input type='submit' name='action' value='Save' tabindex='18' \>&nbsp; \n";
    echo "            <input type='submit' name='action' value='Cancel' tabindex='19' \>&nbsp; \n";
    echo "         </p>\n";
    echo "         <p></p>\n";
    echo "      </form>";
} else { // User isn't authorized to view or change scores.
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "teacher/assignment/new_agenda.php",
            $LOG_DENIED_ACCESS, "Tried to create new agenda item for $subtitle.");

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";
?>
