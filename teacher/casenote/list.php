<?php
/**
 * ***************************************************************
 * teacher/casenote/list.php (c) 2006, 2016-2018 Jonathan Dieter
 *
 * Show list of casenotes for student
 * ***************************************************************
 */

/* Get variables */
$student = dbfuncInt2String($_GET['keyname']);
$student_first_name = dbfuncInt2String($_GET['keyname2']);
$student_username = dbfuncInt2String($_GET['key']);

$title = "Casenotes for $student";

include "core/settermandyear.php";
include "header.php";

/* Check whether student is in current user's watchlist */
$query = $pdb->prepare(
    "SELECT WorkerUsername FROM casenotewatch " .
    "WHERE StudentUsername=:student_username " .
    "AND   WorkerUsername=:username"
);
$query->execute(['student_username' => $student_username,
                 'username' => $username]);
$row = $query->fetch();
if ($row) {
    $is_on_wl = true;
} else {
    $is_on_wl = false;
}

/* Check whether current user has ever written a casenote for this student */
$query = $pdb->prepare(
    "SELECT WorkerUsername FROM casenote " .
    "WHERE WorkerUsername = :username " .
    "AND   StudentUsername = :student_username"
);
$query->execute(['student_username' => $student_username,
                 'username' => $username]);
$row = $query->fetch();
if ($row) {
    $prev_cn = true;
} else {
    $prev_cn = false;
}

$is_principal = check_principal($username);
$is_hod = check_hod($username, $student_username, $currentyear, $currentterm);
$is_counselor = check_counselor($username);
$is_class_teacher = check_class_teacher_student($username, $student_username,
                                                $currentyear, $currentterm);
$is_support_teacher = false;
$is_teacher = check_teacher_student($username, $student_username, $currentyear,
                                    $currentterm);

if (!$is_principal and !$is_hod and !$is_counselor and !$is_class_teacher and
    !$is_support_teacher and !$is_teacher and !$prev_cn) {
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "teacher/casenote/list.php", $LOG_DENIED_ACCESS,
            "Tried to access casenotes for $student_username.");

    /* Print error message */
    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";

    include "footer.php";
    exit(0);
}

log_event($LOG_LEVEL_EVERYTHING, "teacher/casenote/list.php", $LOG_TEACHER,
        "Viewed casenotes for $student.");

/* Build list of principals */
$query = $pdb->query(
    "SELECT user.Title, user.FirstName, user.Surname " .
    "       FROM principal, user " .
    "WHERE Level = 1 " .
    "AND   principal.Username = user.Username"
);
$principal_list = array();
while ( $row = $query->fetch() ) {
    $principal_list[] = "{$row['Title']} {$row['FirstName']} {$row['Surname']}";
}

/* Build list of relevant head of departments */
$query = $pdb->prepare(
    "SELECT user.Title, user.FirstName, user.Surname " .
    "       FROM hod, class, classterm, classlist, user " .
    "WHERE hod.DepartmentIndex = class.DepartmentIndex " .
    "AND   class.YearIndex = :currentyear " .
    "AND   class.ClassIndex = classterm.ClassIndex " .
    "AND   classterm.TermIndex = :currentterm " .
    "AND   classterm.ClassTermIndex = classlist.ClassTermIndex " .
    "AND   classlist.Username = :student_username " .
    "AND   hod.Username = user.Username"
);
$query->execute(['student_username' => $student_username,
                 'currentyear' => $currentyear,
                 'currentterm' => $currentterm]);
$hod_list = array();
while ( $row = $query->fetch() ) {
    $hod_list[] = "{$row['Title']} {$row['FirstName']} {$row['Surname']}";
}

/* Build list of this student's class teacher for this year */
$query = $pdb->prepare(
    "SELECT user.Title, user.FirstName, user.Surname " .
    "       FROM class, classterm, classlist, user " .
    "WHERE class.ClassTeacherUsername = user.Username " .
    "AND   class.YearIndex = :currentyear " .
    "AND   class.ClassIndex = classterm.ClassIndex " .
    "AND   classterm.TermIndex = :currentterm " .
    "AND   classterm.ClassTermIndex = classlist.ClassTermIndex " .
    "AND   classlist.Username = :student_username "
);
$query->execute(['student_username' => $student_username,
                 'currentyear' => $currentyear,
                 'currentterm' => $currentterm]);
