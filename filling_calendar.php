<?php

include "vendor/autoload.php";

function filling_calendar($db) {
    $db->query("INSERT INTO `history` (`object`, `action`, `depth`) VALUES ('calendar', 'start', 1);");

    $groups_query = $db->query("SELECT DISTINCT `group_id` FROM `user_group` WHERE `google_calendar_id`!=''");

    while($group = $groups_query->fetch_row()) {
        $schedule_query = $db->query("SELECT * FROM `schedule` WHERE `group_id`={$group[0]} and `date`>='" . date("Y-m-d") . "' ORDER BY `date` ASC, `time_start` ASC, `sort` ASC");
        $schedule = [];
        while($row = $schedule_query->fetch_row()) {
            $schedule[$row[1]][$row[2]][] = $row;
        }

        if (count($schedule) > 0) {
            $users_query = $db->query("SELECT `user_group`.`google_calendar_id`, `user_group`.`normal_lesson_s_color`, `user_group`.`remote_lesson_s_color`, `user`.`google_calendar_data`, `user`.`id` FROM `user_group` LEFT JOIN `user` ON `user_group`.`user_id`=`user`.`id` WHERE `user_group`.`google_calendar_id`!='' AND `user_group`.`group_id`={$group[0]}");
            while($user = $users_query->fetch_row()) {
                echo "user_id: " . $user[4] . "\n";
                updateCalendar($user[3], $user[0], $user[1], $user[2], $schedule);
            }
        }
    }

    $db->query("INSERT INTO `history` (`object`, `action`, `depth`) VALUES ('calendar', 'finish', 1);");
}


/**
 * @param $service
 * @param $calendar_id
 * @param $date_start
 *
 * @return array
 */
function getEvents($service, $calendar_id, $date_start) {
    $optParams = ["timeMin" => $date_start."T00:00:00+03:00", "timeZone" => 'Europe/Moscow', "orderBy" => 'startTime', 'singleEvents' => true,];
    $events = $service->events->listEvents($calendar_id, $optParams);

    $evs = [];
    while(true) {
        foreach($events->getItems() as $event) {
            $evs[] = $event;
        }
        $pageToken = $events->getNextPageToken();
        if ($pageToken) {
            $optParams['pageToken'] = $pageToken;
            $events = $service->events->listEvents($calendar_id, $optParams);
        } else {
            break;
        }
    }

    return $evs;
}

function updateCalendar($access_token, $calendar_id, $normal_lesson_color, $remote_lesson_color, $schedule) {
    try {
        $client = new Google_Client();
        $client->setAuthConfig(__DIR__ . '/client_secret.json');
        $client->setAccessToken($access_token);
        $service = new Google_Service_Calendar($client);

        $events = getEvents($service, $calendar_id, date("Y-m-d"));
    } catch(Exception $e) {
        echo "Пользователь запретил доступ\n";
        return;
    }
    echo "Count: ".count($events)."\n";
    foreach($schedule as $day => $lessonses) {//Идем по всем дням
        foreach($lessonses as $time_start => $lessons) {//Идем по всем парам в течении дня
            $count_lessons = count($lessons);//Для удаления лишних занятий во время одной пары
            foreach($lessons as $key => $lesson) {
                $event_have_found = 0;
                $lesson_date = $day."T".$lesson[3]."+03:00";//Время конца пары
                foreach($events as $event_key => $event) {
                    $event_date = $event->getEnd()->getDateTime();//Время конца события
                    if ($event_date == $lesson_date) {
                        $event_have_found++;
                        if ($event_have_found - 1 == $key) {//Это для указания нескольких событий в одно время
                            $need_update = false;
                            if ($lesson[4] == "") {//Имеется событие на несуществующую пару, удаляем его
                                echo "Удаляем событие {$lesson[1]} {$lesson[2]}-{$lesson[3]} {$lesson[4]} {$event->getId()}\n";
                                $service->events->delete($calendar_id, $event->getId());
                            } else {//Обновляем событие пары
                                if ($event->getLocation() != $lesson[5]) {//Местоположение пары изменилось
                                    $event->setLocation($lesson[5]);
                                    $need_update = true;
                                }
                                if ($event->getSummary() != $lesson[4]) {//Наименование пары изменилось
                                    $event->setSummary($lesson[4]);
                                    $need_update = true;
                                }
                                if ($event->getDescription() != $lesson[6]) {//Ссылки на пары изменились
                                    $event->setDescription($lesson[6]);
                                    $need_update = true;
                                }
                                if ($lesson[6] == "") {
                                    if ($event->getColorId() != $normal_lesson_color) {//Цвет пары изменился
                                        $event->setColorId($normal_lesson_color);
                                        $need_update = true;
                                    }
                                } else {
                                    if ($event->getColorId() != $remote_lesson_color) {//Цвет пары изменился
                                        $event->setColorId($remote_lesson_color);
                                        $need_update = true;
                                    }
                                }

                                if ($need_update) {
                                    $service->events->update($calendar_id, $event->getId(), $event);
                                    echo "Обновили событие {$lesson[1]} {$lesson[2]}-{$lesson[3]} {$lesson[4]} {$event->getId()}\n";
                                }
                            }
                        } elseif($event_have_found > $count_lessons) {//Нашли событие в одно время большее чем нужно
                            echo "Удаляем событие {$lesson[1]} {$lesson[2]}-{$lesson[3]} {$lesson[4]} {$event->getId()}\n";
                            $service->events->delete($calendar_id, $event->getId());
                            unset($events[$event_key]);
                        }
                    }
                }
                if (($event_have_found - 1 < $key) && ($lesson[4] != "")) {//нашли пару, на которую еще не указано событие
                    $data = ['summary' => $lesson[4], 'start' => ['dateTime' => $day."T".$lesson[2]."+03:00", 'timeZone' => 'Europe/Moscow',], 'end' => ['dateTime' => $day."T".$lesson[3]."+03:00", 'timeZone' => 'Europe/Moscow',], 'colorId' => $normal_lesson_color,];
                    if ($lesson[5] != "") {
                        $data['location'] = $lesson[5];
                    }
                    if ($lesson[6] != "") {
                        $data['description'] = $lesson[6];
                        $data['colorId'] = $remote_lesson_color;
                    }
                    $event = new Google_Service_Calendar_Event($data);
                    $event = $service->events->insert($calendar_id, $event);
                    if ($event) {
                        echo "Создали событие {$lesson[1]} {$lesson[2]}-{$lesson[3]} {$lesson[4]} {$event->getId()}\n";
                    }
                }
            }
        }
    }
}