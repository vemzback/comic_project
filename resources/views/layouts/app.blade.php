<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="Discover new comics, chapters, and genres in the zYx comic platform.">
        <link rel="icon" type="image/png" href="{{ asset('images/zyx-logo-transparent.png') }}">
        <link rel="shortcut icon" type="image/png" href="{{ asset('images/zyx-logo-transparent.png') }}">
        <title>@yield('title', 'zYx comic')</title>
        @stack('head')
        @vite(['resources/css/app.css', 'resources/css/public.css', 'resources/js/app.js'])
    </head>
    <body class="{{ request()->routeIs('admin.*') ? 'admin-shell' : 'public-shell' }}">
        <a href="#main-content" class="skip-link">Skip to main content</a>
        <header class="site-header">
            <div class="container nav-wrap">
                <a href="{{ route('home') }}" class="brand" aria-label="zYx comic home">
                    <span class="brand-logo-frame" aria-hidden="true">
                        <img
                            src="{{ asset('images/zyx-logo-transparent.png') }}"
                            alt=""
                            class="brand-logo"
                            width="1254"
                            height="1254"
                        >
                    </span>
                    <span class="brand-wordmark" aria-hidden="true">comic</span>
                    <span class="sr-only">zYx comic</span>
                </a>

                <button class="menu-toggle" aria-expanded="false" aria-controls="{{ request()->routeIs('admin.*') ? 'site-menu' : 'mobile-menu-panel' }}" aria-label="Toggle navigation menu">
                    <span class="menu-icon">
                        <span class="hamburger-line hamburger-top"></span>
                        <span class="hamburger-line hamburger-middle"></span>
                        <span class="hamburger-line hamburger-bottom"></span>
                    </span>
                </button>

                <div id="site-menu" class="nav-menu" aria-label="Site navigation">
                    <nav class="main-nav glow-menu" aria-label="Main navigation">
                        <a href="{{ route('home') }}" class="nav-glow-link nav-glow-home" @if(request()->routeIs('home')) aria-current="page" @endif>
                            <span class="nav-glow-content">
                                <svg class="nav-glow-icon" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z" />
                                </svg>
                                <span>Home</span>
                            </span>
                            <span class="nav-glow-back" aria-hidden="true">Home</span>
                        </a>
                        <a href="{{ route('comics') }}" class="nav-glow-link nav-glow-comics" @if(request()->routeIs('comics') || request()->routeIs('comic.detail')) aria-current="page" @endif>
                            <span class="nav-glow-content">
                                <svg class="nav-glow-icon" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z" />
                                </svg>
                                <span>Comics</span>
                            </span>
                            <span class="nav-glow-back" aria-hidden="true">Comics</span>
                        </a>
                        <a href="{{ route('genres') }}" class="nav-glow-link nav-glow-genres" @if(request()->routeIs('genres') || request()->routeIs('genres.show')) aria-current="page" @endif>
                            <span class="nav-glow-content">
                                <svg class="nav-glow-icon" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M20.6 13.6 11 4H4v7l9.6 9.6a2 2 0 0 0 2.8 0l4.2-4.2a2 2 0 0 0 0-2.8Z" />
                                    <circle cx="7.5" cy="7.5" r="1" />
                                </svg>
                                <span>Genres</span>
                            </span>
                            <span class="nav-glow-back" aria-hidden="true">Genres</span>
                        </a>
                        <a href="{{ route('search') }}" class="nav-glow-link nav-glow-search" @if(request()->routeIs('search')) aria-current="page" @endif>
                            <span class="nav-glow-content">
                                <svg class="nav-glow-icon" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle cx="11" cy="11" r="7" />
                                    <path d="m20 20-4-4" />
                                </svg>
                                <span>Search</span>
                            </span>
                            <span class="nav-glow-back" aria-hidden="true">Search</span>
                        </a>
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

                @unless (request()->routeIs('admin.*'))
                    <div id="mobile-menu-panel" class="mobile-menu-panel" aria-label="Mobile navigation">
                        <nav class="mobile-text-nav" aria-label="Mobile main navigation">
                            <a href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>Home</a>
                            <a href="{{ route('comics') }}" @if(request()->routeIs('comics') || request()->routeIs('comic.detail')) aria-current="page" @endif>Comics</a>
                            <a href="{{ route('genres') }}" @if(request()->routeIs('genres') || request()->routeIs('genres.show')) aria-current="page" @endif>Genres</a>
                            <a href="{{ route('search') }}" @if(request()->routeIs('search')) aria-current="page" @endif>Search</a>
                        </nav>

                        <div class="mobile-account-nav">
                            @guest
                                <a href="{{ route('login') }}">Login</a>
                                <a href="{{ route('register') }}" class="mobile-register-link">Register</a>
                            @else
                                <a href="{{ route('profile') }}">Profile</a>
                                <a href="{{ route('bookmarks.index') }}">Bookmarks</a>
                                <a href="{{ route('history.index') }}">Reading history</a>

                                @if (auth()->user()->role === 'admin')
                                    <a href="{{ route('admin.dashboard') }}">Admin dashboard</a>
                                @endif

                                <form method="POST" action="{{ route('logout') }}" class="mobile-logout-form">
                                    @csrf
                                    <button type="submit">Logout</button>
                                </form>
                            @endguest
                        </div>
                    </div>
                @endunless
            </div>
        </header>

        <main id="main-content" class="site-main" tabindex="-1">
            @yield('content')
        </main>

        <footer class="site-footer">
            <div class="container footer-wrap footer-editorial">
                @if (request()->routeIs('admin.*'))
                    <div class="footer-admin-summary">
                        <p>&copy; {{ now()->year }} zYx comic administration.</p>
                        <a href="{{ route('home') }}">View public website</a>
                    </div>
                @else
                <div class="footer-main">
                    <div class="footer-about">
                        <p class="eyebrow">Established 2026</p>
                        <h2>zYx comic</h2>
                        <p>An independent digital comic platform for discovering, reading, rating, and discussing stories with the community.</p>
                    </div>

                    <nav class="footer-column" aria-label="Explore">
                        <h3>Explore</h3>
                        <ul class="footer-links">
                            <li><a href="{{ route('home') }}">Home</a></li>
                            <li><a href="{{ route('comics') }}">Catalog</a></li>
                            <li><a href="{{ route('genres') }}">Genres</a></li>
                            <li><a href="{{ route('search') }}">Search</a></li>
                        </ul>
                    </nav>

                    <nav class="footer-column" aria-label="Account">
                        <h3>Account</h3>
                        <ul class="footer-links">
                            @guest
                                <li><a href="{{ route('login') }}">Login</a></li>
                                <li><a href="{{ route('register') }}">Register</a></li>
                            @else
                                <li><a href="{{ route('profile') }}">Profile</a></li>
                                <li><a href="{{ route('bookmarks.index') }}">Bookmarks</a></li>
                                <li><a href="{{ route('history.index') }}">Reading history</a></li>
                            @endguest
                        </ul>
                    </nav>

                    <div class="footer-column footer-connect">
                        <h3>Contact &amp; social</h3>
                        <p>Official contact details and social channels are coming soon.</p>
                        <ul class="footer-social-list" aria-label="Planned social channels">
                            <li>Instagram</li>
                            <li>X</li>
                            <li>Discord</li>
                        </ul>
                    </div>
                </div>

                <div class="footer-bottom">
                    <p>&copy; {{ now()->year }} zYx comic. All rights reserved.</p>
                    <p>Independent digital library launched in 2026.</p>
                </div>
                @endif
            </div>
        </footer>
    </body>
</html>
