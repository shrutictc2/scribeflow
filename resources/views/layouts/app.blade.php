<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ScribeFlow')</title>
    <style>
        :root {
            --navy: #1f3864;
            --ink: #1a1a1a;
            --muted: #5b6472;
            --border: #e2e5ea;
            --bg: #f7f8fa;
            --ok: #1e7e34;
            --warn: #a15c00;
            --danger: #b02a37;
            --info: #2563eb;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: var(--ink);
            background: var(--bg);
        }
        header.site {
            background: var(--navy);
            color: #fff;
            padding: 18px 24px;
        }
        header.site a { color: #fff; text-decoration: none; font-weight: 700; font-size: 1.15rem; }
        header.site p { margin: 2px 0 0; color: #c9d3e6; font-size: .85rem; }
        main { max-width: 840px; margin: 0 auto; padding: 28px 20px 60px; }
        .card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 22px 24px;
            margin-bottom: 20px;
        }
        h1 { font-size: 1.4rem; margin: 0 0 6px; }
        h2 { font-size: 1.1rem; margin: 0 0 10px; }
        .muted { color: var(--muted); }
        .status-msg {
            background: #e6f4ea; color: var(--ok);
            border: 1px solid #bfe3c8; border-radius: 8px;
            padding: 10px 14px; margin-bottom: 18px; font-size: .92rem;
        }
        .error-list {
            background: #fbeaec; color: var(--danger);
            border: 1px solid #f1c2c8; border-radius: 8px;
            padding: 10px 14px; margin-bottom: 18px; font-size: .9rem;
        }
        .error-list ul { margin: 4px 0 0; padding-left: 18px; }
        label { display: block; font-weight: 600; font-size: .88rem; margin-bottom: 6px; }
        input[type=text], input[type=file] {
            width: 100%; padding: 9px 10px; border: 1px solid var(--border);
            border-radius: 6px; font-size: .92rem; margin-bottom: 16px;
        }
        button, .btn {
            display: inline-block; background: var(--navy); color: #fff;
            border: none; border-radius: 6px; padding: 10px 18px;
            font-size: .92rem; font-weight: 600; cursor: pointer; text-decoration: none;
        }
        button.secondary, .btn.secondary { background: #fff; color: var(--navy); border: 1px solid var(--navy); }
        button.danger { background: var(--danger); }
        table { width: 100%; border-collapse: collapse; font-size: .92rem; }
        th, td { text-align: left; padding: 10px 8px; border-bottom: 1px solid var(--border); }
        th { color: var(--muted); font-size: .78rem; text-transform: uppercase; letter-spacing: .03em; }
        tr:hover { background: #fafbfc; }
        .badge {
            display: inline-block; padding: 3px 10px; border-radius: 999px;
            font-size: .76rem; font-weight: 700; letter-spacing: .02em;
        }
        .badge--working { background: #fff3cd; color: var(--warn); }
        .badge--info { background: #dbeafe; color: var(--info); }
        .badge--success { background: #d4edda; color: var(--ok); }
        .badge--danger { background: #f8d7da; color: var(--danger); }
        .transcript-box {
            white-space: pre-wrap; background: #fafbfc; border: 1px solid var(--border);
            border-radius: 8px; padding: 14px; font-size: .88rem; line-height: 1.55;
            max-height: 320px; overflow-y: auto;
        }
        ul.action-items { padding-left: 20px; }
        ul.action-items li { margin-bottom: 6px; }
        .top-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
        footer { text-align: center; color: var(--muted); font-size: .8rem; padding: 20px; }
    </style>
</head>
<body>
    <header class="site">
        <a href="{{ route('recordings.index') }}">ScribeFlow</a>
        <p>Upload a call recording &rarr; automatic transcript &rarr; AI summary &amp; action items</p>
    </header>

    <main>
        @if (session('status'))
            <div class="status-msg">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="error-list">
                <strong>Please fix the following:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <footer>ScribeFlow &middot; a Laravel demo project</footer>
</body>
</html>
