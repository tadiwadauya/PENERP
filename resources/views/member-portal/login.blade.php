<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>PENERP Member Portal - Login</title>
<style>body{font-family:Arial,sans-serif;background:#edf3fb;margin:0;color:#172b4d}.card{max-width:410px;margin:9vh auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 8px 30px #163c6b20}h1{color:#124c92;font-size:25px}label{display:block;margin:16px 0 6px;font-weight:bold}input{box-sizing:border-box;width:100%;padding:12px;border:1px solid #bdcde0;border-radius:6px;font-size:16px}button{width:100%;padding:13px;margin-top:24px;background:#125aa5;color:white;border:0;border-radius:6px;font-size:16px;cursor:pointer}.error{background:#fff0f0;color:#9c2424;padding:10px;border-radius:6px}.hint{color:#52677e;font-size:13px}</style></head>
<body><main class="card"><h1>PENERP Member Portal</h1><p>Log in to view your Benefit Statements.</p>
@if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('member-portal.login.submit') }}">@csrf
<label for="member_number">Member Number</label><input id="member_number" name="member_number" value="{{ old('member_number') }}" autocomplete="username" required autofocus>
<p class="hint">Enter your PENERP or PenAd Member Number.</p>
<label for="national_id">National ID Number</label><input id="national_id" name="national_id" type="password" autocomplete="current-password" required>
<p class="hint">Enter your National ID without spaces or hyphens.</p><button type="submit">Log In</button></form></main></body></html>
