<?php
/**
 * ***************************************************************
 * teacher/report/proofread_modify.php (c) 2008, 2017 Jonathan Dieter
 *
 * Show subject conduct, effort, average and comment for report
 * Change class conduct, effort, average and comments
 * ***************************************************************
 */

/* Get variables */
if (! isset($_GET['next']))
    $_GET['next'] = dbfuncString2Int($backLink);


$class = dbfuncInt2String($_GET['keyname']);
$student_name = dbfuncInt2String($_GET['keyname2']);
$title = "Report for " . $student_name;
$classtermindex = intval(safe(dbfuncInt2String($_GET['key'])));
$student_username = safe(dbfuncInt2String($_GET['key2']));
$link = "index.php?location=" .
         dbfuncString2Int("teacher/report/class_modify_action.php") . "&amp;key=" .
         $_GET['key'] . "&amp;key2=" . $_GET['key2'] . "&amp;keyname=" .
         $_GET['keyname'] . "&amp;keyname2=" . $_GET['keyname2'] . "&amp;next=" .
         $_GET['next'];

if(!isset($_GET['key']) or $classtermindex === False)
    redirect('/');

$extra_js = "class_report.js";

include "core/settermandyear.php";
include "header.php"; // Show header

/* Check whether subject is open for report editing */
$query = "SELECT classterm.AverageType, classterm.EffortType, classterm.ConductType, " .
         "       classterm.AverageTypeIndex, classterm.EffortTypeIndex, " .
         "       classterm.ConductTypeIndex, classterm.CTCommentType, " .
         "       classterm.HODCommentType, classterm.PrincipalCommentType, " .
         "       classterm.CanDoReport, classterm.AbsenceType, class.DepartmentIndex, " .
         "       department.ProofreaderUsername " .
         "       FROM classterm, class, department " .
         "WHERE classterm.ClassTermIndex   = $classtermindex " .
         "AND   class.ClassIndex           = classterm.ClassIndex " .
         "AND   department.DepartmentIndex = class.DepartmentIndex ";
$res = & $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if (! $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) or $row['CanDoReport'] == 0) {
    /* Print error message */
    include "header.php";
    echo "      <p>Reports for this class aren't open.</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    log_event($LOG_LEVEL_ERROR, "teacher/report/class_modify.php",
            $LOG_DENIED_ACCESS, "Tried to modify report for $subject.");

    include "footer.php";
    exit(0);
}

$average_type = $row['AverageType'];
$absence_type = $row['AbsenceType'];
$effort_type = $row['EffortType'];
$conduct_type = $row['ConductType'];
$ct_comment_type = $row['CTCommentType'];
$hod_comment_type = $row['HODCommentType'];
$pr_comment_type = $row['PrincipalCommentType'];
$can_do_report = $row['CanDoReport'];
$average_type_index = $row['AverageTypeIndex'];
$effort_type_index = $row['EffortTypeIndex'];
$conduct_type_index = $row['ConductTypeIndex'];
$depindex = $row['DepartmentIndex'];
$proof_username = $row['ProofreaderUsername'];

/* Check whether current user is principal */
$res = &  $db->query(
                "SELECT Username FROM principal " .
                 "WHERE Username=\"$username\" AND Level=1");
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
    $is_principal = true;
} else {
    $is_principal = false;
}

/* Check whether current user is a hod */
$res = &  $db->query(
                "SELECT hod.Username FROM hod, class, classterm " .
                 "WHERE hod.Username        = '$username' " .
                 "AND   hod.DepartmentIndex = class.DepartmentIndex " .
                 "AND   class.ClassIndex    = classterm.ClassIndex " .
                 "AND   classterm.ClassTermIndex = $classtermindex");
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
    $is_hod = true;
} else {
    $is_hod = false;
}

/* Check whether user is authorized to change scores */
$res = & $db->query(
                "SELECT class.ClassIndex FROM class, classterm " .
                 "WHERE class.ClassIndex           = classterm.ClassIndex " .
                 "AND   classterm.ClassTermIndex   = $classtermindex " .
                 "AND   class.ClassTeacherUsername = '$username'");
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
    $is_ct = true;
} else {
    $is_ct = false;
}

/* Check whether user is proofreader */
if ($proof_username == $username) {
    $is_proofreader = true;
} else {
    $is_proofreader = false;
}

if (! $is_ct and ! $is_hod and ! $is_principal and ! $is_admin and
     ! $is_proofreader) {
    /* Print error message */
    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    log_event($LOG_LEVEL_ERROR, "teacher/report/class_modify.php",
            $LOG_DENIED_ACCESS,
            "Tried to modify class report for $student_name.");

    include "footer.php";
    exit(0);
}

