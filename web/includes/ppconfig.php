<?php
// paperplane 1.0
// github.com/tomroyal
// config to be included elsewhere

// token and app name for your Dropbox API app - needs folder-level permissions only

$apt_debug_mode = 1;

$dbxtoken = getenv('DB_TOKEN');
$dbxname = getenv('DB_NAME');

// allow user registration? 1 for yes, 0 for no
$pp_allow_reg = 1;
// simple password required to show registration options - leave blank for open reg.
$pp_reg_pass = getenv('REG_KEY');

// salt for passwords and stuff - mash the keyboard ;)
$pwsalt = getenv('PASS_SALT');

// connect database

$schemaname = "pp";
$con = pg_connect(getenv('DATABASE_URL')); // pg conn is $con, not $link

// end
