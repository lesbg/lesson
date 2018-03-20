<?php
/**
 * ***************************************************************
 * teacher/book/choose_book_number.php (c) 2010-2013, 2018 Jonathan Dieter
 *
 * Choose book number to check out
 * ***************************************************************
 */
$book_title_index = dbfuncInt2String($_GET['key']);
$book_title = dbfuncInt2String($_GET['keyname']);
if (isset($_GET['key4'])) {
    $student = dbfuncInt2String($_GET['key4']);
} else {
    $student = NULL;
}
$title = "Select book number for " . dbfuncInt2String($_GET['keyname']);

$is_class_teacher = check_class_teacher_year($username, $yearindex);
$is_teacher = check_teacher_year($username, $yearindex);

include "header.php";

$query = $pdb->prepare(
    "SELECT book_title_owner.Username FROM book_title_owner, book " .
     "WHERE book_title_owner.BookTitleIndex=book.BookTitleIndex " .
     "AND   book.BookIndex = :book_index " .
     "AND   book_title_owner.Username=:username"
);
$query->execute(['book_index' => $book_index,
                 'username' => $username]);
$row = $query->fetch();

if (!$is_admin and !$is_class_teacher and !$row) {
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "teacher/book/choose_book_number.php",
            $LOG_DENIED_ACCESS,
            "Attempted to choose book number for $book_title.");

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    include "footer.php";
    exit(0);
}

$query = $pdb->query(
    "SELECT BookState FROM book_state ORDER BY BookStateIndex DESC LIMIT 1"
);
$row = $query->fetch();

if ($row)
    $best_status = $row['BookState'];
else
    $best_status = "New";


$query = $pdb->prepare(
    "SELECT book.BookIndex, book.BookNumber, book_title.BookTitleIndex, BookTitle, " .
    "       book_status.BookStatusTypeIndex, NULL AS `Date`, book_state.BookState, " .
    "       subjecttype.Title AS SubjectType " . "  FROM book_title " .
    "  INNER JOIN book USING (BookTitleIndex) " .
    "  LEFT OUTER JOIN book_status USING (BookIndex) " .
    "  LEFT OUTER JOIN book_state ON book_status.State = book_state.BookStateIndex " .
    "  LEFT OUTER JOIN book_status AS bs2 ON book_status.BookIndex = bs2.BookIndex " .
    "                AND book_status.Order < bs2.Order " .
    "  LEFT OUTER JOIN book_subject_type ON (book_subject_type.BookTitleIndex = book_title.BookTitleIndex) " .
    "  LEFT OUTER JOIN subjecttype USING (SubjectTypeIndex) " .
    "WHERE (book_status.BookStatusTypeIndex = 1" .
    "       OR book_status.BookStatusTypeIndex = 3 " .
    "       OR book_status.BookStatusTypeIndex IS NULL) " .
    "AND book.Retired = 0 " . "AND book_title.Retired = 0 " .
    "AND book_title.BookTitleIndex = :book_title_index " .
    "ORDER BY BookNumber, BookIndex"
);
$query->execute(['book_title_index' => $book_title_index]);
$data = $query->fetchAll();

if ($data) {
    echo "      <table align='center' border='1'>\n"; // Table headers
    echo "         <tr>\n";
    echo "            <th>Number</th>\n";
    echo "            <th>Current State</th>\n";
    echo "         </tr>\n";

    foreach($data as $row) {
        $link = "index.php?location=" .
                 dbfuncString2Int("teacher/book/check_in_out_copy.php") .
                 "&amp;key=" . dbfuncString2Int($row['BookIndex']) .
                 "&amp;key2=" . dbfuncString2Int(2) . "&amp;key4=" .
                 $_GET['key4'] . "&amp;next=" . $_GET['next'];

        echo "      <tr>\n";
        echo "         <td><a href='$link'>{$row['BookNumber']}</td>\n";
        if (is_null($row['BookState'])) {
            echo "         <td>$best_status</td>\n";
        } else {
            echo "         <td>{$row['BookState']}</td>\n";
        }
        echo "      </tr>\n";
    }
    echo "         </table>\n";
} else {
    echo "      <p>This book has no active copies</p>\n";
}
log_event($LOG_LEVEL_EVERYTHING, "teacher/book/choose_book_number.php",
        $LOG_ADMIN, "Chose book number for $book_title.");

include "footer.php";
