<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in — MCS P&I</title>
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <h1>MCS P&amp;I System</h1>
        <p class="subtitle">Purchasing &amp; Inventory Tracking</p>
        @if($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-group">
                <label for="user_id">User ID</label>
                <input type="text" id="user_id" name="user_id" value="{{ old('user_id') }}" required autofocus autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary">Sign in</button>
        </form>
        <div class="demo-accounts">
            <strong>Demo accounts</strong><br>
            Administrator — <code>admin</code> / <code>admin123</code><br>
            Purchasing Officer — <code>po1</code> / <code>po123</code><br>
            Inventory Officer — <code>io1</code> / <code>io123</code><br>
            End User — <code>eu1</code> / <code>eu123</code>
        </div>
    </div>
</div>
</body>
</html>
