<?php
/**
 * ***************************************************************
 * teacher/casenote/new_action.php (c) 2006, 2018 Jonathan Dieter
 *
 * Insert new casenote into database
 * ***************************************************************
 */

/* Get variables */
$student_username = safe(dbfuncInt2String($_GET['key']));
$student = dbfuncInt2String($_GET['keyname']);
$link = "index.php?location=" . dbfuncString2Int("teacher/casenote/list.php") .
         "&amp;key=" . $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] .
         "&amp;keyname2=" . $_GET['keyname2'];

include "core/settermandyear.php";

$is_principal = check_principal($username);
$is_hod = check_hod_student($username, $student_username, $currentyear, $currentterm);
$is_counselor = check_counselor($username);
$is_class_teacher = check_class_teacher_student($username, $student_username,
                                                $currentyear, $currentterm);
$is_support_teacher = false;
$is_teacher = check_teacher_student($username, $student_username, $currentyear,
                                    $currentterm);

if (!$is_principal and !$is_hod and !$is_counselor and !$is_class_teacher and
    !$is_support_teacher and !$is_teacher) {
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "teacher/casenote/new_action.php",
            $LOG_DENIED_ACCESS, "Tried to create new casenote for $student.");
    $title = "LESSON - Unauthorized access";
    $noHeaderLinks = true;
    $noJS = true;

    include "header.php"; // Print header

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    include "footer.php";
    exit(0);
}

/* If whatever button was pressed wasn't save, redirect back */
if ($_POST["action"] != "Save") {
    redirect($nextLink);
    exit(0);
}

$title = "LESSON - Saving casenote...";
$noHeaderLinks = true;
$noJS = true;

include "header.php"; // Print header

echo "      <p align='center'>Saving casenote...";

/* Check whether or not a casenote was included and cancel if it wasn't */
if ($_POST['note'] == "") {
    echo "failed</p>\n";
    echo "      <p align='center'>There is no point in saving an empty casenote</p>\n";
    echo "      <p align='center'><a href='$link'>Continue</a></p>\n"; // Link to next page

    include "footer.php";
    exit(0);
}

$note = str_replace("\n", "<br>\n", $_POST['note']);
$note = "<p>$note</p>";
$level = $_POST['level'];

/* Insert into casenote table */
$pdb->prepare(
    "INSERT INTO casenote (WorkerUsername, StudentUsername, " .
    "                      Note, Level, Date) " .
    "       VALUES " .
    "       (:username, :student_username, :note, :level, NOW())"
)->execute(['username' => $username, 'student_username' => $student_username,
            'note' => $note, 'level' => $level]);
$cn_index = $pdb->lastInsertId('CaseNoteIndex');

if ($level == 3) {
    foreach ( $_POST['counselor_list'] as $counselor ) {
        /* Set counselor as someone who can read casenote */
        $pdb->prepare(
            "INSERT INTO casenotelist (WorkerUsername, " .
            "                          CaseNoteIndex) " .
            "       VALUES " .
            "       (:counselor, :cn_index)"
        )->execute(['counselor' => $counselor, 'cn_index' => $cn_index]);
    }
}

log_event($LOG_LEVEL_TEACHER, "teacher/casenote/new_action.php",
        $LOG_TEACHER, "Created new casenote for $student.");

if($level < 1) {
    echo " done</p>\n";

    echo "      <p align='center'><a href='$link'>Continue</a></p>\n"; // Link to next page

    include "footer.php";
    exit(0);
}

$new_list = array();

/* Build list of principals */
$query = $pdb->query(
    "SELECT Username " .
    "       FROM principal " .
    "WHERE Level = 1 "
);
while($row = $query->fetch())
    $new_list[] = $row['Username'];

