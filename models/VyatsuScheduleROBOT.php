<?php
include "vendor/simple_html_dom.php";
include "functions.php";
include "classes.php";


class VyatsuScheduleROBOT {
    private $timetables = "https://www.vyatsu.ru/studentu-1/spravochnaya-informatsiya/raspisanie-zanyatiy-dlya-studentov.html";
    public $schedule = "";
    public $next_schedule = "";
    public $date;
    public $text;
    public $data;

    private $db;

    public $info;
    public $groupInfo;

    public $tgUserId;

    public $group_id;

    public $updates = [];
    public $groupUpdates = [];

    public $sentMessages = [];

    public $phrases = ["parse_page" => ["Сейчас посмотрим, что там у нас..", "Одну...секунду...", "Хмм, сейчас проверю на сайте",], "newSchedule" => ["Псс, там расписание появилось",], "newNextSchedule" => ["Псс, там появилось новое расписание", "Я нашел кое-что новенькое...",], "add_group" => ["Выбери тип подготовки:",], "save_group" => ["Отлично\xF0\x9F\x98\x88\nЯ запомнил, где ты учишься", "Превосходно\xF0\x9F\x98\x88\nТеперь я знаю, откуда ты", "Ну что ж..\nЯ знаю, где ты учишься..бойся\xF0\x9F\x98\x88\nУ-а-ха-ха-ха-ха",], "empty_group" => ["Хмм... Ты еще не указал группу, как же я узнаю твое расписание?",], "group_not_saved" => ["Ты пытаешься меня обмануть\xF0\x9F\x98\xA1\nВ ВятГУ нет такой группы\nУкажи свою настоящую учебную группу", "Может быть ты ошибся\xE2\x9D\x93\nПопробуй еще раз", "Я не могу найти такую группу\xF0\x9F\x99\x88\nдавай другую", "Все фигня, давай по новой",], "empty_schedule" => ["Кажется, расписания пока что нет\xF0\x9F\x98\x8C\nА это означает только одно: НЕТ УЧЕБЫ", "Расписания нет, как и твоей стипендии\xF0\x9F\x98\x85",], "empty_next_schedule" => ["Следующего расписания я не нашел\xF0\x9F\x98\x93", "Следующее расписание пока не появилось",], "check_next_schedule" => ["Держи расписание, с любовью\xF0\x9F\x98\x98\nА я пока проверю, есть ли следующее расписание", "Вот твоё текущее расписание\xF0\x9F\x98\x98\nСейчас проверю следующее",], "send_schedule" => ["Получи и распишись\xF0\x9F\x98\x98", "На тебе\xF0\x9F\x98\x98",], "next_schedule" => ["Оо, уже есть следующее расписание", "Кажется, я нашёл следующее расписание",], "empty_today_schedule" => ["Кажется, на этот день расписания для тебя у меня нет!", "На этот день расписания для тебя у меня нет!", "Этот день без расписания",], "default" => ["Я, конечно, многое умею, но общаться с тобой мне пока сложновато\xF0\x9F\x98\x85", "Что ты несёшь\xE2\x81\x89", "Я ТЕ-БЯ НЕ ПО-НИ-МА-Ю!", "\xF0\x9F\x92\xA9\xF0\x9F\x92\xA9\xF0\x9F\x92\xA9", "Мы, конечно, можем пообщаться, но, боюсь, наш диалог сведётся к тому, что каждый будет говорить о своём",], "voice" => ["Какой красивый голос\xF0\x9F\x98\x8D\nКак жаль, что у меня нет ушей, что бы его послушать\xF0\x9F\x98\x85",], "not_today" => ["Это не совсем сегодня, но ближайшее что я нашёл",], "number_of_classes" => ["0" => ["В этот день ты свободен\xF0\x9F\x98\x8F",], "1" => ["",], "2" => ["",], "3" => ["",], "4" => ["",], "5" => ["",], "6" => ["",], "7" => ["",],], "break" => ["Окей-окей, понял",], "warning" => ["\xE2\x9A\xA0\xE2\x9A\xA1\xE2\x9A\xA0<b>Упс...</b>\xE2\x9A\xA0\xE2\x9A\xA1\xE2\x9A\xA0\nЧто-то пошло не так",], "buttons" => ["Как можно было потерять кнопки\xE2\x9D\x93\xF0\x9F\x98\x91", "\xF0\x9F\x98\x92Использовать команды - это, конечно, прикольно.\nНо как насчет кнопок?", "Вот, держи, больше не теряй\xF0\x9F\x98\x89"], "trying_to_use_a_deleted_group" => ["Кажется, ты уже удалил эту группу"], "trying_to_use_a_deleted_calendar" => ["Кажется, ты уже удалил этот календарь"], "dont_changed_group" => ["Ух ты, какой переменчивый", "Как ты резко передумал",], "change_keyboard" => ["\xF0\x9F\x98\xB1Да-здравствует новый вид кнопок", "Просто меняю вид кнопок\xF0\x9F\x99\x88", "Поменял для тебе вид кнопок\xF0\x9F\x98\x98"]];


    public function __construct($user_id, $text, $data = null, $rememberMessage = true) {
        $this->tgUserId = $user_id;
        $this->text = $text;
        $this->data = $data;

        $db_connection = explode("\n", file_get_contents("private/db.connection"));
        $this->db = new mysqli(trim($db_connection[0]), trim($db_connection[1]), trim($db_connection[2]), trim($db_connection[3]));
        $this->db->query("SET NAMES 'utf8'");
        $this->db->query("SET CHARACTER SET 'utf8'");
        $this->db->query("SET SESSION collation_connection = 'utf8_general_ci'");
        $this->info = $this->db->query("SELECT * FROM `user` WHERE `telegram_user_id`=" . $this->tgUserId)->fetch_row();
        if (empty($this->info)) {
            $this->db->query("INSERT INTO `user` (`telegram_user_id`, `group`, `comment`, `last_message`, `penultimate_message`, `first_name`, `last_name`, `username`, `block`, `google_calendar_data`) VALUES ('" . $this->tgUserId . "', '', '', '', '', '" . ((!is_null($data)) ? $data->message->from->first_name : '') . "', '" . (((!is_null($data)) and (!empty($data->message->from->last_name))) ? $data->message->from->last_name : '') . "', '" . ((!is_null($data)) ? $data->message->from->username : '') . "', 0, '');");
            $this->info = $this->db->query("SELECT * FROM `user` WHERE `telegram_user_id`=" . $this->tgUserId)->fetch_row();
        } elseif (!empty($this->info[2])) {//Проверь действительно ли расписание, если да, то установи его в $this->schedule
            $this->groupInfo = $this->db->query("SELECT * FROM `group` WHERE `name`='" . $this->info[2] . "'")->fetch_row();
            if (!empty($this->groupInfo)) {
                $this->group_id = $this->groupInfo[0];
                if (checking_the_schedule_is_up_to_date($this->groupInfo[5], 1, ">=")) {//Если последняя дата расписания >= чем сейчас, то занесем ее в расписание-----------------------------------------
                    $this->schedule = $this->groupInfo[5];
                    if (!empty($this->groupInfo[6])) {
                        $this->next_schedule = $this->groupInfo[6];
                    }
                } elseif (!empty($this->groupInfo[6])) {
                    if (checking_the_schedule_is_up_to_date($this->groupInfo[6], 0, "<=")) {
                        $this->schedule = $this->groupInfo[6];
                        $this->groupUpdates["schedule"] = "\"" . $this->groupInfo[6] . "\"";
                        $this->groupUpdates["next_schedule"] = "\"\"";
                    }
                }
            }
        }

        if ($rememberMessage) {
            $this->updates = ["last_message" => "\"" . $text . "\"", "penultimate_message" => "\"" . $this->info[4] . "\""];
        }
    }

    private function getBotToken() {
        return file_get_contents("private/" . basename(__FILE__, ".php") . ".token");
    }

    private function getGoogleAccessToken() {
        if ($this->info[12] == "") return false;
        $data = json_decode($this->info[12]);
        if (!property_exists($data, "refresh_token")) return false;

        $access_token = $this->request("https://www.googleapis.com/oauth2/v4/token", "POST", "", "grant_type=refresh_token&refresh_token=" . urlencode($data->refresh_token) . "&client_id=" . file_get_contents("private/google.client_id") . "&client_secret=" . file_get_contents("private/google.client_secret"), ["Excpect:", "Accept-Encoding:", "Host: www.googleapis.com", "Cache-Control: no-store", "content-type: application/x-www-form-urlencoded", "Accept:"], "GuzzleHttp/6.2.0 curl/7.70.0 PHP/7.4.10");

        if (empty($access_token)) return false;

        $access_token = json_decode($access_token);
        if (property_exists($access_token, "refresh_token")) {
            $this->updates["google_calendar_data"] = "\"" . json_encode($access_token) . "\"";
        }

        if (!property_exists($access_token, "access_token")) return false;

        return $access_token->access_token;
    }

