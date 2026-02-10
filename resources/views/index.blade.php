<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>XL-MEBEL-API</title>
    <link rel="icon" type="image/png" href='/img/favicon.png' />
</head>
<style>
    @import url('https://fonts.googleapis.com/css?family=Manrope:700|Manrope:400');

    :root {
        --text: hsl(0, 0%, 99%);
        --textDim: hsl(0, 0%, 60%);
        --background: hsl(0, 0%, 7%);
        --primary: hsl(336, 100%, 65%);
        --primaryBg: hsla(342, 100%, 65%, 0.01);
        --primaryHi: hsla(325, 100%, 75%, 0.25);
        --primaryFg: hsl(319, 100%, 85%);
        --secondary: hsl(345, 52%, 14%);
        --secondaryFg: hsl(327, 51%, 75%);
        --secondaryBg: hsla(336, 52%, 14%, 0.05);
        --secondaryHi: hsla(327, 52%, 30%, 0.5);
        --accent: hsl(341, 100%, 94%);
        --accentBg: hsla(346, 100%, 94%, 0.01);
        --accentHi: hsla(155, 100%, 100%, 25%);
    }

    body {
        font-family: 'Manrope';
        font-weight: 400;
        background-color: var(--background);
        color: var(--text);
        padding: 0 10%;
        display: flex;
        flex-direction: column;
        align-items: center;
        height: 100vh;
        overflow: hidden;
    }

    nav {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 2rem;
        color: var(--textDim);
        width: 100%;
        box-sizing: border-box;
        z-index: 9999;
    }

    .menu:hover {
        color: var(--text);
        cursor: pointer;
    }

    .sitename {
        font-weight: bold;
    }

    .grid {
        position: absolute;
        height: 100%;
        weight: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        align-self: center;
        z-index: -1;
    }

    .grid-svg {
        height: 80%;
        width: 80%;
        position: relative;
        z-index: 1;
    }

    .blur {
        height: 12rem;
        width: 12rem;
        background-color: var(--primary);
        filter: blur(100px);
        border-radius: 100px;
        z-index: 0;
        position: absolute;
    }

    .title {
        position: absolute;
        left: 35%;
        width: 30%;
        font-size: clamp(3rem, 8vw, 6rem);
        font-weight: 700;
        letter-spacing: -0.2rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-self: center;
        height: 100%;
        z-index: 1000;
        text-align: center;
    }

    .title>p {
        margin: 0;
        line-height: 5rem;
        width: 100%;
    }

    .title>p:nth-child(1) {
        align-self: flex-start;
    }

    .title>p:nth-child(2) {
        color: var(--primary);
        align-self: flex-end;
    }

    .title>p:nth-child(3) {
        align-self: flex-end;
    }

    .material-icons {
        display: none;
        fill: var(--text);
    }

    .button {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        position: absolute;
        text-decoration: none;
        color: inherit;
        cursor: pointer;
        height: 50px;
        width: 160px;
        z-index: 9999;
    }

    button {
        height: 50px;
        width: 160px;
        clip-path: path("M 0 25 C 0 -5, -5 0, 80 0 S 160 -5, 160 25, 165 50 80 50, 0 55, 0 25");
        border: none;
        border-radius: 13px;
        background-color: var(--primaryBg);
        box-shadow: 0px -3px 15px 0px var(--primaryHi) inset;
        color: var(--primaryFg);
        font-family: "Manrope";
        font-size: 1rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        position: absolute;
        transform: translateY(0px);
        transition: all 0.2s ease;
    }

    span {
        width: 100px;
        height: 60px;
        background-color: var(--primaryHi);
        border-radius: 100%;
        filter: blur(20px);
        position: absolute;
        bottom: -50%;
        transition: all 0.2s ease;
    }

    .button:hover>span {
        opacity: 60%;
    }

    .button:hover>button {
        transform: translateY(5px);
    }

    .button.first {
        top: 12%;
        right: 20%;
    }

    .button.sec {
        bottom: 13%;
        right: 11%;
    }

    .button.sec>button {
        background-color: var(--accentBg);
        box-shadow: 0px -3px 15px 0px var(--accentHi) inset;
        color: var(--accentFg);
    }

    .button.sec>span {
        background-color: var(--accentHi);
    }

    .button.third {
        bottom: 25%;
        left: 15%;
    }

    .button.third>button {
        background-color: var(--secondaryBg);
        box-shadow: 0px -3px 15px 0px var(--secondary) inset;
        color: var(--secondaryFg);
    }

    .button.third>span {
        background-color: var(--secondaryHi);
    }


    .top-right {
        position: absolute;
        top: 0;
        right: 0;
        z-index: -1;
        opacity: 50%;
    }

    .bottom-left {
        position: absolute;
        bottom: 0;
        left: 0;
        z-index: -1;
        opacity: 50%;
    }
    @media screen and (max-width: 1000px) {
        .title {
            font-size: 4rem;
            line-height:
        }

        .title>p {
            line-height: 5rem;
            letter-spacing: -0.3rem;
        }

        nav> :not(.sitename, .material-icons) {
            display: none;
        }

        nav {
            justify-content: space-between;
        }

        .material-icons {
            display: flex;
            align-items: center;
        }

    }

    ::-moz-selection { /* Code for Firefox */
        color: #f7f2f5;
        background: #b76788;
    }

    ::selection {
        color: #f7f2f5;
        background: #b76788;
    }
