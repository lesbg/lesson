<?php
/**
 * ***************************************************************
 * teacher/assignment/modify_action.php (c) 2004-2007, 2016-2017 Jonathan Dieter
 *
 * Sanitize assignment and run query to change grades
 * ***************************************************************
 */

$nextLink = dbfuncInt2String($_GET['next']); // Link to next page
include "core/settermandyear.php";

/* If delete was pressed, confirm deletion */
if ($_POST["action"] == 'Delete') {
    include "teacher/assignment/delete_confirm.php";
    exit(0);
}

/* Check if any undefined buttons (including Cancel) were pressed */
if ($_POST["action"] != "Save" and $_POST["action"] != "Update" and
     $_POST["action"] != "Move this assignment to next term" and
     $_POST["action"] != "Convert to agenda item" and
     $_POST["action"] != "Convert to assignment") {
    redirect($nextLink);
    exit(0);
}

$title = "LESSON - Saving changes..."; // common info and go to the appropriate page.
$noHeaderLinks = true;
$noJS = true;

include "header.php"; // Print header

echo "      <p align='center'>Saving changes...";

if (! isset($_POST['agenda']) or $_POST['agenda'] == 0) {
    $agenda = "0";
} else {
    $agenda = "1";
}

if ($_POST["action"] == "Save") {
    $subjectindex = intval(dbfuncInt2String($_GET['key2']));
    $query = "SELECT subject.AverageType, subject.AverageTypeIndex " .
             "       FROM subject " .
             "WHERE subject.SubjectIndex = $subjectindex";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());
    $row = & $res->fetchRow(DB_FETCHMODE_ASSOC);

    $average_type = $row['AverageType'];
    $average_type_index = $row['AverageTypeIndex'];
    $new = true;
} else {
    $assignment_index = intval(dbfuncInt2String($_GET['key']));

    $query = "SELECT subject.SubjectIndex, subject.AverageType, subject.AverageTypeIndex " .
             "       FROM subject INNER JOIN assignment USING (SubjectIndex) " .
             "WHERE assignment.AssignmentIndex = $assignment_index";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());
    $row = & $res->fetchRow(DB_FETCHMODE_ASSOC);

    $subjectindex = $row['SubjectIndex'];
    $average_type = $row['AverageType'];
    $average_type_index = $row['AverageTypeIndex'];
    $new = false;
}

/* Check whether user is authorized to change scores */
if($new) {
    $query =    "SELECT subjectteacher.Username FROM subjectteacher " .
                "WHERE subjectteacher.SubjectIndex = $subjectindex " .
                "AND   subjectteacher.Username     = '$username' ";
} else {
    $query =    "SELECT subjectteacher.Username FROM subjectteacher, assignment " .
                "WHERE subjectteacher.SubjectIndex = assignment.SubjectIndex " .
                "AND   assignment.AssignmentIndex = $assignment_index " .
                "AND   subjectteacher.Username     = '$username' ";
}
$res = & $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo());

/* Check whether user is authorized to change scores */
if ($res->numRows() == 0 and !$is_admin) {
    if($new) {
        $query =    "SELECT subject.Name FROM subject " .
                    "WHERE subject.SubjectIndex        = $subjectindex";
    } else {
        $query =    "SELECT subject.Name FROM assignment, subject " .
                    "WHERE assignment.AssignmentIndex  = $assignment_index " .
                    "AND   subject.SubjectIndex        = assignment.SubjectIndex";
    }
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());
    $row = & $res->fetchRow(DB_FETCHMODE_ASSOC);
    log_event($LOG_LEVEL_ERROR, "teacher/assignment/modify_action.php",
              $LOG_DENIED_ACCESS, "Tried to modify marks for an assignment in {$row['Name']}.");

    /* Print error message */
    include "header.php";

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    include "footer.php";
    exit(0);
}

/* Check whether or not a title was included and set title to "No title" if it wasn't included */
if (! isset($_POST['title']) or $_POST['title'] == "") {
    echo "</p>\n      <p>Title not entered, setting to 'No title'.</p>\n      <p>"; // Print error message
    $_POST['title'] = "No title";
}