$query = "SELECT user.Gender, user.FirstName, user.Surname, " .
         "       classterm.Average, classterm.Conduct, classterm.Effort, " .
         "       classterm.Rank, classterm.CTComment, classterm.HODComment, " .
         "       classterm.CTCommentDone, classterm.HODCommentDone, " .
         "       classterm.PrincipalComment, classterm.PrincipalCommentDone, " .
         "       classterm.PrincipalUsername, classterm.HODUsername, " .
         "       classterm.ReportDone, classterm.Absences, " .
         "       average_index.Display AS AverageDisplay, " .
         "       effort_index.Display AS EffortDisplay, " .
         "       conduct_index.Display AS ConductDisplay " .
         "       FROM user, classlist, classterm " .
         "       LEFT OUTER JOIN nonmark_index AS average_index ON " .
         "            classterm.Average = average_index.NonmarkIndex " .
         "       LEFT OUTER JOIN nonmark_index AS effort_index ON " .
         "            classterm.Effort = effort_index.NonmarkIndex " .
         "       LEFT OUTER JOIN nonmark_index AS conduct_index ON " .
         "            classterm.Conduct = conduct_index.NonmarkIndex " .
         "WHERE classlist.Username       = '$student_username' " .
         "AND   user.Username            = '$student_username' " .
         "AND   classterm.ClassListIndex = classlist.ClassListIndex " .
         "AND   classlist.ClassIndex     = $classindex " .
         "AND   classterm.TermIndex      = $termindex ";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if (! $row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    /* Print error message */
    echo "      <p>$student_name is not in $class.</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";

    include "footer.php";
    exit(0);
}

$student_info = $row;

$prev_uname = "";
$next_uname = "";

$query = "SELECT classlist.Username FROM classterm, classlist, user " .
         "WHERE classlist.ClassListIndex = classterm.ClassListIndex " .
         "AND   classlist.ClassIndex     = $classindex " .
         "AND   classterm.TermIndex      = $termindex " .
         "AND   user.Username            = classlist.Username " .
         "ORDER BY user.FirstName, user.Surname, user.Username";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
    if ($row['Username'] == $student_username) {
        if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            $next_uname = $row['Username'];
        }
        break;
    }
    $prev_uname = $row['Username'];
}

if (! is_null($average_type_index)) {
    $query = "SELECT Input, Display FROM nonmark_index " .
             "WHERE  NonmarkTypeIndex=$average_type_index ";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $input = strtoupper($row['Input']);
        $ainput_array = "'$input'";
        $adisplay_array = "'{$row['Display']}'";
        while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
            $ainput_array .= ", '{$row['Input']}'";
            $adisplay_array .= ", '{$row['Display']}'";
        }
    }
} else {
    $ainput_array = "";
    $adisplay_array = "";
}
if (! is_null($effort_type_index)) {
    $query = "SELECT Input, Display FROM nonmark_index " .
             "WHERE  NonmarkTypeIndex=$effort_type_index ";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $input = strtoupper($row['Input']);
        $einput_array = "'$input'";
        $edisplay_array = "'{$row['Display']}'";
        while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
            $einput_array .= ", '{$row['Input']}'";
            $edisplay_array .= ", '{$row['Display']}'";
        }
    }
} else {
    $einput_array = "";
    $edisplay_array = "";
}
if (! is_null($conduct_type_index)) {
    $query = "SELECT Input, Display FROM nonmark_index " .
             "WHERE  NonmarkTypeIndex=$conduct_type_index ";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $input = strtoupper($row['Input']);
        $cinput_array = "'$input'";
        $cdisplay_array = "'{$row['Display']}'";
        while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
            $cinput_array .= ", '{$row['Input']}'";
            $cdisplay_array .= ", '{$row['Display']}'";
        }
    }
} else {
    $cinput_array = "";
    $cdisplay_array = "";
}

if ($ct_comment_type == $COMMENT_TYPE_MANDATORY or
     $ct_comment_type == $COMMENT_TYPE_OPTIONAL or
     $hod_comment_type == $COMMENT_TYPE_MANDATORY or
     $hod_comment_type == $COMMENT_TYPE_OPTIONAL or
     $pr_comment_type == $COMMENT_TYPE_MANDATORY or
     $pr_comment_type == $COMMENT_TYPE_OPTIONAL) {
    $query = "SELECT CommentIndex, Comment, Strength FROM comment ORDER BY CommentIndex";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    $count = 0;
    if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $comment = str_replace("'", "\'", $row['Comment']);
        $comment = str_replace("\"", "\\\"", $comment);
        if ($row['CommentIndex'] == $count) {
            $comment_array = "'$comment'";
            $cval_array = "'{$row['Strength']}'";
        } else {
            $comment_array = "'($count)'";
            $cval_array = "''";
            $count += 1;
            while ( $row['CommentIndex'] > $count ) {
                $comment_array .= ", '($count)'";
                $cval_array .= ", ''";
                $count += 1;
            }
            $comment_array .= ", '$comment'";
            $cval_array .= ", '{$row['Strength']}'";
        }
        while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
            $comment = str_replace("'", "\'", $row['Comment']);
            $comment = str_replace("\"", "\\\"", $comment);
            $count += 1;
            while ( $row['CommentIndex'] > $count ) {
                $comment_array .= ", '($count)'";
                $cval_array .= ", ''";
                $count += 1;
            }
            $comment_array .= ", '$comment'";
            $cval_array .= ", '{$row['Strength']}'";
        }
    }
}

