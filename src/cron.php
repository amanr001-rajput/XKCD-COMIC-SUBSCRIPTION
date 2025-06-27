<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'functions.php';

// Log start of cron job
error_log("Starting XKCD comic distribution: " . date('Y-m-d H:i:s'));

// Send XKCD comics to all subscribers
sendXKCDUpdatesToSubscribers();

// Log completion
error_log("Completed XKCD comic distribution: " . date('Y-m-d H:i:s'));
?>