<?php
	/*****************************************************************
	 * core/constants.php  (c) 2005 Jonathan Dieter
	 *
	 * Store any global constants to be used in LESSON.
	 *****************************************************************/

	/* Non-user modifiable globals.  Do not touch unless you *really* know what you're doing */
	$PERM_ADMIN            = 0;                                   // Administrator permission bit
	$PERM_LIST_USERS       = 1;
	$PERM_EDIT_USERS       = 2;
	$PERM_LIST_CLASSES     = 3;
	$PERM_EDIT_CLASSES     = 4;
	$PERM_LIST_CONTENT     = 5;
	$PERM_EDIT_CONTENT     = 6;
	$PERM_MODIFY_PERM      = 7;

	$LOG_LOGIN             = 0;
	$LOG_LOGOUT            = 1;
	$LOG_DENIED_ACCESS     = 2;
	$LOG_ADMIN             = 3;
	$LOG_ASSIGNMENT        = 4;
	$LOG_STUDENT           = 5;
	$LOG_TEACHER           = 6;
	$LOG_ERROR             = 7;
	$LOG_USER              = 8;
	$LOG_STRING[0]         = "Login";
	$LOG_STRING[1]         = "Logout";
	$LOG_STRING[2]         = "Denied Access";
	$LOG_STRING[3]         = "Admin";
	$LOG_STRING[4]         = "Assignment";
	$LOG_STRING[5]         = "Student";
	$LOG_STRING[6]         = "Teacher";
	$LOG_STRING[7]         = "Error";
	$LOG_STRING[8]         = "User";
	
	$LOG_LEVEL_NONE        = 0;         // No logging
	$LOG_LEVEL_ERROR       = 1;         // Only log major errors (i.e. unauthorized access)
	$LOG_LEVEL_ACCESS      = 2;         // Only log who's using database
	$LOG_LEVEL_ADMIN       = 5;         // Log administrative use (i.e. users added, removed, etc.)
	$LOG_LEVEL_TEACHER     = 7;         // Log teacher's use (i.e. creating assignments, etc.)
	$LOG_LEVEL_EVERYTHING  = 10;        // Log everything (warning: will fill up logs quickly)
	$LOG_LEVEL_STRING[1]   = "Severe";
	$LOG_LEVEL_STRING[2]   = "Access";
	$LOG_LEVEL_STRING[5]   = "Admin";
	$LOG_LEVEL_STRING[7]   = "Teacher";
	$LOG_LEVEL_STRING[10]  = "Everything";

	$MSG_TYPE_USERNAME       = 0;
	$MSG_TYPE_SUBJECT        = 1;
	$MSG_TYPE_CLASS          = 2;
	$MSG_TYPE_TEACHERS       = 3;
	$MSG_TYPE_CLASS_TEACHERS = 9;
	$MSG_TYPE_STUDENTS       = 4;
	$MSG_TYPE_SPECIAL        = 5;
	$MSG_TYPE_REGULAR        = 6;
	$MSG_TYPE_NEW            = 7;
	$MSG_TYPE_OLD            = 8;
	$MSG_TYPE_COUNSELORS     = 10;
	$MSG_TYPE_HOD            = 11;
	$MSG_TYPE_PRINCIPALS     = 12;

	$PUN_PERM_NONE           = 0;
	$PUN_PERM_REQUEST        = 1;
	$PUN_PERM_ISSUE          = 2;
	$PUN_PERM_MASS           = 3;
	$PUN_PERM_PROXY          = 4;
	$PUN_PERM_SEE            = 5;
	$PUN_PERM_APPROVE        = 6;
	$PUN_PERM_ALL            = 7;
	$PUN_PERM_SUSPEND        = 8;
	
	$RULE_TYPE_CC            = 0;
	$RULE_TYPE_FILTER        = 1;

	$MARK_ABSENT             = -1;
	$MARK_EXEMPT             = -2;
	$MARK_LATE               = -3;

	$ATT_IN_CLASS            = 0;
	$ATT_ABSENT              = 1;
	$ATT_LATE                = 2;
	$ATT_SUSPENDED           = 3;

	$AVG_TYPE_NONE           = 0;
	$AVG_TYPE_PERCENT        = 1;
	$AVG_TYPE_INDEX          = 2;
	$AVG_TYPE_MAX            = 3;

	$CONDUCT_TYPE_NONE       = 0;
	$CONDUCT_TYPE_PERCENT    = 1;
	$CONDUCT_TYPE_INDEX      = 2;
	$CONDUCT_TYPE_MAX        = 3;

	$EFFORT_TYPE_NONE        = 0;
	$EFFORT_TYPE_PERCENT     = 1;
	$EFFORT_TYPE_INDEX       = 2;
	$EFFORT_TYPE_MAX         = 3;

	$COMMENT_TYPE_NONE       = 0;
	$COMMENT_TYPE_OPTIONAL   = 1;
	$COMMENT_TYPE_MANDATORY  = 2;
	$COMMENT_TYPE_MAX        = 3;

	$CLASS_AVG_TYPE_NONE           = 0;
	$CLASS_AVG_TYPE_PERCENT        = 1;
	$CLASS_AVG_TYPE_INDEX          = 2;
	$CLASS_AVG_TYPE_CALC           = 3;
	$CLASS_AVG_TYPE_MAX            = 4;

	$CLASS_CONDUCT_TYPE_NONE       = 0;
	$CLASS_CONDUCT_TYPE_PERCENT    = 1;
	$CLASS_CONDUCT_TYPE_INDEX      = 2;
	$CLASS_CONDUCT_TYPE_CALC       = 3;
	$CLASS_CONDUCT_TYPE_PUN        = 4;
	$CLASS_CONDUCT_TYPE_MAX        = 5;

	$CLASS_EFFORT_TYPE_NONE        = 0;
	$CLASS_EFFORT_TYPE_PERCENT     = 1;
	$CLASS_EFFORT_TYPE_INDEX       = 2;
	$CLASS_EFFORT_TYPE_CALC        = 3;
	$CLASS_EFFORT_TYPE_MAX         = 4;

	$ABSENCE_TYPE_NONE             = 0;
	$ABSENCE_TYPE_NUM              = 1;
	$ABSENCE_TYPE_CALC             = 2;
	$ABSENCE_TYPE_MAX              = 3;
?>