/* Check whether or not a description was included and set it properly if it was */
if ($_POST['descr_type'] == '0') {
    if ($_POST['descr'] == "") {
        $descr = "NULL";
    } else {
        $descr = safe(htmlize_comment($_POST['descr']));
        $descr = "'$descr'";
    }
    $descr_id = "NULL";
    $descr_file_type = "NULL";
} else {
    if (! isset($_FILES['descr_upload']) or
         $_FILES['descr_upload']['error'] != UPLOAD_ERR_OK) {
        $descr_id = "NULL";
        $descr_file_type = "NULL";

        if (! isset($_FILES['descr_upload']['error'])) {
            $error = "Error when attempting to upload file";
        } elseif ($_FILES['descr_upload']['error'] == UPLOAD_ERR_INI_SIZE or
                 $_FILES['descr_upload']['error'] == UPLOAD_ERR_FORM_SIZE) {
            $error = "You have attempted to upload a file that is too large";
        } elseif ($_FILES['descr_upload']['error'] == UPLOAD_ERR_PARTIAL) {
            $error = "Only part of the file was uploaded";
        } elseif ($_FILES['descr_upload']['error'] == UPLOAD_ERR_NO_FILE) {
            // $error = "You must choose a file to be uploaded";
            $descr_id = "DescriptionFileIndex";
            $descr_file_type = "DescriptionFileType";
            $error = false;
        } else {
            $error = "Error when attempting to upload file";
        }
        if ($error) {
            print
            "</p><p align='center' class='error'>$error.  Description will be blank.</p><p align='center'>\n";
        }
    } else {
        $descr_file_type = safe($_FILES['descr_upload']['type']);
        if ($descr_file_type != "application/pdf") {
            print
            "</p><p align='center' class='error'>Uploaded file is not a PDF document.  Description will be blank.</p><p align='center'>\n";
            $descr_file_type = "NULL";
            $descr_id = "NULL";
        } else {
            $descr_file = $_FILES['descr_upload']['tmp_name'];

            $descr_id = "'" . get_id_from_upload($_FILES['descr_upload']) . "'";
            $descr_file_type = "'$descr_file_type'";
        }
    }
    $descr = "NULL";
}

/* Check whether or not the date was set, and set it to today if it wasn't */
if (! isset($_POST['date']) or $_POST['date'] == "") { // Make sure date is in correct format.
    echo "</p>\n      <p align='center'>Date not entered, defaulting to today.</p>\n      <p align='center'>"; // Print error message
    $_POST['date'] = & dbfuncCreateDate(date($dateformat));
} else {
    $_POST['date'] = & dbfuncCreateDate($_POST['date']);
}
$_POST['date'] = "'" . $_POST['date'] . "'";

/* Check whether or not the due date was set, and set it to NULL if it wasn't */
if (! isset($_POST['duedate']) or $_POST['duedate'] == "") { // Make sure date is in correct format.
    if ($agenda == "1" or $_POST['action'] == "Convert to agenda item") {
        print
        "</p><p align='center' class='error'>Due date not entered in agenda item, defaulting to tomorrow.</p><p align='center'>\n";
        $_POST['duedate'] = "DATE(DATE_ADD(NOW(), INTERVAL 1 DAY))";
    } else {
        $_POST['duedate'] = "NULL";
    }
} else {
    $_POST['duedate'] = & dbfuncCreateDate(safe($_POST['duedate']));
    $_POST['duedate'] = "'" . $_POST['duedate'] . "'";
}

if ($_POST['makeuptype'] != "NULL") {
    $_POST['makeuptype'] = intval($_POST['makeuptype']);
}

/* Check whether this assignment should be hidden from students */
if ($_POST['hidden'] == "on") {
    $_POST['hidden'] = "1";
} else {
    $_POST['hidden'] = "0";
}

