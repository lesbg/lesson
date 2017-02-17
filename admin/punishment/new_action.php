<?php
/**
 * ***************************************************************
 * admin/punishment/new_action.php (c) 2006-2017 Jonathan Dieter
 *
 * Create a punishment
 * ***************************************************************
 */

/* Get variables */
$studentusername = dbfuncInt2String($_GET['key']);
$student = dbfuncInt2String($_GET['keyname']);
$link = dbfuncInt2String($_GET['next']);

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

$query = "SELECT Permissions FROM disciplineperms WHERE Username='$username'";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    $perm = $row['Permissions'];
} else {
    $perm = $DEFAULT_PUN_PERM;
}

$type = intval($_POST['type']);
$query =    "SELECT PermLevel FROM " .
            "   disciplinetype INNER JOIN disciplineweight USING (DisciplineTypeIndex) " .
            "WHERE  disciplineweight.DisciplineWeightIndex = $type " .
            "AND    disciplineweight.YearIndex = $currentyear " .
            "AND    disciplineweight.TermIndex = $currentterm ";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    $perm_level = $row['PermLevel'];
    if($perm_level < $PUN_PERM_ISSUE)
        $perm_level = $PUN_PERM_ISSUE;
} else {
    $perm_level = $PUN_PERM_ISSUE;
}

include "core/settermandyear.php";

if ($is_admin or
     ($perm >= $perm_level and $is_teacher)) {
    /* Check which button was pressed */
    if ($_POST["action"] == "Save") { // If update or save were pressed, print
        $title = "LESSON - Saving punishment...";
        $noHeaderLinks = true;
        $noJS = true;

        include "header.php"; // Print header

        echo "      <p align=\"center\">Saving punishment...";

        if (! isset($_POST['date']) || $_POST['date'] == "") { // Make sure date is in correct format.
            echo "</p>\n      <p>Date not entered, defaulting to today.</p>\n      <p>"; // Print error message
            $_POST['date'] = & dbfuncCreateDate(date($dateformat));
        } else {
            $_POST['date'] = & dbfuncCreateDate($_POST['date']);
        }
        $dateinfo = "'" . $db->escapeSimple($_POST['date']) . "'";
        $thisdateinfo = "'" . dbfuncCreateDate(date($dateformat)) . "'";

        if (! isset($_POST['comment']) || $_POST['comment'] == "") {
            $comment = "NULL";
        } else {
            $comment = "'" . safe($_POST['comment']) . "'";
        }

        /* Check whether or not a type was included and cancel if it wasn't */
        if ($_POST['type'] == "" or is_null($_POST['type'])) {
            echo "failed</p>\n";
            echo "      <p align=\"center\">You must select a punishment type!</p>\n";
        } else {
            $weightindex = intval($_POST['type']);
            $query = "SELECT DisciplineWeightIndex FROM disciplineweight " .
                     "WHERE  disciplineweight.DisciplineWeightIndex = $weightindex " .
                     "AND    disciplineweight.YearIndex = $currentyear " .
                     "AND    disciplineweight.TermIndex = $currentterm ";
            $res = & $db->query($query);
            if (DB::isError($res))
                die($res->getDebugInfo()); // Check for errors in query
            $failed = 0;
            if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
                if ($_POST['reason'] == "" or is_null($_POST['reason'])) {
                    echo "failed</p>\n";
                    echo "      <p align=\"center\">You must explain why you want the student punished!</p>\n";
                    $failed = 1;
                } elseif ($_POST['reason'] == "other") {
                    if ($_POST['reasonother'] == "" or
                             is_null($_POST['reasonother'])) {
                        echo "failed</p>\n";
                        echo "      <p align=\"center\">You must explain why you want the student punished!</p>\n";
                        $failed = 1;
                    } else {
                        $reason = $db->escapeSimple($_POST['reasonother']);
                    }
                } else {
                    $reasonindex = intval($_POST['reason']);
                    $query = "SELECT DisciplineReason FROM disciplinereason " .
                             "WHERE  DisciplineReasonIndex = $reasonindex";
                    $res = & $db->query($query);
                    if (DB::isError($res))
                        die($res->getDebugInfo()); // Check for errors in query
                    if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
                        $reason = $row['DisciplineReason'];
                    } else {
                        echo "failed</p>\n";
                        echo "      <p align=\"center\">You must explain why you want the student punished!</p>\n";
                        $failed = 1;
                    }
                }
                if ($failed == 0) {
                    if ($_POST["action"] == "Save") {
                        $query = "INSERT INTO discipline (DisciplineWeightIndex, Username, WorkerUsername, " .
                             "                        RecordUsername, DateRequested, DateIssued, " .
                             "                        Date, Comment, Extra) " .
                             "       VALUES " .
                             "       ($weightindex, '$studentusername', '$username', '$username', " .
                             "        $thisdateinfo, $thisdateinfo, $dateinfo, '$reason', $comment)";
                        $res = & $db->query($query);
                        if (DB::isError($res))
                            die($res->getDebugInfo()); // Check for errors in query
                        update_conduct_mark($studentusername);
                        echo " done</p>\n";
                        log_event($LOG_LEVEL_ADMIN,
                                "admin/punishment/new_action.php", $LOG_ADMIN,
                                "Issued punishment for $student.");
                    } else {
                    }
                }
            } else {
                echo "failed</p>\n";
                echo "      <p align=\"center\">There is no punishment of selected type!</p>\n";
            }
        }

        echo "      <p align=\"center\"><a href=\"$link\">Continue</a></p>\n"; // Link to next page

        include "footer.php";
    } else {
        redirect($link);
    }
} else { // User isn't authorized to create punishment request
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "admin/punishment/new_action.php",
            $LOG_DENIED_ACCESS, "Tried to create a punishment for $student.");
    $title = "LESSON - Unauthorized access";
    $noHeaderLinks = true;
    $noJS = true;

    include "header.php"; // Print header

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
    include "footer.php";
}
?>