$query = "SELECT MAX(subject.AverageType) AS MaxAverage, " .
         "       MAX(subject.ConductType) AS MaxConduct, " .
         "       MAX(subject.CommentType) AS MaxComment, " .
         "       MAX(subject.EffortType) AS MaxEffort,  " .
         "       AVG(subjectstudent.CommentValue) AS CommentAverage, " .
         "       MIN(subjectstudent.ReportDone) AS ReportDone " .
         "       FROM subject, subjectstudent " .
         "WHERE subjectstudent.Username      = '$student_username' " .
         "AND   subjectstudent.SubjectIndex  = subject.SubjectIndex " .
         "AND   subject.TermIndex            = $termindex " .
         "AND   subject.YearIndex            = $yearindex " .
         "GROUP BY subjectstudent.Username";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if (! $row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    echo "      <form action='$link' method='post' name='report'>\n"; // Form method
    echo "         <p align='center'>\n";
    if ($prev_uname != "") {
        echo "            <input type='submit' name='student_$prev_uname' value='&lt;&lt;'>&nbsp; \n";
    }
    if ($next_uname != "") {
        echo "            <input type='submit' name='student_$next_uname' value='&gt;&gt;'>&nbsp; \n";
    }

    echo "         </p>\n";

    echo "         <p align='center'>Student isn't in any subjects.</p>\n";
    echo "       </form>\n";
    include "footer.php";
    exit(0);
}

$subject_average_type = $row['MaxAverage'];
$subject_conduct_type = $row['MaxConduct'];
$subject_effort_type = $row['MaxEffort'];
$subject_comment_type = $row['MaxComment'];
$subject_comment_avg = $row['CommentAverage'];
$subject_report_done = $row['ReportDone'];

$query = "SELECT subject.Name AS SubjectName, subject.SubjectIndex, " .
         "       subjectstudent.Average, subjectstudent.Effort, subjectstudent.Conduct, " .
         "       average_index.Display AS AverageDisplay, " .
         "       effort_index.Display AS EffortDisplay, " .
         "       conduct_index.Display AS ConductDisplay, " .
         "       subject.AverageType, subject.EffortType, subject.ConductType, " .
         "       subject.AverageTypeIndex, subject.EffortTypeIndex, " .
         "       subject.ConductTypeIndex, subject.CommentType, " .
         "       subjectstudent.Comment, subjectstudent.CommentValue, " .
         "       subjectstudent.ReportDone " .
         "       FROM subject, subjecttype, subjectstudent " .
         "       LEFT OUTER JOIN nonmark_index AS average_index ON " .
         "            subjectstudent.Average = average_index.NonmarkIndex " .
         "       LEFT OUTER JOIN nonmark_index AS effort_index ON " .
         "            subjectstudent.Effort = effort_index.NonmarkIndex " .
         "       LEFT OUTER JOIN nonmark_index AS conduct_index ON " .
         "            subjectstudent.Conduct = conduct_index.NonmarkIndex " .
         "WHERE subjectstudent.Username      = '$student_username' " .
         "AND   subjectstudent.SubjectIndex  = subject.SubjectIndex " .
         "AND   subject.TermIndex            = $termindex " .
         "AND   subject.YearIndex            = $yearindex " .
         "AND   subjecttype.SubjectTypeIndex = subject.SubjectTypeIndex " .
         "ORDER BY subject.AverageType DESC, subjecttype.Weight DESC, " .
         "         subjecttype.Title, subject.Name, subject.SubjectIndex";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

