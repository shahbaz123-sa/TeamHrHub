<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <link rel="icon" href="{{ asset('zlogo.ico') }}" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Zarea - ERP Dashboard</title>
    <link rel="stylesheet" type="text/css" href="{{ asset('loader.css') }}" />
    @vite(['resources/js/main.js'])
</head>

<body>
    <div id="app">
        <div id="loading-bg">
            <div class="loading-logo">
                <img width="86" height="48" src="{{ asset('images/company-favicon-rotating-axes.svg') }}" />
            </div>
{{--            <div class="loading">--}}
{{--                <div class="effect-1 effects"></div>--}}
{{--                <div class="effect-2 effects"></div>--}}
{{--                <div class="effect-3 effects"></div>--}}
{{--            </div>--}}
        </div>
    </div>

    <script>
        const loaderColor = localStorage.getItem('vuexy-initial-loader-bg') || '#FFFFFF'
        const primaryColor = localStorage.getItem('vuexy-initial-loader-color') || '#D55D36'

        if (loaderColor)
            document.documentElement.style.setProperty('--initial-loader-bg', loaderColor)
        if (loaderColor)
            document.documentElement.style.setProperty('--initial-loader-bg', loaderColor)

        if (primaryColor)
            document.documentElement.style.setProperty('--initial-loader-color', primaryColor)
    </script>
</body>

</html>
