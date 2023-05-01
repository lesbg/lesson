<?php
// FIX CLASS STUFF

/**
 * ***************************************************************
 * core/dbfunc.php (c) 2004-2018 Jonathan Dieter
 *
 * Functions for connecting to the database, plus miscellaneous
 * functions for getting permissions and munging of location
 * strings to ints
 * ***************************************************************
 */

include "core/permfunc.php"; // Get database connection functions

/* Escape strings so they are safe to enter into a database */
function safe($instring) {
    global $db;
    return $db->escapeSimple(stripslashes($instring));
}

/* 302 redirect */
function redirect($url, $code=302) {
    $url = htmlspecialchars_decode($url);
    header('Location: ' . $url, true, $code);
    exit();
}

/* Connect to database specified by global variable $dsn */
function &dbfuncConnect() {
    /* Set global parameters */
    global $DSN; // DSN to connect to database, stored in globals.php

    /* Connection to database */
    $db = DB::connect($DSN); // Initiate connection
    if (DB::isError($db))
        die($db->getDebugInfo()); // Check for errors in connection

    $query = "SET NAMES 'utf8'";
    $res =& $db->query($query);

    if (DB::isError($res))
        die($res->getDebugInfo());

    return $db;
}

/* Connect to database specified by global variable $dsn */
function &dbfuncPDOConnect() {
    /* Set global parameters */
    global $PDO_DSN; // PDO DSN to connect to database, stored in globals.php
    global $PDO_USER; // PDO database user, stored in globals.php
    global $PDO_PWD; // PDO database password, stored in globals.php

    /* Connection to database using PDO */
    $db = new PDO($PDO_DSN, $PDO_USER, $PDO_PWD);
    $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    return $db;
}
/* Connect to database specified by global variable $MASTER_DSN */
function &dbfuncConnectMaster() {
    /* Set global parameters */
    global $PDO_DSN; // PDO DSN to connect to database, stored in globals.php
    global $PDO_USER; // PDO database user, stored in globals.php
    global $PDO_PWD; // PDO database password, stored in globals.php
    global $MASTER_DSN; // DSN to connect to database, stored in globals.php
    global $MASTER_USER;
    global $MASTER_PWD;

    if(!isset($MASTER_DSN) or is_null($MASTER_DSN)) {
        $MASTER_DSN = $PDO_DSN;
        $MASTER_USER = $PDO_USER;
        $MASTER_PWD = $PDO_PWD;
    }

    /* Connection to database using PDO */
    $db = new PDO($PDO_DSN, $PDO_USER, $PDO_PWD);
    $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    return $db;
}


/* Get code to generate a button-looking hyperlink to $link with ID of $type and tooltip of $tooltip around $text */
function &dbfuncGetButton($link = "", $text, $size = "medium", $type = "", $tooltip = "",
                        $buttonclass = "button") {
    $button = "<span class=\"$size\"><a class=\"$buttonclass\" ";
    if ($tooltip != "") {
        $button .= "title=\"$tooltip\" ";
    }
    if ($link != "") {
        $button .= "href=\"$link\">";
    } else {
        $button .= ">";
    }
    if ($type != "") {
        $button .= "<span class=\"$type\">";
    }
    $button .= "$text";
    if ($type != "") {
        $button .= "</span>";
    }
    $button .= "</a></span>";

    return $button;
}

/* Get code to generate a button-looking hyperlink to $link with ID of $type and tooltip of $tooltip around $text */
function &dbfuncGetDisabledButton($text, $size = "medium", $type = "",
                                $buttonclass = "disabled-button") {
    $button = "<span class=\"$size\"><span class=\"$buttonclass\">";
    if ($type != "") {
        $button .= "<span class=\"$type\">";
    }
    $button .= "$text";
    if ($type != "") {
        $button .= "</span>";
    }
    $button .= "</span></span>";

    return $button;
}
/* Return full username */
function &dbfuncGetFullName() {
    /* Set global parameters */
    global $db;
    global $username;

    /* Run query to extract FirstName and Surname from "user" table */
    $res = & $db->query(
                    "SELECT FirstName, Surname FROM user WHERE Username = \"$username\"");
    if (DB::isError($res))
        die($res->getDebugInfo());

    if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) { // Get result of query
        $fullname = $row['FirstName'] . " " . $row['Surname']; // Store result of query in $fullname,
        $fullname = htmlspecialchars($fullname); // and return
        return $fullname;
    } else {
        return "";
    }
}

/* Get year index */
function dbfuncGetYearIndex() {
    /* Set global parameters */
    global $db;

    /* Run query to extract YearIndex from "currentinfo" table */
    $res = & $db->query(
                    "SELECT YearIndex FROM currentinfo ORDER BY InputDate DESC");
    if (DB::isError($res))
        die($res->getDebugInfo());

    $row = & $res->fetchRow(DB_FETCHMODE_ASSOC); // Get result of query
    return $row['YearIndex']; // and return
}

/* Check whether we are to print totals */
function dbfuncGetPrintTotal() {
    /* Set global parameters */
    global $db;

    /* Run query to extract YearIndex from "currentinfo" table */
    $res = & $db->query(
                    "SELECT PrintTotal FROM currentinfo ORDER BY InputDate DESC");
    if (DB::isError($res))
        die($res->getDebugInfo());

    $row = & $res->fetchRow(DB_FETCHMODE_ASSOC); // Get result of query
    if ($row['PrintTotal'] == 1)
        return true;
    else
        return false;
}

/* Get date format */
function dbfuncGetDateFormat() {
    /* Set global parameters */
    global $db, $username;

    /* Run query to extract date format from "user" table */
    $userRes = & $db->query(
                        "SELECT DateType, DateSeparator FROM user WHERE Username=\"$username\"");
    if (DB::isError($userRes))
        die($userRes->getDebugInfo());
    $userRow = & $userRes->fetchRow(DB_FETCHMODE_ASSOC); // Get result of query

    /* Run query to extract date format from "currentinfo" table */
    $globRes = & $db->query(
                        "SELECT DateType, DateSeparator FROM currentinfo ORDER BY InputDate DESC");
    if (DB::isError($globRes))
        die($globRes->getDebugInfo());
    $globRow = & $globRes->fetchRow(DB_FETCHMODE_ASSOC); // Get result of query

    /* Get date format first from user table, then from currentinfo */
    if (is_null($userRow['DateType'])) {
        $dType = $globRow['DateType'];
    } else {
        $dType = $userRow['DateType'];
    }
    if (is_null($userRow['DateSeparator'])) {
        $dSeparator = $globRow['DateSeparator'];
    } else {
        $dSeparator = $userRow['DateSeparator'];
    }

    if ($dType == 0) {
        return "m{$dSeparator}d{$dSeparator}Y";
    } else {
        return "d{$dSeparator}m{$dSeparator}Y";
    }
}

/* Create date in format yyyy-mm-dd from $dateformat */
function dbfuncCreateDate($inputDate = "") {
    global $dateformat; // Globals

    if ($inputDate == "") {
        return date("Y-m-d");
    } else {
        $dSeparator = substr($dateformat, 1, 1);
        $firstSeparator = strpos($inputDate, $dSeparator);
        $secondSeparator = strpos($inputDate, $dSeparator, $firstSeparator + 1);
        if ($secondSeparator == "") {
            $year = date("Y");
            if (substr($dateformat, 0, 1) == 'd') {
                $month = substr($inputDate, $firstSeparator + 1);
                $day = substr($inputDate, 0, $firstSeparator);
            } else {
                $month = substr($inputDate, 0, $firstSeparator);
                $day = substr($inputDate, $firstSeparator + 1);
            }
        } else {
            $year = substr($inputDate, $secondSeparator + 1);
            if (substr($dateformat, 0, 1) == 'd') {
                $month = substr($inputDate, $firstSeparator + 1,
                                $secondSeparator - $firstSeparator - 1);
                $day = substr($inputDate, 0, $firstSeparator);
            } else {
                $month = substr($inputDate, 0, $firstSeparator);
                $day = substr($inputDate, $firstSeparator + 1,
                            $secondSeparator - $firstSeparator - 1);
            }
        }
        return date("Y-m-d", mktime(0, 0, 0, $month, $day, $year));
    }
}

/* Get term index */
function dbfuncGetTermIndex($depindex) {
    /* Set global parameters */
    global $db;

    $depindex = safe($depindex);

    if (! is_null($depindex) and $depindex != "") {
        /* Run query to extract YearIndex from "currentinfo" table */
        $res = & $db->query(
                        "SELECT TermIndex FROM currentterm WHERE DepartmentIndex=$depindex");
        if (DB::isError($res))
            die($res->getDebugInfo());

        $row = & $res->fetchRow(DB_FETCHMODE_ASSOC); // Get result of query
        return $row['TermIndex']; // and return
    } else {
        return NULL;
    }
}
function dbfuncGetDepIndex() {
    /* Set global parameters */
    global $db;
    global $username;
    global $yearindex;

    /* Run query to extract YearIndex from "currentinfo" table */
    $res = & $db->query(
                    "SELECT DepartmentIndex FROM user WHERE Username=\"$username\"");
    if (DB::isError($res))
        die($res->getDebugInfo());

    $row = & $res->fetchRow(DB_FETCHMODE_ASSOC); // Get result of query
    $depindex = $row['DepartmentIndex']; // and return
    if (is_null($depindex)) {
        $res = &  $db->query(
                        "SELECT class.DepartmentIndex FROM class, classterm, classlist " .
                         "WHERE classlist.Username='$username' " .
                         "AND   classlist.ClassTermIndex=classterm.ClassTermIndex " .
                         "AND   class.ClassIndex = classterm.ClassIndex " .
                         "AND   class.YearIndex = $yearindex " .
                         "ORDER BY classterm.TermIndex DESC " . "LIMIT 1");
        if (DB::isError($res))
            die($res->getDebugInfo());
        if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) { // Get result of query
            return $row['DepartmentIndex']; // and return
        } else {
            return NULL;
        }
    } else {
        return $depindex;
    }
}

function dbfuncIsActiveStudent() {
    /* Set global parameters */
    global $db;
    global $username;
    global $yearindex;

    /* Run query to extract information from "user" table */
    $query =    "SELECT Username FROM user INNER JOIN groupgenmem USING (Username) " .
                "     INNER JOIN groups USING (GroupID) " .
                "WHERE user.Username='$username' " .
                "AND   groups.GroupTypeID='activestudent' " .
                "AND   groups.YearIndex=$yearindex ";
    $res = & $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());


    if ($res->numRows() > 0) {
        return true;
    } else {
        return false;
    }
}

