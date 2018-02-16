<?php
/**
 * ***************************************************************
 * teacher/assignment/modify_action.php (c) 2004-2007, 2016-2018 Jonathan Dieter
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
    $subject_index = dbfuncInt2String($_GET['key2']);
    $is_teacher = check_teacher_subject($username, $subject_index);

    $query = $pdb->prepare(
        "SELECT subject.SubjectIndex, subject.AverageType, subject.AverageTypeIndex, " .
        "       subject.Name " .
        "       FROM subject " .
        "WHERE subject.SubjectIndex = :subject_index"
    );
    $query->execute(['subject_index' => $subject_index]);
    $new = true;
} else {
    $assignment_index = dbfuncInt2String($_GET['key']);
    $is_teacher = check_teacher_assignment($username, $assignment_index);

    $query = $pdb->prepare(
        "SELECT subject.SubjectIndex, subject.AverageType, subject.AverageTypeIndex, " .
        "       subject.Name " .
        "       FROM subject INNER JOIN assignment USING (SubjectIndex) " .
        "WHERE assignment.AssignmentIndex = :assignment_index"
    );
    $query->execute(['assignment_index' => $assignment_index]);
    $new = false;
}

$subject = $query->fetch();
if(!$subject) {
    include "header.php";

    if($new) {
        echo "      <p>The subject you're trying to create an assignment for doesn't exist</p>\n";
    } else {
        echo "      <p>The assignment you're trying to modify doesn't exist</p>\n";
    }
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    include "footer.php";
    exit(0);
}

if (!$is_teacher and !$is_admin) {
    log_event($LOG_LEVEL_ERROR, "teacher/assignment/modify_action.php", $LOG_DENIED_ACCESS,
            "Tried to modify assignment for {$subject['Name']}.");

    /* Print error message */
    include "header.php";

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    include "footer.php";
    exit(0);
}

$subject_index = $subject['SubjectIndex'];
$subject_name = $subject['Name'];
$average_type = $subject['AverageType'];
$average_type_index = $subject['AverageTypeIndex'];
if(!is_null($average_type))
    $average_type = intval($average_type);
if(!is_null($average_type_index))
    $average_type_index = intval($average_type_index);

/* Check whether or not a title was included and set title to "No title" if it wasn't included */
if (! isset($_POST['title']) or $_POST['title'] == "") {
    echo "</p>\n      <p>Title not entered, setting to 'No title'.</p>\n      <p>"; // Print error message
    $_POST['title'] = "No title";
}

/* Check whether or not a description was included and set it properly if it was */
if ($_POST['descr_type'] == '0') {
    if ($_POST['descr'] == "") {
        $descr = null;
    } else {
        $descr = htmlize_comment($_POST['descr']);
    }
    $descr_id = null;
    $descr_file_type = null;
} else {
    if (! isset($_FILES['descr_upload']) or $_FILES['descr_upload']['error'] != UPLOAD_ERR_OK) {
        $descr_id = null;
        $descr_file_type = null;

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
            print "</p><p align='center' class='error'>$error.  Description will be blank.</p><p align='center'>\n";
        }
    } else {
        $descr_file_type = $_FILES['descr_upload']['type'];
        if ($descr_file_type != "application/pdf") {
            print "</p><p align='center' class='error'>Uploaded file is not a PDF document.  Description will be blank.</p><p align='center'>\n";
            $descr_file_type = null;
            $descr_id = null;
        } else {
            $descr_file = $_FILES['descr_upload']['tmp_name'];
            $descr_id = get_id_from_upload($_FILES['descr_upload']);
        }
    }
    $descr = null;
}

/* Check whether or not the date was set, and set it to today if it wasn't */
if (! isset($_POST['date']) or $_POST['date'] == "") { // Make sure date is in correct format.
    echo "</p>\n      <p align='center'>Date not entered, defaulting to today.</p>\n      <p align='center'>"; // Print error message
    $_POST['date'] = & dbfuncCreateDate(date($dateformat));
} else {
    $_POST['date'] = dbfuncCreateDate($_POST['date']);
}

/* Check whether or not the due date was set, and set it to NULL if it wasn't */
if (! isset($_POST['duedate']) or $_POST['duedate'] == "") { // Make sure date is in correct format.
    if ($agenda == "1" or $_POST['action'] == "Convert to agenda item") {
        print
        "</p><p align='center' class='error'>Due date not entered in agenda item, defaulting to tomorrow.</p><p align='center'>\n";
        $_POST['duedate'] = "DATE(DATE_ADD(NOW(), INTERVAL 1 DAY))";
    } else {
        $_POST['duedate'] = null;
    }
} else {
    $_POST['duedate'] = dbfuncCreateDate($_POST['duedate']);
}