echo "      <script language='JavaScript' type='text/javascript'>\n";
echo "         var CONDUCT_TYPE_NONE      = $CONDUCT_TYPE_NONE;\n";
echo "         var CONDUCT_TYPE_PERCENT   = $CONDUCT_TYPE_PERCENT;\n";
echo "         var CONDUCT_TYPE_INDEX     = $CONDUCT_TYPE_INDEX;\n";
echo "         var EFFORT_TYPE_NONE       = $EFFORT_TYPE_NONE;\n";
echo "         var EFFORT_TYPE_PERCENT    = $EFFORT_TYPE_PERCENT;\n";
echo "         var EFFORT_TYPE_INDEX      = $EFFORT_TYPE_INDEX;\n";
echo "         var COMMENT_TYPE_NONE      = $COMMENT_TYPE_NONE;\n";
echo "         var COMMENT_TYPE_MANDATORY = $COMMENT_TYPE_MANDATORY;\n";
echo "         var COMMENT_TYPE_OPTIONAL  = $COMMENT_TYPE_OPTIONAL;\n";
echo "         var ABSENCE_TYPE_NONE      = $ABSENCE_TYPE_NONE;\n";
echo "         var ABSENCE_TYPE_NUM       = $ABSENCE_TYPE_NUM;\n";
echo "         var ABSENCE_TYPE_CALC      = $ABSENCE_TYPE_CALC;\n";
echo "\n";
echo "         var average_type           = $average_type;\n";
if ($average_type == $AVG_TYPE_INDEX) {
    echo "         var average_input_array    = new Array($ainput_array);\n";
    echo "         var average_display_array  = new Array($adisplay_array);\n";
}
echo "\n";
echo "         var effort_type            = $effort_type;\n";
if ($effort_type == $EFFORT_TYPE_INDEX) {
    echo "         var effort_input_array     = new Array($einput_array);\n";
    echo "         var effort_display_array   = new Array($edisplay_array);\n";
}
echo "\n";
echo "         var conduct_type           = $conduct_type;\n";
if ($conduct_type == $CONDUCT_TYPE_INDEX) {
    echo "         var conduct_input_array    = new Array($cinput_array);\n";
    echo "         var conduct_display_array  = new Array($cdisplay_array);\n";
}
echo "\n";
echo "         var absence_type           = $absence_type;\n";
echo "\n";
echo "         var ct_comment_type        = $ct_comment_type;\n";
echo "         var hod_comment_type       = $hod_comment_type;\n";
echo "         var pr_comment_type        = $pr_comment_type;\n";
if ($ct_comment_type == $COMMENT_TYPE_MANDATORY or
     $ct_comment_type == $COMMENT_TYPE_OPTIONAL or
     $hod_comment_type == $COMMENT_TYPE_MANDATORY or
     $hod_comment_type == $COMMENT_TYPE_OPTIONAL or
     $pr_comment_type == $COMMENT_TYPE_MANDATORY or
     $pr_comment_type == $COMMENT_TYPE_OPTIONAL) {
    echo "         var comment_array          = new Array($comment_array);\n";
}
echo "         var gender                  = '{$student_info['Gender']}';\n";
echo "         var firstname               = '{$student_info['FirstName']}';\n";
echo "         var fullname                = '{$student_info['FirstName']} {$student_info['Surname']}';\n";

echo "      </script>\n";
echo "      <form action='$link' method='post' name='report'>\n"; // Form method
$gender = strtolower($student_info['Gender']);
echo "         <p align='center'>\n";
if ($prev_uname != "") {
    echo "            <input type='submit' name='student_$prev_uname' value='&lt;&lt;'>&nbsp; \n";
}
if (! $student_info['ReportDone']) {
    echo "            <input type='submit' name='action' value='Update'>&nbsp; \n";
}
if ((($is_hod and $hod_comment_type != $COMMENT_TYPE_NONE and
     ! $student_info['HODCommentDone']) or
     ($is_principal and $pr_comment_type != $COMMENT_TYPE_NONE and
     ! $student_info['PrincipalCommentDone']) or
     ($is_ct and $ct_comment_type != $COMMENT_TYPE_NONE and
     ! $student_info['CTCommentDone'])) and ! $student_info['ReportDone']) {
    echo "            <input type='submit' name='action' value='Finished with comments'>&nbsp; \n";
}
if (($student_info['CTCommentDone'] or
     ($student_info['HODCommentDone'] and ($is_admin or $is_hod or $is_principal)) or
     ($student_info['PrincipalCommentDone'] and ($is_admin or $is_principal))) and
     ! $student_info['ReportDone']) {
    echo "            <input type='submit' name='action' value='Edit comments'>&nbsp; \n";
}
if (($is_hod or $is_principal or $is_admin) and ! $student_info['ReportDone']) {
    echo "            <input type='submit' name='action' value='Close report'>&nbsp; \n";
}
if ($student_info['ReportDone']) {
    echo "            <input type='submit' name='action' value='Open report'>&nbsp; \n";
}
echo "            <input type='submit' name='action' value='Cancel'>\n";
if ($next_uname != "") {
    echo "            <input type='submit' name='student_$next_uname' value='&gt;&gt;'>&nbsp; \n";
}

echo "         </p>\n";

echo "         <table align='center' border='1'>\n"; // Table headers
echo "            <tr>\n";
echo "               <th>Subject</th>\n";
if ($subject_average_type != $AVG_TYPE_NONE) {
    echo "               <th>Average</th>\n";
}
if ($subject_effort_type != $EFFORT_TYPE_NONE) {
    echo "               <th>Effort</th>\n";
}
if ($subject_conduct_type != $CONDUCT_TYPE_NONE) {
    echo "               <th>Conduct</th>\n";
}
if ($subject_comment_type != $COMMENT_TYPE_NONE) {
    echo "               <th>Comment</th>\n";
    echo "               <th>Tone</th>\n";
}
echo "               <th>Finished</th>\n";
if (! $student_info['ReportDone']) {
    echo "               <th>&nbsp;</th>\n";
}

echo "            </tr>\n";

