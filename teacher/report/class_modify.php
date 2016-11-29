<?php
/**
 * ***************************************************************
 * teacher/report/class_modify.php (c) 2008, 2016 Jonathan Dieter
 *
 * Show subject conduct, effort, average and comment for report
 * Change class conduct, effort, average and commentsd
 * ***************************************************************
 */

/* Get variables */
if (! isset($_GET['next']))
    $_GET['next'] = dbfuncString2Int($backLink);
$class = dbfuncInt2String($_GET['keyname']);
$student_name = dbfuncInt2String($_GET['keyname2']);
if (isset($_GET['showonly']) and dbfuncInt2String($_GET['showonly']) == "1") {
    $show_only = true;
}
if (! $show_only) {
    $title = "Report for " . $student_name;
} else {
    $title = $student_name;
}
$classtermindex = safe(dbfuncInt2String($_GET['key']));
$student_username = safe(dbfuncInt2String($_GET['key2']));

$link = "index.php?location=" .
         dbfuncString2Int("teacher/report/class_modify_action.php") . "&amp;key=" .
         $_GET['key'] . "&amp;key2=" . $_GET['key2'] . "&amp;keyname=" .
         $_GET['keyname'] . "&amp;keyname2=" . $_GET['keyname2'] . "&amp;next=" .
         $_GET['next'];
if (isset($_GET['key3']))
    $link .= "&amp;key3=" . $_GET['key3'];

$extra_js = "class_report.js";

include "core/settermandyear.php";
if (isset($_GET['key3']))
    $termindex = safe(dbfuncInt2String($_GET['key3']));
include "header.php"; // Show header

/* Check whether subject is open for report editing */
$query = "SELECT classterm.AverageType, classterm.EffortType, classterm.ConductType, " .
         "       classterm.AverageTypeIndex, classterm.EffortTypeIndex, " .
         "       classterm.ConductTypeIndex, classterm.CTCommentType, " .
         "       classterm.HODCommentType, classterm.PrincipalCommentType, " .
         "       classterm.CanDoReport, classterm.AbsenceType, " .
         "       MIN(classlist.ReportDone) AS ReportDone," .
         "       class.ClassIndex " . "       FROM classterm, classlist, class " .
         "WHERE classterm.ClassTermIndex = $classtermindex " .
         "AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
         "AND   class.ClassIndex = classterm.ClassIndex " .
         "GROUP BY classterm.ClassIndex";
$res = & $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if (! $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) or
     (! $row['CanDoReport'] and ! $row['ReportDone'])) {
    /* Print error message */
    $noJS = true;
    $noHeaderLinks = true;
    include "header.php"; // Show header

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
$proof_username = $row['ProofreaderUsername'];
$class_index = $row['ClassIndex'];

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
                 "WHERE classterm.ClassTermIndex  = $classtermindex " .
                 "AND   classterm.ClassIndex = class.ClassIndex " .
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

/*
 * update_classterm($classindex, $termindex);
 * update_conduct_input($classindex, $termindex);
 */

$query = "SELECT user.Gender, user.FirstName, user.Surname, user.Username, " .
         "       newmem.Username AS New, specialmem.Username AS Special, " .
         "       classlist.Average, classlist.Conduct, classlist.Effort, " .
         "       classlist.Rank, classlist.CTComment, classlist.HODComment, " .
         "       classlist.CTCommentDone, classlist.HODCommentDone, " .
         "       classlist.PrincipalComment, classlist.PrincipalCommentDone, " .
         "       classlist.PrincipalUsername, classlist.HODUsername, " .
         "       classlist.ReportDone, classlist.ReportProofread, " .
         "       classlist.ReportPrinted, classlist.Absences, " .
         "       classlist.ReportProofDone, classterm.Average AS ClassAverage, " .
         "       classterm.Conduct AS ClassConduct, classterm.Effort AS ClassEffort, " .
         "       average_index.Display AS AverageDisplay, " .
         "       effort_index.Display AS EffortDisplay, " .
         "       conduct_index.Display AS ConductDisplay " .
         "       FROM user, classterm, classlist " .
         "       LEFT OUTER JOIN nonmark_index AS average_index ON " .
         "            classlist.Average = average_index.NonmarkIndex " .
         "       LEFT OUTER JOIN nonmark_index AS effort_index ON " .
         "            classlist.Effort = effort_index.NonmarkIndex " .
         "       LEFT OUTER JOIN nonmark_index AS conduct_index ON " .
         "            classlist.Conduct = conduct_index.NonmarkIndex " .
         "       LEFT OUTER JOIN (groupgenmem AS newmem INNER JOIN " .
         "                        groups AS newgroups ON (newgroups.GroupID=newmem.GroupID " .
         "                                                AND newgroups.GroupTypeID='new' " .
         "                                                AND newgroups.YearIndex=$yearindex)) ON (classlist.Username=newmem.Username) " .
         "       LEFT OUTER JOIN (groupgenmem AS specialmem INNER JOIN " .
         "                        groups AS specgroups ON (specgroups.GroupID=specialmem.GroupID " .
         "                                                 AND specgroups.GroupTypeID='special' " .
         "                                                 AND specgroups.YearIndex=$yearindex)) ON (classlist.Username=specialmem.Username) " .
         "WHERE user.Username            = classlist.Username " .
         "AND   classlist.ClassTermIndex = $classtermindex " .
         "AND   classterm.ClassTermIndex = classlist.ClassTermIndex " .
         "AND   classlist.Username       = '$student_username' " .
         "ORDER BY user.FirstName, user.Surname, user.Username";

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

$query = "";
if ($is_proofreader) {
    $query .= "(SELECT user.Username, user.FirstName, user.Surname, class.Grade, " .
             "        class.ClassName, term.TermNumber " .
             "        FROM department, user, classlist, class, classterm, term " .
             " WHERE user.Username                  = classlist.Username " .
             " AND   classlist.ClassTermIndex       = classterm.ClassTermIndex " .
             " AND   classlist.ReportProofread      = 1 " .
             " AND   classlist.ReportProofDone      = 0 " .
             " AND   classterm.CanDoReport          = 1 " .
             " AND   class.ClassIndex               = classterm.ClassIndex " .
             " AND   term.TermIndex                 = classterm.TermIndex " .
             " AND   department.DepartmentIndex     = class.DepartmentIndex " .
             " AND   department.ProofreaderUsername = '$username') ";
}
if ($is_proofreader and ($is_ct or $is_hod or $is_principal or $is_admin)) {
    $query .= "UNION ";
}
if ($is_ct or $is_hod or $is_principal or $is_admin) {
    $query = "(SELECT user.Username, user.FirstName, user.Surname, class.Grade, " .
         "         class.ClassName, term.TermNumber " .
         "         FROM classterm, classlist, class, term, user " .
         " WHERE classlist.ClassTermIndex = $classtermindex " .
         " AND   classterm.ClassTermIndex = classlist.ClassTermIndex " .
         " AND   class.ClassIndex         = classterm.ClassIndex " .
         " AND   user.Username            = classlist.Username " .
         " AND   term.TermIndex           = classterm.TermIndex) ";
}
$query .= "ORDER BY TermNumber, Grade, ClassName, FirstName, Surname, Username";

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
            $input = strtoupper($row['Input']);
            $ainput_array .= ", '$input'";
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
        $comment = htmlspecialchars($row['Comment'], ENT_QUOTES);
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

$query = "SELECT class.ClassName, class.Grade FROM class, classterm, classlist " .
         "WHERE classlist.Username       = '$student_username' " .
         "AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
         "AND   classterm.TermIndex      = $termindex " .
         "AND   classterm.ClassIndex     = class.ClassIndex " .
         "AND   class.YearIndex          = $yearindex ";

$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    $grade = $row['Grade'];
    $classname = $row['ClassName'];
} else {
    $grade = - 1;
    $classname = "";
}

