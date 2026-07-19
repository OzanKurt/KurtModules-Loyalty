<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $state['program']['name'] }}</title>
    @php($assets = config('loyalty.assets.base'))
    <link rel="stylesheet" href="{{ asset($assets.'/loyalty.css') }}">
    <link rel="stylesheet" href="{{ asset($assets.'/themes/'.$state['program']['theme'].'.css') }}">
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

    <div data-loyalty-qr data-loyalty-qr-value="{{ $state['code'] }}">{!! $qr !!}</div>

    @php($wallet = $wallet ?? [])
    @if (count($wallet))
        <div data-loyalty-wallet-actions>
            @if (in_array('apple', $wallet, true))
                <a data-loyalty-wallet="apple" href="{{ route('loyalty.card.apple', $state['token']) }}">Add to Apple Wallet</a>
            @endif
            @if (in_array('google', $wallet, true))
                <a data-loyalty-wallet="google" href="{{ route('loyalty.card.google', $state['token']) }}">Add to Google Wallet</a>
            @endif
        </div>
    @endif

    <script type="application/json" data-loyalty-config>@json($state)</script>
</main>
<script src="{{ asset(config('loyalty.assets.base').'/loyalty.js') }}"></script>
@stack('loyalty-scripts')
</body>
</html>