/* For each student, print a row with the student's name and score on each report */
$alt_count = 0;
while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
    $alt_count += 1;

    if ($alt_count % 2 == 0) {
        $alt = " class='alt'";
    } else {
        $alt = " class='std'";
    }

    echo "            <tr$alt id='row_{$row['SubjectIndex']}'>\n";
    echo "               <td>{$row['SubjectName']}</td>\n";
    if ($subject_average_type != $AVG_TYPE_NONE) {
        if ($row['AverageType'] == $AVG_TYPE_NONE) {
            $score = "N/A";
        } elseif ($row['AverageType'] == $AVG_TYPE_PERCENT) {
            if ($row['Average'] == - 1) {
                $score = "&nbsp;";
            } else {
                $score = round($row['Average']);
                $score = "$score%";
            }
        } elseif ($row['AverageType'] == $AVG_TYPE_INDEX or
                 $row['AverageType'] == $AVG_TYPE_GRADE) {
            if (is_null($row['AverageDisplay'])) {
                $score = "&nbsp;";
            } else {
                $score = $row['AverageDisplay'];
            }
        } else {
            $score = "N/A";
        }
        echo "               <td>$score</td>\n";
    }

    if ($subject_effort_type != $EFFORT_TYPE_NONE) {
        if ($row['EffortType'] == $EFFORT_TYPE_NONE) {
            $score = "N/A";
        } elseif ($row['EffortType'] == $EFFORT_TYPE_PERCENT) {
            if ($row['Effort'] == - 1) {
                $score = "&nbsp;";
            } else {
                $score = round($row['Effort']);
                $score = "$score%";
            }
        } elseif ($row['EffortType'] == $EFFORT_TYPE_INDEX) {
            if (is_null($row['EffortDisplay'])) {
                $score = "&nbsp;";
            } else {
                $score = $row['EffortDisplay'];
            }
        } else {
            $score = "N/A";
        }
        echo "               <td>$score</td>\n";
    }

    if ($subject_conduct_type != $CONDUCT_TYPE_NONE) {
        if ($row['ConductType'] == $CONDUCT_TYPE_NONE) {
            $score = "N/A";
        } elseif ($row['ConductType'] == $CONDUCT_TYPE_PERCENT) {
            if ($row['Conduct'] == - 1) {
                $score = "&nbsp;";
            } else {
                $score = round($row['Conduct']);
                $score = "$score%";
            }
        } elseif ($row['ConductType'] == $CONDUCT_TYPE_INDEX) {
            if (is_null($row['ConductDisplay'])) {
                $score = "&nbsp;";
            } else {
                $score = $row['ConductDisplay'];
            }
        } else {
            $score = "N/A";
        }
        echo "               <td>$score</td>\n";
    }

    if ($subject_comment_type != $COMMENT_TYPE_NONE) {
        if ($row['CommentType'] == $COMMENT_TYPE_MANDATORY or
             $row['CommentType'] == $COMMENT_TYPE_OPTIONAL) {
            if (! is_null($row['Comment'])) {
                $commentstr = htmlspecialchars($row['Comment'], ENT_QUOTES);
            } else {
                $commentstr = "";
            }
            $cshow = "&nbsp;";
            if (! is_null($row['CommentValue'])) {
                $cval = round($row['CommentValue']);
                if ($cval == 1) {
                    $cshow = "-";
                } elseif ($cval == 2) {
                    $cshow = "=";
                } elseif ($cval == 3) {
                    $cshow = "+";
                }
            }
            echo "               <td>$commentstr</td>\n";
            echo "               <td>$cshow</td>\n";
        } else {
            echo "               <td colspan='2'>N/A</td>\n";
        }
        if ($row['ReportDone'] == 0) {
            echo "               <td><b>No</b></td>\n";
        } else {
            echo "               <td><i>Yes</i></td>\n";
        }
        if (! $student_info['ReportDone']) {
            echo "               <td><input type='submit' name='edit_{$row['SubjectIndex']}' value='Change'></td>\n";
        }
    }
    echo "            </tr>\n";
}
echo "         </table>\n"; // End of table

