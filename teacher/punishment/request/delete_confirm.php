<?php
/**
 * ***************************************************************
 * teacher/punishment/request/delete_confirm.php (c) 2006, 2018 Jonathan Dieter
 *
 * Confirm deletion of a pending punishment from database
 * ***************************************************************
 */

/* Get variables */
$backlogindex = dbfuncInt2String($_GET['key']);
$nextLink = dbfuncInt2String($_GET['next']);

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

    $title = "LESSON - Confirm to delete $name's pending $punishment";
    $noJS = true;
    $noHeaderLinks = true;

    include "core/settermandyear.php";
    include "header.php";

    /* Check whether current user is authorized to delete pending punishment */
    if ($is_admin or $row['WorkerUsername'] == $username) {
        $link = "index.php?location=" .
             dbfuncString2Int("teacher/punishment/request/delete.php") .
             "&amp;key=" . $_GET['key'] . "&amp;next=" . $_GET['next'];

        echo "      <p align='center'>Are you <b>sure</b> you want to delete pending $punishment for $name?</p>\n";
        echo "      <form action='$link' method='post'>\n";
        echo "         <p align='center'>";
        echo "            <input type='submit' name='action' value='Yes, delete pending punishment' \>&nbsp; \n";
        echo "            <input type='submit' name='action' value='No, I changed my mind' \>&nbsp; \n";
        echo "         </p>";
        echo "      </form>\n";
    } else {
        log_event($LOG_LEVEL_ERROR,
                "teacher/punishment/request/delete_confirm.php",
                $LOG_DENIED_ACCESS,
                "Tried to delete pending $log_pun for $name.");
        echo "      <p>You do not have the authority to remove this pending punishment.  <a href='$nextLink'>" .
             "Click here to continue</a>.</p>\n";
    }
} else {
    $title = "LESSON - Pending punishment doesn't exist!";
    $noJS = true;
    $noHeaderLinks = true;

    include "header.php";

    echo "      <p align='center'>This pending punishment doesn't exist.  Perhaps you have already deleted it? " .
         "<a href='$nextLink'>Click here to continue</a>.</p>\n";
}

include "footer.php";
