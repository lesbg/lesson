<?php
/**
 * ***************************************************************
 * teacher/book/check_in_out_copy.php (c) 2010-2016, 2018 Jonathan Dieter
 *
 * Check in or out a copy of a book to a user
 * ***************************************************************
 */
if (! isset($_GET['key']) and isset($_POST['bookindex'])) {
    $_GET['key'] = dbfuncString2Int($_POST['bookindex']);
}
$book_index = dbfuncInt2String($_GET['key']);
if (! isset($_GET['keyname'])) {
    $query = $pdb->prepare(
        "SELECT BookTitle, BookNumber FROM book, book_title " .
        "WHERE book.BookIndex = :book_index " .
        "AND   book_title.BookTitleIndex = book.BookTitleIndex"
    );
    $query->execute(['book_index' => $book_index]);
    $row = $query->fetch();
    if ($row) {
        $_GET['keyname'] = dbfuncString2Int(
                                            $row['BookTitle'] . " copy #" .
                                             $row['BookNumber']);
    } else {
        $_GET['keyname'] = dbfuncString2Int("Unknown book");
    }
}

$book_status_type_index = dbfuncInt2String($_GET['key2']);
$classindex = dbfuncInt2String($_GET['key3']);
if (isset($_GET['key4'])) {
    $student = dbfuncInt2String($_GET['key4']);
} else {
    $student = NULL;
}
if ($book_status_type_index == 1) {
    $direction = "in";
    $dir_movement = "Incoming";
} else {
    $direction = "out";
    $dir_movement = "Outgoing";
}
$title = "Check $direction " . dbfuncInt2String($_GET['keyname']);
if (! isset($needs_confirmation)) {
    $needs_confirmation = false;
}

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
    log_event($LOG_LEVEL_ERROR, "teacher/book/check_in_out_copy.php",
            $LOG_DENIED_ACCESS, "Attempted to check $direction copy of $title.");

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    include "footer.php";
    exit(0);
}

$query = $pdb->query(
    "SELECT BookState, BookStateIndex FROM book_state ORDER BY BookStateIndex DESC LIMIT 1"
);
$row = $query->fetch();

if ($row) {
    $best_status = $row['BookState'];
    $best_status_index = $row['BookStateIndex'];
} else {
    $best_status = "New";
    $best_status_index = 100;
}

$link = "index.php?location=" .
         dbfuncString2Int("teacher/book/check_in_out_copy_action.php") .
         "&amp;key=" . $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] .
         "&amp;key2=" . $_GET['key2'] . "&amp;key3=" . $_GET['key3'] .
         "&amp;key4=" . $_GET['key4'] . "&amp;next=" . $_GET['next'];

$query = $pdb->prepare(
    "SELECT Username, State, BookStatusTypeIndex, Comment " .
    "       FROM book_status " .
    "WHERE book_status.BookIndex = :book_index " .
    "ORDER BY book_status.Order DESC"
);
$query->execute(['book_index' => $book_index]);
$row = $query->fetch();

$ok = true;
$oldstate = 10;

if ($row) {
    if (($row['BookStatusTypeIndex'] == 2 and $book_status_type_index == 2) or
         ($row['BookStatusTypeIndex'] != 2 and $book_status_type_index != 2)) {
        $ok = false;
    } else {
        $oldstate = $row['State'];
    }
    $oldusername = $row['Username'];
} else {
    if ($book_status_type_index == 1) {
        $ok = false;
    } else {
        $oldstate = $best_status_index;
    }
    $oldusername = NULL;
}

