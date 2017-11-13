<?php
/**
 * ***************************************************************
 * teacher/assignment/modify.php (c) 2004-2007, 2016-2017 Jonathan Dieter
 *
 * Show marks for already created assignment and allow teacher to
 * change them.
 * ***************************************************************
 */

/* Get variables */
$is_agenda = false;
$agenda_num = 0;
if(isset($_GET['agenda']) and intval(dbfuncInt2String($_GET['agenda'])) != 0) {
    $is_agenda = true;
    $agenda_num = 1;
}

if(isset($_GET['key'])) {
    $title = dbfuncInt2String($_GET['keyname']);
    $assignmentindex = intval(dbfuncInt2String($_GET['key']));
    $link = "index.php?location=" .
            dbfuncString2Int("teacher/assignment/modify_action.php") .
            "&amp;key=" . $_GET['key'] . "&amp;agenda=" .
            dbfuncString2Int($agenda_num) . "&amp;next=" .
            dbfuncString2Int($backLink);
    $new = false;
} else {
    if ($is_agenda)
        $title = "New agenda item";
    else
        $title = "New assignment";
    $subjectindex = intval(dbfuncInt2String($_GET['key2']));
    $link = "index.php?location=" .
            dbfuncString2Int("teacher/assignment/modify_action.php") .
            "&amp;key2=" . $_GET['key2'] . "&amp;agenda=" .
            dbfuncString2Int($agenda_num) . "&amp;next=" .
            dbfuncString2Int($backLink);
    $new = true;
}

$use_extra_css = true;
$extra_js = "assignment.js";

include "core/settermandyear.php";

/* Check whether user is authorized to change scores */
if($new) {
    $query =    "SELECT subjectteacher.Username FROM subjectteacher " .
                "WHERE subjectteacher.SubjectIndex = $subjectindex " .
                "AND   subjectteacher.Username     = '$username' ";
} else {
    $query =    "SELECT subjectteacher.Username FROM subjectteacher, assignment " .
                "WHERE subjectteacher.SubjectIndex = assignment.SubjectIndex " .
                "AND   assignment.AssignmentIndex = $assignmentindex " .
                "AND   subjectteacher.Username     = '$username' ";
}
$res = & $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo());

if ($res->numRows() == 0 and !$is_admin) {
    /* Get subject name and log unauthorized access attempt */
    if($new) {
        $query =    "SELECT subject.Name FROM subject " .
                    "WHERE subject.SubjectIndex        = $subjectindex";
    } else {
        $query =    "SELECT subject.Name FROM assignment, subject " .
                    "WHERE assignment.AssignmentIndex  = $assignmentindex " .
                    "AND   subject.SubjectIndex        = assignment.SubjectIndex";
    }
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());
    $row = & $res->fetchRow(DB_FETCHMODE_ASSOC);
    log_event($LOG_LEVEL_ERROR, "teacher/assignment/modify.php", $LOG_DENIED_ACCESS,
            "Tried to modify assignment for {$row['Name']}.");

    /* Print error message */
    include "header.php";

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    include "footer.php";
    exit(0);
}

if($new) {
    $query =    "SELECT subject.SubjectIndex, subject.AverageType, subject.AverageTypeIndex, " .
                "       subject.Name " .
                "       FROM subject " .
                "WHERE subject.SubjectIndex = $subjectindex";
} else {
    $query =    "SELECT subject.SubjectIndex, subject.AverageType, subject.AverageTypeIndex, " .
                "       subject.Name " .
                "       FROM subject INNER JOIN assignment USING (SubjectIndex) " .
                "WHERE assignment.AssignmentIndex = $assignmentindex";
}
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query
$row = & $res->fetchRow(DB_FETCHMODE_ASSOC);

$subjectindex = $row['SubjectIndex'];
$subjectname = $row['Name'];
$average_type = $row['AverageType'];
$average_type_index = $row['AverageTypeIndex'];

