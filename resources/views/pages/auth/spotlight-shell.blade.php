<section class="spotlight-promo" aria-labelledby="spotlight-promo-title">
    <div class="spotlight-landscape" aria-hidden="true">
        <svg viewBox="0 0 480 520" preserveAspectRatio="xMidYMin slice" focusable="false">
            <defs>
                <linearGradient id="spotlight-sky" x2="0" y2="1">
                    <stop stop-color="var(--spotlight-scene-top)"/>
                    <stop offset=".65" stop-color="var(--spotlight-scene-mid)"/>
                    <stop offset="1" stop-color="var(--spotlight-scene-bottom)"/>
                </linearGradient>
                <radialGradient id="spotlight-moon-halo">
                    <stop stop-color="#dcecff" stop-opacity=".22"/>
                    <stop offset="1" stop-color="#b6d3e9" stop-opacity="0"/>
                </radialGradient>
                <radialGradient id="spotlight-moon" cx=".3" cy=".23">
                    <stop stop-color="#fff"/>
                    <stop offset=".5" stop-color="#d7e3ed"/>
                    <stop offset="1" stop-color="#8193a2"/>
                </radialGradient>
                <linearGradient id="spotlight-water" x2="0" y2="1">
                    <stop stop-color="var(--spotlight-water-top)"/>
                    <stop offset="1" stop-color="var(--spotlight-water-bottom)"/>
                </linearGradient>
                <radialGradient id="spotlight-sun-halo">
                    <stop stop-color="var(--spotlight-sun-glow)" stop-opacity=".66"/>
                    <stop offset="1" stop-color="var(--spotlight-sun-glow)" stop-opacity="0"/>
                </radialGradient>
                <radialGradient id="spotlight-sun-core" cx=".31" cy=".27">
                    <stop stop-color="#fff6d7"/>
                    <stop offset=".66" stop-color="var(--spotlight-sun)"/>
                    <stop offset="1" stop-color="#da8a30"/>
                </radialGradient>
            </defs>
            <rect width="480" height="520" fill="url(#spotlight-sky)"/>
            <g class="spotlight-clouds">
                <path d="M42 112h64m-50 12h74M189 75h53m-35 11h69"/>
            </g>
            <g class="spotlight-stars" fill="#cfdeea">
                <circle cx="38" cy="66" r="1.4"/><circle cx="108" cy="41" r="1"/>
                <circle cx="159" cy="101" r="1.2"/><circle cx="244" cy="50" r="1.4"/>
                <circle cx="317" cy="90" r="1"/><circle cx="424" cy="54" r="1.3"/>
                <circle cx="81" cy="171" r="1"/><circle cx="221" cy="142" r="1"/>
                <circle cx="390" cy="184" r="1.3"/>
                <path d="m58 126 2-5 2 5 5 2-5 2-2 5-2-5-5-2z"/>
                <path d="m278 166 1.5-4 1.5 4 4 1.5-4 1.5-1.5 4-1.5-4-4-1.5z"/>
            </g>
            <g class="spotlight-sun">
                <circle class="spotlight-sun-halo" cx="365" cy="119" r="75" fill="url(#spotlight-sun-halo)"/>
                <path class="spotlight-sun-rays" d="M365 72v-14m0 122v-14m47-47h14m-122 0h14m80-33 10-10m-86 86 10-10m66 0 10 10m-86-86 10 10"/>
                <circle class="spotlight-sun-core" cx="365" cy="119" r="27" fill="url(#spotlight-sun-core)"/>
            </g>
            <g class="spotlight-moon">
                <circle class="spotlight-moon-glow" cx="365" cy="119" r="74" fill="url(#spotlight-moon-halo)"/>
                <circle cx="365" cy="119" r="26" fill="url(#spotlight-moon)"/>
            </g>
            <path class="spotlight-mountain-back" d="m0 298 58-83 44 48 99-122 73 89 50-55 68 77 49-42 39 47v120H0z"/>
            <path class="spotlight-snow" d="m201 141-28 37 28-13 21 17zM324 175l-19 25 19-10 17 13zM392 210l-17 20 17-8 14 11z"/>
            <path class="spotlight-mountain-mid" d="m0 344 77-83 59 53 75-69 78 91 65-77 65 73 61-48v109H0z"/>
            <path class="spotlight-mountain-front" d="m0 391 94-55 71 61 80-57 64 53 73-43 98 51v65H0z"/>
            <path class="spotlight-ground" d="M0 425c83-24 154-8 232 8s163-5 248-18v105H0z"/>
            <path d="M0 454c85-20 146-8 229 8s165-4 251-16v74H0z" fill="url(#spotlight-water)"/>
            <path class="spotlight-water-line" d="M0 457c82-14 138-2 216 10m26 3c66 10 142 0 238-16M0 487c74-10 131 3 199 12m50 2c79 7 145-4 231-12"/>
            <g class="spotlight-trees">
                <path d="m442 325-20 35h13l-17 24h20v27h7v-27h20l-17-24h13z"/>
                <path transform="translate(266 18)" d="m92 352-15 27h10l-13 18h16v21h6v-21h16l-13-18h10z"/>
            </g>
            <g class="spotlight-meadow">
                <path d="m0 434 9-18 5 18 10-25 4 27m418-2 8-23 5 22 11-29 5 32M336 447l8-16 5 19 8-28 4 29"/>
                <path class="spotlight-meadow-flower" d="M377 415c-4-5 3-9 7-5 2-6 10-4 9 2 7-1 8 8 2 10 3 6-5 10-9 5-4 5-11 0-7-6-6-2-7-9-2-9z"/>
                <circle cx="419" cy="420" r="2.2" fill="#fff6d7"/>
            </g>
        </svg>
    </div>

    <a class="brand brand-light" href="{{ route('home') }}" aria-label="Vistiq English — trang chủ">
        <img class="brand-mark" src="{{ asset('images/vistiq-logo.png') }}" alt="Vistiq" width="64" height="64">
        <span class="brand-wordmark">vistiq <span>english</span></span>
    </a>

    <div class="spotlight-promo-copy">
        <span class="spotlight-kicker"><span aria-hidden="true">@include('pages.dictionary.icon', ['name' => 'sparkle'])</span> VISTIQ ENGLISH</span>
        <h1 id="spotlight-promo-title">Chạm đến<br> <em>tri thức mới.</em></h1>
        <p>Thêm một từ mới,<br> tiến gần hơn mỗi ngày.</p>
    </div>
