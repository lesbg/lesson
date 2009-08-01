<?php
	/*****************************************************************
	 * core/loginfunc.php  (c) 2004 Jonathan Dieter
	 *
	 * Functions for logging in
	 *****************************************************************/

	/* Function that will be called if login is required. */
	function doNothing() {
	}

	function ShowLogin($error=False) {
		$title         = "Welcome to LESSON";
		$noJS          = true;
		$noHeaderLinks = true;
		global $shown;
		print "$shown";
		
		include "header.php";

		
		echo "      <style type='text/css'>\n";
		echo "         #center {width:400px; position:absolute; top:20%; left:50%; min-top: 0%; min-bottom: 100%; margin:auto auto auto -200px; text-align: center;}\n";
		echo "      </style>\n";
		echo "      <div id='center' class='button'>\n";
		$useragent = $_SERVER['HTTP_USER_AGENT'];
		if (preg_match('|MSIE ([0-6].[0-9]{1,2})|',$useragent,$matched)) {
			// Can't handle transparent png's, so we'll give them transparent gif's
			echo "         <p><img height='100' width='400' alt='LESSON Logo' src='images/lesson_logo.gif'></p>\n";
		} else {
			echo "         <p><img height='100' width='400' alt='LESSON Logo' src='images/lesson_logo.png'></p>\n";
		}
		echo "         <p>&nbsp;</p>\n";
	
		echo "         <form method='post' action='" . $_SERVER['PHP_SELF'] . "'>\n";
		echo "            <p>Username: <input type='text' name='username'></p>\n";
		echo "            <p>Password: <input type='password' name='password'></p>\n";
		echo "            <p><input type='submit' value='Login'></p>\n";
		echo "         </form>\n";
		if($error) {
			echo "         <p class='error'>Incorrect username or password.  Please try again!</p>\n";
		}
		echo "      </div>\n";
	}

	/* Perform login */
	function &loginfuncDoLogin() {
		/* Setup global parameters */
		global $DSN;
		global $LOG_LEVEL_ACCESS;
		global $LOG_ERROR;
		global $db;
		global $username;
		global $MAX_TRIES;
		global $LOCAL_HOSTS;
		global $password_number;
		
		/* Parameters to connect to database */
		$params = array("dsn" => $DSN,  // Global variable set in index.php
				"table" => "user",
				"usernamecol" => "Username",
				"passwordcol" => "Password");
		
		/* Setup Auth session */
		if(isset($_POST['username'])) {
			$username = $_POST['username'];
		} else {
			$username = "";
		}
		
		if(isset($_SERVER['REMOTE_HOST'])) {
			$remote_host = $_SERVER['REMOTE_HOST'];
		} else {
			$remote_host = $_SERVER['REMOTE_ADDR'];
		}
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			if($_SERVER['HTTP_X_FORWARDED_FOR'] != "unknown" and isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$remote_host = "{$_SERVER['HTTP_X_FORWARDED_FOR']} through $remote_host";
			}
		}

		if (isset($_SERVER['REMOTE_HOST']) and strtolower(substr($_SERVER['REMOTE_HOST'], -strlen($LOCAL_HOSTS))) == strtolower($LOCAL_HOSTS)) {
			$is_local = TRUE;
		} else {
			$is_local = FALSE;
		}
		
		$res =&  $db->query("SELECT RemoteHost FROM blacklist " .
							"WHERE RemoteHost='$remote_host'");
		if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
				
		if($res->numRows() > 0) {
			if(isset($_POST['username'])) {
				$username = $db->escapeSimple($_POST['username']);
				if($username != "") {
					log_event($LOG_LEVEL_ACCESS, "core/loginfunc.php", $LOG_ERROR, "Failed login (IP address in blacklist)", 0);
				}
				showLogin(True);
			}
			include "footer.php";
			
			exit();
		}

		$authSession = new Auth("DB", $params, "doNothing");  // Create instance of Auth class
		$authSession->setSessionName("LESSONSESSION");                     // Set session name
		$authSession->start();                                             // Run authorization check
		if(!isset($_SESSION['failcount'])) $_SESSION['failcount'] = 0;

		/* Check whether authorization has succeeded */
		if ($authSession->checkAuth() == FALSE) {
			if ($authSession->getStatus() == AUTH_WRONG_LOGIN) {  // Check to see we got wrong login info
				/* If wrong username or password was used, attempt second password */
				if($username != "") {
					$params = array("dsn" => $DSN,  // Global variable set in index.php
							"table" => "user",
							"usernamecol" => "Username",
							"passwordcol" => "Password2");
					
					$authSession = new Auth("DB", $params, "doNothing");  // 
					$authSession->setSessionName("LESSONSESSION");                     // Set session name
					$authSession->start();                                             // Run authorization check
					if(!isset($_SESSION['failcount'])) $_SESSION['failcount'] = 0;

					if ($authSession->checkAuth() == FALSE) {
						if ($authSession->getStatus() == AUTH_WRONG_LOGIN) {
							/* If wrong username or password was used, start complaining */
		
							$username = $db->escapeSimple($username);
		
							$res =&  $db->query("SELECT user.FirstName, user.Surname, user.Username  " .
												"       FROM user " .
												"WHERE user.Username='$username'");
							if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
							if($res->numRows() == 0) {
								$_SESSION['failcount'] += 1;
							}
							log_event($LOG_LEVEL_ACCESS, "core/loginfunc.php", $LOG_ERROR,
									"Failed login (Incorrect password or username).", 0);
							if($_SESSION['failcount'] >= $MAX_TRIES and !$is_local) {
								$res =&  $db->query("INSERT INTO blacklist (RemoteHost) VALUES ('$remote_host')");
								if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
								log_event($LOG_LEVEL_ACCESS, "core/loginfunc.php", $LOG_ERROR,
										"Blacklisted $remote_host because failed login attempts > $MAX_TRIES.", 0);
							}
							showLogin(True);
						} else {
							showLogin(False);
						}
						include "footer.php";
						
						exit();
					} else {
						$_SESSION['password_number'] = 2;
					}
				}
			} else {
				showLogin();
				include "footer.php";
				exit();
			}
		} else {
			if(!isset($_SESSION['password_number'])) {
				$_SESSION['password_number'] = 1;
			}
		}

		return $authSession;  // Return our Auth session by reference
	}
?>
