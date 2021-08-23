<?php
$username = "inlis";
$password = "admin";
$db = "(DESCRIPTION=(ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)(HOST = localhost)(PORT = 1521)))(CONNECT_DATA=(SERVICE_NAME=XE)))" ;
$c = OCILogon($username, $password, $db);