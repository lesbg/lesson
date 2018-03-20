<?php
/**
 * ***************************************************************
 * teacher/book/check_in_out_copy_action.php (c) 2010-2013, 2018 Jonathan Dieter
 *
 * Check in or out a copy of a book in the database
 * ***************************************************************
 */

/* Get variables */
$nextLink = dbfuncInt2String($_GET['next']); // Link to next page
$book_index = dbfuncInt2String($_GET['key']);
$book_status_type_index = dbfuncInt2String($_GET['key2']);
if ($book_status_type_index == 1) {
    $direction = "in";
    $dir_movement = "in from";
} else {
    $direction = "out";
    $dir_movement = "out to";
}
$confirmation = false;

$is_class_teacher = check_class_teacher_year($username, $yearindex);
$is_teacher = check_teacher_year($username, $yearindex);


$query = $pdb->prepare(
    "SELECT book_title_owner.Username FROM book_title_owner, book " .
     "WHERE book_title_owner.BookTitleIndex=book.BookTitleIndex " .
     "AND   book.BookIndex = :book_index " .
     "AND   book_title_owner.Username=:username"
);
$query->execute(['book_index' => $book_index,
                 'username' => $username]);
$row = $query->fetch();

/* Check whether user is authorized to check out a copy to another user */
if (!$is_admin and !$is_class_teacher and !$row) {
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "teacher/book/check_in_out_copy_action.php",
            $LOG_DENIED_ACCESS, "Attempted to check $direction a book.");

    $noJS = true;
    $noHeaderLinks = true;
    $title = "LESSON - Unauthorized access!";

    include "header.php";

    echo "      <p align='center'>You do not have permission to access this page. <a href=" .
         "'$nextLink'>Click here to continue.</a></p>\n";

    include "footer.php";
    exit(0);
}

