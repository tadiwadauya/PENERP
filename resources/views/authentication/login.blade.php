<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Login | LAPF Pension Fund System</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f6f9;
        }

        .login-wrapper {
            width: 100%;
            max-width: 430px;
            padding: 20px;
        }

        .login-card {
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.10);
            overflow: hidden;
        }

        .login-header {
            padding: 30px 30px 22px;
            background: #17263c;
            color: #ffffff;
            text-align: center;
        }

        .login-header h1 {
            margin: 0 0 8px;
            font-size: 24px;
        }

        .login-header p {
            margin: 0;
            font-size: 14px;
            opacity: 0.85;
        }

        .login-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            color: #263238;
            font-size: 14px;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            height: 46px;
            padding: 0 13px;
            border: 1px solid #d6dce2;
            border-radius: 7px;
            font-size: 15px;
            outline: none;
        }

        .form-control:focus {
            border-color: #17263c;
        }

        .remember-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #4d5966;
        }

        .login-button {
            width: 100%;
            height: 46px;
            border: 0;
            border-radius: 7px;
            background: #17263c;
            color: #ffffff;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }

        .login-button:hover {
            opacity: 0.94;
        }

        .alert {
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 7px;
            font-size: 14px;
        }

        .alert-danger {
            background: #f8d7da;
            color: #842029;
        }

        .alert-success {
            background: #d1e7dd;
            color: #0f5132;
        }

        .login-footer {
            padding: 18px 30px;
            border-top: 1px solid #edf0f2;
            text-align: center;
            color: #6c757d;
            font-size: 12px;
        }
    </style>
</head>

<body>

<div class="login-wrapper">

    <div class="login-card">

        <div class="login-header">
            <h1>LAPF Pension Fund System</h1>
            <p>Secure System Access</p>
        </div>

        <div class="login-body">

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul style="margin: 0; padding-left: 18px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}">
                @csrf

                <div class="form-group">
                    <label for="login">
                        Username / Email / Employee Number
                    </label>

                    <input
                        type="text"
                        name="login"
                        id="login"
                        class="form-control"
                        value="{{ old('login') }}"
                        required
                        autofocus
                        autocomplete="username"
                    >
                </div>

                <div class="form-group">
                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <label class="remember-row">
                    <input
                        type="checkbox"
                        name="remember"
                        value="1"
                    >

                    Remember me
                </label>

                <button
                    type="submit"
                    class="login-button"
                >
                    Sign In
                </button>

            </form>

        </div>

        <div class="login-footer">
            Local Authorities Pension Fund
        </div>

    </div>

</div>

</body>
</html>