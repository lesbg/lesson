<?php
/**
 * ***************************************************************
 * teacher/punishment/date/modify_action.php (c) 2006 Jonathan Dieter
 *
 * See whether students showed up for punishment and take
 * appropriate action
 * ***************************************************************
 */

/* Get variables */
$pindex = intval(safe(dbfuncInt2String($_GET['ptype'])));
$nextLink = dbfuncInt2String($_GET['next']); // Link to next page

include "core/settermandyear.php";

$query = "SELECT Username, DisciplineDateIndex FROM disciplinedate " .
         "WHERE DisciplineDateIndex = $pindex " . "AND   Done = 0";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    if ($row['Username'] == $username) {
        $perm = 1;
    } else {
        $perm = 0;
    }
} else {
    include "header.php";
    echo "      <p>This punishment date no longer exists or is closed</p>\n";
    echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
    include "footer.php";
    exit(0);
}

/* Check whether user is authorized to issue mass punishment */
if ($is_admin or $perm == 1) {
    /* Check which button was pressed */
    if ($_POST["action"] == "Done" or $_POST["action"] == "Close") {
        $query = "UPDATE discipline SET ServedType=NULL WHERE DisciplineDateIndex=$pindex";
        $res = &  $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query
        foreach ( $_POST['mass'] as $punusername ) {
            $query = "UPDATE discipline SET ServedType=1 " .
                     "WHERE  discipline.Username = '$punusername' " .
                     "AND    discipline.DisciplineDateIndex = $pindex ";
            $res = &  $db->query($query);
            if (DB::isError($res))
                die($res->getDebugInfo()); // Check for errors in query
        }
        log_event($LOG_LEVEL_TEACHER,
                "teacher/punishment/date/modify_action.php", $LOG_TEACHER,
                "Checked punishment attendance.");

        include "teacher/punishment/date/close_confirm.php";
    } else {
        include "teacher/punishment/date/modify.php";
    }
} else {
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "teacher/punishment/date/modify_action.php",
            $LOG_DENIED_ACCESS, "Attempted to check punishment attendance.");

    $noJS = true;
    $noHeaderLinks = true;
    $title = "LESSON - Unauthorized access!";

    include "header.php";

    echo "      <p align=\"center\">You do not have permission to access this page. <a href=" .
         "\"$nextLink\">Click here to continue.</a></p>\n";

    include "footer.php";
}

?>
