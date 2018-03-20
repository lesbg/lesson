<?php
/**
 * ***************************************************************
 * teacher/book/modify_copy_action.php (c) 2010, 2018 Jonathan Dieter
 *
 * Run query to modify a current copy of a book in the database.
 * ***************************************************************
 */
$book_index = dbfuncInt2String($_GET['key']);
$book = dbfuncInt2String($_GET['keyname']);
$copy = dbfuncInt2String($_GET['keyname2']);
$error = false; // Boolean to store any errors

$query = $pdb->prepare(
    "SELECT book_title_owner.Username FROM book_title_owner, book " .
     "WHERE book_title_owner.BookTitleIndex=book.BookTitleIndex " .
     "AND   book.BookIndex = :book_index " .
     "AND   book_title_owner.Username=:username"
);
$query->execute(['book_index' => $book_index,
                 'username' => $username]);
$row = $query->fetch();

if (!$is_admin and !$row) {
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "teacher/book/modify_copy_action.php",
            $LOG_DENIED_ACCESS,
            "Attempted to change information about about copy $copy of $book.");
    echo "</p>\n      <p>You do not have permission to change this copy.</p>\n      <p>";
    $error = true;
    exit(0);
}

$pdb->prepare(
    "UPDATE book SET BookNumber = :number " .
    "WHERE  BookIndex = :book_index"
)->execute(['number' => $number, 'book_index' => $book_index]);

log_event($LOG_LEVEL_ADMIN, "teacher/book/modify_copy_action.php", $LOG_ADMIN,
    "Modified information about copy $copy of $book.");
