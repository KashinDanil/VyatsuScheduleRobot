<?php

function send_remind_to_users($group_id, $except_user_id = 0) {
    $db_connection = explode("\n", file_get_contents("private/db.connection"));
    $db = new mysqli(trim($db_connection[0]), trim($db_connection[1]), trim($db_connection[2]), trim($db_connection[3]));
    $db->query("SET NAMES 'utf8'");
    $db->query("SET CHARACTER SET 'utf8'");
    $db->query("SET SESSION collation_connection = 'utf8_general_ci'");

    if ($except_user_id != 0) {
        $qr = $db->query("SELECT `telegram_user_id` FROM `user_group` LEFT JOIN `user` ON `user`.`id`=`user_group`.`user_id` WHERE `group_id`=$group_id AND `user_id`!=$except_user_id AND `new_schedule_notifications`=1");
    } else {
        $qr = $db->query("SELECT `telegram_user_id` FROM `user_group` LEFT JOIN `user` ON `user`.`id`=`user_group`.`user_id` WHERE `group_id`=$group_id AND `new_schedule_notifications`=1");
    }
    $telegram_user_ids = [];
    while($row = $qr->fetch_row()) {
        $telegram_user_ids[] = $row[0];
    }
    print_r($telegram_user_ids);
    $qr = $db->query("SELECT `telegram_user_id` FROM `user_group` LEFT JOIN `user` ON `user`.`id`=`user_group`.`user_id` group BY `user_id` HAVING COUNT(*) > 1");
    $has_several_groups = [];
    while($row = $qr->fetch_row()) {
        $has_several_groups[] = $row[0];
    }
    $group = $db->query("SELECT * FROM `group` WHERE `id`=$group_id")->fetch_row();
    $group_name = $group[4];
    $user = new VyatsuScheduleROBOT(0, '', null, false);
    if ($group[6] != "") {
        foreach($telegram_user_ids as $telegram_user_id) {
            $user->tgUserId = $telegram_user_id;
            $user->sendMessage($user->phrases["newNextSchedule"][rand(0, count($user->phrases["newNextSchedule"]) - 1)] . (in_array($telegram_user_id, $has_several_groups) ? "\n(<u>$group_name</u>)" : ""), true, 'HTML', new InlineKeyboard([[new InlineKeyboardButton("Получить", "/get_full_next_schedule+$group_name")]]));
        }
    } else {
        foreach($telegram_user_ids as $telegram_user_id) {
            $user->tgUserId = $telegram_user_id;
            $user->sendMessage($user->phrases["newSchedule"][rand(0, count($user->phrases["newSchedule"]) - 1)] . (in_array($telegram_user_id, $has_several_groups) ? "\n(<u>$group_name</u>)" : ""), true, 'HTML', new InlineKeyboard([[new InlineKeyboardButton("Получить", "/after_update_send_schedule+$group_name")]]));
        }
    }
}

function mb_str_split($string) {
    $array = [];
    for($i = 0; $i < mb_strlen($string); $i++) {
        $array[] = mb_substr($string, $i, 1);
    }

    return $array;
}

function decrease($name) {
    $words = preg_split("/[\s-]/", $name);
    $decrease_word = "";
    foreach($words as $word) {
        if (mb_strlen($word) > 2) {
            $decrease_word .= mb_strtoupper(mb_substr($word, 0, 1));
        }
    }
    if ($decrease_word == "ЭФ") $decrease_word = "ЭТФ";

    return $decrease_word;
}

function checking_the_schedule_is_up_to_date($schedule_link, $date = 0, $comparison_sign_to_now = ">=") {
    $pattern = '/\d*_\d*\.pdf/';
    $matches = [];
    preg_match($pattern, $schedule_link, $matches);
    if (count($matches) > 0) {
        $dates = explode("_", stristr($matches[0], ".pdf", true));
        if (count($dates) >= $date) {
            $dates[$date] = substr($dates[$date], 4) . substr($dates[$date], 2, 2) . substr($dates[$date], 0, 2);
            switch ($comparison_sign_to_now) {
                case ">=" :
                    return $dates[$date] >= date("Ymd");
                case "<=" :
                    return $dates[$date] <= date("Ymd");
                case "<" :
                    return $dates[$date] < date("Ymd");
                case ">" :
                    return $dates[$date] > date("Ymd");
                case "==" :
                    return $dates[$date] == date("Ymd");
            }
        }
        return null;
    } else {
        return null;
    }
}

