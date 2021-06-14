<head>
    <title>API Документация</title>
    <style>
        * {
            font-family: Arial;
        }

        div.navbar > div > a:hover {
            color: #000000;
        }

        img.background {
            background-size: 100%;
            position: fixed;
            width: 100%;
            height: 100%;
            object-fit: cover;
            pointer-events: none;
        }

        body {
            margin: 0;
        }

        div.main {
            width: 100%;
            height: 100%;
            position: relative;
        }

        div.main > div.navbar {
            position: absolute;
            left: 50%;
            transform: translate(-50%, 0);
            background: #a5eaff;
            border: 1px solid #727272;
            border-radius: 0 0 10px 10px;
            color: #404040;
            padding: 0 12px;
            white-space: nowrap;
        }

        div.navbar > div {
            display: inline-block;
            padding: 12px 0;
        }

        div.navbar > div > a {
            padding: 12px 18px;
        }

        a {
            color: #404040;
            text-decoration: none;
            outline: none;
        }

        a.try_it {
            position: absolute;
            right: 12px;
            background: #ffd482;
            padding: 6px 12px;
            border: 1px solid #fbc762;
            border-radius: 4px;
        }

        div.content {
            position: relative;
            left: 50%;
            transform: translate(-50%, 0);
            width: 50%;
            background: #ffe5b4;
            top: 200px;
            padding: 20px;
        <?= isset($_GET["penguin"])?" opacity: 0.1; transition: 0.1s linear;":"" ?>
        }

        <?= isset($_GET["penguin"])?"div.content:hover { opacity: 1; }":"" ?>

        div.footer {
            position: relative;
            top: 200px;
            height: 100px;
        }

        ul.methods {
            margin-top: 0px;
        }

        ul.methods li a {
            color: black;
        }

        table {
            border-collapse: collapse;
            background: #fff6e6;
            border: 1px solid #000;
            width: 100%;
        }

        th, td {
            padding: 5px;
            border: 1px solid #000;
        }

        p {
            margin: 8px 0;
        }
    </style>
