<?php
/**
 * ***************************************************************
 * teacher/assignment/delete_confirm.php (c) 2004, 2018 Jonathan Dieter
 *
 * Confirm deletion of assignment from database
 * ***************************************************************
 */

/* Get variables */
$assignment_index = dbfuncInt2String($_GET['key']);

$title = "LESSON - Confirm to delete assignment";
$noJS = true;
$noHeaderLinks = true;

include "core/settermandyear.php";
include "header.php";

/* Check whether user is authorized to delete assignment */
if (!check_teacher_assignment($username, $assignment_index) and !$is_admin) {
    echo "      <p>You do not have the authority to remove this assignment, or this assignment has already " .
         "been deleted.  <a href='$nextLink'>Click here to continue</a>.</p>\n";
    include "footer.php";
    exit(0);
}

$query = $pdb->prepare(
    "SELECT assignment.Title, assignment.Date, assignment.SubjectIndex " .
    "       FROM assignment " .
    "WHERE assignment.AssignmentIndex  = :assignment_index"
);
$query->execute(['assignment_index' => $assignment_index]);
$row = $query->fetch();

if(!$row) {
    echo "      <p>This assignment doesn't exist.  <a href='$nextLink'>Click here to continue</a>.</p>\n";
    include "footer.php";
    exit(0);
}

$dateinfo = date($dateformat, strtotime($row['Date']));
$link = "index.php?location=" .
         dbfuncString2Int("teacher/assignment/delete.php") . "&amp;key=" .
         $_GET['key'] . "&amp;next=" . $_GET['next'];

echo "      <p align='center'>Are you <strong>sure</strong> you want to delete {$row['Title']} " .
     "($dateinfo) and all of its scores?</p>\n";
echo "      <form action='$link' method='post'>\n";
echo "         <p align='center'>";
echo "            <input type='submit' name='action' value='Yes, delete assignment' \>&nbsp; \n";
echo "            <input type='submit' name='action' value='No, I changed my mind' \>&nbsp; \n";
echo "         </p>";
echo "      </form>\n";

include "footer.php";
