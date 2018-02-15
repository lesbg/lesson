<?php
/**
 * ***************************************************************
 * teacher/assignment/list.php (c) 2004-2013, 2017-2018 Jonathan Dieter
 *
 * Show assignments and marks for a subject
 * ***************************************************************
 */

/* Get variables */
$title = dbfuncInt2String($_GET['keyname']);
$subject_index = dbfuncInt2String($_GET['key']);
$is_agenda = false;
if(isset($_GET['agenda']) and intval(dbfuncInt2String($_GET['agenda'])) != 0)
    $is_agenda = true;

if ($is_agenda)
    $title = "Agenda items for $title";

include "header.php"; // Show header
include "core/settermandyear.php";

/* Check whether user is authorized to change scores */
if (check_teacher_subject($username, $subject_index))
    $is_teacher = true;

if (check_support_teacher_subject($username, $subject_index))
    $is_support_class_teacher = true;

if (!$is_teacher and !$is_support_class_teacher and !$is_admin) {
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "teacher/assignment/list.php", $LOG_DENIED_ACCESS,
            "Tried to access marks for $title.");

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    include "footer.php";
    exit(0);
}

$perm = get_punishment_permissions($username);

/* Get whether marks can be modified */
$query = $pdb->prepare(
    "SELECT AverageType, AverageTypeIndex, CanModify FROM subject " .
    "WHERE subject.SubjectIndex = :subject_index"
);
$query->execute(['subject_index' => $subject_index]);
$row = $query->fetch();
if (!$row) {
    echo "      <p>Subject doesn't exist</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    include "footer.php";
    exit(0);
}
$can_modify = false;
if ($is_admin or intval($row['CanModify']) == 1)
    $can_modify = true;

if (!$is_teacher and !$is_admin)
    $can_modify = false;

$average_type = $row['AverageType'];
if(!is_null($average_type))
    $average_type = intval($average_type);
$average_type_index = $row['AverageTypeIndex'];
if(!is_null($average_type_index))
    $average_type_index = intval($average_type_index);
if(!is_null($subject_index))
    $subject_index = intval($subject_index);

$nochangeyt = true;

include "core/titletermyear.php";

if($is_agenda) {
    $agenda_num = 1;
    $next_agenda_num = 0;
    $next_agenda_title = "Assignments";
} else {
    $agenda_num = 0;
    $next_agenda_num = 1;
    $next_agenda_title = "Agenda items";
}

$newlink = "index.php?location=" .
    dbfuncString2Int("teacher/assignment/modify.php") . "&amp;key2=" .
    dbfuncString2Int($subject_index) . "&amp;keyname=" .
    $_GET['keyname'] . "&amp;agenda=" . dbfuncString2Int($agenda_num);
$agendalink = "index.php?location=" .
    dbfuncString2Int("teacher/assignment/list.php") .
    "&amp;key=" . dbfuncString2Int($subject_index) . "&amp;keyname=" .
    $_GET['keyname'] . "&amp;agenda=" . dbfuncString2Int($next_agenda_num);
$optlink = "index.php?location=" .
    dbfuncString2Int("teacher/subject/modify.php") . "&amp;key=" .
    dbfuncString2Int($subject_index) . "&amp;keyname=" .
    $_GET['keyname'];
if ($average_type == $AVG_TYPE_PERCENT) {
    $prtlink = "index.php?location=" .
        dbfuncString2Int("teacher/assignment/print.php") . "&amp;key=" .
        dbfuncString2Int($subject_index) . "&amp;keyname=" .
        $_GET['keyname'];
}
$cltlink = "index.php?location=" .
    dbfuncString2Int("teacher/assignment/print_gradesheet.php") .
    "&amp;key=" . dbfuncString2Int($subject_index) . "&amp;keyname=" .
    $_GET['keyname'];
$photolink = "index.php?location=" .
    dbfuncString2Int("teacher/assignment/student_photos.php") .
    "&amp;key=" . dbfuncString2Int($subject_index) . "&amp;keyname=" .
    $_GET['keyname'];

$agendabutton = dbfuncGetButton($agendalink, $next_agenda_title, "medium", "",
                                "");