</head>
<body>
<div class="main">
    <img src="/images/site/penguin.jpg" class="background">
    <div class="navbar">
        <div><a href="https://t.me/VyatsuScheduleROBOT" target="_blank">Бот</a></div>
        <div style="border-right: 1px solid #727272; border-left: 1px solid #727272;"><a href="/">Главная</a></div>
        <div><a href="https://t.me/Danil_Kashin" target="_blank">Автор</a></div>
    </div>
    <div class="content"><p>Текущая версия API: 1.0</p>
        <p>Адрес для отправки запросов: https://vyatsuschedulerobot.site/api/v1<br>Запросы необходимо отправлять методом GET<br>Все методы возвращают массивы объектов
        </p>
        <p>Существуют следующие запросы:</p>
        <ul class="methods">
            <li><a href="#group">Группа</a></li>
            <li><a href="#teacher">Преподаватель</a></li>
            <li><a href="#schedule">Расписание</a></li>
        </ul>
        <div><a name="group"></a>
            <h2>Группа</h2>
            <p>/getGroup<a class="try_it" href="/api/v1/getGroup.php?name=МКб" target="_blank">Попробовать</a></p>
            <p><strong>Принимаемые параметры:</strong></p>
            <table>
                <thead>
                <tr>
                    <th>Параметр</th>
                    <th>Необходимость</th>
                    <th>Описание</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>name</td>
                    <td>Не обязательный</td>
                    <td>Наименование группы, может содержать русские буквы, цифры, а также символ '-'</td>
                </tr>
                </tbody>
            </table>
            <p><strong>Возвращаемые параметры:</strong></p>
            <table>
                <thead>
                <tr>
                    <th>Параметр</th>
                    <th>Описание</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>id</td>
                    <td>Номер группы</td>
                </tr>
                <tr>
                    <td>name</td>
                    <td>Сокращенное наименование группы (полного названия, к сожалению, нет)</td>
                </tr>
                <tr>
                    <td>type</td>
                    <td>Тип подготовки. Одно из значений списка: Бакалавриат, Специалитет, Аспирантура, Магистратура</td>
                </tr>
                <tr>
                    <td>year</td>
                    <td>Год подготовки</td>
                </tr>
                <tr>
                    <td>faculty</td>
                    <td>Наименование факультета</td>
                </tr>
                </tbody>
            </table>
        </div>
        <div><a name="teacher"></a>
            <h2>Преподаватель</h2>
            <p>/getTeacher<a class="try_it" href="/api/v1/getTeacher.php?name=Марков" target="_blank">Попробовать</a>
            </p>
            <p><strong>Принимаемые параметры:</strong></p>
            <table>
                <thead>
                <tr>
                    <th>Параметр</th>
                    <th>Необходимость</th>
                    <th>Описание</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>name</td>
                    <td>Не обязательный</td>
                    <td>Фамилия И.О. преподавателя, может содержать русские буквы, а также символы: ' ', '.'</td>
                </tr>
                </tbody>
            </table>
            <p><strong>Возвращаемые параметры:</strong></p>
            <table>
                <thead>
                <tr>
                    <th>Параметр</th>

                    <th>Описание</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>id</td>
                    <td>Номер преподавателя</td>
                </tr>
                <tr>
                    <td>name</td>
                    <td>Фамилия И.О. преподавателя (полных данных, к сожалению, нет)</td>
                </tr>
                <tr>
                    <td>institute</td>
                    <td>Наименование института</td>
                </tr>
                <tr>
                    <td>faculty</td>
                    <td>Наименование факультета</td>
                </tr>
                <tr>
                    <td>department</td>
                    <td>Наименование кафедры</td>
                </tr>
                </tbody>
            </table>
        </div>
        <div><a name="schedule"></a>
            <h2>Расписание</h2>
            <p>/getSchedule<a class="try_it" href="/api/v1/getSchedule.php?group_id=14390"
                              target="_blank">Попробовать</a></p>
            <p><br>К сожалению <a href="https://www.vyatsu.ru/"
                                  target="_blank">ВятГУ</a> не могут предоставить расписание в хорошем виде, по этому имеем то, что имеем.<br>Расписания для групп и преподавтелей - это абсолютно разные расписания. Да, они должны совпадать, но попробуйте рассказать это
                <a href="https://www.vyatsu.ru/"
                   target="_blank">ВятГУ</a>.<br>Расписание для студентов обновляется каждые 2 часа. Расписание для преподавателей обновляется каждые 3 часа.
            </p>
            <p><strong>Принимаемые параметры:</strong></p>
            <table>
                <thead>
                <tr>
                    <th>Параметр</th>
                    <th>Необходимость</th>
                    <th>Описание</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>group_id</td>
                    <td>*Не обязательный</td>
                    <td>Номер группы, обязателен если не указан параметр "teacher_id"</td>
                </tr>
                <tr>
                    <td>teacher_id</td>
                    <td>*Не обязательный</td>
                    <td>Номер преподавателя, обязателен если не указан параметр "group_id"</td>
                </tr>
                <tr>
                    <td>from</td>
                    <td>Не обязательный</td>
                    <td>Дата, в формате 'Y-m-d' (Пример: 2021-03-16), начиная с которой будет предоставлено расписание (включительно)</td>
                </tr>
                <tr>
                    <td>to</td>
                    <td>Не обязательный</td>
                    <td>Дата, в формате 'Y-m-d' (Пример: 2021-04-21), заканчивая которой будет предоставлено расписание (включительно)</td>
                </tr>
                </tbody>
            </table>
            <p><strong>Возвращаемые параметры:</strong></p>
            <table>
                <thead>
                <tr>
                    <th>Параметр</th>

                    <th>Описание</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>group</td>
                    <td>Группа, объект, аналогичный возвращаемому при запросе <a
                                href="#group">/getGroup</a><br>Отображается только при указании параметра 'group_id'
                    </td>
                </tr>
                <tr>
                    <td>teacher</td>
                    <td>Преподаватель, объект, аналогичный возвращаемому при запросе <a
                                href="#teacher">/getTeacher</a><br>Отображается только при указании параметра 'teacher_id'
                    </td>
                </tr>
                <tr>
                    <td>date</td>
                    <td>Дата в формате 'Y-m-d' (Пример: 2021-02-14)</td>
                </tr>
                <tr>
                    <td>lesson_number</td>
                    <td>Номер занятия по порядку (Начинается с единицы)</td>
                </tr>
                <tr>
                    <td>start</td>
                    <td>Время начала занятия в формате 'H:i:s' (Пример: 08:20:00)</td>
                </tr>
                <tr>
                    <td>end</td>
                    <td>Время окончания занятия в формате 'H:i:s' (Пример: 09:50:00)</td>
                </tr>
                <tr>
                    <td>lesson</td>
                    <td>Описание занятия(здесь же указана лекция/практика и, в случае запроса расписания для преподавателя, будет указана аудитория)</td>
                </tr>
                <tr>
                    <td>links</td>
                    <td>Массив ссылок для бесед в Teams (может быть не указано)</td>
                </tr>
                <tr>
                    <td>audience</td>
                    <td>Аудитория в формате 'Номер корпуса-номер кабинета' (Пример: 2-101. Может быть не указано)</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="footer"></div>
</div>
</body>