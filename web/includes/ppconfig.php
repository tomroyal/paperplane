<?php
// paperplane 1.0
// github.com/tomroyal
// config to be included elsewhere

// token and app name for your Dropbox API app - needs folder-level permissions only

$apt_debug_mode = 1;

$dbxtoken = "1l2ISB64xV4AAAAAAADMXOuijHOVKADZh9Qkj387ckgjzl5CPiy3G6WV35IHRT6C";
$dbxname = "paperplane01";

// allow user registration? 1 for yes, 0 for no
$pp_allow_reg = 1;
// simple password required to show registration options - leave blank for open reg.
$pp_reg_pass = "betatest1";

// salt for passwords and stuff - mash the keyboard ;)
$pwsalt = "aiuysfgaysujhfbas786afsyt";

// connect database

$schemaname = "pp";
$con = pg_connect(getenv('DATABASE_URL')); // pg conn is $con, not $link

// end
