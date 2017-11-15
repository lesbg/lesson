<?php
/**
 * ***************************************************************
 * index.php (c) 2004-2005, 2015-2017 Jonathan Dieter
 *
 * Central script that runs by default. This script includes
 * any child scripts that need to be run. Thus, as far as the
 * browser is concerned, it keeps coming back to the same site.
 * The main reason for this is to keep a consistent check on
 * whether the user is authorized.
 * ***************************************************************
 */
include "globals.php"; // Include global variables

/* Create connection to database */
require_once "DB.php"; // Get DB class
include "core/dbfunc.php"; // Get database connection functions
include "core/filefunc.php"; // Include file functions

$db = & dbfuncConnect(); // Connect to database and store in $db

session_name("LESSONSESSION");
session_start();

/* CSRF protection for POST requests with ORIGIN header */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_SERVER["HTTP_ORIGIN"])) {
        if (strpos($URL, $_SERVER["HTTP_ORIGIN"]) !== 0) {
            if(isset($_SESSION['username']))
                $username = $_SESSION['username'];
            $page = strip_tags(dbfuncInt2String($_GET['location']));
            log_event($LOG_LEVEL_ERROR, $page,
                      $LOG_DENIED_ACCESS,
                      "ORIGIN header {$_SERVER['HTTP_ORIGIN']} doesn't match our site $URL, CSRF attack?<br>Method: {$_SERVER['REQUEST_METHOD']}<br>Page: $page");
            redirect($URL);
        }
    }
}

/* CSRF protection for all other requests */
if (isset($_SERVER["HTTP_REFERER"]) and
    (($_SERVER["REQUEST_METHOD"] != "GET" and
      $_SERVER["REQUEST_METHOD"] != "HEAD") or
     (isset($_GET['location']) and
      !is_null($_GET['location']) and
      $_GET['location'] != "" and
      dbfuncInt2String($_GET['location']) != "user/main.php" and
      dbfuncInt2String($_GET['location']) != "user/logout.php" and
      dbfuncInt2String($_GET['location']) != ""))) {
    $check_url = parse_url($_SERVER["HTTP_REFERER"], PHP_URL_SCHEME) . '://' .
                 parse_url($_SERVER["HTTP_REFERER"], PHP_URL_HOST);
    if (strpos($URL, $check_url) !== 0) {
        if(isset($_SESSION['username']))
            $username = $_SESSION['username'];
        $page = strip_tags(dbfuncInt2String($_GET['location']));

        if(is_null($_GET['location']))
            $is_null="yes";
        else
            $is_null="no";

        if(isset($_GET['location']))
            $is_set="yes";
        else
            $is_set="no";

        log_event($LOG_LEVEL_ERROR, strip_tags(dbfuncInt2String($_GET['location'])),
                  $LOG_DENIED_ACCESS, "REFERER header $check_url doesn't match our site $URL, CSRF attack?<br>Referer: {$_SERVER['HTTP_REFERER']}<br>Method: {$_SERVER['REQUEST_METHOD']}<br>Page: $page<br>{$_GET['location']}, $is_set, $is_null");
        redirect($URL);
    }
} else {
    /* If it's any request other than GET or HEAD and there's no referer, then make sure
     * there's no location.  If there is a location, redirect back to root url */
    if(($_SERVER["REQUEST_METHOD"] != "GET" and
        $_SERVER["REQUEST_METHOD"] != "HEAD") and
       (isset($_GET['location']) and
        !is_null($_GET['location']) and
        dbfuncInt2String($_GET['location']) != "user/main.php" and
        dbfuncInt2String($_GET['location']) != "user/logout.php" and
        dbfuncInt2String($_GET['location']) != "")) {

        if(isset($_SESSION['username']))
                $username = $_SESSION['username'];
        $page = strip_tags(dbfuncInt2String($_GET['location']));
        log_event($LOG_LEVEL_ERROR, safe(strip_tags(dbfuncInt2String($_GET['location']))),
                  $LOG_DENIED_ACCESS, "Missing REFERER header, CSRF attack?<br>Method: {$_SERVER['REQUEST_METHOD']}<br>Page: $page");

        redirect($URL);
    }
}

// Clear out any untrusted tags from $_GET variables
if(isset($_GET))
    $_GET = clean_vals($_GET, $base64=True);

if (isset($_SERVER['REMOTE_HOST'])) {
    $remote_host = $_SERVER['REMOTE_HOST'];
} else {
    $remote_host = $_SERVER['REMOTE_ADDR'];
}
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    if ($_SERVER['HTTP_X_FORWARDED_FOR'] != "unknown" and isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $remote_host = "{$_SERVER['HTTP_X_FORWARDED_FOR']} through $remote_host";
    }
}

if (isset($_SERVER['REMOTE_HOST']) and strtolower(substr($_SERVER['REMOTE_HOST'], - strlen($LOCAL_HOSTS))) == strtolower($LOCAL_HOSTS)) {
    $is_local = TRUE;
} else {
    $is_local = FALSE;
}

