<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Presensi Wajah SMK Ar Rahmah</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #0f766e; margin: 0; min-height: 100vh; display: grid; place-items: center; }
        .card { background: #fff; padding: 2rem; border-radius: 10px; width: 100%; max-width: 360px; }
        label { display: block; font-weight: 600; margin: .8rem 0 .25rem; }
        input { width: 100%; padding: .55rem; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; margin-top: 1.2rem; padding: .65rem; background: #0f766e; color: #fff; border: 0; border-radius: 6px; cursor: pointer; }
        .err { background: #fee2e2; color: #b91c1c; padding: .6rem; border-radius: 6px; margin-bottom: .8rem; }
        .ok { background: #dcfce7; color: #15803d; padding: .6rem; border-radius: 6px; margin-bottom: .8rem; }
    </style>
</head>
<body>
<div class="card">
    <h2>Masuk</h2>
    <p>Presensi Wajah SMK Ar Rahmah Bantul</p>

    @if (session('error')) <div class="err">{{ session('error') }}</div> @endif
    @if (session('success')) <div class="ok">{{ session('success') }}</div> @endif
    @if ($errors->any()) <div class="err">{{ $errors->first() }}</div> @endif

    <form method="POST" action="{{ route('login.proses') }}">
        @csrf
        <label for="username">Username</label>
        <input id="username" name="username" value="{{ old('username') }}" required autofocus>
        <label for="password">Password</label>
        <input id="password" type="password" name="password" required>
        <button type="submit">Masuk</button>
    </form>
</div>
</body>
</html>