<?php
/**
 * ***************************************************************
 * teacher/report/comment_list.php (c) 2004-2007, 2018 Jonathan Dieter
 *
 * Show available comments
 * This should only be included from modify_action.php
 * ***************************************************************
 */

$student_name = "";
$student_username = "";

/* Get variables */
$subject_index = dbfuncInt2String($_GET['key']);
$subject = dbfuncInt2String($_GET['keyname']);
if (isset($_GET['key2'])) {
    $student_username = dbfuncInt2String($_GET['key2']);
    $student_name = dbfuncInt2String($_GET['keyname2']);
}

$is_principal = check_principal($username);
$is_hod = check_hod_subject($username, $subject_index);
$is_teacher = check_teacher_subject($username, $subject_index);
$is_ct = false;
if(isset($student_username) and $student_username != "")
    $is_ct = check_class_teacher_student($username, $student_username, $yearindex, $termindex);

if (!$is_ct and !$is_teacher and !$is_admin and !$is_hod and !$is_principal) {    /* Print error message */
    include "header.php"; // Show header

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    log_event($LOG_LEVEL_ERROR, "teacher/report/comment_list.php",
            $LOG_DENIED_ACCESS, "Tried to modify report for $subject.");

    include "footer.php";
    exit(0);
}

/* Check whether subject is open for report editing */
$query = $pdb->prepare(
    "SELECT subject.AverageType, subject.EffortType, subject.ConductType, " .
    "       subject.AverageTypeIndex, subject.EffortTypeIndex, " .
    "       subject.ConductTypeIndex, subject.CommentType, subject.CanDoReport " .
    "       FROM subject " .
    "WHERE subject.SubjectIndex = :subject_index"
);
$query->execute(['subject_index' => $subject_index]);

if (!$row = $query->fetch() or $row['CanDoReport'] == 0 or !isset($st_username_click)) {
    /* Print error message */
    include "header.php"; // Show header

    echo "      <p>Reports for this subject aren't open.</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    log_event($LOG_LEVEL_ERROR, "teacher/report/comment_list.php",
            $LOG_DENIED_ACCESS, "Tried to modify report for $subject.");

    include "footer.php";
    exit(0);
}

$query = $pdb->prepare(
    "SELECT user.FirstName, user.Surname, user.Gender FROM user " .
    "WHERE  user.Username = :username"
);
$query->execute(['username' => $st_username_click]);
if (!$row = $query->fetch()) {
    include "header.php"; // Show header

    echo "      <p>Can't find $st_username_click.</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    include "footer.php";
    exit(0);
}
$student_name = "{$row['FirstName']} {$row['Surname']} ($st_username_click)";
$student_firstname = $row['FirstName'];
$student_fullname = "{$row['FirstName']} {$row['Surname']}";
$student_gender = $row['Gender'];

$title = htmlspecialchars("Comment for $student_name", ENT_QUOTES);
include "header.php";

$link = "index.php?location=" .
         dbfuncString2Int("teacher/report/modify_action.php") . "&amp;key=" .
         $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] . "&amp;next=" .
         $_GET['next'];
if (isset($_GET['key2'])) {
    $link .= "&amp;key2=" . $_GET['key2'] . "&amp;keyname2=" . $_GET['keyname2'];
}

echo "      <form action='$link' method='post' name='report'>\n"; // Form method
echo "         <input type='hidden' name='student_username' value='$st_username_click'>\n";
foreach ( $_POST as $postkey => $postval ) {
    if (substr($postkey, 0, 8) == "comment_") {
        $postval = htmlspecialchars($postval, ENT_QUOTES);
        echo "         <input type='hidden' name='$postkey' value='$postval'>\n";
    } elseif (substr($postkey, 0, 7) == "effort_" or
             substr($postkey, 0, 8) == "conduct_" or
             substr($postkey, 0, 5) == "cval_") {
        echo "         <input type='hidden' name='$postkey' value='$postval'>\n";
    }
}

echo "         <table align='center' border='1'>\n"; // Table headers
echo "            <tr>\n";
echo "               <th>#</th>\n";
echo "               <th>Comment</th>\n";
echo "               <th>&nbsp;</th>\n";
echo "            </tr>\n";

$query = $pdb->prepare(
    "SELECT comment.CommentIndex, comment.Comment " .
    "       FROM comment LEFT OUTER JOIN commenttype USING (CommentIndex), subject " .
    "WHERE (commenttype.SubjectTypeIndex IS NULL " .
    "       OR commenttype.SubjectTypeIndex = subject.SubjectTypeIndex) " .
    "AND   subject.SubjectIndex = :subject_index " .
    "ORDER BY CommentIndex"
);
$query->execute(['subject_index' => $subject_index]);

$alt_count = 0;
while ($row = $query->fetch()) {
    $alt_count += 1;

    if ($alt_count % 2 == 0) {
        $alt = " class='alt'";
    } else {
        $alt = " class='std'";
    }

    $comment_array = get_comment($st_username_click, $row['CommentIndex']);
    $comment = htmlspecialchars($comment_array[0], ENT_QUOTES);

    echo "            <tr$alt id='row_{$row['CommentIndex']}'>\n";
    echo "               <td>{$row['CommentIndex']}</td>\n";
    echo "               <td>$comment</td>\n";
    echo "               <td><input type='submit' name='cupdate_{$row['CommentIndex']}' value='Choose'></td>\n";
    echo "            </tr>\n";
}
echo "         </table>\n";
echo "         <p align='center'><input type='submit' name='comment_action' value='Cancel'></p>\n";
echo "      </form>\n";

include "footer.php";
