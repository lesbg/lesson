<?php
	/*****************************************************************
	 * globals.php  (c) 2004 Jonathan Dieter
	 *
	 * Store any user modifiable global variables to be used by LESSON.
	 *****************************************************************/
	
	include "core/constants.php";                    // Get login functions
	
	/* User modifiable globals */
	
	$MAX_TRIES           = 3;                              // Maximum number of login attempts with
	                                                       // non-existent usernames before IP is blacklisted

	$MAX_LOW_MARKS       = 2000;                           // Maximum number of low marks to show without a
	                                                       // warning

	$DSN                 = "mysql://user@example.com/lesson";     // DSN to connect to database
	$LOG_LEVEL           = $LOG_LEVEL_TEACHER;             // Set log level.  See core/constants.php for more details
	$LOGS_PER_PAGE       = 100;                            // Number of logs to show per page
	$LOCAL_HOSTS         = ".example.local";                   // Domain of local hosts
	$UPLOAD_BASE_DIR     = "/var/www/share/uploads";       // Base directory for uploads

	$SMS_PASSWORD        = "password";

	$SHOW_COMMENT_LENGTH = 30;