    public function getMessage($text = null) {
        if ($this->info[9]) {
            $this->sendMessage("Прости, кожаный мешок, но ты в черном списке.\n\nBlack list:\n...\nn) {$this->data->message->from->first_name} {$this->data->message->from->last_name}\n...\n\nВот, видишь, это твое имя", false, 'HTML', new ReplyKeyboardRemove());
            return;
        }
        $text = $text !== null ? explode("+", $text) : explode("+", $this->text);
//        if ($this->info[3] != "Admin") { $this->sendMessage("Напиши это в конце августа, может отвечу\xF0\x9F\x98\xAC");exit;}
        switch ($text[0]) {
            case "/start":
                if (!empty($this->info[2])) {
                    $this->sendMessage("Привет\xF0\x9F\x91\x8B.\nЯ - твой персональный помощник, буду подсказывать учебное расписание.");
                } else {
                    $this->sendMessage("Привет\xF0\x9F\x91\x8B.\nЯ - твой персональный помощник, буду подсказывать учебное расписание.\nСейчас вместо клавиатуры у тебя появились кнопки с текстом. С их помощью ты можешь общаться со мной. Чтобы ознакомиться с полным списком моего функционала, укажи группу, в которой обучаешься, это можно сделать, воспользовавшись кнопкой \"Запомни в какой группе я обучаюсь\"");
                }
                break;
            case "/info":
                $text_message = "Я - твой персональный помощник, подсказываю учебное расписание и по совместительству являюсь дипломной работой своего создателя. \nРедактор текста: <a href='tg://user?id=365664475'>Ботова Валерия</a>;\nДизайнер изображений: <a href='tg://user?id=1095583001'>Рыков Руслан</a>;\nРазработчик проекта: <a href='tg://user?id=311830743'>Кашин Данил</a>.";
                if ($this->data->callback_query->message->message_id) {
                    $settings = glob("images/" . str_replace(":", "_colon_", $this->getBotToken()) . "/settings+*");
                    if (count($settings) > 0) {
                        $settings = $settings[0];
                        $this->editMessageMediaById($this->data->callback_query->message->message_id, explode("+", explode(".", basename($settings))[0])[1], "photo", $text_message, "HTML", new InlineKeyboard([[new InlineKeyboardButton("« Назад", "/other")]]));
                    } else {
                        $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                    }
                } else {
                    $this->sendMessage($text_message, false, 'HTML');
                }
                break;
            case "Покажи мои группы":
                if (!empty($this->info[2])) {
                    $query = $this->db->query("SELECT `group_id` FROM `user_group` WHERE `user_id`=" . $this->info[0]);
                    $groups = [];
                    while($row = $query->fetch_row()) {
                        $groups[] = $row[0];
                    }
                    if ($this->info[13]) {
                        $text_message = "На данный момент активна группа:\n<u>" . $this->info[2] . "</u>";
                    } else {
                        $text_message = "Активные группы:\n";
                    }
                    $query = $this->db->query("SELECT `name` FROM `group` WHERE `id` IN (" . implode(",", $groups) . ")" . (($this->info[13]) ? " AND `name`!='" . $this->info[2] . "'" : ""));
                    $buttons = [];
                    while($row = $query->fetch_row()) {
                        if ($this->info[13]) {
                            $buttons[] = [new InlineKeyboardButton($row[0], "/change_group+" . $row[0])];
                        } else {
                            $text_message .= "[ " . $row[0] . " ]\n";
                        }
                    }
                    if (!$this->info[13]) {
                        $text_message = mb_substr($text_message, 0, -1);
                    }
                    $buttons[] = [new InlineKeyboardButton("Добавить", "/add_group"), new InlineKeyboardButton("Удалить", "/remove_group")];
                    $buttons = new InlineKeyboard($buttons);
                    if ($this->data->callback_query->message->message_id) {
                        $this->editMessageText($this->data->callback_query->message->message_id, $text_message, 'HTML', $buttons);
                    } else {
                        $this->sendMessage($text_message, false, 'HTML', $buttons);
                    }
                } elseif ($this->data->callback_query->message->message_id) {
                    $this->editMessageText($this->data->callback_query->message->message_id, "Кажется, у тебя не выбрана ни одна группа");
                } else {
                    $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                }
                break;
            case "Покажи настройки":
                $settings = glob("images/" . str_replace(":", "_colon_", $this->getBotToken()) . "/settings+*");
                if (count($settings) > 0) {
                    $settings = $settings[0];
                    if (!empty($this->info[2])) {
                        $buttons = new InlineKeyboard([[new InlineKeyboardButton("Уведомления", "/notifications"), new InlineKeyboardButton("Календарь", "/calendar")], [new InlineKeyboardButton("Тех. поддержка", "/support"), new InlineKeyboardButton("Другое", "/other")],]);
                        if ($this->data->callback_query->message->message_id) {
                            $this->editMessageMediaById($this->data->callback_query->message->message_id, explode("+", explode(".", basename($settings))[0])[1], "photo", "Что именно тебя интересует?", "", $buttons);
                        } else {
                            $this->sendDocumentByFileId(explode("+", explode(".", basename($settings))[0])[1], "Что именно тебя интересует?", "photo", false, '', $buttons);
                        }
                    } else {
                        $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                    }
                } else {
                    $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                }
                break;
            case "/other":
                $text_message = "Что именно тебя интересует?";
                if ($this->data->callback_query->message->message_id) {
                    $settings = glob("images/" . str_replace(":", "_colon_", $this->getBotToken()) . "/settings+*");
                    if (count($settings) > 0) {
                        $settings = $settings[0];
                        $buttons = [];
                        $buttons[] = [new InlineKeyboardButton("Приложение", "/application")];
                        $buttons[] = [new InlineKeyboardButton("Всегда спрашивать группу", "/always_ask_the_group")];
                        $buttons[] = [new InlineKeyboardButton("Клавиатура", "/keyboard")];
                        $buttons[] = [new InlineKeyboardButton("О проекте", "/info")];
                        $buttons[] = [new InlineKeyboardButton("« Назад", "Покажи настройки")];
                        $buttons = new InlineKeyboard($buttons);
                        $this->editMessageMediaById($this->data->callback_query->message->message_id, explode("+", explode(".", basename($settings))[0])[1], "photo", $text_message, "HTML", $buttons);
                    } else {
                        $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                    }
                } else {
                    $this->sendMessage($text_message, false, 'HTML');
                }
                break;
            case "/application":
                if ($this->data->callback_query->message->message_id) {
                    $buttons = new InlineKeyboard([[new InlineKeyboardButton("Android", "", "https://play.google.com/store/apps/details?id=ru.dewish.campus"), new InlineKeyboardButton("IOS", "", "https://apps.apple.com/app/id1534975833")], [new InlineKeyboardButton("« Назад", "/other")]]);
                    $this->editMessageCaption($this->data->callback_query->message->message_id, "Вместе с разработчиком одной из самых популярных программ для просмотра расписания в Play Market и App Store мы подключили ВятГУ к отдельному приложению", 'HTML', $buttons);
                } else {
                    $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                }
                break;
            case "/keyboard":
                if ($this->data->callback_query->message->message_id) {
                    if (count($text) == 3) {
                        $this->db->query("UPDATE `user` SET `resize_keyboard`={$text[2]} WHERE `id`={$this->info[0]}");
                        $this->info = $this->db->query("SELECT * FROM `user` WHERE `id`={$this->info[0]}")->fetch_row();
                        $this->sendMessage($this->phrases["change_keyboard"][rand(0, count($this->phrases["change_keyboard"]) - 1)]);
                    } elseif (count($text) == 2) {
                        $this->db->query("UPDATE `user` SET `keyboard_type`={$text[1]} WHERE `id`={$this->info[0]}");
                        $this->info = $this->db->query("SELECT * FROM `user` WHERE `id`={$this->info[0]}")->fetch_row();
                        $this->sendMessage($this->phrases["change_keyboard"][rand(0, count($this->phrases["change_keyboard"]) - 1)]);
                    }
                    $keyboard = glob("images/" . str_replace(":", "_colon_", $this->getBotToken()) . "/keyboard/" . ($this->info[15] == 1 ? "resized_keyboards" : "not_resized_keyboards") . "+*");
                    if (count($keyboard) > 0) {
                        $keyboard = $keyboard[0];
                        $buttons = [];
                        switch ($this->info[14]) {
                            case 1:
                                $buttons[] = [new InlineKeyboardButton("2", "/keyboard+2"), new InlineKeyboardButton("3", "/keyboard+3")];
                                break;
                            case 2:
                                $buttons[] = [new InlineKeyboardButton("1", "/keyboard+1"), new InlineKeyboardButton("3", "/keyboard+3")];
                                break;
                            case 3:
                                $buttons[] = [new InlineKeyboardButton("1", "/keyboard+1"), new InlineKeyboardButton("2", "/keyboard+2")];
                                break;
                        }
                        $buttons[] = [new InlineKeyboardButton($this->info[15] == 1 ? "Не изменять высоту клавиатуры" : "Изменять высоту клавиатуры", "/keyboard++" . (int)(!$this->info[15]))];
                        $buttons[] = [new InlineKeyboardButton("« Назад", "/other")];
                        $buttons = new InlineKeyboard($buttons);
                        $text_message = "Кастомизируй клавиатуру на свой вкус и цвет";
                        $this->editMessageMediaById($this->data->callback_query->message->message_id, explode("+", explode(".", basename($keyboard))[0])[1], "photo", $text_message, "HTML", $buttons);
                    } else {
                        $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                    }
                } else {
                    $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                }
                break;
            case "/always_ask_the_group":
                if ($this->data->callback_query->message->message_id) {
                    if (count($text) == 2) {
                        $this->db->query("UPDATE `user` SET `always_use_main_group`={$text[1]} WHERE `id`={$this->info[0]}");
                        $this->info = $this->db->query("SELECT * FROM `user` WHERE `id`={$this->info[0]}")->fetch_row();
                    }
                    $buttons = [];
                    $buttons[] = [new InlineKeyboardButton($this->info[13] == 0 ? "Использовать главную группу" : "Всегда спрашивать группу", "/always_ask_the_group+" . ((int)!$this->info[13]))];
                    $buttons[] = [new InlineKeyboardButton("« Назад", "/other")];
                    $buttons = new InlineKeyboard($buttons);
                    $this->editMessageCaption($this->data->callback_query->message->message_id, "Если ты подписан сразу на несколько групп, то при каждом запросе расписания я буду уточнять, какую группу ты имеешь в виду.\n" . ($this->info[13] ? "Сейчас всегда используется главная группа" : "Сейчас я всегда спрашиваю группу"), 'HTML', $buttons);
                } else {
                    $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                }
                break;
            case "/support":
                $text_message = "По поводу любых багов, недоработок, замечаний или просто пожеланий можешь написать <a href='tg://user?id=311830743'>разработчику</a>";
                if ($this->data->callback_query->message->message_id) {
                    $this->editMessageCaption($this->data->callback_query->message->message_id, $text_message, 'HTML', new InlineKeyboard([[new InlineKeyboardButton("« Назад", "Покажи настройки")]]));
                } else {
                    $this->sendMessage($text_message, false, 'HTML');
                }
                break;
            case "/new_schedule_notifications":
                if (!empty($this->info[2])) {
                    $current = $this->db->query("SELECT `new_schedule_notifications` FROM `user_group` WHERE `group_id`=" . $text[1] . " AND `user_id`=" . $this->info[0])->fetch_row();
                    if (count($current) > 0) {
                        if (count($text) == 3) {
                            $this->db->query("UPDATE `user_group` SET `new_schedule_notifications`=" . ($text[2] == 1 ? "1" : "0") . " WHERE `group_id`=" . $text[1] . " AND `user_id`=" . $this->info[0]);
                            $current = $this->db->query("SELECT `new_schedule_notifications` FROM `user_group` WHERE `group_id`=" . $text[1] . " AND `user_id`=" . $this->info[0])->fetch_row();
                        }
                        $current = $current[0];
                        $group_name = $this->db->query("SELECT `name` FROM `group` WHERE `id`=" . $text[1])->fetch_row()[0];
                        $buttons = [];
                        $buttons[] = [new InlineKeyboardButton($current == 0 ? "Включить" : "Выключить", "/new_schedule_notifications+" . $text[1] . "+" . ($current == 0 ? "1" : "0"))];
                        $buttons[] = [new InlineKeyboardButton("« Назад", "/change_notifications+" . $text[1])];
                        $buttons = new InlineKeyboard($buttons);
                        $this->editMessageCaption($this->data->callback_query->message->message_id, "Группа: <u>$group_name</u>\nСейчас уведомления о новом расписании <b>" . ($current == 0 ? "выключены" : "включены") . "</b>", 'HTML', $buttons);
                    } else {
                        $this->editMessageCaption($this->data->callback_query->message->message_id, $this->phrases["trying_to_use_a_deleted_group"][rand(0, count($this->phrases["trying_to_use_a_deleted_group"]) - 1)], '', new InlineKeyboard([[new InlineKeyboardButton("« Назад", "/notifications")]]));
                    }
                } elseif ($this->data->callback_query->message->message_id) {
                    $this->editMessageCaption($this->data->callback_query->message->message_id, "Кажется, у тебя не выбрана ни одна группа");
                } else {
                    $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                }
                break;
            case "/daily_notifications":
                if (!empty($this->info[2])) {
                    $current = $this->db->query("SELECT `daily_notifications` FROM `user_group` WHERE `group_id`=" . $text[1] . " AND `user_id`=" . $this->info[0])->fetch_row();
                    if (count($current) > 0) {
                        if (count($text) == 3) {
                            $this->db->query("UPDATE `user_group` SET `daily_notifications`=" . ($text[2] == 3 ? "3" : ($text[2] == 2 ? "2" : ($text[2] == 1 ? "1" : "0"))) . " WHERE `group_id`=" . $text[1] . " AND `user_id`=" . $this->info[0]);
                            $current = $this->db->query("SELECT `daily_notifications` FROM `user_group` WHERE `group_id`=" . $text[1] . " AND `user_id`=" . $this->info[0])->fetch_row();
                        }
                        $current = $current[0];
                        $group_name = $this->db->query("SELECT `name` FROM `group` WHERE `id`=" . $text[1])->fetch_row()[0];
                        $buttons = [];
                        $message_text = "Группа: <u>$group_name</u>\n";
                        if ($current != 0) {
                            $buttons[] = [new InlineKeyboardButton("Выключить", "/daily_notifications+" . $text[1] . "+0")];
                        } else {
                            $message_text .= "Сейчас ежедневные уведомления <b>выключены</b>";
                        }
                        if ($current != 1) {
                            $buttons[] = [new InlineKeyboardButton("В 07:00 на текущий день", "/daily_notifications+" . $text[1] . "+1")];
                        } else {
                            $message_text .= "Сейчас ежедневные уведомления отправляются <b>в 07:00 на текущий день</b>";
                        }
                        if ($current != 2) {
                            $buttons[] = [new InlineKeyboardButton("По окончании занятий на следующий день", "/daily_notifications+" . $text[1] . "+2")];
                        } else {
                            $message_text .= "Сейчас ежедневные уведомления отправляются <b>по окончании занятий на следующий день</b>";
                        }
                        if ($current != 3) {
                            $buttons[] = [new InlineKeyboardButton("В 21:00 на следующий день", "/daily_notifications+" . $text[1] . "+3")];
                        } else {
                            $message_text .= "Сейчас ежедневные уведомления отправляются <b>в 21:00 на следующий день</b>";
                        }
                        $buttons[] = [new InlineKeyboardButton("« Назад", "/change_notifications+" . $text[1])];
                        $buttons = new InlineKeyboard($buttons);
                        $this->editMessageCaption($this->data->callback_query->message->message_id, $message_text, 'HTML', $buttons);
                    } else {
                        $this->editMessageCaption($this->data->callback_query->message->message_id, $this->phrases["trying_to_use_a_deleted_group"][rand(0, count($this->phrases["trying_to_use_a_deleted_group"]) - 1)], '', new InlineKeyboard([[new InlineKeyboardButton("« Назад", "/notifications")]]));
                    }
                } elseif ($this->data->callback_query->message->message_id) {
                    $this->editMessageCaption($this->data->callback_query->message->message_id, "Кажется, у тебя не выбрана ни одна группа");
                } else {
                    $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                }
                break;
            case "/calendar":
                $settings = glob("images/" . str_replace(":", "_colon_", $this->getBotToken()) . "/settings+*");
                if (count($settings) > 0) {
                    $settings = $settings[0];
                    if (!empty($this->info[2])) {
                        $buttons = [];
                        if ($this->info[12] == "") {
                            $buttons[] = [new InlineKeyboardButton("Подключить", "", "https://accounts.google.com/o/oauth2/auth?response_type=code&access_type=offline&client_id=" . file_get_contents("private/google.client_id") . "&redirect_uri=https%3A%2F%2Fvyatsuschedulerobot.site%2Foauth2callback.php&state=" . $this->info[1] . "+" . $this->data->callback_query->message->message_id . "&scope=https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fcalendar&approval_prompt=auto&include_granted_scopes=true")];
                        } else {
                            $buttons[] = [new InlineKeyboardButton("Обновить доступ", "", "https://accounts.google.com/o/oauth2/auth?response_type=code&access_type=offline&client_id=" . file_get_contents("private/google.client_id") . "&redirect_uri=https%3A%2F%2Fvyatsuschedulerobot.site%2Foauth2callback.php&state=" . $this->info[1] . "+" . $this->data->callback_query->message->message_id . "&scope=https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fcalendar&approval_prompt=auto&include_granted_scopes=true")];
                            $buttons[] = [new InlineKeyboardButton("Мои календари", "/calendars_settings")];
                        }
                        $buttons[] = [new InlineKeyboardButton("Подробнее", "/calendar_info")];
                        $buttons[] = [new InlineKeyboardButton("« Назад", "Покажи настройки")];
                        $buttons = new InlineKeyboard($buttons);
                        $text_message = "Можно использовать Google Календарь для просмотра расписания";
                        if (count($text) == 2) {
                            $this->editMessageMediaById($text[1], explode("+", explode(".", basename($settings))[0])[1], "photo", $text_message, "HTML", $buttons);
                        } else {
                            $this->editMessageMediaById($this->data->callback_query->message->message_id, explode("+", explode(".", basename($settings))[0])[1], "photo", $text_message, "HTML", $buttons);
                        }
                    } elseif ($this->data->callback_query->message->message_id) {
                        $this->editMessageCaption($this->data->callback_query->message->message_id, "Кажется, у тебя не выбрана ни одна группа");
                    } else {
                        $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                    }
                } else {
                    $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                }
                break;
            case "/calendars_settings":
                if (!empty($this->info[2])) {
                    if (!empty($this->info[12])) {
                        $query = $this->db->query("SELECT `group_id`, `google_calendar_id` FROM `user_group` WHERE `user_id`=" . $this->info[0]);
                        $groups = [];
                        $free_group_count = 0;
                        while($row = $query->fetch_row()) {
                            if ($row[1] != "") {
                                $groups[$row[0]] = $row[0];
                            } else {
                                $free_group_count++;
                            }
                        }
                        $buttons = [];
                        if (count($groups) > 0) {
                            $query = $this->db->query("SELECT `id`, `name` FROM `group` WHERE `id` IN (" . implode(",", $groups) . ")");
                            while($row = $query->fetch_row()) {
                                $buttons[] = [new InlineKeyboardButton($row[1], "/edit_calendar+" . $row[0])];
                            }
                        }
                        $message_text = "Имеющиеся календари (Нажми на календарь, чтобы изменить):";
                        if (($free_group_count > 0) and (count($buttons) > 0)) {
                            $buttons[] = [new InlineKeyboardButton("Добавить", "/create_calendar"), new InlineKeyboardButton("Удалить", "/remove_calendar")];
                        } elseif ($free_group_count > 0) {
                            $message_text = "Календарей еще нет, попробуй создать:";
                            $buttons[] = [new InlineKeyboardButton("Добавить", "/create_calendar")];
                        } else {
                            $buttons[] = [new InlineKeyboardButton("Удалить", "/remove_calendar")];
                        }
                        $buttons[] = [new InlineKeyboardButton("« Назад", "/calendar")];
                        $buttons = new InlineKeyboard($buttons);
                        $this->editMessageCaption($this->data->callback_query->message->message_id, $message_text, '', $buttons);
                    } elseif ($this->data->callback_query->message->message_id) {
                        $this->editMessageCaption($this->data->callback_query->message->message_id, "Кажется, ты еще не авторизовался с помощью Google аккаунта", "", new InlineKeyboard([[new InlineKeyboardButton("« Назад", "/calendar")]]));
                    } else {
                        $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                    }
                } elseif ($this->data->callback_query->message->message_id) {
                    $this->editMessageCaption($this->data->callback_query->message->message_id, "Кажется, у тебя не выбрана ни одна группа");
                } else {
                    $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                }
                break;
            case "/create_calendar":
                if (!empty($this->info[2])) {
                    if (!empty($this->info[12])) {
                        $id = false;
                        if (count($text) == 3) {
                            $current_group = $this->db->query("SELECT `google_calendar_id` FROM `user_group` WHERE `group_id`='" . $text[1] . "' AND `user_id`=" . $this->info[0])->fetch_row();
                            if (count($current_group) > 0 and ($current_group[0] == "")) {
                                $id = $this->createGoogleCalendar($text[2]);
                                if ($id !== false) {
                                    $this->db->query("UPDATE `user_group` SET `google_calendar_id`='$id' WHERE `group_id`='" . $text[1] . "' AND `user_id`=" . $this->info[0]);
                                }
                            }
                        }
                        $query = $this->db->query("SELECT `group_id` FROM `user_group` WHERE `google_calendar_id`='' AND `user_id`=" . $this->info[0]);
                        $groups = [];
                        while($row = $query->fetch_row()) {
                            $groups[$row[0]] = $row[0];
                        }
                        $buttons = [];
                        if (count($groups) > 0) {
                            $query = $this->db->query("SELECT `id`, `name` FROM `group` WHERE `id` IN (" . implode(",", $groups) . ")");
                            while($row = $query->fetch_row()) {
                                $buttons[] = [new InlineKeyboardButton($row[1], "/create_calendar+" . $row[0] . "+" . $row[1])];
                            }
                        }
                        $buttons[] = [new InlineKeyboardButton("« Назад", "/calendars_settings")];
                        $buttons = new InlineKeyboard($buttons);
                        $this->editMessageCaption($this->data->callback_query->message->message_id, "Выбери группу, для которой нужно создать календарь." . (count($text) == 3 ? ($id !== false ? "\nКалендарь для группы '{$text[2]}' создан. Вскоре в нем отобразятся занятия" : "\nНе удалось создать календарь для группы '{$text[2]}'\nПопробуй обновить токен") : ""), '', $buttons);
                    } elseif ($this->data->callback_query->message->message_id) {
                        $this->editMessageCaption($this->data->callback_query->message->message_id, "Кажется, ты еще не авторизовался с помощью Google аккаунта", "", new InlineKeyboard([[new InlineKeyboardButton("« Назад", "/calendar")]]));
                    } else {
                        $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                    }
                } elseif ($this->data->callback_query->message->message_id) {
                    $this->editMessageCaption($this->data->callback_query->message->message_id, "Кажется, у тебя не выбрана ни одна группа");
                } else {
                    $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                }
                break;
            case "/remove_calendar":
                if (!empty($this->info[2])) {
                    if (!empty($this->info[12])) {
                        $id = false;
                        if (count($text) == 3) {
                            $id = $this->deleteGoogleCalendar($text[1], $text[2]);
                            if ($id !== false) {
                                $this->db->query("UPDATE `user_group` SET `google_calendar_id`='' WHERE `group_id`='" . $text[1] . "' AND `user_id`=" . $this->info[0]);
                            }
                        }
                        $query = $this->db->query("SELECT `group_id` FROM `user_group` WHERE `google_calendar_id`!='' AND `user_id`=" . $this->info[0]);
                        $groups = [];
                        while($row = $query->fetch_row()) {
                            $groups[$row[0]] = $row[0];
                        }
                        $buttons = [];
                        if (count($groups) > 0) {
                            $query = $this->db->query("SELECT `id`, `name` FROM `group` WHERE `id` IN (" . implode(",", $groups) . ")");
                            while($row = $query->fetch_row()) {
                                $buttons[] = [new InlineKeyboardButton($row[1], "/remove_calendar+" . $row[0] . "+" . $row[1])];
                            }
                        }
                        $buttons[] = [new InlineKeyboardButton("« Назад", "/calendars_settings")];
                        $buttons = new InlineKeyboard($buttons);
                        $this->editMessageCaption($this->data->callback_query->message->message_id, "Выбери группу, календарь которой нужно удалить." . (count($text) == 3 ? ($id !== false ? "\nКалендарь для группы '{$text[2]}' удалён" : "\nНе удалось удалить календарь группы '{$text[2]}'\nПопробуй обновить токен") : ""), '', $buttons);
                    } elseif ($this->data->callback_query->message->message_id) {
                        $this->editMessageCaption($this->data->callback_query->message->message_id, "Кажется, ты еще не авторизовался с помощью Google аккаунта", "", new InlineKeyboard([[new InlineKeyboardButton("« Назад", "/calendar")]]));
                    } else {
                        $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                    }
                } elseif ($this->data->callback_query->message->message_id) {
                    $this->editMessageCaption($this->data->callback_query->message->message_id, "Кажется, у тебя не выбрана ни одна группа");
                } else {
                    $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                }
                break;
            case "/edit_calendar":
                $settings = glob("images/" . str_replace(":", "_colon_", $this->getBotToken()) . "/settings+*");
                if (count($settings) > 0) {
                    $settings = $settings[0];
                    if (!empty($this->info[2])) {
                        if (!empty($this->info[12])) {
                            $google_data = $this->db->query("SELECT `google_calendar_id` FROM `user_group` WHERE `group_id`=" . $text[1] . " AND `user_id`=" . $this->info[0])->fetch_row();
                            if (count($google_data) > 0) {
                                if ($google_data[0] != "") {
                                    $buttons[] = [new InlineKeyboardButton("Цвет очного занятия", "/cnlc+" . $text[1])];
                                    $buttons[] = [new InlineKeyboardButton("Цвет дистанционного занятия", "/crlc+" . $text[1])];
                                    $buttons[] = [new InlineKeyboardButton("« Назад", "/calendars_settings")];
                                    $buttons = new InlineKeyboard($buttons);
                                    $this->editMessageMediaById($this->data->callback_query->message->message_id, explode("+", explode(".", basename($settings))[0])[1], "photo", "Что ты хочешь изменить?", "", $buttons);
                                } else {
                                    $this->editMessageMediaById($this->data->callback_query->message->message_id, explode("+", explode(".", basename($settings))[0])[1], "photo", $this->phrases["trying_to_use_a_deleted_calendar"][rand(0, count($this->phrases["trying_to_use_a_deleted_calendar"]) - 1)], "", new InlineKeyboard([[new InlineKeyboardButton("« Назад", "/calendar")]]));
                                }
                            } else {
                                $this->editMessageMediaById($this->data->callback_query->message->message_id, explode("+", explode(".", basename($settings))[0])[1], "photo", $this->phrases["trying_to_use_a_deleted_group"][rand(0, count($this->phrases["trying_to_use_a_deleted_group"]) - 1)], "", new InlineKeyboard([[new InlineKeyboardButton("« Назад", "/calendar")]]));
                            }
                        } elseif ($this->data->callback_query->message->message_id) {
                            $this->editMessageMediaById($this->data->callback_query->message->message_id, explode("+", explode(".", basename($settings))[0])[1], "photo", "Кажется, ты еще не авторизовался с помощью Google аккаунта", "", new InlineKeyboard([[new InlineKeyboardButton("« Назад", "/calendar")]]));
                        } else {
                            $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                        }
                    } elseif ($this->data->callback_query->message->message_id) {
                        $this->editMessageMediaById($this->data->callback_query->message->message_id, explode("+", explode(".", basename($settings))[0])[1], "photo", "Кажется, у тебя не выбрана ни одна группа");
                    } else {
                        $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                    }
                } else {
                    $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                }
                break;
            case "/cnlc":
            case "/change_normal_lesson_s_color":
                $settings = glob("images/" . str_replace(":", "_colon_", $this->getBotToken()) . "/settings+*");
                if (count($settings) > 0) {
                    $settings = $settings[0];
                    if (!empty($this->info[2])) {
                        if (!empty($this->info[12])) {
                            $google_data = $this->db->query("SELECT `google_calendar_id`, `normal_lesson_s_color` FROM `user_group` WHERE `group_id`=" . $text[1] . " AND `user_id`=" . $this->info[0])->fetch_row();
                            if (count($google_data) > 0) {
                                if ($google_data[0] != "") {
                                    $file = glob("images/" . str_replace(":", "_colon_", $this->getBotToken()) . "/Google/event_colors+*");
                                    if (count($file) > 0) {
                                        $file = $file[0];
                                        if (count($text) == 3) {
                                            $this->db->query("UPDATE `user_group` SET `normal_lesson_s_color`={$text[2]} WHERE `group_id`=" . $text[1] . " AND `user_id`=" . $this->info[0]);
                                            $google_data = $this->db->query("SELECT `google_calendar_id`, `normal_lesson_s_color` FROM `user_group` WHERE `group_id`=" . $text[1] . " AND `user_id`=" . $this->info[0])->fetch_row();
                                        }
                                        $buttons = [];
                                        for($i = 1; $i < 12; $i++) {
                                            if ($i != $google_data[1]) {
                                                $buttons[] = new InlineKeyboardButton($i, "/cnlc+" . $text[1] . "+" . $i);
                                            }
                                        }
                                        $buttons = combination($buttons);
                                        $buttons[] = [new InlineKeyboardButton("« Назад", "/edit_calendar+" . $text[1])];
                                        $buttons = new InlineKeyboard($buttons);
                                        $this->editMessageMediaById($this->data->callback_query->message->message_id, explode("+", explode(".", basename($file))[0])[1], "photo", "Сейчас выбран {$google_data[1]} цвет очного занятия.\nНажми на кнопку с номером того цвета, который хочешь использовать", "", $buttons);
                                        if ((count($text) == 3)) {
                                            $this->answerCallbackQuery($this->data->callback_query->id, "Цвет обновится в течении 3 часов");
                                        }
                                    } else {
                                        $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                                    }
                                } else {
                                    $this->editMessageMediaById($this->data->callback_query->message->message_id, explode("+", explode(".", basename($settings))[0])[1], "photo", $this->phrases["trying_to_use_a_deleted_calendar"][rand(0, count($this->phrases["trying_to_use_a_deleted_calendar"]) - 1)], "", new InlineKeyboard([[new InlineKeyboardButton("« Назад", "/calendar")]]));
                                }
                            } else {
                                $this->editMessageMediaById($this->data->callback_query->message->message_id, explode("+", explode(".", basename($settings))[0])[1], "photo", $this->phrases["trying_to_use_a_deleted_group"][rand(0, count($this->phrases["trying_to_use_a_deleted_group"]) - 1)], "", new InlineKeyboard([[new InlineKeyboardButton("« Назад", "/calendar")]]));
                            }
                        } elseif ($this->data->callback_query->message->message_id) {
                            $this->editMessageMediaById($this->data->callback_query->message->message_id, explode("+", explode(".", basename($settings))[0])[1], "photo", "Кажется, ты еще не авторизовался с помощью Google аккаунта", "", new InlineKeyboard([[new InlineKeyboardButton("« Назад", "/calendar")]]));
                        } else {
                            $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                        }
                    } elseif ($this->data->callback_query->message->message_id) {
                        $this->editMessageMediaById($this->data->callback_query->message->message_id, explode("+", explode(".", basename($settings))[0])[1], "photo", "Кажется, у тебя не выбрана ни одна группа");
                    } else {
                        $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                    }
                } else {
                    $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                }
                break;
            case "/crlc":
            case "/change_remote_lesson_s_color":
                $settings = glob("images/" . str_replace(":", "_colon_", $this->getBotToken()) . "/settings+*");
                if (count($settings) > 0) {
                    $settings = $settings[0];
                    if (!empty($this->info[2])) {
                        if (!empty($this->info[12])) {
                            $google_data = $this->db->query("SELECT `google_calendar_id`, `remote_lesson_s_color` FROM `user_group` WHERE `group_id`=" . $text[1] . " AND `user_id`=" . $this->info[0])->fetch_row();
                            if (count($google_data) > 0) {
                                if ($google_data[0] != "") {
                                    $file = glob("images/" . str_replace(":", "_colon_", $this->getBotToken()) . "/Google/event_colors+*");
                                    if (count($file) > 0) {
                                        $file = $file[0];
                                        if (count($text) == 3) {
                                            $this->db->query("UPDATE `user_group` SET `remote_lesson_s_color`={$text[2]} WHERE `group_id`=" . $text[1] . " AND `user_id`=" . $this->info[0]);
                                            $google_data = $this->db->query("SELECT `google_calendar_id`, `remote_lesson_s_color` FROM `user_group` WHERE `group_id`=" . $text[1] . " AND `user_id`=" . $this->info[0])->fetch_row();
                                        }
                                        $buttons = [];
                                        for($i = 1; $i < 12; $i++) {
                                            if ($i != $google_data[1]) {
                                                $buttons[] = new InlineKeyboardButton($i, "/crlc+" . $text[1] . "+" . $i);
                                            }
                                        }
                                        $buttons = combination($buttons);
                                        $buttons[] = [new InlineKeyboardButton("« Назад", "/edit_calendar+" . $text[1])];
                                        $buttons = new InlineKeyboard($buttons);
                                        $this->editMessageMediaById($this->data->callback_query->message->message_id, explode("+", explode(".", basename($file))[0])[1], "photo", "Сейчас выбран {$google_data[1]} цвет дистанционного занятия.\nНажми на кнопку с номером того цвета, который хочешь использовать", "", $buttons);
                                        if ((count($text) == 3)) {
                                            $this->answerCallbackQuery($this->data->callback_query->id, "Цвет обновится в течении 3 часов");
                                        }
                                    } else {
                                        $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                                    }
                                } else {
                                    $this->editMessageMediaById($this->data->callback_query->message->message_id, explode("+", explode(".", basename($settings))[0])[1], "photo", $this->phrases["trying_to_use_a_deleted_calendar"][rand(0, count($this->phrases["trying_to_use_a_deleted_calendar"]) - 1)], "", new InlineKeyboard([[new InlineKeyboardButton("« Назад", "/calendar")]]));
                                }
                            } else {
                                $this->editMessageMediaById($this->data->callback_query->message->message_id, explode("+", explode(".", basename($settings))[0])[1], "photo", $this->phrases["trying_to_use_a_deleted_group"][rand(0, count($this->phrases["trying_to_use_a_deleted_group"]) - 1)], "", new InlineKeyboard([[new InlineKeyboardButton("« Назад", "/calendar")]]));
                            }
                        } elseif ($this->data->callback_query->message->message_id) {
                            $this->editMessageMediaById($this->data->callback_query->message->message_id, explode("+", explode(".", basename($settings))[0])[1], "photo", "Кажется, ты еще не авторизовался с помощью Google аккаунта", "", new InlineKeyboard([[new InlineKeyboardButton("« Назад", "/calendar")]]));
                        } else {
                            $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                        }
                    } elseif ($this->data->callback_query->message->message_id) {
                        $this->editMessageMediaById($this->data->callback_query->message->message_id, explode("+", explode(".", basename($settings))[0])[1], "photo", "Кажется, у тебя не выбрана ни одна группа");
                    } else {
                        $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                    }
                } else {
                    $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                }
                break;
            case "/calendar_info":
                $example = glob("images/" . str_replace(":", "_colon_", $this->getBotToken()) . "/help/calendar_example+*");
                if (count($example) > 0) {
                    $example = $example[0];
                    if ($this->data->callback_query->message->message_id) {
                        $text_message = "Для использования этого функционала нужно подключить меня к Google календарю\xF0\x9F\x93\x85, " . ((!empty($this->info[12])) ? "вижу, что ты это уже сделал, замечательно\xF0\x9F\x91\x8D" : "для этого нужно перейти назад и воспользоваться кнопкой \"Подключить\"") . ".\nВ этом разделе ты можешь создавать/удалять/изменять календари на имеющиеся группы. \nВ изменении календаря ты можешь устанавливать различные цвета на очные и дистанционные занятия, для более наглядного отображения.\nПосле создания календаря занятия в нём будут автоматически обновляться.\nДля чего это нужно? Можно придумать множество применений этому, но первое, что приходит на ум - это вывести виджет на рабочий стол телефона, таким образом, расписание всегда будет на глазах. Но перед тем как делать это, нужно скачать приложение для Google календаря (если оно еще не установлено), для этого выбери операционную систему своего устройства:";
                        $buttons = new InlineKeyboard([[new InlineKeyboardButton("Android", "", "https://play.google.com/store/apps/details?id=com.google.android.calendar"), new InlineKeyboardButton("IOS", "", "https://apps.apple.com/ru/app/google-календарь/id909319292")], [new InlineKeyboardButton("« Назад", "/calendar")]]);
                        $this->editMessageMediaById($this->data->callback_query->message->message_id, explode("+", explode(".", basename($example))[0])[1], "photo", $text_message, "HTML", $buttons);
                    } else {
                        $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                    }
                } else {
                    $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                }
                break;
            case "/change_notifications":
                if (!empty($this->info[2])) {
                    if (count($this->db->query("SELECT `id` FROM `user_group` WHERE `group_id`=" . $text[1] . " AND `user_id`=" . $this->info[0])->fetch_row()) > 0) {
                        $group_name = $this->db->query("SELECT `name` FROM `group` WHERE `id`=" . $text[1])->fetch_row()[0];
                        $buttons = [];
                        $buttons[] = [new InlineKeyboardButton("Ежедневные", "/daily_notifications+" . $text[1])];
                        $buttons[] = [new InlineKeyboardButton("О новом расписании", "/new_schedule_notifications+" . $text[1])];
                        $buttons[] = [new InlineKeyboardButton("« Назад", "/notifications")];
                        $buttons = new InlineKeyboard($buttons);
                        $this->editMessageCaption($this->data->callback_query->message->message_id, "Группа: <u>$group_name</u>\nКакие уведомления будем изменять?", 'HTML', $buttons);
                    } else {
                        $this->editMessageCaption($this->data->callback_query->message->message_id, $this->phrases["trying_to_use_a_deleted_group"][rand(0, count($this->phrases["trying_to_use_a_deleted_group"]) - 1)], '', new InlineKeyboard([[new InlineKeyboardButton("« Назад", "/notifications")]]));
                    }
                } elseif ($this->data->callback_query->message->message_id) {
                    $this->editMessageCaption($this->data->callback_query->message->message_id, "Кажется, у тебя не выбрана ни одна группа");
                } else {
                    $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                }
                break;
            case "/notifications":
                if (!empty($this->info[2])) {
                    $query = $this->db->query("SELECT `group_id` FROM `user_group` WHERE `user_id`=" . $this->info[0]);
                    $groups = [];
                    while($row = $query->fetch_row()) {
                        $groups[$row[0]] = $row[0];
                    }
                    $query = $this->db->query("SELECT `id`, `name` FROM `group` WHERE `id` IN (" . implode(",", $groups) . ")");
                    $buttons = [];
                    while($row = $query->fetch_row()) {
                        $buttons[] = [new InlineKeyboardButton($row[1], "/change_notifications+" . $row[0])];
                    }
                    $buttons[] = [new InlineKeyboardButton("« Назад", "Покажи настройки")];
                    $buttons = new InlineKeyboard($buttons);
                    $this->editMessageCaption($this->data->callback_query->message->message_id, "Выбери группу, уведомления которой собираешься изменить:", '', $buttons);
                } elseif ($this->data->callback_query->message->message_id) {
                    $this->editMessageCaption($this->data->callback_query->message->message_id, "Кажется, у тебя не выбрана ни одна группа");
                } else {
                    $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                }
                break;
            case "/buttons":
                $this->sendMessage($this->phrases["buttons"][rand(0, count($this->phrases["buttons"]) - 1)]);
                break;
            case "/change_group":
                if (!empty($this->info[2])) {
                    if (!$this->info[13]) {
                        $this->getMessage("Покажи мои группы");
                        exit;
                    }
                    $query = $this->db->query("SELECT `group_id` FROM `user_group` WHERE `user_id`=" . $this->info[0]);
                    $groups = [];
                    while($row = $query->fetch_row()) {
                        $groups[] = $row[0];
                    }
                    if (in_array($this->db->query("SELECT `id` FROM `group` WHERE `name`='" . $text[1] . "'")->fetch_row()[0], $groups)) {//проверяем что указанная группа все еще присутсвует у пользователя
                        $this->info[2] = $text[1];
                        $this->updates["group"] = "\"" . $text[1] . "\"";
                    }
                    $query = $this->db->query("SELECT `name` FROM `group` WHERE `id` IN (" . implode(",", $groups) . ") AND `name`!='" . $this->info[2] . "'");
                    $buttons = [];
                    while($row = $query->fetch_row()) {
                        $buttons[] = [new InlineKeyboardButton($row[0], "/change_group+" . $row[0])];
                    }
                    $buttons[] = [new InlineKeyboardButton("Добавить", "/add_group"), new InlineKeyboardButton("Удалить", "/remove_group")];
                    $buttons = new InlineKeyboard($buttons);
                    $this->editMessageText($this->data->callback_query->message->message_id, "На данный момент активна группа:\n<u>" . $this->info[2] . "</u>", 'HTML', $buttons);
                } else {
                    $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                }
                break;
            case "/remove_group":
                $query = $this->db->query("SELECT `group_id` FROM `user_group` WHERE `user_id`=" . $this->info[0]);
                $groups = [];
                while($row = $query->fetch_row()) {
                    $groups[$row[0]] = $row[0];
                }
                if (count($text) > 1) {
                    if ($this->groupInfo[0] != $text[1]) {
                        $this->deleteGoogleCalendar($text[1], $text[2]);
                        $this->db->query("DELETE FROM `user_group` WHERE `user_id`=" . $this->info[0] . " AND `group_id`=" . $text[1]);
                        unset($groups[$text[1]]);
                    } elseif (count($groups) > 1) {
                        unset($groups[$text[1]]);
                        $this->deleteGoogleCalendar($text[1], $text[2]);
                        $this->db->query("DELETE FROM `user_group` WHERE `user_id`=" . $this->info[0] . " AND `group_id`=" . $text[1]);
                        $this->updates["group"] = "\"" . $this->db->query("SELECT `name` FROM `group` WHERE `id`=" . end($groups))->fetch_row()[0] . "\"";
                    } else {
                        unset($groups[$text[1]]);
                        $this->deleteGoogleCalendar($text[1], $text[2]);
                        $this->db->query("DELETE FROM `user_group` WHERE `user_id`=" . $this->info[0] . " AND `group_id`=" . $text[1]);
                        $this->editMessageText($this->data->callback_query->message->message_id, "Больше удалять нечего\xF0\x9F\x98\x85");
                        $this->updates["group"] = "\"\"";
                        $this->info[2] = "";
                        $this->sendMessage("Предлагаю начать сначала.\nПривет\xF0\x9F\x91\x8B, я - бот, упрощающий твою жизнь. Больше не нужно возиться с постоянно обновляемым расписание в pdf: я все это сделаю за тебя.");
                        $this->Update();
                        return;
                    }
                }
                $query = $this->db->query("SELECT `id`, `name` FROM `group` WHERE `id` IN (" . implode(",", $groups) . ")");
                $buttons = [];
                while($row = $query->fetch_row()) {
                    $buttons[] = [new InlineKeyboardButton($row[1], "/remove_group+" . $row[0] . "+" . $row[1])];
                }
                $buttons[] = [new InlineKeyboardButton("« Назад", "Покажи мои группы")];
                $buttons = new InlineKeyboard($buttons);
                $this->editMessageText($this->data->callback_query->message->message_id, "Выбери группу, которую нужно удалить:", '', $buttons);
                break;
            case "Запомни в какой группе я обучаюсь":
                $buttons = [];
                $query = $this->db->query("SELECT DISTINCT `type` FROM `group` WHERE 1");
                for($counter = 0; $row = $query->fetch_row(); $counter++) {
                    $buttons[$counter][] = new InlineKeyboardButton($row[0], "/add_group+" . $row[0]);
                }
                $buttons = new InlineKeyboard($buttons);
                $this->sendMessage($this->phrases["add_group"][0], false, '', $buttons);
                break;
            case "/add_group":
//                if ($this->info[4] == "/add_group") {
//                    $this->updates = [];
//                }
                if (count($text) == 1) {
                    $buttons = [];
                    $query = $this->db->query("SELECT DISTINCT `type` FROM `group` WHERE 1");
                    while($row = $query->fetch_row()) {//выбор типа
                        $buttons[] = [new InlineKeyboardButton($row[0], "/add_group+" . $row[0])];
                    }
                    if (!empty($this->info[2])) {
                        $buttons[] = [new InlineKeyboardButton("« Назад", "Покажи мои группы")];
                    }
                    $buttons = new InlineKeyboard($buttons);
                    $this->editMessageText($this->data->callback_query->message->message_id, $this->phrases["add_group"][0], '', $buttons);
                } elseif (count($text) == 2) {//Уже указан тип группы
                    $buttons = [];
                    $query = $this->db->query("SELECT DISTINCT `faculty` FROM `group` WHERE `type`='" . $text[1] . "'");
                    while($row = $query->fetch_row()) {
                        $buttons[] = new InlineKeyboardButton($row[0], "/add_group+" . $text[1] . "+" . $row[0]);
                    }
                    $buttons = combination($buttons);
                    $buttons[] = [new InlineKeyboardButton("« Назад", "/add_group")];
                    $buttons = new InlineKeyboard($buttons);
                    $this->editMessageText($this->data->callback_query->message->message_id, "Тип подготовки: " . $text[1] . ".\n" . str_replace("тип подготовки", "факультет", $this->phrases["add_group"][0]), '', $buttons);
                } elseif (count($text) == 3) {//Уже указан тип группы и факультет
                    $buttons = [];
                    $query = $this->db->query("SELECT DISTINCT `year` FROM `group` WHERE `type`='" . $text[1] . "' AND `faculty`='" . $text[2] . "' ORDER BY `year` DESC");
                    while($row = $query->fetch_row()) {
                        $buttons[] = new InlineKeyboardButton($row[0], "/add_group+" . $text[1] . "+" . $text[2] . "+" . $row[0]);
                    }
                    $buttons = combination($buttons);
                    $buttons[] = [new InlineKeyboardButton("« Назад", "/add_group+" . $text[1])];
                    $buttons = new InlineKeyboard($buttons);
                    $this->editMessageText($this->data->callback_query->message->message_id, "Тип подготовки: " . $text[1] . ";\nФакультет: " . $text[2] . ".\n" . str_replace("тип подготовки", "курс", $this->phrases["add_group"][0]), '', $buttons);
                } elseif (in_array(count($text), [4, 5])) {//Уже указан тип группы, факультет и номер курса
                    $buttons = [];
                    $offset = $text[4] ?? 0;
                    $limit = 5;

                    $query = $this->db->query("SELECT `group_id` FROM `user_group` WHERE `user_id`=" . $this->info[0]);
                    $groups = [];
                    while($row = $query->fetch_row()) {
                        $groups[] = $row[0];
                    }

                    $count = $query = $this->db->query("SELECT DISTINCT COUNT(`name`) FROM `group` WHERE `type`='" . $text[1] . "' AND `faculty`='" . $text[2] . "' AND `year`=" . $text[3] . ((count($groups) > 0) ? " AND `id` NOT IN (" . implode(", ", $groups) . ")" : ""))->fetch_row()[0];
                    $query = $this->db->query("SELECT DISTINCT `name` FROM `group` WHERE `type`='" . $text[1] . "' AND `faculty`='" . $text[2] . "' AND `year`=" . $text[3] . ((count($groups) > 0) ? " AND `id` NOT IN (" . implode(", ", $groups) . ")" : "") . " LIMIT $limit OFFSET $offset");
                    while($row = $query->fetch_row()) {
//                        $buttons[] = [new InlineKeyboardButton($row[0], "/add_group+".mb_strtolower($text[1])."+".$text[2]."+".$text[3]."+".$offset."+".$row[0])];
                        $buttons[] = [new InlineKeyboardButton($row[0], "/add_group+" . $text[1] . "++" . $text[3] . "+" . $offset . "+" . $row[0])];
                    }
                    //pagination
                    if ($offset + $limit < $count) {//Добавляем кнопку следующей страницы
                        if ($offset == 0) {
                            $buttons[] = [new InlineKeyboardButton("« Назад", "/add_group+" . $text[1] . "+" . $text[2]), new InlineKeyboardButton("\xE2\x8F\xA9", "/add_group+" . $text[1] . "+" . $text[2] . "+" . $text[3] . "+" . ($offset + $limit))];
                        } else {//Добавляем кнопку предыдущей страницы
                            $buttons[] = [new InlineKeyboardButton("\xE2\x8F\xAA", "/add_group+" . $text[1] . "+" . $text[2] . "+" . $text[3] . "+" . ($offset - $limit)), new InlineKeyboardButton("« Назад", "/add_group+" . $text[1] . "+" . $text[2]), new InlineKeyboardButton("\xE2\x8F\xA9", "/add_group+" . $text[1] . "+" . $text[2] . "+" . $text[3] . "+" . ($offset + $limit))];
                        }
                    } elseif ($offset != 0) {//Добавляем кнопку предыдущей страницы
                        $buttons[] = [new InlineKeyboardButton("\xE2\x8F\xAA", "/add_group+" . $text[1] . "+" . $text[2] . "+" . $text[3] . "+" . ($offset - $limit)), new InlineKeyboardButton("« Назад", "/add_group+" . $text[1] . "+" . $text[2])];
                    } else {
                        $buttons[] = [new InlineKeyboardButton("« Назад", "/add_group+" . $text[1] . "+" . $text[2])];
                    }
                    $buttons = new InlineKeyboard($buttons);
                    $this->editMessageText($this->data->callback_query->message->message_id, "Тип подготовки: " . $text[1] . ";\nФакультет: " . $text[2] . ";\nКурс: " . $text[3] . ".\n" . (($count > 0) ? str_replace("тип подготовки", "группу", $this->phrases["add_group"][0]) : "Все группы уже выбраны."), '', $buttons);
                } elseif (count($text) == 6) {
                    $groupInfo = $this->db->query("SELECT * FROM `group` WHERE `name`='" . $text[5] . "'")->fetch_row();
                    $this->db->query("INSERT INTO `user_group` (`user_id`, `group_id`) VALUES ('" . $this->info[0] . "', '" . $groupInfo[0] . "');");//Создаем привязку пользователя к группе
                    if (!empty($groupInfo)) {
                        $send_new_message = empty($this->groupInfo);
                        $this->groupInfo = $groupInfo;
                        $this->info[2] = $text[5];
                        $this->updates["group"] = "\"" . $text[5] . "\"";
                        $this->getMessage("Покажи мои группы");
                        if ($send_new_message) {
                            $this->sendMessage("Теперь тебе доступны все мои функции!\nКоротко о том, что я умею: могу отправить тебе расписание текстом, могу отправить файл с расписанием, прямиком с <a href='http://vyatsu.ru/'>сайта ВятГУ</a>, а могу автоматически отображать расписание в Google календаре (загляни в настройки, чтобы узнать поподробнее).\nТакже можешь указать сразу несколько учебных групп и просматривать их расписание параллельно!", false, "HTML", "", true);
                        }
                    } else {
                        $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                    }
                } else {
                    $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                }
                break;
            case "Омфср":
            case "Отправь мне файл с расписанием":
                if (!empty($this->info[2])) {
                    $this->sendSchedule();
                } else {
                    $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                }
                break;
            case "/after_update_send_schedule":
                if (count($text) == 2) {
                    $schedule = $this->db->query("SELECT `schedule` FROM `group` WHERE `name`='" . $text[1] . "'")->fetch_row()[0];
                    if ($schedule) {
                        $this->sendDocument($schedule, $this->phrases["send_schedule"][rand(0, count($this->phrases["send_schedule"]) - 1)] . ($this->db->query("SELECT COUNT(*) FROM `user_group` WHERE `user_id`={$this->info[0]} LIMIT 1")->fetch_row()[0] > 1 ? "\n[<u>{$text[1]}</u>]" : ""), "document", false, "HTML");
                    } else {
                        $this->sendMessage("А все, надо было раньше" . ($this->db->query("SELECT COUNT(*) FROM `user_group` WHERE `user_id`={$this->info[0]} LIMIT 1")->fetch_row()[0] > 1 ? "\n<u>{$text[1]}</u>]" : ""), false, "HTML");
                    }
                } else {
                    $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                }
                break;
            case "/get_full_next_schedule":
                if (count($text) == 2) {
                    $next_schedule = $this->db->query("SELECT `next_schedule` FROM `group` WHERE `name`='" . $text[1] . "'")->fetch_row()[0];
                    if ($next_schedule) {
                        $this->sendDocument($next_schedule, $this->phrases["send_schedule"][rand(0, count($this->phrases["send_schedule"]) - 1)] . ($this->db->query("SELECT COUNT(*) FROM `user_group` WHERE `user_id`={$this->info[0]} LIMIT 1")->fetch_row()[0] > 1 ? "\n[<u>{$text[1]}</u>]" : ""), "document", false, "HTML");
                    } else {
                        $this->sendMessage("А все, надо было раньше" . ($this->db->query("SELECT COUNT(*) FROM `user_group` WHERE `user_id`={$this->info[0]} LIMIT 1")->fetch_row()[0] > 1 ? "\n[<u>{$text[1]}</u>]" : ""), false, "HTML");
                    }
                } else {
                    $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                }
                break;
            case "Прнс":
            case "Покажи расписание на сегодня":
                if (!empty($this->info[2])) {
                    if (!empty($text[1])) $this->date = $text[1];
                    if (!empty($text[2])) $this->group_id = $text[2];
                    $this->sendTodaySchedule();
                } else {
                    $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                }
                break;
            case "Прнз":
            case "Покажи расписание на завтра":
                if (!empty($this->info[2])) {
                    if (!empty($text[1])) $this->group_id = $text[1];
                    $this->date = date("Y-m-d", strtotime("+1 day"));
                    $this->sendTodaySchedule();
                } else {
                    $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                }
                break;
            case "/get_building":
                if (!empty($text[1])) $this->sendBuildingLocation($text[1]);
                break;
            case "Где находится корпус номер..":
                $buttons = new InlineKeyboard([[new InlineKeyboardButton("1", "/get_building+1"), new InlineKeyboardButton("2", "/get_building+2"), new InlineKeyboardButton("3", "/get_building+3"), new InlineKeyboardButton("4", "/get_building+4"), new InlineKeyboardButton("5", "/get_building+5"),], [new InlineKeyboardButton("6", "/get_building+6"), new InlineKeyboardButton("7", "/get_building+7"), new InlineKeyboardButton("8", "/get_building+8"), new InlineKeyboardButton("9", "/get_building+9"),], [new InlineKeyboardButton("10", "/get_building+10"), new InlineKeyboardButton("11", "/get_building+11"), new InlineKeyboardButton("12", "/get_building+12"), new InlineKeyboardButton("13", "/get_building+13"), new InlineKeyboardButton("14", "/get_building+14"),], [new InlineKeyboardButton("15", "/get_building+15"), new InlineKeyboardButton("16", "/get_building+16"), new InlineKeyboardButton("17", "/get_building+17"), new InlineKeyboardButton("18", "/get_building+18"),], [new InlineKeyboardButton("19", "/get_building+19"), new InlineKeyboardButton("20", "/get_building+20"), new InlineKeyboardButton("21", "/get_building+21"), new InlineKeyboardButton("22", "/get_building+22"), new InlineKeyboardButton("23", "/get_building+23"),],]);
                $this->sendMessage("Номер... Номер какой? Я что по-твоему экстрасенс?", false, '', $buttons);
                break;
            case "/break":
                if ($this->info[3] == "Admin") {
                    $this->sendMessage($this->phrases["break"][rand(0, count($this->phrases["break"]) - 1)]);
                } else {
                    $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                }
                break;
            case "/sendAll":
                if ($this->info[3] == "Admin") {
                    $this->sendMessage("Напиши что им передать", false, '', new InlineKeyboard([[new InlineKeyboardButton("Отменить", "/break")]]));
                } else {
                    $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                }
                break;
            case "/dont_changed_group":
                $this->sendMessage($this->phrases["dont_changed_group"][rand(0, count($this->phrases["dont_changed_group"]) - 1)]);
                break;
            case "Покажи расписание преподавателя":
                $query = $this->db->query("SELECT DISTINCT LEFT(`name`, 1) AS `first_letter` FROM `teacher` ORDER BY `first_letter`");
                $buttons = [];
                while($row = $query->fetch_row()) {
                    $buttons[] = new InlineKeyboardButton($row[0], "/teacher+" . $row[0]);
                }
                $buttons = new InlineKeyboard(combination($buttons));
                $text = "Расписание какого именно преподавателя ты хочешь узнать? Выбери первую букву фамилии преподавателя";
                if ($this->data->callback_query->message->message_id) {
                    $this->editMessageText($this->data->callback_query->message->message_id, $text, 'HTML', $buttons);
                } else {
                    $this->sendMessage($text, false, "", $buttons);
                }
                break;
            case "/teacher":
                $buttons = [];
                $offset = $text[2] ?? 0;
                $limit = 5;

                $count = $query = $this->db->query("SELECT COUNT(*) FROM `teacher` WHERE `name` LIKE '{$text[1]}%'")->fetch_row()[0];
                $query = $this->db->query("SELECT * FROM `teacher` WHERE `name` LIKE '{$text[1]}%' ORDER BY `name` LIMIT {$limit} OFFSET {$offset}");
                while($row = $query->fetch_row()) {
                    $buttons[] = [new InlineKeyboardButton($row[4], "/gttchrdt+" . $row[0])];
                }
                //pagination
                if ($offset + $limit < $count) {//Добавляем кнопку следующей страницы
                    if ($offset == 0) {
                        $buttons[] = [new InlineKeyboardButton("« Назад", "Покажи расписание преподавателя"), new InlineKeyboardButton("\xE2\x8F\xA9", "/teacher+" . $text[1] . "+" . ($offset + $limit))];
                    } else {//Добавляем кнопку предыдущей страницы
                        $buttons[] = [new InlineKeyboardButton("\xE2\x8F\xAA", "/teacher+" . $text[1] . "+" . ($offset - $limit)), new InlineKeyboardButton("« Назад", "Покажи расписание преподавателя"), new InlineKeyboardButton("\xE2\x8F\xA9", "/teacher+" . $text[1] . "+" . ($offset + $limit))];
                    }
                } elseif ($offset != 0) {//Добавляем кнопку предыдущей страницы
                    $buttons[] = [new InlineKeyboardButton("\xE2\x8F\xAA", "/teacher+" . $text[1] . "+" . ($offset - $limit)), new InlineKeyboardButton("« Назад", "Покажи расписание преподавателя")];
                } else {
                    $buttons[] = [new InlineKeyboardButton("« Назад", "Покажи расписание преподавателя")];
                }
                $buttons = new InlineKeyboard($buttons);
                $text = "Выбери преподавателя";
                $this->editMessageText($this->data->callback_query->message->message_id, $text, '', $buttons);
                break;
            case "/gttchrdt":
                $buttons = [];
                $offset = $text[2] ?? 0;
                $limit = 5;

                $teacher = $this->db->query("SELECT DISTINCT LEFT(`name`, 1), `name`, `institute`, `faculty`, `department` FROM `teacher` WHERE `id`={$text[1]}")->fetch_row();
                $count = $this->db->query("SELECT COUNT(*) FROM (SELECT DISTINCT `date` FROM `teachers_schedule` WHERE `teacher_id`={$text[1]} AND `date`>='" . date("Y-m-d") . "' ORDER BY `date`) AS `table`")->fetch_row()[0];
                $query = $this->db->query("SELECT DISTINCT `date` FROM `teachers_schedule` WHERE `teacher_id`={$text[1]} AND `date`>='" . date("Y-m-d") . "' ORDER BY `date` LIMIT {$limit} OFFSET {$offset}");
                while($row = $query->fetch_row()) {
                    $buttons[] = [new InlineKeyboardButton($row[0], "/gts+{$text[1]}+{$row[0]}")];
                }
                //pagination
                if ($offset + $limit < $count) {//Добавляем кнопку следующей страницы
                    if ($offset == 0) {
                        $buttons[] = [new InlineKeyboardButton("« Назад", "/teacher+{$teacher[0]}"), new InlineKeyboardButton("\xE2\x8F\xA9", "/gttchrdt+{$text[1]}+" . ($offset + $limit))];
                    } else {//Добавляем кнопку предыдущей страницы
                        $buttons[] = [new InlineKeyboardButton("\xE2\x8F\xAA", "/gttchrdt+{$text[1]}+" . ($offset - $limit)), new InlineKeyboardButton("« Назад", "/teacher+{$teacher[0]}"), new InlineKeyboardButton("\xE2\x8F\xA9", "/gttchrdt+" . $text[1] . "+" . ($offset + $limit))];
                    }
                } elseif ($offset != 0) {//Добавляем кнопку предыдущей страницы
                    $buttons[] = [new InlineKeyboardButton("\xE2\x8F\xAA", "/gttchrdt+{$text[1]}+" . ($offset - $limit)), new InlineKeyboardButton("« Назад", "/teacher+{$teacher[0]}")];
                } else {
                    $buttons[] = [new InlineKeyboardButton("« Назад", "/teacher+{$teacher[0]}")];
                }
                $buttons = new InlineKeyboard($buttons);
                $text = "{$teacher[2]} > {$teacher[3]} > {$teacher[4]} > {$teacher[1]}\nНа какой день тебя интересует расписание?";
                $this->editMessageText($this->data->callback_query->message->message_id, $text, '', $buttons);
                break;
//            case "/delay_test":
//                sleep(10);
//                $this->sendMessage("delay test was successfully finished", false, "", false);
//                break;
            case "/gts":
                $this->sendTeachersSchedule($text[1], $text[2]);
                break;
            case "Спасибо":
            case "спасибо":
                $this->sendMessage("Пожалуйста\xF0\x9F\x98\x8A", false, '', false);
                break;
            case "/history":
                if ($this->info[3] == "Admin") {
                    $limit = (count($text) > 1) ? $text[1] : 10;
                    $offset = (count($text) > 2) ? $text[2] : 0;
                    $query = $this->db->query("SELECT `datetime`, `object`, `action` FROM `history` ORDER BY `datetime` DESC, `id` DESC LIMIT $limit OFFSET $offset");
                    $total_rows_count = $this->db->query("SELECT COUNT(*) FROM `history`")->fetch_row()[0];
                    $text_message = ["<code>" . date("Y-m-d H:i:s") . "</code> (Total rows: $total_rows_count):"];
                    $counter = $offset + 1;
                    while($row = $query->fetch_row()) {
                        $text_message[] = $counter++ . ") " . $row[0] . "\n<code>" . $row[1] . "</code>\t<i><b>" . $row[2] . "</b></i>";
                    }
                    $text_message = implode("\n", $text_message);
                    $buttons = [];
                    if ($offset > 0) {
                        $buttons[] = new InlineKeyboardButton("\xE2\x8F\xAA", "/history+" . $limit . "+" . ($offset - $limit));
                    }
                    $buttons[] = new InlineKeyboardButton("\xF0\x9F\x94\x84", implode("+", $text));
                    if ($offset + $limit < $total_rows_count) {
                        $buttons[] = new InlineKeyboardButton("\xE2\x8F\xA9", "/history+" . $limit . "+" . ($offset + $limit));
                    }
                    $buttons = new InlineKeyboard([$buttons]);
                    if ($this->data->callback_query->message->message_id) {
                        $this->editMessageText($this->data->callback_query->message->message_id, $text_message, 'HTML', $buttons);
                    } else {
                        $this->sendMessage($text_message, false, 'HTML', $buttons);
                    }
                } else {
                    $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                }
                break;
//            case "/update_document":
//                if ($this->info[3] == "Admin") {
//                    foreach(glob("images/" . str_replace(":", "_colon_", $this->getBotToken()) . "/keyboard/*") as $file) {
////                        $type = "document";
//                        $type = "photo";
//                        $data = $this->sendDocumentFromFile($file, $file, $type);
//                        $this->sendMessage($data, false, '', false);
//                        $data = json_decode($data);
//                        rename($file, str_replace(".", "+" . end($data->result->$type)->file_id . ".", $file));
//                    }
//                } else {
//                    $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
//                }
//                break;
            default:
                switch ($this->info[4]) {//Последнее сообщение
                    case "/sendAll":
                        if ($this->info[3] == "Admin") {
                            $query = $this->db->query("SELECT * FROM `user` WHERE `block`!=1");
//                            $query = $this->db->query("SELECT * FROM `user` WHERE `block`!=1 AND `id`={$this->info[0]}");
//                            $query = $this->db->query("SELECT DISTINCT `daily_notifications`, `telegram_user_id` FROM `user_group` LEFT JOIN `user` ON `user`.`id`=`user_group`.`user_id` WHERE `daily_notifications`!=0");//Только те пользователи, у которых имеются уведомления о расписании
                            $users = [];
                            while($row = $query->fetch_row()) {
                                $users[] = $row;
                            }
                            if (!empty($this->data->message->document)) {
                                foreach($users as $row) {
                                    $user = new $this($row[1], "", null, false);
                                    $user->sendDocumentByFileId($this->data->message->document->file_id, $this->data->message->caption);
                                }
                            } elseif (!empty($this->data->message->location)) {
                                foreach($users as $row) {
                                    $user = new $this($row[1], "", null, false);
                                    $user->sendLocation($this->data->message->location->latitude, $this->data->message->location->longitude);
                                }
                            } elseif (!empty($this->data->message->photo)) {
                                foreach($users as $row) {
                                    $user = new $this($row[1], "", null, false);
                                    $user->sendDocumentByFileId(end($this->data->message->photo)->file_id, $this->data->message->caption, 'photo');
                                }
                            } elseif (!empty($this->data->message->sticker)) {
                                foreach($users as $row) {
                                    $user = new $this($row[1], "", null, false);
                                    $user->sendDocumentByFileId($this->data->message->sticker->file_id, 'sticker');
                                }
                            } elseif (!empty($this->data->message->audio)) {
                                foreach($users as $row) {
                                    $user = new $this($row[1], "", null, false);
                                    $user->sendDocumentByFileId($this->data->message->audio->file_id, $this->data->message->caption, 'audio');
                                }
                            } elseif (!empty($this->data->message->voice)) {
                                foreach($users as $row) {
                                    $user = new $this($row[1], "", null, false);
                                    $user->sendDocumentByFileId($this->data->message->voice->file_id, '', 'voice');
                                }
                            } elseif (!empty($this->data->message->video)) {
                                foreach($users as $row) {
                                    $user = new $this($row[1], "", null, false);
                                    $user->sendDocumentByFileId($this->data->message->video->file_id, $this->data->message->caption, 'video');
                                }
                            } else {
                                foreach($users as $row) {
                                    $user = new $this($row[1], "", null, false);
                                    $user->sendMessage($this->data->message->text);
                                }
                            }
                        } else {
                            $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                        }
                        break;
                    default:
                        if ((preg_match("/^Где находится корпус номер \\d\\d?$/", $text[0])) and (count($text) == 1)) {
                            $build_number = (int)str_replace("Где находится корпус номер ", "", $text[0]);
                            if (($build_number > 0) and ($build_number < 24)) {
                                $this->getMessage("/get_building+" . $build_number);
                            } else {
                                $this->sendMessage("Кажется, я не знаю корпуса с таким номером\xF0\x9F\x98\x92");
                            }
                        } elseif (!is_null($this->data)) {
                            if (!empty($this->data->message->voice)) {
                                $this->sendMessage($this->phrases["voice"][rand(0, count($this->phrases["voice"]) - 1)]);
                            } else {
                                $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                            }
                        } else {
                            $this->sendMessage($this->phrases["default"][rand(0, count($this->phrases["default"]) - 1)], false, 'HTML');
                        }
                }
        }
        $this->Update();
    }

