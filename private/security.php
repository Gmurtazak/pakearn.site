<?php


if (!headers_sent()) {

    // ---------- Content Security Policy (CSP) ----------
    // Yeh browser ko batata hai ke kis kis source se content load karna allowed hai.
    $csp = [
        "default-src 'self'",               // Sab kuch sirf apni domain se load ho
        "script-src 'self'",                // JavaScript files bhi sirf self se
        "script-src-attr 'none'",           // Inline JS jaise onclick/onerror disable
        "object-src 'none'",                // Flash/Java jese plugins block
        "base-uri 'none'",                  // <base> tag ki chedkhaani rokta hai
        "frame-ancestors 'none'",           // Page kisi aur site ke iframe mein load na ho (anti clickjacking)
        "form-action 'self'",               // Forms sirf self domain pe submit ho sakte hain
        "img-src 'self' data:",             // Apni domain aur base64 images allowed
        "style-src 'self' 'unsafe-inline'", // Inline CSS allow (agar na chahiye to 'unsafe-inline' hata do)
        "connect-src 'self'",               // Ajax ya WebSocket bhi sirf self se
        "upgrade-insecure-requests"         // http:// requests ko auto https:// bana do
    ];
    header('Content-Security-Policy: ' . implode('; ', $csp));

    // ---------- Extra security headers ----------
    header('X-Content-Type-Options: nosniff');                   // Browser ko force karta hai ke correct content type use kare
    header('X-Frame-Options: SAMEORIGIN');                       // Page sirf apne iframe mein allowed ho
    header('Referrer-Policy: strict-origin-when-cross-origin'); // Referrer info limited rakhta hai
    header('Permissions-Policy: geolocation=(), camera=(), microphone=()'); // Unused features disable

    // ---------- HSTS (Only if using HTTPS) ----------
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
    }
}
?>