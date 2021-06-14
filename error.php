<head>
    <title>Кажется, ты не туда заплыл</title>
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
            background: #d5e2eb;
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
    </style>
</head>
<body>
<div class="main">
    <img src="/images/site/turtle.jpg" class="background">
    <div class="navbar"><div><a href="https://t.me/VyatsuScheduleROBOT" target="_blank">Бот</a></div><div style="border-right: 1px solid #727272; border-left: 1px solid #727272;"><a href="/">Главная</a></div><div style="border-right: 1px solid #727272;"><a href="/api/documentation.php">API</a></div><div><a href="https://t.me/Danil_Kashin" target="_blank">Автор</a></div></div>
</div>
</body>