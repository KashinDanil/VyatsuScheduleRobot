<?php
include "../../functions.php";
include "../../models/classes.php";

$db_connection = explode("\n", file_get_contents("../../private/db.connection"));
$db = new mysqli(trim($db_connection[0]), trim($db_connection[1]), trim($db_connection[2]), trim($db_connection[3]));
$db->query("SET NAMES 'utf8'");
$db->query("SET CHARACTER SET 'utf8'");
$db->query("SET SESSION collation_connection = 'utf8_general_ci'");

$query = $db->query("SELECT DISTINCT `faculty` FROM `teacher` WHERE 1");
$faculties = [];
while($row = $query->fetch_row()) {
    $faculties[decrease(trim(str_replace("(факультет)", "", str_replace("(ОРУ)", "", $row[0]))))] = $row[0];
}

$groups = [];
$query = "SELECT * FROM `group` WHERE ";
if (array_key_exists("name", $_GET)) {
    if (!is_string($_GET["name"])) {
        echo "{\"error\":\"'name' should have type string\"}";
        return;
    }
    $group_pattern = "/^[А-Яа-яёЁ\d\-]+$/u";
    if (!preg_match($group_pattern, $_GET["name"])) {
        echo "{\"error\":\"'name' doesn't match the pattern\"}";
        return;
    }
    $query .= "`name` like '%" . $_GET["name"] . "%'";
} else {
    $query .= "1";
}
$query = $db->query($query);
while($row = $query->fetch_row()) {
    $group = new Group($row[0], $row[1], $faculties[$row[2]], $row[3], $row[4]);
    $groups[] = $group;
}

echo json_encode($groups);