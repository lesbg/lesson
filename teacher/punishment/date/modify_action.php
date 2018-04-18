<?php
/**
 * ***************************************************************
 * teacher/punishment/date/modify_action.php (c) 2006, 2018 Jonathan Dieter
 *
 * See whether students showed up for punishment and take
 * appropriate action
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
    log_event($LOG_LEVEL_ERROR, "teacher/punishment/date/modify_action.php",
            $LOG_DENIED_ACCESS, "Attempted to check punishment attendance.");

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
if ($_POST["action"] != "Done" and $_POST["action"] != "Close") {
    include "teacher/punishment/date/modify.php";
    exit(0);
}

$pdb->prepare(
    "UPDATE discipline SET ServedType=NULL WHERE DisciplineDateIndex=:pindex"
)->execute(['pindex' => $pindex]);
foreach ( $_POST['mass'] as $punusername ) {
    $pdb->prepare(
        "UPDATE discipline SET ServedType=1 " .
        "WHERE  discipline.Username = :punusername " .
        "AND    discipline.DisciplineDateIndex = :pindex "
    )->execute(['punusername' => $punusername, 'pindex' => $pindex]);
}
log_event($LOG_LEVEL_TEACHER, "teacher/punishment/date/modify_action.php",
          $LOG_TEACHER, "Checked punishment attendance.");

include "teacher/punishment/date/close_confirm.php";
