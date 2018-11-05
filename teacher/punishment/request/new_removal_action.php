<?php
/**
 * ***************************************************************
 * teacher/punishment/request/new_removal_action.php (c) 2006, 2018 Jonathan Dieter
 *
 * Insert new punishment removal request into database
 * ***************************************************************
 */

/* Get variables */
$discipline_index = dbfuncInt2String($_GET['key']);
$link = dbfuncInt2String($_GET['next']);

include "core/settermandyear.php";

$query = $pdb->prepare(
    "SELECT user.Username, user.FirstName, user.Surname " .
    "       FROM user, discipline " .
    "WHERE user.Username = discipline.Username " .
    "AND   discipline.DisciplineIndex = :discipline_index"
);
$query->execute(['discipline_index' => $discipline_index]);
if ($row = $query->fetch()) {
    $student = "{$row['FirstName']} {$row['SurName']} ({$row['Username']})";
} else {
    $student = "Unknown student";
}

$is_teacher = check_teacher_year($username, $currentyear);

if (!$is_admin and !$is_teacher) {
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "teacher/punishment/request/new_removal.php",
            $LOG_DENIED_ACCESS,
            "Tried to create punishment removal request for $student.");

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";

    include "footer.php";
    exit(0);
}

/* Check which button was pressed */
if ($_POST["action"] != "Save" and $_POST["action"] != "Update") {
    redirect($link);
    exit(0);
}

$title = "LESSON - Saving punishment removal request...";
$noHeaderLinks = true;
$noJS = true;

include "header.php"; // Print header

echo "      <p align='center'>Saving punishment removal request...";

$dateinfo = dbfuncCreateDate(date($dateformat));

/* Check whether or not a type was included and cancel if it wasn't */
if (!isset($_POST['note']) or $_POST['note'] == "" or is_null($_POST['note'])) {
    echo "failed</p>\n";
    echo "      <p align='center'>You must give a reason you want the punishment removed!</p>\n";
} else {
    $failed = 0;

    $pdb->prepare(
        "INSERT INTO disciplinebacklog (DisciplineIndex, WorkerUsername, " .
        "                               Date, RequestType, Comment) " .
        "       VALUES " .
        "       (:discipline_index, :username, :dinfo, 2, :reason)"
    )->execute(['discipline_index' => $discipline_index, 'username' => $username,
                'dinfo' => $dateinfo, 'reason' => $_POST['note']]);
    echo " done</p>\n";
    log_event($LOG_LEVEL_TEACHER,
            "teacher/punishment/request/new_removal_action.php",
            $LOG_TEACHER,
            "Created new punishment removal request for $student.");
}

echo "      <p align='center'><a href='$link'>Continue</a></p>\n"; // Link to next page

include "footer.php";
