<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Change Password | LAPF Pension Fund System
    </title>

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
            background: #f4f6f9;
            font-family: Arial, Helvetica, sans-serif;
        }

        .container {
            width: 100%;
            max-width: 520px;
            padding: 20px;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 15px 40px rgba(0,0,0,.10);
            overflow: hidden;
        }

        .header {
            background: #17263c;
            color: white;
            padding: 28px;
        }

        .header h1 {
            margin: 0 0 8px;
            font-size: 23px;
        }

        .header p {
            margin: 0;
            opacity: .85;
            font-size: 14px;
        }

        .body {
            padding: 30px;
        }

        .policy {
            margin-bottom: 22px;
            padding: 15px;
            background: #f6f8fa;
            border-radius: 7px;
            font-size: 13px;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #d6dce2;
            border-radius: 6px;
        }

        button {
            width: 100%;
            padding: 13px;
            border: 0;
            border-radius: 6px;
            background: #17263c;
            color: white;
            font-weight: 700;
            cursor: pointer;
        }

        .error {
            margin-bottom: 20px;
            padding: 13px;
            background: #f8d7da;
            color: #842029;
            border-radius: 6px;
        }
    </style>
</head>

<body>

<div class="container">

    <div class="card">

        <div class="header">
            <h1>Change Your Password</h1>

            <p>
                Your temporary or expired password must
                be changed before you continue.
            </p>
        </div>

        <div class="body">

            @if($errors->any())
                <div class="error">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>
                                {{ $error }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="policy">
                <strong>Password requirements</strong>

                <br>

                Minimum length:
                {{ $policy->minimum_length }}

                <br>

                @if($policy->require_uppercase)
                    • At least one uppercase letter<br>
                @endif

                @if($policy->require_lowercase)
                    • At least one lowercase letter<br>
                @endif

                @if($policy->require_number)
                    • At least one number<br>
                @endif

                @if($policy->require_special_character)
                    • At least one special character<br>
                @endif

                @if(!$policy->allow_password_reuse)
                    • Previous passwords cannot be reused
                @endif
            </div>

            <form
                method="POST"
                action="{{ route('password.required.update') }}"
            >
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="current_password">
                        Current / Temporary Password
                    </label>

                    <input
                        type="password"
                        name="current_password"
                        id="current_password"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <div class="form-group">
                    <label for="password">
                        New Password
                    </label>

                    <input
                        type="password"
                        name="password"
                        id="password"
                        required
                        autocomplete="new-password"
                    >
                </div>

                <div class="form-group">
                    <label for="password_confirmation">
                        Confirm New Password
                    </label>

                    <input
                        type="password"
                        name="password_confirmation"
                        id="password_confirmation"
                        required
                        autocomplete="new-password"
                    >
                </div>

                <button type="submit">
                    Change Password
                </button>
            </form>

        </div>

    </div>

</div>

</body>
</html>