$ct_list = array();
while ( $row = $query->fetch() ) {
    $ct_list[] = "{$row['Title']} {$row['FirstName']} {$row['Surname']}";
}

$writable = true;
if ($is_principal) {
    $query = "SELECT user.FirstName, user.Surname, user.Username, " .
             "       user.Title, casenote.CaseNoteIndex, casenote.Note, " .
             "       casenote.Level, casenote.Date " .
             "       FROM user, casenote " .
             "WHERE user.Username = casenote.WorkerUsername " .
             "AND   (casenote.Level > 0 OR " .
             "       casenote.WorkerUsername = :username " .
             "AND   casenote.StudentUsername = :student_username " .
             "ORDER BY casenote.Date DESC, casenote.CaseNoteIndex DESC";
} elseif ($is_hod) {
    $qstr = "SELECT user.FirstName, user.Surname, user.Username, " .
             "       user.Title, casenote.CaseNoteIndex, casenote.Note, " .
             "       casenote.Level, casenote.Date " .
             "       FROM user, casenote " .
             "WHERE user.Username = casenote.WorkerUsername " .
             "AND   ((casenote.Level > 0 AND casenote.Level < 5) OR " .
             "       casenote.WorkerUsername = :username) " .
             "AND   casenote.StudentUsername = :student_username " .
             "ORDER BY casenote.Date DESC, casenote.CaseNoteIndex DESC";
} elseif ($is_counselor) {
    $qstr =
        "SELECT user.FirstName, user.Surname, user.Username, " .
        "       user.Title, casenote.CaseNoteIndex, casenote.Note, " .
        "       casenote.Level, casenote.Date " .
        "       FROM user, casenote LEFT OUTER JOIN casenotelist " .
        "       ON casenote.CaseNoteIndex = casenotelist.CaseNoteIndex " .
        "WHERE user.Username = casenote.WorkerUsername " .
        "AND   ((casenote.Level > 0 AND casenote.Level < 3) OR " .
        "       (casenote.Level = 3 AND " .
        "        (casenotelist.WorkerUsername = :username OR " .
        "         casenotelist.WorkerUsername IS NULL)) OR " .
        "       (casenote.WorkerUsername = :username)) " .
        "AND   casenote.StudentUsername = :student_username " .
        "GROUP BY casenote.CaseNoteIndex " .
        "ORDER BY casenote.Date DESC, casenote.CaseNoteIndex DESC";
} elseif ($is_class_teacher) {
    $qstr =
        "SELECT user.FirstName, user.Surname, user.Username, " .
        "       user.Title, casenote.CaseNoteIndex, casenote.Note, " .
        "       casenote.Level, casenote.Date " .
        "       FROM user, casenote " .
        "WHERE user.Username = casenote.WorkerUsername " .
        "AND   ((casenote.Level > 0 AND casenote.Level < 3) OR " .
        "       casenote.WorkerUsername = :username)" .
        "AND   casenote.StudentUsername = :student_username " .
        "ORDER BY casenote.Date DESC, casenote.CaseNoteIndex DESC";
} elseif ($is_support_teacher or $is_teacher) {
    $qstr =
        "SELECT user.FirstName, user.Surname, user.Username, " .
        "       user.Title, casenote.CaseNoteIndex, casenote.Note, " .
        "       casenote.Level, casenote.Date " .
        "       FROM user, casenote " .
        "WHERE user.Username = casenote.WorkerUsername " .
        "AND   ((casenote.Level > 0 AND casenote.Level < 2) OR " .
        "       casenote.WorkerUsername = :username)" .
        "AND   casenote.StudentUsername = :student_username " .
        "ORDER BY casenote.Date DESC, casenote.CaseNoteIndex DESC";
} else {
    $qstr =
        "SELECT user.FirstName, user.Surname, user.Username, " .
        "       user.Title casenote.CaseNoteIndex, casenote.Note, " .
        "       casenote.Level, casenote.Date " .
        "       FROM user, casenote " .
        "WHERE user.Username = casenote.WorkerUsername " .
        "AND   casenote.WorkerUsername = :username " .
        "AND   casenote.StudentUsername = :student_username " .
        "ORDER BY casenote.Date DESC, casenote.CaseNoteIndex DESC";
    $writable = false;
}

