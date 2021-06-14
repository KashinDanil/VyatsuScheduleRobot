<?php
include "models/VyatsuScheduleROBOT.php";
//include "models/DoubleFeatheredBot.php";


if (isset($_GET["message"])) {
    $user = new VyatsuScheduleROBOT(789977687, '', null, false);
    $user->sendMessage($_GET["message"], true, 'HTML', false);
}