$rpt_sentence = "";
$query = "SELECT Grade, ClassCount FROM " .
         "  (SELECT class.Grade, COUNT(DISTINCT class.YearIndex) AS ClassCount " .
         "          FROM class, classterm, classlist " .
         "   WHERE classlist.Username = '$student_username' " .
         "   AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
         "   AND   classterm.ClassIndex     = class.ClassIndex " .
         "   GROUP BY Grade) AS classcount " . "WHERE ClassCount > 1";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    $rpt_sentence = "<p class='error' align='center'>{$student_info['FirstName']} has repeated class {$row['Grade']}";
    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        $rpt_sentence .= " and {$row['Grade']}";
    }
    $rpt_sentence .= ".</p>";
}

$new_sentence = "";
if (!is_null($student_info['New'])) {
    $new_sentence = "<p align='center'>{$student_info['FirstName']} is a new student.";
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
    if ($can_do_report) {
        echo "      <form action='$link' method='post' name='report'>\n"; // Form method
        echo "         <p align='center'>\n";
        if ($prev_uname != "") {
            echo "            <input type='submit' name='student_$prev_uname' value='&lt;&lt;'>&nbsp; \n";
        }
        if ($next_uname != "") {
            echo "            <input type='submit' name='student_$next_uname' value='&gt;&gt;'>&nbsp; \n";
        }

        echo "         </p>\n";
    } else {
        $nochangeyt = true;
        include "core/titletermyear.php";
    }

    echo "         <p align='center'>Student isn't in any subjects.</p>\n";
    if ($can_do_report)
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

$query = "SELECT COUNT(TermIndex) AS TermCount, MIN(TermNumber) AS LowTerm, " .
         "       MAX(TermNumber) AS CurrentTermNumber, " .
         "       DepartmentIndex AS DepartmentIndex " . "FROM (" .
         " SELECT subject.TermIndex, 1 AS TGroup, term.TermNumber, term.DepartmentIndex FROM " .
         "        subject, subjectstudent, term, term AS depterm " .
         " WHERE  subjectstudent.Username = '$student_username' " .
         " AND    subject.SubjectIndex    = subjectstudent.SubjectIndex " .
         " AND    subject.YearIndex       = $yearindex " .
         " AND    subject.TermIndex       = term.TermIndex " .
         " AND    subject.ShowInList      = 1 " .
         " AND   (subject.AverageType != $AVG_TYPE_NONE OR subject.EffortType != $EFFORT_TYPE_NONE OR subject.ConductType != $CONDUCT_TYPE_NONE OR subject.CommentType != $COMMENT_TYPE_NONE) " .
         " AND    term.DepartmentIndex    =  depterm.DepartmentIndex " .
         " AND    term.TermNumber         <= depterm.TermNumber " .
         " AND    depterm.TermIndex       =  $termindex " .
         " GROUP BY subject.TermIndex) AS SubList " . "GROUP BY TGroup";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    $termcount = $row['TermCount'];
    $lowtermnumber = $row['LowTerm'];
    $termnumber = $row['CurrentTermNumber'];
    $departmentindex = $row['DepartmentIndex'];
} else {
    $termcount = 0;
}

$query =        "SELECT subject.Name AS SubjectName, subject.SubjectIndex, " .
                "       subject.Average AS SubjectAverage, " .
                "       subject.AverageType, subject.EffortType, subject.ConductType, " .
                "       subject.AverageTypeIndex, subject.EffortTypeIndex, " .
                "       subject.ConductTypeIndex, subject.CommentType, " .
                "       subjectstudent.Average, subjectstudent.Effort, subjectstudent.Conduct, " .
                "       average_index.Display AS AverageDisplay, " .
                "       effort_index.Display AS EffortDisplay, " .
                "       conduct_index.Display AS ConductDisplay, " .
                "       subjectstudent.Comment, subjectstudent.CommentValue, " .
                "       subjectstudent.ReportDone, " .
                "       get_weight(subject.SubjectIndex, $class_index, '$student_username') AS SubjectWeight " .
                "       FROM subject, subjecttype, ";
if ($is_admin or $is_ct or $is_hod or $is_principal) {
    $query .=   "         (SELECT subject.Name AS SubjectName FROM subjectstudent, subject, term, term AS currentterm " .
                "       WHERE subjectstudent.Username = '$student_username' " .
                "       AND   subjectstudent.SubjectIndex  = subject.SubjectIndex " .
                "       AND   subject.TermIndex            = term.TermIndex " .
                "       AND   term.TermNumber              <= currentterm.TermNumber " .
                "       AND   term.DepartmentIndex         = $departmentindex " .
                "       AND   currentterm.TermIndex        = $termindex " .
                "       AND   subject.YearIndex            = $yearindex " .
                "       AND   subject.ShowInList           = 1 " .
                "       GROUP BY subject.Name) AS tempsubjectlist, subjectstudent " .
                "       LEFT OUTER JOIN nonmark_index AS average_index ON " .
                "            subjectstudent.Average = average_index.NonmarkIndex " .
                "       LEFT OUTER JOIN nonmark_index AS effort_index ON " .
                "            subjectstudent.Effort = effort_index.NonmarkIndex " .
                "       LEFT OUTER JOIN nonmark_index AS conduct_index ON " .
                "            subjectstudent.Conduct = conduct_index.NonmarkIndex " .
                "WHERE subject.Name = tempsubjectlist.SubjectName " .
                "AND   subjectstudent.Username      = '$student_username' " .
                "AND   subjectstudent.SubjectIndex  = subject.SubjectIndex " .
                "AND   subject.YearIndex = $yearindex " .
                "AND   subject.TermIndex = $termindex ";
} else {
    $query .=   "       subjectstudent " .
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
                "AND   subject.ShowInList           = 1 ";
}
$query .=       "AND   (subject.AverageType != $AVG_TYPE_NONE " .
                "       OR subject.EffortType != $EFFORT_TYPE_NONE " .
                "       OR subject.ConductType != $CONDUCT_TYPE_NONE " .
                "       OR subject.CommentType != $COMMENT_TYPE_NONE) " .
                "AND   subjecttype.SubjectTypeIndex = subject.SubjectTypeIndex " .
                "ORDER BY subjecttype.HighPriority DESC, " .
                "         get_weight(subject.SubjectIndex, $class_index, '$student_username') DESC, " .
                "         subjecttype.Title, subject.Name, subject.TermIndex DESC, subject.SubjectIndex ";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

$gender = strtolower($student_info['Gender']);

if ($can_do_report and ! $show_only) {
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
    $firstname = htmlspecialchars($student_info['FirstName'], ENT_QUOTES);
    $surname = htmlspecialchars($student_info['Surname'], ENT_QUOTES);
    echo "         var gender                  = '{$student_info['Gender']}';\n";
    echo "         var firstname               = '$firstname';\n";
    echo "         var fullname                = '$firstname $surname';\n";
    echo "         var grade                   = '$grade';\n";
    echo "      </script>\n";

    echo "      <form action='$link' method='post' name='report'>\n"; // Form method

    echo "         <p align='center'>\n";
    if ($prev_uname != "") {
        echo "            <input type='hidden' name='studentprev' value='$prev_uname'>\n";
        echo "            <input type='submit' name='student_$prev_uname' value='&lt;&lt;'>&nbsp; \n";
    }
    if (! $student_info['ReportDone']) {
        echo "            <input type='submit' name='action' value='Update'>&nbsp; \n";
    }
    if ((($is_hod and $hod_comment_type != $COMMENT_TYPE_NONE and
         ! $student_info['HODCommentDone']) or ($is_principal and
         $pr_comment_type != $COMMENT_TYPE_NONE and
         ! $student_info['PrincipalCommentDone']) or ($is_ct and
         $ct_comment_type != $COMMENT_TYPE_NONE and
         ! $student_info['CTCommentDone'])) and ! $student_info['ReportDone']) {
        echo "            <input type='submit' name='action' value='Finished with comments'>&nbsp; \n";
    }
    if (($student_info['CTCommentDone'] or
         ($student_info['HODCommentDone'] and
         ($is_admin or $is_hod or $is_principal or $is_proofreader)) or
         ($student_info['PrincipalCommentDone'] and
         ($is_admin or $is_principal or $is_proofreader))) and
         ! $student_info['ReportDone']) {
        echo "            <input type='submit' name='action' value='Edit comments'>&nbsp; \n";
    }
    if (($is_hod or $is_principal or $is_admin) and ! $student_info['ReportDone']) {
        echo "            <input type='submit' name='action' value='Close report'>&nbsp; \n";
    }
    if ($student_info['ReportDone'] and ($is_admin or $is_principal)) {
        echo "            <input type='submit' name='action' value='Open report'>&nbsp; \n";
    }
    if ($is_proofreader) {
        echo "            <input type='submit' name='action' value='Done with report'>&nbsp; \n";
    }
    echo "            <input type='submit' name='action' value='Cancel'>\n";
    if ($next_uname != "") {
        echo "            <input type='submit' name='student_$next_uname' value='&gt;&gt;'>&nbsp; \n";
        echo "            <input type='hidden' name='studentnext' value='$next_uname'>\n";
    }

    echo "         </p>\n";
} else {
    $nochangeyt = true;
    include "core/titletermyear.php";
}

