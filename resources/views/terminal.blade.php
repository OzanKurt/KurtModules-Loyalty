<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('loyalty::messages.terminal.title') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php($assets = config('loyalty.assets.base'))
    <link rel="stylesheet" href="{{ asset($assets.'/loyalty.css') }}">
    <link rel="stylesheet" href="{{ asset($assets.'/themes/minimal.css') }}">
    @stack('loyalty-head')
</head>
<body>
<main
    data-loyalty-terminal
    data-loyalty-stamp-url="{{ route('loyalty.terminal.stamp') }}"
    data-loyalty-redeem-url="{{ route('loyalty.terminal.redeem') }}"
    aria-label="{{ __('loyalty::messages.terminal.title') }}"
>
    <h1>{{ __('loyalty::messages.terminal.title') }}</h1>

    <div data-loyalty-scanner></div>

    <form data-loyalty-terminal-form>
        <label for="loyalty-card-code">{{ __('loyalty::messages.terminal.code_placeholder') }}</label>
        <input
            type="text"
            id="loyalty-card-code"
            name="card_token"
            data-loyalty-card-input
            autocomplete="off"
            autocapitalize="characters"
            placeholder="{{ __('loyalty::messages.terminal.code_placeholder') }}"
        >
        <button type="submit" data-loyalty-stamp-btn>{{ __('loyalty::messages.terminal.add_stamp') }}</button>
        <button type="button" data-loyalty-redeem-btn>{{ __('loyalty::messages.terminal.redeem') }}</button>
    </form>

    <div data-loyalty-terminal-result role="status" aria-live="polite" hidden></div>
</main>
<script src="{{ asset(config('loyalty.assets.base').'/loyalty.js') }}"></script>
@stack('loyalty-scripts')
</body>
</html>
