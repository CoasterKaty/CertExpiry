<?php
include '../inc/page.php';
$myPage = new sitePage('Title of page', '', '1');
$myPage->logo = '/images/logo.png';
$myPage->addContent('Hello World');
echo $myPage->printPage();
?>
