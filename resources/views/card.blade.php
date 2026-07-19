<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $state['program']['name'] }}</title>
    @stack('loyalty-head')
</head>
<body>
<main
    data-loyalty-card
    data-loyalty-token="{{ $state['token'] }}"
    data-loyalty-theme="{{ $state['program']['theme'] }}"
    data-loyalty-state-url="{{ route('loyalty.card.state', $state['token']) }}"
>
    <header>
        <h1 data-loyalty-program-name>{{ $state['program']['name'] }}</h1>
        <p data-loyalty-reward>{{ $state['program']['reward'] }}</p>
    </header>

    <p data-loyalty-progress>
        <span data-loyalty-count>{{ $state['stamps_count'] }}</span>
        /
        <span data-loyalty-required>{{ $state['program']['stamps_required'] }}</span>
    </p>

    <ol data-loyalty-stamps>
        @foreach ($state['stamps'] as $stamp)
            <li
                data-loyalty-stamp
                data-index="{{ $stamp['index'] }}"
                data-state="{{ $stamp['state'] }}"
                data-icon="{{ $state['program']['icon'] }}"
            ></li>
        @endforeach
    </ol>

    <div data-loyalty-qr data-loyalty-qr-value="{{ $state['code'] }}"></div>

    <div data-loyalty-wallet-actions hidden>
        <a data-loyalty-wallet="apple" hidden>Apple Wallet</a>
        <a data-loyalty-wallet="google" hidden>Google Wallet</a>
    </div>

    <script type="application/json" data-loyalty-config>@json($state)</script>
</main>
@stack('loyalty-scripts')
</body>
</html>