    private function createGoogleCalendar($group_name) {
        if ($this->info[12] != "") {
            $this->answerCallbackQuery($this->data->callback_query->id, "Создаю календарь для группы '{$group_name}'");
            if ($access_token = $this->getGoogleAccessToken()) {
                $result = $this->request("https://www.googleapis.com/calendar/v3/calendars", "POST", "", json_encode(["summary" => $group_name, "timeZone" => "Europe/Moscow"]), ["Accept-Encoding:", "Host: www.googleapis.com", "content-type: application/x-www-form-urlencoded", "Accept:", "Authorization: Bearer " . $access_token], "google-api-php-client/2.0.0-alpha");
                if ($result == false) return false;

                $result = json_decode($result);
                if (property_exists($result, "id")) {
                    return $result->id;
                }
            }
        }

        return false;
    }

    private function deleteGoogleCalendar($group_id, $group_name) {
        if ($this->info[12] != "") {
            $calendar_id = $this->db->query("SELECT `google_calendar_id` FROM `user_group` WHERE `group_id`=$group_id AND `user_id`=" . $this->info[0])->fetch_row()[0];
            if (($calendar_id != "") and ($access_token = $this->getGoogleAccessToken())) {
                $this->answerCallbackQuery($this->data->callback_query->id, "Удаляю календарь для группы '{$group_name}'");
                $http_code = $this->request("https://www.googleapis.com/calendar/v3/calendars/" . urlencode($calendar_id), "DELETE", "HTTP_CODE", "", ["Accept-Encoding:", "Host: www.googleapis.com", "content-type: application/x-www-form-urlencoded", "Accept:", "Authorization: Bearer " . $access_token], "google-api-php-client/2.0.0-alpha");
                if ($http_code == 204) {
                    return true;
                }
            }
        }

        return false;
    }

