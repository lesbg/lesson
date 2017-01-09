<?php
/**
 * ***************************************************************
 * teacher/report/close_action.php (c) 2008 Jonathan Dieter
 *
 * Run query to close reports for a subject
 * ***************************************************************
 */
$student_name = "";
$student_username = "";

/* Get variables */
$subjectindex = intval(safe(dbfuncInt2String($_GET['key'])));
$subject = dbfuncInt2String($_GET['keyname']);
if (isset($_GET['key2'])) {
    $student_username = safe(dbfuncInt2String($_GET['key2']));
    $student_name = dbfuncInt2String($_GET['keyname2']);
}

$nextLink = dbfuncInt2String($_GET['next']); // Link to next page
$error = false; // Boolean to store any errors

/*
 * if($is_admin) $showalldeps = true;
 * include "core/settermandyear.php";
 */

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
                "SELECT hod.Username FROM hod, term, subject " .
                 "WHERE hod.Username         = '$username' " .
                 "AND   hod.DepartmentIndex  = term.DepartmentIndex " .
                 "AND   term.TermIndex       = subject.TermIndex " .
                 "AND   subject.SubjectIndex = $subjectindex");
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
    $is_hod = true;
} else {
    $is_hod = false;
}

/* Check whether user is authorized to change scores */
$query = "(SELECT subjectteacher.Username FROM subjectteacher " .
         " WHERE subjectteacher.SubjectIndex = $subjectindex " .
         " AND   subjectteacher.Username     = '$username')";
if ($student_username != "") {
    $query .= "UNION " .
         "(SELECT class.ClassTeacherUsername FROM class, classterm, classlist " .
         " WHERE  classlist.Username         = '$student_username' " .
         " AND    classlist.ClassTermIndex   = classterm.ClassTermIndex " .
         " AND    classterm.TermIndex        = $termindex " .
         " AND    classterm.ClassIndex       = class.ClassIndex " .
         " AND    class.ClassTeacherUsername = '$username' " .
         " AND    class.YearIndex            = $yearindex) ";
}
$res = & $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() == 0 and ! $is_admin and ! $is_hod and ! $is_principal) {
    /* Print error message */
    include "header.php"; // Show header

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    log_event($LOG_LEVEL_ERROR, "teacher/report/comment_list.php",
            $LOG_DENIED_ACCESS, "Tried to modify report for $subject.");

    include "footer.php";
    exit(0);
}

/* Check whether subject is open for report editing */
$query = "SELECT subject.CanDoReport " . "       FROM subject " .
         "WHERE subject.SubjectIndex = $subjectindex";
$res = & $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if (! $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) or $row['CanDoReport'] == 0) {
    /* Print error message */
    include "header.php"; // Show header

    echo "      <p>Reports for this subject aren't open.</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    log_event($LOG_LEVEL_ERROR, "teacher/report/comment_list.php",
            $LOG_DENIED_ACCESS, "Tried to modify report for $subject.");

    include "footer.php";
    exit(0);
}

if ($_POST['action'] == "Yes, I'm finished") {
    $is_error = false;

    $query = "SELECT user.Username, subjectstudent.Comment, subjectstudent.Average, " .
             "       subject.CommentType, subject.AverageType, " .
             "       user.FirstName, user.Surname " .
             "       FROM user, subjectstudent, subject " .
             "WHERE user.Username               = subjectstudent.Username " .
             "AND   subject.SubjectIndex        = $subjectindex " .
             "AND   subjectstudent.SubjectIndex = $subjectindex ";
    if ($student_username == "") {
        $query .= "AND   subjectstudent.ReportDone   = 0";
    } else {
        $query .= "AND   subjectstudent.Username     = '$student_username'";
    }
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    $title = "LESSON - Saving changes...";
    $noHeaderLinks = true;
    $noJS = true;

    include "header.php";

    echo "      <p align='center'>Saving changes...";

    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        if (is_null($row['Comment']) and
             $row['CommentType'] == $COMMENT_TYPE_MANDATORY and
             ($row['Average'] != - 1 or
             ($row['AverageType'] != $AVG_TYPE_PERCENT and
             $row['AverageType'] != $AVG_TYPE_GRADE))) {
            echo "</p><p align='center'>You must set a comment for {$row['FirstName']} {$row['Surname']}.</p><p align='center'>";
            $is_error = true;
        } else {
            $query = "UPDATE subjectstudent SET " . "       ReportDone=1 " .
                     "WHERE subjectstudent.Username = '{$row['Username']}' " .
                     "AND   subjectstudent.SubjectIndex = $subjectindex";
            $nres = & $db->query($query);
            if (DB::isError($nres))
                die($nres->getDebugInfo());
        }
    }
    if ($is_error) {
        echo "failed.</p>\n";
    } else {
        echo "done.</p>\n";
    }
    echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n"; // Link to next page
} else {
    redirect($nextLink);
}
?>
