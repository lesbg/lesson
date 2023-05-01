<?php
/**
 * ***************************************************************
 * teacher/book/new_copy_action.php (c) 2010, 2018 Jonathan Dieter
 *
 * Run query to insert a new copy of a book into the database.
 * ***************************************************************
 */
$book_title_index = dbfuncInt2String($_GET['key']);
$book = dbfuncInt2String($_GET['keyname']);

$error = false; // Boolean to store any errors

$query = $pdb->prepare(
    "SELECT Username FROM book_title_owner " .
    "WHERE BookTitleIndex = :book_title_index " .
    "AND   Username = :username"
);
$query->execute(['book_title_index' => $book_title_index,
                 'username' => $username]);
$row = $query->fetch();

if (!$is_admin and !$row) {
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "teacher/book/new_copy_action.php",
            $LOG_DENIED_ACCESS, "Attempted to create new copy of $book.");
    echo "</p>\n      <p>You do not have permission to add a book title.</p>\n      <p>";
    include "footer.php";
    exit(0);
}

/* Add new book type */
$pdb->prepare(
    "INSERT INTO book (BookIndex, BookNumber, BookTitleIndex) " .
    "VALUES (:index, :number, :book_title_index)"
)->execute(['index' => "${book_title_index}-$number", 'number' => $number,
            'book_title_index' => $book_title_index]);

log_event($LOG_LEVEL_ADMIN, "teacher/book/new_copy_action.php", $LOG_ADMIN,
        "Created new copy of $book.");