/* Check whether this assignment is uploadable */
if ($_POST['uploadable'] == "on") {
    $_POST['uploadable'] = "1";
    /* Set assignment's directory */
    $remove_array = array(
            "!",
            "#",
            ":",
            "/",
            "\\",
            "\"",
            "'",
            "<",
            ">",
            "?",
            "*",
            "|",
            "&",
            "@",
            "`"
    );
    $upload_name = str_replace($remove_array, "", safe($_POST["title"]));
} else {
    $_POST['uploadable'] = "0";
    $upload_name = "NULL";
}

$title = safe($_POST['title']);

/* Check whether maximum score was included, and set to 0 if it wasn't */
if ($agenda == "0") {
    if ($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) {
        if (! isset($_POST['max']) or $_POST['max'] == "") {
            echo "</p>\n      <p>Maximum score not entered, defaulting to 0.</p>\n      <p>"; // Print error message
            $_POST['max'] = "0";
        } else {
            if ($_POST['max'] != "0") {
                settype($_POST['max'], "double");
                if ($_POST['max'] <= 0)
                    echo "</p>\n      <p>Maximum score must be a number greater than or equal to 0...defaulting to 0.</p>\n      <p>";
                settype($_POST['max'], "string");
            }
        }

        /* Check whether top mark was included, and set to NULL if it wasn't */
        if (! isset($_POST['top_mark']) or $_POST['top_mark'] == "") {
            if ($_POST['curve_type'] == 2) {
                echo "</p>\n      <p>Top mark must be a number between 0 and 100...setting to 100.</p>\n      <p>";
                $_POST['top_mark'] = "100";
            } else {
                $_POST['top_mark'] = "NULL";
            }
        } else {
            if ($_POST['top_mark'] != "0") {
                settype($_POST['top_mark'], "double");
                if ($_POST['top_mark'] <= 0) {
                    echo "</p>\n      <p>Top mark must be a number between 0 and 100...setting to 0.</p>\n      <p>";
                    $_POST['top_mark'] = "0";
                } elseif ($_POST['top_mark'] > 100) {
                    echo "</p>\n      <p>Top mark must be a number between 0 and 100...setting to 100.</p>\n      <p>";
                    $_POST['top_mark'] = "100";
                }
                settype($_POST['top_mark'], "string");
            }
        }

        /* Check whether bottom mark was included, and set to NULL if it wasn't */
        if (! isset($_POST['bottom_mark']) or $_POST['bottom_mark'] == "") {
            if ($_POST['curve_type'] == 2) {
                echo "</p>\n      <p>Bottom mark must be a number between 0 and 100...setting to 0.</p>\n      <p>";
                $_POST['bottom_mark'] = "0";
            } else {
                $_POST['bottom_mark'] = "NULL";
            }
        } else {
            if ($_POST['bottom_mark'] != "0") {
                settype($_POST['bottom_mark'], "double");
                if ($_POST['bottom_mark'] <= 0) {
                    echo "</p>\n      <p>Bottom mark must be a number between 0 and 100...setting to 0.</p>\n      <p>";
                    $_POST['bottom_mark'] = "0";
                } elseif ($_POST['top_mark'] > 100) {
                    echo "</p>\n      <p>Bottom mark must be a number between 0 and 100...setting to 100.</p>\n      <p>";
                    $_POST['bottom_mark'] = "100";
                }
                settype($_POST['bottom_mark'], "string");
            }
        }

        /* Validate ignore_zero */
        if (! isset($_POST['ignore_zero']) or $_POST['ignore_zero'] != "1") {
            $_POST['ignore_zero'] = "0";
        }

        /* Check category */
        if (! isset($_POST['category']) or $_POST['category'] == "") {
            $_POST['category'] = "NULL"; /* Check whether user is authorized to change scores */
        } else {
            settype($_POST['category'], "double");
            settype($_POST['category'], "string");
        }

        /* Check whether weight was included, and set to 0 if it wasn't */
        if (! isset($_POST['weight']) or $_POST['weight'] == "") {
            echo "</p>\n      <p>Weight not entered, defaulting to 1.</p>\n      <p>"; // Print error message
            $_POST['weight'] = "1";
        } else {
            if ($_POST['weight'] != "0") {
                settype($_POST['weight'], "double");
                if ($_POST['weight'] == 0) {
                    echo "</p>\n      <p>Weight must be a number...defaulting to 1.</p>\n      <p>";
                    $_POST['weight'] = 1;
                }
                settype($_POST['weight'], "string");
            }
        }
    }
}

