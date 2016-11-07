<?php
/// This doesn't actually work right now
/**
 * ***************************************************************
 * admin/support/modify_action.php (c) 2005 Jonathan Dieter
 *
 * Modify support teachers and students
 * ***************************************************************
 */

exit(0);

/* Get variables */
$nextLink = dbfuncInt2String($_GET['next']); // Link to next page

$query = "SELECT Username FROM counselorlist WHERE Username=\"$username\"";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    $is_counselor = true;
} else {
    $is_counselor = false;
}

$showalldeps = true;
include "core/settermandyear.php";

/* Check whether user is authorized to issue mass punishment */
if ($is_admin or $is_counselor) {
    /* Check which button was pressed */
    if ($_POST["action"] == ">") { // If > was pressed, remove selected students from
        foreach ( $_POST['removefromsupport'] as $remIndex ) { // punishment
            $query = "DELETE FROM support WHERE SupportIndex=\"$remIndex\"";
            $nres = &  $db->query($query);
            if (DB::isError($nres))
                die($nres->getDebugInfo()); // Check for errors in query
        }
        include "admin/support/modify.php";
    } elseif ($_POST["action"] == "<") {
        $tusername = $db->escapeSimple($_POST['teacher']);
        $query = "SELECT Title, Surname FROM user WHERE Username=\"$tusername\" AND SupportTeacher=1";
        $res = & $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query
        if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            $ttitle = $row['Title'];
            $tsurname = $row['Surname'];
            foreach ( $_POST['addtosupport'] as $addUserName ) {
                $query = "SELECT FirstName FROM user " .
                         "WHERE Username = '$addUserName' ";
                $nres = &  $db->query($query);
                if (DB::isError($nres))
                    die($nres->getDebugInfo()); // Check for errors in query
                if ($nrow = & $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
                    $query = "INSERT IGNORE INTO support (SupportIndex, WorkerUsername, StudentUsername) " .
                             "            VALUES  (\"{$tusername}{$addUserName}\", \"$tusername\", " .
                             "                     \"$addUserName\")";
                    $res = &  $db->query($query);
                    if (DB::isError($res))
                        die($res->getDebugInfo()); // Check for errors in query
                }
            }
        } else {
            $errorlist[] = "You must select a teacher!";
        }

        include "admin/support/modify.php";
    } elseif ($_POST["action"] == "Done") {
        redirect($nextLink);
    } else {
        include "admin/support/modify.php";
    }
} else {
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "admin/support/modify_action.php",
            $LOG_DENIED_ACCESS, "Attempted to edit learning support.");

    $noJS = true;
    $noHeaderLinks = true;
    $title = "LESSON - Unauthorized access!";

    include "header.php";

    echo "      <p align=\"center\">You do not have permission to access this page. <a href=" .
         "\"$nextLink\">Click here to continue.</a></p>\n";

    include "footer.php";
}

?>