$colcount = 0;
echo $rpt_sentence;
echo $new_sentence;
echo "         <table align='center' border='1'>\n"; // Table headers
echo "            <tr>\n";
echo "               <th>Subject</th>\n";
if ($is_ct or $is_admin or $is_principal or $is_hod) {
    if ($subject_average_type != $AVG_TYPE_NONE) {
        echo "               <th>Weight</th>\n";
        $query = "SELECT TermName, get_term_weight(TermIndex, $class_index, '$student_username') AS Weight FROM term " .
                 "WHERE TermNumber >= $lowtermnumber " .
                 "AND   TermNumber <= $termnumber " .
                 "AND   DepartmentIndex = $departmentindex " .
                 "ORDER BY term.TermNumber ASC";
        $nres = &  $db->query($query);
        if (DB::isError($nres))
            die($nres->getDebugInfo()); // Check for errors in query
        while ( $nrow = & $nres->fetchRow(DB_FETCHMODE_ASSOC) ) {
            $name = htmlentities(
                                "{$nrow['TermName']} - Weight {$nrow['Weight']}");
            $pname = htmlentities(substr($nrow['TermName'], 0, 1));
            echo "               <th><a title='$name'>$pname</a></th>\n";
        }
        echo "               <th><a title='Average'>A</a></th>\n";
    }
    if (! $show_only) {
        if ($subject_effort_type != $EFFORT_TYPE_NONE) {
            echo "               <th>Effort</th>\n";
            $colcount += 1;
        }
        if ($subject_conduct_type != $CONDUCT_TYPE_NONE) {
            echo "               <th>Conduct</th>\n";
            $colcount += 1;
        }
    }
}
if (! $show_only) {
    if ($subject_comment_type != $COMMENT_TYPE_NONE) {
        echo "               <th>Comment</th>\n";
        echo "               <th>Tone</th>\n";
        $colcount += 2;
    }
    echo "               <th>Finished</th>\n";
    $colcount += 1;
    if (! $student_info['ReportDone']) {
        echo "               <th>&nbsp;</th>\n";
        $colcount += 1;
    }
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
    echo "               <td nowrap>{$row['SubjectName']}</td>\n";

    if ($subject_average_type != $AVG_TYPE_NONE) {
        echo "               <td nowrap>{$row['SubjectWeight']}</td>\n";
    }

    if ($is_admin or $is_ct or $is_hod or $is_principal) {
        if ($subject_average_type != $AVG_TYPE_NONE) {
            $subject_name = safe($row['SubjectName']);
            $query = "SELECT subject.AverageType, subject.AverageTypeIndex, subjectstudent.Average, " .
                     "       subject.EffortType, subject.EffortTypeIndex, subjectstudent.Effort, " .
                     "       subject.ConductType, subject.ConductTypeIndex, subjectstudent.Conduct, " .
                     "       subject.CommentType, subjectstudent.Comment, subject.Average AS SubjectAverage, " .
                     "       average_index.Display AS AverageDisplay, " .
                     "       effort_index.Display AS EffortDisplay, " .
                     "       conduct_index.Display AS ConductDisplay, " .
                     "      subject.TermIndex, term.TermNumber, " .
                     "       get_term_weight(term.TermIndex, $class_index, '$student_username') AS Weight " .
                     " FROM " . " (term INNER JOIN term AS depterm " .
                     "       ON  term.DepartmentIndex = depterm.DepartmentIndex" .
                     "       AND depterm.TermIndex = $termindex" .
                     "       AND term.TermNumber <= depterm.TermNumber " .
                     "       AND term.TermNumber >= $lowtermnumber) " .
                     " INNER JOIN class ON (class.ClassIndex = $class_index) " .
                     " LEFT OUTER JOIN " . " (subjectstudent INNER JOIN subject " .
                     "       ON  subjectstudent.Username = '$student_username' " .
                     "       AND subjectstudent.SubjectIndex = subject.SubjectIndex " .
                     "       AND subject.YearIndex = $yearindex " .
                     "       AND subject.Name = '$subject_name' " .
                     "       AND subject.ShowInList = 1 " .
                     "       AND (subject.AverageType != $AVG_TYPE_NONE OR subject.EffortType != $EFFORT_TYPE_NONE OR subject.ConductType != $CONDUCT_TYPE_NONE OR subject.CommentType != $COMMENT_TYPE_NONE)) " .
                     " ON term.TermIndex = subject.TermIndex " .
                     " LEFT OUTER JOIN nonmark_index AS average_index ON " .
                     "       subjectstudent.Average = average_index.NonmarkIndex " .
                     " LEFT OUTER JOIN nonmark_index AS effort_index ON " .
                     "       subjectstudent.Effort = effort_index.NonmarkIndex " .
                     " LEFT OUTER JOIN nonmark_index AS conduct_index ON " .
                     "       subjectstudent.Conduct = conduct_index.NonmarkIndex " .
                     "ORDER BY term.TermNumber ASC";
            $dres = &  $db->query($query);
            if (DB::isError($dres))
                die($dres->getDebugInfo()); // Check for errors in query

            $average = 0;
            $average_max = 0;
            $subj_average = 0;
            $subj_average_max = 0;

            while ( $drow = & $dres->fetchRow(DB_FETCHMODE_ASSOC) ) {
                $term_weight = $drow['Weight'];

                if ($drow['AverageType'] == $AVG_TYPE_NONE) {
                    $score = "N/A";
                } elseif ($drow['AverageType'] == $AVG_TYPE_PERCENT) {
                    if ($drow['Average'] == - 1 or is_null($drow['Average'])) {
                        $score = "-";
                    } else {
                        $scorestr = round($drow['Average']);
                        $average += $scorestr * $term_weight;
                        $average_max += 100 * $term_weight;

                        if ($scorestr < 60) {
                            $color = "#CC0000";
                        } elseif ($scorestr < 75) {
                            $color = "#666600";
                        } elseif ($scorestr < 90) {
                            $color = "#000000";
                        } else {
                            $color = "#339900";
                        }
                        $score = "<span style='color: $color'>$scorestr</span>";
                    }
                    if ($drow['SubjectAverage'] != - 1) {
                        $subjscore = round($drow['SubjectAverage']);
                        $subj_average += $subjscore * $term_weight;
                        $subj_average_max += 100 * $term_weight;

                        $score = "<b>$score</b> ($subjscore)";
                    }
                } elseif ($drow['AverageType'] == $AVG_TYPE_INDEX or
                         $drow['AverageType'] == $AVG_TYPE_GRADE) {
                    if (is_null($drow['AverageDisplay'])) {
                        $score = "-";
                    } else {
                        $score = $drow['AverageDisplay'];
                    }
                } else {
                    $score = "N/A";
                }
                if ($drow['TermIndex'] != $termindex) {
                    $score = str_replace("<b>", "", $score);
                    $score = str_replace("</b>", "", $score);
                }
                echo "               <td nowrap>$score</td>\n";
            }
            if ($average_max > 0) {
                $scorestr = round($average * 100 / $average_max);
                if ($scorestr < 60) {
                    $color = "#CC0000";
                } elseif ($scorestr < 75) {
                    $color = "#666600";
                } elseif ($scorestr < 90) {
                    $color = "#000000";
                } else {
                    $color = "#339900";
                }
                $score = "<span style='color: $color'>$scorestr</span>";
            } else {
                $score = "-";
            }
            if ($subj_average_max > 0) {
                $subjscore = round($subj_average * 100 / $subj_average_max);
                $score = "<i>$score</i> ($subjscore)";
            } else {
                $score = "<i>$score</i>";
            }
            echo "               <td nowrap>$score</td>\n";
        }
        if (! $show_only) {
            if ($subject_effort_type != $EFFORT_TYPE_NONE) {
                if ($row['EffortType'] == $EFFORT_TYPE_NONE) {
                    $score = "N/A";
                } elseif ($row['EffortType'] == $EFFORT_TYPE_PERCENT) {
                    if ($row['Effort'] == - 1) {
                        $score = "-";
                    } else {
                        $score = round($row['Effort']);
                        $score = "$score%";
                    }
                } elseif ($row['EffortType'] == $EFFORT_TYPE_INDEX) {
                    if (is_null($row['EffortDisplay'])) {
                        $score = "-";
                    } else {
                        $score = $row['EffortDisplay'];
                    }
                } else {
                    $score = "N/A";
                }
                echo "               <td nowrap>$score</td>\n";
            }

            if ($subject_conduct_type != $CONDUCT_TYPE_NONE) {
                if ($row['ConductType'] == $CONDUCT_TYPE_NONE) {
                    $score = "N/A";
                } elseif ($row['ConductType'] == $CONDUCT_TYPE_PERCENT) {
                    if ($row['Conduct'] == - 1) {
                        $score = "-";
                    } else {
                        $score = round($row['Conduct']);
                        $score = "$score%";
                    }
                } elseif ($row['ConductType'] == $CONDUCT_TYPE_INDEX) {
                    if (is_null($row['ConductDisplay'])) {
                        $score = "-";
                    } else {
                        $score = $row['ConductDisplay'];
                    }
                } else {
                    $score = "N/A";
                }
                echo "               <td nowrap>$score</td>\n";
            }
        }
    }

    if (! $show_only) {
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
        }
        if ($row['ReportDone'] == 0) {
            echo "               <td nowrap><b>No</b></td>\n";
        } else {
            echo "               <td nowrap><i>Yes</i></td>\n";
        }
        if (! $student_info['ReportDone']) {
            echo "               <td nowrap><input type='submit' name='edit_{$row['SubjectIndex']}' value='Change'></td>\n";
        }
    }
    echo "            </tr>\n";
}

