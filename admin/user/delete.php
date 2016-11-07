<?php
/**
 * ***************************************************************
 * admin/user/delete.php (c) 2005, 2016 Jonathan Dieter
 *
 * Delete user from database
 * ***************************************************************
 */

/* Get variables */
$delusername = dbfuncInt2String($_GET['key']);
$delfullname = dbfuncInt2String($_GET['keyname']);
$nextLink = dbfuncInt2String($_GET['next']);

include "core/settermandyear.php";

if ($_POST['action'] == "Yes, delete user") {
    $title = "LESSON - Deleting User";
    $noJS = true;
    $noHeaderLinks = true;

    include "header.php";

    /* Check whether current user is authorized to change scores */
    if ($is_admin) {
        $errorname = "";
        $iserror = False;

        $res = &  $db->query(
                        "SELECT Username FROM classlist " . // Check whether user to be deleted is in any class
                         "WHERE Username  = '$delusername'");
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query
        if ($res->numRows() > 0) {
            $errorname .= "      <p align=\"center\">You cannot delete $delfullname until you remove them from all of " .
         "their classes.</p>\n";
            $iserror = True;
            log_event($LOG_LEVEL_ADMIN, "admin/user/delete.php", $LOG_ERROR,
                    "Attempted to delete user $delfullname, but they were still in a class.");
        }

        $res = &  $db->query(
                        "SELECT Username FROM subjectstudent " . // Check whether user to be deleted is in any subjects
                         "WHERE Username  = '$delusername'");
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query
        if ($res->numRows() > 0) {
            $errorname .= "      <p align=\"center\">You cannot delete $delfullname until you remove them from all of " .
         "their subjects.</p>\n";
            $iserror = True;
            log_event($LOG_LEVEL_ADMIN, "admin/user/delete.php", $LOG_ERROR,
                    "Attempted to delete user $delfullname, but they were still in a subject.");
        }

        $res = &  $db->query(
                        "SELECT Username FROM subjectteacher " . // Check whether user to be deleted teaches any subjects
                         "WHERE Username  = '$delusername'");
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query
        if ($res->numRows() > 0) {
            $errorname .= "      <p align=\"center\">You cannot delete $delfullname until you remove them from teaching " .
         "all of their subjects.</p>\n";
            $iserror = True;
            log_event($LOG_LEVEL_ADMIN, "admin/user/delete.php", $LOG_ERROR,
                    "Attempted to delete user $delfullname, but they were still teaching a subject.");
        }

        $res = &  $db->query(
                "SELECT Username FROM familylist " . // Check whether user to be deleted teaches any subjects
                "WHERE Username  = '$delusername'");
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query
        if ($res->numRows() > 0) {
            $errorname .= "      <p align=\"center\">You cannot delete $delfullname until you remove them from their " .
            "family.</p>\n";
            $iserror = True;
            log_event($LOG_LEVEL_ADMIN, "admin/user/delete.php", $LOG_ERROR,
            "Attempted to delete user $delfullname, but they were still in a family.");
        }

        if ($iserror) { // Check whether there have been any errors during the
            echo $errorname; // sanity checks
        } else {
            $res = &  $db->query(
                    "DELETE FROM groupmem " . // Remove user from user table
                    "WHERE Member  = '$delusername'");
            if (DB::isError($res))
                die($res->getDebugInfo()); // Check for errors in query

            $res = &  $db->query(
                    "DELETE FROM groupgenmem " . // Remove user from user table
                    "WHERE Username  = '$delusername'");
            if (DB::isError($res))
                die($res->getDebugInfo()); // Check for errors in query

            $res = &  $db->query(
                            "DELETE FROM user " . // Remove user from user table
                             "WHERE Username  = '$delusername'");
            if (DB::isError($res))
                die($res->getDebugInfo()); // Check for errors in query

            echo "      <p align=\"center\">$delfullname successfully deleted.</p>\n";
            log_event($LOG_LEVEL_ADMIN, "admin/user/delete.php", $LOG_ADMIN,
                    "Deleted user $delfullname.");
        }
        echo "      <p align=\"center\"><a href=\"$nextLink\">Continue</a></p>\n";
    } else {
        log_event($LOG_LEVEL_ERROR, "admin/user/delete.php", $LOG_DENIED_ACCESS,
                "Tried to delete user $delfullname.");
        echo "      <p>You do not have the authority to remove this user.  <a href=\"$nextLink\">" .
             "Click here to continue</a>.</p>\n";
    }
} else {
    redirect($nextLink);
}

include "footer.php";
?>
