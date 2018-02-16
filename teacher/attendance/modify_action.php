<?php
/**
 * ***************************************************************
 * teacher/attendance/modify_action.php (c) 2007-2015, 2018 Jonathan Dieter
 *
 * Insert attendance information into database
 * ***************************************************************
 */
$subject_name = dbfuncInt2String($_GET['keyname']);
$subject_index = dbfuncInt2String($_GET['key']);
$periodindex = dbfuncInt2String($_GET['key2']);
$date = dbfuncInt2String($_GET['key3']);
$nextLink = dbfuncInt2String($_GET['next']);
$title = "Attendance for $subject_name";

/* Check whether user is authorized to do attendance */
if (!check_attendance($username, $subject_index) and !$is_admin) {
    include "header.php";

    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "teacher/attendance/modify_action.php",
            $LOG_DENIED_ACCESS, "Tried to do attendance for $subject_name.");

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    include "footer.php";
    exit(0);
}

if ($_POST["action"] == "Done") {
    $title = "LESSON - Saving attendance...";
    $noHeaderLinks = true;
    $noJS = true;

    /* See whether we have permission to set student as suspended */
    if (get_punishment_permissions($username) > $PUN_PERM_SUSPEND or $is_admin) {
        $is_admin = true;
    } else {
        $is_admin = false;
    }

    include "header.php"; // Print header

    echo "      <p align='center'>Saving attendance...";

    $query = $pdb->prepare(
        "SELECT subjectstudent.Username FROM " .
        "       timetable, subjectstudent " .
        "WHERE subjectstudent.SubjectIndex = :subject_index " .
        "AND   timetable.SubjectIndex      = subjectstudent.SubjectIndex " .
        "AND   timetable.PeriodIndex       = :periodindex " .
        "AND   timetable.DayIndex          = DAYOFWEEK(:date) - 1 " .
        "GROUP BY subjectstudent.Username "
    );
    $query->execute(['subject_index' => $subject_index,
                     'periodindex' => $periodindex, 'date' => $date]);

    foreach($query as $row) {
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
                         "WHERE Date = :date " .
                         "AND   Username = :username ";
                $exec_array = ['date' => $date, 'username' => $row['Username']];
                if (! $is_admin) {
                    $query .= "AND   AttendanceTypeIndex != :suspended ";
                    $exec_array = array_merge($exec_array, ['suspended' => $ATT_SUSPENDED]);
                }
                $pdb->prepare($query)->execute($exec_array);

                if ($value != $ATT_IN_CLASS) {
                    $pdb->prepare(
                        "INSERT INTO attendance (Username, AttendanceTypeIndex, SubjectIndex, " .
                        "                        PeriodIndex, Date) " .
                        " VALUES (?, ?, ?, ?, ?)"
                    )->execute([$row['Username'], $value, $subject_index,
                                $periodindex, $date]);
                }
            }
        }
    }
    $query = $pdb->prepare(
        "SELECT AttendanceDoneIndex FROM attendancedone " .
        "WHERE SubjectIndex=:subject_index " .
        "AND   PeriodIndex=:periodindex " .
        "AND   Date=:date "
    );
    $query->execute(['subject_index' => $subject_index,
                     'periodindex' => $periodindex, 'date' => $date]);
    $row = $query->fetch();
    if (!$row) {
        $pdb->prepare(
            "INSERT INTO attendancedone (SubjectIndex, PeriodIndex, Date, Username) " .
            "                    VALUES (?, ?, ?, ?)"
        )->execute([$subject_index, $periodindex, $date, $username]);
    }

    echo " done</p>\n";

    echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n"; // Link to next page
} elseif ($_POST["action"] == "Cancel") {
    redirect($nextLink);
}

include "footer.php";
