<?php
/**
 * ***************************************************************
 * teacher/punishment/request/delete.php (c) 2006, 2018 Jonathan Dieter
 *
 * Delete pending punishment for student
 * ***************************************************************
 */

/* Get variables */
$backlogindex = dbfuncInt2String($_GET['key']);
$nextLink = dbfuncInt2String($_GET['next']);

if ($_POST['action'] != "Yes, delete pending punishment") {
    redirect($nextLink);
    exit(0);
}

$title = "LESSON - Deleting pending punishment";
$noJS = true;
$noHeaderLinks = true;

include "core/settermandyear.php";
include "header.php";

/* Get information about punishment */
$query = $pdb->prepare(
    "SELECT disciplinetype.DisciplineType, disciplinebacklog.WorkerUsername, user.Username, " .
    "       user.FirstName, user.Surname, disciplinebacklog.Date, disciplinebacklog.Comment " .
    "       FROM disciplinetype, disciplinebacklog, user " .
    "WHERE  disciplinebacklog.DisciplineTypeIndex = disciplinetype.DisciplineTypeIndex " .
    "AND    disciplinebacklog.DisciplineBacklogIndex = :backlogindex " .
    "AND    disciplinebacklog.Username = user.Username " .
    "AND    disciplinebacklog.RequestType = 1 "
);
$query->execute(['backlogindex' => $backlogindex]);
if ($row = $query->fetch()) {
    $name = "{$row['FirstName']} {$row['Surname']} ({$row['Username']})";
    $dateinfo = date($dateformat, strtotime($row['Date']));
    $punishment = "{$row['DisciplineType']} on $dateinfo";
    $log_pun = "{$row['DisciplineType']} on {$row['Date']}";

    /* Check whether current user is authorized to delete pending punishment */
    if ($is_admin or $row['WorkerUsername'] == $username) {
        $pdb->prepare(
            "DELETE FROM disciplinebacklog " . // Remove punishment from discipline table
            "WHERE DisciplineBacklogIndex = :backlogindex"
        )->execute(['backlogindex' => $backlogindex]);

        echo "      <p align='center'>Pending $punishment for $name successfully deleted.</p>\n";
        log_event($LOG_LEVEL_TEACHER,
                "teacher/punishment/request/delete.php", $LOG_TEACHER,
                "Deleted pending $log_pun for $name.");

        echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n";
    } else {
        log_event($LOG_LEVEL_ERROR, "teacher/punishment/request/delete.php",
                $LOG_DENIED_ACCESS,
                "Tried to delete pending $log_pun for $name.");
        echo "      <p>You do not have the authority to remove this punishment.  <a href='$nextLink'>" .
             "Click here to continue</a>.</p>\n";
    }
} else {
    echo "      <p align='center'>This pending punishment doesn't exist.  Perhaps you have already deleted it? " .
         "<a href='$nextLink'>Click here to continue</a>.</p>\n";
}

include "footer.php";