if(!$new) {
    /* Get assignment info */
    $query = "SELECT assignment.Title, assignment.Description, assignment.Max, " .
             "       assignment.DescriptionFileType, assignment.DescriptionFileIndex, " .
             "       assignment.TopMark, assignment.BottomMark, assignment.CurveType, " .
             "       assignment.Weight, assignment.Date, assignment.CategoryListIndex, " .
             "       assignment.DueDate, assignment.Hidden, assignment.IgnoreZero, " .
             "       assignment.Uploadable, assignment.UploadName, assignment.MakeupTypeIndex, " .
             "       makeup_type.OriginalMax, makeup_type.TargetMax " .
             "       FROM assignment LEFT OUTER JOIN makeup_type USING (MakeupTypeIndex) " .
             "WHERE assignment.AssignmentIndex  = $assignmentindex ";
    $asr = &  $db->query($query);
    if (DB::isError($asr))
        die($asr->getDebugInfo());
    $aRow = & $asr->fetchRow(DB_FETCHMODE_ASSOC);

    /* Check whether this is the current term, and if it isn't, whether the next term is open */
    if ($termindex != $currentterm) {
        $query = "SELECT TermIndex FROM term WHERE DepartmentIndex = $depindex ORDER BY TermNumber";
        $sres = & $db->query($query);
        if (DB::isError($sres))
            die($sres->getDebugInfo());
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
                     "WHERE subject.Name         = '$subjectname' " .
                     "AND   subject.SubjectIndex = subjectteacher.SubjectIndex " .
                     "AND   subject.TermIndex    = $next_termindex " .
                     "AND   subject.YearIndex    = $yearindex " .
                     "AND   subject.CanModify    = 1 ";
            if (! $is_admin) {
                $query .= "AND subjectteacher.Username = '$username'";
            }
            $sres = & $db->query($query);
            if (DB::isError($sres))
                die($sres->getDebugInfo());
            if ($srow = & $sres->fetchRow(DB_FETCHMODE_ASSOC)) {
                $next_subjectindex = $srow['SubjectIndex'];
            } else {
                $next_subjectindex = NULL;
            }
        }
    } else {
        $next_subjectindex = NULL;
    }

    $top_mark = $aRow['TopMark'];
    $bottom_mark = $aRow['BottomMark'];
    $dateinfo = date($dateformat, strtotime($aRow['Date']));
    if (isset($aRow['DueDate'])) {
        $duedateinfo = date($dateformat, strtotime($aRow['DueDate']));
    } else {
        $duedateinfo = "";
    }
    $assignment_title = htmlspecialchars($aRow['Title'], ENT_QUOTES);
    $curve_type = $aRow['CurveType'];

    $ignore_zero = $aRow['IgnoreZero'];
    if(!is_null($aRow['DescriptionFileType'])) {
        $descr_file_type = $aRow['DescriptionFileType'];
    } else {
        $descr_file_type = "";
    }
    $descr_file_index = $aRow['DescriptionFileIndex'];
    $hidden = $aRow['Hidden'];
    $uploadable = $aRow['Uploadable'];
    $max = $aRow['Max'];
    $weight = $aRow['Weight'];
    $category_list_index = $aRow['CategoryListIndex'];
    $makeup_type_index = $aRow['MakeupTypeIndex'];
    $description = $aRow['Description'];
} else {
    $top_mark = "";
    $bottom_mark = "";
    $dateinfo = date($dateformat); // Today
    $duedateinfo = date($dateformat, time() + (24 * 60 * 60)); // Tomorrow
    $assignment_title = "";
    $curve_type = 0;
    $ignore_zero = 1;
    $descr_file_type = "";
    $hidden = 0;
    $uploadable = 0;
    $max = "";
    $weight = "1";
    $category_list_index = null;
    $makeup_type_index = null;
    $description = "";
}

if ($average_type == $AVG_TYPE_INDEX and !is_null($average_type_index)) {
    $query = "SELECT Input, Display FROM nonmark_index " .
         "WHERE  NonmarkTypeIndex=$average_type_index ";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $input = strtoupper($row['Input']);
        $ainput_array = "'{$row['Input']}'";
        $adisplay_array = "'{$row['Display']}'";
        while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
            $input = strtoupper($srow['Input']);
            $ainput_array .= ", '$input'";
            $adisplay_array .= ", '{$row['Display']}'";
        }
    }
} else {
    $ainput_array = "";
    $adisplay_array = "";
}

/* Print assignment information table with fields filled in */

if ($ignore_zero == 1) {
    $ignorezero0 = "checked";
} else {
    $ignorezero0 = "";
}

