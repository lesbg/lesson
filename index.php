<?php
	/*****************************************************************
	 * index.php  (c) 2004, 2005 Jonathan Dieter
	 *
	 * Central script that runs by default.  This script includes
	 * any child scripts that need to be run.  Thus, as far as the
	 * browser is concerned, it keeps coming back to the same site.
	 * The main reason for this is to keep a consistent check on
	 * whether the user is authorized.
	 *****************************************************************/

	include "globals.php";                          // Include global variables
	
	/* Create connection to database */
	require_once "DB.php";                          // Get DB class
	include "core/dbfunc.php";                      // Get database connection functions
	
	$db =& dbfuncConnect();                         // Connect to database and store in $db
	$query = "SET NAMES 'utf8'";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

	/* Perform login */
	require_once "Auth.php";                         // Get Auth class
	include "core/loginfunc.php";                    // Get login functions
	$shown         =  False;                         // Whether login screen has been shown already
	$loginSession  =& loginfuncDoLogin();            // Authenticate login
	$username      =  strtolower($loginSession->getUsername());   // Get login username
	$yearindex     =  dbfuncGetYearIndex();           // Get current year
	$depindex      =  dbfuncGetDepIndex();            // Get current department index
	$termindex     =  dbfuncGetTermIndex($depindex);  // Get current term
	$fullname      =& dbfuncGetFullName();            // Get fullname from database
	$permissions   =  dbfuncGetPermissions();         // Get user's permissions from database<
	$dateformat    =  dbfuncGetDateFormat();          // Get date format
	$printtotal    =  dbfuncGetPrintTotal();          // Find out whether we should print totals
	$activestudent =  dbfuncIsActiveStudent();
	$activeteacher =  dbfuncIsActiveTeacher();
	$phone_prefix  =  dbfuncGetPhonePrefix();
	$phone_RLZ     =  dbfuncGetPhoneRLZ();

	if (isset($_SERVER['REMOTE_HOST']) and strtolower(substr($_SERVER['REMOTE_HOST'], -strlen($LOCAL_HOSTS))) == strtolower($LOCAL_HOSTS)) {
		$is_local = TRUE;
	} else {
		$is_local = FALSE;
	}

	$password_number = $_SESSION['password_number'];

	start_log("index.php");
	
	if(isset($_SERVER['HTTP_REFERER'])) {
		$backLink = htmlspecialchars($_SERVER['HTTP_REFERER']);
	} else {
		$backLink = "index.php?location=" . dbfuncString2Int("user/main.php");
	}
	
	$curLink = substr($_SERVER['REQUEST_URI'], strrpos($_SERVER['REQUEST_URI'], '/')+1);

	$location = "user/start.php";

	if(isset($_GET['location'])) {                   // Check whether we've been passed a location
		$location = dbfuncInt2String($_GET['location']);   // If so, switch to it.
	}
	
	if(isset($_SESSION['depindex'])) {
		$depindex  = $_SESSION['depindex'];
	}
	if(isset($_SESSION['yearindex'])) {              // Set yearindex to session variable if set
		$yearindex = $_SESSION['yearindex'];
	}
	if(isset($_SESSION['termindex'])) {              // Set termindex to session variable if set
		$termindex = $_SESSION['termindex'];
	}

	if($_SESSION['password_number'] == 2) {
		$pwd_val = "Password2";
	} else {
		$pwd_val = "Password";
	}
	$query = "SELECT Username FROM user WHERE Username='$username' AND $pwd_val=MD5('$username')";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	if($res->numRows() > 0 and $location != "user/dochangepassword.php") {
		log_event($LOG_LEVEL_ACCESS, "index.php", $LOG_USER,
		"Forcing user to change their password because it is the same as their username.");
		$samepass = true;
		$location = "user/changepassword.php";
	}

	if(dbfuncGetPermission($permissions, $PERM_ADMIN)) {
		$is_admin = True;
	} else {
		$is_admin = False;
	}

	//echo "$location - $password_number";
	include "$location"; // Switch to current page
	
	/*update_conduct_year_term(6, 1);
	update_conduct_year_term(6, 7);*/
        //update_classterm(149, 1);
        



//	update_conduct_year_term(5, 7);
	$db->disconnect();                               // Close connection to database
?>