function dbfuncIsActiveTeacher() {
    /* Set global parameters */
    global $db;
    global $username;
    global $yearindex;

    /* Run query to extract information from "user" table */
    $query =    "SELECT Username FROM user INNER JOIN groupgenmem USING (Username) " .
                "     INNER JOIN groups USING (GroupID) " .
                "WHERE user.Username='$username' " .
                "AND   groups.GroupTypeID='activeteacher' " .
                "AND   groups.YearIndex=$yearindex ";
    $res = & $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());


    if ($res->numRows() > 0) {
        return true;
    } else {
        return false;
    }
}
/* Return integer representing permissions */
function dbfuncGetPermissions() {
    /* Set global parameters */
    global $db;
    global $username;

    /* Run query to extract permissions from "user" table */
    $res = & $db->query(
                    "SELECT Permissions FROM user WHERE Username = \"$username\"");
    if (DB::isError($res))
        return 0; // If there's an error, assume user has no permissions

    $row = & $res->fetchRow(DB_FETCHMODE_ASSOC); // Get result of query
    return $row['Permissions']; // and return
}

/* Get individual permission from permissions */
function dbfuncGetPermission($permissions, $number) {
    for($count = 0; $count < $number; $count ++) { // Cycle through and remove all insignificant bits
        $permissions = floor($permissions / 2); // by dividing $permissions by two until we reach
                                                       // $number
    }
    if (($permissions / 2) == floor($permissions / 2)) { // Check least significant byte by determining if
        return false; // $permissions is even or odd. If $permissions is
    } else { // even, the LSB is 0 or false, while if $permissions
        return true; // is even, the LSB is 1 or true. Return appropriate
    } // boolean.
}

/* Hash function to convert *any* string to a safe combination of numbers and multi-case letters */
function dbfuncString2Int($strValue) {
    return base64_encode($strValue);
}
function dbfuncArray2String($Array) {
    $Return = '';
    $NullValue = "^^^";
    foreach ( $Array as $Key => $Value ) {
        if (is_array($Value)) {
            $ReturnValue = '^^array^' . dbfuncArray2String($Value);
        } else {
            $ReturnValue = (strlen($Value) > 0) ? $Value : $NullValue;
        }
        $Return .= urlencode(base64_encode($Key)) . '|' .
                 urlencode(base64_encode($ReturnValue)) . '||';
    }
    return urlencode(substr($Return, 0, - 2));
}
function dbfuncString2Array($String) {
    $Return = array();
    $String = urldecode($String);
    $TempArray = explode('||', $String);
    $NullValue = urlencode(base64_encode("^^^"));
    foreach ( $TempArray as $TempValue ) {
        list($Key, $Value) = explode('|', $TempValue);
        $DecodedKey = base64_decode(urldecode($Key));
        if ($Value != $NullValue) {
            $ReturnValue = base64_decode(urldecode($Value));
            if (substr($ReturnValue, 0, 8) == '^^array^') {
                $ReturnValue = dbfuncString2Array(substr($ReturnValue, 8));
            }
            $Return[$DecodedKey] = $ReturnValue;
        } else {
            $Return[$DecodedKey] = NULL;
        }
    }
    return $Return;
}
function dbfuncArray2Int($array) {
    $strValue = "";
    $strValue = dbfuncArray2String($array);
    return dbfuncString2Int($strValue);
}
function dbfuncInt2Array($strValue) {
    $strValue = dbfuncInt2String($strValue);
    $array = dbfuncString2Array($strValue);
    return $array;
}

/* Hash function to a safe combination of numbers and multi-case letters into a string */
function dbfuncInt2String($strValue) {
    return base64_decode($strValue);
}

/* Function to setup logging */
function start_log($page) {
    global $LOG_LEVEL;
    global $LOG_LOGIN;
    global $LOG_LEVEL_ACCESS;
    global $db;
    global $username;
    global $password_number;

    $page = safe($page);
    if (! isset($_SESSION['LogIndex']) && $LOG_LEVEL >= $LOG_LEVEL_ACCESS) { // Login hasn't been logged yet, so log it
        if (isset($_SERVER['REMOTE_HOST'])) {
            $remote_host = $_SERVER['REMOTE_HOST'];
        } else {
            $remote_host = $_SERVER['REMOTE_ADDR'];
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            if ($_SERVER['HTTP_X_FORWARDED_FOR'] != "unknown" and
                isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                if ($remote_host == "10.88.0.1") {
                    $remote_host = $_SERVER['HTTP_X_FORWARDED_FOR'];
                } else {
                    $remote_host = "{$_SERVER['HTTP_X_FORWARDED_FOR']} through $remote_host";
                }
            }
        }

        $today = date("Y-m-d H:i:s");
        $pwd_string = "Logged in using password $password_number";
        if($password_number == 3)
            $pwd_string = "Logged in using LDAP password";

        $res = &  $db->query(
                        "INSERT INTO log (Username, Code, Level, Time, Page, RemoteHost, Comment) " .
                         "VALUES (\"$username\", $LOG_LOGIN, $LOG_LEVEL_ACCESS, \"$today\", " .
                         "\"$page\", \"$remote_host\", \"$pwd_string\")");
        if (DB::isError($res))
            die($res->getDebugInfo());
        $res = & $db->query("SELECT LAST_INSERT_ID() AS LogIndex");
        if (DB::isError($res))
            die($res->getDebugInfo());
        if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC) and $row['LogIndex'] != 0) { // Get new log index
            $_SESSION['LogIndex'] = $row['LogIndex'];
        } else {
            include "header.php";
            echo "     <p>Error appending to log!</p>\n"; // Somehow the login wasn't logged
            include "footer.php";
            exit();
        }
        $res = & $db->query(
                        "UPDATE log SET Session={$_SESSION['LogIndex']} WHERE LogIndex={$_SESSION['LogIndex']}");
        if (DB::isError($res))
            die($res->getDebugInfo());
    }
}

/* Log event (must include either comment or code */
function log_event($log_level, $page, $code = NULL, $comment = NULL, $set_log_index = 1) {
    global $LOG_LEVEL;
    global $db;
    global $username;

    $log_level = safe($log_level);
    $page = safe($page);
    $code = safe($code);

    if ($LOG_LEVEL >= $log_level) {
        $today = date("Y-m-d H:i:s");
        if ($comment == NULL || $comment == '') { // If comment is blank, set to NULL
            $comment = "NULL";
        } else {
            $comment = "'" . $db->escapeSimple($comment) . "'"; // If comment is not blank, put quotes around it
        }

        if (isset($_SERVER['REMOTE_HOST'])) {
            $remote_host = $_SERVER['REMOTE_HOST'];
        } else {
            $remote_host = $_SERVER['REMOTE_ADDR'];
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            if ($_SERVER['HTTP_X_FORWARDED_FOR'] != "unknown" &&
                isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                if ($remote_host == "10.88.0.1") {
                    $remote_host = $_SERVER['HTTP_X_FORWARDED_FOR'];
                } else {
                    $remote_host = "{$_SERVER['HTTP_X_FORWARDED_FOR']} through $remote_host";
                }
            }
        }

        if ($set_log_index == 1) {
            if (isset($_SESSION['LogIndex'])) {
                $res = &  $db->query(
                                "INSERT INTO log (Username, Code, Level, Comment, Time, Session, Page, RemoteHost) " .
                                     "VALUES (\"$username\", $code, $log_level, $comment, \"$today\", " .
                                     "{$_SESSION['LogIndex']}, \"$page\", \"$remote_host\")");
                if (DB::isError($res))
                    die($res->getDebugInfo());
            } else {
                $res = &  $db->query(
                                "INSERT INTO log (Username, Code, Level, Comment, Time, Page, RemoteHost) " .
                                 "VALUES (\"$username\", $code, $log_level, $comment, \"$today\", " .
                                 "\"$page\", \"$remote_host\")");
                if (DB::isError($res))
                    die($res->getDebugInfo());
                $res = & $db->query("SELECT LAST_INSERT_ID() AS LogIndex");
                if (DB::isError($res))
                    die($res->getDebugInfo());
                if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC) and $row['LogIndex'] != 0) { // Get new log index
                    $_SESSION['LogIndex'] = $row['LogIndex'];
                } else {
                    echo "     <p>Error appending to log!</p>\n"; // Somehow we were unable to add to log
                    include "footer.php";
                    exit();
                }
                $res = & $db->query(
                                "UPDATE log SET Session={$_SESSION['LogIndex']} WHERE LogIndex={$_SESSION['LogIndex']}");
                if (DB::isError($res))
                    die($res->getDebugInfo());
            }
        } else {
            $res = &  $db->query(
                            "INSERT INTO log (Username, Code, Level, Comment, Time, Session, Page, RemoteHost) " .
                             "VALUES (\"$username\", $code, $log_level, $comment, \"$today\", " .
                             "NULL, \"$page\", \"$remote_host\")");
            if (DB::isError($res))
                die($res->getDebugInfo());
        }
    }
}

