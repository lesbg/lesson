<?php
/**
 * ***************************************************************
 * teacher/assignment/new.php (c) 2004-2008 Jonathan Dieter
 *
 * Show fields to fill in for new assignment
 * ***************************************************************
 */

/* Get variables */
$title = "New Assignment";
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
            "Starting new assignment for $subtitle.");

    $query = "SELECT subject.AverageType, subject.AverageTypeIndex " .
             "       FROM subject " .
             "WHERE subject.SubjectIndex = $subjectindex";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query
    $row = & $res->fetchRow(DB_FETCHMODE_ASSOC);

    $average_type = $row['AverageType'];
    $average_type_index = $row['AverageTypeIndex'];

    if ($average_type == $AVG_TYPE_INDEX and ! is_null($average_type_index)) {
        $query = "SELECT Input, Display FROM nonmark_index " .
             "WHERE  NonmarkTypeIndex=$average_type_index ";
        $sres = &  $db->query($query);
        if (DB::isError($sres))
            die($res->getDebugInfo()); // Check for errors in query

        if ($srow = & $sres->fetchRow(DB_FETCHMODE_ASSOC)) {
            $input = strtoupper($srow['Input']);
            $ainput_array = "'{$srow['Input']}'";
            $adisplay_array = "'{$srow['Display']}'";
            while ( $srow = & $sres->fetchRow(DB_FETCHMODE_ASSOC) ) {
                $input = strtoupper($srow['Input']);
                $ainput_array .= ", '$input'";
                $adisplay_array .= ", '{$srow['Display']}'";
            }
        }
    } else {
        $ainput_array = "";
        $adisplay_array = "";
    }

    $res = &  $db->query(
                    "SELECT user.FirstName, user.Surname, user.Username, query.ClassOrder FROM user, " .
                     "       subjectstudent LEFT OUTER JOIN " .
                     "       (SELECT classlist.ClassOrder, classlist.Username FROM class, " .
                     "               classlist, classterm, subject " .
                     "        WHERE classlist.ClassTermIndex = classterm.ClassTermIndex " .
                     "        AND   classterm.TermIndex = subject.TermIndex " .
                     "        AND   class.ClassIndex = classterm.ClassIndex " .
                     "        AND   class.YearIndex = subject.YearIndex " .
                     "        AND subject.SubjectIndex=$subjectindex) AS query " .
                     "       ON subjectstudent.Username = query.Username " .
                     "WHERE user.Username=subjectstudent.Username " .
                     "AND subjectstudent.SubjectIndex=$subjectindex " .
                     "ORDER BY user.FirstName, user.Surname, user.Username");
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    echo "      <script language='JavaScript' type='text/javascript'>\n";
    echo "         window.onload = recalc_all;\n";
    echo "\n";
    echo "         var AVERAGE_TYPE_NONE      = $AVG_TYPE_NONE;\n";
    echo "         var AVERAGE_TYPE_PERCENT   = $AVG_TYPE_PERCENT;\n";
    echo "         var AVERAGE_TYPE_INDEX     = $AVG_TYPE_INDEX;\n";
    echo "         var AVERAGE_TYPE_GRADE     = $AVG_TYPE_GRADE;\n";
    echo "\n";
    echo "         var average_type           = $average_type;\n";
    if ($average_type == $AVG_TYPE_INDEX) {
        echo "         var average_input_array    = new Array($ainput_array);\n";
        echo "         var average_display_array  = new Array($adisplay_array);\n";
    }
    echo "\n";
    echo "      </script>\n";

    $dateinfo = date($dateformat); // Print assignment information table with empty fields to fill in
    $duedateinfo = date($dateformat, time() + (24 * 60 * 60)); // Print assignment information table with empty fields to fill in
    echo "      <form action='$link' enctype='multipart/form-data' method='post' name='assignment'>\n"; // Form method
    echo "         <input type='hidden' id='agenda' name='agenda' value='0'>\n";
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
    if ($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) {
        echo "            <tr>\n";
        echo "               <td>Maximum score:</td>\n";
        echo "               <td colspan='2'><input type='text' name='max' id='max' onChange='recalc_all();' " .
             "tabindex='4' size='50'></td>\n";
        echo "            </tr>\n";
        echo "            <tr>\n";
        echo "               <td>Weight:</td>\n";
        echo "               <td colspan='2'><input type='text' name='weight' value='1' " .
             "tabindex='5' size='50'></td>\n";
        echo "            </tr>\n";
    }
    echo "            <tr>\n";
    echo "               <td>Assignment Options:</td>\n";
    echo "               <td colspan='2'><input type='checkbox' name='hidden' id='hidden' tabindex='6' onchange='check_style();'> " .
         "<label for='hidden'>Hidden</label><br>\n";
    echo "                  <input type='checkbox' name='uploadable' id='uploadable' tabindex='7'> " .
         "<label for='uploadable'>Allow students to upload files so you can access them</label></td>\n";
    echo "            </tr>\n";

    if ($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) {
        /* Get category info */
        $bsr = &  $db->query(
                        "SELECT category.CategoryName, categorylist.CategoryListIndex, " .
                             "       categorylist.Weight, " .
                             "       categorylist.TotalWeight FROM category, categorylist, subject " .
                             "WHERE subject.SubjectIndex        = $subjectindex " .
                             "AND   categorylist.SubjectIndex   = subject.SubjectIndex " .
                             "AND   category.CategoryIndex      = categorylist.CategoryIndex " .
                             "ORDER BY category.CategoryName");
        if (DB::isError($bsr))
            die($bsr->getDebugInfo()); // Check for errors in query
        if ($bsr->numRows() > 0) {
            echo "            <tr>\n";
            echo "               <td>Category:</td>\n";
            echo "               <td colspan='2'>\n";
            echo "                  <select name='category' tabindex='8'>\n";
            // echo " <option value='NULL' selected>(None)\n";
            while ( $bRow = & $bsr->fetchRow(DB_FETCHMODE_ASSOC) ) {
                $percentage = sprintf("%01.1f",
                                    ($bRow['Weight'] * 100) /
                                     $bRow['TotalWeight']);
                echo "                     <option value='{$bRow['CategoryListIndex']}'>" .
                     "{$bRow['CategoryName']} - {$percentage}%</option>\n";
            }
            echo "                  </select>\n";
            echo "               </td>\n";
            echo "            </tr>\n";
        }
    }
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
    if ($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) {
        echo "            <tr>\n";
        echo "               <td>Curve Type:</td>\n";
        echo "               <td>\n";
        echo "                  <input type='radio' name='curve_type' onChange='recalc_all();' id='curve_type0' " .
             "value='0' tabindex='13' checked><label for='curve_type0'>None</label><br>\n";
        echo "                  <input type='radio' name='curve_type' onChange='recalc_all();' id='curve_type1' " .
             "value='1' tabindex='14'><label for='curve_type1'>Maximum score is 100%</label><br>\n";
        echo "                  <input type='radio' name='curve_type' onChange='recalc_all();' id='curve_type2' " .
             "value='2' tabindex='15'><label for='curve_type2'>Distributed scoring</label>\n";
        echo "               </td>\n";
        echo "               <td>\n";
        echo "                  <label id='top_mark_label' for='top_mark'>Top mark: \n";
        echo "                  <input type='text' name='top_mark' id='top_mark' onChange='recalc_all();' " .
             "size='5' tabindex='16' onChange='recalc_all();'>%</label><br>\n";
        echo "                  <label id='bottom_mark_label' for='bottom_mark'>Bottom mark: \n";
        echo "                  <input type='text' name='bottom_mark' id='bottom_mark' onChange='recalc_all();' " .
             "size='5' tabindex='17' onChange='recalc_all();'>%</label><br>\n";
        echo "                  <label id='ignore_zero_label' for='ignore_zero'>";
        echo "                  <input type='checkbox' name='ignore_zero' id='ignore_zero' onChange='recalc_all();' " .
             "value='1' tabindex='18' onChange='recalc_all();'>Don't change zeroes</label><br>\n";
        echo "               </td>\n";
        echo "            </tr>\n";
    }
    echo "         </table>\n";
    echo "         <p align='center'>\n";
    echo "            <input type='submit' name='action' value='Save' tabindex='19' \>&nbsp; \n";
    echo "            <input type='submit' name='action' value='Cancel' tabindex='20' \>&nbsp; \n";
    echo "         </p>\n";
    echo "         <p></p>\n";

    /* Print out table in which to enter scores and comments */
    $tabC = 20;
    if ($res->numRows() > 0) {
        echo "         <table align='center' border='1'>\n"; // Table headers
        echo "            <tr>\n";
        echo "               <th>&nbsp;</th>\n";
        echo "               <th>Student</th>\n";
        if ($average_type != $AVG_TYPE_NONE) {
            echo "               <th>Score</th>\n";
        }
        echo "               <th>Comment</th>\n";
        echo "            </tr>\n";

        /* For each student, print a row with the student's name and location to enter score and comment */
        $alt_count = 0;
        if ($res->numRows() > 0) {
            $tabS = $tabC;
            $tabC = $tabC + $res->numRows();
        }
        $order = 1;
        while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
            $tabS += 1;
            $tabC += 1;
            $alt_count += 1;
            if ($alt_count % 2 == 0) {
                $alt = " class='alt'";
            } else {
                $alt = " class='std'";
            }
            echo "            <tr$alt id='row_{$row['Username']}'>\n";
            /* echo " <td>{$row['ClassOrder']}</td>\n"; */
            echo "               <td>$order</td>\n";
            $order += 1;
            echo "               <td>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
            if ($average_type != $AVG_TYPE_NONE) {
                echo "               <td><input type='text' name='score_{$row['Username']}' id='score_{$row['Username']}' " .
                     "size='5' tabindex='$tabS' onChange='recalc_avg(&quot;{$row['Username']}&quot;);'>" .
                     " = <label name='avg_{$row['Username']}' id='avg_{$row['Username']}' " .
                     "for='score_{$row['Username']}'>0%</label></td>\n";
            }
            echo "               <td><input type='text' name='comment_{$row['Username']}' " .
                 "size='50' tabindex='$tabC'></td>\n";
            echo "            </tr>\n";
        }
        echo "         </table>\n"; // End of table
        echo "         <p></p>\n";
    } else {
        echo "         <p>No students in class list.</p>\n";
    }
    $tabSave = $tabC + 1;
    $tabCancel = $tabC + 2;
    echo "         <p align='center'>\n";
    echo "            <input type='submit' name='action' value='Save' tabindex='$tabSave' \>&nbsp; \n";
    echo "            <input type='submit' name='action' value='Cancel' tabindex='$tabCancel' \>&nbsp; \n";
    echo "         </p>\n";
    echo "      </form>";
} else { // User isn't authorized to view or change scores.
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "teacher/assignment/new.php",
            $LOG_DENIED_ACCESS, "Tried to create new assignment for $subtitle.");

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";
?>