if ($ok) {
    if (isset($errorlist)) {
        echo $errorlist;
    }

    if (! isset($_POST['state']) or is_null($_POST['state']) or
         $_POST['state'] == "") {
        if ($book_status_type_index == 1) {
            $_POST['state'] = NULL;
        } else {
            $_POST['state'] = $oldstate;
        }
    } else {
        if ($_POST['state'] == "NULL") {
            $_POST['state'] = NULL;
        } else {
            $_POST['state'] = intval($_POST['state']);
        }
    }

    if (! isset($_POST['student'])) { // POST takes precedence over GET
        $_POST['student'] = $student;
    }
    if (is_null($_POST['student']) and $book_status_type_index == 1) {
        $_POST['student'] = $oldusername;
    }

    if ($_POST['class'] == "NULL") {
        $_POST['class'] = NULL;
    }
    if (! is_null($_POST['student']) and
         (! isset($_POST['class']) or is_null($_POST['class']))) {
        $query = $pdb->prepare(
            "(SELECT class.ClassIndex " .
            "        FROM class, classterm, classlist, currentterm " .
            " WHERE classlist.Username = :student_username " .
            " AND   classlist.ClassTermIndex=classterm.ClassTermIndex " .
            " AND   classterm.ClassIndex=class.ClassIndex " .
            " AND   class.YearIndex=:yearindex " .
            " AND   classterm.TermIndex=currentterm.TermIndex) " . "UNION " .
            "(SELECT 'teacher' AS ClassIndex FROM user " .
            "    INNER JOIN groupgenmem " .
            "    ON (user.Username=groupgenmem.Username) INNER JOIN groups USING (GroupID)" .
            " WHERE user.Username = :student_username " .
            " AND   groups.GroupTypeID='activeteacher' " .
            " AND   groups.YearIndex=:yearindex) LIMIT 1"
        );
        $query->execute(['student_username' => $_POST['student'],
                         'yearindex' => $yearindex]);
        $row = $query->fetch();
        if ($row) {
            $_POST['class'] = $row['ClassIndex'];
        } else {
            $_POST['class'] = NULL;
        }
    }
    if (! isset($_POST['class']) and $classindex != - 1) {
        $_POST['class'] = $classindex;
    }
    if (! isset($_POST['class'])) {
        $_POST['class'] = NULL;
    } else {
        if ($_POST['class'] != "teacher") {
            $_POST['class'] = intval($_POST['class']);
        }
    }

    if (! isset($_POST['comment'])) {
        $_POST['comment'] = "";
    } else {
        $_POST['comment'] = htmlspecialchars($_POST['comment'], ENT_QUOTES);
    }

    echo "      <form action='$link' method='post' name='checkout'>\n"; // Form method
    echo "         <table class='transparent' align='center'>\n"; // Table headers

    echo "      <tr>\n";
    echo "         <td colspan='3'><b>Book History:</b></td>\n";
    echo "      </tr>\n";

    // Book history
    $query = $pdb->query(
        "SELECT BookStatusType FROM book_status_type WHERE BookStatusTypeIndex=3"
    );
    $row = $query->fetch();
    if ($row) {
        $purchased = $row['BookStatusType'];
    } else {
        $purchased = "Unknown";
    }
    $query = $pdb->prepare(
        "SELECT book_status.Comment, book_status.BookStatusTypeIndex, " .
        "       book_status_type.BookStatusType, book_state.BookState AS State " .
        "       FROM book_status " .
        "        INNER JOIN book_status_type USING (BookStatusTypeIndex) " .
        "        LEFT OUTER JOIN book_state " .
        "         ON (book_status.State = book_state.BookStateIndex)" .
        "WHERE book_status.BookIndex = :book_index " .
        "ORDER BY `Order` "
    );
    $query->execute(['book_index' => $book_index]);

    $prev_state = - 1;
    $prev_type = - 1;
    $prev_comment = "";
    foreach($query as $row) {
        if ($prev_state == - 1 and $row['BookStatusTypeIndex'] != 3) {
            echo "      <tr>\n";
            echo "         <td>$purchased:</td>\n";
            echo "         <td>$best_status</td>\n";
            echo "         <td><i>Initial purchase</i></td>";
            echo "      </tr>\n";
            $prev_state = $best_status_index;
        }
        if (1 == 1) {
            if ($prev_state == - 1) {
                if (is_null($row['Comment'])) {
                    $row['Comment'] = "<i>Initial purchase</i>";
                }
                if (is_null($row['State'])) {
                    $row['State'] = $best_status;
                }
            }
            echo "      <tr>\n";
            echo "         <td>{$row['BookStatusType']}:</td>\n";
            echo "         <td>{$row['State']}</td>\n";
            echo "         <td>{$row['Comment']}</td>";
            echo "      </tr>\n";
        }
        $prev_state = $row['State'];
        $prev_type = $row['BookStatusTypeIndex'];
        $prev_comment = $row['Comment'];
    }
    if ($prev_state == - 1) {
        echo "      <tr>\n";
        echo "         <td>$purchased:</td>\n";
        echo "         <td>$best_status</td>\n";
        echo "         <td><i>Initial purchase</i></td>";
        echo "      </tr>\n";
    }

    echo "         <tr>\n";
    echo "            <td><b>$dir_movement state</b></td>\n";
    echo "            <td colspan='2'>\n";
    echo "               <select name='state'>\n";
    if (! isset($_POST['state']) or is_null($_POST['state'])) {
        $default = "selected";
    } else {
        $default = "";
    }
    echo "                  <option value='NULL' $default></option>\n";
    $query = $pdb->query(
        "SELECT BookStateIndex, BookState " .
        "FROM book_state " .
        "ORDER BY BookStateIndex DESC"
    );

    foreach($query as $row) {
        if ($row['BookStateIndex'] == $_POST['state']) {
            $default = "selected";
        } else {
            $default = "";
        }

        echo "                  <option value='{$nrow['BookStateIndex']}' $default>{$nrow['BookState']}</option>\n";
    }
    echo "               </select>\n";
    echo "            </td>\n";
    echo "         </tr>\n";
    echo "         <tr>\n";
    echo "            <td><b>Comment</b></td>\n";
    echo "            <td colspan='2'><textarea rows='3' cols='40' name='comment'>{$_POST['comment']}</textarea></td>\n";
    echo "         </tr>\n";

    /* Create listboxes with classes */
    echo "         <tr>\n";
    echo "            <td><b>Class</b></td>";
    echo "            <td colspan='2'><select name='class' onchange='checkout.submit()'>\n";
    $default_class = NULL;
    $query = $pdb->prepare(
        "SELECT class.ClassIndex, class.ClassName FROM " .
        "       class INNER JOIN book_class " .
        "         ON (class.ClassName = book_class.ClassName" .
        "             OR class.Grade = book_class.Grade) " .
        "        INNER JOIN book USING (BookTitleIndex) " .
        "WHERE YearIndex = :yearindex " .
        "AND   book.BookIndex = :book_index " .
        "GROUP BY class.ClassIndex " .
        "ORDER BY class.Grade, class.ClassName"
    );
    $query->execute(['book_index' => $book_index,
                     'yearindex' => $yearindex]);
    $data = $query->fetchAll();
    if ($data) {
        foreach($data as $row) {
            if (is_null($default_class)) {
                $default_class = $row['ClassIndex'];
            }
            if ($row['ClassIndex'] == $_POST['class']) {
                $default = "selected";
                $default_chosen = true;
            } else {
                $default = "";
            }

            echo "                     <option value='{$row['ClassIndex']}' $default>{$row['ClassName']}\n";
        }
        echo "                        <option value='NULL'>--\n";
    }
    if (is_null($default_class)) {
        $default_class = 'teacher';
    }
    echo "                        <option value='teacher'>Teachers\n";
    $query = $pdb->prepare(
        "SELECT ClassIndex, ClassName FROM class " .
        "WHERE YearIndex = :yearindex " .
        "ORDER BY Grade, ClassName"
    );
    $query->execute(['yearindex' => $yearindex]);
    foreach($query as $row) {
        if ($row['ClassIndex'] == $_POST['class'] and ! $default_chosen) {
            $default = "selected";
        } else {
            $default = "";
        }

        echo "                     <option value='{$row['ClassIndex']}' $default>{$row['ClassName']}\n";
    }
    echo "                  </select>\n";
    echo "                  <noscript>\n"; // No javascript compatibility
    echo "                     <input type='submit' name='action' value='Update' \>\n";
    echo "                  </noscript><br>\n";
    echo "             </td>\n";
    echo "          </tr>\n";

    /* Get list of students who are in the active class */
    if (! isset($_POST['class']) or is_null($_POST['class']) or
         $_POST['class'] == "") {
        $_POST['class'] = $default_class;
    }
    echo "          <tr>\n";
    echo "             <td colspan='3'>";
    echo "                  <select name='student' style='width: 398px;' size=14>\n";
    if ($_POST['class'] != "") {
        if ($_POST['class'] == "teacher") {
            $query = $pdb->prepare(
                "SELECT user.FirstName, user.Surname, user.Username FROM " .
                "       user INNER JOIN groupgenmem ON (user.Username=groupgenmem.Username) " .
                "            INNER JOIN groups USING (GroupID) " .
                "WHERE groups.GroupTypeID='activeteacher' " .
                "AND   groups.YearIndex=:yearindex " .
                "ORDER BY user.Username"
            );
            $query->execute(['yearindex' => $yearindex]);
            foreach($query as $row) {
                if (isset($_POST['student']) and
                     $_POST['student'] == $row['Username']) {
                    $default = "selected";
                } else {
                    $default = "";
                }
                echo "                     <option value='{$row['Username']}' $default>{$row['Username']} - {$row['Title']} {$row['FirstName']} " .
                     "{$row['Surname']}\n";
            }
        } else {
            $query = $pdb->prepare(
                "SELECT user.FirstName, user.Surname, user.Username FROM " .
                "       user, classterm, classlist, currentterm " .
                "WHERE  user.Username = classlist.Username " .
                "AND    classlist.ClassTermIndex = classterm.ClassTermIndex " .
                "AND    classterm.TermIndex = currentterm.TermIndex " .
                "AND    classterm.ClassIndex = :class_index " .
                "ORDER BY user.Username"
            );
            $query->execute(['class_index' => $_POST['class']]);
            foreach($query as $row) {
                if (isset($_POST['student']) and
                     $_POST['student'] == $row['Username']) {
                    $default = "selected";
                } else {
                    $default = "";
                }
                echo "                     <option value='{$row['Username']}' $default>{$row['Username']} - {$row['FirstName']} " .
                     "{$row['Surname']}\n";
            }
        }
    }
    echo "                  </select>\n";
    echo "               </td>\n";
    echo "            </tr>\n";
    echo "         </table>\n";
    if ($needs_confirmation) {
        $confirmation_value = 1;
    } else {
        $confirmation_value = 0;
    }
    echo "         <input type='hidden' name='confirmation' value='$confirmation_value' />\n";
    echo "         <p align='center'>\n";
    echo "            <input type='submit' name='action' value='Check $direction'>&nbsp; \n";
    echo "            <input type='submit' name='action' value='Cancel'>&nbsp; \n";
    echo "         </p>\n";
    echo "      </form>\n";
} else {
    echo "      <p>This copy is already checked $direction</p>\n";
}
log_event($LOG_LEVEL_EVERYTHING, "teacher/book/check_in_out_copy.php",
        $LOG_ADMIN, "Checked $direction copy of $title.");

include "footer.php";
