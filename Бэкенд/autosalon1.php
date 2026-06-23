<?session_start();
$mysqli = new mysqli('localhost', 'root', '', 'avtosalon');
if ($mysqli->connect_error) {
    die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

#echo '<link rel="stylesheet" href="style.css">';