/* Check which button was pressed */
if ($_POST["action"] == "Check $direction") {
    $query = $pdb->query(
        "SELECT BookStateIndex FROM book_state ORDER BY BookStateIndex DESC LIMIT 1"
    );
    $row = $query->fetch();

    if ($row)
        $best_status_index = $row['BookStateIndex'];
    else
        $best_status_index = 100;

    $query = $pdb->prepare(
        "SELECT Username, State, BookStatusTypeIndex, Comment " .
        "       FROM book_status " .
        "WHERE book_status.BookIndex = :book_index " .
        "ORDER BY book_status.Order DESC"
    );
    $query->execute(['book_index' => $book_index]);
    $row = $query->fetch();

    $oldstate = 10;
    if ($row) {
        $oldstatustype = $row['BookStatusTypeIndex'];
        $oldstate = $row['State'];
        $oldusername = $row['Username'];
    } else {
        $oldstatustype = 3;
        $oldstate = $best_status_index;
        $oldusername = NULL;
    }

    $errorlist = "";

    // Make sure we're not trying to check in a book that's already checked in or vice versa
    if (($oldstatustype == 2 and $book_status_type_index == 2) or
         ($oldstatustype != 2 and $book_status_type_index == 1)) {
        $errorlist .= "<p align='center' class='error'>This book has already been checked $direction</p>\n      <p>"; // Print error message
        $format_error = True;
    }

    // Check whether this is the second time this has been pressed
    if (isset($_POST['confirmation']) and $_POST['confirmation'] == '1') {
        $confirmation = true;
    }
    if (! isset($_POST['state']) or is_null($_POST['state']) or
         $_POST['state'] == "NULL" or $_POST['state'] == "") {
        $errorlist .= "<p align='center' class='error'>You must enter the current state of the book</p>\n      <p>"; // Print error message
        $format_error = True;
    } else {
        $state = intval($_POST['state']);
    }

    if (! isset($_POST['student']) or is_null($_POST['student']) or
         $_POST['student'] == "") {
        $errorlist .= "<p align='center' class='error'>You must choose someone to check the book $dir_movement</p>\n      <p>"; // Print error message
        $format_error = True;
    } else {
        $student = safe($_POST['student']);
    }

    if (! isset($_POST['comment']) or $_POST['comment'] == "") {
        $comment = "NULL";
    } else {
        $comment = "'" . safe($_POST['comment']) . "'";
    }

    if ($oldstate - $state < 0) {
        if ($comment == "NULL") {
            $errorlist .= "<p align='center' class='error'>You must write a comment if you mark the book<br>as being <strong>better</strong> than it was the last time it was seen.</p>\n      <p>"; // Print error message
            $format_error = true;
        } elseif (! $confirmation) {
            $needs_confirmation = true;
            $errorlist .= "<p align='center' class='error'>Are you sure you want to mark the book<br>as being <strong>better</strong> than it was the last time it was seen?<br>If so, press 'Check $direction' again.</p>\n      <p>"; // Print error message
            $format_error = true;
        }
    }

    // When checking in books, make sure there's a comment if there's a drop of more than two
    if ($oldstate - $state > 2 and $comment == "NULL" and
         $book_status_type_index == 1) {
        $errorlist .= "<p align='center' class='error'>You must write a comment if you mark the book as being more<br>than <strong>two</strong> steps worse than it was when it was checked out.</p>\n      <p>"; // Print error message
        $format_error = true;
    }

    // When checking out books, make sure that user really wants to change the state
    if ($oldstate != $state and $book_status_type_index == 2) {
        if ($comment == "NULL") {
            $errorlist .= "<p align='center' class='error'>You must write a comment if you mark the book as having<br>a <strong>different</strong> state than it had when it was checked in.</p>\n      <p>"; // Print error message
            $format_error = true;
        } elseif (! $confirmation) {
            $needs_confirmation = true;
            $errorlist .= "<p align='center' class='error'>Are you sure you want to mark the book as having<br>a <strong>different</strong> state than it had when it was checked in?<br>If so, press 'Check $direction' again.</p>\n      <p>"; // Print error message
            $format_error = true;
        }
    }

    // When checking in books, make sure the user really wants to change who checked it in from who checked it out
    if ($oldusername != $_POST['student'] and $book_status_type_index == 1) {
        if ($comment == "NULL") {
            $errorlist .= "<p align='center' class='error'>You must write a comment if you check in the book from<br>a <strong>different</strong> student than the one who checked it out.</p>\n      <p>"; // Print error message
            $format_error = true;
        } elseif (! $confirmation) {
            $needs_confirmation = true;
            $errorlist .= "<p align='center' class='error'>Are you sure you want to check in the book from<br>a <strong>different</strong> student than the one who checked it out?<br>If so, press 'Check $direction' again.</p>\n      <p>"; // Print error message
            $format_error = true;
        }
    }
    if (! $format_error) {
        include "core/settermandyear.php";

        $title = "LESSON - Checking $direction book...";
        $noHeaderLinks = true;
        $noJS = true;

        include "header.php"; // Print header

        echo "      <p align='center'>Checking $direction book...";
        $query = $pdb->prepare(
            "SELECT book_status.Order FROM book_status " .
            "WHERE BookIndex=:book_index " .
            "ORDER BY book_status.Order DESC LIMIT 1"
        );
        $query->execute(['book_index' => $book_index]);
        $row = $query->fetch();
        if($row)
            $order = $row['Order'] + 1;
        else
            $order = 1;

        $query = $pdb->prepare(
            "INSERT INTO book_status (BookIndex, `Order`, BookStatusTypeIndex, State, Date, TeacherUsername, Username, Comment) " .
            "VALUES (:book_index, :order, :book_status_type_index, :state, NOW(), :username, :student, :comment)"
        )->execute(['book_index' => $book_index, 'order' => $order,
                    'book_status_type_index' => $book_status_type_index,
                    'state' => $state, 'username' => $username,
                    'student' => $student, 'comment' => $comment]);
        echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n"; // Link to next page

        include "footer.php";
    } else {
        include "teacher/book/check_in_out_copy.php";
    }
} elseif ($_POST["action"] == "Cancel") {
    redirect($nextLink);
} else {
    include "teacher/book/check_in_out_copy.php";
}
