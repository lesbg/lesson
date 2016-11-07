<?php
/**
 * ***************************************************************
 * admin/family/delete.php (c) 2015 Jonathan Dieter
 *
 * Delete family code from database
 * ***************************************************************
 */

/* Get variables */
$delfcode = htmlspecialchars(dbfuncInt2String($_GET['key']));
$delfcodem = safe(dbfuncInt2String($_GET['key']));
$delfullname = htmlspecialchars(dbfuncInt2String($_GET['keyname']));
$nextLink = dbfuncInt2String($_GET['next']);

include "core/settermandyear.php";

if ($_POST['action'] == "Yes, delete family code") {
    $title = "LESSON - Deleting family code";
    $noJS = true;
    $noHeaderLinks = true;

    include "header.php";

    /* Check whether current user is authorized to change scores */
    if ($is_admin) {
        $errorname = "";
        $iserror = False;

        /* Check whether any users have been assigned to this family */
        $query = "SELECT Username FROM familylist WHERE FamilyCode = '$delfcodem'";
        $res = &  $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query
        if ($res->numRows() > 0) {
            $errorname .= "      <p align=\"center\">You cannot delete the $delfullname family until you remove " .
                                                    "their code ($delfcode) from every user.</p>\n";
            $iserror = True;
            log_event($LOG_LEVEL_ADMIN, "admin/family/delete.php", $LOG_ERROR,
                    "Attempted to delete the $delfullname family ($delfcode), but their code was still assigned to one or more users.");
        }

        if ($iserror) { // Check whether there have been any errors during the
            echo $errorname; // sanity checks
        } else {
            $res = &  $db->query(
                            "DELETE FROM family " . // Remove family from family table
                             "WHERE FamilyCode  = '$delfcodem'");
            if (DB::isError($res))
                die($res->getDebugInfo()); // Check for errors in query

            echo "      <p align=\"center\">The $delfullname family ($delfcode) has been successfully deleted.</p>\n";
            log_event($LOG_LEVEL_ADMIN, "admin/family/delete.php", $LOG_ADMIN,
                    "Deleted the $delfullname family ($delfcode).");
        }
        echo "      <p align=\"center\"><a href=\"$nextLink\">Continue</a></p>\n";
    } else {
        log_event($LOG_LEVEL_ERROR, "admin/family/delete.php", $LOG_DENIED_ACCESS,
                "Tried to delete the $delfullname family ($delfcode).");
        echo "      <p>You do not have the authority to remove this family.  <a href=\"$nextLink\">" .
             "Click here to continue</a>.</p>\n";
    }
} else {
    redirect($nextLink);
}

include "footer.php";
