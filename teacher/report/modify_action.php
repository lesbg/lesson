<?php
/**
 * ***************************************************************
 * teacher/report/modify_action.php (c) 2008 Jonathan Dieter
 *
 * Run query to change grades
 * ***************************************************************
 */
$student_name = "";
$student_username = "";

/* Get variables */
$subjectindex = safe(dbfuncInt2String($_GET['key']));
$subject = dbfuncInt2String($_GET['keyname']);
if (isset($_GET['key2'])) {
    $student_username = safe(dbfuncInt2String($_GET['key2']));
    $student_name = dbfuncInt2String($_GET['keyname2']);
}

$nextLink = dbfuncInt2String($_GET['next']); // Link to next page
$error = false; // Boolean to store any errors

include "core/settermandyear.php";

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

/* Check whether current user is a hod */
$res = &  $db->query(
                "SELECT hod.Username FROM hod, term, subject " .
                 "WHERE hod.Username         = '$username' " .
                 "AND   hod.DepartmentIndex  = term.DepartmentIndex " .
                 "AND   term.TermIndex       = subject.TermIndex " .
                 "AND   subject.SubjectIndex = $subjectindex");
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
    $is_hod = true;
} else {
    $is_hod = false;
}

/* Check whether user is subject teacher */
$query = "SELECT subjectteacher.Username FROM subjectteacher " .
         "WHERE subjectteacher.SubjectIndex = $subjectindex " .
         "AND   subjectteacher.Username     = '$username' ";
$res = & $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
    $is_st = true;
} else {
    $is_st = false;
}

/* Check whether user is proofreader */
$query = "SELECT department.ProofreaderUsername FROM department, subject " .
         "WHERE subject.SubjectIndex           = $subjectindex " .
         "AND   department.ProofreaderUsername = '$username' " .
         "AND   department.DepartmentIndex     = subject.DepartmentIndex ";
$res = & $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
    $is_proofreader = true;
} else {
    $is_proofreader = false;
}

/* Check whether user is class teacher */
$query = "SELECT class.ClassTeacherUsername FROM class, classterm, classlist " .
         "WHERE  classlist.Username         = '$student_username' " .
         "AND    classlist.ClassTermIndex   = classterm.ClassTermIndex " .
         "AND    classterm.TermIndex        = $termindex " .
         "AND    classterm.ClassIndex       = class.ClassIndex " .
         "AND    class.ClassTeacherUsername = '$username' " .
         "AND    class.YearIndex            = $yearindex ";
$res = & $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
    $is_ct = true;
} else {
    $is_ct = false;
}

if (! $is_st and ! $is_admin and ! $is_hod and ! $is_principal and
     ($student_username == "" or (! $is_ct and ! $is_proofreader))) {
    /* Print error message */
    include "header.php"; // Show header

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    log_event($LOG_LEVEL_ERROR, "teacher/report/comment_list.php",
            $LOG_DENIED_ACCESS, "Tried to modify report for $subject.");

    include "footer.php";
    exit(0);
}

/* Check whether subject is open for report editing */
$query = "SELECT subject.AverageType, subject.EffortType, subject.ConductType, " .
         "       subject.AverageTypeIndex, subject.EffortTypeIndex, " .
         "       subject.ConductTypeIndex, subject.CommentType, subject.CanDoReport " .
         "       FROM subject " . "WHERE subject.SubjectIndex = $subjectindex";
$res = & $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if (! $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) or $row['CanDoReport'] == 0) {
    /* Print error message */
    include "header.php"; // Show header

    print $query;
    echo "      <p>Reports for this subject aren't open.</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    log_event($LOG_LEVEL_ERROR, "teacher/report/comment_list.php",
            $LOG_DENIED_ACCESS, "Tried to modify report for $subject.");

    include "footer.php";
    exit(0);
}

