<?php
session_start();
include 'db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: products.php");
    exit();
}

$preview_sql = "SELECT products.*, categories.category_name
                FROM products
                JOIN categories ON products.category_id = categories.category_id
                WHERE products.status = 'active'
                ORDER BY products.created_at DESC
                LIMIT 4";
$preview_result = mysqli_query($conn, $preview_sql);
$preview_items  = [];
while ($row = mysqli_fetch_assoc($preview_result)) {
    $preview_items[] = $row;
}

$student_count_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users"));
$student_count     = $student_count_row['total'];

$listing_count_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM products"));
$listing_count     = $listing_count_row['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusLink — Built for Student Hustle</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --orange:     orange;
            --orange-lt:  #fff0e5;
            --orange-mid: #fff0e5;
            --cream:      #f8f1e8;
            --cream-dark: #eadfd2;
            --nav-bg:     #fffaf3;
            --text:       #111827;
            --muted:      #6b7280;
            --card-bg:    #ffffff;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--cream);
            color: var(--text);
            overflow-x: hidden;
        }

        a { text-decoration: none; color: inherit; }

        nav {
            position: sticky;
            top: 0;
            z-index: 100;
            background: var(--nav-bg);
            border-bottom: 1px solid var(--cream-dark);
            padding: 0 8%;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .nav-logo { display: flex; align-items: center; gap: 10px; }

        .nav-logo-icon {
            width: 40px; height: 40px;
            background: var(--orange);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .nav-logo-text strong { display: block; font-family: 'Sora', sans-serif; font-size: 16px; font-weight: 800; }
        .nav-logo-text span { display: block; font-size: 10px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.07em; }

        .nav-links { display: flex; align-items: center; gap: 10px; }

        .nav-links a {
            font-size: 14px; font-weight: 500; color: #374151;
            padding: 9px 18px; border-radius: 12px; transition: background 0.15s;
        }

        .nav-links a:hover { background: var(--orange-mid); color: var(--orange); }

        .nav-cta {
            background: var(--orange) !important; color: white !important;
            font-weight: 700 !important; box-shadow: 0 2px 8px rgba(255,165,0,0.35);
        }

        .nav-cta:hover { opacity: 0.9; background: var(--orange) !important; }

        .hero {
            padding: 90px 8% 80px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .hero-eyebrow {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--orange-mid); color: var(--orange);
            font-size: 13px; font-weight: 700;
            padding: 6px 14px; border-radius: 999px;
            margin-bottom: 22px; text-transform: uppercase; letter-spacing: 0.06em;
        }

        .hero h1 {
            font-family: 'Sora', sans-serif; font-size: 52px;
            font-weight: 800; line-height: 1.1; color: #020617; margin-bottom: 22px;
        }

        .hero h1 span { color: var(--orange); }

        .hero p {
            font-size: 17px; color: var(--muted); line-height: 1.7;
            margin-bottom: 36px; max-width: 480px;
        }

        .hero-btns { display: flex; gap: 14px; flex-wrap: wrap; }

        .btn-primary {
            background: var(--orange); color: white;
            padding: 14px 28px; border-radius: 14px;
            font-weight: 700; font-size: 15px;
            box-shadow: 0 4px 14px rgba(255,165,0,0.35);
            transition: opacity 0.15s, transform 0.15s;
        }

        .btn-primary:hover { opacity: 0.92; transform: translateY(-1px); }

        .btn-secondary {
            background: white; color: var(--text);
            padding: 14px 28px; border-radius: 14px;
            font-weight: 600; font-size: 15px;
            border: 1.5px solid var(--cream-dark);
            transition: border-color 0.15s, background 0.15s;
        }

        .btn-secondary:hover { border-color: var(--orange); background: var(--orange-lt); }

        .hero-stats {
            display: flex; gap: 28px;
            margin-top: 40px; padding-top: 32px;
            border-top: 1px solid var(--cream-dark);
        }

        .stat-item strong {
            display: block; font-family: 'Sora', sans-serif;
            font-size: 26px; font-weight: 800; color: var(--orange);
        }

        .stat-item span { font-size: 13px; color: var(--muted); margin-top: 2px; display: block; }

        /* Hero visual */
        .hero-visual { position: relative; }

        .hero-card-stack {
            position: relative;
            height: 480px;
        }

        .floating-card {
            position: absolute;
            background: white;
            border: 1px solid var(--cream-dark);
            border-radius: 20px;
            padding: 18px 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            animation: float 6s ease-in-out infinite;
        }

        .floating-card:nth-child(2) { animation-delay: -2s; }
        .floating-card:nth-child(3) { animation-delay: -4s; }
        .floating-card:nth-child(4) { animation-delay: -1s; }
        .floating-card:nth-child(5) { animation-delay: -3s; }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50%       { transform: translateY(-8px); }
        }

        /* Card positions — 5 cards laid out in a staggered column */
        .card-main      { top: 0;    left: 0;   right: 80px; z-index: 3; }
        .card-secondary { top: 95px; left: 60px; right: 0;   z-index: 2; }
        .card-accent    { top: 185px; right: 0; z-index: 4; padding: 14px 18px; min-width: 150px; }
        .card-order     { top: 210px; left: 0;  right: 100px; z-index: 3; }
        .card-message   { top: 340px; left: 40px; right: 0;  z-index: 2; }

        .mini-product { display: flex; align-items: center; gap: 14px; }

        .mini-product-img {
            width: 52px; height: 52px;
            background: var(--orange-mid);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; color: var(--orange);
        }

        .mini-product-name { font-weight: 700; font-size: 14px; margin-bottom: 3px; }
        .mini-product-price { color: var(--orange); font-weight: 800; font-size: 15px; }
        .mini-product-seller { font-size: 12px; color: var(--muted); margin-top: 2px; }

        .badge-new {
            background: #f0fdf4; color: #16a34a;
            font-size: 11px; font-weight: 700;
            padding: 3px 9px; border-radius: 999px;
            display: inline-block; margin-top: 5px;
        }

        .accent-stat { text-align: center; }

        .accent-stat strong {
            font-family: 'Sora', sans-serif; font-size: 20px;
            font-weight: 800; color: var(--orange); display: block;
        }

        .accent-stat span { font-size: 12px; color: var(--muted); }

        /* Trust bar */
        .trust-bar {
            background: var(--nav-bg);
            border-top: 1px solid var(--cream-dark);
            border-bottom: 1px solid var(--cream-dark);
            padding: 18px 8%;
            display: flex; justify-content: center;
            gap: 48px; flex-wrap: wrap;
        }

        .trust-item {
            display: flex; align-items: center; gap: 10px;
            font-size: 14px; font-weight: 600; color: #374151;
        }

        .trust-item svg { color: var(--orange); flex-shrink: 0; }

        /* Sections */
        .section { max-width: 1100px; margin: 0 auto; padding: 80px 8%; }

        .section-label {
            text-align: center; font-size: 12px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.1em;
            color: var(--orange); margin-bottom: 14px;
        }

        .section-title {
            font-family: 'Sora', sans-serif; font-size: 36px;
            font-weight: 800; text-align: center; color: #020617; margin-bottom: 12px;
        }

        .section-sub { text-align: center; color: var(--muted); font-size: 16px; margin-bottom: 52px; }

        .steps-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }

        .step-card {
            background: white; border: 1px solid var(--cream-dark);
            border-radius: 22px; padding: 32px 28px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.04);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .step-card:hover { transform: translateY(-4px); box-shadow: 0 10px 28px rgba(0,0,0,0.08); }

        .step-num {
            width: 44px; height: 44px; background: var(--orange-mid);
            color: var(--orange); border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 800; margin-bottom: 20px;
        }

        .step-card h3 { font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 700; margin-bottom: 10px; }
        .step-card p { font-size: 14px; color: var(--muted); line-height: 1.6; }

        /* Listings */
        .listings-section {
            background: var(--nav-bg);
            border-top: 1px solid var(--cream-dark);
            border-bottom: 1px solid var(--cream-dark);
            padding: 80px 8%;
        }

        .listings-inner { max-width: 1100px; margin: 0 auto; }

        .listings-grid {
            display: grid; grid-template-columns: repeat(4, 1fr);
            gap: 20px; margin-bottom: 36px;
        }

        .listing-card {
            background: white; border: 1px solid var(--cream-dark);
            border-radius: 18px; overflow: hidden;
            box-shadow: 0 4px 14px rgba(0,0,0,0.05);
            filter: blur(1.5px); pointer-events: none; user-select: none;
        }

        .listing-card img { width: 100%; height: 160px; object-fit: cover; }

        .listing-card-body { padding: 14px; }

        .listing-cat {
            background: var(--orange-mid); color: var(--orange);
            font-size: 11px; font-weight: 700; padding: 3px 10px;
            border-radius: 999px; display: inline-block; margin-bottom: 8px;
            text-transform: uppercase; letter-spacing: 0.04em;
        }

        .listing-title { font-weight: 700; font-size: 14px; margin-bottom: 4px; color: var(--text); }
        .listing-price { color: var(--orange); font-weight: 800; font-size: 16px; }

        .listings-blur-wrap { position: relative; }

        .listings-blur-wrap::after {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(to bottom, transparent 20%, var(--nav-bg) 80%);
            pointer-events: none;
        }

        .listings-cta { text-align: center; padding-top: 10px; }
        .listings-cta p { color: var(--muted); font-size: 15px; margin-bottom: 20px; }

        /* Categories */
        .categories-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }

        .cat-card {
            background: white; border: 1px solid var(--cream-dark);
            border-radius: 18px; padding: 24px 20px; text-align: center;
            transition: border-color 0.15s, transform 0.15s, background 0.15s; cursor: default;
        }

        .cat-card:hover { border-color: var(--orange); background: var(--orange-lt); transform: translateY(-3px); }

        .cat-icon {
            width: 52px; height: 52px; background: var(--orange-mid);
            border-radius: 16px; display: flex; align-items: center;
            justify-content: center; margin: 0 auto 14px; color: var(--orange);
        }

        .cat-card h3 { font-size: 14px; font-weight: 700; margin-bottom: 4px; }
        .cat-card p { font-size: 12px; color: var(--muted); }

        /* CTA */
        .cta-banner { background: var(--orange); padding: 70px 8%; text-align: center; }

        .cta-banner h2 {
            font-family: 'Sora', sans-serif; font-size: 38px;
            font-weight: 800; color: white; margin-bottom: 14px;
        }

        .cta-banner p { color: rgba(255,255,255,0.85); font-size: 17px; margin-bottom: 32px; }

        .btn-white {
            background: white; color: var(--orange);
            padding: 14px 32px; border-radius: 14px;
            font-weight: 800; font-size: 15px; display: inline-block;
            transition: transform 0.15s, box-shadow 0.15s;
            box-shadow: 0 4px 14px rgba(0,0,0,0.15);
        }

        .btn-white:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.2); }

        /* Footer */
        footer {
            background: var(--nav-bg); border-top: 1px solid var(--cream-dark);
            padding: 28px 8%; display: flex; justify-content: space-between;
            align-items: center; font-size: 13px; color: var(--muted);
        }

        .footer-brand { display: flex; align-items: center; gap: 10px; }

        .footer-logo {
            width: 28px; height: 28px; background: var(--orange); color: white;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-family: 'Sora', sans-serif; font-weight: 800; font-size: 10px;
        }

        /* Responsive */
        @media (max-width: 900px) {
            nav { padding: 0 5%; height: 64px; }
            .nav-logo-text span { display: none; }
            .hero { grid-template-columns: 1fr; padding: 50px 6% 40px; gap: 36px; }
            .hero h1 { font-size: 36px; }
            .hero p { font-size: 15px; }
            .hero-visual { display: none; }
            .hero-stats { gap: 20px; flex-wrap: wrap; }
            .trust-bar { gap: 16px; padding: 18px 5%; justify-content: flex-start; }
            .trust-item { font-size: 13px; }
            .section { padding: 60px 6%; }
            .section-title { font-size: 28px; }
            .section-sub { font-size: 14px; }
            .steps-grid { grid-template-columns: 1fr; }
            .listings-section { padding: 60px 6%; }
            .listings-grid { grid-template-columns: 1fr 1fr; }
            .categories-grid { grid-template-columns: 1fr 1fr; }
            .cta-banner { padding: 50px 6%; }
            .cta-banner h2 { font-size: 28px; }
            .cta-banner p { font-size: 15px; }
            footer { flex-direction: column; gap: 12px; text-align: center; padding: 24px 6%; align-items: center; }
        }

        @media (max-width: 500px) {
            nav { padding: 0 4%; }
            .hero h1 { font-size: 30px; }
            .hero-btns { flex-direction: column; }
            .hero-stats { gap: 16px; }
            .stat-item strong { font-size: 22px; }
            .trust-bar { flex-direction: column; gap: 12px; }
            .listings-grid { grid-template-columns: 1fr; }
            .categories-grid { grid-template-columns: 1fr 1fr; }
            .nav-links .hide-mobile { display: none; }
            .cta-banner h2 { font-size: 24px; }
            .section-title { font-size: 24px; }
            footer { font-size: 12px; }
        }
    </style>