function combination($buttons) {
    $nice_buttons = [];
    $count = count($buttons);
    if ($count <= 5 or $count > 33) {
        foreach($buttons as $button) {
            $nice_buttons[] = [$button];
        }
    } elseif ($count == 10) {// 3 4 3
        foreach($buttons as $key => $button) {
            if ($key < 3) {
                $nice_buttons[0][] = $button;
            } elseif ($key < 7) {
                $nice_buttons[1][] = $button;
            } else {
                $nice_buttons[2][] = $button;
            }
        }
    } elseif (($count % 5 == 0) or in_array($count, [])) {
        foreach($buttons as $key => $button) {
            $nice_buttons[$key / 5][] = $button;
        }
    } elseif (($count % 4 == 0) or in_array($count, [7])) {
        foreach($buttons as $key => $button) {
            $nice_buttons[$key / 4][] = $button;
        }
    } elseif (in_array($count, [6, 9])) {
        foreach($buttons as $key => $button) {
            $nice_buttons[$key / 3][] = $button;
        }
    } elseif ($count == 11) {// 4 3 4
        foreach($buttons as $key => $button) {
            if ($key < 4) {
                $nice_buttons[0][] = $button;
            } elseif ($key < 7) {
                $nice_buttons[1][] = $button;
            } else {
                $nice_buttons[2][] = $button;
            }
        }
    } elseif ($count == 13) {//4 5 4
        foreach($buttons as $key => $button) {
            if ($key < 4) {
                $nice_buttons[0][] = $button;
            } elseif ($key < 9) {
                $nice_buttons[1][] = $button;
            } else {
                $nice_buttons[2][] = $button;
            }
        }
    } elseif ($count == 14) {
        foreach($buttons as $key => $button) {
            if ($key < 5) {
                $nice_buttons[0][] = $button;
            } elseif ($key < 9) {
                $nice_buttons[1][] = $button;
            } else {
                $nice_buttons[2][] = $button;
            }
        }
    } elseif ($count == 17) { // 3 4 3 4 3
        foreach($buttons as $key => $button) {
            if ($key < 3) {
                $nice_buttons[0][] = $button;
            } elseif ($key < 7) {
                $nice_buttons[1][] = $button;
            } elseif ($key < 10) {
                $nice_buttons[2][] = $button;
            } elseif ($key < 14) {
                $nice_buttons[3][] = $button;
            } else {
                $nice_buttons[4][] = $button;
            }
        }
    } elseif ($count == 18) { // 4 3 4 3 4
        foreach($buttons as $key => $button) {
            if ($key < 4) {
                $nice_buttons[0][] = $button;
            } elseif ($key < 7) {
                $nice_buttons[1][] = $button;
            } elseif ($key < 11) {
                $nice_buttons[2][] = $button;
            } elseif ($key < 14) {
                $nice_buttons[3][] = $button;
            } else {
                $nice_buttons[4][] = $button;
            }
        }
    } elseif ($count == 19) { // 4 5 5 5
        foreach($buttons as $key => $button) {
            if ($key < 4) {
                $nice_buttons[0][] = $button;
            } elseif ($key < 9) {
                $nice_buttons[1][] = $button;
            } elseif ($key < 14) {
                $nice_buttons[2][] = $button;
            } else {
                $nice_buttons[3][] = $button;
            }
        }
    } elseif ($count == 21) { // 4 4 5 4 4
        foreach($buttons as $key => $button) {
            if ($key < 4) {
                $nice_buttons[0][] = $button;
            } elseif ($key < 8) {
                $nice_buttons[1][] = $button;
            } elseif ($key < 13) {
                $nice_buttons[2][] = $button;
            } elseif ($key < 17) {
                $nice_buttons[3][] = $button;
            } else {
                $nice_buttons[4][] = $button;
            }
        }
    } elseif ($count == 22) { // 4 5 4 5 4
        foreach($buttons as $key => $button) {
            if ($key < 4) {
                $nice_buttons[0][] = $button;
            } elseif ($key < 9) {
                $nice_buttons[1][] = $button;
            } elseif ($key < 13) {
                $nice_buttons[2][] = $button;
            } elseif ($key < 18) {
                $nice_buttons[3][] = $button;
            } else {
                $nice_buttons[4][] = $button;
            }
        }
    } elseif ($count == 23) { // 5 4 5 4 5
        foreach($buttons as $key => $button) {
            if ($key < 5) {
                $nice_buttons[0][] = $button;
            } elseif ($key < 9) {
                $nice_buttons[1][] = $button;
            } elseif ($key < 14) {
                $nice_buttons[2][] = $button;
            } elseif ($key < 18) {
                $nice_buttons[3][] = $button;
            } else {
                $nice_buttons[4][] = $button;
            }
        }
    } elseif ($count == 24) { // 5 5 4 5 5
        foreach($buttons as $key => $button) {
            if ($key < 5) {
                $nice_buttons[0][] = $button;
            } elseif ($key < 10) {
                $nice_buttons[1][] = $button;
            } elseif ($key < 14) {
                $nice_buttons[2][] = $button;
            } elseif ($key < 19) {
                $nice_buttons[3][] = $button;
            } else {
                $nice_buttons[4][] = $button;
            }
        }
    } elseif ($count == 25) { // 5 5 5 5 5
        foreach($buttons as $key => $button) {
            if ($key < 5) {
                $nice_buttons[0][] = $button;
            } elseif ($key < 10) {
                $nice_buttons[1][] = $button;
            } elseif ($key < 15) {
                $nice_buttons[2][] = $button;
            } elseif ($key < 20) {
                $nice_buttons[3][] = $button;
            } else {
                $nice_buttons[4][] = $button;
            }
        }
    } elseif ($count == 26) { // 5 5 6 5 5
        foreach($buttons as $key => $button) {
            if ($key < 5) {
                $nice_buttons[0][] = $button;
            } elseif ($key < 10) {
                $nice_buttons[1][] = $button;
            } elseif ($key < 16) {
                $nice_buttons[2][] = $button;
            } elseif ($key < 21) {
                $nice_buttons[3][] = $button;
            } else {
                $nice_buttons[4][] = $button;
            }
        }
    } elseif ($count == 27) { // 5 6 5 6 5
        foreach($buttons as $key => $button) {
            if ($key < 5) {
                $nice_buttons[0][] = $button;
            } elseif ($key < 11) {
                $nice_buttons[1][] = $button;
            } elseif ($key < 16) {
                $nice_buttons[2][] = $button;
            } elseif ($key < 22) {
                $nice_buttons[3][] = $button;
            } else {
                $nice_buttons[4][] = $button;
            }
        }
    } elseif ($count == 28) { // 6 5 6 5 6
        foreach($buttons as $key => $button) {
            if ($key < 6) {
                $nice_buttons[0][] = $button;
            } elseif ($key < 11) {
                $nice_buttons[1][] = $button;
            } elseif ($key < 17) {
                $nice_buttons[2][] = $button;
            } elseif ($key < 22) {
                $nice_buttons[3][] = $button;
            } else {
                $nice_buttons[4][] = $button;
            }
        }
    } elseif ($count == 29) { // 6 6 5 6 6
        foreach($buttons as $key => $button) {
            if ($key < 6) {
                $nice_buttons[0][] = $button;
            } elseif ($key < 12) {
                $nice_buttons[1][] = $button;
            } elseif ($key < 17) {
                $nice_buttons[2][] = $button;
            } elseif ($key < 23) {
                $nice_buttons[3][] = $button;
            } else {
                $nice_buttons[4][] = $button;
            }
        }
    } elseif ($count == 30) { // 6 6 6 6 6
        foreach($buttons as $key => $button) {
            if ($key < 6) {
                $nice_buttons[0][] = $button;
            } elseif ($key < 12) {
                $nice_buttons[1][] = $button;
            } elseif ($key < 18) {
                $nice_buttons[2][] = $button;
            } elseif ($key < 24) {
                $nice_buttons[3][] = $button;
            } else {
                $nice_buttons[4][] = $button;
            }
        }
    } elseif ($count == 31) { // 6 6 7 6 6
        foreach($buttons as $key => $button) {
            if ($key < 6) {
                $nice_buttons[0][] = $button;
            } elseif ($key < 12) {
                $nice_buttons[1][] = $button;
            } elseif ($key < 19) {
                $nice_buttons[2][] = $button;
            } elseif ($key < 25) {
                $nice_buttons[3][] = $button;
            } else {
                $nice_buttons[4][] = $button;
            }
        }
    } elseif ($count == 32) { // 6 7 6 7 6
        foreach($buttons as $key => $button) {
            if ($key < 6) {
                $nice_buttons[0][] = $button;
            } elseif ($key < 13) {
                $nice_buttons[1][] = $button;
            } elseif ($key < 19) {
                $nice_buttons[2][] = $button;
            } elseif ($key < 26) {
                $nice_buttons[3][] = $button;
            } else {
                $nice_buttons[4][] = $button;
            }
        }
    } elseif ($count == 33) { // 7 6 7 6 7
        foreach($buttons as $key => $button) {
            if ($key < 7) {
                $nice_buttons[0][] = $button;
            } elseif ($key < 13) {
                $nice_buttons[1][] = $button;
            } elseif ($key < 20) {
                $nice_buttons[2][] = $button;
            } elseif ($key < 27) {
                $nice_buttons[3][] = $button;
            } else {
                $nice_buttons[4][] = $button;
            }
        }
    }

    return $nice_buttons;
}

/**
 * @return mysqli
 */
function get_db_connection() {
    $db_connection = explode("\n", file_get_contents("private/db.connection"));
    return new mysqli(trim($db_connection[0]), trim($db_connection[1]), trim($db_connection[2]), trim($db_connection[3]));
}