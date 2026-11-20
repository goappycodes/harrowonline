<?php
/** Head, site header and main menu. The menus are Appearance > Menus (inc/menus.php); edited by hand since then. */
defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?><?php hub_html_attrs(); ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Merriweather:wght@700;900&family=Open+Sans:ital,wght@0,400;0,600;0,700;1,400&display=swap">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="ho-skip" href="#main">Skip to content</a>
<header class="ho-header" id="ho-header">
        <div class="ho-container ho-header__inner">
            <button class="ho-header__icon-btn ho-header__burger" type="button" aria-controls="ho-drawer" aria-expanded="false" aria-label="Open menu" data-ho-drawer-open>
                <svg class="ho-icon ho-icon--menu" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>            </button>

                        <a class="ho-header__logo ho-header__logo--hub" href="<?php echo esc_url( hub_url( '' ) ); ?>" rel="home">
                                <img src="<?php echo esc_url( hub_asset( 'img/hub-logo.svg' ) ); ?>" width="146" height="100" alt="Harrow Online Hub" fetchpriority="high">
                            </a>

            <nav class="ho-nav" aria-label="Main">
                <?php hub_main_menu( 'header' ); // Appearance > Menus: Main menu (inc/menus.php) ?>            </nav>

            <div class="ho-header__actions">
                <a class="ho-btn ho-btn--primary ho-header__cta" href="https://harrowonline.org/newsletter/">
                    <svg class="ho-icon ho-icon--mail" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><rect x="3" y="5" width="18" height="14" rx="2.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="m4 7 8 6 8-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg><span>Newsletter</span>
                </a>
                <a class="ho-header__icon-btn ho-header__account hub-bar__out" href="<?php echo esc_url( hub_url( 'account/' ) ); ?>" aria-label="My account"><svg class="ho-icon ho-icon--user" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="4" fill="none" stroke="currentColor" stroke-width="2"/><path d="M4 20.5c1.2-3.8 4.3-5.7 8-5.7s6.8 1.9 8 5.7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></a><a class="ho-header__icon-btn ho-header__account hub-bar__in" href="<?php echo esc_url( hub_url( 'account/' ) ); ?>" aria-label="My account"><svg class="ho-icon ho-icon--user" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="4" fill="none" stroke="currentColor" stroke-width="2"/><path d="M4 20.5c1.2-3.8 4.3-5.7 8-5.7s6.8 1.9 8 5.7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></a>
                <button class="ho-header__icon-btn ho-header__search-btn" type="button" aria-controls="ho-search" aria-expanded="false" aria-label="Search" data-ho-search-toggle>
                    <svg class="ho-icon ho-icon--search" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2.2"/><path d="m20 20-4.2-4.2" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>                </button>
            </div>
        </div>

        <div class="ho-search" id="ho-search" hidden>
            <div class="ho-container">
                <form class="ho-search__form ho-search__form--hub" role="search" method="get" action="<?php echo esc_url( hub_url( 'search/' ) ); ?>">
                    <label class="ho-sr" for="ho-search-input">Search the Hub</label>
                    <input class="ho-search__input" id="ho-search-input" type="search" name="q" value="" placeholder="Search businesses, events, classes and offers&hellip;" autocomplete="off">
                    <button class="ho-btn ho-btn--primary" type="submit">Search</button>
                    <a class="ho-search__alt" href="https://harrowonline.org/?s=" data-news-search>Search news instead</a>
                </form>
            </div>
        </div>
    </header>

    <div class="ho-drawer" id="ho-drawer" hidden>
        <div class="ho-drawer__backdrop" data-ho-drawer-close></div>
        <div class="ho-drawer__panel" role="dialog" aria-modal="true" aria-label="Menu">
            <div class="ho-drawer__head">
                <a class="ho-drawer__logo ho-drawer__logo--hub" href="<?php echo esc_url( hub_url( '' ) ); ?>">
                    <img src="<?php echo esc_url( hub_asset( 'img/hub-logo.svg' ) ); ?>" width="146" height="100" alt="Harrow Online Hub" loading="lazy">
                </a>
                <button class="ho-header__icon-btn" type="button" aria-label="Close menu" data-ho-drawer-close>
                    <svg class="ho-icon ho-icon--close" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>                </button>
            </div>
            
            <nav class="ho-drawer__nav" aria-label="Mobile">
                <?php hub_main_menu( 'drawer' ); ?>            </nav>
            <a class="ho-btn ho-btn--primary ho-btn--block" href="https://harrowonline.org/newsletter/"><svg class="ho-icon ho-icon--mail" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><rect x="3" y="5" width="18" height="14" rx="2.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="m4 7 8 6 8-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg><span>Get the newsletter</span></a>
            <p class="ho-drawer__label">Follow Harrow Online</p>
            <?php echo hub_social_links( 'dark' ); // Appearance > Menus: Social links ?>        </div>
    </div>

<main id="main">