if ($is_admin or $is_ct or $is_hod or $is_principal) {
    if ($conduct_type != $CLASS_CONDUCT_TYPE_NONE) {
        $query = "SELECT term.TermNumber, term.TermIndex, classlist.Conduct, " .
             "       classterm.Conduct AS ClassConduct, classterm.ConductType," .
             "       get_term_weight(term.TermIndex, classterm.ClassIndex, '$student_username') AS Weight FROM " .
             " (term INNER JOIN term AS depterm " .
             "       ON  term.DepartmentIndex = depterm.DepartmentIndex" .
             "       AND depterm.TermIndex = $termindex" .
             "       AND term.TermNumber <= depterm.TermNumber) " .
             " INNER JOIN " .
             " (classlist INNER JOIN (classterm INNER JOIN class USING (ClassIndex)) " .
             "       ON  classlist.Username = '$student_username' " .
             "       AND classlist.ClassTermIndex = classterm.ClassTermIndex " .
             "       AND class.YearIndex = $yearindex) " .
             " ON term.TermIndex = classterm.TermIndex " .
             "ORDER BY term.TermNumber";
        $cRes = &   $db->query($query);
        if (DB::isError($cRes))
            die($cRes->getDebugInfo()); // Check for errors in query

        $ovl_conduct = 0;
        $ovl_conduct_max = 0;
        $cls_ovl_conduct = 0;
        $cls_ovl_conduct_max = 0;

        $alt_count += 1;

        if ($alt_count % 2 == 0) {
            $alt = " class='alt'";
        } else {
            $alt = " class='std'";
        }

        echo "            <tr$alt id='row_average']}'>\n";
        echo "               <td nowrap>Conduct</td>\n";
        echo "               <td nowrap>1</td>\n";

        while ( $cRow = & $cRes->fetchrow(DB_FETCHMODE_ASSOC) ) {
            $term_weight = $cRow['Weight'];

            if ($cRow['Conduct'] != - 1 and ! is_null($cRow['Conduct'])) {
                $term_conduct = round($cRow['Conduct']);
                $ovl_conduct += $term_conduct * $term_weight;
                $ovl_conduct_max += 100 * $term_weight;

                $term_conduct = "$term_conduct%";
            }
            if ($cRow['ClassConduct'] != - 1 and ! is_null(
                                                        $cRow['ClassConduct'])) {

                $class_term_conduct = "$class_term_conduct%";
            }

            $conduct_type = $cRow['ConductType'];
            if ($conduct_type != $CLASS_CONDUCT_TYPE_NONE) {
                if ($conduct_type == $CLASS_CONDUCT_TYPE_PERCENT) {
                    if (isset($_POST["conduct"]) and
                         $termindex == $cRow['TermIndex']) {
                        $scorestr = $_POST["conduct"];

                        if (strval(intval($scorestr)) != $scorestr) {
                            $score = "-";
                        } elseif (intval($scorestr) > 100) {
                            $score = "100";
                        } elseif (intval($scorestr) < 0) {
                            $score = "0";
                        } else {
                            $score = "$scorestr";
                        }
                    } else {
                        if ($cRow['Conduct'] == - 1) {
                            $scorestr = "";
                            $score = "-";
                        } else {
                            $scorestr = round($cRow['Conduct']);
                            $score = "$scorestr";
                        }
                    }
                    if ($score != "-") {
                        $term_conduct = round(intval($score));
                        $ovl_conduct += $term_conduct * $term_weight;
                        $ovl_conduct_max += 100 * $term_weight;
                    }
                } elseif ($conduct_type == $CLASS_CONDUCT_TYPE_INDEX) {
                    if (isset($_POST["conduct"]) and
                             $termindex == $cRow['TermIndex']) {
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
                            $score = "-";
                        }
                    } else {
                        $scoreindex = $cRow['Conduct'];
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
                            $score = "-";
                        }
                    }
                } elseif ($conduct_type == $CLASS_CONDUCT_TYPE_CALC or
                         $conduct_type == $CLASS_CONDUCT_TYPE_PUN) {
                    if ($cRow['Conduct'] == - 1) {
                        $score = "-";
                    } else {
                        $scorestr = round($cRow['Conduct']);
                        if ($scorestr < 60) {
                            $color = "#CC0000";
                        } elseif ($scorestr < 75) {
                            $color = "#666600";
                        } elseif ($scorestr < 90) {
                            $color = "#000000";
                        } else {
                            $color = "#339900";
                        }
                        $score = "<span style='color: $color'>$scorestr%</span>";
                    }
                    if ($cRow['ClassConduct'] == - 1) {
                        $score = "<b>$score</b>";
                    } else {
                        $class_term_conduct = round($cRow['ClassConduct']);
                        $cls_ovl_conduct += $class_term_conduct * $term_weight;
                        $cls_ovl_conduct_max += 100 * $term_weight;

                        $score = "<b>$score</b> ($class_term_conduct)";
                    }
                    if ($cRow['Conduct'] != - 1) {
                        $term_conduct = round($cRow['Conduct']);
                        $ovl_conduct += $term_conduct * $term_weight;
                        $ovl_conduct_max += 100 * $term_weight;
                    }
                } else {
                    $score = "-";
                }
                if ($cRow['TermIndex'] != $termindex) {
                    $score = str_replace("<b>", "", $score);
                    $score = str_replace("</b>", "", $score);
                }

                if (($conduct_type == $CLASS_CONDUCT_TYPE_INDEX or
                     $conduct_type == $CLASS_CONDUCT_TYPE_PERCENT) and
                     ! $student_info['ReportDone'] and
                     $termindex == $cRow['TermIndex']) {
                    echo "               <td><input type='text' name='conduct' " .
                     "id='conduct' value='$scorestr' size='4' onChange='recalc_conduct();'> = <label name='c' id='cavg' for='conduct'>$score</label></td>\n";
                } else {
                    echo "               <td>$score</td>\n";
                }
            }
        }

        if ($cls_ovl_conduct_max > 0) {
            $scorestr = round($cls_ovl_conduct * 100 / $cls_ovl_conduct_max);
            $cls_ovl_conduct = " ($scorestr)";
        } else {
            $cls_ovl_conduct = "";
        }

        if ($ovl_conduct_max > 0) {
            $scorestr = round($ovl_conduct * 100 / $ovl_conduct_max);
            $ovl_conduct = "$scorestr";

            if ($scorestr < 60) {
                $color = "#CC0000";
            } elseif ($scorestr < 75) {
                $color = "#666600";
            } elseif ($scorestr < 90) {
                $color = "#000000";
            } else {
                $color = "#339900";
            }
            $ovl_conduct = "<i><span style='color: $color'>$scorestr%</span></i>";
        } else {
            $ovl_conduct = "-";
        }
        echo "               <td>$ovl_conduct$cls_ovl_conduct</td>\n";
        if ($colcount > 0) {
            echo "               <td colspan='$colcount'>&nbsp;</td>\n";
        }
        echo "            </tr>\n";
    }

    if ($average_type != $CLASS_AVG_TYPE_NONE) {
        $query = "SELECT term.TermNumber, term.TermIndex, classlist.Average, " .
             "       classterm.Average AS ClassAverage, classterm.AverageType," .
             "       get_term_weight(term.TermIndex, classterm.ClassIndex, '$student_username') AS Weight FROM " .
             " (term INNER JOIN term AS depterm " .
             "       ON  term.DepartmentIndex = depterm.DepartmentIndex" .
             "       AND depterm.TermIndex = $termindex" .
             "       AND term.TermNumber <= depterm.TermNumber) " .
             " INNER JOIN " .
             " (classlist INNER JOIN (classterm INNER JOIN class USING (ClassIndex)) " .
             "       ON  classlist.Username = '$student_username' " .
             "       AND classlist.ClassTermIndex = classterm.ClassTermIndex " .
             "       AND class.YearIndex = $yearindex) " .
             " ON term.TermIndex = classterm.TermIndex " .
             "ORDER BY term.TermNumber";
        $cRes = &   $db->query($query);
        if (DB::isError($cRes))
            die($cRes->getDebugInfo()); // Check for errors in query

        $ovl_average = 0;
        $ovl_average_max = 0;
        $cls_ovl_average = 0;
        $cls_ovl_average_max = 0;

        $alt_count += 1;

        if ($alt_count % 2 == 0) {
            $alt = " class='alt'";
        } else {
            $alt = " class='std'";
        }

        echo "            <tr$alt id='row_average']}'>\n";
        echo "               <td nowrap colspan='2'><b>Average</b></td>\n";

        while ( $cRow = & $cRes->fetchrow(DB_FETCHMODE_ASSOC) ) {
            $term_weight = $cRow['Weight'];

            if ($cRow['Average'] != - 1 and ! is_null($cRow['Average'])) {
                $term_average = round($cRow['Average']);
                $ovl_average += $term_average * $term_weight;
                $ovl_average_max += 100 * $term_weight;

                $term_average = "$term_average%";
            }
            if ($cRow['ClassAverage'] != - 1 and ! is_null(
                                                        $cRow['ClassAverage'])) {

                $class_term_average = "$class_term_average%";
            }

            $average_type = $cRow['AverageType'];
            if ($average_type != $CLASS_AVG_TYPE_NONE) {
                if ($average_type == $CLASS_AVG_TYPE_PERCENT) {
                    if (isset($_POST["average"]) and
                         $termindex == $cRow['TermIndex']) {
                        $scorestr = $_POST["average"];

                        if (strval(intval($scorestr)) != $scorestr) {
                            $score = "N/A";
                        } elseif (intval($scorestr) > 100) {
                            $score = "100";
                        } elseif (intval($scorestr) < 0) {
                            $score = "0";
                        } else {
                            $score = "$scorestr";
                        }
                    } else {
                        if ($cRow['Average'] == - 1) {
                            $scorestr = "";
                            $score = "N/A";
                        } else {
                            $scorestr = round($cRow['Average']);
                            $score = "$scorestr";
                        }
                    }
                    if ($score != "N/A") {
                        $term_average = round(intval($score));
                        $ovl_average += $term_average * $term_weight;
                        $ovl_average_max += 100 * $term_weight;
                    }
                } elseif ($average_type == $CLASS_AVG_TYPE_INDEX) {
                    if (isset($_POST["average"]) and
                             $termindex == $cRow['TermIndex']) {
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
                        $scoreindex = $cRow['Average'];
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
                    if ($cRow['Average'] == - 1) {
                        $score = "N/A";
                    } else {
                        $scorestr = round($cRow['Average']);
                        if ($scorestr < 60) {
                            $color = "#CC0000";
                        } elseif ($scorestr < 75) {
                            $color = "#666600";
                        } elseif ($scorestr < 90) {
                            $color = "#000000";
                        } else {
                            $color = "#339900";
                        }
                        $score = "<span style='color: $color'>$scorestr%</span>";
                    }
                    if ($cRow['ClassAverage'] == - 1) {
                        $score = "<b>$score</b>";
                    } else {
                        $class_term_average = round($cRow['ClassAverage']);
                        $cls_ovl_average += $class_term_average * $term_weight;
                        $cls_ovl_average_max += 100 * $term_weight;

                        $score = "<b>$score</b> ($class_term_average)";
                    }
                    if ($cRow['Average'] != - 1) {
                        $term_average = round($cRow['Average']);
                        $ovl_average += $term_average * $term_weight;
                        $ovl_average_max += 100 * $term_weight;
                    }
                } else {
                    $score = "N/A";
                }
                if (($average_type == $CLASS_AVG_TYPE_INDEX or
                     $average_type == $CLASS_AVG_TYPE_PERCENT) and
                     ! $student_info['ReportDone'] and
                     $termindex == $cRow['TermIndex']) {
                    echo "               <td><input type='text' name='average' " .
                     "id='average' value='$scorestr' size='4' onChange='recalc_avg();'> = <label name='aavg' id='aavg' for='average'>$score</label></td>\n";
                } else {
                    echo "               <td>$score</td>\n";
                }
            }
        }

        if ($cls_ovl_average_max > 0) {
            $scorestr = round($cls_ovl_average * 100 / $cls_ovl_average_max);
            $cls_ovl_average = " ($scorestr)";
        } else {
            $cls_ovl_average = "";
        }

        if ($ovl_average_max > 0) {
            $scorestr = round($ovl_average * 100 / $ovl_average_max);
            $ovl_average = "$scorestr";

            if ($scorestr < 60) {
                $color = "#CC0000";
            } elseif ($scorestr < 75) {
                $color = "#666600";
            } elseif ($scorestr < 90) {
                $color = "#000000";
            } else {
                $color = "#339900";
            }
            $ovl_average = "<b><i><span style='color: $color'>$scorestr%</span></i></b>";
        } else {
            $ovl_average = "-";
        }
        echo "               <td>$ovl_average$cls_ovl_average</td>";
        if ($colcount > 0) {
            echo "               <td colspan='$colcount'>&nbsp;</td>\n";
        }
        echo "            </tr>\n";
    }

    if ($average_type == $CLASS_AVG_TYPE_PERCENT or
         $average_type == $CLASS_AVG_TYPE_CALC) {
        $query = "SELECT term.TermNumber, term.TermIndex, classlist.Rank FROM " .
         " (term INNER JOIN term AS depterm " .
         "       ON  term.DepartmentIndex = depterm.DepartmentIndex" .
         "       AND depterm.TermIndex = $termindex" .
         "       AND term.TermNumber <= depterm.TermNumber) " . " INNER JOIN " .
         " (classlist INNER JOIN (classterm INNER JOIN class USING (ClassIndex)) " .
         "       ON  classlist.Username = '$student_username' " .
         "       AND classlist.ClassTermIndex = classterm.ClassTermIndex " .
         "       AND class.YearIndex = $yearindex) " .
         " ON term.TermIndex = classterm.TermIndex " . "ORDER BY term.TermNumber";
    $cRes = &   $db->query($query);
    if (DB::isError($cRes))
        die($cRes->getDebugInfo()); // Check for errors in query

    $alt_count += 1;

    if ($alt_count % 2 == 0) {
        $alt = " class='alt'";
    } else {
        $alt = " class='std'";
    }

    echo "            <tr$alt id='row_rank']}'>\n";
    echo "               <td nowrap colspan='2'>Rank</td>\n";

    while ( $cRow = & $cRes->fetchrow(DB_FETCHMODE_ASSOC) ) {
        if ($cRow['Rank'] == - 1) {
            $scorestr = "";
            $score = "-";
        } else {
            $scorestr = round($cRow['Rank']);
            $score = "$scorestr";
        }
        $score = "<b>$score</b>";
        if ($cRow['TermIndex'] != $termindex) {
            $score = str_replace("<b>", "", $score);
            $score = str_replace("</b>", "", $score);
        }

        echo "               <td>$score</td>\n";
    }

    $query = "SELECT classlist.Username, term.TermNumber, term.TermIndex, " .
             "       ROUND(SUM(CONVERT(ROUND(classlist.Average * get_term_weight(term.TermIndex, class.ClassIndex, '$student_username')), DECIMAL)) / " .
             "                           SUM(get_term_weight(term.TermIndex, class.ClassIndex, '$student_username'))) AS Average FROM " .
             " (term INNER JOIN term AS depterm " .
             "       ON  term.DepartmentIndex = depterm.DepartmentIndex" .
             "       AND depterm.TermIndex = $termindex" .
             "       AND term.TermNumber <= depterm.TermNumber) " .
             " INNER JOIN " .
             " (classlist AS tclasslist INNER JOIN (classterm INNER JOIN class USING (ClassIndex)) " .
             "  ON  tclasslist.Username = '$student_username' " .
             "  AND tclasslist.ClassTermIndex = classterm.ClassTermIndex " .
             "  AND class.YearIndex = $yearindex) " .
             " ON term.TermIndex = classterm.TermIndex " .
             " INNER JOIN classlist " .
             "  ON classterm.ClassTermIndex = classlist.ClassTermIndex " .
             "  AND classlist.Average > -1 " . " GROUP BY classlist.Username " .
             " ORDER BY Average DESC";
    $cRes = &   $db->query($query);
    if (DB::isError($cRes))
        die($cRes->getDebugInfo()); // Check for errors in query

    $countrank = 0;
    $rank = - 1;
    $prevmark = - 1;
    $same = 1;
    /* Student username may not show up if they don't have any marks in any subjects */
    while ( $cRow = & $cRes->fetchrow(DB_FETCHMODE_ASSOC) ) {
        if ($cRow['Average'] != $prevmark) {
            $countrank += $same;
            $same = 1;
        } else {
            $same += 1;
        }
        $prevmark = $cRow['Average'];
        if ($cRow['Username'] == $student_username) {
            $rank = $countrank;
            break;
        }
    }

    if ($rank == - 1) {
        $ovl_rank = "-";
    } else {
        $ovl_rank = "<i>$rank</i>";
    }
    echo "               <td>$ovl_rank</td>";
    if ($colcount > 0) {
        echo "               <td colspan='$colcount'>&nbsp;</td>\n";
    }
    echo "            </tr>\n";
}

if ($effort_type != $CLASS_EFFORT_TYPE_NONE) {
    $query = "SELECT term.TermNumber, term.TermIndex, classlist.Effort, " .
         "       classterm.Effort AS ClassEffort, classterm.EffortType, " .
         "       get_term_weight(term.TermIndex, classterm.ClassIndex, '$student_username') AS Weight FROM " .
         " (term INNER JOIN term AS depterm " .
         "       ON  term.DepartmentIndex = depterm.DepartmentIndex" .
         "       AND depterm.TermIndex = $termindex" .
         "       AND term.TermNumber <= depterm.TermNumber) " . " INNER JOIN " .
         " (classlist INNER JOIN (classterm INNER JOIN class USING (ClassIndex)) " .
         "       ON  classlist.Username = '$student_username' " .
         "       AND classlist.ClassTermIndex = classterm.ClassTermIndex " .
         "       AND class.YearIndex = $yearindex) " .
         " ON term.TermIndex = classterm.TermIndex " . "ORDER BY term.TermNumber";
    $cRes = &   $db->query($query);
    if (DB::isError($cRes))
        die($cRes->getDebugInfo()); // Check for errors in query

    $ovl_effort = 0;
    $ovl_effort_max = 0;
    $cls_ovl_effort = 0;
    $cls_ovl_effort_max = 0;

    $alt_count += 1;

    if ($alt_count % 2 == 0) {
        $alt = " class='alt'";
    } else {
        $alt = " class='std'";
    }

    echo "            <tr$alt id='row_average']}'>\n";
    echo "               <td nowrap colspan='2'>Effort</td>\n";

    while ( $cRow = & $cRes->fetchrow(DB_FETCHMODE_ASSOC) ) {
        $term_weight = 1;

        if (! is_null($cRow['Weight'])) {
            $term_weight = $cRow['Weight'];
        }
        if ($cRow['Effort'] != - 1 and ! is_null($cRow['Effort'])) {
            $term_effort = round($cRow['Effort']);
            $ovl_effort += $term_effort * $term_weight;
            $ovl_effort_max += 100 * $term_weight;

            $term_effort = "$term_effort%";
        }
        if ($cRow['ClassEffort'] != - 1 and ! is_null($cRow['ClassEffort'])) {

            $class_term_effort = "$class_term_effort%";
        }

        $effort_type = $cRow['EffortType'];
        if ($effort_type != $CLASS_EFFORT_TYPE_NONE) {
            if ($effort_type == $CLASS_EFFORT_TYPE_PERCENT) {
                if (isset($_POST["effort"]) and $termindex == $cRow['TermIndex']) {
                    $scorestr = $_POST["effort"];

                    if (strval(intval($scorestr)) != $scorestr) {
                        $score = "-";
                    } elseif (intval($scorestr) > 100) {
                        $score = "100";
                    } elseif (intval($scorestr) < 0) {
                        $score = "0";
                    } else {
                        $score = "$scorestr";
                    }
                } else {
                    if ($cRow['Effort'] == - 1) {
                        $scorestr = "";
                        $score = "-";
                    } else {
                        $scorestr = round($cRow['Effort']);
                        $score = "$scorestr";
                    }
                }
                if ($score != "-") {
                    $term_effort = round(intval($score));
                    $ovl_effort += $term_effort * $term_weight;
                    $ovl_effort_max += 100 * $term_weight;
                }
            } elseif ($effort_type == $CLASS_EFFORT_TYPE_INDEX) {
                if (isset($_POST["effort"]) and $termindex == $cRow['TermIndex']) {
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
                        $score = "-";
                    }
                } else {
                    $scoreindex = $cRow['Effort'];
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
                        $score = "-";
                    }
                }
            } elseif ($effort_type == $CLASS_EFFORT_TYPE_CALC) {
                if ($cRow['Effort'] == - 1) {
                    $score = "-";
                } else {
                    $scorestr = round($cRow['Effort']);
                    if ($scorestr < 60) {
                        $color = "#CC0000";
                    } elseif ($scorestr < 75) {
                        $color = "#666600";
                    } elseif ($scorestr < 90) {
                        $color = "#000000";
                    } else {
                        $color = "#339900";
                    }
                    $score = "<span style='color: $color'>$scorestr%</span>";
                }
                if ($cRow['ClassEffort'] == - 1) {
                    $score = "<b>$score</b>";
                } else {
                    $class_term_effort = round($cRow['ClassEffort']);
                    $cls_ovl_effort += $class_term_effort * $term_weight;
                    $cls_ovl_effort_max += 100 * $term_weight;

                    $score = "<b>$score</b> ($class_term_effort)";
                }
                if ($cRow['Effort'] != - 1) {
                    $term_effort = round($cRow['Effort']);
                    $ovl_effort += $term_effort * $term_weight;
                    $ovl_effort_max += 100 * $term_weight;
                }
            } else {
                $score = "-";
            }
            if ($cRow['TermIndex'] != $termindex) {
                $score = str_replace("<b>", "", $score);
                $score = str_replace("</b>", "", $score);
            }

            if (($effort_type == $CLASS_EFFORT_TYPE_INDEX or
                 $effort_type == $CLASS_EFFORT_TYPE_PERCENT) and
                 ! $student_info['ReportDone'] and
                 $termindex == $cRow['TermIndex']) {
                echo "               <td><input type='text' name='effort' " .
                 "id='effort' value='$scorestr' size='4' onChange='recalc_effort();'> = <label name='e' id='eavg' for='effort'>$score</label></td>\n";
            } else {
                echo "               <td>$score</td>\n";
            }
        }
    }

    if ($cls_ovl_effort_max > 0) {
        $scorestr = round($cls_ovl_effort * 100 / $cls_ovl_effort_max);
        $cls_ovl_effort = " ($scorestr)";
    } else {
        $cls_ovl_effort = "";
    }

    if ($ovl_effort_max > 0) {
        $scorestr = round($ovl_effort * 100 / $ovl_effort_max);
        $ovl_effort = "$scorestr";

        if ($scorestr < 60) {
            $color = "#CC0000";
        } elseif ($scorestr < 75) {
            $color = "#666600";
        } elseif ($scorestr < 90) {
            $color = "#000000";
        } else {
            $color = "#339900";
        }
        $ovl_effort = "<i><span style='color: $color'>$scorestr%</span></i>";
    } else {
        $ovl_effort = "-";
    }
    echo "               <td>$ovl_effort$cls_ovl_effort</td>";
    if ($colcount > 0) {
        echo "               <td colspan='$colcount'>&nbsp;</td>\n";
    }
    echo "            </tr>\n";
}

