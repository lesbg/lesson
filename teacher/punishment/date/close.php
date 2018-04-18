<?php
/**
 * ***************************************************************
 * teacher/punishment/date/close.php (c) 2006, 2018 Jonathan Dieter
 *
 * Close punishment permanently
 * ***************************************************************
 */

/* Get variables */
$pindex = dbfuncInt2String($_GET['ptype']);
$nextLink = dbfuncInt2String($_GET['next']); // Link to next page

include "core/settermandyear.php";

$perm = 0;
$query = $pdb->prepare(
    "SELECT Username, DisciplineDateIndex FROM disciplinedate " .
    "WHERE DisciplineDateIndex = :pindex " .
    "AND   Username            = :username " .
    "AND   Done=0"
);
$query->execute(['pindex' => $pindex, 'username' => $username]);
if($query->fetch())
    $perm = 1;

/* Check whether user is authorized */
if (!$is_admin and $perm == 0) {
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "teacher/punishment/date/close.php",
            $LOG_DENIED_ACCESS, "Attempted to close punishment attendance.");

    $noJS = true;
    $noHeaderLinks = true;
    $title = "LESSON - Unauthorized access!";

    include "header.php";

    echo "      <p align='center'>You do not have permission to access this page. <a href=" .
         "'$nextLink'>Click here to continue.</a></p>\n";

    include "footer.php";
    exit(0);
}

/* Check which button was pressed */
if ($_POST["action"] != "Yes, close punishment") {
    redirect($nextLink);
    exit(0);
}

/* Set everyone who didn't show up as No-show */
$pdb->prepare(
    "UPDATE discipline SET ServedType=0 " .
    "WHERE ServedType IS NULL AND DisciplineDateIndex=:pindex"
)->execute(['pindex' => $pindex]);

/* Request new punishment for those who didn't show */
$query = $pdb->prepare(
    "SELECT discipline.Username, disciplinetype.NextIndex, disciplinedate.PunishDate, " .
    "       disciplinetype.DisciplineType FROM discipline, disciplinedate, disciplinetype " .
    "WHERE discipline.DisciplineDateIndex = disciplinedate.DisciplineDateIndex " .
    "AND   disciplinedate.DisciplineDateIndex = :pindex " .
    "AND   disciplinedate.DisciplineTypeIndex = disciplinetype.DisciplineTypeIndex " .
    "AND   discipline.ServedType = 0 " .
    "GROUP BY discipline.Username"
);
$query->execute(['pindex' => $pindex]);
while($row = $query->fetch()) {
    $puntype = strtolower($row['DisciplineType']);
    $comment = $db->escapeSimple("Missed $puntype");
    $pdb->prepare(
        "INSERT INTO disciplinebacklog (DisciplineTypeIndex, Date, DateOfViolation, Username, " .
        "                               WorkerUsername, RequestType, Comment) " .
        "       VALUES (:next_index, CURDATE(), :pdate, " .
        "               :username, :wusername, 1, :comment)"
    )->execute(['next_index' => $row['NextIndex'], 'pdate' => $row['PunishDate'],
                'username' => $row['Username'], 'wusername' => $username,
                'comment' => $comment]);
}

/* Set discipline session as closed */
$pdb->prepare(
    "UPDATE disciplinedate SET Done=1 " .
    "WHERE DisciplineDateIndex=:pindex"
)->execute(['pindex' => $pindex]);

log_event($LOG_LEVEL_TEACHER, "teacher/punishment/date/close.php",
        $LOG_TEACHER, "Closed punishment attendance.");

$title = "LESSON - Closing punishment attendance...";
$noHeaderLinks = true;
$noJS = true;

include "header.php"; // Print header

echo "      <p align='center'>Closing punishment attendance...done</p>\n";

echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n"; // Link to next page

include "footer.php";