if ($descr_file_type != "") {
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

if ($hidden == 1) {
    $hiddenchk = "checked";
} else {
    $hiddenchk = "";
}

if ($uploadable == 1) {
    $uploadablechk = "checked";
} else {
    $uploadablechk = "";
}

$subtitle = $subjectname;

log_event($LOG_LEVEL_EVERYTHING, "teacher/assignment/modify.php", $LOG_TEACHER,
        "Viewed assignment ($title) for $subjectname.");

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
} else {
    $query =    "SELECT MakeupTypeIndex, OriginalMax, TargetMax FROM makeup_type " .
                "ORDER BY MakeupType";
    $res =& $db->query($query);

    if (DB::isError($res))
        die($res->getDebugInfo());
    if ($res->numRows() > 0) {
        echo "         var makeup_dict = {\n";
        $first = true;
        while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
            if(!$first) {
                echo ",\n";
            } else {
                $first = false;
            }
            echo "                       {$row[MakeupTypeIndex]}: [{$row[OriginalMax]}, {$row[TargetMax]}]";
        }
        echo "\n";
        echo "         };";
    }
}

echo "\n";

echo "      </script>\n";
echo "      <form action='$link' enctype='multipart/form-data' method='post' name='assignment'>\n"; // Form method
echo "         <input type='hidden' id='agenda' name='agenda' value='$agenda_num'>\n";
echo "         <table class='transparent' align='center'>\n";
echo "            <tr>\n";
echo "               <td>Title:</td>\n";
echo "               <td colspan='2'><input type='text' name='title' value='{$assignment_title}' " .
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
if(!$is_agenda) {
    if ($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) {
        echo "            <tr>\n";
        echo "               <td>Maximum score:</td>\n";
        echo "               <td colspan='2'><input type='text' name='max' id='max' onChange='recalc_all();' " .
             "value='$max' tabindex='4' size='50'></td>\n";
        echo "            </tr>\n";
        echo "            <tr>\n";
        echo "               <td>Weight:</td>\n";
        echo "               <td colspan='2'><input type='text' name='weight' value='$weight' " .
             "tabindex='5' size='50'></td>\n";
        echo "            </tr>\n";
    }
}
echo "            <tr>\n";
echo "               <td>Assignment Options:</td>\n";
echo "               <td colspan='2'><input type='checkbox' name='hidden' id='hidden' tabindex='6' onchange='check_style();' $hiddenchk> " .
     "<label for='hidden'>Hidden from students</label><br>\n";
echo "                  <input type='checkbox' name='uploadable' id='uploadable' tabindex='7' $uploadablechk> " .
     "<label id='uploadable_lbl' for='uploadable'>Allow students to upload files so you can access them</label></td>\n";
echo "            </tr>\n";
if(!$is_agenda) {
    if ($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) {
        /* Get category info */
        $query =    "SELECT category.CategoryName, categorylist.CategoryListIndex, " .
                     "       categorylist.Weight, categorylist.TotalWeight FROM category, " .
                     "       categorylist, subject " .
                     "WHERE subject.SubjectIndex        = $subjectindex " .
                     "AND   categorylist.SubjectIndex   = subject.SubjectIndex " .
                     "AND   category.CategoryIndex      = categorylist.CategoryIndex " .
                     "ORDER BY category.CategoryName";
        $res = &  $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo());
        if ($res->numRows() > 0) {
            echo "            <tr>\n";
            echo "               <td>Category:</td>\n";
            echo "               <td colspan='2'>\n";
            echo "                  <select name='category' tabindex='8'>\n";
            $selected = "";
            while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
                $percentage = sprintf("%01.1f",
                                    ($row['Weight'] * 100) / $row['TotalWeight']);
                $selected = "";
                if ($category_list_index == $row['CategoryListIndex'])
                    $selected = " selected";
                echo "                     <option value='{$row['CategoryListIndex']}'$selected>" .
                     "{$row['CategoryName']} - {$percentage}%</option>\n";
            }
            echo "                  </select>\n";
            echo "               </td>\n";
            echo "            </tr>\n";
        }
        $query =    "SELECT MakeupTypeIndex, MakeupType FROM makeup_type " .
                    "ORDER BY MakeupType";
        $res =& $db->query($query);

        if (DB::isError($res))
            die($res->getDebugInfo());
        if ($res->numRows() > 0) {
            echo "            <tr>\n";
            echo "               <td>Makeup type:</td>\n";
            echo "               <td colspan='2'>\n";
            echo "                  <select name='makeuptype' id='makeuptype' tabindex='9' onChange='makeup_check();'>\n";
            if(is_null($makeup_type_index)) {
                $selected = " selected";
            } else {
                $selected = "";
            }
            echo "                     <option value='NULL'$selected>" .
                     "<i>None</i></option>\n";

            while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
                $selected = "";
                if ($makeup_type_index == $row['MakeupTypeIndex'])
                    $selected = " selected";
                echo "                     <option value='{$row['MakeupTypeIndex']}'$selected>" .
                     "{$row['MakeupType']}</option>\n";
            }
            echo "                  </select>\n";
            echo "               </td>\n";
            echo "            </tr>\n";
        }
    }
}
$description = htmlspecialchars(unhtmlize_comment($description), ENT_QUOTES);
$currentdata = "None";
if ($descr_file_type != "") {
    if ($descr_file_type == "application/pdf") {
        $fileloc = get_path_from_id($descr_file_index);
        $currentdata = "<a href='$fileloc'>PDF Document</a>";
    } else {
        $currentdata = "Unknown format";
    }
}

