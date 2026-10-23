<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Salesboard') &middot; Salesboard</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        .kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
        .kpi { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; }
        .kpi .value { font-size: 1.5rem; font-weight: 700; margin-top: .25rem; }
        .bars text { font-size: 11px; fill: #6b7280; }
        .bar { fill: #1d4ed8; }
        .hbar { display: grid; grid-template-columns: 110px 1fr 100px; gap: .5rem; align-items: center; margin: .35rem 0; font-size: .9rem; }
        .hbar .track { background: #eef2ff; border-radius: 4px; height: 14px; }
        .hbar .fill { background: #1d4ed8; height: 14px; border-radius: 4px; }
        .two { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 800px) { .kpis { grid-template-columns: 1fr 1fr; } .two { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <header class="nav">
        <a class="logo" href="{{ url('/') }}">Salesboard</a>
        <nav class="links">
            @auth
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="link-btn" type="submit">Log out</button>
                </form>
            @endauth
        </nav>
    </header>
    <main class="container">
        @yield('content')
    </main>
</body>
</html>
