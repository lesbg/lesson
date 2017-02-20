<?php
/**
 * ***************************************************************
 * admin/punishment/list.php (c) 2006-2016 Jonathan Dieter
 *
 * Print information about all issued punishment history for
 * term
 * ***************************************************************
 */

/* Get variables */
$title = "Punishments issued this term";

$query = "SELECT user.FirstName, user.Surname, user.Username FROM " .
         "       user INNER JOIN groupgenmem ON (user.Username=groupgenmem.Username) " .
         "            INNER JOIN groups USING (GroupID) " .
         "WHERE user.Username='$username' " .
         "AND   groups.GroupTypeID='activeteacher' " .
         "AND   groups.YearIndex=$yearindex " .
         "ORDER BY user.Username";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query
if ($res->numRows() > 0) {
    $is_teacher = true;
} else {
    $is_teacher = false;
}

$query = "SELECT Permissions FROM disciplineperms WHERE Username=\"$username\"";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    $perm = $row['Permissions'];
} else {
    $perm = $DEFAULT_PUN_PERM;
}

include "header.php";

/* Make sure user has permission to view all punishments */
if ($is_admin or ($perm >= $PUN_PERM_SEE and $is_teacher)) {
    $showalldeps = true;
    include "core/settermandyear.php";
    if (isset($_GET['start'])) {
        $start = dbfuncInt2String($_GET['start']);
    } else {
        $start = "0";
    }

    if (! isset($_GET['sort'])) {
        $sortnum = '0';
    } else {
        $sortnum = dbfuncInt2String($_GET['sort']);
    }

    for($a = 0; $a < 16; $a ++) {
        $sort[$a] = "sort";
    }

    $sort[intval($sortnum)] = "bsort";

    if ($sortnum == '1') {
        $sortorder = "ORDER BY discipline.Date, class.Grade, class.ClassName, user.Username, tuser.Username, discipline.DisciplineIndex ";
    } elseif ($sortnum == '2') {
        $sortorder = "ORDER BY user.Username, discipline.Date DESC, tuser.Username, discipline.DisciplineIndex ";
    } elseif ($sortnum == '3') {
        $sortorder = "ORDER BY user.username DESC, discipline.Date DESC, tuser.Username, discipline.DisciplineIndex ";
    } elseif ($sortnum == '4') {
        $sortorder = "ORDER BY tuser.Username, discipline.Date DESC, class.Grade, class.ClassName, user.Username, discipline.DisciplineIndex ";
    } elseif ($sortnum == '5') {
        $sortorder = "ORDER BY tuser.Username DESC, discipline.Date DESC, class.Grade, class.ClassName, user.Username, discipline.DisciplineIndex ";
    } elseif ($sortnum == '6') {
        $sortorder = "ORDER BY disciplinetype.DisciplineType, discipline.Date DESC, class.Grade, class.ClassName, user.Username, tuser.Username, discipline.DisciplineIndex ";
    } elseif ($sortnum == '7') {
        $sortorder = "ORDER BY disciplinetype.DisciplineType DESC, discipline.Date DESC, class.Grade, class.ClassName, user.Username, tuser.Username, discipline.DisciplineIndex ";
    } elseif ($sortnum == '8') {
        $sortorder = "ORDER BY class.Grade, class.ClassName, user.Username, discipline.Date DESC, tuser.Username, discipline.DisciplineIndex ";
    } elseif ($sortnum == '9') {
        $sortorder = "ORDER BY class.Grade DESC, class.ClassName DESC, user.Username, discipline.Date DESC, tuser.Username, discipline.DisciplineIndex ";
    } elseif ($sortnum == '10') {
        $sortorder = "ORDER BY discipline.Comment, discipline.Date DESC, class.Grade, class.ClassName, user.Username, tuser.Username, discipline.DisciplineIndex ";
    } elseif ($sortnum == '11') {
        $sortorder = "ORDER BY discipline.Comment DESC, discipline.Date DESC, class.Grade, class.ClassName, user.Username, tuser.Username, discipline.DisciplineIndex ";
    } elseif ($sortnum == '12') {
        $sortorder = "ORDER BY disciplinedate.PunishDate, discipline.Date DESC, class.Grade, class.ClassName, user.Username, tuser.Username, discipline.DisciplineIndex ";
    } elseif ($sortnum == '13') {
        $sortorder = "ORDER BY disciplinedate.PunishDate DESC, discipline.Date DESC, class.Grade, class.ClassName, user.Username, tuser.Username, discipline.DisciplineIndex ";
    } elseif ($sortnum == '14') {
        $sortorder = "ORDER BY discipline.ServedTyped, discipline.Date DESC, class.Grade, class.ClassName, user.Username, tuser.Username, discipline.DisciplineIndex ";
    } elseif ($sortnum == '15') {
        $sortorder = "ORDER BY discipline.ServedTyped DESC, discipline.Date DESC, class.Grade, class.ClassName, user.Username, tuser.Username, discipline.DisciplineIndex ";
    } else {
        $sortorder = "ORDER BY discipline.Date DESC, class.Grade, class.ClassName, user.Username, tuser.Username, discipline.DisciplineIndex ";
    }

    $dateAsc = dbfuncGetButton(
                            "index.php?location=" .
                             dbfuncString2Int("admin/punishment/list.php") .
                             "&amp;sort=" . dbfuncString2Int("1"), "A", "small", "{$sort[1]}",
                            "Sort ascending");
    $dateDec = dbfuncGetButton(
                            "index.php?location=" .
                             dbfuncString2Int("admin/punishment/list.php") .
                             "&amp;sort=" . dbfuncString2Int("0"), "D", "small", "{$sort[0]}",
                            "Sort descending");
    $studentAsc = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/punishment/list.php") .
                                 "&amp;sort=" . dbfuncString2Int("2"), "A", "small", "{$sort[2]}",
                                "Sort ascending");
    $studentDec = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/punishment/list.php") .
                                 "&amp;sort=" . dbfuncString2Int("3"), "D", "small", "{$sort[3]}",
                                "Sort descending");
    $teacherAsc = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/punishment/list.php") .
                                 "&amp;sort=" . dbfuncString2Int("4"), "A", "small", "{$sort[4]}",
                                "Sort ascending");
    $teacherDec = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/punishment/list.php") .
                                 "&amp;sort=" . dbfuncString2Int("5"), "D", "small", "{$sort[5]}",
                                "Sort descending");
    $typeAsc = dbfuncGetButton(
                            "index.php?location=" .
                             dbfuncString2Int("admin/punishment/list.php") .
                             "&amp;sort=" . dbfuncString2Int("6"), "A", "small", "{$sort[6]}",
                            "Sort ascending");
    $typeDec = dbfuncGetButton(
                            "index.php?location=" .
                             dbfuncString2Int("admin/punishment/list.php") .
                             "&amp;sort=" . dbfuncString2Int("7"), "D", "small", "{$sort[7]}",
                            "Sort descending");
    $classAsc = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/punishment/list.php") .
                                 "&amp;sort=" . dbfuncString2Int("8"), "A", "small", "{$sort[8]}",
                                "Sort ascending");
    $classDec = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/punishment/list.php") .
                                 "&amp;sort=" . dbfuncString2Int("9"), "D", "small", "{$sort[9]}",
                                "Sort descending");
    $reasonAsc = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/punishment/list.php") .
                                 "&amp;sort=" . dbfuncString2Int("10"), "A", "small", "{$sort[10]}",
                                "Sort ascending");
    $reasonDec = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/punishment/list.php") .
                                 "&amp;sort=" . dbfuncString2Int("11"), "D", "small", "{$sort[11]}",
                                "Sort descending");
    $punDateAsc = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/punishment/list.php") .
                                 "&amp;sort=" . dbfuncString2Int("12"), "A", "small", "{$sort[12]}",
                                "Sort ascending");
    $punDateDec = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/punishment/list.php") .
                                 "&amp;sort=" . dbfuncString2Int("13"), "D", "small", "{$sort[13]}",
                                "Sort descending");
    $showedAsc = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/punishment/list.php") .
                                 "&amp;sort=" . dbfuncString2Int("14"), "A", "small", "{$sort[14]}",
                                "Sort ascending");
    $showedDec = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/punishment/list.php") .
                                 "&amp;sort=" . dbfuncString2Int("15"), "D", "small", "{$sort[15]}",
                                "Sort descending");

    $query = "SELECT COUNT(discipline.DisciplineIndex) AS TotalRows " .
             "       FROM discipline, disciplineweight, classlist, classterm, class " .
             "WHERE discipline.DisciplineWeightIndex=disciplineweight.DisciplineWeightIndex " .
             "AND   disciplineweight.TermIndex = $termindex " .
             "AND   disciplineweight.YearIndex = $yearindex " .
             "AND   discipline.Username = classlist.Username " .
             "AND   classterm.ClassTermIndex = classlist.ClassTermIndex " .
             "AND   classterm.TermIndex = $termindex " .
             "AND   class.ClassIndex = classterm.ClassIndex " .
             "AND   class.YearIndex = $yearindex " .
             "AND   discipline.WorkerUsername IS NOT NULL ";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query
    $row = $res->fetchRow(DB_FETCHMODE_ASSOC);
    $max = intval($row['TotalRows']) - 1;
    $fpstart = intval(intval($max) / $LOGS_PER_PAGE) * $LOGS_PER_PAGE;

    $query = "SELECT disciplinetype.DisciplineType, disciplineweight.DisciplineWeight, user.Username, " .
             "       user.FirstName, user.Surname, discipline.Date, discipline.Comment, discipline.Extra, " .
             "       class.ClassName, " .
             "       discipline.DisciplineIndex, tuser.FirstName AS TFirstName, tuser.Title AS TTitle, " .
             "       tuser.Surname AS TSurname, ruser.FirstName AS RFirstName, ruser.Surname AS RSurname, " .
             "       ruser.Title AS RTitle, " .
             "       disciplinedate.PunishDate, discipline.ServedType " .
             "       FROM class, classterm, classlist, disciplinetype, disciplineweight, " .
             "       discipline LEFT OUTER JOIN disciplinedate ON " .
             "       discipline.DisciplineDateIndex=disciplinedate.DisciplineDateIndex, " .
             "       user, user AS tuser, user AS ruser " .
             "WHERE  disciplineweight.YearIndex = $yearindex " .
             "AND    discipline.WorkerUsername IS NOT NULL " .
             "AND    disciplineweight.TermIndex = $termindex " .
             "AND    discipline.DisciplineWeightIndex = disciplineweight.DisciplineWeightIndex " .
             "AND    disciplineweight.DisciplineTypeIndex = disciplinetype.DisciplineTypeIndex " .
             "AND    classlist.Username = user.Username " .
             "AND    discipline.Username = user.Username " .
             "AND    ruser.Username = discipline.RecordUsername " .
             "AND    tuser.Username = discipline.WorkerUsername " .
             "AND    classterm.ClassTermIndex = classlist.ClassTermIndex " .
             "AND    classterm.TermIndex = $termindex " .
             "AND    class.ClassIndex = classterm.ClassIndex " .
             "AND    class.YearIndex = $yearindex " . "$sortorder " .
             "LIMIT $start, $LOGS_PER_PAGE";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query
    $count = $res->numRows();

    include "core/titletermyear.php";

    if ($count > 0) {
        if (intval($start) > 0) {
            $first_record = dbfuncGetButton(
                                            "index.php?location=" .
                                                 dbfuncString2Int(
                                                                "admin/punishment/list.php") .
                                                 "&amp;sort={$_GET['sort']}&amp;start=" . dbfuncString2Int("0"),
                                                "<<", "medium", "prevnext",
                                                "First page");
            $prev = intval($start) - $LOGS_PER_PAGE;
            if ($prev < 0)
                $prev = 0;
            $prev = strval($prev);
            $prev_record = dbfuncGetButton(
                                        "index.php?location=" .
                                         dbfuncString2Int(
                                                        "admin/punishment/list.php") .
                                         "&amp;sort={$_GET['sort']}&amp;start=" . dbfuncString2Int($prev),
                                        "<", "medium", "prevnext",
                                        "Previous page");
        } else {
            $first_record = dbfuncGetDisabledButton("<<", "medium", "prevnext");
            $prev_record = dbfuncGetDisabledButton("<", "medium", "prevnext");
        }

        if (intval($start) < $fpstart) {
            $last_record = dbfuncGetButton(
                                        "index.php?location=" .
                                             dbfuncString2Int(
                                                            "admin/punishment/list.php") .
                                             "&amp;sort={$_GET['sort']}&amp;start=" . dbfuncString2Int($fpstart),
                                            ">>", "medium", "prevnext",
                                            "Last page");
            $next = intval($start) + $LOGS_PER_PAGE;
            if ($next > $fpstart)
                $next = $fpstart;
            $next = strval($next);
            $next_record = dbfuncGetButton(
                                        "index.php?location=" .
                                         dbfuncString2Int(
                                                        "admin/punishment/list.php") .
                                         "&amp;sort={$_GET['sort']}&amp;start=" . dbfuncString2Int($next),
                                        ">", "medium", "prevnext", "Next page");
        } else {
            $last_record = dbfuncGetDisabledButton(">>", "medium", "prevnext");
            ;
            $next_record = dbfuncGetDisabledButton(">", "medium", "prevnext");
            ;
        }

        $startval = strval(intval($start) + 1);
        $endval = strval(intval($start) + $count);
        $totalval = strval($max + 1);

        /* Print header with rows being shown and buttons to move in punishment list */
        echo "      <table class=\"transparent\" width=\"100%\">\n";
        echo "         <tr>\n";
        echo "            <td width=\"100px\" nowrap>$first_record $prev_record</td>\n";
        echo "            <td class=\"title\" nowrap><span class=\"text\">Showing punishments $startval-$endval " .
             "of $totalval</span></td>\n";
        echo "            <td width=\"100px\" align=\"right\" nowrap>$next_record $last_record</td>\n";
        echo "         </tr>\n";
        echo "      </table>\n";

        /* Print punishments */
        echo "         <span class=\"small_text\">\n";
        echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
        echo "         <tr>\n";
        echo "            <th nowrap></th>\n";
        echo "            <th nowrap>Teacher $teacherAsc $teacherDec</th>\n";
        echo "            <th nowrap>Type $typeAsc $typeDec</th>\n";
        echo "            <th nowrap>Student $studentAsc $studentDec</th>\n";
        echo "            <th nowrap>Class $classAsc $classDec</th>\n";
        echo "            <th nowrap><a title='Violation Date'>VD</a> $dateAsc $dateDec</th>\n";
        echo "            <th nowrap>Reason $reasonAsc $reasonDec</th>\n";
        echo "            <th nowrap>Comment</th>\n";
        echo "            <th nowrap><a title='Punishment Date'>PD</a> $punDateAsc $punDateDec</th>\n";
        echo "            <th nowrap>Showed up? $showedAsc $showedDec</th>\n";
        echo "            <th nowrap>Recorded By</th>\n";
        echo "         </tr>\n";

        /* For each assignment, print a row with the title, date, score and comment */
        $alt_count = 0;
        while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
            $alt_count += 1;
            if ($alt_count % 2 == 0) {
                $alt_step = "alt";
            } else {
                $alt_step = "std";
            }
            if ($row['PermLevel'] == 99) {
                $alt = " class=\"$alt_step\"";
            } elseif (! is_null($row['ServedType']) and $row['ServedType'] == 1) {
                $alt = " class=\"$alt_step\"";
            } elseif ((! is_null($row['ServedType']) and $row['ServedType'] == 0) or
                     $row['DisciplineWeight'] == 0) {
                $alt = " class=\"late-$alt_step\"";
            } else {
                $alt = " class=\"$alt_step\"";
            }
            $dateinfo = date($dateformat, strtotime($row['Date']));
            if ($row['PunishDate'] != "") {
                $punish_date = date($dateformat,
                                    strtotime($row['PunishDate']));
            } else {
                $punish_date = "&nbsp;";
            }
            echo "         <tr$alt>\n";
            $dellink = "index.php?location=" .
                     dbfuncString2Int("admin/punishment/delete_confirm.php") .
                     "&amp;key=" . dbfuncString2Int($row['DisciplineIndex']) .
                     "&amp;next=" .
                     dbfuncString2Int(
                                    "index.php?location=" .
                                     dbfuncString2Int(
                                                    "admin/punishment/list.php"));
            $delbutton = dbfuncGetButton($dellink, "D", "small", "delete",
                                        "Delete punishment");
            echo "            <td nowrap>$delbutton</td>\n";
            echo "            <td nowrap>{$row['TFirstName']} {$row['TSurname']}</td>\n";
            echo "            <td nowrap>{$row['DisciplineType']}</td>\n";
            echo "            <td nowrap>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
            echo "            <td nowrap>{$row['ClassName']}</td>\n";
            echo "            <td nowrap>$dateinfo</td>\n";
            echo "            <td>{$row['Comment']}</td>\n";
            echo "            <td>{$row['Extra']}</td>\n";
            echo "            <td nowrap>$punish_date</td>\n";
            if (! is_null($row['ServedType']) and $row['ServedType'] == 1) {
                echo "            <td nowrap>Yes</td>\n";
            } elseif (! is_null($row['ServedType']) and $row['ServedType'] == 0) {
                echo "            <td nowrap>No</td>\n";
            } else {
                echo "            <td nowrap>&nbsp;</td>\n";
            }
            echo "            <td nowrap>{$row['RFirstName']} {$row['RSurname']}</td>\n";
            echo "         </tr>\n";
        }
        echo "      </table>\n";
        echo "      </span>\n";
        /* Print header with rows being shown and buttons to move in punishment list */
        echo "      <table class=\"transparent\" width=\"100%\">\n";
        echo "         <tr>\n";
        echo "            <td width=\"100px\" nowrap>$first_record $prev_record</td>\n";
        echo "            <td class=\"title\" nowrap><span class=\"text\">Showing punishments $startval-$endval " .
             "of $totalval</span></td>\n";
        echo "            <td width=\"100px\" align=\"right\" nowrap>$next_record $last_record</td>\n";
        echo "         </tr>\n";
        echo "      </table>\n";
    } else {
        echo "      <p align=\"center\" class=\"subtitle\">No punishments have been issued this term.</p>\n";
    }
    log_event($LOG_LEVEL_ADMIN, "admin/punishment/list.php", $LOG_ADMIN,
            "Viewed issued punishments for this term.");
} else {
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "admin/punishment/list.php", $LOG_DENIED_ACCESS,
            "Tried to view issued punishments for this term.");

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
}

include "footer.php";
?>
