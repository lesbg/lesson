<?php
/**
 * ***************************************************************
 * teacher/punishment/date/close.php (c) 2006 Jonathan Dieter
 *
 * Close punishment permanently
 * ***************************************************************
 */

/* Get variables */
$pindex = intval(safe(dbfuncInt2String($_GET['ptype'])));
$nextLink = dbfuncInt2String($_GET['next']); // Link to next page

include "core/settermandyear.php";

$query = "SELECT Username, DisciplineDateIndex FROM disciplinedate " .
         "WHERE DisciplineDateIndex = $pindex " . "AND   Done=0";
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
if (dbfuncGetPermission($permissions, $PERM_ADMIN) or $perm == 1) {
    /* Check which button was pressed */
    if ($_POST["action"] == "Yes, close punishment") {
        /* Set everyone who didn't show up as No-show */
        $query = "UPDATE discipline SET ServedType=0 WHERE ServedType IS NULL AND DisciplineDateIndex=$pindex";
        $res = &  $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query

        /* Request new punishment for those who didn't show */
        $query = "SELECT discipline.Username, disciplinetype.NextIndex, disciplinedate.PunishDate, " .
                 "       disciplinetype.DisciplineType FROM discipline, disciplinedate, disciplinetype " .
                 "WHERE discipline.DisciplineDateIndex = disciplinedate.DisciplineDateIndex " .
                 "AND   disciplinedate.DisciplineDateIndex = $pindex " .
                 "AND   disciplinedate.DisciplineTypeIndex = disciplinetype.DisciplineTypeIndex " .
                 "AND   discipline.ServedType = 0 " .
                 "GROUP BY discipline.Username";
        $nres = &  $db->query($query);
        if (DB::isError($nres))
            die($nres->getDebugInfo()); // Check for errors in query
        while ( $row = & $nres->fetchRow(DB_FETCHMODE_ASSOC) ) {
            $puntype = strtolower($row['DisciplineType']);
            $comment = $db->escapeSimple("Missed $puntype");
            $query = "INSERT INTO disciplinebacklog (DisciplineTypeIndex, Date, DateOfViolation, Username, " .
                     "                               WorkerUsername, RequestType, Comment) " .
                     "       VALUES ({$row['NextIndex']}, CURDATE(), '{$row['PunishDate']}', " .
                     "               '{$row['Username']}', '$username', 1, '$comment')";
            $res = &  $db->query($query);
            if (DB::isError($res))
                die($res->getDebugInfo()); // Check for errors in query
        }

        /* Set discipline session as closed */
        $query = "UPDATE disciplinedate SET Done=1 WHERE DisciplineDateIndex=$pindex";
        $res = &  $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query

        log_event($LOG_LEVEL_TEACHER, "teacher/punishment/date/close.php",
                $LOG_TEACHER, "Closed punishment attendance.");

        $title = "LESSON - Closing punishment attendance...";
        $noHeaderLinks = true;
        $noJS = true;

        include "header.php"; // Print header

        echo "      <p align=\"center\">Closing punishment attendance...done</p>\n";

        echo "      <p align=\"center\"><a href=\"$nextLink\">Continue</a></p>\n"; // Link to next page

        include "footer.php";
    } else {
        $extraMeta = "      <meta http-equiv=\"REFRESH\" content=\"0;url=$nextLink\">\n";
        $noJS = true;
        $noHeaderLinks = true;
        $title = "LESSON - Redirecting...";

        include "header.php";

        echo "      <p align=\"center\">Redirecting you to <a href=\"$nextLink\">$nextLink</a></p>\n";

        include "footer.php";
    }
} else {
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "teacher/punishment/date/close.php",
            $LOG_DENIED_ACCESS, "Attempted to close punishment attendance.");

    $noJS = true;
    $noHeaderLinks = true;
    $title = "LESSON - Unauthorized access!";

    include "header.php";

    echo "      <p align=\"center\">You do not have permission to access this page. <a href=" .
         "\"$nextLink\">Click here to continue.</a></p>\n";

    include "footer.php";
}

?>
