<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>503 | Service Unavailable</title>
    <link rel="icon" href="{{ asset('zlogo.ico') }}">
    <style>
        :root {
            --primary: #d55d36;
            --text-dark: #1e1e1e;
            --text-muted: #6b7280;
            --bg: #f7f7f8;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg);
            color: var(--text-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        main {
            width: 100%;
            max-width: 640px;
            background: #ffffff;
            border-radius: 16px;
            padding: 3rem 2.5rem;
            text-align: center;
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.08);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.35rem 1.25rem;
            border-radius: 999px;
            font-size: 0.85rem;
            letter-spacing: 0.075em;
            text-transform: uppercase;
            background: rgba(213, 93, 54, 0.12);
            color: var(--primary);
            font-weight: 600;
        }

        h1 {
            margin: 1.5rem 0 0.75rem;
            font-size: clamp(2.25rem, 5vw, 3.25rem);
        }

        p {
            margin: 0 auto 1.75rem;
            max-width: 520px;
            color: var(--text-muted);
            line-height: 1.6;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: center;
        }

        a,
        button {
            border: none;
            cursor: pointer;
            border-radius: 999px;
            padding: 0.9rem 1.75rem;
            font-size: 1rem;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .primary {
            background: var(--primary);
            color: #ffffff;
            box-shadow: 0 12px 24px rgba(213, 93, 54, 0.35);
        }

        .ghost {
            background: transparent;
            color: var(--primary);
            border: 1px solid rgba(213, 93, 54, 0.4);
        }

        a:hover,
        button:hover {
            transform: translateY(-2px);
        }

        footer {
            margin-top: 2.5rem;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        @media (max-width: 480px) {
            main {
                padding: 2.5rem 1.5rem;
            }
        }
    </style>
</head>
<body>
<main>
    <span class="badge">503 Service Unavailable</span>
    <h1>
        <img width="50" height="50" src="{{ asset('images/company-favicon-rotating-axes.svg') }}" />
        We are scaling up
    </h1>
    <p>
        {{ config('app.name', 'Application') }} is temporarily unavailable while we finish some important maintenance.
        Thank you for your patience—please try again in a moment.
    </p>
    <div class="actions">
        <button type="button" class="primary" onclick="window.location.reload()">Retry Now</button>
    </div>
    <footer>
        &copy; {{ date('Y') }} {{ config('app.name', 'Application') }}. All rights reserved.
    </footer>
</main>
</body>
</html>