if ($level < 5) {
    /* Build list of relevant head of departments */
    $query = $pdb->prepare(
        "SELECT hod.Username " .
        "       FROM hod, class, classterm, classlist " .
        "WHERE hod.DepartmentIndex = class.DepartmentIndex " .
        "AND   class.YearIndex = :currentyear " .
        "AND   class.ClassIndex = classterm.ClassIndex " .
        "AND   classterm.TermIndex = :currentterm " .
        "AND   classterm.ClassTermIndex = classlist.ClassTermIndex " .
        "AND   classlist.Username = :student_username "
    );
    $query->execute(['student_username' => $student_username,
                     'currentyear' => $currentyear,
                     'currentterm' => $currentterm]);
    while ($row = $query->fetch())
        $new_list[] = $row['Username'];
}

/* Specified Counselors */
if ($level == 3) {
    $query = $pdb->prepare(
        "SELECT WorkerUsername FROM casenotelist " .
        "WHERE  CaseNoteIndex = :cn_index"
    );
    $query->execute(['cn_index' => $cn_index]);
    while ($row = $query->fetch())
        $new_list[] = $row['WorkerUsername'];
}

/* Applicable Counselors */
if ($level <= 3) {
    $query = $pdb->prepare(
        "SELECT WorkerUsername FROM casenotewatch " .
        "WHERE  StudentUsername = :student_username "
    );
    $query->execute(['student_username' => $student_username]);
    while ($row = $query->fetch())
        $new_list[] = $row['WorkerUsername'];
}

/* Class teacher */
if ($level < 3) {
    $query = $pdb->prepare(
        "SELECT class.ClassTeacherUsername " .
        "       FROM class, classterm, classlist " .
        "WHERE class.YearIndex = :currentyear " .
        "AND   class.ClassIndex = classterm.ClassIndex " .
        "AND   classterm.TermIndex = :currentterm " .
        "AND   classterm.ClassTermIndex = classlist.ClassTermIndex " .
        "AND   classlist.Username = :student_username "
    );
    $query->execute(['student_username' => $student_username,
                     'currentyear' => $currentyear,
                     'currentterm' => $currentterm]);
    while ($row = $query->fetch())
        $new_list[] = $row['ClassTeacherUsername'];
}

/* Any current teacher */
if ($level < 2) {
    $query = $pdb->prepare(
        "(SELECT subjectteacher.Username FROM subject, " .
        "        subjectteacher, subjectstudent " .
        " WHERE  subjectteacher.SubjectIndex = " .
        "        subjectstudent.SubjectIndex " .
        " AND    subjectstudent.Username = :student_username " .
        " AND    subject.SubjectIndex = subjectteacher.SubjectIndex " .
        " AND    subject.YearIndex = :currentyear " .
        " AND    subject.TermIndex = :currentterm) " .
        "UNION " .
        "(SELECT user.Username FROM user, support, groups, groupgenmem " .
        " WHERE  support.StudentUsername = :student_username " .
        " AND    support.WorkerUsername  = user.Username " .
        " AND    groupgenmem.Username    = :username " .
        " AND    groups.GroupID          = groupgenmem.GroupID " .
        " AND    groups.GroupTypeID      = 'supportteacher' " .
        " AND    groups.YearIndex        = :currentyear) "
    );
    $query->execute(['student_username' => $student_username,
                     'currentyear' => $currentyear, 'currentterm' => $currentterm,
                     'username' => $username]);
    while ($row = $query->fetch())
        $new_list[] = $row['Username'];
}

$new_list = array_unique($new_list);
foreach($new_list as $wl_username) {
    if($wl_username != $username and $wl_username != "" and $wl_username != NULL) {
        $pdb->prepare(
            "INSERT INTO casenotenew (CaseNoteIndex, WorkerUsername) " .
            "    VALUES (:cn_index, :wl_username)"
        )->execute(['cn_index' => $cn_index, 'wl_username' => $wl_username]);
    }
}

echo " done</p>\n";

echo "      <p align='center'><a href='$link'>Continue</a></p>\n"; // Link to next page

include "footer.php";
