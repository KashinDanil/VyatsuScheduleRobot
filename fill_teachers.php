<?php
ini_set('memory_limit', '128M');
ini_set("max_execution_time", "3600");
header('Content-Type: text/html; charset= utf-8');

include "vendor/simple_html_dom.php";
include "functions.php";

$echo = $argv[1] ?? false;

$start = date("Y-m-d H:i:s", strtotime("+3 hour"));
echo "start($start)\n";

$conn = get_db_connection();

$conn->query("INSERT INTO `history` (`object`, `action`) VALUES ('parse_teachers', 'start');");

$teachers_schedule_link = "https://www.vyatsu.ru/studentu-1/spravochnaya-informatsiya/teacher.html";

$teacher = $conn->query("SELECT * FROM `teacher` WHERE 1");
$teachers = [];
while($row = $teacher->fetch_row()) {
    $teachers[$row[1]][$row[2]][$row[3]][$row[4]] = $row;
}

$html = file_get_html($teachers_schedule_link, false, null, 0, 100000000);

if (!$html instanceof simple_html_dom) {
    echo "Не удалось спарсить страницу со страницами с расписанием\n";
    $conn->query("INSERT INTO `history` (`object`, `action`) VALUES ('parse_teachers', 'finish');");
    if ($echo) echo "finish($start -- " . date("Y-m-d H:i:s", strtotime("+3 hour")) . ")\n";
    return;
}

$dates_pattern = '/\d*_\d*\.html/';
foreach($html->find('div.headerEduPrograms') as $institute_name) {
    $insitute = trim(str_replace("(ОРУ)", "", $institute_name->innertext));
    $faculties_table = $institute_name->next_sibling();
    foreach($faculties_table->find('tbody tr td div.fak_name') as $faculty_name) {
        $faculty = trim(str_replace("(ОРУ)", "", $faculty_name->innertext));
        $departments_div = $faculty_name->next_sibling();
        foreach($departments_div->find('table tbody tr td div.kafPeriod') as $department_name) {
            $department = trim(str_replace("(ОРУ)", "", $department_name->innertext));
            foreach($department_name->next_sibling()->find('a[href$="get_db_connection.html"]') as $schedule) {
                preg_match($dates_pattern, $schedule->attr["href"], $matches);
                if (count($matches) == 0) continue;
                $dates = explode("_", stristr($matches[0], ".html", true));
                foreach($dates as $key => $date) {
                    $dates[$key] = substr($date, 4) . substr($date, 2, 2) . substr($date, 0, 2);
                }
                if ($dates[1] < date("Ymd", strtotime("+3 hour"))) continue;//отсекаем все предыдущие расписания
                if ($dates[0] > date("Ymd", strtotime("+3 hour", strtotime("+14 day")))) continue;//отсекаем все излишне поздние расписания
                fill_teachers_schedule($conn, $teachers, $insitute, $faculty, $department, $schedule->attr["href"], $echo);
//                fill_teachers_schedule($conn, $teachers, "Институт математики и информационных систем", "Факультет компьютерных и физико-математических наук", "Кафедра фундаментальной математики", "/reports/schedule/prepod/889_2_15032021_28032021.html", $echo);
//                return;
            }
        }
    }
}

$conn->query("INSERT INTO `history` (`object`, `action`) VALUES ('parse_teachers', 'finish');");
echo "finish($start -- " . date("Y-m-d H:i:s", strtotime("+3 hour")) . ")\n";

/**
 * @param mysqli $conn
 * @param array  $teachers
 * @param string $insitute
 * @param string $faculty
 * @param string $department
 * @param string $link
 * @param bool   $echo
 */