if ($_POST['makeuptype'] == "NULL") {
    $_POST['makeuptype'] = null;
} else {
    $_POST['makeuptype'] = intval($_POST['makeuptype']);
}

/* Check whether this assignment should be hidden from students */
if (isset($_POST['hidden']) and $_POST['hidden'] == "on") {
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
    $upload_name = str_replace($remove_array, "", $_POST["title"]);
} else {
    $_POST['uploadable'] = "0";
    $upload_name = null;
}

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
                $_POST['top_mark'] = null;
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
                $_POST['bottom_mark'] = null;
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
            $_POST['category'] = null;
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
    $next_subjectindex = $_POST['next_subject'];
    $query = $pdb->prepare(
        "UPDATE assignment SET SubjectIndex = :next_subjectindex " .
        "WHERE AssignmentIndex = :assignment_index"
    )->execute(['next_subjectindex' => $next_subjectindex,
                     'assignment_index' => $assignment_index]);

    update_subject($subject_index);

    $subject_index = $next_subjectindex;
}

if (($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) and
     (! isset($_POST['category']) or is_null($_POST['category']))) {
    $query = $pdb->prepare(
        "SELECT categorylist.CategoryListIndex FROM category, " .
        "       categorylist " .
        "WHERE categorylist.SubjectIndex = :subject_index " .
        "AND   category.CategoryIndex = categorylist.CategoryIndex " .
        "ORDER BY category.CategoryName"
    );
    $query->execute(['subject_index' => $subject_index]);
    $row = $query->fetch();
    if ($row) {
        $_POST['category'] = $row['CategoryListIndex'];
    } else {
        $_POST['category'] = null;
    }
}

/* Set assignment information */
if($new) {
    $query =
        "INSERT INTO assignment (Title, Description, DescriptionFileIndex, " .
        "                        DescriptionFileType, Date, DueDate, " .
        "                        Hidden, Agenda,  ";
    if (($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) and $agenda == "0") {
        $query .=   "                        Max, CategoryListIndex, CurveType, " .
                    "                        TopMark, BottomMark, Weight, " .
                    "                        IgnoreZero, MakeupTypeIndex, ";
    }
    $query .=
        "                        Uploadable, SubjectIndex) " .
        "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ";
    if (($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) and $agenda == "0") {
        $query .=
            "        ?, ?, ?, ?, ?, ?, ?, ?, ";
    }
    $query .=
        "        ?, ?)";

} else {
    $query =        "UPDATE assignment SET Title = ?, Description = ?, " .
                    "       DescriptionFileIndex = ?, DescriptionFileType = ?, " .
                    "       Date = ?, DueDate = ?, Hidden = ?, Agenda = ?, ";
    if (($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) and $agenda == "0") {
        $query .=   "       Max = ?, CategoryListIndex = ?, CurveType = ?, " .
                    "       TopMark = ?, BottomMark = ?, Weight = ?, " .
                    "       IgnoreZero = ?, MakeupTypeIndex = ?, ";
    }
    $query .=       "       Uploadable = ? " .
                    "WHERE AssignmentIndex = ?";
}
$exec_array = [
    $_POST['title'], $descr, $descr_id, $descr_file_type, $_POST['date'],
    $_POST['duedate'], $_POST['hidden'], $agenda
];
if (($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) and $agenda == "0") {
    $exec_array = array_merge($exec_array, [
        $_POST['max'], $_POST['category'], $_POST['curve_type'],
        $_POST['top_mark'], $_POST['bottom_mark'], $_POST['weight'],
        $_POST['ignore_zero'], $_POST['makeuptype']
    ]);
}
$exec_array = array_merge($exec_array, [
    $_POST['uploadable']
]);
if($new) {
    $exec_array = array_merge($exec_array, [
        $subject_index
    ]);
} else {
    $exec_array = array_merge($exec_array, [
        $assignment_index
    ]);
}
$pdb->prepare($query)->execute($exec_array);

if($new) {
    $assignment_index = $pdb->lastInsertId('AssignmentIndex');

    if (!$assignment_index) {
        echo "Error creating new assignment</p>\n";
        include "footer.php";
        exit(0);
    }
}

if ($_POST['uploadable'] == 1) {
    $upload_name = "$upload_name ($assignment_index)";
    if(!$new) {
        $query = $pdb->prepare(
            "SELECT UploadName, Uploadable FROM assignment " .
            "WHERE AssignmentIndex = :assignment_index"
        );
        $query->execute(['assignment_index' => $assignment_index]);
        $row = $query->fetch();
        if ($row) {
            if ($row['Uploadable'] != 0 and $row['UploadName'] != $upload_name) {
                dbfuncMoveDir($assignment_index, $row['UploadName'], $upload_name);
            }
        }
    }
}