    public function sendMessage($text, $disable_notification = false, $parse_mode = '', $reply_markup = "", $disable_web_page_preview = true, $reply_to_message_id = 0) {
        if ($reply_markup === "") {
            if (empty($this->info[2])) {
                $reply_markup = new ReplyKeyboardMarkup([[new KeyboardButton("Запомни в какой группе я обучаюсь")], [new KeyboardButton("Где находится корпус номер.."), new KeyboardButton("Покажи расписание преподавателя")]], true);
            } else {
                switch ($this->info[14]) {
                    case 3:
                        $reply_markup = new ReplyKeyboardMarkup([[new KeyboardButton("Покажи расписание на сегодня"), new KeyboardButton("Отправь мне файл с расписанием")], [new KeyboardButton("Покажи мои группы"), new KeyboardButton("Покажи расписание преподавателя")], [new KeyboardButton("Где находится корпус номер.."), new KeyboardButton("Покажи настройки")]], $this->info[15] == 1);
                        break;
                    case 2:
                        $reply_markup = new ReplyKeyboardMarkup([[new KeyboardButton("Покажи расписание на сегодня")], [new KeyboardButton("Отправь мне файл с расписанием")], [new KeyboardButton("Покажи расписание преподавателя")], [new KeyboardButton("Покажи мои группы")], [new KeyboardButton("Где находится корпус номер..")], [new KeyboardButton("Покажи настройки")]], $this->info[15] == 1);
                        break;
                    case 1:
                    default:
                        $reply_markup = new ReplyKeyboardMarkup([[new KeyboardButton("Покажи расписание на сегодня")], [new KeyboardButton("Покажи мои группы"), new KeyboardButton("Покажи расписание преподавателя")], [new KeyboardButton("Отправь мне файл с расписанием")], [new KeyboardButton("Где находится корпус номер.."), new KeyboardButton("Покажи настройки")]], $this->info[15] == 1);
                }
            }
        }

        $query = ["chat_id" => $this->tgUserId, "text" => $text, "disable_notification" => (($disable_notification) ? 'true' : 'false'), "disable_web_page_preview" => (($disable_web_page_preview) ? "true" : "false")];
        if ($reply_markup != '') $query["reply_markup"] = json_encode($reply_markup);
        if ($parse_mode != '') $query["parse_mode"] = $parse_mode;
        if ($reply_to_message_id != 0) $query["reply_to_message_id"] = $reply_to_message_id;

        return $this->request($this->getTgUrl("sendMessage", $query));
    }

