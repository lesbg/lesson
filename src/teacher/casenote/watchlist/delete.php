<?php
/**
 * ***************************************************************
 * teacher/casenote/watchlist/delete.php (c) 2006, 2018 Jonathan Dieter
 *
 * Delete student from teacher's watchlist
 * ***************************************************************
 */

/* Get variables */
$student = dbfuncInt2String($_GET['keyname']);
$student_username = dbfuncInt2String($_GET['key']);

$nextLink = "index.php?location=" .
             dbfuncString2Int("teacher/casenote/watchlist/list.php");

if ($_POST['action'] != "Yes, remove from my watchlist") {
    redirect($nextLink);
    exit(0);
}

$title = "LESSON - Removing from watchlist";
$noJS = true;
$noHeaderLinks = true;

include "core/settermandyear.php";
include "header.php";

/* Check whether student is on current user's watchlist */
$query = $pdb->prepare(
    "SELECT WorkerUsername FROM casenotewatch " .
    "WHERE WorkerUsername=:username " .
    "AND   StudentUsername=:student_username"
);
$query->execute(['username' => $username,
                 'student_username' => $student_username]);
if ($query->fetch()) {
    /* Remove student from watchlist */
    $pdb->prepare(
        "DELETE FROM casenotewatch " .
        "WHERE CaseNoteWatchIndex=:key"
    )->execute(['key' => "{$username}{$student_username}"]);

    echo "      <p align='center'>Successfully removed $student from your watchlist.</p>\n";
    echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n";
} else {
    echo "      <p>$student is not in your watchlist.  " .
         "<a href='$nextLink'>Click here to continue</a>.</p>\n";
}

include "footer.php";
