<?php
/**
 * ***************************************************************
 * teacher/punishment/date/modify.php (c) 2006, 2018 Jonathan Dieter
 *
 * Check punishment attendance
 * ***************************************************************
 */

/* Get variables */
if (! isset($_GET['type'])) {
    if (! isset($_POST['type'])) {
        $link = "index.php?location=" .
                 dbfuncString2Int("teacher/punishment/date/modify.php") .
                 "&amp;next=" . $_GET['next'];
        include "admin/punishment/choose_type.php";
        exit(0);
    } else {
        $_GET['type'] = dbfuncString2Int($_POST['type']);
    }
}
$dtype = dbfuncInt2String($_GET['type']);
$query = $pdb->prepare(
    "SELECT DisciplineType " .
    "       FROM disciplinetype " .
    "WHERE  disciplinetype.DisciplineTypeIndex = :dtype "
);
$query->execute(['dtype' => $dtype]);
if ($row = $query->fetch()) {
    $disc = strtolower($row['DisciplineType']);
} else {
    $disc = "unknown punishment";
}
$title = "Punishment attendance for $disc";

$perm = 0;
$query = $pdb->prepare(
    "SELECT Username, DisciplineDateIndex FROM disciplinedate " .
    "WHERE DisciplineTypeIndex = :dtype " .
    "AND   Username            = :username " .
    "AND   Done=0"
);
$query->execute(['dtype' => $dtype, 'username' => $username]);
if($query->fetch()) {
    $perm = 1;
    $pindex = $row['DisciplineDateIndex'];
}

/* Make sure user has permission to view student's marks for subject */
if (!$is_admin and $perm == 0) {
    include "header.php";

    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "teacher/punishment/date/modify.php",
            $LOG_DENIED_ACCESS, "Tried to access punishment attendance.");

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLinks'>Click here to go back</a></p>\n";

    include "footer.php";
    exit(0);
}

if ($_POST["action"] == "Check all") {
    $check_all = 1;
} elseif ($_POST["action"] == "Uncheck all") {
    $check_all = -1;
} else {
    $check_all = 0;
}
include "header.php";

$link = "index.php?location=" .
         dbfuncString2Int("teacher/punishment/date/modify_action.php") .
         "&amp;type=" . $_GET['type'] . "&amp;ptype=" .
         dbfuncString2Int($pindex) . "&amp;next=" . $_GET['next'];

$query = $pdb->prepare(
    "SELECT discipline.Username, user.FirstName, user.Surname, discipline.Date, " .
    "       discipline.Comment, class.ClassName, discipline.DisciplineIndex, " .
    "       disciplinedate.PunishDate, discipline.ServedType " .
    "       FROM discipline INNER JOIN classlist USING (Username) " .
    "                       INNER JOIN classterm USING (ClassTermIndex) " .
    "                       INNER JOIN class USING (ClassIndex) " .
    "                       INNER JOIN disciplinedate USING (DisciplineDateIndex) " .
    "                       INNER JOIN user ON (discipline.Username=user.Username) " .
    "WHERE  disciplinedate.DisciplineDateIndex = $pindex " .
    "AND    classterm.TermIndex        = $termindex " .
    "AND    class.YearIndex            = $yearindex " .
    "ORDER BY class.Grade, class.ClassName, user.Username "
);
$query->execute(['pindex' => $pindex, 'yearindex' => $yearindex,
                 'termindex' => $termindex]);
$data = $query->fetchAll();

if (!$data) {
    echo "      <p align='center' class='subtitle'>No students are punished in this list.</p>\n";

    include "footer.php";
    exit(0);
}

/* Print punishments */

echo "      <form action='$link' method='post' name='pundate'>\n"; // Form method

echo "      <p align='center'>\n";
echo "         <input type='submit' name='action' value='Check all'>&nbsp; \n";
echo "         <input type='submit' name='action' value='Uncheck all'>&nbsp; \n";
echo "         <input type='submit' name='action' value='Done'> \n";
echo "      </p>\n";
echo "      <table align='center' border='1'>\n"; // Table headers
echo "         <tr>\n";
echo "            <th>&nbsp;</th>\n";
echo "            <th>Student</th>\n";
echo "            <th>Class</th>\n";
echo "         </tr>\n";

/* For each assignment, print a row with the title, date, score and comment */
$alt_count = 0;
foreach($data as $row) {
    $alt_count += 1;
    if ($alt_count % 2 == 0) {
        $alt_step = "alt";
    } else {
        $alt_step = "std";
    }
    if ($check_all == 0) {
        if (isset($_POST['mass'][$row['Username']])) {
            if ($_POST['mass'][$row['Username']] == "on") {
                $checked = "checked";
            } else {
                $checked = "";
            }
        } else {
            if (! is_null($row['ServedType']) and
                 $row['ServedType'] == 1) {
                $checked = "checked";
            } else {
                $checked = "";
            }
        }
    } elseif ($check_all == 1) {
        $checked = "checked";
    } else {
        $checked = "";
    }
    $alt = " class='$alt_step'";
    echo "         <tr$alt>\n";
    echo "            <td><input type='checkbox' name='mass[]' value='{$row['Username']}' id='check{$row['Username']}' $checked></input></td>\n";
    echo "            <td><label for='check{$row['Username']}'>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</label></td>\n";
    echo "            <td><label for='check{$row['Username']}'>{$row['ClassName']}</label></td>\n";
    echo "         </tr>\n";
}
echo "      </table>\n";
echo "      </form>\n";

include "footer.php";
