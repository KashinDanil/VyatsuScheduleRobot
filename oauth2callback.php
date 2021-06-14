<?php
include "models/VyatsuScheduleROBOT.php";
//include "models/DoubleFeatheredBot.php";

if ($_GET["code"] and $_GET["state"]) {
    if ($ch = curl_init("https://www.googleapis.com/oauth2/v4/token")) {
        $headers = ["Expect:", "Accept-Encoding:", "Host: www.googleapis.com", "cache-control: no-store", "content-type: application/x-www-form-urlencoded", "Accept:",];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=authorization_code&code=".urlencode($_GET["code"])."&redirect_uri=https%3A%2F%2Fvyatsuschedulerobot.site%2Foauth2callback.php&client_id=" . file_get_contents("private/google.client_id") . "&client_secret=" . file_get_contents("private/google.client_secret"));
        curl_setopt($ch, CURLOPT_USERAGENT, "GuzzleHttp/6.2.0 curl/7.70.0 PHP/7.4.10");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $result = curl_exec($ch);
        curl_close($ch);

        if (!empty($result)) {
            $db_connection = explode("\n", file_get_contents("private/db.connection"));
            $db = new mysqli(trim($db_connection[0]), trim($db_connection[1]), trim($db_connection[2]), trim($db_connection[3]));
            $db->query("SET NAMES 'utf8'");
            $db->query("SET CHARACTER SET 'utf8'");
            $db->query("SET SESSION collation_connection = 'utf8_general_ci'");


            $result = json_decode($result);
            $state = explode(" ", $_GET["state"]);
            $old_data = $db->query("SELECT `google_calendar_data` FROM `user` WHERE `telegram_user_id`=".$state[0])->fetch_row();
            if (!empty($old_data) and !empty($old_data[0])) {
                $old_data = json_decode($old_data[0]);
            } else {
                $old_data = json_decode("{}");
            }
            foreach($result as $field => $value) {
                $old_data->$field = $value;
            }

            $group = $db->query("UPDATE `user` SET `google_calendar_data`='".json_encode($old_data)."' where `telegram_user_id`=".$state[0]);

            if (count($state) > 1) {
                $user = new VyatsuScheduleROBOT($state[0], "", null, false);
                $user->getMessage("/calendar+".$state[1]);
            }
        }
    }
}
header('Location: https://t.me/VyatsuScheduleROBOT');