function fill_teachers_schedule($conn, &$teachers, $insitute, $faculty, $department, $link, $echo = true) {
    if ($echo) echo "insitute: {$insitute}\nfaculty: {$faculty}\ndepartment: {$department}\nschedule: {$link}\n";
    $html = file_get_html("https://www.vyatsu.ru{$link}", false, null, 0, 100000000);

    if (!$html instanceof simple_html_dom) {
        echo "Не удалось спарсить страницу(https://www.vyatsu.ru{$link}) с расписанием\n";
        return;
    }

    if ((array_key_exists($insitute, $teachers)) and (array_key_exists($faculty, $teachers[$insitute])) and (array_key_exists($department, $teachers[$insitute][$faculty]))) {
        $sub_teachers = $teachers[$insitute][$faculty][$department];
    } else {
        $sub_teachers = [];
    }

    $column_teacher = [];
    $day = "";
    $teacher_name_pattern = '/^[А-ЯЁа-яё\-]+\s+[А-ЯЁ]\.[А-ЯЁ]\.$/u';
    $time_pattern = "/\d{2}:\d{2}-\d{2}:\d{2}/";
    $group_pattern = "/[А-Яа-яЁё]+-\d{4}-\d{2}-\d{2}/u";
    $link_pattern = "/https\:\/\/teams\.microsoft\.com\/[^\s]*/";
    foreach($html->find('table tr') as $line_number => $tr) {
        $time_start = "";
        $time_finish = "";
        $date_was_changed = false;
        if ($line_number == 1) {
            foreach($tr->find('td span') as $column_number => $teacher) {
                $short_teachers_name = trim(str_replace("&nbsp;", " ", $teacher->innertext));
                if (in_array($short_teachers_name, ["", "День", "Интервал"])) continue;//Пропускаем не преподавателей
                if (!preg_match($teacher_name_pattern, $short_teachers_name)) {
                    if ($echo) echo "Пропускаем преподавателя $short_teachers_name, так как не совпадает с регулярным выражением\n";
                    continue;
                }
                if (!array_key_exists($short_teachers_name, $sub_teachers)) {//создаем преподавателя
                    $query = "INSERT INTO `teacher` (`institute`, `faculty`, `department`, `name`) VALUES ('$insitute', '$faculty', '$department', '$short_teachers_name');";
                    if ($echo) echo $query . "\n";
                    $create = $conn->query($query);
                    if ($create) {
                        $sub_teachers[$short_teachers_name] = $conn->query("SELECT * FROM `teacher` WHERE `name` LIKE '{$short_teachers_name}'")->fetch_row();
                    }
                }
                $column_teacher[$column_number] = $short_teachers_name;
            }
        }
        if ($line_number < 2) continue;//расписание начинается только со второй строки
        foreach($tr->find('td') as $column_number => $td) {
            $lessons = trim(html_entity_decode(str_replace("&nbsp;", " ", $td->innertext)));
            if ($column_number < 2) {
                if (array_key_exists("rowspan", $td->attr) and $td->attr["rowspan"] == 7) {
                    $day = $td->find('div span', 0);
                    if (empty($day)) continue;
                    $day = trim(str_replace("&nbsp;", " ", $day->innertext));
                    echo $day . "\n";
                    $day = preg_replace("/[^\d]/", "", $day);
                    echo $day . "\n";
                    $day = "20" . substr($day, 4, 2) . "-" . substr($day, 2, 2) . "-" . substr($day, 0, 2);
                    echo $day . "\n";
                    $date_was_changed = true;
                    continue;
                } elseif (preg_match($time_pattern, $lessons)) {
                    $time_start = $td->find('span', 0);
                    if (empty($time_start)) {
                        break;//препрываем строку если на ней нет времени
                    }
                    $time_start = explode("-", trim(str_replace("&nbsp;", " ", $time_start->innertext)));
                    if (count($time_start) != 2) break;//препрываем строку если на ней нет времени
                    $time_finish = $time_start[1];
                    $time_start = $time_start[0];
                    continue;
                }
            }
            if (!array_key_exists($column_number, $column_teacher)) continue;//Пропускаем, т к это не преподаватель
            if ($time_start === "") continue;//пропускаем строку без времени
            if ($echo) echo "$column_number) {$line_number}x{$column_number}) " . ($td->attr["class"] ?? "") . ": " . $lessons . ": \n";
            if (!$date_was_changed) {
                $column_number++;
            }
            if (trim($lessons) == "<span></span>") {//убираем все записи занятий для преподавателя и идем дальше
                if (!array_key_exists($column_number, $column_teacher)) continue;//Пропускаем, т к это не преподаватель
                if (array_key_exists($column_teacher[$column_number], $sub_teachers)) {
                    $query = "DELETE FROM `teachers_schedule` WHERE `date`='$day' AND `time_start`='$time_start:00' AND `teacher_id`={$sub_teachers[$column_teacher[$column_number]][0]}";
                    if ($echo) echo $query . "\n";
                    $conn->query($query);
                }
                continue;
            }
            $lessons = explode("<br>", $lessons);
            $group_was_found = false;
            foreach($lessons as &$lesson) {
                $group_matches = [];
                preg_match_all($group_pattern, $lesson, $group_matches);
                if (!empty($group_matches[0])) {
                    $group_was_found = true;
                }
                foreach(array_unique($group_matches[0]) as $group_name) {
                    $subgroups = [];
                    preg_match_all("/$group_name, \d{2}\sподгруппа/", $lesson, $subgroup_matches);
                    foreach($subgroup_matches[0] as $subgroup) {
                        $subgroups[] = (int)mb_substr($subgroup, mb_strlen($group_name) + 2, 2);
                    }
                    if (count($subgroups) > 0) {
                        $lesson = preg_replace("/$group_name\s+" . implode("\s+", $subgroup_matches[0]) . "/", $group_name . " (" . implode(", ", $subgroups) . (count($subgroups) > 1 ? " подгруппы" : " подгруппа") . ")", $lesson);
                    }
                    $lesson = trim($lesson);
                }
            }
            if (!$group_was_found) {
                if ($echo) echo "Не нашли ни одной группы\n";
                continue;
            }
            $lessons = implode("\n", $lessons);
            preg_match_all($link_pattern, $lessons, $links_matches);
            $links_matches = implode("\n", array_unique($links_matches[0]));
            $lessons = str_replace($links_matches, "", $lessons);
            $lessons = preg_replace("/[ \f\r\t\v]+/", " ", $lessons);
            if (!array_key_exists($column_number, $column_teacher)) continue;//Пропускаем, т к это не преподаватель
            if (array_key_exists($column_teacher[$column_number], $sub_teachers)) {
                $query = "INSERT INTO `teachers_schedule` (`teacher_id`, `date`, `time_start`, `time_finish`, `lesson`, `links`) VALUES ({$sub_teachers[$column_teacher[$column_number]][0]}, '$day', '$time_start:00', '$time_finish:00', '$lessons', '$links_matches') ON DUPLICATE KEY UPDATE `lesson`='$lessons', `links`='$links_matches'";
                if ($echo) echo $query . "\n";
                $conn->query($query);
            }
        }
    }
    $teachers[$insitute][$faculty][$department] = $sub_teachers;
}