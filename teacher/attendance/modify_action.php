<?php
/**
 * ***************************************************************
 * teacher/attendance/modify_action.php (c) 2007-2015 Jonathan Dieter
 *
 * Insert attendance information into database
 * ***************************************************************
 */
$subject_name = dbfuncInt2String($_GET['keyname']);
$subjectindex = safe(dbfuncInt2String($_GET['key']));
$periodindex = safe(dbfuncInt2String($_GET['key2']));
$date = safe(dbfuncInt2String($_GET['key3']));
$nextLink = dbfuncInt2String($_GET['next']);
$title = "Attendance for $subject_name";

/* Check whether user is authorized to change scores */
$res = &  $db->query(
                "(SELECT Username FROM subjectteacher " .
                 " WHERE SubjectIndex = $subjectindex " .
                 " AND   Username     = \"$username\") " . "UNION " .
                 "(SELECT Username FROM disciplineperms " .
                 " WHERE Permissions >= $PUN_PERM_SUSPEND)");
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0 or dbfuncGetPermission($permissions, $PERM_ADMIN)) {
    if ($_POST["action"] == "Done") {
        $title = "LESSON - Saving attendance...";
        $noHeaderLinks = true;
        $noJS = true;

        /* See whether we're an administrator */
        $pres = &  $db->query(
                            "SELECT Username FROM disciplineperms " .
                             "WHERE Permissions >= $PUN_PERM_SUSPEND");
        if (DB::isError($pres))
            die($pres->getDebugInfo()); // Check for errors in query
        if ($pres->numRows() > 0 or dbfuncGetPermission($permissions, $PERM_ADMIN)) {
            $is_admin = true;
        } else {
            $is_admin = false;
        }

        include "header.php"; // Print header

        echo "      <p align=\"center\">Saving attendance...";

        $query = "SELECT subjectstudent.Username FROM " .
                 "       timetable, subjectstudent " .
                 "WHERE subjectstudent.SubjectIndex = $subjectindex " .
                 "AND   timetable.SubjectIndex      = $subjectindex " .
                 "AND   timetable.PeriodIndex       = $periodindex " .
                 "AND   timetable.DayIndex          = DAYOFWEEK(\"$date\") - 1 " .
                 "GROUP BY subjectstudent.Username ";
        $res = &  $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query

        while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
            if (isset($_POST["att_{$row['Username']}"])) {
                $value = $_POST["att_{$row['Username']}"];
                $valid_input = false;
                if ($value == $ATT_ABSENT or $value == $ATT_LATE or
                     $value == $ATT_IN_CLASS)
                    $valid_input = true;
                if ($value == $ATT_SUSPENDED and $is_admin)
                    $valid_input = true;
                if ($valid_input) {
                    /* Get rid of old attendance information for this student */
                    $query = "DELETE FROM attendance " .
                             "WHERE Date = \"$date\" " .
                             "AND   Username = \"{$row['Username']}\" ";
                    if (! $is_admin)
                        $query .= "AND   AttendanceTypeIndex != $ATT_SUSPENDED ";
                    $nres = &  $db->query($query);
                    if (DB::isError($nres))
                        die($nres->getDebugInfo()); // Check for errors in query
                    if ($value != $ATT_IN_CLASS) {
                        /* Check to see if there's any attendance information for this student */
                        $query = "SELECT AttendanceIndex FROM attendance " .
             "WHERE Date = \"$date\" " .
             "AND   Username = \"{$row['Username']}\" " .
             "AND   SubjectIndex   =  $subjectindex " .
             "AND   PeriodIndex    =  $periodindex ";
                        $nres = &  $db->query($query);
                        if (DB::isError($nres))
                            die($nres->getDebugInfo()); // Check for errors in query
                        if ($nres->numRows() == 0) {
                            $query = "INSERT INTO attendance (Username, AttendanceTypeIndex, SubjectIndex, " .
             "                        PeriodIndex, Date) " .
             " VALUES (\"{$row['Username']}\", $value, $subjectindex, " .
             "         $periodindex, \"$date\")";
                            $nres = &  $db->query($query);
                            if (DB::isError($nres))
                                die($nres->getDebugInfo());
                        }
                    }
                }
            }
        }
        $query = "SELECT AttendanceDoneIndex FROM attendancedone " .
                 "WHERE SubjectIndex=$subjectindex " .
                 "AND   PeriodIndex=$periodindex " . "AND   Date=\"$date\" ";
        $res = &  $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo());
        if ($res->numRows() == 0) {
            $query = "INSERT INTO attendancedone (SubjectIndex, PeriodIndex, Date, Username) " .
                 "                    VALUES ($subjectindex, $periodindex, \"$date\", \"$username\")";
            $res = &  $db->query($query);
            if (DB::isError($res))
                die($res->getDebugInfo());
        }

        echo " done</p>\n";

        echo "      <p align=\"center\"><a href=\"$nextLink\">Continue</a></p>\n"; // Link to next page
    } elseif ($_POST["action"] == "Cancel") {
        $extraMeta = "      <meta http-equiv=\"REFRESH\" content=\"0;url=$nextLink\">\n";
        $noJS = true;
        $noHeaderLinks = true;
        $title = "LESSON - Redirecting...";

        include "header.php";

        echo "      <p align=\"center\">Redirecting you to <a href=\"$nextLink\">$nextLink</a></p>\n";
    }
} else {
    include "header.php";

    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "teacher/attendance/modify_action.php",
            $LOG_DENIED_ACCESS, "Tried to do attendance for $subject_name.");

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
}

include "footer.php";
?>