/* Clear new casenotes flag for current student */
$pdb->prepare(
    "DELETE casenotenew.* FROM casenotenew, casenote " .
    "WHERE casenotenew.WorkerUsername=:username " .
    "AND   casenote.CaseNoteIndex=casenotenew.CaseNoteIndex " .
    "AND   casenote.StudentUsername=:student_username "
)->execute(['username' => $username,
            'student_username' => $student_username]);
if ($writable) {
    /* Check to see if we are supposed to add someone to the watchlist */
    if (isset($_GET['key2'])) {
        if (dbfuncInt2String($_GET['key2']) == "add") {
            $pdb->prepare(
                "INSERT INTO casenotewatch (CaseNoteWatchIndex, " .
                "            WorkerUsername, StudentUsername) " .
                "       VALUES " .
                "            (:key, :username, :student_username)"
            )->execute(['key' => "{$username}{$student_username}",
                        'username' => $username,
                        'student_username' => $student_username]);
            $is_on_wl = true;
        } elseif (dbfuncInt2String($_GET['key2']) == "remove") {
            $pdb->prepare(
                "DELETE FROM casenotewatch " .
                "WHERE CaseNoteWatchIndex=:key"
            )->execute(['key' => "{$username}{$student_username}"]);
            $is_on_wl = false;
        }
    }

    $addLink = "index.php?location=" .
             dbfuncString2Int("teacher/casenote/new.php") . "&amp;key=" .
             $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] .
             "&amp;keyname2=" . $_GET['keyname2'];

    $addbutton = dbfuncGetButton($addLink, "New casenote", "medium", "",
                                "Create new casenote for $student_first_name");
    if (! $is_on_wl) {
        if ($is_counselor) {
            $wlnLink = "index.php?location=" .
                     dbfuncString2Int("teacher/casenote/list.php") .
                     "&amp;key=" . $_GET['key'] . "&amp;keyname=" .
                     $_GET['keyname'] . "&amp;keyname2=" . $_GET['keyname2'] .
                     "&amp;key2=" . dbfuncString2Int("add");
            $wlbutton = dbfuncGetButton($wlnLink, "Add to my watchlist",
                                        "medium", "cn",
                                        "Add $student_first_name to my casenote watchlist");
        } else {
            $pdb->prepare(
                "DELETE FROM casenotewatch " .
                "WHERE WorkerUsername=:username"
            )->execute(['username' => $username]);
            $is_on_wl = false;
            $wlbutton = "";
        }
    } else {
        if ($is_counselor) {
            $wlnLink = "index.php?location=" .
                     dbfuncString2Int("teacher/casenote/list.php") .
                     "&amp;key=" . $_GET['key'] . "&amp;keyname=" .
                     $_GET['keyname'] . "&amp;keyname2=" . $_GET['keyname2'] .
                     "&amp;key2=" . dbfuncString2Int("remove");
            $wlbutton = dbfuncGetButton($wlnLink,
                                        "Remove from my watchlist", "medium",
                                        "delete",
                                        "Remove $student_first_name from my casenote watchlist");
        } else {
            $wlbutton = "";
        }
    }
    echo "        <p align='center'>$addbutton $wlbutton</p>\n";
} elseif ($is_on_wl) {
    /*
     * Remove student from watchlist because teacher is no longer able to see
     * anybody else's casenotes
     */
    $pdb->prepare(
        "DELETE FROM casenotewatch " .
        "WHERE CaseNoteWatchIndex=:key"
    )->execute(['key' => "{$username}{$student_username}"]);
    echo "        <p align='center'>Removed $student from your watchlist because you are no longer able to see any other teacher's casenotes on this student.</p>\n";
    $is_on_wl = false;
}

