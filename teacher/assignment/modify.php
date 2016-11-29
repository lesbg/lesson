<?php
/**
 * ***************************************************************
 * teacher/assignment/modify.php (c) 2004-2007, 2016 Jonathan Dieter
 *
 * Show marks for already created assignment and allow teacher to
 * change them.
 * ***************************************************************
 */

/* Get variables */
$title = dbfuncInt2String($_GET['keyname']);
$assignmentindex = safe(dbfuncInt2String($_GET['key']));
$link = "index.php?location=" .
         dbfuncString2Int("teacher/assignment/new_or_modify_action.php") .
         "&amp;key=" . $_GET['key'] . "&amp;next=" .
         dbfuncString2Int($backLink);
$use_extra_css = true;
$extra_js = "assignment.js";

include "core/settermandyear.php";

/* Check whether user is authorized to change scores */
$res = & $db->query(
                "SELECT subjectteacher.Username FROM subjectteacher, assignment " .
                 "WHERE subjectteacher.SubjectIndex = assignment.SubjectIndex " .
                 "AND   assignment.AssignmentIndex  = $assignmentindex " .
                 "AND   subjectteacher.Username     = '$username'");
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0 or $is_admin) {
    $res = &  $db->query(
                    "SELECT user.FirstName, user.Surname, user.Username, mark.Score, mark.Comment, " .
                         "       query.ClassOrder FROM assignment, subjectstudent LEFT OUTER JOIN" .
                         "       (SELECT classlist.ClassOrder, classlist.Username " .
                         "               FROM class, classterm, classlist, subject, assignment " .
                         "        WHERE classlist.ClassTermIndex = classterm.ClassTermIndex " .
                         "        AND   classterm.TermIndex = subject.TermIndex " .
                         "        AND   class.ClassIndex = classterm.ClassIndex " .
                         "        AND   class.YearIndex = subject.YearIndex " .
                         "        AND   subject.SubjectIndex       = assignment.SubjectIndex " .
                         "        AND   assignment.AssignmentIndex = $assignmentindex) AS query " .
                         "       ON subjectstudent.Username = query.Username, " .
                         "       user LEFT OUTER JOIN mark ON (mark.AssignmentIndex = $assignmentindex " .
                         "                                           AND mark.Username = user.Username) " .
                         "WHERE user.Username               = subjectstudent.Username " .
                         "AND   subjectstudent.SubjectIndex = assignment.SubjectIndex " .
                         "AND   assignment.AssignmentIndex  = $assignmentindex " .
                         "ORDER BY user.FirstName, user.Surname, user.Username");
    // "ORDER BY user.Username");
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    /* Get assignment info */
    $query = "SELECT assignment.Title, assignment.Description, assignment.Max, " .
             "       assignment.DescriptionFileType, assignment.DescriptionFileIndex, " .
             "       assignment.TopMark, assignment.BottomMark, assignment.CurveType, " .
             "       assignment.Weight, assignment.Date, assignment.CategoryListIndex, " .
             "       assignment.DueDate, assignment.Hidden, assignment.IgnoreZero, " .
             "       assignment.Uploadable, assignment.UploadName, " .
             "       subject.Name, subject.AverageType, subject.AverageTypeIndex " .
             "       FROM assignment, subject " .
             "WHERE assignment.AssignmentIndex  = $assignmentindex " .
             "AND   subject.SubjectIndex        = assignment.SubjectIndex";
    $asr = &  $db->query($query);
    if (DB::isError($asr))
        die($asr->getDebugInfo()); // Check for errors in query
    $aRow = & $asr->fetchRow(DB_FETCHMODE_ASSOC);

    /* Check whether this is the current term, and if it isn't, whether the next term is open */
    if ($termindex != $currentterm) {
        $query = "SELECT TermIndex FROM term WHERE DepartmentIndex = $depindex ORDER BY TermNumber";
        $sres = & $db->query($query);
        if (DB::isError($sres))
            die($sres->getDebugInfo()); // Check for errors in query
        while ( $srow = & $sres->fetchRow(DB_FETCHMODE_ASSOC) ) {
            if ($srow['TermIndex'] == $termindex) {
                if ($srow = & $sres->fetchRow(DB_FETCHMODE_ASSOC)) {
                    $next_termindex = $srow['TermIndex'];
                } else {
                    $next_termindex = NULL;
                }
            }
        }
        if (! is_null($next_termindex)) {
            $query = "SELECT subject.SubjectIndex FROM subject, subjectteacher " .
                     "WHERE subject.Name         = '{$aRow['Name']}' " .
                     "AND   subject.SubjectIndex = subjectteacher.SubjectIndex " .
                     "AND   subject.TermIndex    = $next_termindex " .
                     "AND   subject.YearIndex    = $yearindex " .
                     "AND   subject.CanModify    = 1 ";
            if (! $is_admin) {
                $query .= "AND subjectteacher.Username = '$username'";
            }
            $sres = & $db->query($query);
            if (DB::isError($sres))
                die($sres->getDebugInfo()); // Check for errors in query
            if ($srow = & $sres->fetchRow(DB_FETCHMODE_ASSOC)) {
                $next_subjectindex = $srow['SubjectIndex'];
            } else {
                $next_subjectindex = NULL;
            }
        }
    } else {
        $next_subjectindex = NULL;
    }

    $average_type = $aRow['AverageType'];
    $average_type_index = $aRow['AverageTypeIndex'];

    if ($average_type == $AVG_TYPE_INDEX and
         ! is_null($aRow['AverageTypeIndex'])) {
        $query = "SELECT Input, Display FROM nonmark_index " .
         "WHERE  NonmarkTypeIndex={$aRow['AverageTypeIndex']} ";
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

/* Get max and minimum mark */
if ($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) {
    $top_mark = $aRow['TopMark'];
    $bottom_mark = $aRow['BottomMark'];
    $query = "SELECT MAX(Score) AS MaxScore, MIN(Score) AS MinScore FROM mark " .
             "WHERE AssignmentIndex  = $assignmentindex ";
    if ($aRow['IgnoreZero'] == 1) {
        $query .= "AND   Score           > 0";
    } else {
        $query .= "AND   Score           >= 0";
    }

    $bsr = &  $db->query($query);

    if (DB::isError($bsr))
        die($bsr->getDebugInfo()); // Check for errors in query
    if ($bRow = & $bsr->fetchRow(DB_FETCHMODE_ASSOC)) {
        $min_score = $bRow['MinScore'];
        $max_score = $bRow['MaxScore'];
        if ($max_score - $min_score == 0) {
            $m = $b = 0;
        } else {
            $m = ($top_mark - $bottom_mark) / ($max_score - $min_score);
            $b = ($top_mark * $min_score - $bottom_mark * $max_score) /
                 ($min_score - $max_score);
        }
    } else {
        $m = $b = 0;
    }
}

/* Print assignment information table with fields filled in */
$dateinfo = date($dateformat, strtotime($aRow['Date']));
if (isset($aRow['DueDate'])) {
    $duedateinfo = date($dateformat, strtotime($aRow['DueDate']));
} else {
    $duedateinfo = "";
}
$aRow['Title'] = htmlspecialchars($aRow['Title'], ENT_QUOTES);
$curve_type = $aRow['CurveType'];

$ignore_zero = $aRow['IgnoreZero'];
if ($ignore_zero == 1) {
    $ignorezero0 = "checked";
}

if (isset($aRow['DescriptionFileType']) and $aRow['DescriptionFileType'] != "") {
    $descrtype0 = "";
    $descrtype1 = "checked";
} else {
    $descrtype0 = "checked";
    $descrtype1 = "";
}

if ($curve_type == 1) {
    $curvetype0 = "";
    $curvetype1 = "checked";
    $curvetype2 = "";
} elseif ($curve_type == 2) {
    $curvetype0 = "";
    $curvetype1 = "";
    $curvetype2 = "checked";
} else {
    $curvetype0 = "checked";
    $curvetype1 = "";
    $curvetype2 = "";
}

if ($aRow['Hidden'] == 1) {
    $hidden = "checked";
} else {
    $hidden = "";
}

if ($aRow['Uploadable'] == 1) {
    $uploadable = "checked";
} else {
    $uploadable = "";
}

$subtitle = $aRow['Name'];

log_event($LOG_LEVEL_EVERYTHING, "teacher/assignment/modify.php", $LOG_TEACHER,
        "Viewed assignment ($title) for {$aRow['Name']}.");

include "header.php"; // Show header

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
echo "      <form action='$link' enctype='multipart/form-data' method='post' name='assignment'>\n"; // Form method
echo "         <input type='hidden' id='agenda' name='agenda' value='0'>\n";
echo "         <table class='transparent' align='center'>\n";
echo "            <tr>\n";
echo "               <td>Title:</td>\n";
echo "               <td colspan='2'><input type='text' name='title' value='{$aRow['Title']}' " .
     "tabindex='1' size='50'></td>\n";
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
         "value='{$aRow['Max']}' tabindex='4' size='50'></td>\n";
    echo "            </tr>\n";
    echo "            <tr>\n";
    echo "               <td>Weight:</td>\n";
    echo "               <td colspan='2'><input type='text' name='weight' value='{$aRow['Weight']}' " .
         "tabindex='5' size='50'></td>\n";
    echo "            </tr>\n";
}
echo "            <tr>\n";
echo "               <td>Assignment Options:</td>\n";
echo "               <td colspan='2'><input type='checkbox' name='hidden' id='hidden' tabindex='6' onchange='check_style();' $hidden> " .
     "<label for='hidden'>Hidden from students</label><br>\n";
echo "                  <input type='checkbox' name='uploadable' id='uploadable' tabindex='7' $uploadable> " .
     "<label id='uploadable_lbl' for='uploadable'>Allow students to upload files so you can access them</label></td>\n";
echo "            </tr>\n";

if ($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) {
    /* Get category info */
    $bsr = &  $db->query(
                    "SELECT category.CategoryName, categorylist.CategoryListIndex, " .
                         "       categorylist.Weight, categorylist.TotalWeight FROM category, " .
                         "       categorylist, subject, assignment " .
                         "WHERE assignment.AssignmentIndex  = $assignmentindex " .
                         "AND   subject.SubjectIndex        = assignment.SubjectIndex " .
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
        $selected = "";
        // if(is_null($aRow['CategoryIndex'])) $selected = " selected";
        // echo " <option value='NULL'$selected>(None)\n";
        while ( $bRow = & $bsr->fetchRow(DB_FETCHMODE_ASSOC) ) {
            $percentage = sprintf("%01.1f",
                                ($bRow['Weight'] * 100) / $bRow['TotalWeight']);
            $selected = "";
            if ($aRow['CategoryListIndex'] == $bRow['CategoryListIndex'])
                $selected = " selected";
            echo "                     <option value='{$bRow['CategoryListIndex']}'$selected>" .
                 "{$bRow['CategoryName']} - {$percentage}%</option>\n";
        }
        echo "                  </select>\n";
        echo "               </td>\n";
        echo "            </tr>\n";
    }
}

$aRow['Description'] = htmlspecialchars(
                                        unhtmlize_comment($aRow['Description']),
                                        ENT_QUOTES);
$currentdata = "None";
if (isset($aRow['DescriptionFileType'])) {
    if ($aRow['DescriptionFileType'] == "application/pdf") {
        $fileloc = get_path_from_id($aRow['DescriptionFileIndex']);
        $currentdata = "<a href='$fileloc'>PDF Document</a>";
    } elseif ($aRow['DescriptionFileType'] != "") {
        $currentdata = "Unknown format";
    }
}

echo "            <tr>\n";
echo "               <td>Description:</td>\n";
echo "               <td colspan='2'>\n";
echo "                  <input type='radio' name='descr_type' id='descr_type0' value='0' tabindex='9' onChange='descr_check();' $descrtype0>\n";
echo "                  <textarea style='vertical-align: top' rows='10' cols='50' id='descr' name='descr' tabindex='10'>{$aRow['Description']}</textarea><br>\n";
echo "                  <input type='radio' name='descr_type' id='descr_type1' value='1' tabindex='11' onChange='descr_check();' $descrtype1>\n";
echo "                  <input type='file' name='descr_upload' id='descr_upload' tabindex='12' accept='application/pdf'><input type='hidden' name='MAX_FILE_SIZE' value='10240000'><br>\n";
echo "                  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Current file: <i>$currentdata</i>\n";
echo "               </td>\n";
echo "            </tr>\n";
if ($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) {
    echo "            <tr>\n";
    echo "               <td>Curve Type:</td>\n";
    echo "               <td>\n";
    echo "                  <input type='radio' name='curve_type' onChange='recalc_all();' id='curve_type0' " .
         "value='0' tabindex='13' $curvetype0><label for='curve_type0'>None</label><br>\n";
    echo "                  <input type='radio' name='curve_type' onChange='recalc_all();' id='curve_type1' " .
         "value='1' tabindex='14' $curvetype1><label for='curve_type1'>Maximum score is 100%</label><br>\n";
    echo "                  <input type='radio' name='curve_type' onChange='recalc_all();' id='curve_type2' " .
         "value='2' tabindex='15' $curvetype2><label for='curve_type2'>Distributed scoring</label>\n";
    echo "               </td>\n";
    echo "               <td>\n";
    echo "                  <label id='top_mark_label' for='top_mark'>Top mark: \n";
    echo "                  <input type='text' name='top_mark' id='top_mark' onChange='recalc_all();' " .
         "value='$top_mark' size='5' tabindex='16' onChange='recalc_all();'>%</label><br>\n";
    echo "                  <label id='bottom_mark_label' for='bottom_mark'>Bottom mark: \n";
    echo "                  <input type='text' name='bottom_mark' id='bottom_mark' onChange='recalc_all();' " .
         "value='$bottom_mark' size='5' tabindex='17' onChange='recalc_all();'>%</label><br>\n";
    echo "                  <label id='ignore_zero_label' for='ignore_zero'>";
    echo "                  <input type='checkbox' name='ignore_zero' id='ignore_zero' onChange='recalc_all();' " .
         "value='1' tabindex='18' onChange='recalc_all();' $ignorezero0>Don't change zeroes</label><br>\n";
    echo "               </td>\n";
    echo "            </tr>\n";
}
echo "         </table>\n";
echo "         <p align='center'>\n";
echo "            <input type='submit' name='action' value='Update' tabindex='19' />&nbsp; \n";
echo "            <input type='submit' name='action' value='Cancel' tabindex='20' />&nbsp; \n";
echo "            <input type='submit' name='action' value='Delete' tabindex='21' />&nbsp; \n";
echo "            <input type='submit' name='action' value='Convert to agenda item' tabindex='22' \>&nbsp; \n";
if (! is_null($next_subjectindex)) {
    echo "            <input type='hidden' name='next_subject' value='$next_subjectindex' /><input type='submit' name='action' value='Move this assignment to next term' tabindex='23' />&nbsp; \n";
}
echo "         </p>\n";
echo "         <p></p>\n";
/* Print scores and comments */
$tabC = 24;
$order = 1;
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

    /* For each student, print a row with the student's name and score on each assignment */
    $alt_count = 0;
    if ($res->numRows() > 0) {
        $tabS = $tabC;
        $tabC = $tabC + $res->numRows();
    }
    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        $tabS += 1;
        $tabC += 1;
        $alt_count += 1;

        if ($alt_count % 2 == 0) {
            $alt = " class='alt'";
        } else {
            $alt = " class='std'";
        }

        if ($average_type == $AVG_TYPE_PERCENT or
             $average_type == $AVG_TYPE_GRADE) {
            if ($row['Score'] == $MARK_ABSENT) {
                $row['Score'] = 'A';
                $avg = "N/A";
            } elseif ($row['Score'] == $MARK_EXEMPT) {
                $row['Score'] = 'E';
                $avg = "N/A";
            } elseif ($row['Score'] == $MARK_LATE) {
                $row['Score'] = 'L';
                $avg = "0%";
            } else {
                if ($curve_type == 1) {
                    if ($max_score == 0) {
                        $avg = "0%";
                    } else {
                        $avg = round(($row['Score'] / $max_score) * 100) . "%";
                    }
                } elseif ($curve_type == 2) {
                    if (($m == 0 && $b == 0) or
                             ($row['Score'] == 0 and ignore_zero == 1)) {
                        $avg = "0%";
                    } else {
                        $avg = round(($m * $row['Score']) + $b) . "%";
                    }
                } else {
                    if ($aRow['Max'] == 0) {
                        $avg = "0%";
                    } else {
                        $avg = round(($row['Score'] / $aRow['Max']) * 100) . "%";
                    }
                }
            }
        } elseif ($average_type == $AVG_TYPE_INDEX) {
            if (! isset($average_type_index) or $average_type_index == "" or
                     ! isset($row['Score']) or $row['Score'] == "") {
                $row['Score'] = "";
                $avg = "N/A";
            } else {
                $query = "SELECT Input, Display FROM nonmark_index " .
                         "WHERE NonmarkTypeIndex = $average_type_index " .
                         "AND   NonmarkIndex     = {$row['Score']}";
                $sres = & $db->query($query);
                if (DB::isError($sres))
                    die($sres->getDebugInfo()); // Check for errors in query
                if ($srow = & $sres->fetchRow(DB_FETCHMODE_ASSOC)) {
                    $row['Score'] = $srow['Input'];
                    $avg = $srow['Display'];
                } else {
                    $row['Score'] = "";
                    $avg = "N/A";
                }
            }
        }
        $row['Comment'] = htmlspecialchars($row['Comment'],
                                           ENT_QUOTES);

        echo "            <tr$alt id='row_{$row['Username']}'>\n";
        /* echo " <td>{$row['ClassOrder']}</td>\n"; */
        echo "               <td>$order</td>\n";
        $order += 1;
        echo "               <td>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
        if ($average_type != $AVG_TYPE_NONE) {
            echo "               <td><input type='text' name='score_{$row['Username']}' id='score_{$row['Username']}' " .
                 "value='{$row['Score']}' size='5' tabindex='$tabS' " .
                 "onChange='recalc_avg(&quot;{$row['Username']}&quot;);'>" .
                 " = <label name='avg_{$row['Username']}' id='avg_{$row['Username']}' " .
                 "for='score_{$row['Username']}'>$avg</label></td>\n";
        }
        echo "               <td><input type='text' name='comment_{$row['Username']}' " .
             "value='{$row['Comment']}' size='50' tabindex='$tabC'></td>\n";
        echo "            </tr>\n";
    }
    echo "         </table>\n"; // End of table
    echo "         <p></p>\n";
} else {
    echo "          <p>No students in class list.</p>\n";
}
$tabUpdate = $tabC + 1;
$tabCancel = $tabC + 2;
$tabDelete = $tabC + 3;
$tabAgenda = $tabC + 4;
echo "         <p align='center'>\n";
echo "            <input type='submit' name='action' value='Update' tabindex='$tabUpdate' \>&nbsp; \n";
echo "            <input type='submit' name='action' value='Cancel' tabindex='$tabCancel' \>&nbsp; \n";
echo "            <input type='submit' name='action' value='Delete' tabindex='$tabDelete' \>&nbsp; \n";
echo "            <input type='submit' name='action' value='Convert to agenda item' tabindex='$tabAgenda' \>&nbsp; \n";
echo "         </p>\n";

echo "      </form>\n";
} else { // User isn't authorized to view or change scores.
/* Get subject name and log unauthorized access attempt */
$asr = &  $db->query(
                "SELECT subject.Name FROM assignment, subject " .
                 "WHERE assignment.AssignmentIndex  = $assignmentindex " .
                 "AND   subject.SubjectIndex        = assignment.SubjectIndex");
if (DB::isError($asr))
    die($asr->getDebugInfo()); // Check for errors in query
$aRow = & $asr->fetchRow(DB_FETCHMODE_ASSOC);
log_event($LOG_LEVEL_ERROR, "teacher/assignment/modify.php", $LOG_DENIED_ACCESS,
        "Tried to modify assignment for {$aRow['Name']}.");

/* Print error message */
include "header.php";

echo "      <p>You do not have permission to access this page</p>\n";
echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";
?>
