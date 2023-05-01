<?php
/**
 * ***************************************************************
 * admin/book/new_or_modify_title_action.php (c) 2010 Jonathan Dieter
 *
 * Change book title information
 * ***************************************************************
 */

/* Get variables */
$key = safe(dbfuncInt2String($_GET['key'])); // Key
$nextLink = dbfuncInt2String($_GET['next']); // Link to next page

$check_key = "";

/* Check which button was pressed */
foreach ( array_keys($_POST) as $item ) {
    if (strpos($item, "del_subject_") === 0) {
        $check_subject_key = $item;
    }
    if (strpos($item, "del_class_") === 0) {
        $check_class_key = $item;
    }
}

$cost = floatval($_POST['cost']);
if ($cost == 0) {
    $cost = 'NULL';
}

if (isset($_POST['action']) and
     ($_POST['action'] == "Save" or $_POST['action'] == "Update")) { // If update or save were pressed, print
                                                                    // common info and go to the right page.
    /* Check for input errors */
    $format_error = False;
    $errorlist = "";
    if (! isset($_POST['title']) or is_null($_POST['title']) or
         $_POST['title'] == "") { // Make sure name has been entered
        $errorlist .= "<p class='error' align='center'>You must specify the book's title!</p>\n";
        $format_error = True;
    }

    $title = safe($_POST['title']);
    if ($_POST['type'] == "new") { // Create new title if "Save" was pressed
        if (! isset($_POST['id']) or is_null($_POST['id']) or $_POST['id'] == "") { // Make sure name has been entered
            $errorlist .= "<p class='error' align='center'>You must specify the book's ID!</p>\n";
            $format_error = True;
        } else {
            $id = safe($_POST['id']);
            if ($_POST['type'] == "new") {
                $query = "SELECT BookTitleIndex FROM book_title " .
                     "WHERE BookTitleIndex = '$id'";
                $res = & $db->query($query);
                if (DB::isError($res))
                    die($res->getDebugInfo()); // Check for errors in query
                if ($res->numRows() > 0) {
                    $errorlist .= "<p class='error' align='center'>This book ID is already being used.  Please choose another.</p>\n";
                    $format_error = True;
                }
            }
        }
    }

    if (! $format_error) {
        $errorlist = ""; // Clear error list. This list will now only contain database errors.

        $book = "LESSON - Saving changes...";
        $noHeaderLinks = true;
        $noJS = true;

        include "header.php";

        echo "      <p align='center'>Saving changes...";

        if ($_POST['type'] == "new") { // Create new title if "Save" was pressed
            include "admin/book/new_title_action.php";
        } else {
            include "admin/book/modify_title_action.php"; // Change title if "Update" was pressed
        }

        if ($error) { // If we ran into any errors, print failed, otherwise print done
            echo "failed!</p>\n";
        } else {
            echo "done.</p>\n";
        }
        echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n"; // Link to next page
        include "footer.php";
    } else {
        if ($_POST['type'] == "new") {
            include "admin/book/new_title.php";
        } else {
            include "admin/book/modify_title.php";
        }
    }

    // User removed subject from book
} elseif (isset($_POST[$check_subject_key]) and
         $_POST[$check_subject_key] == "Remove") {
    $check_key = intval(str_replace('del_subject_', '', $check_subject_key));
    $query = "DELETE FROM book_subject_type " .
             "WHERE  BookSubjectTypeIndex = $check_key";
    $aRes = & $db->query($query);
    if (DB::isError($aRes))
        die($aRes->getDebugInfo()); // Check for errors in query
    include "admin/book/modify_title.php";

    // User added subject to book
} elseif (isset($_POST['add_subject']) and $_POST['add_subject'] == "Add") {
    if (isset($_POST['subject']) and ! is_null($_POST['subject']) and
             $_POST['subject'] != "NULL") {
        $subjecttypeindex = safe($_POST['subject']);
        $query = "SELECT SubjectTypeIndex FROM subjecttype " .
                 "WHERE SubjectTypeIndex = $subjecttypeindex";
        $aRes = & $db->query($query);
        if (! DB::isError($aRes) and $aRes->numRows() > 0) {
            $query = "INSERT INTO book_subject_type (BookTitleIndex, SubjectTypeIndex) " .
                 "            VALUES ('$key', $subjecttypeindex)";
            $aRes = & $db->query($query);
            if (DB::isError($aRes))
                die($aRes->getDebugInfo()); // Check for errors in query
        }
    }
    include "admin/book/modify_title.php";

    // User removed class or grade from book
} elseif (isset($_POST[$check_class_key]) and
         $_POST[$check_class_key] == "Remove") {
    $check_key = intval(str_replace('del_class_', '', $check_class_key));
    $query = "DELETE FROM book_class " . "WHERE  BookClassIndex = $check_key";
    $aRes = & $db->query($query);
    if (DB::isError($aRes))
        die($aRes->getDebugInfo()); // Check for errors in query
    include "admin/book/modify_title.php";

    // User added class to book
} elseif (isset($_POST['add_class']) and $_POST['add_class'] == "Add") {
    if (isset($_POST['class']) and ! is_null($_POST['class']) and
             $_POST['class'] != "NULL" and isset($_POST['class_special']) and
             intval($_POST['class_special']) >= 0 and
             intval($_POST['class_special']) < 3) {
        $classname = safe($_POST['class']);
        $special = intval($_POST['class_special']);
        $query = "SELECT ClassName FROM class " .
                 "WHERE ClassName = '$classname' " .
                 "AND   YearIndex = $yearindex";
        $aRes = & $db->query($query);
        if (! DB::isError($aRes) and $aRes->numRows() > 0) {
            $query = "INSERT INTO book_class (BookTitleIndex, ClassName, Flags) " .
                 "            VALUES ('$key', '$classname', $special)";
            $aRes = & $db->query($query);
            if (DB::isError($aRes))
                die($aRes->getDebugInfo()); // Check for errors in query
        }
    }
    include "admin/book/modify_title.php";

    // User added grade to book
} elseif (isset($_POST['add_grade']) and $_POST['add_grade'] == "Add") {
    if (isset($_POST['grade']) and ! is_null($_POST['grade']) and
             $_POST['grade'] != "NULL" and isset($_POST['grade_special']) and
             intval($_POST['grade_special']) >= 0 and
             intval($_POST['grade_special']) < 3) {
        $grade = intval($_POST['grade']);
        $special = intval($_POST['grade_special']);
        $query = "SELECT Grade FROM grade " . "WHERE Grade = $grade";
        $aRes = & $db->query($query);
        if (! DB::isError($aRes) and $aRes->numRows() > 0) {
            $query = "INSERT INTO book_class (BookTitleIndex, Grade, Flags) " .
                 "            VALUES ('$key', '$grade', $special)";
            $aRes = & $db->query($query);
            if (DB::isError($aRes))
                die($aRes->getDebugInfo()); // Check for errors in query
        }
    }
    include "admin/book/modify_title.php";
} else { // if($_POST['action'] == "Cancel")
    redirect($nextLink);
}
?>