if ($can_modify) {
    if(!$is_agenda) {
        if($average_type != $AVG_TYPE_NONE) {
            $newbutton = dbfuncGetButton($newlink, "New assignment", "medium", "",
                                        "Create new assignment for this subject");
            $optbutton = dbfuncGetButton($optlink, "Subject options", "medium", "",
                                        "Edit options for this subject");
        } else {
            $newbutton = "";
            $optbutton = "";
        }
    } else {
        $newbutton = dbfuncGetButton($newlink, "New agenda item", "medium", "",
                                    "Create new agenda item for this subject");
        $optbutton = "";
    }
} else {
    $newbutton = "";
    $optbutton = "";
}
if(!$is_agenda and $average_type == $AVG_TYPE_PERCENT) {
    $prtbutton = dbfuncGetButton($prtlink, "Printable marks", "medium", "",
                                    "View printable marks for this subject");
} else {
    $prtbutton = "";
}

$cltbutton = dbfuncGetButton($cltlink, "Printable gradesheet", "medium", "",
                            "View printable gradesheet for this subject");
$photobutton = dbfuncGetButton($photolink, "Student photos", "medium", "",
                            "View student photos");

echo "      <p align='center'>$agendabutton$newbutton$optbutton$prtbutton$cltbutton$photobutton</p>\n";

if($is_agenda) {
    $query = $pdb->prepare(
        "SELECT Title, Date, DueDate, assignment.AssignmentIndex, " .
        "       AverageType, ShowAverage, Agenda, subject.Name AS SubjectName, " .
        "       Uploadable, assignment.Weight, Hidden, " .
        "       subject.SubjectIndex " .
        "       FROM subject INNER JOIN assignment USING (SubjectIndex) " .
        "WHERE subject.SubjectIndex = :subject_index " .
        "AND   Agenda       = 1 " .
        "ORDER BY Date DESC, AssignmentIndex DESC"
    );
    $query->execute(['subject_index' => $subject_index]);
    $data = $query->fetchAll();

    /* If no agenda items, leave message and exit */
    if (!$data) {
        echo "      <p align='center'>No agenda items.</p>\n";
        log_event($LOG_LEVEL_EVERYTHING, "teacher/assignment/list.php",
                $LOG_STUDENT, "Viewed all of " .
                dbfuncInt2String($_GET['keyname']) . "'s agenda items.");
        include "footer.php";
        exit(0);
    }

    echo "      <table align='center' border='1'>\n";
    echo "         <tr>\n";
    echo "            <th>Title</th>\n";
    echo "            <th>Date</th>\n";
    echo "            <th>Due Date</th>\n";
    echo "         </tr>\n";

    /* List each agenda item */
    $alt_count = 0;
    foreach($data as $row) {
        $alt_count += 1;
        if ($alt_count % 2 == 0) {
            $alt_step = "alt";
        } else {
            $alt_step = "std";
        }

        $alt = " class='agenda-$alt_step'";
        $aclass = " class='agenda'";

        if ($row['Hidden'] == 1) {
            $alt = " class='hidden-$alt_step'";
        }

        echo "         <tr$alt>\n";
        $modifylink = "index.php?location=" .
                     dbfuncString2Int(
                                    "teacher/assignment/modify.php") .
                     "&amp;key=" .
                     dbfuncString2Int($row['AssignmentIndex']) .
                     "&amp;keyname=" . dbfuncString2Int($row['Title']) .
                     "&amp;agenda=" . dbfuncString2Int($agenda_num);
        echo "          <td><a$aclass href='$modifylink'>{$row['Title']}</a></td>\n";

        $dateinfo = date($dateformat, strtotime($row['Date']));
        $duedateinfo = date($dateformat, strtotime($row['DueDate']));
        echo "            <td>$dateinfo</td>\n";
        echo "            <td>$duedateinfo</td>\n";
        echo "         </tr>\n";
    }
    echo "      </table>\n"; // End of table

    log_event($LOG_LEVEL_EVERYTHING, "teacher/assignment/list.php",
            $LOG_STUDENT, "Viewed all of " . dbfuncInt2String($_GET['keyname']) .
             "'s agenda items.");
    include "footer.php";
    exit(0);
}

echo "      <table align='center' border='1'>\n"; // Table headers
echo "         <tr>\n";
echo "            <th>&nbsp;</th>\n";
echo "            <th>Student</th>\n";

