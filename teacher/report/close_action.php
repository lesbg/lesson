<?php
/**
 * ***************************************************************
 * teacher/report/close_action.php (c) 2008, 2018 Jonathan Dieter
 *
 * Run query to close reports for a subject
 * ***************************************************************
 */
$student_name = "";
$student_username = "";

/* Get variables */
$subject_index = dbfuncInt2String($_GET['key']);
$subject = dbfuncInt2String($_GET['keyname']);
if (isset($_GET['key2'])) {
    $student_username = dbfuncInt2String($_GET['key2']);
    $student_name = dbfuncInt2String($_GET['keyname2']);
}

$nextLink = dbfuncInt2String($_GET['next']); // Link to next page
$error = false; // Boolean to store any errors

$is_principal = check_principal($username);
$is_hod = check_hod_subject($username, $subject_index);
$is_teacher = check_teacher_subject($username, $subject_index);
$is_ct = false;
if(isset($student_username) and $student_username != "")
    $is_ct = check_class_teacher_student($username, $student_username, $yearindex, $termindex);

if (!$is_ct and !$is_teacher and !$is_admin and !$is_hod and !$is_principal) {
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
$query = $pdb->prepare(
    "SELECT subject.CanDoReport " .
    "       FROM subject " .
    "WHERE subject.SubjectIndex = :subject_index"
);
$query->execute(['subject_index' => $subject_index]);

if (!$row = $query->fetch() or $row['CanDoReport'] == 0) {
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

    $qvals = ['subject_index' => $subject_index];
    $query = "SELECT user.Username, subjectstudent.Comment, subjectstudent.Average, " .
             "       subject.CommentType, subject.AverageType, " .
             "       user.FirstName, user.Surname " .
             "       FROM user, subjectstudent, subject " .
             "WHERE user.Username               = subjectstudent.Username " .
             "AND   subject.SubjectIndex        = :subject_index " .
             "AND   subjectstudent.SubjectIndex = subject.SubjectIndex ";
    if ($student_username == "") {
        $query .= "AND   subjectstudent.ReportDone   = 0";
    } else {
        $query .= "AND   subjectstudent.Username     = :student_username ";
        $qvals['student_username'] = $student_username;
    }
    $query = $pdb->prepare($query);
    $query->execute($qvals);

    $title = "LESSON - Saving changes...";
    $noHeaderLinks = true;
    $noJS = true;

    include "header.php";

    echo "      <p align='center'>Saving changes...";

    while ($row = $query->fetch()) {
        if (is_null($row['Comment']) and
             $row['CommentType'] == $COMMENT_TYPE_MANDATORY and
             ($row['Average'] != - 1 or
             ($row['AverageType'] != $AVG_TYPE_PERCENT and
             $row['AverageType'] != $AVG_TYPE_GRADE))) {
            echo "</p><p align='center'>You must set a comment for {$row['FirstName']} {$row['Surname']}.</p><p align='center'>";
            $is_error = true;
        } else {
            $pdb->prepare(
                "UPDATE subjectstudent SET " .
                "       ReportDone=1 " .
                "WHERE subjectstudent.Username = :student_username " .
                "AND   subjectstudent.SubjectIndex = :subject_index"
            )->execute(['student_username' => $row['Username'],
                        'subject_index' => $subject_index]);
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
