<?php
/**
 * ***************************************************************
 * admin/subject/modify_by_student_action.php (c) 2006 Jonathan Dieter
 *
 * Add or remove student from subjects
 * ***************************************************************
 */

/* Get variables */
$nextLink = dbfuncInt2String($_GET['next']); // Link to next page
$studentusername = dbfuncInt2String($_GET['key']); // Student username
$student = dbfuncInt2String($_GET['keyname']);

$showalldeps = true;
include "core/settermandyear.php";

/* Check whether user is authorized to change subject */
if (dbfuncGetPermission($permissions, $PERM_ADMIN)) {
    /* Check which button was pressed */
    if ($_POST["action"] == ">>") { // If >> was pressed, remove student from
        foreach ( $_POST['removesubject'] as $remSubject ) { // subjects
            if (substr($remSubject, 0, 1) == "!") {
                $remSubject = substr($remSubject, 1);
                $forceRemove = true;
            }
            $res = &  $db->query(
                            "SELECT Name FROM subject " .
                             "WHERE SubjectIndex = $remSubject");
            if (DB::isError($res))
                die($res->getDebugInfo()); // Check for errors in query
            if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
                $subjectname = $row['Name'];
            } else {
                die("Subject doesn't exist!");
            }
            $res = &  $db->query(
                            "SELECT user.FirstName, user.Surname, mark.Username, subject.Name " .
                             "       FROM subject, mark, assignment, user " .
                             "WHERE mark.Username = '$studentusername' " .
                             "AND   mark.AssignmentIndex = assignment.AssignmentIndex " .
                             "AND   user.Username = mark.Username " .
                             "AND   assignment.SubjectIndex = $remSubject " .
                             "AND   assignment.SubjectIndex = subject.SubjectIndex " .
                             "AND   mark.Score > 0");
            if (DB::isError($res))
                die($res->getDebugInfo()); // Check for errors in query

            if ($res->numRows() > 0 && ! $forceRemove) { // If there's at least one mark with a score or comment,
                $row = & $res->fetchRow(DB_FETCHMODE_ASSOC); // and we're not force the removal, pop up an error
                $errorlist[$remSubject] = "{$row['Name']}"; // message
            } else { // Remove all null score and comment marks, then remove user from subject
                $nRes = & $db->query(
                        "SELECT AssignmentIndex FROM assignment WHERE SubjectIndex = $remSubject");
                if (DB::isError($nRes))
                    die($nRes->getDebugInfo()); // Check for errors in query
                while ( $nRow = & $nRes->fetchRow(DB_FETCHMODE_ASSOC) ) { // This is a work-around for early (<4.0) versions
                    $res = &  $db->query(
                        "DELETE FROM mark " . // of MySQL
                         "WHERE mark.Username = '$studentusername' " .
                         "AND   mark.AssignmentIndex = {$nRow['AssignmentIndex']}");
                    if (DB::isError($res))
                        die($res->getDebugInfo()); // Check for errors in query
                }
                $res = &  $db->query(
                                "DELETE FROM subjectstudent " .
                                 "WHERE Username     = \"$studentusername\" " .
                                 "AND   SubjectIndex = $remSubject");
                if (DB::isError($res))
                    die($res->getDebugInfo()); // Check for errors in query
                log_event($LOG_LEVEL_ADMIN, "admin/subject/modify_by_student_action.php",
            $LOG_ADMIN, "Removed $student from subject $subjectname.");
            }
        }
        update_conduct_mark($studentusername);
        include "admin/subject/modify_by_student.php";
    } elseif ($_POST["action"] == "<<") { // If << was pressed, add students to
        foreach ( $_POST['addsubject'] as $addSubject ) { // subject
            $res = &  $db->query(
                    "SELECT Username FROM subjectstudent " .
                             "WHERE Username     = \"$studentusername\" " .
                             "AND   SubjectIndex = $addSubject");
            if (DB::isError($res))
                die($res->getDebugInfo()); // Check for errors in query
            if ($res->numRows() == 0) {
                $res = &  $db->query(
                                "SELECT Name FROM subject " .
                         "WHERE SubjectIndex = $addSubject");
                if (DB::isError($res))
                    die($res->getDebugInfo()); // Check for errors in query
                if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
                    $subjectname = $row['Name'];
                } else {
                    die("Subject doesn't exist!");
                }
                $res = & $db->query(
                                "INSERT INTO subjectstudent (Username, SubjectIndex) VALUES " .
                                 "                           (\"$studentusername\", $addSubject)");
                if (DB::isError($res))
                    die($res->getDebugInfo()); // Check for errors in query
                log_event($LOG_LEVEL_ADMIN, "admin/subject/modify_by_student_action.php",
            $LOG_ADMIN, "Added $student to subject $subjectname.");
            }
        }
        update_conduct_mark($studentusername);
        include "admin/subject/modify_by_student.php";
    } elseif ($_POST["action"] == "Done") {
        $extraMeta = "      <meta http-equiv=\"REFRESH\" content=\"0;url=$nextLink\">\n";
        $noJS = true;
        $noHeaderLinks = true;
        $title = "LESSON - Redirecting...";

        include "header.php";

        echo "      <p align=\"center\">Redirecting you to <a href=\"$nextLink\">$nextLink</a></p>\n";

        include "footer.php";
    } else {
        include "admin/subject/modify_by_student.php";
    }
} else {
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "admin/subject/modify_by_student_action.php",
            $LOG_DENIED_ACCESS,
            "Attempted to modify subjects for student $student.");

    $noJS = true;
    $noHeaderLinks = true;
    $title = "LESSON - Unauthorized access!";

    include "header.php";

    echo "      <p align=\"center\">You do not have permission to access this page. <a href=" .
         "\"$nextLink\">Click here to continue.</a></p>\n";

    include "footer.php";
}

?>