    public function editMessageMediaById($message_id, $id, $type = "document", $caption = "", $parse_mode = "", $reply_markup = "") {
        $inputMedia = new InputMedia($id, $type);
        if ($caption) $inputMedia->caption = $caption;
        if ($parse_mode) $inputMedia->parse_mode = $parse_mode;

        $query = ["chat_id" => $this->tgUserId, "message_id" => $message_id];
        if (!empty($inputMedia)) $query["media"] = json_encode($inputMedia);
        if ($reply_markup != "") $query["reply_markup"] = json_encode($reply_markup);

        return $this->request($this->getTgUrl("editMessageMedia", $query));
    }

    public function editMessageCaption($message_id, $text, $parse_mode = "", $reply_markup = "") {
        $query = ["chat_id" => $this->tgUserId, "message_id" => $message_id, "caption" => $text];
        if ($parse_mode != "") $query["parse_mode"] = $parse_mode;
        if ($reply_markup != "") $query["reply_markup"] = json_encode($reply_markup);

        return $this->request($this->getTgUrl("editMessageCaption", $query));
    }

    public function editMessageText($message_id, $text, $parse_mode = '', $reply_markup = "", $disable_web_page_preview = true) {
        $query = ["chat_id" => $this->tgUserId, "message_id" => $message_id, "text" => $text, "disable_web_page_preview" => (($disable_web_page_preview) ? "true" : "false")];
        if ($parse_mode != "") $query["parse_mode"] = $parse_mode;
        if ($reply_markup != "") $query["reply_markup"] = json_encode($reply_markup);

        return $this->request($this->getTgUrl("editMessageText", $query));
    }

