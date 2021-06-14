<?php
ini_set('memory_limit', '128M');
ini_set("max_execution_time", "3600");
header('Content-Type: text/html; charset= utf-8');

include "vendor/simple_html_dom.php";
include "filling_calendar.php";
include "functions.php";

$echo = true;

$session = false;
if ((count($argv) > 1) and ($argv[1] === "session")) {
    $session = true;
}

$start = date("Y-m-d H:i:s", strtotime("+3 hour"));
echo "start($start)\n";

$conn = get_db_connection();

$conn->query("INSERT INTO `history` (`object`, `action`) VALUES ('parse" . ($session ? "_session" : "") . "', 'start');");

$group = $conn->query("SELECT * FROM `group` WHERE 1");
$groups = [];
while($row = $group->fetch_row()) {
    $groups[strtolower($row[4])] = $row;
}

$link = "https://www.vyatsu.ru/studentu-1/spravochnaya-informatsiya/raspisanie-zanyatiy-dlya-studentov.html";
if ($session) {
    $link = "https://www.vyatsu.ru/internet-gazeta/raspisanie-sessiy-obuchayuschihsya-na-2016-2017-uc.html";
}
$html = file_get_html($link, false, null, 0, 100000000);

if ($html != false) {
    $data = [];
    $pattern_type_and_year = "/[а-я]-\d/u";
    $current_group = 1;
    foreach($html->find("div.fak_name") as $div) {
        $faculty = decrease(trim(str_replace("(факультет)", "", str_replace("(ОРУ)", "", $div->innertext))));
        foreach($html->find("div#fak_id_" . $div->attr["data-fak_id"] . " div.grpPeriod" . ($session ? " a" : "")) as $dom_elem) {
            $name = trim($dom_elem->innertext);
            if ($echo) echo $current_group++ . ") " . $name . " ";
            if ($session) {
                $pattern = '/\d+_\d_\d{8}_\d{8}\.pdf/';
                if (preg_match($pattern, $dom_elem->attr["href"], $matches)) {
                    $data = explode("_", stristr($matches[0], ".pdf", true));//[0 => номер группы, 1 => четность семестра, 2 => дата начала, 3 => дата конца]
                    $date_end = substr($data[3], 4) . substr($data[3], 2, 2) . substr($data[3], 0, 2);
                    if ($date_end >= date("Ymd", strtotime("+3 hour"))) {
                        $schedule = "https://www.vyatsu.ru" . $dom_elem->attr["href"];
                        if ((array_key_exists($name, $groups)) and ($groups[$name][8] != $schedule)) {
                            $query = "UPDATE `group` SET `session` = '$schedule', `updated` = '" . date("Y-m-d H:i:s", strtotime("+3 hour")) . "' WHERE `id`={$groups[$name][0]} AND `name` = '{$groups[$name][4]}';";
                            if ($echo) echo $query;
                            if ($echo) echo "(" . $conn->query($query) . ")"; else $conn->query($query);
                        }
                    }
                }
                if ($echo) echo "\n";
                continue;
            }
//        if ($name != "ПОа-2703-59-00") { echo "\n"; continue; }
            preg_match($pattern_type_and_year, $name, $match);
            if (count($match) > 0) {
                $type = "";
                switch (mb_substr($match[0], 0, 1)) {
                    case "б":
                        $type = "Бакалавриат";
                        break;
                    case "с":
                        $type = "Специалитет";
                        break;
                    case "м":
                        $type = "Магистратура";
                        break;
                    case "а":
                        $type = "Аспирантура";
                        break;
                }
                $id = substr($dom_elem->attr["data-grp_period_id"], 0, -1);
                $year = substr($match[0], -1, 1);

                if (!isset($groups[$name])) {//создаем запись группы
                    if ($echo) echo "Создем группу ";
                    $query = "INSERT INTO `group` (`id`, `type`, `faculty`, `year`, `name`) VALUES ($id, '$type', '" . $faculty . "', $year, '" . $name . "');";
                    if ($echo) echo $query . "\n";
                    if ($echo) echo "(" . $conn->query($query) . ")"; else $conn->query($query);
                    $groups[$name] = $conn->query("SELECT * FROM `group` WHERE `id`=$id AND `name`='$name'")->fetch_row();
                }
                $a = [];
                if (count($html->find("div#listPeriod_{$id}2")) > 0) {
                    $a = $html->find("div#listPeriod_{$id}2")[0]->find("a");
                }
                if (empty($a)) {
                    $a = $html->find("div#listPeriod_{$id}1")[0]->find("a");
                }
                $count_a = count($a);
                if ($count_a > 0) {
                    if ($count_a > 1) {//Если есть предпоследнее расписание, то проверяем какое из них актуальное
                        $schedule = "";
                        $next_schedule = "";
                        $pattern = '/\d*_\d*\.pdf/';
                        for($current = 0; $current < $count_a; $current++) {//Проверяем расписания
                            $matches = [];
                            preg_match($pattern, $a[$current]->attr["href"], $matches);
                            if (count($matches) > 0) {
                                $dates = explode("_", stristr($matches[0], ".pdf", true));
                                foreach($dates as $key => $date) {
                                    $dates[$key] = substr($date, 4) . substr($date, 2, 2) . substr($date, 0, 2);
                                }
                                if ($dates[1] >= date("Ymd", strtotime("+3 hour"))) {
                                    if ($dates[0] <= date("Ymd", strtotime("+3 hour"))) {
                                        $schedule = "https://www.vyatsu.ru" . $a[$current]->attr["href"];
                                    } elseif ($dates[0] > date("Ymd", strtotime("+3 hour"))) {
                                        $next_schedule = "https://www.vyatsu.ru" . $a[$current]->attr["href"];
                                    }
                                }
                            }
                        }
                        if (($next_schedule != "") and ($schedule == "")) {
                            $schedule = $next_schedule;
                            $next_schedule = "";
                        }
//                    echo "schedule: $schedule\n";
//                    echo "next_schedule: $next_schedule\n";

                        if (($groups[$name][5] != $schedule) or ($groups[$name][6] != $next_schedule)) {
                            $query = "UPDATE `group` SET `schedule` = '$schedule', `next_schedule` = '$next_schedule'" . (($groups[$name][6] != $schedule) ? ", `updated` = '" . date("Y-m-d H:i:s", strtotime("+3 hour")) . "'" : "") . " WHERE `id`={$groups[$name][0]} AND `name` = '{$groups[$name][4]}'";
                            if ($echo) echo $query;
                            if ($echo) echo "(" . $conn->query($query) . ")"; else $conn->query($query);
                        }
                    } else {
                        $schedule = "https://www.vyatsu.ru" . $a[0]->attr["href"];
                        if ($groups[$name][5] != $schedule) {
                            $query = "UPDATE `group` SET `schedule` = '$schedule', `next_schedule` = '', `updated` = '" . date("Y-m-d H:i:s", strtotime("+3 hour")) . "' WHERE `id`={$groups[$name][0]} AND `name` = '{$groups[$name][4]}';";
                            if ($echo) echo $query;
                            if ($echo) echo "(" . $conn->query($query) . ")"; else $conn->query($query);
                        }
                    }
                } else {
                    if ($echo) echo "Удаляем все расписания ";
                    $query = "UPDATE `group` SET `schedule` = '', `next_schedule` = '', `updated` = '" . date("Y-m-d H:i:s", strtotime("+3 hour")) . "' WHERE `id`={$groups[$name][0]} AND `name` = '{$groups[$name][4]}';";
                    if ($echo) echo $query;
                    if ($echo) echo "(" . $conn->query($query) . ")"; else $conn->query($query);
                }
            }
            if ($echo) echo "\n";
        }
    }
}
echo "Заполняем таблицу с расписанием\n";
exec("/home/admin/web/Diplomwork/filling_schedule.sh >>/home/admin/web/Diplomwork/logs/filling_schedule.txt");

if (!$session) {
    echo "Отправляем уведомления пользователям о новом расписании\n";
    echo file_get_contents("https://vyatsuschedulerobot.site/send_remind_to_users.php") . "\n";
}

echo "Заполняем календари\n";
filling_calendar($conn);

$conn->query("INSERT INTO `history` (`object`, `action`) VALUES ('parse" . ($session ? "_session" : "") . "', 'finish');");

echo "finish($start -- " . date("Y-m-d H:i:s", strtotime("+3 hour")) . ")\n";