$include = "teacher/report/modify.php";
$do_include = true;
$average_type = $row['AverageType'];
$effort_type = $row['EffortType'];
$conduct_type = $row['ConductType'];
$comment_type = $row['CommentType'];
$average_type_index = $row['AverageTypeIndex'];
$effort_type_index = $row['EffortTypeIndex'];
$conduct_type_index = $row['ConductTypeIndex'];

foreach ( $_POST as $postkey => $postval ) {
    if (substr($postkey, 0, 13) == "find_comment_" and $postval == "...") {
        $st_username_click = substr($postkey, 13);
        $include = "teacher/report/comment_list.php";
        $do_include = true;
    } elseif (substr($postkey, 0, 8) == "cupdate_" and $postval == "Choose") {
        $comment_index = substr($postkey, 8);
        if (! isset($_POST['student_username']))
            continue;
        $st_username = $_POST['student_username'];
        $comment_array = get_comment($st_username, $comment_index);
        $_POST["comment_$st_username"] = $comment_array[0];
        $_POST["cval_$st_username"] = "{$comment_array[1]}";
        $do_include = true;
    } elseif (substr($postkey, 0, 8) == "comment_") {
        $cval_total = 0;
        $cval_count = 0;

        $st_username = substr($postkey, 8);

        /*
         * Ugly hack to make sure current value of comment gets averaged if comment wasn't erased
         * Please note that this is just a "rough guess" of the tone of the comment
         */
        if (isset($_POST["cval_{$st_username}"]) and
             ! is_null($_POST["cval_{$st_username}"]) and
             $_POST["cval_{$st_username}"] != "" and strlen($postval) > 10) {
            $cval_count += 1;
            $cval_total += floatval($_POST["cval_{$st_username}"]);
        } else {
            $_POST["cval_{$st_username}"] = '';
        }

        $startloc = strpos($postval, '{');
        while ( $startloc !== false ) {
            $endloc = strpos($postval, '}');
            if ($endloc === false) {
                $postval = str_replace('{', '(', $postval);
                $startloc = strpos($postval, '{');
                continue;
            }
            if ($endloc < $startloc) {
                $postval = substr_replace($postval, ')', $endloc, 1);
                $startloc = strpos($postval, '{');
                continue;
            }
            $nextloc = strpos($postval, '{', $startloc + 1);
            if ($nextloc !== false and $nextloc < $endloc) {
                $postval = substr_replace($postval, '(', $startloc, 1);
                $startloc = strpos($postval, '{');
                continue;
            }
            $replaceval = substr($postval, $startloc + 1,
                                $endloc - ($startloc + 1));
            if ($replaceval == "") {
                $postval = str_replace('{}', '()', $postval);
                $startloc = strpos($postval, '{');
                continue;
            }
            if (strval(intval($replaceval)) != $replaceval) {
                $postval = str_replace("{" . $replaceval . "}", "($replaceval)",
                                    $postval);
                $startloc = strpos($postval, '{');
                continue;
            }

            $comment_array = get_comment($st_username, $replaceval);

            if ($comment_array === false) {
                $postval = str_replace("{" . $replaceval . "}", "($replaceval)",
                                    $postval);
                $startloc = strpos($postval, '{');
                continue;
            }

            $comment = $comment_array[0];
            $strength = $comment_array[1];

            $postval = str_replace("{" . $replaceval . "}", $comment, $postval);

            $cval_count += 1;
            $cval_total += $strength;
            $_POST["cval_{$st_username}"] = strval(
                                                floatval($cval_total) /
                                                 floatval($cval_count));
            $startloc = strpos($postval, '{');
        }
        $postval = str_replace('}', ')', $postval);
        $postval = trim($postval);
        $_POST[$postkey] = $postval;
    }
}