</section>

<section class="auth-form-panel" aria-label="Tài khoản Vistiq">
    <a class="spotlight-close" href="{{ route('home') }}" aria-label="Về trang chủ">
        <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m5 5 10 10M15 5 5 15"/></svg>
    </a>
    <div class="auth-form-wrap">@include($page['view'])</div>
    <div class="auth-footer"><span>© {{ date('Y') }} Vistiq English</span><a href="{{ route('home') }}">Về trang chủ <span aria-hidden="true">@include('pages.dictionary.icon', ['name' => 'external-link'])</span></a></div>
</section>

<svg class="spotlight-beam" data-spotlight-beam aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="spotlight-beam-gradient" gradientUnits="userSpaceOnUse">
            <stop stop-color="#fff4bd" stop-opacity=".78"/>
            <stop offset=".28" stop-color="#fff0a6" stop-opacity=".38"/>
            <stop offset="1" stop-color="#fff0a6" stop-opacity="0"/>
        </linearGradient>
        <filter id="spotlight-beam-blur" x="-50%" y="-50%" width="200%" height="200%">
            <feGaussianBlur stdDeviation="4"/>
        </filter>
    </defs>
    <polygon data-spotlight-cone fill="url(#spotlight-beam-gradient)" filter="url(#spotlight-beam-blur)"/>
</svg>