/* Find correct directory for $assignment_index */
function &dbfuncGetDir($assignment_index, $dirname, $username) {
    global $UPLOAD_BASE_DIR;
    global $db;

    $assignment_index = safe($assignment_index);

    $remove_array = array(
            "!",
            "#",
            ":",
            "/",
            "\\",
            "\"",
            "<",
            ">",
            "?",
            "*",
            "|",
            "&",
            "@",
            "`"
    );

    $res = &  $db->query(
                    "SELECT year.Year, term.TermName, subject.Name, " .
                     "       term.TermNumber " .
                     "FROM  assignment, subject, year, term " .
                     "WHERE assignment.AssignmentIndex = $assignment_index " .
                     "AND   subject.SubjectIndex = assignment.SubjectIndex " .
                     "AND   year.YearIndex = subject.YearIndex " .
                     "AND   term.TermIndex = subject.TermIndex");
    if (DB::isError($res))
        die($res->getDebugInfo());
    if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) { // Get assignment
        $uname = str_replace($remove_array, "", $username);
        $year = str_replace($remove_array, "", "{$row['Year']}");
        $term = str_replace($remove_array, "",
                            "{$row['TermNumber']}. {$row['TermName']}");
        $sname = str_replace($remove_array, "", $row['Name']);
        $dirname = str_replace($remove_array, "", $dirname);
        $new_dirname = "$UPLOAD_BASE_DIR/$uname/$year/$term/$sname/$dirname";
        return $new_dirname;
    } else {
        print "<p>Assignment with index $assignment_index doesn't exist!</p>\n";
        exit(1);
    }
}
function dbfuncMkDir($assignment_index, $dirname) {
    global $LOG_ERROR;
    global $LOG_LEVEL_ERROR;
    global $db;

    $assignment_index = safe($assignment_index);

    $res = &  $db->query(
                    "SELECT subjectteacher.Username " .
                     "FROM assignment, subject, subjectteacher " .
                     "WHERE assignment.AssignmentIndex = $assignment_index " .
                     "AND   subject.SubjectIndex = assignment.SubjectIndex " .
                     "AND   subjectteacher.SubjectIndex = subject.SubjectIndex");
    if (DB::isError($res))
        die($res->getDebugInfo());
    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) { // Get username
        $dir = & dbfuncGetDir($assignment_index, $dirname, $row['Username']);
        if (is_dir($dir)) {
            continue;
        } else {
            $result = mkdir($dir, 0777, True);
            if ($result == False) {
                log_event($LOG_LEVEL_ERROR, "core/dbfunc.php", $LOG_ERROR,
                        "Unable to create $dir.");
                print "<p>Unable to create $dir.</p>\n";
            }
            chmod("$dir", 0777);
            chmod("$dir/..", 0777); // Class
            chmod("$dir/../..", 0777); // Term
            chmod("$dir/../../..", 0777); // Year
        }
    }
    return $result;
}
function dbfuncMoveDir($assignment_index, $old_dirname, $new_dirname) {
    global $LOG_ERROR;
    global $LOG_LEVEL_ERROR;
    global $db;

    $assignment_index = safe($assignment_index);

    $res = &  $db->query(
                    "SELECT subjectteacher.Username " .
                     "FROM assignment, subject, subjectteacher " .
                     "WHERE assignment.AssignmentIndex = $assignment_index " .
                     "AND   subject.SubjectIndex = assignment.SubjectIndex " .
                     "AND   subjectteacher.SubjectIndex = subject.SubjectIndex");
    if (DB::isError($res))
        die($res->getDebugInfo());
    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) { // Get username
        $old_dir = & dbfuncGetDir($assignment_index, $old_dirname, $row['Username']);
        $new_dir = & dbfuncGetDir($assignment_index, $new_dirname,
                                $row['Username']);
        if (is_dir($old_dir)) {
            $result = rename($old_dir, $new_dir);
        } else {
            return dbfuncMkDir($assignment_index, $new_dirname);
        }
        if ($result == False) {
            log_event($LOG_LEVEL_ERROR, "core/dbfunc.php", $LOG_ERROR,
                    "Unable to move $old_dir to $new_dir.");
            print "<p>Unable to move $old_dir to $new_dir.</p>\n";
        }
    }
    return $result;
}
function getNamesFromList($namelist) {
    $total = count($namelist);
    $count = 0;
    $name_string = "";
    if ($total == 0) {
        return "";
    } elseif ($total == 1) {
        foreach ( $namelist as $name ) {
            return $name;
        }
    } else {
        foreach ( $namelist as $name ) {
            $count ++;
            if ($count == $total) {
                $name_string .= " and";
            } elseif ($count > 1) {
                $name_string .= ",";
            }
            $name_string .= " $name";
        }
        return htmlspecialchars(trim($name_string), ENT_QUOTES);
    }
}


/* Class that contains all assignment information */
class Assignment {
    public $curve_type = null;
    public $max = null;
    public $ignore_zero = null;
    public $top_mark = null;
    public $bottom_mark = null;
    public $student_max = null;
    public $student_min = null;
    public $makeup_type_index = null;
    public $original_max = null;
    public $target_max = null;

    public function __construct($assignment_index) {
        global $db;

        /* Get assignment information */
        $query =    "SELECT CurveType, Max, IgnoreZero, TopMark, BottomMark, StudentMax, " .
                    "       StudentMin, MakeupTypeIndex, OriginalMax, TargetMax " .
                    "       FROM assignment LEFT OUTER JOIN makeup_type USING (MakeupTypeIndex) " .
                    "WHERE AssignmentIndex = $assignment_index";
        $res = &  $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo());

        if (!$row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            echo "ERROR: assignment has disappeared!";
            exit(0);
        }

        $this->curve_type        = $row['CurveType'];
        $this->max               = $row['Max'];
        $this->ignore_zero       = $row['IgnoreZero'];
        $this->top_mark          = $row['TopMark'];
        $this->bottom_mark       = $row['BottomMark'];
        $this->student_max       = $row['StudentMax'];
        $this->student_min       = $row['StudentMin'];
        $this->makeup_type_index = $row['MakeupTypeIndex'];
        $this->original_max      = $row['OriginalMax'];
        $this->target_max        = $row['TargetMax'];
    }
}

/* Return null if no average, otherwise return average for score */
function calculate_score_average($score, &$a) {
    global $MARK_ABSENT;
    global $MARK_EXEMPT;
    global $MARK_LATE;

    if(is_null($score) or $score == $MARK_ABSENT or $score == $MARK_EXEMPT)
        return null;

    if($score == $MARK_LATE)
        return 0;

    if($a->curve_type == 1) {
        return round(($score / $a->student_max) * 100);
    } elseif($a->curve_type == 2) {
        if($score == 0 and $a->ignore_zero == 1)
            return 0;

        return round(((($a->top_mark - $a->bottom_mark) / ($a->student_max - $a->student_min)) * $score) +
                     ((($a->top_mark * $a->student_min) - ($a->bottom_mark * $a->student_max)) /
                      ($a->student_min - $a->student_max)));
    } else { // Default is score out of maximum
        if($a->max == 0)
            return null;
        else
            return round(($score / $a->max) * 100);
    }
}

class Mark {
    public $average = null;
    public $makeup_avg = null;
    public $overall_avg = null;
    public $score = null;
    public $makeup_score = null;

    public function __construct($score, $makeup_score, &$a) {
        global $MARK_ABSENT;
        global $MARK_EXEMPT;
        global $MARK_LATE;

        $this->score = $score;
        $this->average = calculate_score_average($score, $a);

        /* If makeups aren't enabled, set overall average and bail */
        if(is_null($a->makeup_type_index)) {
            $this->overall_avg = $this->average;
            return;
        }

        $this->makeup_score = $makeup_score;
        $this->makeup_avg = calculate_score_average($makeup_score, $a);

        /* If either average is empty, set the other to be the overall average */
        if(is_null($this->average)) {
            $this->overall_avg = $this->makeup_avg;
            return;
        } elseif(is_null($this->makeup_avg)) {
            $this->overall_avg = $this->average;
            return;
        }

        /* If makeup has no effect, set overall to original average to avoid divide-by-zero */
        if($a->original_max == $a->target_max or $this->average >= $a->target_max) {
            $this->overall_avg = $this->average;
            return;
        }

        /* If we're below the original max, then apply straight function */
        if($this->average <= $a->original_max)
            $mult = ($a->target_max - 100) / ($a->original_max - $a->target_max);
        /* If we're above the original max, but below the target max, apply linear dropoff */
        else
            $mult = ($a->target_max - 100) / ($this->average - $a->target_max);

        $this->overall_avg = round(($this->average*$mult + $this->makeup_avg) / ($mult + 1));
    }
}

function update_marks($assignment_index) {
    global $db;
    global $MARK_LATE;

    $assignment_index = intval($assignment_index);

    /* Update assignment max and min score */
    $query =    "UPDATE assignment, (SELECT MAX(Score) AS MaxScore, MIN(Score) AS MinScore " .
                "                    FROM mark, assignment " .
                "                    WHERE mark.AssignmentIndex = $assignment_index " .
                "                    AND   assignment.AssignmentIndex = $assignment_index " .
                "                    AND   ((mark.Score >= 0 AND assignment.IgnoreZero = 0) " .
                "                            OR (mark.Score > 0 AND assignment.IgnoreZero = 1)) " .
                "                    AND   mark.Score IS NOT NULL " .
                "                    GROUP BY mark.AssignmentIndex) AS score " .
                "SET   assignment.StudentMax = score.MaxScore, " .
                "      assignment.StudentMin = score.MinScore " .
                "WHERE assignment.AssignmentIndex = $assignment_index ";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    $assignment = new Assignment($assignment_index);

    /* Get all marks for assignment and calculate averages */
    $query =    "SELECT MarkIndex, Username, Score, MakeupScore, Comment, AssignmentIndex FROM mark " .
                "WHERE AssignmentIndex = $assignment_index";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    if($res->numRows() > 0) {
        $new_query =    "REPLACE INTO mark (MarkIndex, Username, AssignmentIndex, Score, Comment, " .
                        "                   Percentage, OriginalPercentage, MakeupScore, " .
                        "                   MakeupPercentage) " .
                        "VALUES ";

        while ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            $mark = new Mark($row['Score'], $row['MakeupScore'], $assignment);
            if(is_null($row['Username'])) {
                $row['Username'] = "NULL";
            } else {
                $row['Username'] = "'" . safe($row['Username']) . "'";
            }
            if(is_null($row['Comment'])) {
                $row['Comment'] = "NULL";
            } else {
                $row['Comment'] = "'" . safe($row['Comment']) . "'";
            }
            foreach(array('AssignmentIndex', 'Score', 'MakeupScore') as $item) {
                if(is_null($row[$item]))
                    $row[$item] = "NULL";
            }
            if(is_null($mark->score)) $mark->score = "NULL";
            if(is_null($mark->makeup_score)) $mark->makeup_score = "NULL";
            if(is_null($mark->average)) $mark->average = "NULL";
            if(is_null($mark->overall_avg)) $mark->overall_avg = "NULL";
            if(is_null($mark->makeup_avg)) $mark->makeup_avg = "NULL";

            $new_query .=   "({$row['MarkIndex']}, {$row['Username']}, {$row['AssignmentIndex']}, " .
                            " {$row['Score']}, {$row['Comment']}, {$mark->overall_avg}, " .
                            " {$mark->average}, {$row['MakeupScore']}, {$mark->makeup_avg}),";
        }
        /* Replace last comma with semi-colon */
        $new_query = substr_replace($new_query, ";", -1, 1);
        /* Run query to update marks */
        $res = &  $db->query($new_query);
        if (DB::isError($res))
            die($res->getDebugInfo());
    }

    /* Calculate subject average for assignment */
    $query = "UPDATE assignment SET Average = -1 " .
             "WHERE AssignmentIndex = $assignment_index";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    $query = "UPDATE assignment, " .
             "   (SELECT (SUM(Percentage) / COUNT(AssignmentIndex)) AS Average, " .
             "           COUNT(AssignmentIndex) AS Count FROM mark " .
             "    WHERE AssignmentIndex = $assignment_index " .
             "    AND   Percentage >= 0 " .
             "    AND   Percentage IS NOT NULL " .
             "    GROUP BY AssignmentIndex) AS score " .
             "SET assignment.Average = score.Average " .
             "WHERE assignment.AssignmentIndex = $assignment_index " .
             "AND   (score.Count > 0 AND score.Count IS NOT NULL) ";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    /* Find subject index and update subject info */
    $query = "SELECT SubjectIndex FROM assignment " .
             "WHERE AssignmentIndex = $assignment_index ";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        update_subject($row['SubjectIndex']);
    }

    return true;
}

