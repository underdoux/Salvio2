<?php
session_start();
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
if(isset($_SESSION['user'])) {
    echo "User is logged in: " . $_SESSION['user']['username'];
} else {
    echo "No user session found";
}
