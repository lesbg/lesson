<?php
/**
 * ***************************************************************
 * admin/viewlog.php (c) 2005 Jonathan Dieter
 *
 * View log of activity
 * ***************************************************************
 */
$title = "Log";

include "header.php"; // Show header

if (dbfuncGetPermission($permissions, $PERM_ADMIN)) { // Make sure user has permissions
    log_event($LOG_LEVEL_EVERYTHING, "admin/viewlog.php", $LOG_ADMIN,
        "Viewed log.");

    if (isset($_GET['start'])) {
        $start = intval($_GET['start']);
    } else {
        $start = "0";
    }

    if (! isset($_GET['sort'])) {
        $_GET['sort'] = '0';
    }

    for($a = 0; $a < 14; $a ++) {
        $sort[$a] = "sort";
    }

    $sort[intval($_GET['sort'])] = "bsort";

    if ($_GET['sort'] == '1') {
        $sortorder = "ORDER BY log.Time, log.LogIndex ";
    } elseif ($_GET['sort'] == '2') {
        $sortorder = "ORDER BY user.Username, log.Time DESC, log.LogIndex DESC ";
    } elseif ($_GET['sort'] == '3') {
        $sortorder = "ORDER BY user.Username DESC, log.Time DESC, log.LogIndex DESC ";
    } elseif ($_GET['sort'] == '4') {
        $sortorder = "ORDER BY log.Level, log.Time DESC, log.LogIndex DESC ";
    } elseif ($_GET['sort'] == '5') {
        $sortorder = "ORDER BY log.Level DESC, log.Time DESC, log.LogIndex DESC ";
    } elseif ($_GET['sort'] == '6') {
        $sortorder = "ORDER BY log.Code, log.Time DESC, log.LogIndex DESC ";
    } elseif ($_GET['sort'] == '7') {
        $sortorder = "ORDER BY log.Code DESC, log.Time DESC, log.LogIndex DESC ";
    } elseif ($_GET['sort'] == '8') {
        $sortorder = "ORDER BY log.Comment, log.Time DESC, log.LogIndex DESC ";
    } elseif ($_GET['sort'] == '9') {
        $sortorder = "ORDER BY log.Comment DESC, log.Time DESC, log.LogIndex DESC ";
    } elseif ($_GET['sort'] == '10') {
        $sortorder = "ORDER BY log.Page, log.Time DESC, log.LogIndex DESC ";
    } elseif ($_GET['sort'] == '11') {
        $sortorder = "ORDER BY log.Page DESC, log.Time DESC, log.LogIndex DESC ";
    } elseif ($_GET['sort'] == '12') {
        $sortorder = "ORDER BY log.RemoteHost, log.Time DESC, log.LogIndex DESC ";
    } elseif ($_GET['sort'] == '13') {
        $sortorder = "ORDER BY log.RemoteHost DESC, log.Time DESC, log.LogIndex DESC ";
    } else {
        $sortorder = "ORDER BY log.Time DESC, log.LogIndex DESC ";
    }

    $sessionAsc = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/viewlog.php") .
                                 "&amp;sort=1", "A", "small", "{$sort[1]}",
                                "Sort ascending");
    $sessionDec = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/viewlog.php") .
                                 "&amp;sort=0", "D", "small", "{$sort[0]}",
                                "Sort descending");
    $unameAsc = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/viewlog.php") .
                                 "&amp;sort=2", "A", "small", "{$sort[2]}",
                                "Sort ascending");
    $unameDec = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/viewlog.php") .
                                 "&amp;sort=3", "D", "small", "{$sort[3]}",
                                "Sort descending");
    $levelAsc = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/viewlog.php") .
                                 "&amp;sort=4", "A", "small", "{$sort[4]}",
                                "Sort ascending");
    $levelDec = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/viewlog.php") .
                                 "&amp;sort=5", "D", "small", "{$sort[5]}",
                                "Sort descending");
    $codeAsc = dbfuncGetButton(
                            "index.php?location=" .
                             dbfuncString2Int("admin/viewlog.php") .
                             "&amp;sort=6", "A", "small", "{$sort[6]}",
                            "Sort ascending");
    $codeDec = dbfuncGetButton(
                            "index.php?location=" .
                             dbfuncString2Int("admin/viewlog.php") .
                             "&amp;sort=7", "D", "small", "{$sort[7]}",
                            "Sort descending");
    $commentAsc = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/viewlog.php") .
                                 "&amp;sort=8", "A", "small", "{$sort[8]}",
                                "Sort ascending");
    $commentDec = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/viewlog.php") .
                                 "&amp;sort=9", "D", "small", "{$sort[9]}",
                                "Sort descending");
    $pageAsc = dbfuncGetButton(
                            "index.php?location=" .
                             dbfuncString2Int("admin/viewlog.php") .
                             "&amp;sort=10", "A", "small", "{$sort[10]}",
                            "Sort ascending");
    $pageDec = dbfuncGetButton(
                            "index.php?location=" .
                             dbfuncString2Int("admin/viewlog.php") .
                             "&amp;sort=11", "D", "small", "{$sort[11]}",
                            "Sort descending");
    $rhostAsc = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/viewlog.php") .
                                 "&amp;sort=12", "A", "small", "{$sort[12]}",
                                "Sort ascending");
    $rhostDec = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/viewlog.php") .
                                 "&amp;sort=13", "D", "small", "{$sort[13]}",
                                "Sort descending");

    $res = &  $db->query("SELECT COUNT(log.Time) AS TotalRows FROM log");
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query
    $row = $res->fetchRow(DB_FETCHMODE_ASSOC);
    $max = intval($row['TotalRows']) - 1;
    $fpstart = intval(intval($max) / $LOGS_PER_PAGE) * $LOGS_PER_PAGE;
    if ($start > $max)
        $start = 0;

    $res = &  $db->query(
                    "SELECT user.FirstName, user.Surname, log.Username, log.Time, log.Session, " .
                     "       log.Code, log.Level, log.Comment, log.RemoteHost, log.Page FROM log " .
                     "       LEFT OUTER JOIN user ON user.Username = log.Username " .
                     "$sortorder " . "LIMIT $start, $LOGS_PER_PAGE");
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query
    $count = $res->numRows();

    if (intval($start) > 0) {
        $first_record = dbfuncGetButton(
                                        "index.php?location=" .
                                             dbfuncString2Int(
                                                            "admin/viewlog.php") .
                                             "&amp;sort={$_GET['sort']}&amp;start=0",
                                            "<<", "medium", "prevnext",
                                            "First page");
        $prev = intval($start) - $LOGS_PER_PAGE;
        if ($prev < 0)
            $prev = 0;
        $prev = strval($prev);
        $prev_record = dbfuncGetButton(
                                    "index.php?location=" .
                                     dbfuncString2Int("admin/viewlog.php") .
                                     "&amp;sort={$_GET['sort']}&amp;start=$prev",
                                    "<", "medium", "prevnext", "Previous page");
    } else {
        $first_record = dbfuncGetDisabledButton("<<", "medium", "prevnext");
        $prev_record = dbfuncGetDisabledButton("<", "medium", "prevnext");
    }

    if (intval($start) < $fpstart) {
        $last_record = dbfuncGetButton(
                                    "index.php?location=" .
                                         dbfuncString2Int("admin/viewlog.php") .
                                         "&amp;sort={$_GET['sort']}&amp;start=$fpstart",
                                        ">>", "medium", "prevnext", "Last page");
        $next = intval($start) + $LOGS_PER_PAGE;
        if ($next > $fpstart)
            $next = $fpstart;
        $next = strval($next);
        $next_record = dbfuncGetButton(
                                    "index.php?location=" .
                                     dbfuncString2Int("admin/viewlog.php") .
                                     "&amp;sort={$_GET['sort']}&amp;start=$next",
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

    /* Print header with rows being shown and buttons to move in log */
    echo "      <table class=\"transparent\" width=\"100%\">\n";
    echo "         <tr>\n";
    echo "            <td width=\"100px\" nowrap>$first_record $prev_record</td>\n";
    echo "            <td align=\"center\" nowrap>Showing records $startval-$endval " .
         "of $totalval</td>\n";
    echo "            <td width=\"100px\" align=\"right\" nowrap>$next_record $last_record</td>\n";
    echo "         </tr>\n";
    echo "      </table>\n";

    /* Print students and their class */
    if ($res->numRows() > 0) {
        echo "         <span class=\"small_text\">\n";
        echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
        echo "         <tr>\n";
        echo "            <th nowrap>Time $sessionAsc $sessionDec</th>\n";
        echo "            <th nowrap>User $unameAsc $unameDec</th>\n";
        /* echo " <th nowrap>Level $levelAsc $levelDec</th>\n"; */
        echo "            <th nowrap>Code $codeAsc $codeDec</th>\n";
        echo "            <th nowrap>Comment $commentAsc $commentDec</th>\n";
        /* echo " <th nowrap>Page $pageAsc $pageDec</th>\n"; */
        echo "            <th nowrap>Remote Host $rhostAsc $rhostDec</th>\n";
        echo "         </tr>\n";

        /* For each student, print a row with the student's name and what class they're in */
        $alt_count = 0;
        while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
            $alt_count += 1;
            if ($alt_count % 2 == 0) {
                $alt = " class=\"alt\"";
            } else {
                $alt = " class=\"std\"";
            }
            echo "         <tr$alt>\n";
            echo "            <td nowrap>{$row['Time']}</td>\n";
            if ($row['Surname'] != NULL) {
                echo "            <td nowrap>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
            } else {
                echo "            <td nowrap>{$row['Username']} (No match in database)</td>\n";
            }
            /* echo " <td nowrap>{$LOG_LEVEL_STRING[$row['Level']]}</td>\n"; */
            echo "            <td nowrap>{$LOG_STRING[$row['Code']]}</td>\n";
            echo "            <td nowrap>{$row['Comment']}</td>\n";
            /* echo " <td nowrap>{$row['Page']}</td>\n"; */
            echo "            <td nowrap>{$row['RemoteHost']}</td>\n";
            echo "         </tr>\n";
        }
        echo "      </table>\n"; // End of table
        echo "      </span>\n";
    } else {
        echo "      <p>The log is empty.</p>\n";
    }
    /* Print header with rows being shown and buttons to move in log */
    echo "      <table class=\"transparent\" width=\"100%\">\n";
    echo "         <tr>\n";
    echo "            <td width=\"50px\">$first_record $prev_record</td>\n";
    echo "            <td class=\"title\"><span class=\"text\">$startval-$endval " .
         "of $totalval</span></td>\n";
    echo "            <td width=\"50px\">$next_record $last_record</td>\n";
    echo "         </tr>\n";
    echo "      </table>\n";
} else {
    log_event($LOG_LEVEL_ERROR, "admin/viewlog.php", $LOG_DENIED_ACCESS,
            "Tried to view log.");
    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
}

include "footer.php";
?>