/*
 * function update_classterm_from_user($username, $term_index, $year_index) {
 * global $db;
 *
 * $query = "SELECT classlist.ClassIndex FROM class, classlist " .
 * "WHERE classlist.ClassIndex = class.ClassIndex " .
 * "AND class.YearIndex = $year_index " .
 * "AND classlist.Username = '$username'";
 * $res =& $db->query($query);
 * if(DB::isError($res)) die($res->getDebugInfo());
 *
 * if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
 * update_classterm($row['ClassIndex'], $term_index);
 * }
 * }
 */
function update_classterm_from_subject($subject_index) {
    global $db;

    $query = "SELECT classterm.ClassTermIndex FROM " .
             "       classlist, classterm, class, subject, subjectstudent " .
             "WHERE subject.SubjectIndex = $subject_index " .
             "AND   subjectstudent.SubjectIndex = subject.SubjectIndex " .
             "AND   classlist.Username = subjectstudent.Username " .
             "AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
             "AND   classterm.TermIndex = subject.TermIndex " .
             "AND   classterm.ClassIndex = class.ClassIndex " .
             "AND   class.YearIndex = subject.YearIndex " .
             "GROUP BY classterm.ClassIndex";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        update_classterm($row['ClassTermIndex']);
    }
}
function update_year_term($year_index, $term_index) {
    global $db;
    global $AVG_TYPE_PERCENT;

    $query = "SELECT ClassTermIndex FROM class, classterm " .
             "WHERE classterm.ClassIndex = class.ClassIndex " .
             "AND   class.YearIndex      = $year_index " .
             "AND   classterm.TermIndex  = $term_index";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        update_classterm($row['ClassTermIndex']);
    }
}
function update_classterm($classtermindex) {
    global $db;
    global $AVG_TYPE_PERCENT;
    global $CLASS_CONDUCT_TYPE_CALC;
    global $CLASS_CONDUCT_TYPE_PUN;

    /*
     * $query = "UPDATE classlist, classterm " .
     * " SET classlist.Average=-1, classlist.Rank=-1 " .
     * "WHERE classterm.TermIndex = $term_index " .
     * "AND classterm.ClassIndex = $class_index " .
     * "AND classterm.ClassTermIndex = classlist.ClassTermIndex ";
     * $res =& $db->query($query);
     * if(DB::isError($res)) die($res->getDebugInfo());
     */
    $query = "SELECT Username, ROUND(SUM(DistWeight * Average) / SUM(DistWeight)) AS Avg " .
             "     FROM " .
             "    ((SELECT classlist.Username, subject.SubjectTypeIndex, AVG(ROUND(subjectstudent.Average)) AS Average, " .
             "             get_weight(subject.SubjectIndex, class.ClassIndex, classlist.Username) AS DistWeight " .
             "        FROM class INNER JOIN classterm USING (ClassIndex) " .
             "                   INNER JOIN classlist USING (ClassTermIndex) " .
             "                   LEFT OUTER JOIN " .
             "                    (subject INNER JOIN subjectstudent USING (SubjectIndex))" .
             "                   ON (subject.TermIndex = classterm.TermIndex " .
             "                       AND subject.YearIndex = class.YearIndex " .
             "                       AND subject.AverageType = $AVG_TYPE_PERCENT " .
             "                       AND subjectstudent.Username = classlist.Username " .
             "                       AND subjectstudent.Average >= 0) " .
             "        WHERE classterm.ClassTermIndex  = $classtermindex " .
             "        GROUP BY subject.SubjectTypeIndex, classlist.Username " .
             "     ) " . "     UNION " .
             "     (SELECT classlist.Username, 0 AS SubjectTypeIndex, classlist.Conduct AS Average, 1.0 AS DistWeight " .
             "        FROM classlist, classterm " .
             "        WHERE classterm.ClassTermIndex = $classtermindex " .
             "        AND classlist.ClassTermIndex   = classterm.ClassTermIndex " .
             "        AND (classterm.ConductType     = $CLASS_CONDUCT_TYPE_CALC" .
             "          OR classterm.ConductType     = $CLASS_CONDUCT_TYPE_PUN)" .
             "        AND classlist.Conduct         >= 0" . "     )" .
             "    ) AS ctgrade GROUP BY Username";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        if (is_null($row['Avg']))
            $row['Avg'] = "-1";

        $query = "UPDATE classlist " . "SET classlist.Average = {$row['Avg']} " .
                 "WHERE classlist.Username  = '{$row['Username']}' " .
                 "AND   classlist.ClassTermIndex = $classtermindex ";
        $nres = &  $db->query($query);
        if (DB::isError($nres))
            die($nres->getDebugInfo());
    }

    // Update class average
    $query = "UPDATE classterm SET classterm.Average=-1 " .
             "WHERE classterm.ClassTermIndex = $classtermindex ";
    $nres = &  $db->query($query);
    if (DB::isError($nres))
        die($nres->getDebugInfo());

    $query = "UPDATE classterm, " .
             " (SELECT ClassTermIndex, AVG(Average) AS ClassAverage " .
             "  FROM classlist " . "  WHERE Average >= 0" .
             "  AND   ClassTermIndex = $classtermindex " .
             "  GROUP BY ClassTermIndex) AS ctaverage " .
             "SET    classterm.Average = ctaverage.ClassAverage " .
             "WHERE  ctaverage.ClassTermIndex = classterm.ClassTermIndex " .
             "AND    classterm.ClassTermIndex = $classtermindex";
    $nres = &  $db->query($query);
    if (DB::isError($nres))
        die($nres->getDebugInfo());

    $query = "SELECT classlist.ClassListIndex, classlist.Username, classlist.Rank, classlist.Average FROM classlist " .
             "WHERE classlist.ClassTermIndex = $classtermindex " .
             "AND   classlist.Average        >= 0 " . "ORDER BY Average DESC";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    /* Set class ranking */
    $rank = 1;
    $prevmark = 0;
    $count = 0;
    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        if ($prevmark > round($row['Average'])) {
            $rank += $count;
            $count = 1;
        } else {
            $count += 1;
        }
        $prevmark = round($row['Average']);

        if ($row['Rank'] != $rank) {
            $query = "UPDATE classlist SET Rank=$rank " .
                 "WHERE ClassListIndex = {$row['ClassListIndex']}";
            $nres = &  $db->query($query);
            if (DB::isError($nres))
                die($nres->getDebugInfo());
        }
    }
}
function update_subject($subject_index) {
    global $db;
    global $MARK_LATE;
    global $AVG_TYPE_GRADE;

    $subject_index = safe($subject_index);

    $query = "SELECT AverageType, AverageTypeIndex FROM subject WHERE SubjectIndex = $subject_index";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());
    if (! ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC))) {
        return false;
    }

    $avg_type = $row["AverageType"];
    $avg_type_index = $row["AverageTypeIndex"];

    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    /* Calculate student's current average in subject */
    $query = "SELECT MAX(Average) AS Average, Username FROM " .
             "   ((SELECT" .
             "     ROUND(SUM((Mark * CategoryWeight * 100) / Weight) / SUM(CategoryWeight)) AS Average, Username FROM" .
             "      (SELECT" .
             "        SUM(mark.Percentage * assignment.Weight) AS Mark, " .
             "        SUM(assignment.Weight) * 100 AS Weight, " .
             "        IF(categorylist.Weight IS NULL, 1, categorylist.Weight) AS CategoryWeight, " .
             "        mark.Username FROM " .
             "          assignment INNER JOIN subjectstudent USING (SubjectIndex) " .
             "          LEFT OUTER JOIN (categorylist INNER JOIN category USING (CategoryIndex)) USING (CategoryListIndex) " .
             "          LEFT OUTER JOIN mark ON (subjectstudent.Username = mark.Username AND assignment.AssignmentIndex = mark.AssignmentIndex) " .
             "       WHERE assignment.SubjectIndex = $subject_index" .
             "       AND   assignment.Agenda       = 0 " .
             "       AND   assignment.Hidden       = 0 " .
             "       AND   mark.AssignmentIndex    = assignment.AssignmentIndex " .
             "       AND   mark.Percentage         >= 0 " .
             "       AND   assignment.Weight       > 0 " .
             "       GROUP BY subjectstudent.Username, category.CategoryIndex)" .
             "     AS category_total GROUP BY Username) " .
             "     UNION " .
             "    (SELECT -1 AS Average, Username FROM subjectstudent" .
             "     WHERE subjectstudent.SubjectIndex = $subject_index)) AS pscore" .
             "    GROUP BY Username " . "    ORDER BY Username ";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());
    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        $query = "UPDATE subjectstudent SET Average={$row['Average']} " .
                 "WHERE SubjectIndex = $subject_index " .
                 "AND Username = '{$row['Username']}'";
        $nres = &  $db->query($query);
        if (DB::isError($nres))
            die($nres->getDebugInfo());
    }
    $query = "SELECT Username, Average, Rank FROM subjectstudent " .
             "WHERE SubjectIndex = $subject_index " . "AND   Average >= 0 " .
             "ORDER BY Average DESC";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    /* Set subject ranking */
    $rank = 1;
    $prevmark = 0;
    $count = 0;
    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        if ($prevmark > round($row['Average'])) {
            $rank += $count;
            $count = 1;
        } else {
            $count += 1;
        }
        $prevmark = round($row['Average']);
        if ($rank != $row['Rank']) {
            $query = "UPDATE subjectstudent SET Rank=$rank " .
                 "WHERE SubjectIndex = $subject_index " .
                 "AND Username = '{$row['Username']}'";
            $nres = &  $db->query($query);
            if (DB::isError($nres))
                die($nres->getDebugInfo());
        }
    }

    /* Calculate subject average */
    $query = "UPDATE subject SET Average = -1 " .
             "WHERE SubjectIndex = $subject_index";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    $query = "UPDATE subject, " .
             "   (SELECT AVG(subjectstudent.Average) AS Average, " .
             "           COUNT(subjectstudent.Average) AS Count " .
             "           FROM subjectstudent " .
             "    WHERE subjectstudent.SubjectIndex = $subject_index " .
             "    AND   subjectstudent.Average >= 0) AS score " .
             "SET subject.Average = score.Average " .
             "WHERE subject.SubjectIndex = $subject_index " .
             "AND   (score.Count > 0 AND score.Count IS NOT NULL)";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    update_classterm_from_subject($subject_index);

    if ($avg_type == $AVG_TYPE_GRADE) {
        $query = "UPDATE subjectstudent, " . "       (SELECT * FROM " .
             "          (SELECT Username, NonmarkIndex, MinScore, Display FROM nonmark_index, subjectstudent " .
             "           WHERE (nonmark_index.MinScore <= subjectstudent.Average OR nonmark_index.MinScore IS NULL) " .
             "           AND nonmark_index.NonMarkTypeIndex = $avg_type_index " .
             "           AND subjectstudent.SubjectIndex = $subject_index " .
             "           AND subjectstudent.Average != -1 " .
             "           ORDER BY MinScore DESC) AS score1 " .
             "        GROUP BY Username) AS score " .
             "SET subjectstudent.Average = score.NonMarkIndex " .
             "WHERE subjectstudent.SubjectIndex = $subject_index " .
             "AND   subjectstudent.Username = score.Username";
        $res = &  $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo());
    }
    return true;
}

