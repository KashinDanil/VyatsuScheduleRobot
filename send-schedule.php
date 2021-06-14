<?php
include "models/VyatsuScheduleROBOT.php";
//include "models/DoubleFeatheredBot.php";

$db_connection = explode("\n", file_get_contents("private/db.connection"));
$db = new mysqli(trim($db_connection[0]), trim($db_connection[1]), trim($db_connection[2]), trim($db_connection[3]));
$db->query("SET NAMES 'utf8'");
$db->query("SET CHARACTER SET 'utf8'");
$db->query("SET SESSION collation_connection = 'utf8_general_ci'");

$db->query("INSERT INTO `history` (`object`, `action`) VALUES ('schedule', 'start');");

$day_of_week = [1 => "Понедельник", 2 => "Вторник", 3 => "Среда", 4 => "Четверг", 5 => "Пятница", 6 => "Суббота", 7 => "Воскресенье",];
$startTime = [1 => "08:20", 2 => "10:00", 3 => "11:45", 4 => "14:00", 5 => "15:45", 6 => "17:20", 7 => "18:55",];
$endTime = [1 => "09:50", 2 => "11:30", 3 => "13:15", 4 => "15:30", 5 => "17:15", 6 => "18:50", 7 => "20:25",];
$figures = [1 => "\x31\xE2\x83\xA3", 2 => "\x32\xE2\x83\xA3", 3 => "\x33\xE2\x83\xA3", 4 => "\x34\xE2\x83\xA3", 5 => "\x35\xE2\x83\xA3", 6 => "\x36\xE2\x83\xA3", 7 => "\x37\xE2\x83\xA3",];
if ($_GET["reminder_option"] == 1) {
    $date = date("Y-m-d");
} else {
    $date = date("Y-m-d", strtotime("+1 day"));
}

$qr = $db->query("SELECT `telegram_user_id` FROM `user_group` LEFT JOIN `user` ON `user`.`id`=`user_group`.`user_id` group BY `user_id` HAVING COUNT(*) > 1");
$has_several_groups = [];
while($row = $qr->fetch_row()) {
    $has_several_groups[] = $row[0];
}

$user = new VyatsuScheduleROBOT(0, '', null, false);
if ($_GET["reminder_option"] == 2) {
    if ($_GET["time_finish"] == "08:20:00") {
        $query = "SELECT `group_id` FROM `schedule` WHERE `date`='" . date("Y-m-d") . "' AND `lesson`='' AND `group_id` in (SELECT DISTINCT `group_id` FROM `user_group` WHERE `daily_notifications`=2) GROUP BY `group_id` HAVING COUNT(*)=7";
    } else {
        $query = "SELECT `group_id` FROM `schedule` WHERE `date`='" . date("Y-m-d") . "' AND `lesson`!='' AND `group_id` in (SELECT DISTINCT `group_id` FROM `user_group` WHERE `daily_notifications`=2) GROUP BY `group_id` HAVING MAX(`time_finish`)='" . $_GET["time_finish"] . "'";
    }
} else {
    $query = "SELECT DISTINCT `group_id` FROM `user_group` WHERE `daily_notifications`=" . $_GET["reminder_option"];
}
echo "1) " . $query . "\n";
$query_groups = $db->query($query);
while($group_id = $query_groups->fetch_row()) {
    echo "group_id: " . $group_id[0] . "\n";
    $group_name = $db->query("SELECT `name` FROM `group` WHERE `id`=" . $group_id[0])->fetch_row()[0];

    $textMessage = "";
    $query = "SELECT * FROM `schedule` WHERE `group_id`=" . $group_id[0] . " AND `date`='" . $date . "' ORDER BY `time_start` ASC, `sort` ASC";
    echo "2) " . $query . "\n";
    $query = $db->query($query);
    $lesson_number = 0;
    $lessons = [];
    while($row = $query->fetch_row()) {
        if ($row[7] == 0) {
            $lesson_number++;
        }
        if ($row[4] != "") {
            if ($row[6] != "") {
                $lessons[$lesson_number][] = "<a href=\"" . $row[6] . "\">" . $row[4] . "</a>";
            } elseif (($row[5] != "")) {
                $lessons[$lesson_number][] = $row[4] . " <u>" . $row[5] . "</u>";
            } else {
                $lessons[$lesson_number][] = $row[4];
            }
        }
    }
    if ($lesson_number == 0) {
        echo "Для этой группы нет расписания на $date\n";
        continue;
    }
    foreach($lessons as $lesson_number => $lesson) {
        $textMessage .= $figures[$lesson_number] . "<b>" . $startTime[$lesson_number] . " &lt; </b>" . implode(" / ", $lesson) . "<b> &lt; " . $endTime[$lesson_number] . "</b>\n";
    }
    $textMessage .= $user->phrases["number_of_classes"][count($lessons)][rand(0, count($user->phrases["number_of_classes"][count($lessons)]) - 1)];
    $query = "SELECT `sort` FROM `schedule` WHERE `group_id`=" . $group_id[0] . " AND `date`='" . date("Y-m-d", strtotime("+1 day", strtotime($date))) . "' LIMIT 1";
    echo "3) " . $query . "\n";
    if ($db->query($query)->fetch_row()) {
        if ($date == date("Y-m-d")) {
            $buttons = new InlineKeyboard([[new InlineKeyboardButton("На завтра", "Прнз+" . $group_id[0])]]);
        } else {
            $buttons = new InlineKeyboard([[new InlineKeyboardButton("На " . date("d.m", strtotime("+1 day", strtotime($date))), "Прнс+" . date("Y-m-d", strtotime("+1 day", strtotime($date))) . "+" . $group_id[0])]]);
        }
    } else {
        $buttons = false;
    }

    if ($_GET["staff_only"] == "true") {
        $query = "SELECT DISTINCT `telegram_user_id` FROM `user_group` LEFT JOIN `user` ON `user`.`id`=`user_group`.`user_id` WHERE `daily_notifications`=" . $_GET["reminder_option"] . " AND `group_id`=" . $group_id[0]."  AND `user`.`id`=2";
    } else {
        $query = "SELECT DISTINCT `telegram_user_id` FROM `user_group` LEFT JOIN `user` ON `user`.`id`=`user_group`.`user_id` WHERE `daily_notifications`=" . $_GET["reminder_option"] . " AND `group_id`=" . $group_id[0];
    }
    echo "4) " . $query . "\n";
    $query_user = $db->query($query);
    while($telegram_user_id = $query_user->fetch_row()) {
        echo "user_id: " . $telegram_user_id[0] . "\n";
        $user->tgUserId = $telegram_user_id[0];
        echo "1) " . $user->sendMessage("<b>" . $day_of_week[date("N", strtotime($date))] . "(" . (int)date("d", strtotime($date)) . "." . (int)date("m", strtotime($date)) . ")</b>" . (in_array($telegram_user_id[0], $has_several_groups) ? " <u>$group_name</u>:\n" : "<b>:</b>\n") . $textMessage, true, 'HTML', $buttons) . "\n";
    }
}

$db->query("INSERT INTO `history` (`object`, `action`) VALUES ('schedule', 'finish');");