echo "            <tr>\n";
echo "               <td>Description:</td>\n";
echo "               <td colspan='2'>\n";
echo "                  <input type='radio' name='descr_type' id='descr_type0' value='0' tabindex='10' onChange='descr_check();' $descrtype0>\n";
echo "                  <textarea style='vertical-align: top' rows='10' cols='50' id='descr' name='descr' tabindex='11'>$description</textarea><br>\n";
echo "                  <input type='radio' name='descr_type' id='descr_type1' value='1' tabindex='12' onChange='descr_check();' $descrtype1>\n";
echo "                  <input type='file' name='descr_upload' id='descr_upload' tabindex='13' accept='application/pdf'><input type='hidden' name='MAX_FILE_SIZE' value='10240000'><br>\n";
echo "                  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Current file: <i>$currentdata</i>\n";
echo "               </td>\n";
echo "            </tr>\n";
if(!$is_agenda) {
    if ($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) {
        echo "            <tr>\n";
        echo "               <td>Curve Type:</td>\n";
        echo "               <td>\n";
        echo "                  <input type='radio' name='curve_type' onChange='recalc_all();' id='curve_type0' " .
             "value='0' tabindex='14' $curvetype0><label for='curve_type0'>None</label><br>\n";
        echo "                  <input type='radio' name='curve_type' onChange='recalc_all();' id='curve_type1' " .
             "value='1' tabindex='15' $curvetype1><label for='curve_type1'>Maximum score is 100%</label><br>\n";
        echo "                  <input type='radio' name='curve_type' onChange='recalc_all();' id='curve_type2' " .
             "value='2' tabindex='16' $curvetype2><label for='curve_type2'>Distributed scoring</label>\n";
        echo "               </td>\n";
        echo "               <td>\n";
        echo "                  <label id='top_mark_label' for='top_mark'>Top mark: \n";
        echo "                  <input type='text' name='top_mark' id='top_mark' onChange='recalc_all();' " .
             "value='$top_mark' size='5' tabindex='17' onChange='recalc_all();'>%</label><br>\n";
        echo "                  <label id='bottom_mark_label' for='bottom_mark'>Bottom mark: \n";
        echo "                  <input type='text' name='bottom_mark' id='bottom_mark' onChange='recalc_all();' " .
             "value='$bottom_mark' size='5' tabindex='18' onChange='recalc_all();'>%</label><br>\n";
        echo "                  <label id='ignore_zero_label' for='ignore_zero'>";
        echo "                  <input type='checkbox' name='ignore_zero' id='ignore_zero' onChange='recalc_all();' " .
             "value='1' tabindex='19' onChange='recalc_all();' $ignorezero0>Don't change zeroes</label><br>\n";
        echo "               </td>\n";
        echo "            </tr>\n";
    }
}
echo "         </table>\n";
echo "         <p align='center'>\n";
if($new) {
    echo "            <input type='submit' name='action' value='Save' tabindex='20' />&nbsp; \n";
    echo "            <input type='submit' name='action' value='Cancel' tabindex='21' />&nbsp; \n";
} else {
    echo "            <input type='submit' name='action' value='Update' tabindex='20' />&nbsp; \n";
    echo "            <input type='submit' name='action' value='Cancel' tabindex='21' />&nbsp; \n";
    echo "            <input type='submit' name='action' value='Delete' tabindex='22' />&nbsp; \n";
    if(!$is_agenda) {
        echo "            <input type='submit' name='action' value='Convert to agenda item' tabindex='23' />&nbsp; \n";
    } else {
        echo "            <input type='submit' name='action' value='Convert to assignment' tabindex='23' />&nbsp; \n";
    }
}
if (! is_null($next_subjectindex)) {
    echo "            <input type='hidden' name='next_subject' value='$next_subjectindex' /><input type='submit' name='action' value='Move this assignment to next term' tabindex='24' />&nbsp; \n";
}
echo "         </p>\n";
if($is_agenda) {
    echo "      </form>\n";
    include "footer.php";
    exit(0);
}
echo "         <p></p>\n";