/* Update all student conduct marks for year and term */
function update_conduct_year_term($year, $term) {
    global $db;

    $query = "SELECT classlist.Username FROM classlist, classterm, class " .
             "WHERE classlist.ClassTermIndex = classterm.ClassTermIndex " .
             "AND   classterm.TermIndex = $term " .
             "AND   classterm.ClassIndex = class.ClassIndex " .
             "AND   class.YearIndex = $year ";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());
    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        update_conduct_mark($row['Username'], $year, $term);
    }
}

/*
 * function update_conduct_input($class_index, $term_index) {
 * global $db;
 * global $CONDUCT_TYPE_PERCENT;
 * global $CLASS_CONDUCT_TYPE_CALC;
 *
 * $query = "UPDATE classterm, classlist " .
 * " SET classterm.Conduct=-1 " .
 * "WHERE classterm.TermIndex = $term_index " .
 * "AND classterm.ClassListIndex = classlist.ClassListIndex " .
 * "AND classlist.ClassIndex = $class_index";
 * $res =& $db->query($query);
 * if(DB::isError($res)) die($res->getDebugInfo());
 *
 * $query = "SELECT classlist.Username, classlist.ClassListIndex, class.YearIndex, " .
 * " class_term.ConductType " .
 * " FROM classlist, class, class_term " .
 * "WHERE classlist.ClassIndex = $class_index " .
 * "AND class.ClassIndex = $class_index " .
 * "AND class_term.ClassIndex = $class_index " .
 * "AND class_term.TermIndex = $term_index";
 * $res =& $db->query($query);
 * if(DB::isError($res)) die($res->getDebugInfo());
 *
 * while($row =& $res->fetchRow(DB_FETCHMODE_ASSOC) and $row['ConductType'] == $CLASS_CONDUCT_TYPE_CALC) {
 * $query = "UPDATE classterm, " .
 * " (SELECT SUM(subject_weight.DistWeight * subjectstudent.Conduct) * 100 / " .
 * " SUM(subject_weight.DistWeight * 100) AS Avg " .
 * " FROM subjectstudent, subject, " .
 * " (SELECT subjecttype.SubjectTypeIndex, " .
 * " subjecttype.Weight / COUNT(subject.SubjectIndex) " .
 * " AS DistWeight, " .
 * " subjecttype.Weight FROM subjecttype, subject, subjectstudent " .
 * " WHERE subject.YearIndex = {$row['YearIndex']} " .
 * " AND subject.TermIndex = $term_index " .
 * " AND subject.ConductType = $CONDUCT_TYPE_PERCENT " .
 * " AND subjectstudent.subjectIndex = subject.SubjectIndex " .
 * " AND subjectstudent.Conduct >= 0 " .
 * " AND subjectstudent.Username = '{$row['Username']}' " .
 * " AND subjecttype.SubjectTypeIndex = subject.SubjectTypeIndex " .
 * " AND subjecttype.Weight IS NOT NULL " .
 * " GROUP BY subjecttype.SubjectTypeIndex) AS subject_weight " .
 * " WHERE subject.YearIndex = {$row['YearIndex']} " .
 * " AND subject.TermIndex = $term_index " .
 * " AND subject.ConductType = $CONDUCT_TYPE_PERCENT " .
 * " AND subjectstudent.subjectIndex = subject.SubjectIndex " .
 * " AND subjectstudent.Conduct >= 0 " .
 * " AND subjectstudent.Username = '{$row['Username']}' " .
 * " AND subject_weight.SubjectTypeIndex = subject.SubjectTypeIndex " .
 * " GROUP BY subjectstudent.Username) AS ctinfo " .
 * "SET classterm.Conduct = ctinfo.Avg " .
 * "WHERE classterm.ClassListIndex = '{$row['ClassListIndex']}' " .
 * "AND classterm.TermIndex = $term_index";
 * $nres =& $db->query($query);
 * if(DB::isError($nres)) die($nres->getDebugInfo());
 * }
 * }
 */
