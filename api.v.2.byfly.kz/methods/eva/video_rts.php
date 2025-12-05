<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#000000">
    <title>EVA GPT - разговор по душам</title>
    <style>
        body,
        html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background: #000;
        }

        .iframe-container {
            width: 100%;
            height: 100vh;
            height: -webkit-fill-available;
            /* Для iOS */
        }

        iframe {
            width: 100%;
            height: 100vh;
            border: none;
        }
    </style>
</head>

<body>
    <div class="iframe-container">
        <iframe
            src="https://studio.d-id.com/agents/share?id=agt_Vm4h-Ki7&utm_source=copy&key=WVhWMGFEQjhOamd3TUdRNVlqY3pOek0xWlRnMlpHVTRPV1poTVRKak9qZEtaM0pOWm1wTmNVSlFWVFpLUVhOTk1IWnRhdz09&mobile=true"
            allow="microphone; camera; fullscreen" frameborder="0" style="width:100%; height:100vh; border:none;">
        </iframe>
    </div>

    <script>
        // Запуск в полноэкранном режиме при загрузке
        document.addEventListener('DOMContentLoaded', () => {
            if (window.navigator.standalone) {
                // Уже в PWA-режиме (iOS)
                console.log("Running in fullscreen PWA mode");
            } else if (document.documentElement.requestFullscreen) {
                // Для Android/Chrome (Fullscreen API)
                document.documentElement.requestFullscreen().catch(err => {
                    console.error("Fullscreen error:", err);
                });
            }
        });

        // Фикс для iOS (чтобы скрыть UI браузера при скролле)
        window.addEventListener('load', () => {
            setTimeout(() => {
                window.scrollTo(0, 1);
            }, 100);
        });
    </script>
</body>

</html>