</head>
<body>

<nav>
    <div class="nav-logo">
        <div class="nav-logo-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white"
                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <path d="M16 10a4 4 0 0 1-8 0"/>
            </svg>
        </div>
        <div class="nav-logo-text">
            <strong>CampusLink</strong>
            <span>Built for student hustle</span>
        </div>
    </div>
    <div class="nav-links">
        <a href="#how-it-works" class="hide-mobile">How it works</a>
        <a href="#categories" class="hide-mobile">Categories</a>
        <a href="login.php">Sign in</a>
        <a href="register.php" class="nav-cta">Get started</a>
    </div>
</nav>

<div class="hero">
    <div class="hero-left">
        <div class="hero-eyebrow">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
            SA Student-only marketplace
        </div>
        <h1>The hustle<br>starts <span>on campus</span></h1>
        <p>Buy, sell and trade with verified South African students. From textbooks to tutoring, CampusLink is your campus community marketplace.</p>
        <div class="hero-btns">
            <a href="register.php" class="btn-primary">Join for free →</a>
            <a href="login.php" class="btn-secondary">Browse listings</a>
        </div>
        <div class="hero-stats">
            <div class="stat-item">
                <strong><?php echo $student_count > 0 ? $student_count . '+' : '—'; ?></strong>
                <span>Verified students</span>
            </div>
            <div class="stat-item">
                <strong><?php echo $listing_count > 0 ? $listing_count . '+' : '—'; ?></strong>
                <span>Active listings</span>
            </div>
            <div class="stat-item">
                <strong>29</strong>
                <span>SA institutions</span>
            </div>
        </div>
    </div>

    <div class="hero-visual">
        <div class="hero-card-stack">

            <!-- Card 1: ECO Textbook -->
            <div class="floating-card card-main">
                <div class="mini-product">
                    <div class="mini-product-img">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="mini-product-name">ECO 102 Textbook</div>
                        <div class="mini-product-price">R 180</div>
                        <div class="mini-product-seller">by Thabo N. · Wits</div>
                        <span class="badge-new">Just listed</span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Maths Tutoring -->
            <div class="floating-card card-secondary">
                <div class="mini-product">
                    <div class="mini-product-img">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="3" width="20" height="14" rx="2"/>
                            <path d="M8 21h8M12 17v4"/>
                        </svg>
                    </div>
                    <div>
                        <div class="mini-product-name">Maths Tutoring</div>
                        <div class="mini-product-price">R 120/hr</div>
                        <div class="mini-product-seller">by Lerato M. · UP</div>
                        <span class="badge-new">4.9 rated</span>
                    </div>
                </div>
            </div>

            <!-- Card 3: Earnings stat -->
            <div class="floating-card card-accent">
                <div class="accent-stat">
                    <strong>R 2 400</strong>
                    <span>earned this week</span>
                </div>
            </div>

            <!-- Card 4: Order Placed -->
            <div class="floating-card card-order">
                <div class="mini-product">
                    <div class="mini-product-img">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                            <line x1="3" y1="6" x2="21" y2="6"/>
                            <path d="M16 10a4 4 0 01-8 0"/>
                        </svg>
                    </div>
                    <div>
                        <div class="mini-product-name">Order Placed</div>
                        <div class="mini-product-price">Calculus Textbook</div>
                        <div class="mini-product-seller">by Sipho K. · Wits</div>
                        <span class="badge-new">Confirmed</span>
                    </div>
                </div>
            </div>

            <!-- Card 5: New Message -->
            <div class="floating-card card-message">
                <div class="mini-product">
                    <div class="mini-product-img">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="mini-product-name">New Message</div>
                        <div class="mini-product-price">Is this still available?</div>
                        <div class="mini-product-seller">from Aisha T. · TUT</div>
                        <span class="badge-new">Just now</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="trust-bar">
    <div class="trust-item">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        </svg>
        Student email verified
    </div>
    <div class="trust-item">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2"/>
            <path d="M7 11V7a5 5 0 0110 0v4"/>
        </svg>
        Passwords securely hashed
    </div>
    <div class="trust-item">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
        </svg>
        SA students only
    </div>
    <div class="trust-item">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z"/>
            <circle cx="12" cy="10" r="3"/>
        </svg>
        PAXI delivery available
    </div>