echo "         <table class='transparent' align='center' width=600px>\n";
if ($average_type != $CLASS_AVG_TYPE_NONE) {
    echo "            <tr>\n";
    echo "               <td>Average:</td>\n";

    /* Check for type of average mark and put in appropriate information */
    if ($average_type != $CLASS_AVG_TYPE_NONE) {
        if ($average_type == $CLASS_AVG_TYPE_PERCENT) {
            if (isset($_POST["average"])) {
                $scorestr = $_POST["average"];
                if (strval(intval($scorestr)) != $scorestr) {
                    $score = "N/A";
                } elseif (intval($scorestr) > 100) {
                    $score = "100%";
                } elseif (intval($scorestr) < 0) {
                    $score = "0%";
                } else {
                    $score = "$scorestr%";
                }
            } else {
                if ($student_info['Average'] == - 1) {
                    $scorestr = "";
                    $score = "N/A";
                } else {
                    $scorestr = round($student_info['Average']);
                    $score = "$scorestr%";
                }
            }
        } elseif ($average_type == $CLASS_AVG_TYPE_INDEX) {
            if (isset($_POST["average"])) {
                $scorestr = safe($_POST["average"]);
                $query = "SELECT Display FROM nonmark_index " .
                         "WHERE Input='$scorestr' " .
                         "AND   NonmarkTypeIndex=$average_type_index";
                $nres = & $db->query($query);
                if (DB::isError($nres))
                    die($nres->getDebugInfo());

                if ($nrow = & $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
                    $score = $nrow['Display'];
                } else {
                    $score = "N/A";
                }
            } else {
                $scoreindex = $student_info['Average'];
                $query = "SELECT Input, Display FROM nonmark_index " .
                         "WHERE NonmarkIndex=$scoreindex";
                $nres = & $db->query($query);
                if (DB::isError($nres))
                    die($nres->getDebugInfo());

                if ($nrow = & $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
                    $scorestr = $nrow['Input'];
                    $score = $nrow['Display'];
                } else {
                    $scorestr = "";
                    $score = "N/A";
                }
            }
        } elseif ($average_type == $CLASS_AVG_TYPE_CALC) {
            if ($student_info['Average'] == - 1) {
                $score = "N/A";
            } else {
                $scorestr = round($student_info['Average']);
                $score = "$scorestr%";
            }
        } else {
            $score = "N/A";
        }
        if (($average_type == $CLASS_AVG_TYPE_INDEX or
             $average_type == $CLASS_AVG_TYPE_PERCENT) and
             ! $student_info['ReportDone']) {
            echo "               <td><input type='text' name='average' " .
             "id='average' value='$scorestr' size='4' onChange='recalc_avg();'> = <label name='aavg' id='aavg' for='average'>$score</label</td>\n";
        } else {
            echo "               <td>$score</td>\n";
        }
    }
    echo "            </tr>\n";
}
if ($effort_type != $CLASS_EFFORT_TYPE_NONE) {
    echo "            <tr>\n";
    echo "               <td>Effort:</td>\n";

    /* Check for type of effort mark and put in appropriate information */
    if ($effort_type != $CLASS_EFFORT_TYPE_NONE) {
        if ($effort_type == $CLASS_EFFORT_TYPE_PERCENT) {
            if (isset($_POST["effort"])) {
                $scorestr = $_POST["effort"];
                if (strval(intval($scorestr)) != $scorestr) {
                    $score = "N/A";
                } elseif (intval($scorestr) > 100) {
                    $score = "100%";
                } elseif (intval($scorestr) < 0) {
                    $score = "0%";
                } else {
                    $score = "$scorestr%";
                }
            } else {
                if ($student_info['Effort'] == - 1) {
                    $scorestr = "";
                    $score = "N/A";
                } else {
                    $scorestr = round($student_info['Effort']);
                    $score = "$scorestr%";
                }
            }
        } elseif ($effort_type == $CLASS_EFFORT_TYPE_INDEX) {
            if (isset($_POST["effort"])) {
                $scorestr = safe($_POST["effort"]);
                $query = "SELECT Display FROM nonmark_index " .
                         "WHERE Input='$scorestr' " .
                         "AND   NonmarkTypeIndex=$effort_type_index";
                $nres = & $db->query($query);
                if (DB::isError($nres))
                    die($nres->getDebugInfo());

                if ($nrow = & $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
                    $score = $nrow['Display'];
                } else {
                    $score = "N/A";
                }
            } else {
                $scoreindex = $student_info['Effort'];
                $query = "SELECT Input, Display FROM nonmark_index " .
                         "WHERE NonmarkIndex=$scoreindex";
                $nres = & $db->query($query);
                if (DB::isError($nres))
                    die($nres->getDebugInfo());

                if ($nrow = & $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
                    $scorestr = $nrow['Input'];
                    $score = $nrow['Display'];
                } else {
                    $scorestr = "";
                    $score = "N/A";
                }
            }
        } elseif ($effort_type == $CLASS_EFFORT_TYPE_CALC) {
            if ($student_info['Effort'] == - 1) {
                $score = "N/A";
            } else {
                $scorestr = round($student_info['Effort']);
                $score = "$scorestr%";
            }
        } else {
            $score = "N/A";
        }
        if (($effort_type == $CLASS_EFFORT_TYPE_INDEX or
             $effort_type == $CLASS_EFFORT_TYPE_PERCENT) and
             ! $student_info['ReportDone']) {
            echo "               <td><input type='text' name='effort' " .
             "id='effort' value='$scorestr' size='4' onChange='recalc_effort();'> = <label name='eavg' id='eavg' for='effort'>$score</label</td>\n";
        } else {
            echo "               <td>$score</td>\n";
        }
    }
    echo "            </tr>\n";
}
if ($conduct_type != $CLASS_CONDUCT_TYPE_NONE) {
    echo "            <tr>\n";
    echo "               <td>Conduct:</td>\n";

    /* Check for type of conduct mark and put in appropriate information */
    if ($conduct_type != $CLASS_CONDUCT_TYPE_NONE) {
        if ($conduct_type == $CLASS_CONDUCT_TYPE_PERCENT) {
            if (isset($_POST["conduct"])) {
                $scorestr = $_POST["conduct"];
                if (strval(intval($scorestr)) != $scorestr) {
                    $score = "N/A";
                } elseif (intval($scorestr) > 100) {
                    $score = "100%";
                } elseif (intval($scorestr) < 0) {
                    $score = "0%";
                } else {
                    $score = "$scorestr%";
                }
            } else {
                if ($student_info['Conduct'] == - 1) {
                    $scorestr = "";
                    $score = "N/A";
                } else {
                    $scorestr = round($student_info['Conduct']);
                    $score = "$scorestr%";
                }
            }
        } elseif ($conduct_type == $CLASS_CONDUCT_TYPE_INDEX) {
            if (isset($_POST["conduct"])) {
                $scorestr = safe($_POST["conduct"]);
                $query = "SELECT Display FROM nonmark_index " .
                         "WHERE Input='$scorestr' " .
                         "AND   NonmarkTypeIndex=$conduct_type_index";
                $nres = & $db->query($query);
                if (DB::isError($nres))
                    die($nres->getDebugInfo());

                if ($nrow = & $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
                    $score = $nrow['Display'];
                } else {
                    $score = "N/A";
                }
            } else {
                $scoreindex = $student_info['Conduct'];
                $query = "SELECT Input, Display FROM nonmark_index " .
                         "WHERE NonmarkIndex=$scoreindex";
                $nres = & $db->query($query);
                if (DB::isError($nres))
                    die($nres->getDebugInfo());

                if ($nrow = & $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
                    $scorestr = $nrow['Input'];
                    $score = $nrow['Display'];
                } else {
                    $scorestr = "";
                    $score = "N/A";
                }
            }
        } elseif ($conduct_type == $CLASS_CONDUCT_TYPE_CALC) {
            if ($student_info['Conduct'] == - 1) {
                $score = "N/A";
            } else {
                $scorestr = round($student_info['Conduct']);
                $score = "$scorestr%";
            }
        } elseif ($conduct_type == $CLASS_CONDUCT_TYPE_PUN) {
            $query = "SELECT Score FROM conduct_mark " .
                     "WHERE YearIndex = $yearindex " .
                     "AND   TermIndex = $termindex " .
                     "AND   Username = '$student_username'";
            $nres = & $db->query($query);
            if (DB::isError($nres))
                die($nres->getDebugInfo());

            if ($nrow = & $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
                $scorestr = round($nrow['Score']);
                $score = "$scorestr%";
            } else {
                $score = "N/A";
            }
        } else {
            $score = "N/A";
        }
        if (($conduct_type == $CLASS_CONDUCT_TYPE_INDEX or
             $conduct_type == $CLASS_CONDUCT_TYPE_PERCENT) and
             ! $student_info['ReportDone']) {
            echo "               <td><input type='text' name='conduct' " .
             "id='conduct' value='$scorestr' size='4' onChange='recalc_conduct();'> = <label name='cavg' id='cavg' for='conduct'>$score</label</td>\n";
        } else {
            echo "               <td>$score</td>\n";
        }
    }
    echo "            </tr>\n";
}
if ($absence_type != $ABSENCE_TYPE_NONE) {
    echo "            <tr>\n";
    echo "               <td>Absences:</td>\n";

    /* Check for type of absences mark and put in appropriate information */
    if ($absence_type != $ABSENCE_TYPE_NONE) {
        if ($absence_type == $ABSENCE_TYPE_NUM) {
            if (isset($_POST["absences"])) {
                $scorestr = $_POST["absences"];
                if (strval(intval($scorestr)) != $scorestr) {
                    $score = "N/A";
                } elseif (intval($scorestr) < 0) {
                    $score = "0";
                } else {
                    $score = "$scorestr";
                }
            } else {
                if ($student_info['Absences'] == - 1) {
                    $scorestr = "";
                    $score = "N/A";
                } else {
                    $scorestr = $student_info['Absences'];
                    $score = "$scorestr";
                }
            }
        } else {
            $score = "N/A";
        }
        if (($absence_type == $ABSENCE_TYPE_NUM) and
             ! $student_info['ReportDone']) {
            echo "               <td><input type='text' name='absences' " .
             "id='absences' value='$scorestr' size='4' onChange='recalc_absences();'> = <label name='abavg' id='abavg' for='absences'>$score</label</td>\n";
        } else {
            echo "               <td>$score</td>\n";
        }
    }
    echo "            </tr>\n";
}