    public function sendLocation($latitude, $longitude, $disable_notification = false, $reply_markup = "", $reply_to_message_id = 0) {
        $query = ["chat_id" => $this->tgUserId, "latitude" => $latitude, "longitude" => $longitude, "disable_notification" => (($disable_notification) ? 'true' : 'false')];
        if ($reply_markup != "") $query["reply_markup"] = json_encode($reply_markup);
        if ($reply_to_message_id != 0) $query["reply_to_message_id"] = $reply_to_message_id;

        return $this->request($this->getTgUrl("sendLocation", $query));
    }

    public function answerCallbackQuery($callback_query_id, $text, $show_alert = false) {
        $query = ["callback_query_id" => $callback_query_id, "text" => $text, "show_alert" => $show_alert];

        return $this->request($this->getTgUrl("answerCallbackQuery", $query));
    }

    public function sendDocumentByFileId($file_id, $caption = "", $type = "document", $disable_notification = false, $parse_mode = '', $reply_markup = "", $reply_to_message_id = 0) {
        $query = ["chat_id" => $this->tgUserId, "caption" => $caption, strtolower($type) => $file_id, "disable_notification" => (($disable_notification) ? 'true' : 'false')];
        if ($parse_mode != "") $query["parse_mode"] = $parse_mode;
        if ($reply_markup != "") $query["reply_markup"] = json_encode($reply_markup);
        if ($reply_to_message_id != 0) $query["reply_to_message_id"] = $reply_to_message_id;

        return $this->request($this->getTgUrl("send" . ucfirst(strtolower($type)), $query));

    }

    public function sendDocumentFromFile($document, $caption = "", $type = "document", $disable_notification = false, $parse_mode = '', $reply_markup = "", $reply_to_message_id = 0) {
        $query = ["chat_id" => $this->tgUserId, "caption" => $caption, "disable_notification" => (($disable_notification) ? 'true' : 'false')];
        if ($parse_mode != "") $query["parse_mode"] = $parse_mode;
        if ($reply_markup != "") $query["reply_markup"] = json_encode($reply_markup);
        if ($reply_to_message_id != 0) $query["reply_to_message_id"] = $reply_to_message_id;

        return $this->request($this->getTgUrl("send" . ucfirst(strtolower($type)), $query), "POST", "", [$type => curl_file_create($document, mime_content_type($document), basename($document))], ['Content-Type: multipart/form-data']);
    }

