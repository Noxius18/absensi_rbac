<?php
require_once 'lib/auth.php';

logout();
header('Location: index.php');
exit;

?>