<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="Discover new comics, chapters, and genres in the Comic Project platform.">
        <title>@yield('title', 'Comic Project')</title>
        @vite(['resources/css/app.css', 'resources/css/public.css', 'resources/js/app.js'])
    </head>
    <body>
        <header class="site-header">
            <div class="container nav-wrap">
                <a href="{{ route('home') }}" class="brand">Comic Project</a>

                <nav class="main-nav" aria-label="Main navigation">
                    <a href="{{ route('home') }}">Home</a>
                    <a href="{{ route('comics') }}">Comics</a>
                    <a href="{{ route('genres') }}">Genres</a>
                    <a href="{{ route('search') }}">Search</a>
                </nav>

                <div class="nav-actions">
                    @guest
                        <a href="{{ route('login') }}" class="btn btn-ghost">Login</a>
                        <a href="{{ route('register') }}" class="btn btn-primary">Register</a>
                    @else
                        <a href="{{ route('profile') }}" class="btn btn-ghost">Profile</a>

                        @if (auth()->user()->role === 'admin')
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost">Admin Dashboard</a>
                        @endif

                        <form method="POST" action="{{ route('logout') }}" class="inline-form">
                            @csrf
                            <button type="submit" class="btn btn-danger">Logout</button>
                        </form>
                    @endguest
                </div>
            </div>
        </header>

        <main>
            @yield('content')
        </main>

        <footer class="site-footer">
            <div class="container footer-wrap">
                <div>
                    <h3>Comic Project</h3>
                    <p>Discover your next favorite comic.</p>
                </div>
                <div>
                    <ul class="footer-links">
                        <li><a href="{{ route('home') }}">Home</a></li>
                        <li><a href="{{ route('comics') }}">Comics</a></li>
                        <li><a href="{{ route('genres') }}">Genres</a></li>
                    </ul>
                </div>
            </div>
        </footer>
    </body>
</html>
