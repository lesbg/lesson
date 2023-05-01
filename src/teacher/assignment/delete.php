<?php
/**
 * ***************************************************************
 * teacher/assignment/delete.php (c) 2004-2007, 2018 Jonathan Dieter
 *
 * Delete assignment from database
 * ***************************************************************
 */

/* Get variables */
$assignment_index = dbfuncInt2String($_GET['key']);
$nextLink = dbfuncInt2String($_GET['next']);

if ($_POST['action'] != "Yes, delete assignment") {
    redirect($nextLink);
    exit(0);
}

$title = "LESSON - Deleting Assignment";
$noJS = true;
$noHeaderLinks = true;

include "core/settermandyear.php";
include "header.php";

/* Get subject name, assignment title and subject index for assignment */
$query = $pdb->prepare(
    "SELECT subject.Name, assignment.Title, assignment.SubjectIndex FROM assignment, subject " .
    "WHERE assignment.AssignmentIndex  = :assignment_index " .
    "AND   subject.SubjectIndex        = assignment.SubjectIndex"
);
$query->execute(['assignment_index' => $assignment_index]);
$row = $query->fetch();

if (!check_teacher_assignment($username, $assignment_index) and !$is_admin) {
    if ($row) {
        log_event($LOG_LEVEL_ERROR, "teacher/assignment/delete.php",
                $LOG_DENIED_ACCESS,
                "Tried to remove assignment ({$row['Title']} in {$row['Name']}.");
        echo "      <p>You do not have the authority to remove this assignment.  " .
             "<a href='$nextLink'>Click here to continue</a>.</p>\n";
    } else {
        log_event($LOG_LEVEL_EVERYTHING, "teacher/assignment/delete.php",
                $LOG_ERROR, "Tried to remove non-existent assignment.");
        echo "      <p>This assignment has already been deleted.  " .
             "<a href='$nextLink'>Click here to continue</a>.</p>\n";
    }
    include "footer.php";
    exit(0);
}

/* Delete all marks for assignment */
$pdb->prepare(
    "DELETE FROM mark " .
    "WHERE AssignmentIndex = :assignment_index"
)->execute(['assignment_index' => $assignment_index]);

/* Delete assignment */
$pdb->prepare(
    "DELETE FROM assignment " .
    "WHERE AssignmentIndex = :assignment_index"
)->execute(['assignment_index' => $assignment_index]);

echo "      <p align='center'>Assignment successfully deleted.</p>\n";
echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n";

update_subject($row['SubjectIndex']);

log_event($LOG_LEVEL_TEACHER, "teacher/deleteassignment", $LOG_TEACHER,
        "Deleted assignment ({$row['Title']}) in {$row['Name']}.");

include "footer.php";
