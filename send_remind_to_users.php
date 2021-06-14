<?php
include "models/VyatsuScheduleROBOT.php";
//include "models/DoubleFeatheredBot.php";

$db_connection = explode("\n", file_get_contents("private/db.connection"));
$db = new mysqli(trim($db_connection[0]), trim($db_connection[1]), trim($db_connection[2]), trim($db_connection[3]));
$db->query("SET NAMES 'utf8'");
$db->query("SET CHARACTER SET 'utf8'");
$db->query("SET SESSION collation_connection = 'utf8_general_ci'");

$db->query("INSERT INTO `history` (`object`, `action`, `depth`) VALUES ('new_file', 'start', 1);");
$query = $db->query("SELECT `id` FROM `group` WHERE `schedule`!='' AND `updated`>'".date("Y-m-d H:i:s", strtotime("-1 hour"))."'");
while($row = $query->fetch_row()) {
    echo $row[0]."\n";
    send_remind_to_users($row[0]);
}
$db->query("INSERT INTO `history` (`object`, `action`, `depth`) VALUES ('new_file', 'finish', 1);");