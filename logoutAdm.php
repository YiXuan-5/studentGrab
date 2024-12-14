<?php
//session_start(): Start a new session or resume an existing session
session_start();
//session_destroy(): Destroys all data registered to a session
session_destroy();
header("Location: mainPage.html");
exit;
?> 