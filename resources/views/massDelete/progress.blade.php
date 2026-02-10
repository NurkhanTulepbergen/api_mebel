<!DOCTYPE html>
<html>
<head>
    <title>Mass Delete - Process</title>
    <link rel="icon" type="image/png" href="/img/favicon.png" />
</head>
<style>
    :root {
        --background: hsl(0, 0%, 7%);
        --glow-color: hsla(336, 100%, 72%, 0.2);
        --grid-line-color: hsla(0, 0%, 40%, 0.08);
    }

    body {
        margin: 0;
        padding: 0;
        background-color: var(--background);
        height: 100vh;
        overflow: hidden;
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

    body {
        display: flex;
        justify-content: center;
        align-items: flex-start;
    }

    .console {
        margin-top: 2.5%;
        width: 75%;
        height: 90%;
        background-color: #1b1b1b;
        color: #10e413;
        border-radius: 5px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        z-index: 2;
    }

    .console pre {
        flex: 1;
        padding: 10px;
        white-space: pre-wrap;
        word-break: break-word;
        overflow-y: auto;
        margin: 0;
    }

    .title {
        background-color: #56544c;
        color: white;
        text-align: center;
        padding: 5px 10px;
        border-radius: 5px 5px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: bold;
        font-size: 1rem;
        height: 1rem;
    }

    .title .close-button {
        display: flex;
        aspect-ratio: 1 / 1;
        height: 1.1rem;
        margin-left: auto;
        transition: opacity 0.2s ease;
    }

    .title .close-button:hover {
        opacity: 0.7;
    }

    .hidden {
        display: none !important;
    }
</style>

<body>
    <div class="edge-glow left"></div>
    <div class="edge-glow right"></div>
    <div class="edge-glow top"></div>
    <div class="edge-glow bottom"></div>

    <div class="grid-lines"></div>
    <div class="console">
        <div class="title">
            Mass Delete
            <a href="/">
                <img src="/img/close.png" id="close-button" class="close-button hidden" />
            </a>
        </div>
        <pre id="log-output"></pre>
    </div>

    <script>
        const logOutput = document.getElementById('log-output');
        let lastMessageCount = 0;
        const closeButton = document.getElementById('close-button');
        async function pollStatus() {
            try {
                const res = await fetch('/api/mass-delete');
                const data = await res.json();

                if (data.messages.length !== lastMessageCount) {
                    logOutput.textContent = data.messages.join('\n');
                    logOutput.scrollTop = logOutput.scrollHeight;
                    lastMessageCount = data.messages.length;
                }

                if (data.status === 'done' || data.status === 'failed') {
                    logOutput.textContent += `\n[Proccess ended: ${data.status}]`;
                    logOutput.scrollTop = logOutput.scrollHeight;
                    closeButton.classList.remove('hidden');
                    return;
                }

                setTimeout(pollStatus, 1000);
            } catch (err) {
                logOutput.textContent += "\n[Ошибка подключения к серверу]";
            }
        }
        pollStatus();
    </script>
</body>

</html>