$query = $pdb->prepare($qstr);
$query->execute(['username' => $username,
                 'student_username' => $student_username]);
while ( $row = $query->fetch() ) {
    $author = "{$row['Title']} {$row['FirstName']} {$row['Surname']}";
    $date = date(dbfuncGetDateFormat(), strtotime($row['Date']));
    $time = date("g:iA", strtotime($row['Date']));
    $level = "";
    if ($row['Level'] == 0) {
        $level = "Private";
        $level_title = "";
    } else {
        if ($row['Level'] == 1) {
            $text = array(
                    "all $student_first_name's teachers",
                    "all counselors",
                    $author
            );
            $name_list = array_unique(
                                    array_merge($text, $hod_list,
                                                $principal_list));
        } elseif ($row['Level'] == 2) {
            $text = array_merge(
                                array(
                                        "all counselors",
                                        $author
                                ), $ct_list);
            $name_list = array_unique(
                                    array_merge($text, $hod_list,
                                                $principal_list));
        } elseif ($row['Level'] == 3) {
            $nquery = $pdb->prepare(
                "SELECT user.Title, user.FirstName, user.Surname " .
                "       FROM casenotelist, counselorlist, user " .
                "WHERE  CaseNoteIndex = :casenote_index " .
                "AND    casenotelist.WorkerUsername = user.Username " .
                "AND    counselorlist.Username = casenotelist.WorkerUsername " .
                "ORDER BY casenotelist.WorkerUsername"
            );
            $nquery->execute(['casenote_index' => $row['CaseNoteIndex']]);
            $ndata = $nquery->fetchAll();
            $counselor_list = array();
            if ($ndata) {
                foreach($ndata as $nrow) {
                    $counselor_list[] = "{$nrow['Title']} {$nrow['FirstName']} {$nrow['Surname']}";
                }
            } else {
                $nquery = $pdb->query(
                    "SELECT user.Title, user.FirstName, user.Surname " .
                    "       FROM counselorlist, user " .
                    "WHERE  counselorlist.Username = user.Username " .
                    "ORDER BY counselorlist.Username"
                );
                while( $nrow = $nquery->fetch() ) {
                    $counselor_list[] = "{$nrow['Title']} {$nrow['FirstName']} {$nrow['Surname']}";
                }
            }
            $text = array(
                    $author
            );
            $name_list = array_unique(
                                    array_merge($counselor_list, $text,
                                                $hod_list, $principal_list));
        } elseif ($row['Level'] == 4) {
            $text = array(
                    $author
            );
            $name_list = array_unique(
                                    array_merge($text, $hod_list,
                                                $principal_list));
        } elseif ($row['Level'] == 5) {
            $text = array(
                    $author
            );
            $name_list = array_unique(
                                    array_merge($text, $principal_list));
        }
        $level = "Level: {$row['Level']}";
        $level_title = "Viewable by " . getNamesFromList($name_list);
    }
    echo "         <table align='center' border='1' width='400px'>\n"; // Table headers
    echo "            <tr class='alt'>\n";
    echo "               <td style='border-style: none'>\n";
    echo "                  <span style='float: left'>{$row['Title']} {$row['FirstName']} {$row['Surname']} ({$row['Username']})</span><span style='float: right'>$date</span><br>\n";
    echo "                  <span style='float: left'><a title='$level_title' class='cn-level{$row['Level']}'>$level</a></span><span style='float: right'>$time</span>\n";
    echo "               </td>\n";
    echo "            </tr>\n";
    echo "            <tr class='std'>\n";
    echo "               <td colspan='2'>\n";
    echo "                  {$row['Note']}\n";
    echo "               </td>\n";
    echo "            </tr>\n";
    echo "         </table>\n";
    echo "         <p></p>\n";
}

include "footer.php";