if ($average_type != 0) {
    $rowcount = 0;

    /* Get assignment list */
    $query = $pdb->prepare(
        "SELECT assignment.Title, assignment.Date, assignment.Hidden, " .
        "       assignment.AssignmentIndex, category.CategoryName, " .
        "       assignment.MakeupTypeIndex " .
        "       FROM assignment " .
        "       LEFT OUTER JOIN categorylist USING (CategoryListIndex) " .
        "       LEFT OUTER JOIN category USING (CategoryIndex) " .
        "WHERE assignment.SubjectIndex = :subject_index " .
        "AND   assignment.Agenda = 0 " .
        "ORDER BY Date, AssignmentIndex"
    );
    $query->execute(['subject_index' => $subject_index]);

    // Run through list of all assignments and print each assignment and date
    foreach($query as $row) {
        $rowcount += 1;
        $dateinfo = date($dateformat, strtotime($row['Date']));
        $row['Title'] = htmlspecialchars($row['Title']);
        $hidden = $row['Hidden'];

        $link = "index.php?location=" .
                 dbfuncString2Int("teacher/assignment/modify.php") .
                 "&amp;key=" . dbfuncString2Int($row['AssignmentIndex']) .
                 "&amp;keyname=" . dbfuncString2Int($row['Title']);
        $headtype = "";
        if ($hidden == 1)
            $headtype = " class='hidden'";
        if (is_null($row['CategoryName'])) {
            $catinfo = "";
        } else {
            $catinfo = "<br><span class='small'>{$row['CategoryName']}</span>";
        }
        if ($can_modify) {
            echo "            <th$headtype width=10px><a$headtype href='$link'>{$row['Title']}<br> ({$dateinfo}){$catinfo}</a></th>\n";
        } else {
            echo "            <th$headtype width=10px>{$row['Title']}<br>($dateinfo){$catinfo}</th>\n";
        }
    }
    if ($average_type == $AVG_TYPE_PERCENT or
         $average_type == $AVG_TYPE_GRADE) {
        echo "            <th width=10px>Total</th>\n"; // Show total percentage if desired
    }
}
echo "         </tr>\n";

