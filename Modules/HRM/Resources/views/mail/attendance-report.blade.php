<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'Attendance Report' }}</title>
    </head>
    <body style="font-family: Arial, Helvetica, sans-serif; color:#222;">
        <h2 style="margin:0 0 10px;">{{ 'Attendance Report' }}</h2>
        @if(!empty($title))
            <p style="margin:0 0 8px; color:#555;">
                Please find attached the <strong>{{ $title }}</strong> for <strong>{{ $range }}</strong> .
            </p>
        @endif
        <p style="margin:0 0 8px; color:#555;">Report Generated at: <strong>{{ $generated_at ?? now() }}</strong></p>
    </body>
</html>

