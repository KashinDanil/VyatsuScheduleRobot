<?php
//VyatsuScheduleROBOT
include "models/VyatsuScheduleROBOT.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = file_get_contents("php://input");
//    file_put_contents("logs/VyatsuScheduleROBOT.txt", $data . "\n", FILE_APPEND | LOCK_EX);
    $data = json_decode($data);

    if (isset($data->callback_query)) {
        $user = new VyatsuScheduleROBOT($data->callback_query->from->id, $data->callback_query->data, $data);
        $user->getMessage();
    } else {
        $user = new VyatsuScheduleROBOT($data->message->from->id, $data->message->text, $data);
        $user->getMessage();
    }
}