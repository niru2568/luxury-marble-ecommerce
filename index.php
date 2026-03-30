<?php
// Starting the session
session_start();

// Include database configuration
include('config/db.php');

// Include header
include('include/header.php');

// Include main content
include('include/content.php');

// Include footer
include('include/footer.php');
?>