if($new) {
    $query =    "SELECT user.FirstName, user.Surname, user.Username, NULL AS Score, NULL AS Comment, " .
                "       NULL AS MakeupScore, NULL AS Percentage, NULL AS MakeupPercentage, " .
                "       NULL AS OriginalPercentage, query.ClassOrder " .
                "       FROM subjectstudent LEFT OUTER JOIN" .
                "       (SELECT classlist.ClassOrder, classlist.Username " .
                "               FROM class, classterm, classlist, subject " .
                "        WHERE classlist.ClassTermIndex = classterm.ClassTermIndex " .
                "        AND   classterm.TermIndex = subject.TermIndex " .
                "        AND   class.ClassIndex = classterm.ClassIndex " .
                "        AND   class.YearIndex = subject.YearIndex " .
                "        AND   subject.SubjectIndex       = $subjectindex) AS query " .
                "       ON subjectstudent.Username = query.Username, " .
                "       user " .
                "WHERE user.Username               = subjectstudent.Username " .
                "AND   subjectstudent.SubjectIndex = $subjectindex " .
                "ORDER BY user.FirstName, user.Surname, user.Username";
} else {
    $query =    "SELECT user.FirstName, user.Surname, user.Username, mark.Score, mark.Comment, " .
                "       mark.MakeupScore, mark.Percentage, mark.MakeupPercentage, mark.OriginalPercentage, " .
                "       query.ClassOrder FROM subjectstudent LEFT OUTER JOIN" .
                "       (SELECT classlist.ClassOrder, classlist.Username " .
                "               FROM class, classterm, classlist, subject " .
                "        WHERE classlist.ClassTermIndex = classterm.ClassTermIndex " .
                "        AND   classterm.TermIndex = subject.TermIndex " .
                "        AND   class.ClassIndex = classterm.ClassIndex " .
                "        AND   class.YearIndex = subject.YearIndex " .
                "        AND   subject.SubjectIndex       = $subjectindex) AS query " .
                "       ON subjectstudent.Username = query.Username, " .
                "       user LEFT OUTER JOIN mark ON (mark.AssignmentIndex = $assignmentindex " .
                "                                           AND mark.Username = user.Username) " .
                "WHERE user.Username               = subjectstudent.Username " .
                "AND   subjectstudent.SubjectIndex = $subjectindex " .
                "ORDER BY user.FirstName, user.Surname, user.Username";
}
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo());