</style>

<body>
    <div class="grid">
        <svg class="grid-svg" xmlns="http://www.w3.org/2000/svg" width="982" height="786" viewBox="0 0 982 786"
            fill="none">
            <path fill-rule="evenodd" clip-rule="evenodd"
                d="M490 401V537H348.5V401H490ZM490 785.5V676H348.5V785.5H347.5V676H206V785.5H205V676H63.5V785.5H62.5V676H0V675H62.5V538H0V537H62.5V401H0V400H62.5V258H0V257H62.5V116H0V115H62.5V0H63.5V115L205 115V0H206V115L347.5 115V0H348.5V115H490V0H491V115L627.5 115V0H628.5V115H765V0H766V115L902.5 115V0H903.5V115H982V116H903.5V257H982V258H903.5V400H982V401H903.5V537H982V538H903.5V675H982V676H903.5V785.5H902.5V676H766V785.5H765V676H628.5V785.5H627.5V676H491V785.5H490ZM902.5 675V538H766V675H902.5ZM902.5 537V401H766V537H902.5ZM902.5 400V258H766V400H902.5ZM902.5 257V116L766 116V257H902.5ZM627.5 675H491V538H627.5V675ZM765 675H628.5V538H765V675ZM348.5 675H490V538H348.5V675ZM347.5 538V675H206V538H347.5ZM205 538V675H63.5V538H205ZM765 537V401H628.5V537H765ZM765 400V258H628.5V400H765ZM765 257V116H628.5V257H765ZM347.5 401V537H206V401H347.5ZM205 401V537H63.5V401H205ZM627.5 401V537H491V401H627.5ZM627.5 116L491 116V257H627.5V116ZM627.5 258H491V400H627.5V258ZM63.5 257V116L205 116V257H63.5ZM63.5 400V258H205V400H63.5ZM206 116V257H347.5V116L206 116ZM348.5 116V257H490V116H348.5ZM206 400V258H347.5V400H206ZM348.5 258V400H490V258H348.5Z"
                fill="url(#paint0_radial_1_8)" />
            <defs>
                <radialGradient id="paint0_radial_1_8" cx="0" cy="0" r="1"
                    gradientUnits="userSpaceOnUse"
                    gradientTransform="translate(491 392.75) rotate(90) scale(513.25 679.989)">
                    <stop stop-color="white" stop-opacity="0.2" />
                    <stop offset="1" stop-color="#000" stop-opacity="0" />
                </radialGradient>
            </defs>
        </svg>
        <div class="blur"></div>
    </div>

    <div class="title">
        <p>XL-JV</p>
        <p>Mebel</p>
        <p>Tool</p>
    </div>


    @foreach ($links as $link)
        <a href="{{ $link['href'] }}" class="button first">
            <button>{{ $link['label'] }}</button><span></span>
        </a>
    @endforeach

    <script>
    function getRandomPosition(zone, buttonWidthPx = 160, buttonHeightPx = 50) {
        const screenWidth = window.innerWidth;
        const screenHeight = window.innerHeight;

        const buttonWidthPercent = (buttonWidthPx / screenWidth) * 100;
        const buttonHeightPercent = (buttonHeightPx / screenHeight) * 100;

        let horizontalZone = zone === 'left'
            ? [0, 35 - buttonWidthPercent]
            : [65, 100 - buttonWidthPercent];

        const verticalRange = [10, 90 - buttonHeightPercent];

        const top = Math.random() * (verticalRange[1] - verticalRange[0]) + verticalRange[0];
        const left = Math.random() * (horizontalZone[1] - horizontalZone[0]) + horizontalZone[0];

        return {
            top,
            left,
            width: buttonWidthPercent,
            height: buttonHeightPercent
        };
    }

    function isOverlapping(a, b, buffer = 2) {
        return !(
            a.top + a.height + buffer < b.top ||
            a.top > b.top + b.height + buffer ||
            a.left + a.width + buffer < b.left ||
            a.left > b.left + b.width + buffer
        );
    }

    document.addEventListener("DOMContentLoaded", () => {
        const buttons = document.querySelectorAll(".button");
        const placedButtons = [];

        let leftCount = 0;
        let rightCount = 0;
        const half = Math.ceil(buttons.length / 2);

        buttons.forEach(button => {
            const maxTries = 100;
            let placed = false;
            let tries = 0;
            let zone;

            while (!placed && tries < maxTries) {
                // Выбираем зону: рандомно, но с учётом заполненности
                const preferRight = Math.random() > 0.5;
                if ((preferRight && rightCount >= half) || (!preferRight && leftCount < half)) {
                    zone = 'left';
                } else {
                    zone = 'right';
                }

                const position = getRandomPosition(zone);

                const overlaps = placedButtons.some(existing => isOverlapping(existing, position));

                if (!overlaps) {
                    button.style.top = `${position.top}%`;
                    button.style.left = `${position.left}%`;
                    button.style.right = 'auto';
                    button.style.bottom = 'auto';

                    placedButtons.push(position);
                    zone === 'left' ? leftCount++ : rightCount++;
                    placed = true;
                }

                tries++;
            }

            if (!placed) {
                console.warn("Could not place button without overlap and within bounds.");
            }
        });
    });
</script>


</body>

</html>