function update_conduct_mark($studentusername, $year = -1, $term = -1) {
    global $yearindex;
    global $termindex;
    global $db;

    if ($year == - 1)
        $year = $yearindex;
    if ($term == - 1)
        $term = $termindex;
    $query = "SELECT class.HasConduct, term.HasConduct AS TermConduct, " .
             "       COUNT(subjectstudent.SubjectIndex) AS SubjectCount, " .
             "       classterm.ClassTermIndex FROM " .
             "       class, classterm, classlist, term, subject, subjectstudent " .
             "WHERE class.YearIndex = subject.YearIndex " .
             "AND   classterm.ClassIndex = class.ClassIndex " .
             "AND   classterm.TermIndex = term.TermIndex " .
             "AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
             "AND   classlist.Username = '$studentusername' " .
             "AND   term.TermIndex = $term " . "AND   subject.YearIndex = $year " .
             "AND   subject.TermIndex = term.TermIndex " .
             "AND   subject.SubjectIndex = subjectstudent.SubjectIndex " .
             "AND   subjectstudent.Username = '$studentusername' " .
             "GROUP BY subjectstudent.Username";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC) and $row['HasConduct'] == 1 and
         $row['TermConduct'] == 1) {
        $classterm = $row['ClassTermIndex'];

        $query = "SELECT IF((sum(disciplineweight.DisciplineWeight) > 100), 0, " .
                 "          (100 - sum(disciplineweight.DisciplineWeight))) AS Score " .
                 "       FROM discipline, disciplineweight " .
                 "WHERE discipline.DisciplineWeightIndex = disciplineweight.DisciplineWeightIndex " .
                 "AND   disciplineweight.YearIndex = $year " .
                 "AND   disciplineweight.TermIndex = $term " .
                 "AND   discipline.Username        = '$studentusername' " .
                 "GROUP BY discipline.Username";
        $res = &  $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo());

        if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            $score = $row['Score'];
        } else {
            $score = 100;
        }

        $query = "SELECT classlist.Conduct FROM classlist " .
                 "WHERE Username       = '$studentusername' " .
                 "AND   ClassTermIndex = $classterm";
        $res = &  $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo());

        $check_conduct = - 1;
        if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            $check_conduct = $row['Conduct'];
        }

        if ($check_conduct != $score) {
            $query = "UPDATE classlist SET Conduct=$score " .
                 "WHERE Username       = '$studentusername' " .
                 "AND   ClassTermIndex = $classterm";
            $res = &  $db->query($query);
            if (DB::isError($res))
                die($res->getDebugInfo());

            $query = "UPDATE classterm, " .
                     " (SELECT ClassTermIndex, AVG(Conduct) AS ClassAverage " .
                     "  FROM classlist " . "  WHERE Conduct >= 0 " .
                     "  GROUP BY ClassTermIndex) AS ctaverage " .
                     "SET    classterm.Conduct = ctaverage.ClassAverage " .
                     "WHERE  ctaverage.ClassTermIndex = classterm.ClassTermIndex " .
                     "AND    classterm.ClassTermIndex = $classterm";
            $res = &  $db->query($query);
            if (DB::isError($res))
                die($res->getDebugInfo());
        }
    } else {
        $classterm = $row['ClassTermIndex'];

        if (! isset($classterm) or is_null($classterm)) {
            return;
        }

        $query = "SELECT classlist.Conduct FROM classlist " .
                 "WHERE Username       = '$studentusername' " .
                 "AND   ClassTermIndex = $classterm";
        $res = &  $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo());

        $check_conduct = 100;
        if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            $check_conduct = $row['Conduct'];
        }

        if ($check_conduct != - 1) {
            $query = "UPDATE classlist SET Conduct=-1 " .
                 "WHERE ClassTermIndex = $classterm";
            $res = &  $db->query($query);
            if (DB::isError($res))
                die($res->getDebugInfo());

            $query = "UPDATE classterm SET Conduct=-1 " .
                     "WHERE ClassTermIndex = $classterm";
            $res = &  $db->query($query);
            if (DB::isError($res))
                die($res->getDebugInfo());
        }
    }
}
function dbfuncGetPhoneRLZ() {
    /* Set global parameters */
    global $db;

    /* Run query to extract information from "user" table */
    $res = & $db->query(
                    "SELECT PhoneRLZ FROM currentinfo ORDER BY InputDate DESC LIMIT 1");
    if (DB::isError($res))
        die($res->getDebugInfo());

    if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        if ($row['PhoneRLZ'] == 1) {
            return true;
        }
    }
    return false;
}
function dbfuncGetPhonePrefix() {
    /* Set global parameters */
    global $db;
    global $username;

    /* Run query to extract information from "user" table */
    $res = & $db->query(
                    "SELECT PhonePrefix FROM currentinfo ORDER BY InputDate DESC LIMIT 1");
    if (DB::isError($res))
        die($res->getDebugInfo());

    if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        return $row['PhonePrefix'];
    }
    return "";
}
function &get_comment($username, $comment_index) {
    /* Set global parameters */
    global $db;
    global $yearindex;
    global $termindex;

    $query = "SELECT user.FirstName, user.Surname, user.Gender, class.Grade, " .
             "       comment.Comment, comment.Strength FROM user, comment, class, classterm, classlist " .
             "WHERE user.Username            = '$username' " .
             "AND   comment.CommentIndex     = $comment_index " .
             "AND   classlist.Username       = user.Username " .
             "AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
             "AND   classterm.TermIndex      = $termindex " .
             "AND   classterm.ClassIndex     = class.ClassIndex " .
             "AND   class.YearIndex          = $yearindex ";
    $res = & $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    if (! $row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        return false;
    }

    $grade = $row['Grade'];

    if (strtolower($row['Gender']) == 'm') {
        $heshe = "he";
        $himher = "him";
        $hisher = "his";
    } else {
        $heshe = "she";
        $himher = "her";
        $hisher = "her";
    }

    $comment = $row['Comment'];
    $comment = str_replace("[Name]", $row['FirstName'], $comment);
    $comment = str_replace("[NAME]", $row['FirstName'], $comment);
    $comment = str_replace("[name]", $row['FirstName'], $comment);
    $comment = str_replace("[FullName]",
                        "{$row['FirstName']} {$row['Surname']}", $comment);
    $comment = str_replace("[FULLNAME]",
                        "{$row['FirstName']} {$row['Surname']}", $comment);
    $comment = str_replace("[fullname]",
                        "{$row['FirstName']} {$row['Surname']}", $comment);
    $comment = str_replace("[Fullname]",
                        "{$row['FirstName']} {$row['Surname']}", $comment);
    $comment = str_replace("[him/her]", $himher, $comment);
    $comment = str_replace("[Him/her]", ucfirst($himher), $comment);
    $comment = str_replace("[Him/Her]", ucfirst($himher), $comment);
    $comment = str_replace("[he/she]", $heshe, $comment);
    $comment = str_replace("[He/she]", ucfirst($heshe), $comment);
    $comment = str_replace("[He/She]", ucfirst($heshe), $comment);
    $comment = str_replace("[his/her]", $hisher, $comment);
    $comment = str_replace("[His/her]", ucfirst($hisher), $comment);
    $comment = str_replace("[His/Her]", ucfirst($hisher), $comment);
    $comment = str_replace("[Grade]", strval($grade), $comment);
    $comment = str_replace("[grade]", strval($grade), $comment);
    $comment = str_replace("[GRADE]", strval($grade), $comment);
    $comment = str_replace("[NextGrade]", strval($grade + 1), $comment);
    $comment = str_replace("[Nextgrade]", strval($grade + 1), $comment);
    $comment = str_replace("[nextgrade]", strval($grade + 1), $comment);
    $comment = str_replace("[NEXTGRADE]", strval($grade + 1), $comment);

    return array(
            $comment,
            $row['Strength']
    );
}
function &htmlize_comment($comment) {
    $comment = str_replace("\r\n", "<br>", $comment);
    return $comment;
}
function &unhtmlize_comment($comment) {
    $comment = str_replace("<br>", "\r\n", $comment);
    return $comment;
}
function format_mark($mark, $type, $mark_type = 0) {
    global $AVG_TYPE_NONE;
    global $AVG_TYPE_INDEX;
    global $AVG_TYPE_PERCENT;
    global $AVG_TYPE_GRADE;

    global $CLASS_AVG_TYPE_NONE;
    global $CLASS_AVG_TYPE_INDEX;
    global $CLASS_AVG_TYPE_PERCENT;
    global $CLASS_AVG_TYPE_CALC;
    global $CLASS_AVG_TYPE_GRADE;

    if (($mark_type == 1 and
         ($type == $CLASS_AVG_TYPE_PERCENT or $type == $CLASS_AVG_TYPE_CALC)) or
         ($mark_type == 0 and ($type == $AVG_TYPE_PERCENT))) {
        if ($mark == "-") {
            $score = "-";
        } else {
            $scorestr = round($mark);

            if ($scorestr < 60) {
                $color = "#CC0000";
            } elseif ($scorestr < 75) {
                $color = "#666600";
            } elseif ($scorestr < 90) {
                $color = "#000000";
            } else {
                $color = "#339900";
            }
            $score = "<span style='color: $color'>$scorestr</span>";
        }
    } else {
        $score = $mark;
    }

    return $score;
}

function gen_members($group_id, &$members, &$group_ids) {
    global $db;

    if(in_array($group_id, $group_ids))
        return;

    $group_ids[] = $group_id;

    $query =    "SELECT Member FROM groupmem, groups, grouptype " .
                "WHERE groups.GroupID='$group_id' " .
                "AND   groupmem.GroupID=groups.GroupID " .
                "AND   grouptype.GroupTypeID=groups.GroupTypeID " .
                "AND   grouptype.PrimaryGroupType=0";
    $res = & $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    # Todo: Allow exclusion as well as inclusion based on priority
    while ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        if(substr($row['Member'], 0, 1) == "@") {
            gen_members(substr($row['Member'], 1), $members, $group_ids);
        } else {
            if(!in_array($row['Member'], $members)) {
                $members[] = $row['Member'];
            }
        }
    }

    return;
}

function gen_group_members($group_id) {
    global $db;

    $members = array();
    $group_ids = array();

    gen_members($group_id, $members, $group_ids);

    $query = "SELECT GroupGenMemberIndex, Username FROM groupgenmem WHERE GroupID='$group_id'";
    $res = & $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    /* Remove members who have been removed from group */
    while ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        echo "<p>$username</p>\n";
        if(!in_array($row['Username'], $members)) {
            $query = "DELETE FROM groupgenmem WHERE GroupGenMemberIndex={$row['GroupGenMemberIndex']}";
            $nres = & $db->query($query);
            if (DB::isError($nres))
                die($nres->getDebugInfo());
        }
    }

    /* Add new members into group */
    foreach($members as $uname) {
        $query = "SELECT GroupGenMemberIndex, Username FROM groupgenmem WHERE GroupID='$group_id' AND Username='$uname'";
        $res = & $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo());

        if($res->numRows() == 0) {
            $query = "INSERT INTO groupgenmem (Username, GroupID, IDUsername) VALUES ('$uname', '$group_id', '{$group_id}:{$uname}')";
            $nres = & $db->query($query);
            if (DB::isError($nres))
                die($nres->getDebugInfo());
        }
    }
}

function update_parent_members($member, $group_id) {
    global $db;

    $query =    "SELECT groups.GroupID FROM groupmem, groups, grouptype " .
                "WHERE groupmem.Member = CONCAT('@', '$group_id') " .
                "AND   groups.GroupID = groupmem.GroupID " .
                "AND   grouptype.GroupTypeID = groups.GroupTypeID " .
                "AND   grouptype.PrimaryGroupType = 0 ";
    $res = & $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    while ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        add_member_to_groupgen($member, safe($row['GroupID']));
        $query =    "UPDATE groups, " .
                    "(SELECT groups.GroupID, COUNT(groupgenmem.Username) AS RealUserCount FROM groups, groupgenmem " .
                    " WHERE groupgenmem.GroupID = groups.GroupID " .
                    " AND groups.GroupID = '{$row['GroupID']}' " .
                    " GROUP BY groups.GroupID) AS oldgroup " .
                    "SET groups.RealUserCount = oldgroup.RealUserCount " .
                    "WHERE groups.GroupID = oldgroup.GroupID ";

        $nres = & $db->query($query);
        if (DB::isError($nres))
            die($nres->getDebugInfo());

        update_parent_members($member, safe($row['GroupID']));
    }
}

function add_member_to_groupgen($member, $group_id) {
    global $db;

    /* If new group member is also a group, add all members of the new group to the parent */
    if(substr($member, 0, 1) == "@") {
        $query =    "SELECT groupmem.Member FROM groupmem " .
                    "WHERE CONCAT('@', groupmem.GroupID) = '$member'";
        $res = & $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo());

        while ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            add_member_to_groupgen(safe($row['Member']), $group_id);
        }
    } else {
        $query = "SELECT Username FROM groupgenmem WHERE GroupID='$group_id' AND Username='$member'";
        $res = & $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo());

        if($res->numRows() == 0) {
            $query = "INSERT INTO groupgenmem (Username, GroupID, IDUsername) VALUES ('$member', '$group_id', '{$group_id}:{$member}')";
            $res = & $db->query($query);
            if (DB::isError($res))
                die($res->getDebugInfo());
        }
    }
}

function remove_member_from_groupgen($member, $group_id) {
    global $db;

    /* If removed group member is also a group, remove all members of the group from the parent */
    if(substr($member, 0, 1) == "@") {
        $query =    "SELECT groupmem.Member FROM groupmem " .
                    "WHERE CONCAT('@', groupmem.GroupID) = '$member'";
        $res = & $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo());

        while ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            add_member_to_groupgen(safe($row['Member']), $group_id);
        }
    } else {
        $query = "SELECT Username FROM groupgenmem WHERE GroupID='$group_id' AND Username='$member'";
        $res = & $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo());

        if($res->numRows() > 0) {
            $query = "DELETE FROM groupgenmem WHERE IDUsername='{$group_id}:{$member}'";
            $res = & $db->query($query);
            if (DB::isError($res))
                die($res->getDebugInfo());
        }
    }
}

