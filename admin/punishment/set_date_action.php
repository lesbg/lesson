<?php
/**
 * ***************************************************************
 * admin/punishment/set_date_action.php (c) 2006-2013 Jonathan Dieter
 *
 * Set punishment date
 * ***************************************************************
 */

/* Get variables */
$dtype = dbfuncInt2String($_GET['type']);
$pindex = dbfuncInt2String($_GET['key']);
$nextLink = dbfuncInt2String($_GET['next']); // Link to next page

include "core/settermandyear.php";

$query = "SELECT user.FirstName, user.Surname, user.Username FROM " .
         "       user INNER JOIN groupgenmem ON (user.Username=groupgenmem.Username) " .
         "            INNER JOIN groups USING (GroupID) " .
         "WHERE user.Username='$username' " .
         "AND   groups.GroupTypeID='activeteacher' " .
         "AND   groups.YearIndex=$yearindex " .
         "ORDER BY user.Username";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query
if ($res->numRows() > 0) {
    $is_teacher = true;
} else {
    $is_teacher = false;
}

$query = "SELECT Permissions FROM disciplineperms WHERE Username=\"$username\"";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    $perm = $row['Permissions'];
} else {
    $perm = $DEFAULT_PUN_PERM;
}

/* Check whether user is authorized to issue mass punishment */
if (dbfuncGetPermission($permissions, $PERM_ADMIN) or
     ($perm >= $PUN_PERM_ALL and $is_teacher)) {
    /* Check which button was pressed */
    if ($_POST["action"] == "Set punishment date") {
        $title = "LESSON - Creating punishment date...";
        $noHeaderLinks = true;
        $noJS = true;

        $link = "index.php?location=" .
                 dbfuncString2Int("admin/punishment/date_student.php") .
                 "&amp;next=" . $_GET['next'] . "&amp;type=" . $_GET['type'];
        include "header.php"; // Print header

        echo "      <p align=\"center\">Setting up punishment date...";

        /* Check whether or not a type was included and cancel if it wasn't */
        $tusername = $db->escapeSimple($_POST['teacher']);
        $dtype = intval(dbfuncInt2String($_GET['type']));
        if (! isset($_POST['pundate']) or is_null($_POST['pundate']) or
             $_POST['pundate'] == "") {
            $pundate = & dbfuncCreateDate(date($dateformat));
        } else {
            $pundate = & dbfuncCreateDate($_POST['pundate']);
        }
        if (! isset($_POST['enddate']) or is_null($_POST['enddate']) or
             $_POST['enddate'] == "") {
            $enddate = & dbfuncCreateDate(date($dateformat));
        } else {
            $enddate = & dbfuncCreateDate($_POST['enddate']);
        }

        if ($pindex == "NULL") {
            /* Create punishment date */
            $query = "INSERT INTO disciplinedate (PunishDate, EndDate, Username, DisciplineTypeIndex)" .
                 "                    VALUES (\"$pundate\", \"$enddate\", \"$tusername\", $dtype)";
            $res = &  $db->query($query);
            if (DB::isError($res))
                die($res->getDebugInfo()); // Check for errors in query
        } else {
            /* Create punishment date */
            $query = "UPDATE disciplinedate SET PunishDate=\"$pundate\", EndDate=\"$enddate\", Username=\"$tusername\" WHERE DisciplineDateIndex = $pindex";
            $res = &  $db->query($query);
            if (DB::isError($res))
                die($res->getDebugInfo()); // Check for errors in query
        }
        echo " done</p>\n";

        echo "      <p align=\"center\"><a href=\"$nextLink\">Continue</a></p>\n"; // Link to next page

        include "footer.php";
    } elseif ($_POST["action"] == "Delete punishment date" and $pindex != "NULL") {
        $title = "LESSON - Creating punishment date...";
        $noHeaderLinks = true;
        $noJS = true;

        $link = "index.php?location=" .
                 dbfuncString2Int("admin/punishment/date_student.php") .
                 "&amp;next=" . $_GET['next'] . "&amp;type=" . $_GET['type'];
        include "header.php"; // Print header

        echo "      <p align=\"center\">Deleting punishment date...";

        /* Clean out punishments set for this date */
        $query = "UPDATE discipline SET DisciplineDateIndex=NULL, ServedType=NULL " .
                 "WHERE DisciplineDateIndex = $pindex";
        $res = &  $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query

        /* Remove punishment date */
        $query = "DELETE FROM disciplinedate " .
                 "WHERE DisciplineDateIndex = $pindex";
        $res = &  $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query

        echo " done</p>\n";

        echo "      <p align=\"center\"><a href=\"$nextLink\">Continue</a></p>\n"; // Link to next page

        include "footer.php";
    } else {
        redirect($nextLink);
    }
} else {
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "admin/punishment/set_date_action.php",
            $LOG_DENIED_ACCESS, "Attempted to set up punishment date.");

    $noJS = true;
    $noHeaderLinks = true;
    $title = "LESSON - Unauthorized access!";

    include "header.php";

    echo "      <p align=\"center\">You do not have permission to access this page. <a href=" .
         "\"$nextLink\">Click here to continue.</a></p>\n";

    include "footer.php";
}

?>