    private function DownloadFile($url) {
        if (empty($url)) return false;
        $filename = "schedules/" . basename($url);
        if (file_exists($filename)) {
            return $filename;
        }

        $data = $this->request($url);

        if (file_put_contents($filename, $data)) {
            return $filename;
        } else {
            return false;
        }
    }

    public function sendDocument($file, $caption = "", $type = "document", $disable_notification = false, $parse_mode = '', $reply_markup = "", $reply_to_message_id = 0) {
        if (strtolower(substr($file, 0, 4)) == "http") {
            if (($filename = $this->DownloadFile($file)) !== false) {
                $result = $this->sendDocumentFromFile($filename, $caption, $type, $disable_notification, $parse_mode, $reply_markup, $reply_to_message_id);
                unlink($filename);//Удаляем скачаный файл

                return $result;
            } else {
                return "Не удалось скачать файл";
            }
        } else {
            return $this->sendDocumentFromFile($file, $caption, $type, $disable_notification, $parse_mode, $reply_markup, $reply_to_message_id);
        }
    }

    public function Update() {
        if (!empty($this->updates)) {
            $update = ["`last_activity`=\"" . date("Y-m-d H:i:s") . "\""];
            foreach($this->updates as $field => $value) {
                $update[] = "`" . $field . "`=" . $value;
            }
            $this->db->query("UPDATE `user` SET " . implode(", ", $update) . " WHERE `telegram_user_id`=" . $this->tgUserId);
        }
        if ((!empty($this->groupUpdates)) and (!empty($this->groupInfo))) {
            $update = [];
            $send_remind_to_users = false;
            foreach($this->groupUpdates as $field => $value) {
                $update[] = "`" . $field . "`=" . $value;
                if ($this->groupInfo[6] != $this->schedule) {
                    if (($field == "next_schedule") and ($value != "\"\"") and (!$send_remind_to_users)) {
                        $send_remind_to_users = true;
                        $update[] = "`updated`='" . date("Y-m-d H:i:s") . "'";
                    }
                    if (($field == "schedule") and ($value != "\"\"") and (!$send_remind_to_users)) {
                        $send_remind_to_users = true;
                        $update[] = "`updated`='" . date("Y-m-d H:i:s") . "'";
                    }
                }
            }
            $this->db->query("UPDATE `group` SET " . implode(", ", $update) . " WHERE `id`=" . $this->groupInfo[0]);
            if ($send_remind_to_users) {
                send_remind_to_users($this->groupInfo[0], $this->info[0]);
            }
        }
    }

    public function getSchedule() {
        $html = file_get_html($this->timetables, false, null, 0, 100000000);

        if ($html === false) return false;

        foreach($html->find("div.fak_name") as $div) {
            foreach($html->find("div#fak_id_" . $div->attr["data-fak_id"] . " div.grpPeriod") as $sub_div) {
                $name = trim($sub_div->innertext);
                if ($name == $this->info[2]) {
                    $id = substr($sub_div->attr["data-grp_period_id"], 0, -1);
                    $a = [];
                    if (count($html->find("div#listPeriod_{$id}2")) > 0) {
                        $a = $html->find("div#listPeriod_{$id}2")[0]->find("a");
                    }
                    if (empty($a)) {
                        $a = $html->find("div#listPeriod_{$id}1")[0]->find("a");
                    }
                    if (count($a) > 0) {
                        if (count($a) > 1) {//Если есть предпоследнее расписание, то проверяем какое из них актуальное
                            for($current = 0; $current < 2; $current++) {//Проверяем 2 последних расписания
                                if ((checking_the_schedule_is_up_to_date($a[$current]->attr["href"], 1, ">=")) and (checking_the_schedule_is_up_to_date($a[$current]->attr["href"], 0, "<="))) {//текущее расписание
                                    $schedule = "https://www.vyatsu.ru" . $a[$current]->attr["href"];
                                    if ($this->groupInfo[5] != $schedule) {
                                        $this->schedule = $schedule;
                                        $this->groupUpdates["schedule"] = "\"" . $schedule . "\"";
                                        $this->next_schedule = "";
                                        $this->groupUpdates["next_schedule"] = "\"\"";
                                    }
                                    $next = ($current == 1) ? 0 : 1;//проверяем в любом случае, т к "могучий" ВятГУ иногда меняет расписания местами
                                    if (checking_the_schedule_is_up_to_date($a[$next]->attr["href"], 0, ">")) {//если начало расписания больше чем сейчас
                                        $next_schedule = "https://www.vyatsu.ru" . $a[$next]->attr["href"];
                                        $this->next_schedule = $next_schedule;
                                        $this->groupUpdates["next_schedule"] = "\"" . $next_schedule . "\"";
                                    }
                                }
                            }
                        } else {
                            $schedule = "https://www.vyatsu.ru" . $a[0]->attr["href"];
                            if ((checking_the_schedule_is_up_to_date($schedule, 1, ">=")) and ($this->groupInfo[5] != $schedule)) {
                                $this->schedule = $schedule;
                                $this->groupUpdates["schedule"] = "\"" . $schedule . "\"";
                            }
                        }
                    }

                    return true;
                }
            }
        }


        return false;
    }

    public function sendSchedule($next = false) {
        if ($next) $this->schedule = $this->next_schedule;
        $has_several_groups = $this->db->query("SELECT COUNT(*) FROM `user_group` WHERE `user_id`={$this->info[0]} LIMIT 1")->fetch_row()[0] > 1;
        if (($has_several_groups) and (!$this->info[13])) {
            $text = explode("+", $this->text);
            if (count($text) == 1) {
                $text_message = "Выбери группу файл с расписанием которой хочешь увидеть:";
                $query = $this->db->query("SELECT `group`.`id`, `group`.`name` FROM `user_group` LEFT JOIN `group` on `user_group`.`group_id` = `group`.`id` WHERE `user_id`=" . $this->info[0]);
                $buttons = [];
                while($group = $query->fetch_row()) {
                    $buttons[] = [new InlineKeyboardButton($group[1], "Омфср+" . $group[0] . "++")];
                }
                $buttons = new InlineKeyboard($buttons);
                $this->sendMessage($text_message, false, "", $buttons);
                return;
            } elseif (($this->data->callback_query->message->message_id) and (count(explode("+", $this->text)) == 4)) {
                $this->groupInfo = $this->db->query("SELECT * FROM `group` WHERE `id` = " . (!empty($text[1]) ? $text[1] : $text[2]))->fetch_row();
                $this->info[2] = $this->groupInfo[4];
                $this->group_id = $this->groupInfo[0];
                if (checking_the_schedule_is_up_to_date($this->groupInfo[5], 1, ">=")) {//Если последняя дата расписания >= чем сейчас, то занесем ее в расписание-----------------------------------------
                    $this->schedule = $this->groupInfo[5];
                    if (!empty($this->groupInfo[6])) {
                        $this->next_schedule = $this->groupInfo[6];
                    }
                } elseif (!empty($this->groupInfo[6])) {
                    if (checking_the_schedule_is_up_to_date($this->groupInfo[6], 1, "<=")) {
                        $this->schedule = $this->groupInfo[6];
                        $this->groupUpdates["schedule"] = "\"" . $this->groupInfo[6] . "\"";
                        $this->groupUpdates["next_schedule"] = "\"\"";
                    }
                } else {
                    $this->schedule = "";
                    $this->next_schedule = "";
                }
            }
        }
        $add_text = (mb_substr($this->text, 0, 20) == "Покажи расписание на") ? "Мне пока что не удалось разобрать расписание по этому: \n" : "";
        if (!empty($this->schedule)) {
            if ($this->data->callback_query->message->message_id) {
                $this->editMessageText($this->data->callback_query->message->message_id, "Сейчас отправлю файл для группы <u>" . $this->info[2] . "</u>", "HTML");
            }
            if ($next) {
                $this->sendDocument($this->schedule, $add_text . $this->phrases["send_schedule"][rand(0, count($this->phrases["send_schedule"]) - 1)] . ($has_several_groups ? "\n[<u>" . $this->info[2] . "</u>]" : ""), "document", false, "HTML", "", $this->data->callback_query->message->message_id ?? 0);
            } else {
                if (empty($this->next_schedule)) {
                    $this->sentMessages[] = json_decode($this->sendDocument($this->schedule, $add_text . $this->phrases["check_next_schedule"][rand(0, count($this->phrases["check_next_schedule"]) - 1)] . ($has_several_groups ? "\n[<u>" . $this->info[2] . "</u>]" : ""), "document", false, "HTML", "", $this->data->callback_query->message->message_id ?? 0));
                    $this->getSchedule();
                    if (!empty($this->next_schedule)) {
                        $button = new InlineKeyboard([[new InlineKeyboardButton("Получить", "/get_full_next_schedule+" . $this->info[2])]]);
                        $this->sendMessage($this->phrases["next_schedule"][rand(0, count($this->phrases["next_schedule"]) - 1)] . ($has_several_groups ? "\n[<u>" . $this->info[2] . "</u>]" : ""), false, "HTML", $button, true, $this->sentMessages[count($this->sentMessages) - 1]->result->message_id);
                    } else {
                        $this->sendMessage($this->phrases["empty_next_schedule"][rand(0, count($this->phrases["empty_next_schedule"]) - 1)] . ($has_several_groups ? "\n[<u>" . $this->info[2] . "</u>]" : ""), false, "HTML", false, true, $this->sentMessages[count($this->sentMessages) - 1]->result->message_id);
                    }
                } else {
                    $this->sendDocument($this->schedule, $add_text . $this->phrases["send_schedule"][rand(0, count($this->phrases["send_schedule"]) - 1)] . ($has_several_groups ? "\n[<u>" . $this->info[2] . "</u>]" : ""), "document", false, "HTML", new InlineKeyboard([[new InlineKeyboardButton("Следующее расписание", "/get_full_next_schedule+" . $this->info[2])]]), $this->data->callback_query->message->message_id ?? 0);
                }
            }
        } else {
            if ($this->data->callback_query->message->message_id) {
                $this->editMessageText($this->data->callback_query->message->message_id, $this->phrases["parse_page"][rand(0, count($this->phrases["parse_page"]) - 1)] . "\n[<u>" . $this->info[2] . "</u>]", "HTML");
                $reply_message_id = $this->data->callback_query->message->message_id;
            } else {
                $this->sentMessages[] = json_decode($this->sendMessage($this->phrases["parse_page"][rand(0, count($this->phrases["parse_page"]) - 1)] . ($has_several_groups ? "\n[<u>" . $this->info[2] . "</u>]" : ""), false, "HTML"));
                $reply_message_id = $this->sentMessages[count($this->sentMessages) - 1]->result->message_id;
            }
            $this->getSchedule();
            if ($next) {
                if (!empty($this->next_schedule)) {
                    $this->sendDocument($this->next_schedule, $add_text . $this->phrases["send_schedule"][rand(0, count($this->phrases["send_schedule"]) - 1)] . ($has_several_groups ? "\n[<u>" . $this->info[2] . "</u>]" : ""), "document", false, "HTML", "", $reply_message_id);
                } else {
                    $this->sendMessage($this->phrases["empty_next_schedule"][rand(0, count($this->phrases["empty_next_schedule"]) - 1)] . ($has_several_groups ? "\n[<u>" . $this->info[2] . "</u>]" : ""), false, "HTML", false, true, $reply_message_id);
                }
            } else {
                if (!empty($this->schedule)) {
                    $this->sendDocument($this->schedule, $add_text . $this->phrases["send_schedule"][rand(0, count($this->phrases["send_schedule"]) - 1)] . ($has_several_groups ? "\n[<u>" . $this->info[2] . "</u>]" : ""), "document", false, "HTML", (((!$next) and (!empty($this->next_schedule))) ? new InlineKeyboard([[new InlineKeyboardButton("Следующее расписание", "/get_full_next_schedule+" . $this->info[2])]]) : ""), $reply_message_id);
                } else {
                    $this->sendMessage($this->phrases["empty_schedule"][rand(0, count($this->phrases["empty_schedule"]) - 1)] . ($has_several_groups ? "\n[<u>" . $this->info[2] . "</u>]" : ""), false, "HTML", false, true, $reply_message_id);
                }
            }
        }
    }

