<?php
require_once('./ShellLib/ShellCms.php');
if(file_exists('./vendor/autoload.php')){
    require_once ('./vendor/autoload.php');
}
$core = new Core();
$core->ParseRequest();