<?php
/**
 * ***************************************************************
 * teacher/assignment/new_action.php (c) 2004-2010 Jonathan Dieter
 *
 * Run query to insert a new assignment or agenda item into the database.
 * ***************************************************************
 */

/* Get variables */
include "core/settermandyear.php";

$subjectindex = safe(dbfuncInt2String($_GET['key']));

$error = false; // Boolean to store any errors

/* Check whether user is authorized to change scores */
$res = & $db->query(
                "SELECT subjectteacher.Username FROM subjectteacher " .
                 "WHERE subjectteacher.SubjectIndex = $subjectindex " .
                 "AND   subjectteacher.Username     = '$username'");
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0 or $is_admin) {
    $query = "SELECT subject.AverageType, subject.AverageTypeIndex " .
         "       FROM subject " . "WHERE subject.SubjectIndex = $subjectindex";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query
    $row = & $res->fetchRow(DB_FETCHMODE_ASSOC);

    $average_type = $row['AverageType'];
    $average_type_index = $row['AverageTypeIndex'];

    if (($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) and
         $_POST['category'] == "NULL") {
        $res = &  $db->query(
                        "SELECT categorylist.CategoryListIndex FROM category, categorylist " .
                         "WHERE categorylist.SubjectIndex = $subjectindex " .
                         "AND   category.CategoryIndex = categorylist.CategoryIndex " .
                         "ORDER BY category.CategoryName");
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query
        if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            $_POST['category'] = $row['CategoryListIndex'];
        }
    }
    $query = "INSERT INTO assignment (Title, Description, DescriptionFileIndex, " .
             "                        DescriptionFileType, Date, DueDate, ";
    if (($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) and
         $agenda == "0") {
        $query .=   "                        Max, CategoryListIndex, " .
                    "                        CurveType, TopMark, " .
                    "                        BottomMark, Weight, IgnoreZero, ";
    }
    $query .=   "                        Hidden, Agenda, " .
                "                        SubjectIndex, Uploadable) " .
                "VALUES ('$title' , $descr, $descr_id, " .
                "        $descr_file_type, {$_POST['date']}, {$_POST['duedate']}, ";
    if (($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) and
         $agenda == "0") {
        $query .=   "        {$_POST['max']}, {$_POST['category']}, " .
                    "        {$_POST['curve_type']}, {$_POST['top_mark']}, {$_POST['bottom_mark']}, " .
                    "        {$_POST['weight']}, {$_POST['ignore_zero']}, ";
    }
    $query .=   "        {$_POST['hidden']}, $agenda, $subjectindex, " .
                "        {$_POST['uploadable']})";
    $aRes = & $db->query($query);
    if (DB::isError($aRes))
        die($aRes->getDebugInfo()); // Check for errors in query

    $aRes = & $db->query("SELECT LAST_INSERT_ID() AS AssignmentIndex");
    if (DB::isError($aRes))
        die($aRes->getDebugInfo()); // Check for errors in query

    if ($aRow = & $aRes->fetchRow(DB_FETCHMODE_ASSOC) and
         $aRow['AssignmentIndex'] != 0) { // Get new assignment index
        $assignmentindex = $aRow['AssignmentIndex'];
    } else {
        echo "Error creating new assignment</p>\n"; // Somehow the new assignment was not
        include "footer.php"; // created
        die();
    }

    if ($_POST['uploadable'] == 1) {
        $upload_name = $upload_name . " ($assignmentindex)";
        $res = & $db->query(
                        "UPDATE assignment SET UploadName = '$upload_name' WHERE AssignmentIndex = $assignmentindex");
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query
    }

    $res = & $db->query(
                    "SELECT subjectstudent.Username, mark.MarkIndex FROM " . // Get list of students
                     "       subjectstudent LEFT OUTER JOIN mark ON (mark.AssignmentIndex = $assignmentindex " .
                     "       AND mark.Username = subjectstudent.Username), assignment " .
                     "WHERE assignment.AssignmentIndex = $assignmentindex " .
                     "AND   subjectstudent.SubjectIndex = assignment.SubjectIndex " .
                     "ORDER BY subjectstudent.Username");
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    /* For each student, insert new mark */
    $query = "INSERT INTO mark (MarkIndex, Username, AssignmentIndex, Score, " .
             "                  Comment) VALUES ";
    $mark_count = 0;
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

        if ($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) {
            if (strtoupper($score) == 'A') {
                $score = "$MARK_ABSENT"; // Change "A" for absent to $MARK_ABSENT.
            } elseif (strtoupper($score) == 'E') {
                $score = "$MARK_EXEMPT";
            } elseif (strtoupper($score) == 'L') {
                $score = "$MARK_LATE";
            } elseif ($score == '' || ! isset($_POST["score_{$row['Username']}"])) { // If score is blanks, set to NULL
                $score = "NULL";
            } else {
                if ($score != "0") {
                    settype($score, "double");
                    if ($score < 0) { // If score is less than 0, print error message and set to 0.
                        echo "</p>\n      <p>Score for {$row['Username']} must be at least 0...setting to 0.</p>\n      <p>";
                        $score = 0;
                    }
                    if ($score == 0) // If score started with a letter, print error message and set to 0.
                        echo "</p>\n      <p>Score for {$row['Username']} must be a number...setting to 0.</p>\n      <p>";
                    settype($score, "string");
                }
            }
        } elseif ($average_type == $AVG_TYPE_INDEX) {
            $inval = safe($_POST["score_{$row['Username']}"]);
            $inval = strtoupper($inval);
            $nquery = "SELECT NonmarkIndex FROM nonmark_index WHERE NonmarkTypeIndex=$average_type_index AND Input = '$inval'";
            $sRes = & $db->query($nquery);
            if (DB::isError($sRes))
                die($sRes->getDebugInfo()); // Check for errors in query

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

        if($score != "NULL" or $comment != "NULL") {
            /* Insert scores into database */
            $query .= "('', '{$row['Username']}', $assignmentindex, $score, " .
                     " $comment), ";
            $mark_count += 1;
        }
    }
    if($mark_count > 0) {
        $query = rtrim($query, " ,") . ";";
        $update = & $db->query($query);
        if (DB::isError($update)) {
            echo "</p>\n      <p>Insert: " . $update->getDebugInfo() . "</p>\n      <p>"; // Print any errors
            $error = true;
        }

        if ($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) {
            update_marks($assignmentindex);
        }
    }
    $asr = &  $db->query(
                    "SELECT subject.Name FROM subject " .
                     "WHERE subject.SubjectIndex = $subjectindex");
    if (DB::isError($asr))
        die($asr->getDebugInfo()); // Check for errors in query
    $aRow = & $asr->fetchRow(DB_FETCHMODE_ASSOC);
    log_event($LOG_LEVEL_TEACHER, "teacher/assignment/new_action.php", $LOG_TEACHER,
            "Created new assignment ($title) for {$aRow['Name']}.");
} else { // User isn't authorized to add marks
    /* Get subject name and log unauthorized access attempt */
    $asr = &  $db->query(
                    "SELECT subject.Name FROM subject " .
                     "WHERE subject.SubjectIndex = $subjectindex");
    if (DB::isError($asr))
        die($asr->getDebugInfo()); // Check for errors in query
    $aRow = & $asr->fetchRow(DB_FETCHMODE_ASSOC);
    log_event($LOG_LEVEL_ERROR, "teacher/assignment/new_action.php",
            $LOG_DENIED_ACCESS, "Tried to create new marks for {$aRow['Name']}.");

    echo "</p>\n      <p>You do not have permission to add an assignment.</p>\n      <p>";
    $error = true;
}
