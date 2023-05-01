<?php
/**
 * ***************************************************************
 * teacher/assignment/student_photos.php (c) 2016, 2018 Jonathan Dieter
 *
 * Show student photos
 * ***************************************************************
 */

/* Get variables */
$title = dbfuncInt2String($_GET['keyname']);
$subject_index = dbfuncInt2String($_GET['key']);

include "header.php"; // Show header
include "core/settermandyear.php";

/* Check whether user is authorized to change scores */
if (check_teacher_subject($username, $subject_index))
    $is_teacher = true;

if (check_support_teacher_subject($username, $subject_index))
    $is_support_class_teacher = true;

if (!$is_teacher and !$is_support_class_teacher and !$is_admin) {
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "teacher/assignment/student_photos.php", $LOG_DENIED_ACCESS,
        "Tried to access student photos for $title.");

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

$nochangeyt = true;

include "core/titletermyear.php";
$printlink = "index.php?location=" .
    dbfuncString2Int("teacher/assignment/student_photos.php") . "&amp;key=" .
    dbfuncString2Int($subject_index) . "&amp;keyname=" .
    $_GET['keyname'];

$prtbutton = dbfuncGetButton($printlink, "Print", "medium", "",
                            "View printable page");

/*echo "      <p align='center'>$prtbutton</p>\n";*/

echo "      <table align='center' border='1'>\n"; // Table headers
echo "         <tr>\n";
echo "            <th>&nbsp;</th>\n";
echo "            <th>Student</th>\n";

$query = $pdb->prepare(
    "SELECT user.FirstName, user.Surname, user.Username, " .
    "       smallimage.FileIndex AS SmallIndex, smallimage.Height, smallimage.Width, " .
    "       largeimage.FileIndex AS LargeIndex " .
    "       FROM subjectstudent " .
    "       INNER JOIN user ON " .
    "         (user.Username=subjectstudent.Username " .
    "          AND subjectstudent.SubjectIndex=:subject_index) " .
    "       LEFT OUTER JOIN (SELECT photo.* FROM photo LEFT OUTER JOIN photo AS newphoto " .
    "                             ON (photo.Username=newphoto.Username " .
    "                                 AND photo.YearIndex < newphoto.YearIndex " .
    "                                 AND newphoto.YearIndex <= :yearindex) " .
    "                             WHERE photo.YearIndex <= :yearindex " .
    "                             AND newphoto.YearIndex IS NULL) AS photo ON " .
    "              (user.Username=photo.Username) " .
    "       LEFT OUTER JOIN image AS largeimage ON (photo.LargeImageIndex=largeimage.ImageIndex) " .
    "       LEFT OUTER JOIN image AS smallimage ON (photo.SmallImageIndex=smallimage.ImageIndex) " .
    "WHERE user.Username = subjectstudent.Username " .
    "AND   subjectstudent.SubjectIndex = :subject_index " .
    "ORDER BY user.FirstName, user.Surname, user.Username"
);
$query->execute(['subject_index' => $subject_index, 'yearindex' => $yearindex]);

$alt_count = 0;
$order = 1;

foreach($query as $row) {
    $alt_count += 1;
    if ($alt_count % 2 == 0) {
        $alt_step = "alt";
    } else {
        $alt_step = "std";
    }

    $alt = " class='$alt_step'";
    echo "         <tr$alt>\n";

    if(!is_null($row['LargeIndex'])) {
        $link = get_path_from_id($row['LargeIndex']);
        $img = get_path_from_id($row['SmallIndex']);
        $img = "<img src='$img' height='{$row['Height']}' width='{$row['Width']}' />";
    } else {
        $link = "";
        $img = "None";
    }
    echo "            <td><a href='$link'>$img</a></td>\n";
    echo "            <td nowrap><a href='$link'>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</a></td>\n";
    echo "         </tr>\n";
}

include "footer.php";
