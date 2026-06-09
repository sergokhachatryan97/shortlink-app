@extends('layouts.app')

@section('title', 'Privacy Policy — Trastly')

@section('content')
<div class="cosmic-page-section">
    <div class="container" style="max-width:720px;position:relative;z-index:1;padding:2rem 1rem 3rem;">
        <h1 style="color:#fff;font-size:1.75rem;font-weight:700;margin-bottom:1.5rem;">Privacy Policy</h1>
        <div style="color:rgba(255,255,255,0.8);font-size:0.9375rem;line-height:1.7;">
            <p><strong>Last updated:</strong> June 9, 2026</p>

            <h3 style="color:#fff;margin-top:1.5rem;">1. Introduction</h3>
            <p>Trastly ("we", "our", "us") operates the website trastly.org and the Trastly UTM Builder & Link Shortener browser extension. This Privacy Policy explains how we handle your information.</p>

            <h3 style="color:#fff;margin-top:1.5rem;">2. Information We Collect</h3>
            <p><strong>Website:</strong> When you register, we collect your email address and name. Payment information is processed by third-party providers (YooKassa) and is not stored on our servers.</p>
            <p><strong>Browser Extension:</strong> The extension reads the URL and title of your active browser tab solely to auto-fill the UTM builder form. All settings, presets, and link history are stored locally in your browser using chrome.storage.local. No browsing data is transmitted to our servers.</p>
            <p>The only network request the extension makes is when you explicitly click "Shorten with Trastly" — this sends the generated UTM URL to the Trastly API to create a short link.</p>

            <h3 style="color:#fff;margin-top:1.5rem;">3. How We Use Information</h3>
            <ul>
                <li>To provide link shortening and UTM building services</li>
                <li>To manage your account and balance</li>
                <li>To process payments</li>
            </ul>

            <h3 style="color:#fff;margin-top:1.5rem;">4. Data Sharing</h3>
            <p>We do not sell, trade, or share your personal data with third parties, except as required by law or to process payments through our payment providers.</p>

            <h3 style="color:#fff;margin-top:1.5rem;">5. Data Storage</h3>
            <p>Website data is stored on secure servers. Extension data is stored locally in your browser and never leaves your device unless you initiate a link shortening request.</p>

            <h3 style="color:#fff;margin-top:1.5rem;">6. Cookies</h3>
            <p>We use essential cookies for authentication and session management. We may use analytics cookies (Yandex Metrika, Google Analytics) to understand site usage.</p>

            <h3 style="color:#fff;margin-top:1.5rem;">7. Your Rights</h3>
            <p>You can delete your account and all associated data by contacting us. Extension data can be cleared by removing the extension from your browser.</p>

            <h3 style="color:#fff;margin-top:1.5rem;">8. Contact</h3>
            <p>For privacy questions, contact us at <a href="mailto:support@trastly.org" style="color:#a78bfa;">support@trastly.org</a></p>
        </div>
    </div>
</div>
@endsection