if ($absence_type != $ABSENCE_TYPE_NONE) {
    $query = "SELECT term.TermNumber, term.TermIndex, classlist.Absences FROM " .
         " (term INNER JOIN term AS depterm " .
         "       ON  term.DepartmentIndex = depterm.DepartmentIndex" .
         "       AND depterm.TermIndex = $termindex" .
         "       AND term.TermNumber <= depterm.TermNumber) " . " INNER JOIN " .
         " (classlist INNER JOIN (classterm INNER JOIN class USING (ClassIndex)) " .
         "       ON  classlist.Username = '$student_username' " .
         "       AND classlist.ClassTermIndex = classterm.ClassTermIndex " .
         "       AND class.YearIndex = $yearindex) " .
         " ON term.TermIndex = classterm.TermIndex " . "ORDER BY term.TermNumber";
    $cRes = &   $db->query($query);
    if (DB::isError($cRes))
        die($cRes->getDebugInfo()); // Check for errors in query

    $alt_count += 1;

    if ($alt_count % 2 == 0) {
        $alt = " class='alt'";
    } else {
        $alt = " class='std'";
    }

    echo "            <tr$alt id='row_absences']}'>\n";
    echo "               <td nowrap colspan='2'>Absences</td>\n";

    while ( $cRow = & $cRes->fetchrow(DB_FETCHMODE_ASSOC) ) {
        if ($absence_type == $ABSENCE_TYPE_NUM) {
            if (isset($_POST["absences"]) and $termindex == $cRow['TermIndex']) {
                $scorestr = $_POST["absences"];
                if (strval(intval($scorestr)) != $scorestr) {
                    $score = "-";
                } elseif (intval($scorestr) < 0) {
                    $score = "0";
                } else {
                    $score = "$scorestr";
                }
            } else {
                if ($cRow['Absences'] == - 1) {
                    $scorestr = "";
                    $score = "-";
                } else {
                    $scorestr = round($cRow['Absences']);
                    $score = "$scorestr";
                }
            }
            if ($score != "-") {
                $term_absence = round(intval($score));
            }
        } elseif ($absence_type == $ABSENCE_TYPE_CALC) {
            $absent = 0;
            $late = 0;
            $suspended = 0;

            $nquery = "SELECT AttendanceTypeIndex, COUNT(AttendanceIndex) AS Count " .
                     "       FROM attendance INNER JOIN subject USING (SubjectIndex) " .
                     "       INNER JOIN period USING (PeriodIndex) " .
                     "WHERE  attendance.Username = '$student_username' " .
                     "AND    subject.YearIndex = $yearindex " .
                     "AND    subject.TermIndex = ${cRow['TermIndex']} " .
                     "AND    period.Period = 1 " .
                     "AND    attendance.AttendanceTypeIndex > 0 " .
                     "GROUP BY AttendanceTypeIndex ";
            $dRes = &   $db->query($nquery);
            if (DB::isError($dRes))
                die($dRes->getDebugInfo()); // Check for errors in query
            while ( $dRow = & $dRes->fetchrow(DB_FETCHMODE_ASSOC) ) {
                if ($dRow['AttendanceTypeIndex'] == $ATT_ABSENT)
                    $absent = $dRow['Count'];
                if ($dRow['AttendanceTypeIndex'] == $ATT_LATE)
                    $late = $dRow['Count'];
                if ($dRow['AttendanceTypeIndex'] == $ATT_SUSPENDED)
                    $suspended = $dRow['Count'];
            }
            $score = $absent + $suspended;
            $score = "<b>$score</b>";
        } else {
            $score = "-";
        }
        if ($cRow['TermIndex'] != $termindex) {
            $score = str_replace("<b>", "", $score);
            $score = str_replace("</b>", "", $score);
        }

        if (($absence_type == $ABSENCE_TYPE_NUM) and ! $student_info['ReportDone'] and
             $termindex == $cRow['TermIndex']) {
            echo "               <td><input type='text' name='absences' " .
             "id='absences' value='$scorestr' size='4' onChange='recalc_absences();'> = <label name='ab' id='abavg' for='absences'>$score</label></td>\n";
        } else {
            echo "               <td>$score</td>\n";
        }
    }

    $abs_colcount = $colcount + 1;
    if ($abs_colcount > 0) {
        echo "               <td colspan='$abs_colcount'>&nbsp;</td>\n";
    }
    echo "            </tr>\n";
}
}
echo "         </table>\n"; // End of table

