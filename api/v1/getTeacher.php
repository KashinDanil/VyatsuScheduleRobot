<?php
include "../../models/classes.php";

$db_connection = explode("\n", file_get_contents("../../private/db.connection"));
$db = new mysqli(trim($db_connection[0]), trim($db_connection[1]), trim($db_connection[2]), trim($db_connection[3]));
$db->query("SET NAMES 'utf8'");
$db->query("SET CHARACTER SET 'utf8'");
$db->query("SET SESSION collation_connection = 'utf8_general_ci'");


$teachers = [];
$query = "SELECT * FROM `teacher` WHERE ";
if (array_key_exists("name", $_GET)) {
    if (!is_string($_GET["name"])) {
        echo "{\"error\":\"'name' should have type string\"}";
        return;
    }
    $teacher_pattern = "/^[А-Яа-яёЁ\s\.]+$/u";
    if (!preg_match($teacher_pattern, $_GET["name"])) {
        echo "{\"error\":\"'name' doesn't match the pattern\"}";
        return;
    }
    $query .= "`name` like '%" . $_GET["name"] . "%'";
} else {
    $query .= "1";
}
$qr = $db->query($query);
while($row = $qr->fetch_row()) {
    $teacher = new Teacher($row[0], $row[1], $row[2], $row[3], $row[4]);
    $teachers[] = $teacher;
}

echo json_encode($teachers);