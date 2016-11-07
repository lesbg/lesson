<?php
/**
 * ***************************************************************
 * admin/punishment/proxy_action.php (c) 2006-2016 Jonathan Dieter
 *
 * Issue punishment on behalf of another teacher
 * ***************************************************************
 */

/* Get variables */
$nextLink = dbfuncInt2String($_GET['next']); // Link to next page

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

$query = "SELECT Permissions FROM disciplineperms WHERE Username='$username'";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    $perm = $row['Permissions'];
} else {
    $perm = $DEFAULT_PUN_PERM;
}

$showalldeps = true;
include "core/settermandyear.php";

/* Check whether user is authorized to issue mass punishment */
if ($is_admin or ($perm >= $PUN_PERM_PROXY and $is_teacher)) {
    if (isset($_POST["punished"])) {
        $punish_list = dbfuncString2Array($_POST["punished"]);
    } else {
        $punish_list = array();
    }

    /* Check which button was pressed */
    if ($_POST["action"] == ">") { // If > was pressed, remove selected students from
        foreach ( $_POST['removefrompunishment'] as $remIndex ) { // punishment
            unset($punish_list[$remIndex]);
        }
        include "admin/punishment/proxy.php";
    } elseif ($_POST["action"] == ">>") {
        unset($punish_list);
        $punish_list = array();
        include "admin/punishment/proxy.php";
    } elseif ($_POST["action"] == "<") { // If < was pressed, add selected students to
        if (isset($_POST['addtopunishment'])) {
            foreach ( $_POST['addtopunishment'] as $addUserName ) {
                $query = "SELECT user.FirstName, user.Surname FROM " .
                         "       user, classterm, classlist, class " .
                         "WHERE  user.Username = '$addUserName' " .
                         "AND    user.Username = classlist.Username " .
                         "AND    classlist.ClassTermIndex = classterm.ClassTermIndex " .
                         "AND    classterm.TermIndex = $currentterm " .
                         "AND    classterm.ClassIndex = class.ClassIndex " .
                         "AND    class.YearIndex = $currentyear";
                $nres = &  $db->query($query);
                if (DB::isError($nres))
                    die($nres->getDebugInfo()); // Check for errors in query
                if ($nrow = & $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
                    print $nrow['Firstname'];
                    if (! isset($_POST['date']) || $_POST['date'] == "") { // Make sure date is in correct format.
                        $dateinfo = & dbfuncCreateDate(date($dateformat));
                    } else {
                        $dateinfo = & dbfuncCreateDate($_POST['date']);
                    }
                    $dateinfo = $db->escapeSimple($dateinfo);
                    $thisdateinfo = dbfuncCreateDate(date($dateformat));
                    $weightindex = intval($_POST['type']);
                    $query = "SELECT DisciplineType FROM disciplineweight, disciplinetype " .
                             "WHERE  disciplineweight.DisciplineWeightIndex = $weightindex " .
                             "AND    disciplinetype.DisciplineTypeIndex = disciplineweight.DisciplineTypeIndex " .
                             "AND    disciplineweight.YearIndex = $currentyear " .
                             "AND    disciplineweight.TermIndex = $currentterm ";
                    $res = & $db->query($query);
                    if (DB::isError($res))
                        die($res->getDebugInfo()); // Check for errors in query
                    $failed = 0;
                    if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
                        $dtype = $row['DisciplineType'];
                        if ($_POST['reason'] == "" or
                             is_null($_POST['reason'])) {
                            $errorlist[] = "You must explain why you want the students punished!";
                            $failed = 1;
                        } elseif ($_POST['reason'] == "other") {
                            if ($_POST['reasonother'] == "" or
                                     is_null($_POST['reasonother'])) {
                                $errorlist[] = "You must explain why you want the students punished!";
                                $failed = 1;
                            } else {
                                $reason = $_POST['reasonother'];
                            }
                        } else {
                            $reasonindex = intval($_POST['reason']);
                            $query = "SELECT DisciplineReason FROM disciplinereason " .
                                     "WHERE  DisciplineReasonIndex = $reasonindex";
                            $res = & $db->query($query);
                            if (DB::isError($res))
                                die($res->getDebugInfo()); // Check for errors in query
                            if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
                                $reason = $row['DisciplineReason'];
                            } else {
                                $errorlist[] = "You must explain why you want the students punished!";
                                $failed = 1;
                            }
                        }
                        $tusername = $db->escapeSimple($_POST['teacher']);
                        $query = "SELECT Title, Surname FROM user WHERE Username='$tusername'";
                        $res = & $db->query($query);
                        if (DB::isError($res))
                            die($res->getDebugInfo()); // Check for errors in query
                        if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
                            $ttitle = $row['Title'];
                            $tsurname = $row['Surname'];
                        } else {
                            $errorlist[] = "You must select a teacher!";
                            $failed = 1;
                        }

                        if ($failed == 0) {
                            $punish_list[] = array(
                                    "display" => "{$nrow['FirstName']} {$nrow['Surname']} ($addUserName) - " .
                                         "$dtype - $ttitle $tsurname - {$_POST['date']}",
                                        "student" => $addUserName,
                                        "teacher" => $tusername,
                                        "issuedate" => $dateinfo,
                                        "today" => $thisdateinfo,
                                        "weightindex" => $weightindex,
                                        "reason" => $reason,
                                        "dtype" => $dtype,
                                        "type" => 0
                            );
                        }
                    } else {
                        $errorlist[] = "You must explain why you want the students punished!";
                    }
                }
            }
        }
        include "admin/punishment/proxy.php";
    } elseif ($_POST["action"] == "<<") {
        $_POST["class"] = intval($_POST["class"]);
        $res = &  $db->query(
                        "SELECT ClassName FROM class " .
                         "WHERE ClassIndex={$_POST['class']}");
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query
        if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            $classindex = $_POST['class'];
            $classname = $row['ClassName'];
            if (! isset($_POST['date']) || $_POST['date'] == "") { // Make sure date is in correct format.
                $dateinfo = & dbfuncCreateDate(date($dateformat));
            } else {
                $dateinfo = & dbfuncCreateDate($_POST['date']);
            }
            $dateinfo = $db->escapeSimple($dateinfo);
            $thisdateinfo = dbfuncCreateDate(date($dateformat));
            $weightindex = intval($_POST['type']);
            $query = "SELECT DisciplineType FROM disciplineweight, disciplinetype " .
                     "WHERE  disciplineweight.DisciplineWeightIndex = $weightindex " .
                     "AND    disciplinetype.DisciplineTypeIndex = disciplineweight.DisciplineTypeIndex " .
                     "AND    disciplineweight.YearIndex = $currentyear " .
                     "AND    disciplineweight.TermIndex = $currentterm ";
            $res = & $db->query($query);
            if (DB::isError($res))
                die($res->getDebugInfo()); // Check for errors in query
            $failed = 0;
            if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
                $dtype = $row['DisciplineType'];
                if ($_POST['reason'] == "" or is_null($_POST['reason'])) {
                    $errorlist[] = "You must explain why you want the students punished!";
                    $failed = 1;
                } elseif ($_POST['reason'] == "other") {
                    if ($_POST['reasonother'] == "" or
                             is_null($_POST['reasonother'])) {
                        $errorlist[] = "You must explain why you want the students punished!";
                        $failed = 1;
                    } else {
                        $reason = $_POST['reasonother'];
                    }
                } else {
                    $reasonindex = intval($_POST['reason']);
                    $query = "SELECT DisciplineReason FROM disciplinereason " .
                             "WHERE  DisciplineReasonIndex = $reasonindex";
                    $res = & $db->query($query);
                    if (DB::isError($res))
                        die($res->getDebugInfo()); // Check for errors in query
                    if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
                        $reason = $row['DisciplineReason'];
                    } else {
                        $errorlist[] = "You must explain why you want the students punished!";
                        $failed = 1;
                    }
                }
                $tusername = $db->escapeSimple($_POST['teacher']);
                $query = "SELECT Title, Surname FROM user WHERE Username='$tusername'";
                $res = & $db->query($query);
                if (DB::isError($res))
                    die($res->getDebugInfo()); // Check for errors in query
                if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
                    $ttitle = $row['Title'];
                    $tsurname = $row['Surname'];
                } else {
                    $errorlist[] = "You must select a teacher!";
                    $failed = 1;
                }

                if ($failed == 0) {
                    $punish_list[] = array(
                            "display" => "Class $classname - " .
                                 "$dtype - $ttitle $tsurname - {$_POST['date']}",
                                "class" => $classindex,
                                "teacher" => $tusername,
                                "issuedate" => $dateinfo,
                                "today" => $thisdateinfo,
                                "weightindex" => $weightindex,
                                "reason" => $reason,
                                "dtype" => $dtype,
                                "type" => 1
                    );
                }
            } else {
                $errorlist[] = "You must explain why you want the students punished!";
            }
        }
        include "admin/punishment/proxy.php";
    } elseif ($_POST["action"] == "Issue Punishments") {
        $title = "LESSON - Issuing punishments...";
        $noHeaderLinks = true;
        $noJS = true;

        include "header.php"; // Print header

        echo "      <p align='center'>Issuing punishments...";

        /* Check whether or not a type was included and cancel if it wasn't */
        if ($_POST['type'] == "" or is_null($_POST['type'])) {
            echo "failed</p>\n";
            echo "      <p align='center'>You must select a punishment type!</p>\n";
        } else {
            foreach ( $punish_list as $key => $punishment ) {
                if ($punishment != "") {
                    $weightindex = intval($punishment['weightindex']);
                    $tusername = $db->escapeSimple($punishment['teacher']);
                    $thisdateinfo = $db->escapeSimple($punishment['today']);
                    $dateinfo = $db->escapeSimple($punishment['issuedate']);
                    $reason = $db->escapeSimple($punishment['reason']);
                    $dtype = $punishment['dtype'];
                    if ($punishment['type'] == 1) {
                        $classindex = intval($punishment['class']);
                        $query = "SELECT user.Username FROM user, classterm, classlist " .
                                 "WHERE  user.Username = classlist.Username " .
                                 "AND    classterm.TermIndex = $currentterm " .
                                 "AND    classlist.ClassTermIndex = classterm.ClassTermIndex " .
                                 "AND    classterm.ClassIndex = $classindex " .
                                 "ORDER BY user.Username";
                        $pres = &  $db->query($query);
                        if (DB::isError($pres))
                            die($pres->getDebugInfo()); // Check for errors in query
                        while ( $row = & $pres->fetchRow(DB_FETCHMODE_ASSOC) ) {
                            $query = "INSERT INTO discipline (DisciplineWeightIndex, Username, WorkerUsername, " .
                                     "                        RecordUsername, DateRequested, DateIssued, " .
                                     "                        Date, Comment) " .
                                     "       VALUES " .
                                     "       ($weightindex, '{$row['Username']}', '$tusername', '$username', " .
                                     "        '$thisdateinfo', '$thisdateinfo', '$dateinfo', '$reason')";
                            $res = & $db->query($query);
                            if (DB::isError($res))
                                die($res->getDebugInfo()); // Check for errors in query
                            update_conduct_mark($row['Username']);
                            log_event($LOG_LEVEL_ADMIN,
                                    "admin/punishment/proxy_action.php",
                                    $LOG_ADMIN,
                                    "Issued $dtype to {$row['Username']} on behalf of $tusername.");
                        }
                    } else {
                        $studentusername = $db->escapeSimple(
                                                            $punishment['student']);
                        $query = "INSERT INTO discipline (DisciplineWeightIndex, Username, WorkerUsername, " .
                                 "                        RecordUsername, DateRequested, DateIssued, " .
                                 "                        Date, Comment) " .
                                 "       VALUES " .
                                 "       ($weightindex, '$studentusername', '$tusername', '$username', " .
                                 "        '$thisdateinfo', '$thisdateinfo', '$dateinfo', '$reason')";
                        $res = & $db->query($query);
                        if (DB::isError($res))
                            die($res->getDebugInfo()); // Check for errors in query
                        update_conduct_mark($studentusername);
                        log_event($LOG_LEVEL_ADMIN,
                                "admin/punishment/proxy_action.php", $LOG_ADMIN,
                                "Issued $dtype to $studentusername on behalf of $tusername.");
                    }
                }
            }
            echo " done</p>\n";
        }

        echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n"; // Link to next page

        include "footer.php";
    } elseif ($_POST["action"] == "Cancel") {
        $extraMeta = "      <meta http-equiv='REFRESH' content='0;url=$nextLink'>\n";
        $noJS = true;
        $noHeaderLinks = true;
        $title = "LESSON - Redirecting...";

        include "header.php";

        echo "      <p align='center'>Redirecting you to <a href='$nextLink'>$nextLink</a></p>\n";

        include "footer.php";
    } else {
        include "admin/punishment/proxy.php";
    }
} else {
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "admin/punishment/proxy_action.php",
            $LOG_DENIED_ACCESS, "Attempted to issue proxy punishment.");

    $noJS = true;
    $noHeaderLinks = true;
    $title = "LESSON - Unauthorized access!";

    include "header.php";

    echo "      <p align='center'>You do not have permission to access this page. <a href=" .
         "'$nextLink'>Click here to continue.</a></p>\n";

    include "footer.php";
}

?>
