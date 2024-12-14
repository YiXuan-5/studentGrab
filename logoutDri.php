<?php
session_start();
session_destroy();
header("Location: loginDri.php");
exit;
?> 