    public function sendTodaySchedule() {
        if (!empty($this->info[2])) {
            $has_several_groups = $this->db->query("SELECT COUNT(*) FROM `user_group` WHERE `user_id`={$this->info[0]} LIMIT 1")->fetch_row()[0] > 1;
            if (($has_several_groups) and (!$this->info[13]) and (strpos($this->text, "+") === false)) {
                $text_message = "Выбери группу расписание которой хочешь увидеть" . mb_substr($this->text, 17) . ":";
                $query = $this->db->query("SELECT `group`.`id`, `group`.`name` FROM `user_group` LEFT JOIN `group` on `user_group`.`group_id` = `group`.`id` WHERE `user_id`=" . $this->info[0]);
                $buttons = [];
                while($group = $query->fetch_row()) {
                    $buttons[] = [new InlineKeyboardButton($group[1], $this->text . (($this->text === "Покажи расписание на сегодня") ? ("++" . $group[0] . "+") : ("+" . $group[0] . "++")))];
                }
                $buttons = new InlineKeyboard($buttons);
                $this->sendMessage($text_message, false, "", $buttons);
                return;
            }
            if (empty($this->date)) {
                $this->date = date("Y-m-d");
            }
            if (empty($this->db->query("SELECT `group_id` FROM `schedule` WHERE `group_id`=" . $this->group_id . " AND `date`='" . $this->date . "' LIMIT 1")->fetch_row())) {
                $this->date = $this->db->query("SELECT `date` FROM `schedule` WHERE `group_id`=" . $this->group_id . " AND `date`>'" . date("Y-m-d") . "' ORDER BY `date` LIMIT 1")->fetch_row();
                if (!empty($this->date)) {
                    $this->date = $this->date[0];
                } else {
                    $this->date = date("Y-m-d");
                }
            }
            $day_of_week = [1 => "Понедельник", 2 => "Вторник", 3 => "Среда", 4 => "Четверг", 5 => "Пятница", 6 => "Суббота", 7 => "Воскресенье",];
            $startTime = [1 => "08:20", 2 => "10:00", 3 => "11:45", 4 => "14:00", 5 => "15:45", 6 => "17:20", 7 => "18:55",];
            $endTime = [1 => "09:50", 2 => "11:30", 3 => "13:15", 4 => "15:30", 5 => "17:15", 6 => "18:50", 7 => "20:25",];
            $figures = [1 => "\x31\xE2\x83\xA3", 2 => "\x32\xE2\x83\xA3", 3 => "\x33\xE2\x83\xA3", 4 => "\x34\xE2\x83\xA3", 5 => "\x35\xE2\x83\xA3", 6 => "\x36\xE2\x83\xA3", 7 => "\x37\xE2\x83\xA3",];
            $textMessage = "";
            if ((stripos($this->text, "Покажи расписание на сегодня") !== false) and ($this->date != date("Y-m-d"))) {
                $textMessage = $this->phrases["not_today"][rand(0, count($this->phrases["not_today"]) - 1)] . "\n";
            }
            $textMessage .= "<b>" . $day_of_week[date("N", strtotime($this->date))] . "(" . (int)date("d", strtotime($this->date)) . "." . (int)date("m", strtotime($this->date)) . ")</b>" . ($has_several_groups ? " [<u>" . $group_id = $this->db->query("SELECT `name` FROM `group` WHERE `id`='" . $this->group_id . "'")->fetch_row()[0] . "</u>]:\n" : "<b>:</b>\n");

            $query = $this->db->query("SELECT * FROM `schedule` WHERE `group_id`=" . $this->group_id . " AND `date`='" . $this->date . "' ORDER BY `time_start`, `sort`");
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
                if ($this->schedule == "") {
                    if ((!$this->info[13]) and ($this->data->callback_query->message->message_id) and (count(explode("+", $this->text)) == 4)) {
                        $this->editMessageText($this->data->callback_query->message->message_id, "<u>" . $this->info[2] . "</u>:\n" . $this->phrases["empty_schedule"][rand(0, count($this->phrases["empty_schedule"]) - 1)], 'HTML', false);
                    } else {
                        $this->sendMessage(($has_several_groups ? "<u>" . $this->info[2] . "</u>:\n" : "") . $this->phrases["empty_schedule"][rand(0, count($this->phrases["empty_schedule"]) - 1)], false, 'HTML');
                    }
                } else {
                    $this->getMessage("Отправь мне файл с расписанием");
                }
                return;
            }
            foreach($lessons as $lesson_number => $lesson) {
                $textMessage .= $figures[$lesson_number] . "<b>" . $startTime[$lesson_number] . " &lt; </b>" . implode(" / ", $lesson) . "<b> &lt; " . $endTime[$lesson_number] . "</b>\n";
            }
            $textMessage .= $this->phrases["number_of_classes"][count($lessons)][rand(0, count($this->phrases["number_of_classes"][count($lessons)]) - 1)];
            if ($this->db->query("SELECT `sort` FROM `schedule` WHERE `group_id`=" . $this->group_id . " AND `date`='" . date("Y-m-d", strtotime("+1 day", strtotime($this->date))) . "' LIMIT 1")->fetch_row()) {
                if ($this->date == date("Y-m-d")) {
                    $buttons = new InlineKeyboard([[new InlineKeyboardButton("На завтра", "Прнз+" . $this->group_id)]]);
                } else {
                    $buttons = new InlineKeyboard([[new InlineKeyboardButton("На " . date("d.m", strtotime("+1 day", strtotime($this->date))), "Прнс+" . date("Y-m-d", strtotime("+1 day", strtotime($this->date))) . "+" . $this->group_id)]]);
                }
            } else {
                $buttons = "";
            }

            if ((!$this->info[13]) and ($this->data->callback_query->message->message_id) and (count(explode("+", $this->text)) == 4)) {
                $this->editMessageText($this->data->callback_query->message->message_id, $textMessage, 'HTML', $buttons);
            } else {
                $this->sendMessage($textMessage, false, 'HTML', $buttons);
            }
        } else {
            $this->sendMessage($this->phrases["empty_group"][rand(0, count($this->phrases["empty_group"]) - 1)]);
        }
    }

    public function sendTeachersSchedule($teacher_id, $date) {
        $day_of_week = [1 => "Понедельник", 2 => "Вторник", 3 => "Среда", 4 => "Четверг", 5 => "Пятница", 6 => "Суббота", 7 => "Воскресенье",];
        $figures = ["08:20:00" => "\x31\xE2\x83\xA3", "10:00:00" => "\x32\xE2\x83\xA3", "11:45:00" => "\x33\xE2\x83\xA3", "14:00:00" => "\x34\xE2\x83\xA3", "15:45:00" => "\x35\xE2\x83\xA3", "17:20:00" => "\x36\xE2\x83\xA3", "18:55:00" => "\x37\xE2\x83\xA3"];
        $audience_pattern = "/(\s+\d+\s*-\s*[^\s]+\s+)|(^\d+\s*-\s*[^\s]+\s+)/";
        $teacher = $this->db->query("SELECT `name` FROM `teacher` WHERE `id`={$teacher_id}")->fetch_row()[0];

        $query = $this->db->query("SELECT * FROM `teachers_schedule` WHERE `teacher_id` = {$teacher_id} AND `date` = '{$date}' ORDER BY `time_start`");
        $lessons = ["<b>" . $day_of_week[date("N", strtotime($date))] . "(" . (int)date("d", strtotime($date)) . "." . (int)date("m", strtotime($date)) . ")</b> [<u>$teacher</u>]:"];
        while($row = $query->fetch_row()) {
            $row[4] = str_replace(["_", "*", "~", "`"], "", $row[4]);
            preg_match_all($audience_pattern, $row[4], $audience_matches);
            foreach($audience_matches[0] as $audience_match) {
                $row[4] = str_replace($audience_match, " <u>" . preg_replace("/\s+/", "", $audience_match) . "</u> ", $row[4]);
            }
            $row[4] = trim($row[4]);
            if (empty($row[5])) {
                $lessons[] = $figures[$row[2]] . "<b>" . substr($row[2], 0, 5) . " &lt; </b>" . $row[4] . "<b> &lt; " . substr($row[3], 0, 5) . "</b>";
            } else {
                $links = explode("\n", $row[5]);
                if (count($links) == 1) {
                    $lessons[] = $figures[$row[2]] . "<b>" . substr($row[2], 0, 5) . " &lt; </b><a href='{$links[0]}'>" . $row[4] . "</a><b> &lt; " . substr($row[3], 0, 5) . "</b>";
                } else {
                    $lessons[] = $figures[$row[2]] . "<b>" . substr($row[2], 0, 5) . " &lt; </b>" . $row[4] . "\n" . implode(" ", array_map(function($link) { return "<a href='{$link}'>[Teams]</a>"; }, $links)) . "<b> &lt; " . substr($row[3], 0, 5) . "</b>";
                }
            }
        }

        $buttons = [];
        $empty_schedule = count($lessons) == 1;
        if ($empty_schedule) {
            $lessons[] = "Кажется, на этот день расписания нет";
        }
        //pagination
        if (!$empty_schedule) {
            $query = $this->db->query("SELECT DISTINCT `date` FROM `teachers_schedule` WHERE `teacher_id`={$teacher_id} AND `date`>='" . date("Y-m-d") . "' AND `date`<'{$date}' ORDER BY `date` DESC LIMIT 1")->fetch_row();
            if ($query) {
                $buttons[] = new InlineKeyboardButton("На " . date("d.m", strtotime($query[0])), "/gts+{$teacher_id}+{$query[0]}");
            }
        }
        $buttons[] = new InlineKeyboardButton("« Назад", "/gttchrdt+{$teacher_id}");
        if (!$empty_schedule) {
            $query = $this->db->query("SELECT DISTINCT `date` FROM `teachers_schedule` WHERE `teacher_id`={$teacher_id} AND `date`>'{$date}' ORDER BY `date` LIMIT 1")->fetch_row();
            if ($query) {
                $buttons[] = new InlineKeyboardButton("На " . date("d.m", strtotime($query[0])), "/gts+{$teacher_id}+{$query[0]}");
            }
        }
        $buttons = new InlineKeyboard([$buttons]);

        $text = implode("\n", $lessons);
        $this->editMessageText($this->data->callback_query->message->message_id, $text, 'HTML', $buttons);
    }

    public function sendBuildingLocation($buildingNumber) {
        $address = ["1" => "Ул. Московская, д. 36", "2" => "Ул. Московская, д. 39", "3" => "Ул. Московская, д. 29", "4" => "Ул. К.Либкнехта, 76", "5" => "Ул. Карла Маркса, д. 77", "6" => "Студенческий проезд, д. 9", "7" => "Ул. Преображенская, д. 32", "8" => "Студенческий проезд, д. 11", "9" => "Студенческий проезд, д.11а", "10" => "Ул. Ломоносова, д. 18-а", "11" => "Ул. Преображенская, 41", "12" => "Ул. Ленина, д.127", "13" => "Ул. Красноармейская, д.26", "14" => "Ул. Ленина, д.111", "15" => "Ул. Ленина, д.198", "16" => "Ул. Свободы, д.122", "17" => "Ул. Ленина, д.111а", "18" => "Ул. Молодой Гвардии, д.13", "19" => "Ул.Орловская, д.12", "20" => "Ул.Ленина, д.113", "21" => "Ул.Ленина, д.111а", "22" => "Ул.Ленина, д.111а", "23" => "Ул. Володарского, д. 2"];
        $coordinates = ["1" => "58.602656, 49.666561", "2" => "58.604412, 49.665843", "3" => "58.604315, 49.673868", "4" => "58.602069, 49.665461", "5" => "58.599858, 49.667886", "6" => "58.605197, 49.618111", "7" => "58.605176, 49.673631", "8" => "58.604542, 49.618161", "9" => "58.604426, 49.617223", "10" => "58.604575, 49.615900", "11" => "58.605719, 49.670754", "12" => "58.586846, 49.681661", "13" => "58.591209, 49.676563", "14" => "58.591584, 49.680733", "15" => "58.567355, 49.686324", "16" => "58.591255, 49.678211", "17" => "58.590916, 49.681074", "18" => "58.598946, 49.687072", "19" => "58.596088, 49.687693", "20" => "58.590663, 49.681460", "21" => "58.590916, 49.681074", "22" => "58.590916, 49.681074", "23" => "58.616253, 49.671574"];
        $links = ["1" => "https://www.vyatsu.ru/internet-gazeta/uchebnyiy-korpus-1.html", "2" => "https://www.vyatsu.ru/internet-gazeta/uchebnyiy-korpus-2.html", "3" => "https://www.vyatsu.ru/internet-gazeta/uchebnyiy-korpus-3.html", "4" => "https://www.vyatsu.ru/internet-gazeta/uchebnyiy-korpus-4.html", "5" => "https://www.vyatsu.ru/internet-gazeta/uchebnyiy-korpus-5.html", "6" => "https://www.vyatsu.ru/internet-gazeta/uchebnyiy-korpus-6.html", "7" => "https://www.vyatsu.ru/internet-gazeta/uchebnyiy-korpus-7.html", "8" => "https://www.vyatsu.ru/internet-gazeta/uchebnyiy-korpus-8.html", "9" => "https://www.vyatsu.ru/studentu-1/pervokursniku/adresa-i-telefonyi-uchebnyih-korpusov-fakul-tetov.html", "10" => "https://www.vyatsu.ru/studentu-1/pervokursniku/adresa-i-telefonyi-uchebnyih-korpusov-fakul-tetov.html", "11" => "https://www.vyatsu.ru/studentu-1/pervokursniku/adresa-i-telefonyi-uchebnyih-korpusov-fakul-tetov.html", "12" => "https://www.vyatsu.ru/studentu-1/pervokursniku/adresa-i-telefonyi-uchebnyih-korpusov-fakul-tetov.html", "13" => "https://www.vyatsu.ru/studentu-1/pervokursniku/adresa-i-telefonyi-uchebnyih-korpusov-fakul-tetov.html", "14" => "https://www.vyatsu.ru/studentu-1/pervokursniku/adresa-i-telefonyi-uchebnyih-korpusov-fakul-tetov.html", "15" => "https://www.vyatsu.ru/studentu-1/pervokursniku/adresa-i-telefonyi-uchebnyih-korpusov-fakul-tetov.html", "16" => "https://www.vyatsu.ru/studentu-1/pervokursniku/adresa-i-telefonyi-uchebnyih-korpusov-fakul-tetov.html", "17" => "https://www.vyatsu.ru/studentu-1/pervokursniku/adresa-i-telefonyi-uchebnyih-korpusov-fakul-tetov.html", "18" => "https://www.vyatsu.ru/studentu-1/pervokursniku/adresa-i-telefonyi-uchebnyih-korpusov-fakul-tetov.html", "19" => "https://www.vyatsu.ru/studentu-1/pervokursniku/adresa-i-telefonyi-uchebnyih-korpusov-fakul-tetov.html", "20" => "https://www.vyatsu.ru/studentu-1/pervokursniku/adresa-i-telefonyi-uchebnyih-korpusov-fakul-tetov.html", "21" => "https://www.vyatsu.ru/studentu-1/pervokursniku/adresa-i-telefonyi-uchebnyih-korpusov-fakul-tetov.html", "22" => "https://www.vyatsu.ru/studentu-1/pervokursniku/adresa-i-telefonyi-uchebnyih-korpusov-fakul-tetov.html", "23" => "https://www.vyatsu.ru/studentu-1/pervokursniku/adresa-i-telefonyi-uchebnyih-korpusov-fakul-tetov.html"];
        $coordinates = explode(", ", $coordinates[$buildingNumber]);
        $this->sendLocation($coordinates[0], $coordinates[1], false, new InlineKeyboard([[new InlineKeyboardButton($buildingNumber . ") " . $address[$buildingNumber], "", $links[$buildingNumber])]]));
    }

    public function getTgBaseUrl() {
        return "https://api.telegram.org/bot" . $this->getBotToken();
    }

    public function getTgUrl($method, $query) {
        return $this->getTgBaseUrl() . "/$method?" . http_build_query($query);
    }

    public function request($url, $method = "GET", $result_type = "", $postfields = "", $headers = [], $useragent = "") {
        if ($ch = curl_init($url)) {
            if (count($headers) > 0) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            if ($method == "POST") {
                curl_setopt($ch, CURLOPT_POST, true);
                if ($postfields !== "") {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
                }
            } elseif ($method != "GET") {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                if ($postfields !== "") {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
                }
            }
            if ($useragent !== "") {
                curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
            }

            $result = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($result_type == "HTTP_CODE") return $http_code;

            if (empty($result)) return false;

            return $result;
        }

        return false;
    }
}