$change_pw = False;

if(!isset($_SESSION['username'])) {
    if(!isset($_POST['username'])) {
        include "user/login.php";
        exit(0);
    } else {
        include "user/login_action.php";
    }
}
/* Perform login */

$shown = False; // Whether login screen has been shown already
$username = $_SESSION['username']; // Get login username
$yearindex = dbfuncGetYearIndex(); // Get current year
$depindex = dbfuncGetDepIndex(); // Get current department index
$termindex = dbfuncGetTermIndex($depindex); // Get current term
$fullname = & dbfuncGetFullName(); // Get fullname from database
$permissions = dbfuncGetPermissions(); // Get user's permissions from database<
$dateformat = dbfuncGetDateFormat(); // Get date format
$printtotal = dbfuncGetPrintTotal(); // Find out whether we should print totals
$activestudent = dbfuncIsActiveStudent();
$activeteacher = dbfuncIsActiveTeacher();
$phone_prefix = dbfuncGetPhonePrefix();
$phone_RLZ = dbfuncGetPhoneRLZ();

if (isset($_SERVER['REMOTE_HOST']) and
     strtolower(substr($_SERVER['REMOTE_HOST'], - strlen($LOCAL_HOSTS))) ==
     strtolower($LOCAL_HOSTS)) {
    $is_local = TRUE;
} else {
    $is_local = FALSE;
}

$password_number = $_SESSION['password_number'];

start_log("index.php");

if (isset($_SERVER['HTTP_REFERER'])) {
    $backLink = htmlspecialchars($_SERVER['HTTP_REFERER']);
} else {
    $backLink = "index.php?location=" . dbfuncString2Int("user/main.php");
}

$curLink = substr($_SERVER['REQUEST_URI'],
                strrpos($_SERVER['REQUEST_URI'], '/') + 1);

$location = "user/start.php";

if (isset($_GET['location'])) { // Check whether we've been passed a location
    $location = strip_tags(dbfuncInt2String($_GET['location'])); // If so, switch to it.
}

if (isset($_SESSION['depindex'])) {
    $depindex = $_SESSION['depindex'];
}
if (isset($_SESSION['yearindex'])) { // Set yearindex to session variable if set
    $yearindex = $_SESSION['yearindex'];
}
if (isset($_SESSION['termindex'])) { // Set termindex to session variable if set
    $termindex = $_SESSION['termindex'];
}
if (isset($_SESSION['samepass']) and $_SESSION['samepass']) {
    $samepass = true;
    if($location != "user/dochangepassword.php") {
        log_event($LOG_LEVEL_ACCESS, "index.php", $LOG_USER,
                  "Forcing user to change their password because it is the same as their username.");
        $location = "user/changepassword.php";
    }
}

if (dbfuncGetPermission($permissions, $PERM_ADMIN)) {
    $is_admin = True;
} else {
    $is_admin = False;
}

// echo "$location - $password_number";
include "$location"; // Switch to current page

//update_year_term(14, 1);
// update_conduct_year_term(1, 1);
                     // update_conduct_year_term(7, 1);
                     // update_year_term(9, 6);


/*$query = "SELECT SubjectIndex FROM subject WHERE YearIndex=14 AND TermIndex=1 AND AverageType=1 ";
$res =& $db->query($query);
if(DB::isError($res)) die($res->getDebugInfo());
    while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    update_subject($row["SubjectIndex"]);
}*/

/*
$query = "SELECT FileIndex FROM filebuffer ORDER BY FileIndex";
$res =& $db->query($query);
if(DB::isError($res)) die($res->getDebugInfo()); // Check for errors in query
while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    get_path_from_id($row['FileIndex']);
}*/


/*
$filelist = array();
$dir = new DirectoryIterator("/networld/temp/mugshots2012/");
foreach ($dir as $fileinfo) {
    if (!$fileinfo->isDot()) {
        $filelist[] = $fileinfo->getFilename();
    }
}

$query = "SELECT Username FROM classlist INNER JOIN classterm USING (ClassTermIndex) INNER JOIN class USING (ClassIndex) WHERE class.YearIndex=9 AND Grade>5 GROUP BY classlist.Username ORDER BY class.Grade, class.ClassName, classlist.Username";
$res =& $db->query($query);
if(DB::isError($res)) die($res->getDebugInfo()); // Check for errors in query
while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    $uname = $row['Username'];
    if(in_array("$uname.jpg", $filelist)) {
        echo $uname;
        upload_photo("/networld/temp/mugshots2012/$uname.jpg", $uname, 9);
    }
}*/


/*
$query = "SELECT FileIndex FROM filebuffer";
$res =& $db->query($query);
if(DB::isError($res)) die($res->getDebugInfo()); // Check for errors in query
while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    get_path_from_id($row['FileIndex']);
}*/

$db->disconnect(); // Close connection to database
