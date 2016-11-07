<?php
/**
 * ***************************************************************
 * admin/subject/delete.php (c) 2005 Jonathan Dieter
 *
 * Delete subject from database
 * ***************************************************************
 */

/* Get variables */
$subjectindex = dbfuncInt2String($_GET['key']);
$subject = dbfuncInt2String($_GET['keyname']);
$nextLink = dbfuncInt2String($_GET['next']);

include "core/settermandyear.php";

if ($_POST['action'] == "Yes, delete subject") {
    $title = "LESSON - Deleting subject";
    $noJS = true;
    $noHeaderLinks = true;

    include "header.php";

    /* Check whether current user is authorized to change scores */
    if (dbfuncGetPermission($permissions, $PERM_ADMIN)) {
        $errorname = "";
        $iserror = False;

        $res = &  $db->query(
                        "SELECT Username FROM subjectstudent " . // Check whether user to be deleted is in any class
                         "WHERE SubjectIndex = $subjectindex");
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query
        if ($res->numRows() > 0) {
            $errorname .= "      <p align=\"center\">You cannot delete $subject until you remove all students from the " .
         "subject.</p>\n";
            $iserror = True;
            log_event($LOG_LEVEL_ADMIN, "admin/subject/delete.php", $LOG_ERROR,
                    "Attempted to delete subject $subject, but there were still students in it.");
        }

        if ($iserror) { // Check whether there have been any errors during the
            echo $errorname; // sanity checks
        } else {
            $res = &  $db->query(
                            "DELETE FROM subjectteacher " . // Remove any teachers from subject
                             "WHERE SubjectIndex = $subjectindex");
            if (DB::isError($res))
                die($res->getDebugInfo()); // Check for errors in query

            $nRes = & $db->query(
                                "SELECT AssignmentIndex, Title FROM assignment WHERE SubjectIndex = $subjectindex");
            if (DB::isError($nRes))
                die($nRes->getDebugInfo()); // Check for errors in query
            while ( $nRow = & $nRes->fetchRow(DB_FETCHMODE_ASSOC) ) { // This is a work-around for early (<4.0) versions
                $res = &  $db->query(
                    "DELETE FROM mark " . // of MySQL
                     "WHERE mark.AssignmentIndex = {$nRow['AssignmentIndex']}");
                if (DB::isError($res))
                    die($res->getDebugInfo()); // Check for errors in query
                echo "      <p align=\"center\">Removing \"{$nRow['Title']}\".</p>\n";
            }

            $res = &  $db->query(
                            "DELETE FROM assignment " . // Remove all assignments for subject
                             "WHERE SubjectIndex = $subjectindex");
            if (DB::isError($res))
                die($res->getDebugInfo()); // Check for errors in query

            $res = &  $db->query(
                            "DELETE FROM subject " . // Remove subject from subject table
                             "WHERE SubjectIndex = $subjectindex");
            if (DB::isError($res))
                die($res->getDebugInfo()); // Check for errors in query

            echo "      <p align=\"center\">$subject successfully deleted.</p>\n";
            log_event($LOG_LEVEL_ADMIN, "admin/subject/delete.php", $LOG_ADMIN,
                    "Deleted subject $subject.");
        }
        echo "      <p align=\"center\"><a href=\"$nextLink\">Continue</a></p>\n";
    } else {
        log_event($LOG_LEVEL_ERROR, "admin/subject/delete.php",
                $LOG_DENIED_ACCESS, "Tried to delete subject $subject.");
        echo "      <p>You do not have the authority to remove this subject.  <a href=\"$nextLink\">" .
             "Click here to continue</a>.</p>\n";
    }
} else {
    redirect($nextLink);
}

include "footer.php";
