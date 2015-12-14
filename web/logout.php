<?php
// paperplane 1.1
// github.com/tomroyal
// does logout

// html template
include('./html/top.php');

session_start(); 

if (isset($_SESSION['pp_user'])) {  
    // logged in
    session_destroy();

    echo('<h4>Goodbye</h4>');
    echo('<p>You\'re logged out. To sign in again, click <a href="./index.php">here</a>.');
    echo('<p>Thanks for using Paper Plane.');
} else {
	// not logged in
    echo('<p>You are logged out.');
   
};
// html template
include('./html/btm.php');

?>