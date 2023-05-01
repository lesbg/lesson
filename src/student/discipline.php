<?php
/**
 * ***************************************************************
 * student/discipline.php (c) 2006, 2007 Jonathan Dieter
 *
 * Print information about student's discipline history for term
 * ***************************************************************
 */

/* Get variables */
$studentusername = dbfuncInt2String($_GET['key']);
$name = dbfuncInt2String($_GET['keyname']);
$title = "Discipline history for $name ($studentusername)";

/*
 * Key wasn't included. The only time I've seen this happen is when a student doesn't logout and lets
 * another student use their computer, so we'll force a logout
 */
if (! isset($_GET['key'])) {
    log_event($LOG_LEVEL_ACCESS, "student/discipline.php", $LOG_ERROR,
            "Page was accessed without key (Make sure user logged out).");
    include "user/logout.php";
    exit(0);
}

include "header.php";

/* Check whether current user is principal */
$res = &  $db->query(
                "SELECT Username FROM principal " .
                 "WHERE Username=\"$username\" AND Level=1");
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
    $is_principal = true;
} else {
    $is_principal = false;
}

/* Check whether current user is a counselor */
$res = &  $db->query(
                "SELECT Username FROM counselorlist " .
                 "WHERE Username=\"$username\"");
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
    $is_counselor = true;
} else {
    $is_counselor = false;
}

/* Check whether current user is a hod */
$query = "SELECT hod.Username FROM hod, class, classterm, classlist " .
         "WHERE hod.Username='$username' " .
         "AND hod.DepartmentIndex = class.DepartmentIndex " .
         "AND classlist.Username = '$studentusername' " .
         "AND classlist.ClassTermIndex = classterm.ClassTermIndex " .
         "AND classterm.ClassIndex = class.ClassIndex";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
    $is_hod = true;
} else {
    $is_hod = false;
}

/* Check whether current user is student's guardian */
$query =    "SELECT familylist.Username FROM " .
        "    familylist INNER JOIN familylist AS familylist2 ON (familylist.FamilyCode=familylist2.FamilyCode) " .
        "WHERE familylist.Username         = '$studentusername' " .
        "AND   familylist2.Username        = '$username' " .
        "AND   familylist2.Guardian        = 1 ";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
    $is_guardian = true;
} else {
    $is_guardian = false;
}