if ($_POST['action'] == "Update" or
     $_POST['action'] == "Apply conduct to all my subjects" or
     $_POST['action'] == "I'm finished with these marks") {
    /* Check for subject teachers and store in list */
    $query = "SELECT subjectteacher.Username FROM subjectteacher " .
     "WHERE  subjectteacher.SubjectIndex = $subjectindex";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    $teachers = array();
    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        array_push($teachers, $row['Username']);
    }
    $query = "SELECT user.Username FROM user, subjectstudent " .
             "WHERE user.Username               = subjectstudent.Username " .
             "AND   subjectstudent.SubjectIndex = $subjectindex ";
    if ($student_username == "") {
        $query .= "AND   subjectstudent.ReportDone   = 0";
    } else {
        $query .= "AND   subjectstudent.Username     = '$student_username'";
    }
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    if ($_POST['action'] == "Update" or
         $_POST['action'] == "Apply conduct to all my subjects") {
        $title = "LESSON - Saving changes...";
        $noHeaderLinks = true;
        $noJS = true;

        include "header.php";

        echo "      <p align='center'>Saving changes...";
    }

    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        /* Check for type of effort mark and put in appropriate information */
        if ($is_st or $is_ct or $is_hod or $is_principal or $is_admin) {
            $average = "-1";
            if ($average_type != $AVG_TYPE_NONE and
                 isset($_POST["avg_{$row['Username']}"])) {
                /*
                 * if($effort_type == $EFFORT_TYPE_PERCENT) {
                 * $scorestr = $_POST["effort_{$row['Username']}"];
                 * if(strval(intval($scorestr)) != $scorestr) {
                 * $effort = "-1";
                 * } elseif(intval($scorestr) > 100) {
                 * $effort = "100";
                 * } elseif(intval($scorestr) < 0) {
                 * $effort = "0";
                 * } else {
                 * $effort = $scorestr;
                 * }
                 */
                if ($average_type == $AVG_TYPE_INDEX) {
                    $scorestr = safe($_POST["avg_{$row['Username']}"]);
                    $query = "SELECT NonmarkIndex FROM nonmark_index " .
                             "WHERE Input='$scorestr' " .
                             "AND   NonmarkTypeIndex=$average_type_index";
                    $nres = & $db->query($query);
                    if (DB::isError($nres))
                        die($nres->getDebugInfo());

                    if ($nrow = & $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
                        $average = "{$nrow['NonmarkIndex']}";
                    }
                }
            } else {
                $average = NULL;
            }

            $effort = "-1";
            if ($effort_type != $EFFORT_TYPE_NONE and
                 isset($_POST["effort_{$row['Username']}"])) {
                if ($effort_type == $EFFORT_TYPE_PERCENT) {
                    $scorestr = $_POST["effort_{$row['Username']}"];
                    if (strval(intval($scorestr)) != $scorestr) {
                        $effort = "-1";
                    } elseif (intval($scorestr) > 100) {
                        $effort = "100";
                    } elseif (intval($scorestr) < 0) {
                        $effort = "0";
                    } else {
                        $effort = $scorestr;
                    }
                } elseif ($effort_type == $EFFORT_TYPE_INDEX) {
                    $scorestr = safe($_POST["effort_{$row['Username']}"]);
                    $query = "SELECT NonmarkIndex FROM nonmark_index " .
                             "WHERE Input='$scorestr' " .
                             "AND   NonmarkTypeIndex=$effort_type_index";
                    $nres = & $db->query($query);
                    if (DB::isError($nres))
                        die($nres->getDebugInfo());

                    if ($nrow = & $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
                        $effort = "{$nrow['NonmarkIndex']}";
                    }
                }
            } else {
                $effort = NULL;
            }

            $conduct = "-1";
            if ($conduct_type != $CONDUCT_TYPE_NONE and
                 isset($_POST["conduct_{$row['Username']}"])) {
                if ($conduct_type == $CONDUCT_TYPE_PERCENT) {
                    $scorestr = $_POST["conduct_{$row['Username']}"];
                    if (strval(intval($scorestr)) != $scorestr) {
                        $conduct = "-1";
                    } elseif (intval($scorestr) > 100) {
                        $conduct = "100";
                    } elseif (intval($scorestr) < 0) {
                        $conduct = "0";
                    } else {
                        $conduct = $scorestr;
                    }
                } elseif ($conduct_type == $CONDUCT_TYPE_INDEX) {
                    $scorestr = safe($_POST["conduct_{$row['Username']}"]);
                    $query = "SELECT NonmarkIndex FROM nonmark_index " .
                             "WHERE Input='$scorestr' " .
                             "AND   NonmarkTypeIndex=$conduct_type_index";
                    $nres = & $db->query($query);
                    if (DB::isError($nres))
                        die($nres->getDebugInfo());

                    if ($nrow = & $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
                        $conduct = "{$nrow['NonmarkIndex']}";
                    }
                }
            } else {
                $conduct = NULL;
            }
        } else {
            $conduct = NULL;
            $effort = NULL;
        }

        $comment = "NULL";
        $cval = "NULL";
        if ($comment_type == $COMMENT_TYPE_MANDATORY or
             $comment_type == $COMMENT_TYPE_OPTIONAL) {
            if (isset($_POST["comment_{$row['Username']}"])) {
                $comment = trim(safe($_POST["comment_{$row['Username']}"]));
            } else {
                $comment = NULL;
            }
            if (isset($_POST["cval_{$row['Username']}"])) {
                $cval = $_POST["cval_{$row['Username']}"];
                if (strval(intval($cval)) != $cval)
                    $cval = "";
            } else {
                $comment = NULL;
            }
        }
        if ($comment == "") {
            $comment = "NULL";
        } else {
            $comment = "'$comment'";
        }
        if ($cval == "") {
            $cval = "NULL";
        }

        $query = "UPDATE subjectstudent SET ";
        if (! is_null($average)) {
            $query .= "       Average=$average, ";
        }
        if (! is_null($effort)) {
            $query .= "       Effort=$effort, ";
        }
        if (! is_null($conduct)) {
            $query .= "       Conduct=$conduct, ";
        }
        if (! is_null($comment)) {
            $query .= "       Comment=$comment, ";
        }
        if (! is_null($cval)) {
            $query .= "       CommentValue=$cval, ";
        }
        $query .= "       ReportDone=0 " .
                 "WHERE subjectstudent.Username = '{$row['Username']}' " .
                 "AND   subjectstudent.SubjectIndex = $subjectindex";
        $nres = & $db->query($query);
        if (DB::isError($nres))
            die($nres->getDebugInfo());

        if ($_POST['action'] == "Apply conduct to all my subjects" and
             in_array($username, $teachers)) {
            $query = "UPDATE subjectstudent, subjectteacher, subject, " .
                     "       subject AS currentsubject " .
                     "       SET subjectstudent.Conduct = $conduct " .
                     "WHERE subjectteacher.SubjectIndex = subjectstudent.SubjectIndex " .
                     "AND   subjectstudent.Username     = '{$row['Username']}' " .
                     "AND   subjectteacher.Username     = '$username' " .
                     "AND   subject.SubjectIndex        = subjectteacher.SubjectIndex " .
                     "AND   currentsubject.SubjectIndex = $subjectindex " .
                     "AND   subject.YearIndex           = currentsubject.YearIndex " .
                     "AND   subject.TermIndex           = currentsubject.TermIndex";
            $nres = & $db->query($query);
            if (DB::isError($nres))
                die($nres->getDebugInfo());
        }
    }
    if ($_POST['action'] == "Update" or
         $_POST['action'] == "Apply conduct to all my subjects") {
        echo "done.</p>\n";
        echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n"; // Link to next page
        $do_include = false;
    } else {
        $include = "teacher/report/close_confirm.php";
    }
} elseif ($_POST['action'] == "Cancel") {
    $extraMeta = "      <meta http-equiv='REFRESH' content='0;url=$nextLink'>\n";
    $noJS = true;
    $noHeaderLinks = true;
    $title = "LESSON - Cancelling...";

    include "header.php";

    echo "      <p align='center'>Cancelling and redirecting you to <a href='$nextLink'>$nextLink</a>." .
         "</p>\n";

    include "footer.php";
    $do_include = false;
}

if ($do_include)
    include $include;
?>