if ($_POST['action'] == "Move this assignment to next term") {
    $next_subjectindex = intval($_POST['next_subject']);
    $query = "UPDATE assignment SET SubjectIndex=$next_subjectindex " .
             "WHERE AssignmentIndex = $assignment_index";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    update_subject($subjectindex);

    $subjectindex = $next_subjectindex;
}

if (($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) and
     (! isset($_POST['category']) or $_POST['category'] == "NULL")) {
    $query =    "SELECT categorylist.CategoryListIndex FROM category, " .
                "       categorylist " .
                "WHERE categorylist.SubjectIndex = $subjectindex " .
                "AND   category.CategoryIndex = categorylist.CategoryIndex " .
                "ORDER BY category.CategoryName";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());
    if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $_POST['category'] = $row['CategoryListIndex'];
    } else {
        $_POST['category'] = "NULL";
    }
}

if ($_POST['uploadable'] == 1 and !$new) {
    $upload_name = "$upload_name ($assignment_index)";
    $query =    "SELECT UploadName, Uploadable FROM assignment WHERE AssignmentIndex = $assignment_index";
    $res = & $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());
    if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        if ($row['Uploadable'] != 0) {
            if ($row['UploadName'] != $upload_name) {
                dbfuncMoveDir($assignment_index, $row['UploadName'],
                            $upload_name);
            }
        }
    }
    $upload_name = "'$upload_name'";
}

/* Set assignment information */
if($new) {
    $query =        "INSERT INTO assignment (Title, Description, DescriptionFileIndex, " .
                    "                        DescriptionFileType, Date, DueDate, ";
    if (($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) and $agenda == "0") {
        $query .=   "                        Max, CategoryListIndex, CurveType, " .
                    "                        TopMark, BottomMark, Weight, " .
                    "                        IgnoreZero, MakeupTypeIndex, ";
    }
    $query .=       "                        Hidden, Agenda, " .
                    "                        SubjectIndex, Uploadable) " .
                    "VALUES ('$title' , $descr, $descr_id, $descr_file_type, " .
                    "        {$_POST['date']}, {$_POST['duedate']}, ";
    if (($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) and $agenda == "0") {
        $query .=   "        {$_POST['max']}, {$_POST['category']}, {$_POST['curve_type']}, " .
                    "        {$_POST['top_mark']}, {$_POST['bottom_mark']}, {$_POST['weight']}, " .
                    "        {$_POST['ignore_zero']}, {$_POST['makeuptype']}, ";
    }
    $query .=       "        {$_POST['hidden']}, $agenda, $subjectindex, " .
                    "        {$_POST['uploadable']})";
} else {
    $query =        "UPDATE assignment SET Title = '$title', Description = $descr, " .
                    "       DescriptionFileIndex = $descr_id, DescriptionFileType = $descr_file_type, " .
                    "       Date = {$_POST['date']}, DueDate = {$_POST['duedate']}, " .
                    "       UploadName = {$upload_name}, Uploadable = {$_POST['uploadable']}, ";
    if (($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) and $agenda == "0") {
        $query .=   "       CurveType = {$_POST['curve_type']}, TopMark = {$_POST['top_mark']}, " .
                    "       BottomMark = {$_POST['bottom_mark']}, Weight = {$_POST['weight']}, " .
                    "       CategoryListIndex = {$_POST['category']}, Max = {$_POST['max']}, " .
                    "       IgnoreZero = {$_POST['ignore_zero']}, MakeupTypeIndex = {$_POST['makeuptype']}, ";
    }
    $query .=       "       Hidden = {$_POST['hidden']}, Agenda = $agenda " .
                    "WHERE AssignmentIndex = $assignment_index";
}
$aRes = & $db->query($query);
if (DB::isError($aRes))
    die($aRes->getDebugInfo());