/* Make sure user has permission to view student's marks for subject */
if ($is_admin or $is_hod or $is_principal or $is_counselor or $is_guardian or
     $studentusername == $username) {
    include "core/settermandyear.php";

    /* Print assignments and scores */
    include "core/titletermyear.php";

    /* Calculate conduct mark */
    $query = "SELECT classlist.Conduct FROM classlist, classterm, class " .
             "WHERE class.YearIndex = $yearindex " .
             "AND   classterm.ClassIndex = class.ClassIndex " .
             "AND   classterm.TermIndex = $termindex " .
             "AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
             "AND   classlist.Username = '$studentusername'";
    $conductRes = &   $db->query($query);
    if (DB::isError($conductRes))
        die($conductRes->getDebugInfo()); // Check for errors in query
    if ($conductRow = & $conductRes->fetchrow(DB_FETCHMODE_ASSOC) and
     $conductRow['Conduct'] != "") {
        $total_conduct = $conductRow['Conduct'];

        if ($termindex == $currentterm) {
            $query = "(SELECT disciplinetype.DisciplineType, disciplineweight.DisciplineWeight,  " .
                 "        discipline.WorkerUsername, user.Title, user.FirstName, user.Surname, discipline.Date, discipline.Comment, discipline.DisciplineIndex, disciplinetype.PermLevel, disciplinetype.DoPunishment, " .
                 "        disciplinedate.PunishDate, discipline.ServedType, disciplineweight.TermIndex " .
                 "        FROM disciplinetype, disciplineweight, (discipline LEFT OUTER JOIN user " .
                 "        ON discipline.WorkerUsername = user.Username) LEFT OUTER JOIN disciplinedate " .
                 "        ON discipline.DisciplineDateIndex = disciplinedate.DisciplineDateIndex " .
                 " WHERE  discipline.Username = \"$studentusername\" " .
                 " AND    disciplineweight.YearIndex = $yearindex " .
                 " AND    disciplineweight.TermIndex = $termindex " .
                 " AND    discipline.DisciplineWeightIndex = disciplineweight.DisciplineWeightIndex " .
                 " AND    disciplineweight.DisciplineTypeIndex = disciplinetype.DisciplineTypeIndex) " .
                 "UNION " .
                 "(SELECT disciplinetype.DisciplineType, disciplineweight.DisciplineWeight,  " .
                 "        discipline.WorkerUsername, user.Title, user.FirstName, user.Surname, discipline.Date, discipline.Comment, discipline.DisciplineIndex, disciplinetype.PermLevel, disciplinetype.DoPunishment, " .
                 "        disciplinedate.PunishDate, discipline.ServedType, disciplineweight.TermIndex " .
                 "        FROM disciplinetype, disciplineweight, discipline LEFT OUTER JOIN user " .
                 "        ON discipline.WorkerUsername = user.Username LEFT OUTER JOIN disciplinedate " .
                 "        ON discipline.DisciplineDateIndex = disciplinedate.DisciplineDateIndex " .
                 " WHERE  discipline.Username = \"$studentusername\" " .
                 " AND    disciplineweight.YearIndex = $yearindex " .
                 " AND    disciplineweight.DisciplineWeight > 0 " .
                 " AND    (disciplinedate.Done IS NULL OR disciplinedate.Done = 0) " .
                 " AND    disciplinetype.DoPunishment = 1 " .
                 " AND    discipline.DisciplineWeightIndex = disciplineweight.DisciplineWeightIndex " .
                 " AND    disciplineweight.DisciplineTypeIndex = disciplinetype.DisciplineTypeIndex) " .
                 "ORDER BY Date DESC, WorkerUsername, DisciplineType, DisciplineIndex";
        } else {
            $query = "SELECT disciplinetype.DisciplineType, disciplineweight.DisciplineWeight,  " .
                     "       discipline.WorkerUsername, user.Title, user.FirstName, user.Surname, discipline.Date, discipline.Comment, discipline.DisciplineIndex, disciplinetype.PermLevel, disciplinetype.DoPunishment, " .
                     "       disciplinedate.PunishDate, discipline.ServedType, disciplineweight.TermIndex " .
                     "       FROM disciplinetype, disciplineweight, (discipline LEFT OUTER JOIN user " .
                     "       ON discipline.WorkerUsername = user.Username) LEFT OUTER JOIN disciplinedate " .
                     "       ON discipline.DisciplineDateIndex = disciplinedate.DisciplineDateIndex " .
                     "WHERE  discipline.Username = \"$studentusername\" " .
                     "AND    disciplineweight.YearIndex = $yearindex " .
                     "AND    disciplineweight.TermIndex = $termindex " .
                     "AND    discipline.DisciplineWeightIndex = disciplineweight.DisciplineWeightIndex " .
                     "AND    disciplineweight.DisciplineTypeIndex = disciplinetype.DisciplineTypeIndex " .
                     "ORDER BY Date DESC, WorkerUsername, DisciplineType, DisciplineIndex";
        }
        $res = &  $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query

        if ($conductRow['Conduct'] < 0) {
            echo "      <p class=\"subtitle\" align=\"center\">Conduct: 0%</p>\n";
        } else {
            echo "      <p class=\"subtitle\" align=\"center\">Conduct: {$conductRow['Conduct']}%</p>\n";
        }

        $conduct_mark = $conductRow['Conduct'];

        echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
        echo "         <tr>\n";
        echo "            <th>Discipline Type</th>\n";
        echo "            <th>Date</th>\n";
        echo "            <th>Teacher</th>\n";
        echo "            <th>Conduct</th>\n";
        echo "            <th>Punishment Date</th>\n";
        echo "            <th>Reason</th>\n";
        echo "         </tr>\n";

        /* For each punishment, print a row with the title, date, score and comment */
        $alt_count = 0;
        $printed_beginning = False;

        if ($res->numRows() > 0) {
            while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
                // Write beginning of term if there are no punishments yet this term
                if (! $printed_beginning and $row['TermIndex'] != $termindex) {
                    $alt_count += 1;
                    if ($alt_count % 2 == 0) {
                        $alt_step = "alt";
                    } else {
                        $alt_step = "std";
                    }
                    $alt = " class=\"$alt_step\"";
                    echo "         <tr$alt>\n";
                    echo "            <td><i>Beginning of term</i></td>\n";
                    echo "            <td>&nbsp;</td>\n";
                    echo "            <td>&nbsp;</td>\n";
                    echo "            <td><i>100%</i></td>\n";
                    echo "            <td>&nbsp;</td>\n";
                    echo "            <td>&nbsp;</td>\n";
                    echo "         </tr>\n";
                    $printed_beginning = True;
                }

                $alt_count += 1;
                if ($alt_count % 2 == 0) {
                    $alt_step = "alt";
                } else {
                    $alt_step = "std";
                }
                if ((! is_null($row['ServedType']) and $row['ServedType'] == 1) or
                     $row['DoPunishment'] == 0) {
                    $alt = " class=\"$alt_step\"";
                } elseif ((! is_null($row['ServedType']) and
                         $row['ServedType'] == 0) or
                         (is_null($row['ServedType']) and
                         ! is_null($row['PunishDate'])) or
                         $row['DisciplineWeight'] == 0) {
                    $alt = " class=\"late-$alt_step\"";
                } else {
                    $alt = " class=\"almost-$alt_step\"";
                }
                $dateinfo = date($dateformat, strtotime($row['Date']));
                if (! is_null($row['PunishDate'])) {
                    $punish_date = date($dateformat,
                                        strtotime($row['PunishDate']));
                } else {
                    $punish_date = "&nbsp;";
                }
                echo "         <tr$alt>\n";
                echo "            <td>{$row['DisciplineType']}</td>\n";
                echo "            <td>$dateinfo</td>\n";
                echo "            <td>{$row['Title']} {$row['FirstName']} {$row['Surname']}</td>\n";
                if ($row['TermIndex'] == $termindex) {
                    if ($conduct_mark < 0) {
                        echo "            <td>0%</td>\n";
                    } else {
                        echo "            <td>$conduct_mark%</td>\n";
                    }
                    $conduct_mark = $conduct_mark + $row['DisciplineWeight'];
                } else {
                    echo "            <td>&nbsp;</td>\n";
                }
                echo "            <td>$punish_date</td>\n";
                echo "            <td>{$row['Comment']}</td>\n";
                echo "         </tr>\n";
            }
        }
    }
    $alt_count += 1;
    if ($alt_count % 2 == 0) {
        $alt_step = "alt";
    } else {
        $alt_step = "std";
    }
    $alt = " class=\"$alt_step\"";
    echo "         <tr$alt>\n";
    echo "            <td><i>Beginning of term</i></td>\n";
    echo "            <td>&nbsp;</td>\n";
    echo "            <td>&nbsp;</td>\n";
    echo "            <td><i>100%</i></td>\n";
    echo "            <td>&nbsp;</td>\n";
    echo "            <td>&nbsp;</td>\n";
    echo "         </tr>\n";
    echo "      </table>\n";
    log_event($LOG_LEVEL_EVERYTHING, "student/discipline.php", $LOG_STUDENT,
            "Viewed $name's discipline history.");
} else {
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "student/discipline.php", $LOG_DENIED_ACCESS,
            "Tried to access $name's discipline history.");

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
}

include "footer.php";
