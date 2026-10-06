<section class="auth-promo" aria-labelledby="auth-promo-title" data-auth-light-scene data-auth-light-state="off">
    <a class="brand brand-light" href="{{ route('home') }}" aria-label="Vistiq English — trang chủ">
        <img class="brand-mark" src="{{ asset('images/vistiq-logo.png') }}" alt="Vistiq">
    </a>

    <div class="auth-promo-copy">
        <span class="promo-label"><i aria-hidden="true"></i> CHỖ HỌC CỦA BẠN</span>
        <h1 id="auth-promo-title">Tiến từng bước,<br><em>xa thêm một chút.</em></h1>
        <p>Tra cứu từ vựng, luyện tập và giữ lại những điều bạn đã học — trong cùng một không gian.</p>

        <div class="auth-landscape">
            <svg viewBox="0 0 640 330" role="presentation" aria-hidden="true" focusable="false">
                <defs>
                    <linearGradient id="auth-sky" x1="0" y1="0" x2="0.86" y2="1">
                        <stop class="auth-sky-top" offset="0" stop-color="#142e3b" />
                        <stop class="auth-sky-bottom" offset="1" stop-color="#09151f" />
                    </linearGradient>
                    <radialGradient id="auth-moonlight">
                        <stop offset="0" stop-color="#ffe6b0" stop-opacity=".58" />
                        <stop offset="1" stop-color="#ffe6b0" stop-opacity="0" />
                    </radialGradient>
                    <linearGradient id="auth-ridge" x1="0" y1="0" x2="0" y2="1">
                        <stop class="auth-ridge-top" offset="0" stop-color="#6b969c" />
                        <stop class="auth-ridge-bottom" offset="1" stop-color="#203b46" />
                    </linearGradient>
                </defs>
                <rect width="640" height="330" rx="20" fill="url(#auth-sky)" />
                <circle cx="460" cy="84" r="94" fill="url(#auth-moonlight)" />
                <circle class="auth-light-source" cx="460" cy="84" r="27" fill="#f9e4bc" />
                <g class="auth-stars" fill="#c7e8dd">
                    <circle cx="80" cy="72" r="1.8" />
                    <circle cx="167" cy="51" r="1.4" />
                    <circle cx="557" cy="143" r="1.6" />
                </g>
                <path class="auth-mountain-back" d="M0 206 104 113l91 75 99-118 131 146 83-76 132 96v94H0Z" fill="#1d3945" />
                <path class="auth-snow-lines" d="m125 132-21-19 95 75-23-2zM294 70l-21 31 21-18 23 24zM425 217l72-77 47 37-43-20z" fill="#a2c9c0" fill-opacity=".47" />
                <path d="m0 243 137-85 111 81 117-77 95 81 93-43 87 64v66H0Z" fill="url(#auth-ridge)" />
                <path class="auth-snow-lines" d="m139 158-18 12 17-5 14 10zM365 162l-24 16 24-7 16 10zM553 201l-21 12 18-4 14 11z" fill="#bad8c9" fill-opacity=".6" />
                <path class="auth-ground" d="M0 271c98-26 167-8 248 12s164 14 241-7 106-17 151-6v60H0Z" fill="#0c1e29" />
                <path d="M0 295c114-13 194-2 294 14s226-3 346-15" fill="none" stroke="#9ac2b2" stroke-opacity=".2" />
                <g class="auth-trees" fill="#cde8d8" fill-opacity=".7">
                    <path d="M88 250h3v24h-3zM79 261l10-19 10 19z" />
                    <path d="M533 237h3v26h-3zM523 250l11-21 10 21z" />
                </g>
            </svg>
            <span class="landscape-word"><small>TỪ VỰNG · ÁNH SÁNG</small><b>luminous</b><span>/ˈluː.mə.nəs/</span></span>
            <span class="landscape-caption"><i aria-hidden="true"></i> <span data-auth-light-status aria-live="polite">Trời sáng · đèn pin đang tắt</span></span>
        </div>
    </div>

    <span class="promo-foot">MỖI NGÀY MỘT CHÚT TIẾN BỘ</span>
</section>

<section class="auth-form-panel" aria-label="Tài khoản Vistiq">
    <div class="auth-form-wrap">@include($page['view'])</div>
    <div class="auth-footer">
        <span>© {{ date('Y') }} Vistiq English</span>
        <a href="{{ route('home') }}">Về trang chủ <span aria-hidden="true">@include('pages.dictionary.icon', ['name' => 'external-link'])</span></a>
    </div>
</section>