/* If new assignment, get assignment index */
if($new) {
    $aRes = & $db->query("SELECT LAST_INSERT_ID() AS AssignmentIndex");
    if (DB::isError($aRes))
        die($aRes->getDebugInfo());

    if ($aRow = & $aRes->fetchRow(DB_FETCHMODE_ASSOC) and $aRow['AssignmentIndex'] != 0) {
        $assignment_index = $aRow['AssignmentIndex'];
    } else {
        echo "Error creating new assignment</p>\n";
        include "footer.php";
        die();
    }
}

if ($_POST['action'] == "Convert to agenda item") {
    $query =    "UPDATE assignment SET Agenda=1 " .
                "WHERE AssignmentIndex = $assignment_index";
    $nres = &  $db->query($query);
    if (DB::isError($nres))
        die($nres->getDebugInfo());
}

if ($_POST['action'] == "Convert to assignment" and $average_type != $AVG_TYPE_NONE) {
    $query =    "UPDATE assignment SET Agenda=0 " .
                "WHERE AssignmentIndex = $assignment_index";
    $nres = &  $db->query($query);
    if (DB::isError($nres))
        die($nres->getDebugInfo());
}

if ($agenda == "0") {
    $query =    "SELECT subjectstudent.Username FROM " .
                "       subjectstudent LEFT OUTER JOIN mark ON (mark.AssignmentIndex = $assignment_index " .
                "       AND mark.Username = subjectstudent.Username), assignment " .
                "WHERE assignment.AssignmentIndex = $assignment_index " .
                "AND   subjectstudent.SubjectIndex = assignment.SubjectIndex " .
                "ORDER BY subjectstudent.Username";
    $res = & $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    /* For each student, check whether there's already a mark, then either insert or update mark as needed */
    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        // If comment isn't set, we may be accidentally overwriting marks
        if(!array_key_exists("comment_{$row['Username']}", $_POST)) {
            continue;
        }

        if ($average_type != $AVG_TYPE_NONE) {
            $score = $_POST["score_{$row['Username']}"]; // Get score for username from POST data
        } else {
            $score = "NULL";
        }

        $comment = $_POST["comment_{$row['Username']}"]; // Get comment for username from POST data

        $has_makeup = false;
        $makeup_score = "NULL";
        $score = "NULL";
        if ($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) {
            if ($_POST['makeuptype'] == "NULL") {
                $items = array('score');
            } else {
                $items = array('makeup_score', 'score');
                $has_makeup = true;
            }
            foreach($items as $item) {
                $score = $_POST["{$item}_{$row['Username']}"];
                if (strtoupper($score) == 'A') {
                    $score = "$MARK_ABSENT";
                } elseif (strtoupper($score) == 'E') {
                    $score = "$MARK_EXEMPT";
                } elseif (strtoupper($score) == 'L') {
                    $score = "$MARK_LATE";
                } elseif ($score == '' or !isset($_POST["{$item}_{$row['Username']}"])) {
                    $score = "NULL";
                } else {
                    if ($score != "0") {
                        $max = $_POST['max'];
                        settype($max, "double");
                        settype($score, "double");
                        if($item == "score")
                            $type = "Score";
                        else
                            $type = "Makeup score";
                        if ($score < 0) {
                            echo "</p>\n      <p>$type for {$row['Username']} must be at least 0...setting to 0.</p>\n      <p>";
                            $score = 0;
                        }
                        if ($score > $max) {
                            echo "</p>\n      <p>Warning!  $type for {$row['Username']} is greater than $max.</p>\n      <p>";
                        }
                        if ($score == 0) {
                            echo "</p>\n      <p>$type for {$row['Username']} must be a number, A (for absent), E (for exempt), or L (for late)... clearing. " .
                                 "</p>\n      <p>";
                            $score = "NULL";
                        }
                        settype($score, "string");
                    }
                }
                if($item != "score")
                    $makeup_score = $score;
            }
        } elseif ($average_type == $AVG_TYPE_INDEX) {
            $inval = safe($_POST["score_{$row['Username']}"]);
            $inval = strtoupper($inval);
            $nquery = "SELECT NonmarkIndex FROM nonmark_index WHERE NonmarkTypeIndex=$average_type_index AND Input = '$inval'";
            $sRes = & $db->query($nquery);
            if (DB::isError($sRes))
                die($sRes->getDebugInfo());

            if ($sRow = & $sRes->fetchRow(DB_FETCHMODE_ASSOC)) {
                $score = $sRow['NonmarkIndex'];
            } else {
                if (isset($inval) and $inval != "") {
                    echo "</p>\n      <p>Mark for {$row['Username']} is invalid...clearing. " .
                         "</p>\n      <p>";
                }
                $score = "NULL";
            }
        } else {
            $score = "NULL";
        }
        if ($comment == '' or ! isset($_POST["comment_{$row['Username']}"])) { // If comment is blank, set to NULL
            $comment = "NULL";
        } else {
            $comment = safe(htmlize_comment($comment));
            $comment = "'$comment'"; // If comment is not blank, put quotes around it
        }

        $query =    "SELECT mark.MarkIndex FROM assignment, mark " .
                    "WHERE assignment.AssignmentIndex = $assignment_index " .
                    "AND   mark.AssignmentIndex       = assignment.AssignmentIndex " .
                    "AND   mark.Username              = '{$row['Username']}'";
        $sRes = & $db->query($query);
        if (DB::isError($sRes))
            die($sRes->getDebugInfo());

        if ($sRow = & $sRes->fetchRow(DB_FETCHMODE_ASSOC)) {
            if($score == "NULL" and $comment == "NULL" and $makeup_score == "NULL") {
                $query = "DELETE FROM mark WHERE mark.MarkIndex = {$sRow['MarkIndex']}";
                $update = & $db->query($query);
                if (DB::isError($update)) {
                    echo "</p>\n      <p>Update: " . $update->getMessage() .
                         "</p>\n      <p>";
                    $error = true;
                }
            } else {
                $query =    "UPDATE mark SET Score = $score, MakeupScore = $makeup_score, Comment = $comment " .
                            "WHERE mark.MarkIndex  = {$sRow['MarkIndex']} ";
                $update = & $db->query($query);
                if (DB::isError($update)) {
                    echo "</p>\n      <p>Update: " . $update->getMessage() .
                         "</p>\n      <p>";
                    $error = true;
                }
            }
        } else {
            if($score != "NULL" or $comment != "NULL") {
                $query =    "INSERT INTO mark (MarkIndex, Username, AssignmentIndex, " .
                            "Score, MakeupScore, Comment) VALUES ('', '{$row['Username']}', " .
                            "$assignment_index, $score, $makeup_score, $comment)";
                $update = & $db->query($query);
                if (DB::isError($update)) {
                    echo "</p>\n      <p>Insert: " . $update->getDebugInfo() .
                         "</p>\n      <p>";
                    $error = true;
                }
            }
        }
    }
}
if ($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) {
    update_marks($assignment_index);
}

$query =    "SELECT subject.Name FROM assignment, subject " .
            "WHERE assignment.AssignmentIndex  = $assignment_index " .
            "AND   subject.SubjectIndex        = assignment.SubjectIndex";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo());
$row = & $res->fetchRow(DB_FETCHMODE_ASSOC);
log_event($LOG_LEVEL_TEACHER, "teacher/assignment/modify_action.php",
        $LOG_TEACHER, "Modified assignment ($title) for {$row['Name']}.");

if ($error) {
    echo "failed!</p>\n";
} else {
    echo "done.</p>\n";
}

echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n";

include "footer.php";
