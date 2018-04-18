<?php
/**
 * ***************************************************************
 * teacher/punishment/date/close_confirm.php (c) 2006, 2018 Jonathan Dieter
 *
 * Confirm punishment attendance closure
 * ***************************************************************
 */

/* Get variables */
$title = "LESSON - Confirm to close punishment";
$noJS = true;
$noHeaderLinks = true;
$pindex = dbfuncInt2String($_GET['ptype']);

include "header.php";

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
    echo "      <p>This punishment date no longer exists or is closed</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";

    include "footer.php"
    exit(0);
}

$link = "index.php?location=" .
     dbfuncString2Int("teacher/punishment/date/close.php") . "&amp;type=" .
     $_GET['type'] . "&amp;ptype=" . $_GET['ptype'] . "&amp;next=" .
     $_GET['next'];

echo "      <p align='center'>Are you sure you want to close this punishment?</p>\n";
echo "      <form action='$link' method='post'>\n";
echo "         <p align='center'>";
echo "            <input type='submit' name='action' value='Yes, close punishment' \>&nbsp; \n";
echo "            <input type='submit' name='action' value='No, I changed my mind' \>&nbsp; \n";
echo "         </p>";
echo "      </form>\n";

include "footer.php";
