<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Loyalty Terminal</title>
    @stack('loyalty-head')
</head>
<body>
<main
    data-loyalty-terminal
    data-loyalty-stamp-url="{{ route('loyalty.terminal.stamp') }}"
    data-loyalty-redeem-url="{{ route('loyalty.terminal.redeem') }}"
>
    <div data-loyalty-scanner></div>

    <form data-loyalty-terminal-form>
        <input type="text" name="card_token" data-loyalty-card-input placeholder="Card code">
        <button type="submit" data-loyalty-stamp-btn>Add stamp</button>
        <button type="button" data-loyalty-redeem-btn>Redeem reward</button>
    </form>

    <div data-loyalty-terminal-result hidden></div>
</main>
@stack('loyalty-scripts')
</body>
</html>