</div>

<div class="section" id="how-it-works">
    <div class="section-label">Simple process</div>
    <h2 class="section-title">How CampusLink works</h2>
    <p class="section-sub">Three steps to start buying or selling on campus.</p>
    <div class="steps-grid">
        <div class="step-card">
            <div class="step-num">1</div>
            <h3>Register with your student email</h3>
            <p>Sign up using your official institutional email address. Only verified South African students can join, no Gmail, no strangers.</p>
        </div>
        <div class="step-card">
            <div class="step-num">2</div>
            <h3>List or browse in minutes</h3>
            <p>Post your textbook, gadget, or service in under 3 minutes. Or browse listings from verified students at your institution and across the country.</p>
        </div>
        <div class="step-card">
            <div class="step-num">3</div>
            <h3>Connect, pay and deliver safely</h3>
            <p>Message the seller directly, choose your delivery method between campus pickup, PAXI, or courier, and pay via EFT, SnapScan, or cash on collection.</p>
        </div>
    </div>
</div>

<?php if (!empty($preview_items)): ?>
<div class="listings-section">
    <div class="listings-inner">
        <div class="section-label" style="text-align:center;">Live on CampusLink</div>
        <h2 class="section-title">Recent listings</h2>
        <p class="section-sub">Sign in to see full details, message sellers, and place orders.</p>
        <div class="listings-blur-wrap">
            <div class="listings-grid">
                <?php foreach ($preview_items as $item): ?>
                <div class="listing-card">
                    <?php if ($item['image']): ?>
                        <?php $first_image = explode(',', $item['image'])[0]; ?>
                        <img src="uploads/<?php echo rawurlencode(trim($first_image)); ?>"
                             alt="<?php echo htmlspecialchars($item['title']); ?>">
                    <?php else: ?>
                        <div style="height:160px;background:var(--orange-mid);display:flex;align-items:center;justify-content:center;">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--orange)"
                                 stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2"/>
                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                <polyline points="21 15 16 10 5 21"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                    <div class="listing-card-body">
                        <div class="listing-cat"><?php echo htmlspecialchars($item['category_name']); ?></div>
                        <div class="listing-title"><?php echo htmlspecialchars($item['title']); ?></div>
                        <div class="listing-price">R <?php echo number_format($item['price'], 2); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="listings-cta">
            <p>Sign in to see all listings, contact sellers, and start trading.</p>
            <a href="register.php" class="btn-primary">Join to browse →</a>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="section" id="categories">
    <div class="section-label">What's on the platform</div>
    <h2 class="section-title">Something for every student</h2>
    <p class="section-sub">From study materials to side hustles — CampusLink covers it all.</p>
    <div class="categories-grid">
        <div class="cat-card">
            <div class="cat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                </svg>
            </div>
            <h3>Textbooks</h3>
            <p>Buy and sell used academic books</p>
        </div>
        <div class="cat-card">
            <div class="cat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="3" width="20" height="14" rx="2"/>
                    <path d="M8 21h8M12 17v4"/>
                </svg>
            </div>
            <h3>Electronics</h3>
            <p>Laptops, phones, gadgets and more</p>
        </div>
        <div class="cat-card">
            <div class="cat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 8v4l3 3"/>
                </svg>
            </div>
            <h3>Tutoring</h3>
            <p>Get help or offer your expertise</p>
        </div>
        <div class="cat-card">
            <div class="cat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>
                </svg>
            </div>
            <h3>Services</h3>
            <p>Hairstyling, food, design and more</p>
        </div>
        <div class="cat-card">
            <div class="cat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <path d="M16 10a4 4 0 01-8 0"/>
                </svg>
            </div>
            <h3>Clothing</h3>
            <p>Affordable fashion and uniforms</p>
        </div>
        <div class="cat-card">
            <div class="cat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
            </div>
            <h3>Furniture</h3>
            <p>Digs essentials at student prices</p>
        </div>
        <div class="cat-card">
            <div class="cat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
            </div>
            <h3>Verified only</h3>
            <p>Every seller is a real SA student</p>
        </div>
        <div class="cat-card">
            <div class="cat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="3" width="15" height="13" rx="1"/>
                    <path d="M16 8h4l3 3v5h-7V8z"/>
                    <circle cx="5.5" cy="18.5" r="2.5"/>
                    <circle cx="18.5" cy="18.5" r="2.5"/>
                </svg>
            </div>
            <h3>PAXI delivery</h3>
            <p>Nationwide delivery via PEP stores</p>
        </div>
    </div>
</div>

<div class="cta-banner">
    <h2>Ready to start your hustle?</h2>
    <p>Join thousands of South African students already trading on CampusLink.</p>
    <a href="register.php" class="btn-white">Create your free account →</a>
</div>

<footer>
    <div class="footer-brand">
        <div class="footer-logo">CL</div>
        <strong>CampusLink</strong>
        <span>Built for student hustle</span>
    </div>
    <div>
        © 2026 CampusLink. A safer marketplace for South African students.
        &nbsp;·&nbsp; <a href="about.php" style="color:#6b7280;">About</a>
        &nbsp;·&nbsp; <a href="contact.php" style="color:#6b7280;">Contact</a>
        &nbsp;·&nbsp; <a href="terms.php" style="color:#6b7280;">Terms</a>
    </div>
</footer>

</body>
</html>