/* Print scores and comments */
$makeupObjCounter = 0;
$tabC = 25;
$order = 1;
if ($res->numRows() > 0) {
    echo "         <table align='center' border='1'>\n";
    echo "            <tr>\n";
    echo "               <th>&nbsp;</th>\n";
    echo "               <th>Student</th>\n";
    if ($average_type != $AVG_TYPE_NONE) {
        echo "               <th>Score</th>\n";
        if (!is_null($makeup_type_index)) {
            $style = "";
        } else {
            $style = " style='display: none'";
        }
        echo "               <th id='makeupObj_0' $style>Makeup</th>\n";
        echo "               <th id='makeupObj_1' $style>Overall</th>\n";
    }
    echo "               <th>Comment</th>\n";
    echo "            </tr>\n";
    $makeupObjCounter += 2;

    /* For each student, print a row with the student's name and score on each assignment */
    $alt_count = 0;

    $tabS = $tabC;
    $tabM = $tabC + ($res->numRows()*2);
    $tabC = $tabC + ($res->numRows()*4);

    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        $tabS += 1;
        $tabM += 1;
        $tabC += 1;
        $alt_count += 1;

        if ($alt_count % 2 == 0) {
            $alt = " class='alt'";
        } else {
            $alt = " class='std'";
        }

        if (is_null($makeup_type_index)) {
            $items = array('Score');
            $has_makeup = False;
        } else {
            $items = array('MakeupScore', 'Score');
            $has_makeup = True;
        }

        if ($average_type == $AVG_TYPE_PERCENT or
             $average_type == $AVG_TYPE_GRADE) {
            foreach($items as $item) {
                if ($row[$item] == $MARK_ABSENT) {
                    $row[$item] = 'A';
                } elseif ($row[$item] == $MARK_EXEMPT) {
                    $row[$item] = 'E';
                } elseif ($row[$item] == $MARK_LATE) {
                    $row[$item] = 'L';
                }
            }

            $avg = format_average($row['OriginalPercentage']);
            $makeup_avg = format_average($row['MakeupPercentage']);
            $overall_avg = format_average($row['Percentage']);
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
                    die($sres->getDebugInfo());
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
        echo "               <td>$order</td>\n";
        $order += 1;
        echo "               <td>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
        if ($average_type != $AVG_TYPE_NONE) {
            echo "               <td><input type='text' name='score_{$row['Username']}' id='score_{$row['Username']}' " .
                 "value='{$row['Score']}' size='5' tabindex='$tabS' " .
                 "onChange='recalc_avg(&quot;{$row['Username']}&quot;);'>" .
                 " = <label name='avg_{$row['Username']}' id='avg_{$row['Username']}' " .
                 "for='score_{$row['Username']}'>$avg</label></td>\n";
            if ($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) {
                if($has_makeup) {
                    $style = "";
                } else {
                    $style = " style='display: none'";
                }
                echo "               <td id='makeupObj_$makeupObjCounter' $style><input type='text' name='makeup_score_{$row['Username']}' id='makeup_score_{$row['Username']}' " .
                 "value='{$row['MakeupScore']}' size='5' tabindex='$tabM' " .
                 "onChange='recalc_avg(&quot;{$row['Username']}&quot;, true);'>" .
                 " = <label name='makeup_avg_{$row['Username']}' id='makeup_avg_{$row['Username']}' " .
                 "for='makeup_score_{$row['Username']}'>$makeup_avg</label></td>\n";
                $makeupObjCounter += 1;
                echo "               <td id='makeupObj_$makeupObjCounter' $style><b><label name='overall_avg_{$row['Username']}' id='overall_avg_{$row['Username']}'>$overall_avg</label></b></td>\n";
                $makeupObjCounter += 1;
            }
        }
        echo "               <td><input type='text' name='comment_{$row['Username']}' " .
             "value='{$row['Comment']}' size='50' tabindex='$tabC'></td>\n";
        echo "            </tr>\n";
    }
    echo "         </table>\n";
    echo "         <p></p>\n";
} else {
    echo "          <p>No students in class list.</p>\n";
}
$tabUpdate = $tabC + 1;
$tabCancel = $tabC + 2;
$tabDelete = $tabC + 3;
$tabAgenda = $tabC + 4;
echo "         <p align='center'>\n";
if($new) {
    echo "            <input type='submit' name='action' value='Save' tabindex='$tabUpdate' \>&nbsp; \n";
    echo "            <input type='submit' name='action' value='Cancel' tabindex='$tabCancel' \>&nbsp; \n";
} else {
    echo "            <input type='submit' name='action' value='Update' tabindex='$tabUpdate' \>&nbsp; \n";
    echo "            <input type='submit' name='action' value='Cancel' tabindex='$tabCancel' \>&nbsp; \n";
    echo "            <input type='submit' name='action' value='Delete' tabindex='$tabDelete' \>&nbsp; \n";
    echo "            <input type='submit' name='action' value='Convert to agenda item' tabindex='$tabAgenda' \>&nbsp; \n";
}
echo "         </p>\n";

echo "      </form>\n";

include "footer.php";