$pdb->prepare(
    "UPDATE assignment SET UploadName=:upload_name " .
    "WHERE AssignmentIndex = :assignment_index"
)->execute(['upload_name' => $upload_name,
            'assignment_index' => $assignment_index]);

if ($_POST['action'] == "Convert to agenda item") {
    $pdb->prepare(
        "UPDATE assignment SET Agenda=1 " .
        "WHERE AssignmentIndex = :assignment_index"
    )->execute(['assignment_index' => $assignment_index]);
}

if ($_POST['action'] == "Convert to assignment" and $average_type != $AVG_TYPE_NONE) {
    $pdb->prepare(
        "UPDATE assignment SET Agenda=0 " .
        "WHERE AssignmentIndex = :assignment_index"
    )->execute(['assignment_index' => $assignment_index]);
}

if ($agenda == "0") {
    $query = $pdb->prepare(
        "SELECT subjectstudent.Username FROM " .
        "       subjectstudent LEFT OUTER JOIN mark ON (mark.AssignmentIndex = :assignment_index " .
        "       AND mark.Username = subjectstudent.Username), assignment " .
        "WHERE assignment.AssignmentIndex = :assignment_index " .
        "AND   subjectstudent.SubjectIndex = assignment.SubjectIndex " .
        "ORDER BY subjectstudent.Username"
    );
    $query->execute(['assignment_index' => $assignment_index]);

    /* For each student, check whether there's already a mark, then either insert or update mark as needed */
    foreach($query as $row) {
        // If comment isn't set, we may be accidentally overwriting marks
        if(!array_key_exists("comment_{$row['Username']}", $_POST)) {
            continue;
        }

        $comment = $_POST["comment_{$row['Username']}"]; // Get comment for username from POST data

        $has_makeup = false;
        $makeup_score = null;
        $score = null;
        if ($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) {
            if (is_null($_POST['makeuptype'])) {
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
                    $score = null;
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
                            $score = null;
                        }
                        settype($score, "string");
                    }
                }
                if($item != "score")
                    $makeup_score = $score;
            }
        } elseif ($average_type == $AVG_TYPE_INDEX) {
            $inval = $_POST["score_{$row['Username']}"];
            $inval = strtoupper($inval);
            $score = get_nonmark_index($inval, $average_type_index);

            if (!$score) {
                if (isset($inval) and $inval != "") {
                    echo "</p>\n      <p>Mark for {$row['Username']} is invalid...clearing. " .
                         "</p>\n      <p>";
                }
                $score = null;
            }
        } else {
            $score = null;
        }
        if ($comment == '' or ! isset($_POST["comment_{$row['Username']}"])) { // If comment is blank, set to NULL
            $comment = null;
        } else {
            $comment = htmlize_comment($comment);
        }

        $squery = $pdb->prepare(
            "SELECT mark.MarkIndex FROM assignment, mark " .
            "WHERE assignment.AssignmentIndex = :assignment_index " .
            "AND   mark.AssignmentIndex       = assignment.AssignmentIndex " .
            "AND   mark.Username              = :username"
        );
        $squery->execute(['assignment_index' => $assignment_index, 'username' => $row['Username']]);
        $sRow = $squery->fetch();
        if($sRow) {
            if(is_null($score) and is_null($comment) and is_null($makeup_score)) {
                $pdb->prepare(
                    "DELETE FROM mark WHERE mark.MarkIndex = :mark_index"
                )->execute(['mark_index' => $sRow['MarkIndex']]);
            } else {
                $pdb->prepare(
                    "UPDATE mark SET Score = :score, MakeupScore = :makeup_score, " .
                    "                Comment = :comment " .
                    "WHERE mark.MarkIndex  = :mark_index "
                )->execute(['score' => $score, 'makeup_score' => $makeup_score,
                            'comment' => $comment,
                            'mark_index' => $sRow['MarkIndex']]);
            }
        } else {
            if(!is_null($score) or !is_null($comment)) {
                $pdb->prepare(
                    "INSERT INTO mark (Username, AssignmentIndex, " .
                    "Score, MakeupScore, Comment) VALUES (:username, " .
                    ":assignment_index, :score, :makeup_score, :comment)"
                )->execute(['username' => $row['Username'],
                            'assignment_index' => $assignment_index,
                            'score' => $score, 'makeup_score' => $makeup_score,
                            'comment' => $comment]);
            }
        }
    }
}
if ($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) {
    update_marks($assignment_index);
}

log_event($LOG_LEVEL_TEACHER, "teacher/assignment/modify_action.php",
        $LOG_TEACHER, "Modified assignment ($title) for {$subject_name}.");

if (isset($error) and $error) {
    echo "failed!</p>\n";
} else {
    echo "done.</p>\n";
}

echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n";

include "footer.php";
