<?php
/**
 * ***************************************************************
 * teacher/punishment/request/new_action.php (c) 2006, 2018 Jonathan Dieter
 *
 * Insert new punishment request into database
 * ***************************************************************
 */

/* Get variables */
$student_username = dbfuncInt2String($_GET['key']);
$student = dbfuncInt2String($_GET['keyname']);
$link = dbfuncInt2String($_GET['next']);

$is_teacher = check_teacher_year($currentyear);
$perm = get_punishment_permissions($username);

include "core/settermandyear.php";

// User isn't authorized to create punishment request
if (!$is_admin and ($perm < $PUN_PERM_REQUEST or !$is_teacher)) {
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "teacher/punishment/request/new_action.php",
            $LOG_DENIED_ACCESS,
            "Tried to create a punishment request for $student.");
    $title = "LESSON - Unauthorized access";
    $noHeaderLinks = true;
    $noJS = true;

    include "header.php"; // Print header

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    include "footer.php";
    exit(0);
}

function end_page($link) {
    echo "      <p align='center'><a href='$link'>Continue</a></p>\n"; // Link to next page

    include "footer.php";
    exit(0);
}

/* Check which button was pressed */
if ($_POST["action"] != "Save" and $_POST["action"] != "Update") {
    redirect($link);
    exit(0);
}

$title = "LESSON - Saving punishment request...";
$noHeaderLinks = true;
$noJS = true;

include "header.php"; // Print header

echo "      <p align='center'>Saving punishment request...";

if (! isset($_POST['date']) || $_POST['date'] == "") { // Make sure date is in correct format.
    echo "</p>\n      <p>Date not entered, defaulting to today.</p>\n      <p>"; // Print error message
    $_POST['date'] = & dbfuncCreateDate(date($dateformat));
} else {
    $_POST['date'] = & dbfuncCreateDate($_POST['date']);
}
$dateinfo = $_POST['date'];
$thisdateinfo = dbfuncCreateDate(date($dateformat));

/* Check whether or not a type was included and cancel if it wasn't */
if (!isset($_POST['type']) or $_POST['type'] == "" or is_null($_POST['type'])) {
    echo "failed</p>\n";
    echo "      <p align='center'>You must select a punishment type!</p>\n";
    end_page($link);
}

$query = $pdb->prepare(
    "SELECT DisciplineWeightIndex FROM disciplineweight " .
    "WHERE  disciplineweight.DisciplineTypeIndex = :type " .
    "AND    disciplineweight.YearIndex = :currentyear " .
    "AND    disciplineweight.TermIndex = :currentterm "
);
$query->execute(['type' => $_POST['type'], 'currentyear' => $currentyear,
                 'currentterm' => $currentterm]);

$failed = 0;
$row = $query->fetch();
if(!$row) {
    echo "failed</p>\n";
    echo "      <p align='center'>There is no punishment of selected type!</p>\n";
    end_page($link);
}

$weightindex = $row['DisciplineWeightIndex'];
if (!isset($_POST['reason']) or $_POST['reason'] == "" or is_null($_POST['reason'])) {
    echo "failed</p>\n";
    echo "      <p align='center'>You must explain why you want the student punished!</p>\n";
    end_page($link);
}

if ($_POST['reason'] == "other") {
    if(!isset($_POST['reasonother']) or $_POST['reasonother'] == "" or
       is_null($_POST['reasonother'])) {
        echo "failed</p>\n";
        echo "      <p align='center'>You must explain why you want the student punished!</p>\n";
        end_page($link);
    } else {
        $reason = $_POST['reasonother'];
    }
} else {
    $nquery = $pdb->prepare(
        "SELECT DisciplineReason FROM disciplinereason " .
        "WHERE  DisciplineReasonIndex = :reason_index"
    );
    $nquery->execute(['reason_index' => $_POST['reason']]);
    if($nrow = $nquery->fetch()) {
        $reason = $row['DisciplineReason'];
    } else {
        echo "failed</p>\n";
        echo "      <p align='center'>You must explain why you want the student punished!</p>\n";
        end_page($link);
    }
}

$pdb->prepare(
    "INSERT INTO disciplinebacklog (DisciplineTypeIndex, Username, WorkerUsername, " .
    "                               Date, DateOfViolation, RequestType, Comment) " .
    "       VALUES " .
    "       (:type, :student_username, :username, :tdinfo, :dinfo, 1, " .
    "        :reason)"
)->execute(['type' => $_POST['type'],
            'student_username' => $student_username,
            'username' => $username, 'tdinfo' => $thisdateinfo,
            'dinfo' => $dateinfo, 'reason' => $reason]);
echo " done</p>\n";
log_event($LOG_LEVEL_TEACHER,
        "teacher/punishment/request/new_action.php",
        $LOG_TEACHER,
        "Created new punishment request for $student.");

end_page($link);
