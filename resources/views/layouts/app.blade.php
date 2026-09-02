<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="Discover new comics, chapters, and genres in the zYx comic platform.">
        <title>@yield('title', 'zYx comic')</title>
        @stack('head')
        @vite(['resources/css/app.css', 'resources/css/public.css', 'resources/js/app.js'])
    </head>
    <body class="{{ request()->routeIs('admin.*') ? 'admin-shell' : 'public-shell' }}">
        <a href="#main-content" class="skip-link">Skip to main content</a>
        <header class="site-header">
            <div class="container nav-wrap">
                <a href="{{ route('home') }}" class="brand" aria-label="zYx comic home">
                    <span class="brand-mark">zYx</span>
                    <span class="brand-sub">comic</span>
                </a>

                <button class="menu-toggle" aria-expanded="false" aria-controls="site-menu" aria-label="Toggle navigation menu">
                    <span class="menu-icon">
                        <span class="hamburger-line hamburger-top"></span>
                        <span class="hamburger-line hamburger-middle"></span>
                        <span class="hamburger-line hamburger-bottom"></span>
                    </span>
                </button>

                <div id="site-menu" class="nav-menu" aria-label="Site navigation">
                    <nav class="main-nav" aria-label="Main navigation">
                        <a href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>Home</a>
                        <a href="{{ route('comics') }}" @if(request()->routeIs('comics') || request()->routeIs('comic.detail')) aria-current="page" @endif>Comics</a>
                        <a href="{{ route('genres') }}" @if(request()->routeIs('genres') || request()->routeIs('genres.show')) aria-current="page" @endif>Genres</a>
                        <a href="{{ route('search') }}" @if(request()->routeIs('search')) aria-current="page" @endif>Search</a>
                    </nav>

                    <div class="nav-actions">
                        @guest
                            <a href="{{ route('login') }}" class="btn btn-ghost">Login</a>
                            <a href="{{ route('register') }}" class="btn btn-primary">Register</a>
                        @else
                            <a href="{{ route('profile') }}" class="btn btn-ghost">Profile</a>
                            <a href="{{ route('bookmarks.index') }}" class="btn btn-ghost">Bookmarks</a>
                            <a href="{{ route('history.index') }}" class="btn btn-ghost">Reading History</a>

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
            </div>
        </header>

        <main id="main-content" class="site-main" tabindex="-1">
            @yield('content')
        </main>

        <footer class="site-footer">
            <div class="container footer-wrap footer-editorial">
                <div class="footer-topline">
                    <div>
                        <p class="eyebrow">Independent digital library</p>
                        <h3>zYx comic</h3>
                        <p>Stories worth collecting. Chapters worth staying up for.</p>
                    </div>
                    <nav aria-label="Footer navigation">
                    <ul class="footer-links">
                        <li><a href="{{ route('home') }}">Home</a></li>
                        <li><a href="{{ route('comics') }}">Catalog</a></li>
                        <li><a href="{{ route('genres') }}">Genres</a></li>
                        <li><a href="{{ route('search') }}">Search</a></li>
                    </ul>
                    </nav>
                </div>
            </div>
        </footer>
    </body>
</html>