if ($ct_comment_type != $COMMENT_TYPE_NONE) {
    echo "            <tr>\n";
    echo "               <td colspan='2'><b>Class Teacher's comment:</b><br>\n";
    if (isset($_POST["ct_comment"])) {
        $commentstr = htmlspecialchars($_POST["ct_comment"], ENT_QUOTES);
    } else {
        $commentstr = htmlspecialchars($student_info['CTComment'], ENT_QUOTES);
    }
    if (($ct_comment_type == $COMMENT_TYPE_MANDATORY or
         $ct_comment_type == $COMMENT_TYPE_OPTIONAL) and
         ! $student_info['ReportDone'] and ! $student_info['CTCommentDone']) {
        echo "               <textarea name='ct_comment' " .
         "id='ct_comment' rows='5' cols='80' " .
         "onChange='recalc_comment(&quot;ct&quot;);'>$commentstr</textarea>\n";
    } else {
        echo "               $commentstr\n";
    }
    echo "               </td>\n";
    echo "            </tr>\n";
}
if ($hod_comment_type != $COMMENT_TYPE_NONE) {
    echo "            <tr>\n";
    echo "               <td colspan='2'><b>Head of Department's comment:</b><br>\n";
    if (isset($_POST["hod_comment"])) {
        $commentstr = htmlspecialchars($_POST["hod_comment"], ENT_QUOTES);
    } else {
        $commentstr = htmlspecialchars($student_info['HODComment'], ENT_QUOTES);
    }
    if (($hod_comment_type == $COMMENT_TYPE_MANDATORY or
         $hod_comment_type == $COMMENT_TYPE_OPTIONAL) and
         ($is_admin or $is_hod or $is_principal) and
         ! $student_info['ReportDone'] and ! $student_info['HODCommentDone']) {
        echo "               <textarea name='hod_comment' " .
         "id='hod_comment' rows='5' cols='80' " .
         "onChange='recalc_comment(&quot;hod&quot;);'>$commentstr</textarea>\n";
    } else {
        echo "               $commentstr\n";
    }
    echo "               </td>\n";
    echo "            </tr>\n";
}
if ($pr_comment_type != $COMMENT_TYPE_NONE) {
    echo "            <tr>\n";
    echo "               <td colspan='2'><b>Principal's comment:</b><br>\n";
    if (isset($_POST["pr_comment"])) {
        $commentstr = htmlspecialchars($_POST["pr_comment"], ENT_QUOTES);
    } else {
        $commentstr = htmlspecialchars($student_info['PrincipalComment'],
                                    ENT_QUOTES);
    }
    if (($pr_comment_type == $COMMENT_TYPE_MANDATORY or
         $pr_comment_type == $COMMENT_TYPE_OPTIONAL) and
         ($is_admin or $is_principal) and ! $student_info['ReportDone'] and
         ! $student_info['PrincipalCommentDone']) {
        echo "               <textarea name='pr_comment' " .
         "id='pr_comment' rows='5' cols='80' " .
         "onChange='recalc_comment(&quot;pr&quot;);'>$commentstr</textarea>\n";
    } else {
        echo "               $commentstr\n";
    }
    echo "               </td>\n";
    echo "            </tr>\n";
}
echo "            </tr>\n";
echo "         </table>\n";
echo "         <p></p>\n";