/* For each student, print a row with the student's name and score on each assignment */
if ($is_support_class_teacher and ! $is_teacher and ! $is_admin) {
    $query = $pdb->prepare(
        "SELECT user.FirstName, user.Surname, user.Username, classlist.ClassOrder, " .
         "       subjectstudent.Average FROM user, subject " .
         "       INNER JOIN subjectstudent USING (SubjectIndex)" .
         "       INNER JOIN classlist USING (Username) " .
         "       INNER JOIN classterm ON (classterm.ClassTermIndex=classlist.ClassTermIndex AND classterm.TermIndex=subject.TermIndex) " .
         "       INNER JOIN class ON (class.ClassIndex=classterm.ClassIndex AND class.YearIndex=subject.YearIndex) " .
         "       INNER JOIN support_class ON (classterm.ClassTermIndex=support_class.ClassTermIndex) " .
         "WHERE support_class.Username = :username " .
         "AND user.Username = subjectstudent.Username " .
         "AND subject.SubjectIndex = :subject_index " .
         "ORDER BY user.FirstName, user.Surname, user.Username"
    );
    $query->execute(['username' => $username, 'subject_index' => $subject_index]);
} else {
    $query = $pdb->prepare(
        "SELECT user.FirstName, user.Surname, user.Username, query.ClassOrder, " .
        "       subjectstudent.Average FROM user, " .
        "       subjectstudent LEFT OUTER JOIN " .
        "       (SELECT classlist.ClassOrder, classlist.Username FROM class, " .
        "               classterm, classlist, subject " .
        "        WHERE classlist.ClassTermIndex = classterm.ClassTermIndex " .
        "        AND   classterm.TermIndex = subject.TermIndex " .
        "        AND   class.ClassIndex = classterm.ClassIndex " .
        "        AND   class.YearIndex = subject.YearIndex " .
        "        AND subject.SubjectIndex = :subject_index) AS query " .
        "       ON subjectstudent.Username = query.Username " .
        "WHERE user.Username=subjectstudent.Username " .
        "AND subjectstudent.SubjectIndex = :subject_index " .
        "ORDER BY user.FirstName, user.Surname, user.Username"
    );
    $query->execute(['subject_index' => $subject_index]);
}

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

    if ($currentyear == $yearindex) {
        $cnlink = "index.php?location=" .
             dbfuncString2Int("teacher/casenote/list.php") . "&amp;key=" .
             dbfuncString2Int($row['Username']) . "&amp;keyname=" .
             dbfuncString2Int(
                            "{$row['FirstName']} {$row['Surname']} ({$row['Username']})") .
             "&amp;keyname2=" . dbfuncSTring2Int($row['FirstName']);
        $cnbutton = dbfuncGetButton($cnlink, "C", "small", "cn",
                                    "Casenotes");
    } else {
        $cnbutton = "";
    }
    if ($currentyear == $yearindex and $currentterm == $termindex and
         ($perm >= $PUN_PERM_REQUEST or
         dbfuncGetPermission($permissions, $PERM_ADMIN))) {
        if ($perm == $PUN_PERM_REQUEST) {
            $punlink = "index.php?location=" .
             dbfuncString2Int("teacher/punishment/request/new.php") .
             "&amp;key=" . dbfuncString2Int($row['Username']) .
             "&amp;keyname=" .
             dbfuncString2Int(
                            "{$row['FirstName']} {$row['Surname']} ({$row['Username']})") .
             "&amp;next=" .
             dbfuncString2Int(
                            "index.php?location=" .
                             dbfuncString2Int("teacher/assignment/list.php") .
                             "&amp;key=" . $_GET['key'] . "&amp;keyname=" .
                             $_GET['keyname']);
            $punbutton = dbfuncGetButton($punlink, "P", "small", "delete",
                                        "Request Punishment");
        } else {
            $punlink = "index.php?location=" .
                     dbfuncString2Int("admin/punishment/new.php") . "&amp;key=" .
                     dbfuncString2Int($row['Username']) . "&amp;keyname=" .
                     dbfuncString2Int(
                                    "{$row['FirstName']} {$row['Surname']} ({$row['Username']})") .
                     "&amp;next=" .
                     dbfuncString2Int(
                                    "index.php?location=" .
                                     dbfuncString2Int(
                                                    "teacher/assignment/list.php") .
                                     "&amp;key=" . $_GET['key'] . "&amp;keyname=" .
                                     $_GET['keyname']);
            $punbutton = dbfuncGetButton($punlink, "P", "small", "delete",
                                        "Issue Punishment");
        }
    } else {
        $punbutton = "";
    }

    echo "            <td nowrap>$punbutton$cnbutton $order</td>\n";
    $order += 1;

    if ($average_type != 0) {
        $link = "index.php?location=" .
             dbfuncString2Int("student/subjectinfo.php") . "&amp;key2=" .
             dbfuncString2Int($row['Username']) . "&amp;key2name=" .
             dbfuncString2Int("{$row['FirstName']} {$row['Surname']}") .
             "&amp;key=" . $_GET['key'] . "&amp;keyname=" . $_GET['keyname'];
        echo "            <td nowrap><a href='$link'>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</a></td>\n";

        $mquery = $pdb->prepare(
            "SELECT mark.Percentage, mark.Score, assignment.Weight, " .
            "       mark.OriginalPercentage, mark.MakeupScore, mark.MakeupPercentage, " .
            "       assignment.Hidden, assignment.MakeupTypeIndex, " .
            "       makeup_type.TargetMax " .
            "       FROM assignment LEFT OUTER JOIN mark ON " .
            "       (mark.AssignmentIndex=assignment.AssignmentIndex AND " .
            "        mark.Username = :username) " .
            "       LEFT OUTER JOIN makeup_type USING (MakeupTypeIndex)" .
            "WHERE assignment.SubjectIndex = :subject_index " .
            "AND   assignment.Agenda = 0 " .
            "ORDER BY assignment.Date, assignment.AssignmentIndex"
        );
        $mquery->execute(['username' => $row['Username'], 'subject_index' => $subject_index]);

        foreach($mquery as $mRow) {
            $rowcount += 1;
            $hidden = $mRow['Hidden'];

            $alt = "";
            if ($hidden == 1) {
                $alt = " class='hidden-$alt_step'";
            }

            if ($average_type == $AVG_TYPE_PERCENT or
                 $average_type == $AVG_TYPE_GRADE) {
                echo format_makeup_average($can_modify, $hidden, $alt, $alt_step, $mRow['Percentage'], $mRow['OriginalPercentage'], $mRow['MakeupPercentage'], $mRow['Score'], $mRow['MakeupScore']);
            } elseif ($average_type == $AVG_TYPE_INDEX) {
                if (! isset($average_type_index) or $average_type_index == "" or
                         ! isset($mRow['Score']) or $mRow['Score'] == "") {
                    if ($can_modify == 1 and $hidden == 0) {
                        $alt = " class='unmarked-$alt_step'";
                    }
                    $average = "";
                } else {
                    $average = get_nonmark_display($mRow['Score'], $average_type_index);
                    if(is_null($average)) {
                        if ($can_modify and $hidden == 0) {
                            $alt = " class='unmarked-$alt_step'";
                        }
                        $average = "N/A";
                    }
                }
                echo "            <td$alt nowrap><span style='float: right'>$average</span></td>\n";
            }
        }
        if ($average_type == $AVG_TYPE_PERCENT) { // Show average percentage for all students
            if ($row['Average'] == -1) {
                echo "            <td nowrap><span style='float: right'><b>N/A</b></span></td>\n";
            } else {
                $average = round($row['Average']);
                echo "            <td nowrap><span style='float: right'><b>$average%</b></span></td>\n";
            }
        } elseif ($average_type == $AVG_TYPE_GRADE) { // Show average percentage for all students
            if ($row['Average'] == -1) {
                echo "            <td nowrap><span style='float: right'><b>N/A</b></span></td>\n";
            } else {
                $average = get_nonmark_display($row['Average']);
                if(is_null($average)) {
                    $average = "?";
                }
                echo "            <td nowrap><span style='float: right'><b>$average</b></span></td>\n";
            }
        }
    } else {
        echo "            <td nowrap>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
    }
    echo "         </tr>\n";
}
if ($no_marks == 0 and
     ($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE)) { // Show average percentage for all students
    $alt_count += 1;
    if ($alt_count % 2 == 0) {
        $alt_step = "alt";
    } else {
        $alt_step = "std";
    }
    $alt = " class='$alt_step'";

    echo "         <tr$alt>\n";
    echo "            <td nowrap>&nbsp;</td>\n";
    echo "            <td nowrap><i>Class Average</i></td>\n";

    /* Get assignment averages */
    $query = $pdb->prepare(
        "SELECT Average, Hidden FROM assignment " .
        "WHERE SubjectIndex = :subject_index " .
        "AND   Agenda = 0 " .
        "ORDER BY Date, AssignmentIndex"
    );
    $query->execute(['subject_index' => $subject_index]);
    foreach($query as $row) {
        if(!is_null($row['Average']))
            $row['Average'] = floatval($row['Average']);

        if ($row['Average'] > - 1) {
            $average = round($row['Average']) . "%";
        } else {
            $average = "N/A";
        }
        if ($row['Hidden'] == "1") {
            $alt = " class='hidden-$alt_step'";
        } else {
            $alt = "";
        }
        echo "            <td$alt nowrap><span style='float: right'><i>$average</i></span></td>\n";
    }

    if ($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) {
        /* Get total subject average */
        $query = $pdb->prepare(
            "SELECT Average FROM subject " .
            "WHERE SubjectIndex = :subject_index "
        );
        $query->execute(['subject_index' => $subject_index]);

        foreach($query as $row) {
            if(!is_null($row['Average']))
                $row['Average'] = floatval($row['Average']);

            if ($row['Average'] > - 1) {
                $average = round($row['Average']) . "%";
            } else {
                $average = "N/A";
            }
            echo "            <td nowrap><span style='float: right'><b><i>$average</i></b></span></td>\n";
        }
    }
    echo "         </tr>\n";
}
echo "      </table>\n";
log_event($LOG_LEVEL_EVERYTHING, "teacher/assignment/list.php", $LOG_TEACHER,
        "Accessed marks for $title.");

include "footer.php";
