<?php
/** Newsletter strip, phone tab bar and footer. The footer is Appearance > Widgets (inc/widgets.php) and Menus; edited by hand since then. */
defined( 'ABSPATH' ) || exit;
?>
</main>
<?php if ( hub_page( 'layout' ) !== 'bare' && hub_page( 'nl' ) !== 'off' ) : ?>
<?php hub_newsletter_strip(); // Hub settings > Newsletter (inc/newsletter.php) ?>

<?php endif; ?>
<?php if ( hub_page( 'layout' ) !== 'bare' ) : ?>
<nav class="tabbar" aria-label="Hub quick links">
  <a class="tabbar__item" href="<?php echo esc_url( hub_url( '' ) ); ?>"<?php hub_tab_attr( 'home' ); ?>><svg class="ho-icon"><use href="#i-home"/></svg><span>Hub</span></a>
  <a class="tabbar__item" href="<?php echo esc_url( hub_url( 'search/' ) ); ?>"<?php hub_tab_attr( 'search' ); ?>><svg class="ho-icon"><use href="#i-search"/></svg><span>Search</span></a>
  <a class="tabbar__item" href="<?php echo esc_url( hub_url( 'offers/' ) ); ?>"<?php hub_tab_attr( 'offers' ); ?>><svg class="ho-icon"><use href="#i-tag"/></svg><span>Offers</span></a>
  <a class="tabbar__item" href="<?php echo esc_url( hub_url( 'events/' ) ); ?>"<?php hub_tab_attr( 'events' ); ?>><svg class="ho-icon"><use href="#i-calendar"/></svg><span>What&rsquo;s On</span></a>
  <a class="tabbar__item hub-bar__out" href="<?php echo esc_url( hub_url( 'sign-in/' ) ); ?>"><svg class="ho-icon"><use href="#i-user"/></svg><span>Sign in</span></a>
  <a class="tabbar__item hub-bar__in" href="<?php echo esc_url( hub_url( 'account/' ) ); ?>"<?php hub_tab_attr( 'account' ); ?>><svg class="ho-icon"><use href="#i-user"/></svg><span>Account</span></a>
</nav>

<?php endif; ?>
<footer class="ho-footer">
        <div class="ho-container ho-footer__grid">
            <div class="ho-footer__brand">
                <a class="ho-footer__logo ho-footer__logo--hub" href="<?php echo esc_url( hub_url( '' ) ); ?>">
                    <img src="<?php echo esc_url( hub_asset( 'img/hub-logo-white.svg' ) ); ?>" width="146" height="100" alt="Harrow Online Hub" loading="lazy">
                </a>
                <?php hub_footer_area( 'footer-brand' ); // Appearance > Widgets (inc/widgets.php) ?>
                <?php echo hub_social_links( 'light' ); // Appearance > Menus: Social links ?>            </div>

            <?php hub_footer_area( 'footer-columns' ); ?>
            <?php hub_footer_area( 'footer-newsletter' ); ?>
        </div>

        <div class="ho-container">
            <?php hub_footer_area( 'footer-topics' ); ?>        </div>

        <div class="ho-footer__bottom">
            <div class="ho-container ho-footer__bottom-inner">

                <?php hub_footer_area( 'footer-badges' ); ?>
                <?php hub_footer_area( 'footer-legal' ); ?>
            </div>
        </div>
    </footer>
<?php wp_footer(); ?>
</body>
</html>