function add_member_to_group($member, $group_id) {
    global $db;

    $query =    "SELECT PrimaryGroupType FROM groups, grouptype " .
                "WHERE groups.GroupID = '$group_id' " .
                "AND   grouptype.GroupTypeID = groups.GroupTypeID ";
    $res = & $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    if (!$row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        die("Unable to find group $group_id");
    }
    $is_primary = false;
    if($row['PrimaryGroupType'] == 1)
        $is_primary = true;

    if($is_primary and $member[0] != '@') {
        die("Unable to add regular user to top-level group.  Only other groups can be added to top-level groups.  Please go back and fix the problem.");
    }

    $query = "SELECT Member FROM groupmem WHERE GroupID='$group_id' AND Member='$member'";
    $res = & $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    if($res->numRows() == 0) {
        $query =    "INSERT INTO groupmem (Member, GroupID) VALUES ('$member', '$group_id')";
        $res = & $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo());

        $query =    "UPDATE groups, " .
                    "(SELECT groups.GroupID, COUNT(groupmem.Member) AS MemberCount FROM groups, groupmem " .
                    " WHERE groupmem.GroupID = groups.GroupID " .
                    " AND groups.GroupID = '$group_id' " .
                    " GROUP BY groups.GroupID) AS oldgroup " .
                    "SET groups.MemberCount = oldgroup.MemberCount " .
                    "WHERE groups.GroupID = oldgroup.GroupID ";
        $res = & $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo());
    }
    if(!$is_primary) {
        add_member_to_groupgen($member, $group_id);
        $query =    "UPDATE groups, " .
                    "(SELECT groups.GroupID, COUNT(groupgenmem.Username) AS RealUserCount FROM groups, groupgenmem " .
                    " WHERE groupgenmem.GroupID = groups.GroupID " .
                    " AND groups.GroupID = '$group_id' " .
                    " GROUP BY groups.GroupID) AS oldgroup " .
                    "SET groups.RealUserCount = oldgroup.RealUserCount " .
                    "WHERE groups.GroupID = oldgroup.GroupID ";
        $res = & $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo());

        update_parent_members($member, $group_id);
    }
}

function remove_member_from_group($member, $group_id) {
    global $db;

    $query = "SELECT Member FROM groupmem WHERE GroupID='$group_id' AND Member='$member'";
    $res = & $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    if($res->numRows() > 0) {
        $query =    "DELETE FROM groupmem WHERE Member='$member' AND GroupID='$group_id'";
        $res = & $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo());

        $query =    "UPDATE groups, " .
                    "(SELECT groups.GroupID, COUNT(groupmem.Member) AS MemberCount FROM groups, groupmem " .
                    " WHERE groupmem.GroupID = groups.GroupID " .
                    " AND groups.GroupID = '$group_id' " .
                    " GROUP BY groups.GroupID) AS oldgroup " .
                    "SET groups.MemberCount = oldgroup.MemberCount " .
                    "WHERE groups.GroupID = oldgroup.GroupID ";
        $res = & $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo());
    }
    if(!$is_primary) {
        remove_member_from_groupgen($member, $group_id);
        $query =    "UPDATE groups, " .
                    "(SELECT groups.GroupID, COUNT(groupgenmem.Username) AS RealUserCount FROM groups, groupgenmem " .
                    " WHERE groupgenmem.GroupID = groups.GroupID " .
                    " AND groups.GroupID = '$group_id' " .
                    " GROUP BY groups.GroupID) AS oldgroup " .
                    "SET groups.RealUserCount = oldgroup.RealUserCount " .
                    "WHERE groups.GroupID = oldgroup.GroupID ";
        $res = & $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo());

        update_parent_members($member, $group_id);
    }
}

function crypto_rand_secure($min, $max) {
        $range = $max - $min;
        if ($range == 0) return $min; // not so random...
        $log = log($range, 2);
        $bytes = (int) ($log / 8) + 1; // length in bytes
        $bits = (int) $log + 1; // length in bits
        $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes, $s)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd >= $range);
        return $min + $rnd;
}

function generate_password($count, &$words) {
    if(count($words) == 0)
        return "";

    $origpwd = "";
    for($x = 0; $x < $count; $x++) {
        $y = crypto_rand_secure(0, count($words)-1);
        $origpwd .= " {$words[$y]}";
    }
    $origpwd = trim($origpwd);
    return $origpwd;
}

function makeup_add_students_lower_than($makeup_assignment_index, $aidx, $min, $max, $mandatory=1) {
    global $db;
    global $username;

    $query =    "SELECT Username, Percentage FROM mark " .
                "WHERE AssignmentIndex=$aidx " .
                "AND   Percentage >= 0 " .
                "AND   Percentage >= $min " .
                "AND   Percentage < $max";
    $res = & $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    while ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $query =    "SELECT MakeupUserIndex FROM makeup_user " .
                    "WHERE Username='{$row['Username']}' " .
                    "AND   MakeupAssignmentIndex=$makeup_assignment_index " .
                    "AND   Mandatory=$mandatory";
        $nres = & $db->query($query);
        if (DB::isError($nres))
            die($nres->getDebugInfo());
        if($nres->numRows() != 0)
            continue;

        if($mandatory == 1) {
            $request_username = "'$username'";
            $request_time = "NOW()";
            $requested = 1;
        } else {
            $request_username = "NULL";
            $request_time = "NULL";
            $requested = 0;
        }

        $query =    "INSERT INTO makeup_user (MakeupAssignmentIndex, Username, " .
                    "                         Requested, RequestUsername, " .
                    "                         RequestTime, Mandatory) " .
                    "                 VALUES ($makeup_assignment_index, '{$row['Username']}', " .
                    "                         $requested, $request_username, " .
                    "                         $request_time, $mandatory)";
        $nres = & $db->query($query);
        if (DB::isError($nres))
            die($nres->getDebugInfo());
    }

    // Remove students who are no longer eligible for makeups
    $query =    "DELETE makeup_user FROM makeup_user, (SELECT makeup_user.MakeupUserIndex FROM " .
                "       makeup_user INNER JOIN makeup_assignment " .
                "         ON  makeup_user.MakeupAssignmentIndex=$makeup_assignment_index " .
                "         AND makeup_assignment.MakeupAssignmentIndex=makeup_user.MakeupAssignmentIndex " .
                "         AND makeup_user.Mandatory=$mandatory " .
                "       LEFT OUTER JOIN mark " .
                "         ON  mark.AssignmentIndex=makeup_assignment.AssignmentIndex " .
                "         AND mark.Username=makeup_user.Username " .
                "         AND mark.Percentage >= 0 " .
                "         AND mark.Percentage >= $min " .
                "         AND mark.Percentage < $max " .
                " WHERE mark.Username IS NULL) AS remove_users " .
                "WHERE makeup_user.MakeupUserIndex=remove_users.MakeupUserIndex";
    $res = & $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());
}

// Add mandatory students to makeup
function makeup_add_students($makeup_assigment_index, $aidx, $mandatory_lower, $optional_lower) {
    makeup_add_students_lower_than($makeup_assigment_index, $aidx, 0, $mandatory_lower, 1);
    makeup_add_students_lower_than($makeup_assigment_index, $aidx, $mandatory_lower, $optional_lower, 0);
}

function makeup_remove_students($makeup_assignment_index) {
    global $db;

    $query = "DELETE FROM makeup_user WHERE MakeupAssignmentIndex=$makeup_assignment_index";
    $res = & $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    $query = "DELETE FROM makeup_assignment WHERE MakeupAssignmentIndex=$makeup_assignment_index";
    $res = & $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());
}

function clean_vals($var, $base64=False) {
    if(is_array($var)) {
        $new_var = array();
        foreach($var as $key=>$value) {
            $key = clean_vals($key, False);
            $new_var[$key] = clean_vals($value, $base64);
        }
    } else {
        $new_var = $var;
        if($base64)
            $new_var = base64_decode($new_var);

        // Remove javascript:
        $new_var = str_ireplace("javascript:", "", $new_var);

        // Remove tag braces
        $new_var = str_ireplace("<", "&lt;", $new_var);
        $new_var = str_ireplace(">", "&gt;", $new_var);

        // Re-insert allowed tags
        $new_var = str_ireplace("&lt;br&gt;>", "<br />", $new_var);
        $new_var = str_ireplace("&lt;br /&gt;", "<br />", $new_var);
        $new_var = str_ireplace("&lt;br/&gt;>", "<br />", $new_var);
        $new_var = str_ireplace("&lt;p&gt;", "<p>", $new_var);
        $new_var = str_ireplace("&lt;/p&gt;", "</p>", $new_var);
        $new_var = str_ireplace("&lt;ol&gt;", "<ol>", $new_var);
        $new_var = str_ireplace("&lt;/ol&gt;", "</ol>", $new_var);
        $new_var = str_ireplace("&lt;ul&gt;", "<ul>", $new_var);
        $new_var = str_ireplace("&lt;/ul&gt;", "</ul>", $new_var);

        if($base64)
            $new_var = base64_encode($new_var);
    }
    return $new_var;
}

function class_remove_student($username, $classtermindex) {
    global $db;

    $query =    "DELETE FROM classlist " .
                "WHERE Username       = '$username' " .
                "AND   ClassTermIndex = $classtermindex";
    $res =& $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    return True;
}

function subject_remove_student($username, $subjectindex) {
    global $db;

    $query =    "DELETE mark FROM assignment INNER JOIN mark USING (AssignmentIndex) " .
                "WHERE assignment.SubjectIndex = $subjectindex " .
                "AND   mark.Username           = '$username'";
    $res =& $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    $query =    "DELETE FROM subjectstudent " .
                "WHERE Username     = '$username' " .
                "AND   SubjectIndex = $subjectindex";
    $res =& $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    return True;
}

function punishment_remove_student($username, $yearindex, $termindex) {
    global $db;

    $query =    "DELETE discipline FROM discipline INNER JOIN disciplineweight USING (DisciplineWeightIndex) " .
                "WHERE discipline.Username        = '$username' " .
                "AND   disciplineweight.YearIndex = $yearindex " .
                "AND   disciplineweight.TermIndex = $termindex";
    $res =& $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    return True;
}

function subject_remove_student_from_all($username, $yearindex, $termindex) {
    global $db;

    $query =    "SELECT subject.SubjectIndex FROM subject INNER JOIN subjectstudent USING (SubjectIndex) " .
                "WHERE subjectstudent.Username = '$username' " .
                "AND   subject.YearIndex       = $yearindex " .
                "AND   subject.TermIndex       = $termindex";
    $res =& $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        subject_remove_student($username, $row['SubjectIndex']);
    }

    return True;
}

