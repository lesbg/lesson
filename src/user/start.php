<?php
/**
 * ***************************************************************
 * user/start.php (c) 2005 Jonathan Dieter
 *
 * Start page that redirects to main. Used so that user can
 * press back key to get to main without needing to repost
 * data.
 * ***************************************************************
 */

redirect("index.php?location=" . dbfuncString2Int("user/main.php"));
