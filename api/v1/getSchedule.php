<?php
include "../../models/classes.php";
include "../../functions.php";

$db_connection = explode("\n", file_get_contents("../../private/db.connection"));
$db = new mysqli(trim($db_connection[0]), trim($db_connection[1]), trim($db_connection[2]), trim($db_connection[3]));
$db->query("SET NAMES 'utf8'");
$db->query("SET CHARACTER SET 'utf8'");
$db->query("SET SESSION collation_connection = 'utf8_general_ci'");

if (!array_key_exists("group_id", $_GET) and !array_key_exists("teacher_id", $_GET)) {
    echo '{"error":"one of the parameters \"group_id\" or \"teacher_id\" must be filled"}';
    return;
} elseif (((array_key_exists("group_id", $_GET)) and (!is_numeric($_GET["group_id"]))) or ((array_key_exists("teacher_id", $_GET)) and (!is_numeric($_GET["teacher_id"])))) {
    if (array_key_exists("group_id", $_GET)) {
        echo "{\"error\":\"'group_id' must be a number \"}";
        return;
    } elseif (array_key_exists("teacher_id", $_GET)) {
        echo "{\"error\":\"'teacher_id' must be a number \"}";
        return;
    }
}
$date_pattern = "/^\d{4}-\d{2}-\d{2}$/";
if (array_key_exists("group_id", $_GET)) {
    $group = $query = $db->query("SELECT * FROM `group` WHERE `id`={$_GET["group_id"]}")->fetch_row();
    if (empty($group)) {
        echo "{\"error\":\"group doesn't exist\"}";
        return;
    }
    $query = $db->query("SELECT DISTINCT `faculty` FROM `teacher` WHERE 1");
    $faculties = [];
    while($row = $query->fetch_row()) {
        $faculties[decrease(trim(str_replace("(факультет)", "", str_replace("(ОРУ)", "", $row[0]))))] = $row[0];
    }
    $group = new Group($group[0], $group[1], $faculties[$group[2]], $group[3], $group[4]);

    $schedule = [];
    $query = "SELECT * FROM `schedule` WHERE `group_id`={$_GET["group_id"]} AND `lesson` != \"\" AND `date`>=\"".date("Y-m-d")."\"";
    if (array_key_exists("from", $_GET)) {
        if (!is_string($_GET["from"])) {
            echo "{\"error\":\"'from' should have type string\"}";
            return;
        }
        if (!preg_match($date_pattern, $_GET["from"])) {
            echo "{\"error\":\"'from' doesn't match the pattern\"}";
            return;
        }
        $query .= " AND `date` >= \"{$_GET["from"]}\"";
    }
    if (array_key_exists("to", $_GET)) {
        if (!is_string($_GET["to"])) {
            echo "{\"error\":\"'to' should have type string\"}";
            return;
        }
        if (!preg_match($date_pattern, $_GET["to"])) {
            echo "{\"error\":\"'to' doesn't match the pattern\"}";
            return;
        }
        $query .= " AND `date` <= \"{$_GET["to"]}\"";
    }

    $lesson_number = ["08:20:00" => 1, "10:00:00" => 2, "11:45:00" => 3, "14:00:00" => 4, "15:45:00" => 5, "17:20:00" => 6, "18:55:00" => 7];
    $schedules = [];
    $qr = $db->query($query);
    while($row = $qr->fetch_row()) {
        $schedule = new SubObject(["group" => $group, "date" => $row[1], "start" => $row[2], "end" => $row[3], "lesson" => $row[4], "lesson_number" => $lesson_number[$row[2]]]);
        if ($row[5] != "") {
            $schedule->audience = $row[5];
        }
        if ($row[6] != "") {
            $schedule->links = [$row[6]];
        }
        $schedules[] = $schedule;
    }

    echo json_encode($schedules);
} else {
    $teacher = $query = $db->query("SELECT * FROM `teacher` WHERE `id`={$_GET["teacher_id"]}")->fetch_row();
    if (empty($teacher)) {
        echo "{\"error\":\"teacher doesn't exist\"}";
        return;
    }
    $teacher = new Teacher($teacher[0], $teacher[1], $teacher[2], $teacher[3], $teacher[4]);

    $schedule = [];
    $query = "SELECT * FROM `teachers_schedule` WHERE `teacher_id`={$_GET["teacher_id"]} AND `date`>=\"".date("Y-m-d")."\"";
    if (array_key_exists("from", $_GET)) {
        if (!is_string($_GET["from"])) {
            echo "{\"error\":\"'from' should have type string\"}";
            return;
        }
        if (!preg_match($date_pattern, $_GET["from"])) {
            echo "{\"error\":\"'from' doesn't match the pattern\"}";
            return;
        }
        $query .= " AND `date` >= \"{$_GET["from"]}\"";
    }
    if (array_key_exists("to", $_GET)) {
        if (!is_string($_GET["to"])) {
            echo "{\"error\":\"'to' should have type string\"}";
            return;
        }
        if (!preg_match($date_pattern, $_GET["to"])) {
            echo "{\"error\":\"'to' doesn't match the pattern\"}";
            return;
        }
        $query .= " AND `date` <= \"{$_GET["to"]}\"";
    }

    $lesson_number = ["08:20:00" => 1, "10:00:00" => 2, "11:45:00" => 3, "14:00:00" => 4, "15:45:00" => 5, "17:20:00" => 6, "18:55:00" => 7];
    $schedules = [];
    $qr = $db->query($query);
    while($row = $qr->fetch_row()) {
        $schedule = new SubObject(["teacher" => $teacher, "date" => $row[1], "start" => $row[2], "end" => $row[3], "lesson" => $row[4], "lesson_number" => $lesson_number[$row[2]]]);
        if ($row[5] != "") {
            $schedule->links = explode("\n", $row[5]);
        }
        $schedules[] = $schedule;
    }

    echo json_encode($schedules);
}