function school_remove_student($username, $yearindex, $termindex) {
    global $db;

    subject_remove_student_from_all($username, $yearindex, $termindex);
    punishment_remove_student($username, $yearindex, $termindex);

    $query =    "SELECT classterm.ClassTermIndex FROM " .
                "       class INNER JOIN classterm USING (ClassIndex) " .
                "             INNER JOIN classlist USING (ClassTermIndex) " .
                "WHERE classlist.Username  = '$username' " .
                "AND   class.YearIndex     = $yearindex " .
                "AND   classterm.TermIndex = $termindex";
    $res =& $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        class_remove_student($username, $row['ClassTermIndex']);
    }

    remove_member_from_group($username, "activestudent-$yearindex");
    return True;
}

function format_average($average, $score=-99, $long=false, $can_modify=true) {
    global $MARK_ABSENT;
    global $MARK_EXEMPT;
    global $MARK_LATE;

    if($score != -99) {
        if (is_null($score)) {
            if(!$can_modify) {
                if($long)
                    return "<i>Exempt</i>";
                else
                    return "<i>E</i>";
            } else {
                return "&nbsp;";
            }
        } elseif ($score == $MARK_ABSENT) {
            if($long)
                return "<i>Absent</i>";
            else
                return "<i>A</i>";
        } elseif ($score == $MARK_EXEMPT) {
            if($long)
                return "<i>Exempt</i>";
            else
                return "<i>E</i>";
        } elseif ($score == $MARK_LATE) {
            if(!$can_modify)
                return "0%";
            if($long)
                return "<i>Late</i>";
            else
                return "<i>L</i>";
        }
    }

    if(!is_null($average)) {
        return $average . "%";
    } else {
        return "N/A";
    }
}

function format_makeup_average($can_modify, $hidden, $alt, $alt_step, $overall_avg, $average, $makeup_avg, $score, $makeup_score) {
    global $MARK_ABSENT;
    global $MARK_EXEMPT;
    global $MARK_LATE;

    /* Check whether either mark hasn't been set and, if so, only show other mark*/
    $same = false;
    if(is_null($makeup_score)) {
        $chk_score = $score;
        $same = true;
    } elseif(is_null($score)) {
        $chk_score = $makeup_score;
        $same = true;
    } elseif($score < 0 and $score == $makeup_score) {
        $chk_score = $makeup_score;
        $same = true;
    }

    if($same) {
        if($can_modify and $hidden == 0 and isset($alt_step) and $alt_step != "") {
            if ($chk_score == $MARK_LATE) {
                $alt = " class='late-$alt_step'";
            } elseif(is_null($chk_score)) {
                $alt = " class='unmarked-$alt_step'";
            }
        }
    }
    $retval =  "<td$alt nowrap>";

    if($same) {
        $overall_avg = format_average($overall_avg, $chk_score, true, $can_modify);
    } else {
        $average = format_average($average, $score, false, $can_modify);
        $overall_avg = format_average($overall_avg, -99, false, $can_modify);
        $makeup_avg = format_average($makeup_avg, $makeup_score, false, $can_modify);
        $retval .= "<span style='float: right; font-size: 75%; white-space: nowrap; border-bottom-style: solid; border-bottom-width: thin'>$average&nbsp;<span style='border-left-style: solid; border-left-width: thin'>&nbsp;$makeup_avg</span></span><br/>\n";
    }
    $retval .= "<span style='float: right'>$overall_avg</span>";
    $retval .= "</td>";
    return $retval;
}

function get_nonmark_input($index, $type_index=NULL) {
    global $pdb;

    if(!is_null($type_index)) {
        $query = $pdb->prepare(
            "SELECT Input FROM nonmark_index " .
            "WHERE NonmarkTypeIndex = :type_index " .
            "AND   NonmarkIndex     = :index"
        );
        $query->execute(['type_index' => $type_index, 'index' => $index]);
    } else {
        $query = $pdb->prepare(
            "SELECT Input FROM nonmark_index " .
            "WHERE NonmarkIndex     = :index"
        );
        $query->execute(['index' => $index]);
    }
    $row = $query->fetch();
    if(!$row) {
        return NULL;
    } else {
        return $row['Input'];
    }
}

function get_nonmark_display($index, $type_index=NULL) {
    global $pdb;

    if(!is_null($type_index)) {
        $query = $pdb->prepare(
            "SELECT Display FROM nonmark_index " .
            "WHERE NonmarkTypeIndex = :type_index " .
            "AND   NonmarkIndex     = :index"
        );
        $query->execute(['type_index' => $type_index, 'index' => $index]);
    } else {
        $query = $pdb->prepare(
            "SELECT Display FROM nonmark_index " .
            "WHERE NonmarkIndex     = :index"
        );
        $query->execute(['index' => $index]);
    }
    $row = $query->fetch();
    if(!$row) {
        return NULL;
    } else {
        return $row['Display'];
    }
}

function get_nonmark_index($input, $type_index) {
    global $pdb;

    $query = $pdb->prepare(
        "SELECT NonmarkIndex FROM nonmark_index " .
        "WHERE NonmarkTypeIndex = :type_index " .
        "AND   Input            = :input"
    );
    $query->execute(['type_index' => $type_index, 'input' => $input]);

    $row = $query->fetch();
    if(!$row) {
        return NULL;
    } else {
        return $row['NonmarkIndex'];
    }
}

function get_next_term($termindex, $depindex) {
    global $pdb;
    $next_termindex = NULL;

    $query = $pdb->prepare(
        "SELECT TermIndex FROM term WHERE DepartmentIndex = :depindex " .
        "ORDER BY TermNumber"
    );
    $query->execute(['depindex' => $depindex]);
    foreach($query as $row) {
        if ($row['TermIndex'] == $termindex) {
            $row = $query->fetch();
            if($row) {
                $next_termindex = $row['TermIndex'];
            }
            break;
        }
    }
    return $next_termindex;
}

function run_cmd($cmd, $stdin, $env) {
    $descriptorspec = array(
       0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
       1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
       2 => array("pipe", "w")   // stderr is a pipe
    );

    $cwd = '/tmp';
    $process = proc_open($cmd, $descriptorspec, $pipes, $cwd, $env);

    if (is_resource($process)) {
        fwrite($pipes[0], $stdin);
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $ret = proc_close($process);
        if($ret != 0)
            return array(FALSE, $error . ": " . $output);
        else
            return array(TRUE, "");
    }
    return array(FALSE, "Unable to run command");
}

function check_own_pwd($username, $pwd) {
    $cache_file = tempnam(sys_get_temp_dir(), "LESSON");
    $env = array('KRB5CCNAME' => "FILE:$cache_file");

    $cmd = "kinit -c FILE:$cache_file " . escapeshellarg($username);
    $stdin = "$pwd\n";
    $retval = run_cmd($cmd, $stdin, $env);
    unlink($cache_file);
    if(!$retval[0]) {
        if(strpos($retval[1], "expired") !== False) {
            return 2;
        } else {
            return 0;
        }
    }
    return 1;
}

function change_pwd_priv($username, $pwd) {
    global $IPA_PW_PWD;
    global $IPA_PW_UID;

    $cache_file = tempnam(sys_get_temp_dir(), "LESSON");
    $env = array('KRB5CCNAME' => "FILE:$cache_file");

    $cmd = "kinit -c FILE:$cache_file " . escapeshellarg($IPA_PW_UID);
    $stdin = "$IPA_PW_PWD\n";
    $retval = run_cmd($cmd, $stdin, $env);
    if(!$retval[0])
        die($retval[1]);

    $cmd = "ipa passwd " . escapeshellarg($username);
    $stdin = "$pwd\n$pwd\n";
    $retval = run_cmd($cmd, $stdin, $env);
    unlink($cache_file);
    if(!$retval[0])
        die($retval[1]);
}

function change_own_pwd_priv($username, $pwd) {
    $tmp_pwd = uniqid('', true);
    change_pwd_priv($username, $tmp_pwd);
    change_own_pwd($username, $tmp_pwd, $pwd);
}

function check_pwd_expired($username, $pwd) {
    $cache_file = tempnam(sys_get_temp_dir(), "LESSON");
    $env = array('KRB5CCNAME' => "FILE:$cache_file");
    $cmd = "kinit -c FILE:$cache_file " . escapeshellarg($username);
    $stdin = "$pwd\n";
    $retval = run_cmd($cmd, $stdin, $env);
    unlink($cache_file);
    if($retval[0])
        return False;
    return True;
}

function change_own_pwd($username, $oldpwd, $newpwd) {
    $tmp_pwd = uniqid('', true);
    $cache_file = tempnam(sys_get_temp_dir(), "LESSON");
    $env = array('KRB5CCNAME' => "FILE:$cache_file");

    $cmd = "kinit -c FILE:$cache_file " . escapeshellarg($username);
    $stdin = "$oldpwd\n";
    $retval = run_cmd($cmd, $stdin, $env);
    if(!$retval[0]) {
        if(strpos($retval[1], "expired") !== False) {
            $cmd = "kinit -c FILE:$cache_file " . escapeshellarg($username);
            $stdin = "$oldpwd\n$newpwd\n$newpwd\n";
            $retval = run_cmd($cmd, $stdin, $env);
            if(!$retval[0]) {
                unlink($cache_file);
                die($retval[1]);
                return False;
            }
        } else {
            return False;
        }
    }

    $cmd = "ipa passwd";
    $stdin = "$newpwd\n$newpwd\n$newpwd\n";
    $retval = run_cmd($cmd, $stdin, $env);
    unlink($cache_file);
    if(!$retval[0])
        die($retval[1]);

    return True;
}

function add_user($username, $firstname, $surname, $pwd) {
    global $IPA_PW_PWD;
    global $IPA_PW_UID;

    $cache_file = tempnam(sys_get_temp_dir(), "LESSON");
    $env = array('KRB5CCNAME' => "FILE:$cache_file");

    $cmd = "kinit -c FILE:$cache_file " . escapeshellarg($IPA_PW_UID);
    $stdin = "$IPA_PW_PWD\n";
    $retval = run_cmd($cmd, $stdin, $env);
    if(!$retval[0])
        die($retval[1]);

    $username = escapeshellarg($username);
    $firstname = escapeshellarg($firstname);
    $surname = escapeshellarg($surname);
    $cmd = "ipa user-add $username --first=$firstname --last=$surname --password";
    $stdin = "$pwd\n$pwd\n";
    $retval = run_cmd($cmd, $stdin, $env);
    unlink($cache_file);
    if(!$retval[0]) {
        print_r("\nDead here");
        die($retval[1]);
    }
}

