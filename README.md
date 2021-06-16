# VyatsuScheduleRobot
Этот проект собран из 2-х разных серверов.

Для работы нужно создать директорию private и разместить в ней слеующие файлы:
<ul>
<li><strong>VyatsuScheduleROBOT.token</strong> - в этом файле разместить токен бота</li>
<li><strong>db.connection</strong> - в этом файле разместить данные для подключения к базе данных MySQL в следующем формате:
host<br>
username<br>
password<br>
dbname</li>
<li><strong>google.client_id</strong> - в этом файле разместить client_id приложения Google</li>
<li><strong>google.client_secret</strong> - в этом файле разместить client_secret приложения Google</li>
</ul>
<p>
За тем заменить названия файлов, содержащих "bot token with replaced colon by _colon_", соответственно на токен бота с 
заменным двоеточием на "_colon_" (Это было сделано из-за локалки, размещенной на Windows, где в названии файлов нельзя 
указывать ":", если Вы используете Linux, то этот шаг не обязательный)
</p>
<p>
Создать базу данных со следующей структурой:
<img alt="Структура базы данных" src="https://sun9-26.userapi.com/impg/72yn6yJzSbFyB6ExAJYJrQBWazUT3Dcc53H3kg/flPmVZKQIzs.jpg?size=650x826&quality=96&sign=2348b284022418b0cbffef97688528cc&type=album" />
</p>
<p>
Ну и для уведомлений установить слудющие задачи в cron:
0 7 * * * send-schedule.php?reminder_option=1<br>
15 8 * * * send-schedule.php?reminder_option=2&time_finish=08:20:00<br>
55 9 * * * send-schedule.php?reminder_option=2&time_finish=09:50:00<br>
35 11 * * * send-schedule.php?reminder_option=2&time_finish=11:30:00<br>
20 13 * * * send-schedule.php?reminder_option=2&time_finish=13:15:00<br>
35 15 * * * send-schedule.php?reminder_option=2&time_finish=15:30:00<br>
25 17 * * * send-schedule.php?reminder_option=2&time_finish=17:20:00<br>
55 18 * * * send-schedule.php?reminder_option=2&time_finish=18:50:00<br>
30 20 * * * send-schedule.php?reminder_option=2&time_finish=20:25:00<br>
0 21 * * * send-schedule.php?reminder_option=3<br>
30 */2 * * * fill_groups.php<br>
30 3,7,11,15,19,23 * * * php fill_teachers.php<br>
0 4 * * * php clearing_schedules.php<br>
Уведомления и их запуск располагались на разных серверах, по этому запускались через парс. На своём сервере можно
реализовать это через $argv
</p>
<p>
Остается заменить все прямые пути на актуальные.
</p>
<p>
Проект написан весьма так себе, по этому, если вы собираетесь его поднять, то я Вас сочувствую.
</p>
<p>
По поводу вопросов можете обратиться <a href="https://t.me/Danil_Kashin">t.me/Danil_Kashin</a>
</p>










