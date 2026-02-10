<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>

    <link rel="icon" type="image/png" href="@yield('favicon', '/img/favicon.png')" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/8.2.0/mdb.min.css"
        integrity="sha512-7Gq9D0o4oucsdul8TfQEy1UtovxpFGnbR4je6T/pS6o31wM2HRDwZYScOQ9oVO5JFLI04EVB3WZMi1LG2dUNjg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/8.2.0/mdb.rtl.min.css"
        integrity="sha512-jFcTaYv6syez/6iLZWcOroioatQWhChEmJnxfrkNDXtpO3X/BPJ98h4DUSni42wACMEOoxwjltq2+mtQtJYNIw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<style>
    .c-white {
        color: #f1dada;
    }

    .custom-container {
        background-color: #5c0b18;
        border-radius: 25px;
        color: #f1dada;
    }

    .xl-columns {
        display: flex;
        flex-wrap: wrap;
        column-gap: 20px;
        /* Отступ между колонками */
        row-gap: 10px;
        /* Отступ между строками */
    }

    .xl-columns .checkboxes__item {
        flex: 1 1 calc(25% - 15px);
        /* Четыре колонки с учетом отступов */
    }

    /* Кнопка "Все" остается на всю ширину */
    .xl-columns .d-flex.justify-content-center {
        flex: 1 1 100%;
        /* Кнопка занимает всю ширину */
    }

    .pointer {
        cursor: pointer;
    }

    input[type=file] {
        background-color: #e5788a;
        border: 2px solid #d56376;
        border-radius: 3px;
    }

    input[type=file]::file-selector-button {
        border: none;
        border-right: 3px solid #d56376;
        background-color: #e98092;
        color: #f7dada;
    }

    input[type=file]::file-selector-button:hover {
        background-color: #651220;
        color: #f79595;
    }

    .checkbox.style-d {
        display: inline-block;
        position: relative;
        padding-left: 30px;
        cursor: pointer;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
    }

    .checkbox.style-d input {
        position: absolute;
        opacity: 0;
        cursor: pointer;
        height: 0;
        width: 0;
    }

    .checkbox.style-d input:checked~.checkbox__checkmark {
        background-color: #E5788A;
    }

    .checkbox.style-d input:checked~.checkbox__checkmark:after {
        opacity: 1;
    }

    .checkbox.style-d:hover input~.checkbox__checkmark {
        background-color: #eee;
    }

    .checkbox.style-d:hover input:checked~.checkbox__checkmark {
        background-color: #E5788A;
    }

    .checkbox.style-d:hover input~.checkbox__body {
        color: #E5788A;
    }

    .checkbox.style-d .checkbox__checkmark {
        position: absolute;
        top: 1px;
        left: 0;
        height: 22px;
        width: 22px;
        background-color: #eee;
        transition: background-color 0.25s ease;
        border-radius: 11px;
    }

    .checkbox.style-d .checkbox__checkmark:after {
        content: "";
        position: absolute;
        left: 9px;
        top: 5px;
        width: 5px;
        height: 10px;
        border: solid #f1dada;
        border-width: 0 2px 2px 0;
        transform: rotate(45deg);
        opacity: 0;
        transition: opacity 0.25s ease;
    }

    .checkbox.style-d .checkbox__body {
        color: #f1dada;
        line-height: 1.4;
        font-size: 16px;
        transition: color 0.25s ease;
    }

    :root {
        --background: hsl(0, 0%, 7%);
        --glow-color: hsla(336, 100%, 72%, 0.2);
        --grid-line-color: hsla(0, 0%, 40%, 0.08);
    }

    body {
        margin: 0;
        padding: 0;
        background-color: var(--background);
        min-height: 100vh;
        position: relative;
    }

    /* Свечение по краям */
    .edge-glow {
        position: absolute;
        z-index: 0;
        pointer-events: none;
        background-color: var(--glow-color);
        filter: blur(80px);
    }

    /* Левая и правая — вертикальные */
    .edge-glow.left,
    .edge-glow.right {
        top: 0;
        height: 100%;
        width: 12.5%;
    }

    /* Левая */
    .edge-glow.left {
        left: 0;
    }

    /* Правая */
    .edge-glow.right {
        right: 0;
    }

    /* Верх и низ — горизонтальные */
    .edge-glow.top,
    .edge-glow.bottom {
        left: 0;
        width: 100%;
        height: 2.5%;
    }

    /* Верх */
    .edge-glow.top {
        top: 0;
    }

    /* Низ */
    .edge-glow.bottom {
        bottom: 0;
    }

    /* Сетка */
    .grid-lines {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image:
            linear-gradient(to right, var(--grid-line-color) 1px, transparent 1px),
            linear-gradient(to bottom, var(--grid-line-color) 1px, transparent 1px);
        background-size: 40px 40px;
        z-index: 1;
        pointer-events: none;
    }
</style>

<body>
    <div class="edge-glow left"></div>
    <div class="edge-glow right"></div>
    <div class="edge-glow top"></div>
    <div class="edge-glow bottom"></div>

    <div class="grid-lines"></div>
    <div class="container mt-4">
        @yield('content')
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/8.2.0/mdb.es.min.js"
        integrity="sha512-XKY01lk+oJMq7BeZg8m4OluyN6MMRjtEGfTyrLehOEIHXOOC2upfLz5FoRd91m+KarEc34f+3qgNacBwCxUl8A=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/8.2.0/mdb.umd.min.js"
        integrity="sha512-XaBF6KP9xEbPjS0vTWwV3ETXS4EBvYPIkvEPX7B4QcStZEj6JEesGUEHMhbZMH3aaoSmCzXFoZxWBK/GTa2tBw=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</body>

</html>