if (! $show_only) {
echo "         <table class='transparent' align='center' width=600px>\n";

if ($ct_comment_type != $COMMENT_TYPE_NONE) {
    $query = "SELECT term.TermName, term.TermIndex, classlist.CTComment FROM " .
         " (term INNER JOIN term AS depterm " .
         "       ON  term.DepartmentIndex = depterm.DepartmentIndex" .
         "       AND depterm.TermIndex = $termindex" .
         "       AND term.TermNumber <= depterm.TermNumber) " . " INNER JOIN " .
         " (classlist INNER JOIN (classterm INNER JOIN class USING (ClassIndex)) " .
         "       ON  classlist.Username = '$student_username' " .
         "       AND classlist.ClassTermIndex = classterm.ClassTermIndex " .
         "       AND class.YearIndex = $yearindex) " .
         " ON term.TermIndex = classterm.TermIndex " . "ORDER BY term.TermNumber";
    $cRes = &   $db->query($query);
    if (DB::isError($cRes))
        die($cRes->getDebugInfo()); // Check for errors in query

    while ( $cRow = & $cRes->fetchrow(DB_FETCHMODE_ASSOC) ) {
        /* No point in showing a row if there's no comment for old terms */
        if ((is_null($cRow['CTComment']) or $cRow['CTComment'] == "") and
             $cRow['TermIndex'] != $termindex)
            continue;

        echo "            <tr>\n";
        echo "               <td colspan='2'><b>${cRow['TermName']} - Class Teacher's comment:</b><br>\n";
        if (isset($_POST["ct_comment"]) and $cRow['TermIndex'] == $termindex) {
            $commentstr = htmlspecialchars($_POST["ct_comment"], ENT_QUOTES);
        } else {
            $commentstr = htmlspecialchars($cRow['CTComment'], ENT_QUOTES);
        }
        if (($ct_comment_type == $COMMENT_TYPE_MANDATORY or
             $ct_comment_type == $COMMENT_TYPE_OPTIONAL) and
             ! $student_info['ReportDone'] and ! $student_info['CTCommentDone'] and
             $cRow['TermIndex'] == $termindex) {
            echo "               <textarea name='ct_comment' " .
             "id='ct_comment' rows='5' cols='80' " .
             "onChange='recalc_comment(&quot;ct&quot;);'>$commentstr</textarea>\n";
        } else {
            echo "               $commentstr\n";
        }
        echo "               </td>\n";
        echo "            </tr>\n";
    }
}
if ($hod_comment_type != $COMMENT_TYPE_NONE) {
    $query = "SELECT term.TermName, term.TermIndex, classlist.HODComment FROM " .
         " (term INNER JOIN term AS depterm " .
         "       ON  term.DepartmentIndex = depterm.DepartmentIndex" .
         "       AND depterm.TermIndex = $termindex" .
         "       AND term.TermNumber <= depterm.TermNumber) " . " INNER JOIN " .
         " (classlist INNER JOIN (classterm INNER JOIN class USING (ClassIndex)) " .
         "       ON  classlist.Username = '$student_username' " .
         "       AND classlist.ClassTermIndex = classterm.ClassTermIndex " .
         "       AND class.YearIndex = $yearindex) " .
         " ON term.TermIndex = classterm.TermIndex " . "ORDER BY term.TermNumber";
    $cRes = &   $db->query($query);
    if (DB::isError($cRes))
        die($cRes->getDebugInfo()); // Check for errors in query

    while ( $cRow = & $cRes->fetchrow(DB_FETCHMODE_ASSOC) ) {
        /* No point in showing a row if there's no comment for old terms */
        if ((is_null($cRow['HODComment']) or $cRow['HODComment'] == "") and
             $cRow['TermIndex'] != $termindex)
            continue;

        echo "            <tr>\n";
        echo "               <td colspan='2'><b>${cRow['TermName']} - Head of Department's comment:</b><br>\n";
        if (isset($_POST["hod_comment"]) and $cRow['TermIndex'] == $termindex) {
            $commentstr = htmlspecialchars($_POST["hod_comment"], ENT_QUOTES);
        } else {
            $commentstr = htmlspecialchars($cRow['HODComment'], ENT_QUOTES);
        }
        if (($hod_comment_type == $COMMENT_TYPE_MANDATORY or
             $hod_comment_type == $COMMENT_TYPE_OPTIONAL) and
             ! $student_info['ReportDone'] and ! $student_info['HODCommentDone'] and
             $cRow['TermIndex'] == $termindex) {
            echo "               <textarea name='hod_comment' " .
             "id='hod_comment' rows='5' cols='80' " .
             "onChange='recalc_comment(&quot;hod&quot;);'>$commentstr</textarea>\n";
        } else {
            echo "               $commentstr\n";
        }
        echo "               </td>\n";
        echo "            </tr>\n";
    }
}
if ($pr_comment_type != $COMMENT_TYPE_NONE) {
    $query = "SELECT term.TermName, term.TermIndex, classlist.PrincipalComment FROM " .
         " (term INNER JOIN term AS depterm " .
         "       ON  term.DepartmentIndex = depterm.DepartmentIndex" .
         "       AND depterm.TermIndex = $termindex" .
         "       AND term.TermNumber <= depterm.TermNumber) " . " INNER JOIN " .
         " (classlist INNER JOIN (classterm INNER JOIN class USING (ClassIndex)) " .
         "       ON  classlist.Username = '$student_username' " .
         "       AND classlist.ClassTermIndex = classterm.ClassTermIndex " .
         "       AND class.YearIndex = $yearindex) " .
         " ON term.TermIndex = classterm.TermIndex " . "ORDER BY term.TermNumber";
    $cRes = &   $db->query($query);
    if (DB::isError($cRes))
        die($cRes->getDebugInfo()); // Check for errors in query

    while ( $cRow = & $cRes->fetchrow(DB_FETCHMODE_ASSOC) ) {
        /* No point in showing a row if there's no comment for old terms */
        if ((is_null($cRow['PrincipalComment']) or
             $cRow['PrincipalComment'] == "") and
             $cRow['TermIndex'] != $termindex)
            continue;

        echo "            <tr>\n";
        echo "               <td colspan='2'><b>${cRow['TermName']} - Principal's comment:</b><br>\n";
        if (isset($_POST["pr_comment"]) and $cRow['TermIndex'] == $termindex) {
            $commentstr = htmlspecialchars($_POST["pr_comment"], ENT_QUOTES);
        } else {
            $commentstr = htmlspecialchars($cRow['PrincipalComment'],
                                        ENT_QUOTES);
        }
        if (($pr_comment_type == $COMMENT_TYPE_MANDATORY or
             $pr_comment_type == $COMMENT_TYPE_OPTIONAL) and
             ! $student_info['ReportDone'] and
             ! $student_info['PrincipalCommentDone'] and
             $cRow['TermIndex'] == $termindex) {
            echo "               <textarea name='pr_comment' " .
             "id='pr_comment' rows='5' cols='80' " .
             "onChange='recalc_comment(&quot;pr&quot;);'>$commentstr</textarea>\n";
        } else {
            echo "               $commentstr\n";
        }
        echo "               </td>\n";
        echo "            </tr>\n";
    }
}
echo "         </table>\n";
}
if ($can_do_report and ! $show_only) {
echo "         <p></p>\n";
echo "         <p align='center'>\n";
if ($prev_uname != "") {
    echo "            <input type='hidden' name='studentprev' value='$prev_uname'>\n";
    echo "            <input type='submit' name='student_$prev_uname' value='&lt;&lt;'>&nbsp; \n";
}
if (! $student_info['ReportDone']) {
    echo "            <input type='submit' name='action' value='Update'>&nbsp; \n";
}
if ((($is_hod and $hod_comment_type != $COMMENT_TYPE_NONE and
     ! $student_info['HODCommentDone']) or ($is_principal and
     $pr_comment_type != $COMMENT_TYPE_NONE and
     ! $student_info['PrincipalCommentDone']) or ($is_ct and
     $ct_comment_type != $COMMENT_TYPE_NONE and ! $student_info['CTCommentDone'])) and
     ! $student_info['ReportDone']) {
    echo "            <input type='submit' name='action' value='Finished with comments'>&nbsp; \n";
}
if (($student_info['CTCommentDone'] or
     ($student_info['HODCommentDone'] and
     ($is_admin or $is_hod or $is_principal or $is_proofreader)) or
     ($student_info['PrincipalCommentDone'] and
     ($is_admin or $is_principal or $is_proofreader))) and
     ! $student_info['ReportDone']) {
    echo "            <input type='submit' name='action' value='Edit comments'>&nbsp; \n";
}
if (($is_hod or $is_principal or $is_admin) and ! $student_info['ReportDone']) {
    echo "            <input type='submit' name='action' value='Close report'>&nbsp; \n";
}
if ($student_info['ReportDone'] and ($is_admin or $is_principal)) {
    echo "            <input type='submit' name='action' value='Open report'>&nbsp; \n";
}
if ($is_proofreader) {
    echo "            <input type='submit' name='action' value='Done with report'>&nbsp; \n";
}
echo "            <input type='submit' name='action' value='Cancel'>\n";
if ($next_uname != "") {
    echo "            <input type='submit' name='student_$next_uname' value='&gt;&gt;'>&nbsp; \n";
    echo "            <input type='hidden' name='studentnext' value='$next_uname'>\n";
}

echo "         </p>\n";
echo "       </form>\n";
}

include "footer.php";
?>
