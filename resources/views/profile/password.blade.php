<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Change Password</title>
    </head>
    <body>
        <h1>Change Password</h1>

        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            <div>
                <label for="current_password">Current Password</label>
                <input id="current_password" type="password" name="current_password" required>
                @error('current_password')
                    <span>{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="password">New Password</label>
                <input id="password" type="password" name="password" required>
                @error('password')
                    <span>{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="password_confirmation">Confirm New Password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required>
            </div>

            <button type="submit">Change Password</button>
        </form>

        <p><a href="{{ route('profile') }}">Back to profile</a></p>
    </body>
</html>