echo "         <p align='center'>\n";
if ($prev_uname != "") {
    echo "            <input type='submit' name='student_$prev_uname' value='&lt;&lt;'>&nbsp; \n";
}
if (! $student_info['ReportDone']) {
    echo "            <input type='submit' name='action' value='Update'>&nbsp; \n";
}
if ((($is_hod and $hod_comment_type != $COMMENT_TYPE_NONE and
     ! $student_info['HODCommentDone']) or
     ($is_principal and $pr_comment_type != $COMMENT_TYPE_NONE and
     ! $student_info['PrincipalCommentDone']) or
     ($is_ct and $ct_comment_type != $COMMENT_TYPE_NONE and
     ! $student_info['CTCommentDone'])) and ! $student_info['ReportDone']) {
    echo "            <input type='submit' name='action' value='Finished with comments'>&nbsp; \n";
}
if (($student_info['CTCommentDone'] or $student_info['HODCommentDone'] or
     $student_info['PrincipalCommentDone']) and ! $student_info['ReportDone']) {
    echo "            <input type='submit' name='action' value='Edit comments'>&nbsp; \n";
}
if (($is_hod or $is_principal or $is_admin) and ! $student_info['ReportDone']) {
    echo "            <input type='submit' name='action' value='Close report'>&nbsp; \n";
}
if ($student_info['ReportDone']) {
    echo "            <input type='submit' name='action' value='Open report'>&nbsp; \n";
}
echo "            <input type='submit' name='action' value='Cancel'>\n";
if ($next_uname != "") {
    echo "            <input type='submit' name='student_$next_uname' value='&gt;&gt;'>&nbsp; \n";
}

echo "         </p>\n";

echo "         </p>\n";
echo "       </form>\n";
include "footer.php";
?>
