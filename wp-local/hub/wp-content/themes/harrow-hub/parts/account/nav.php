<?php
/**
 * The account menu on every account page (page-templates/account*.php, through hub_view_account_nav()): who is
 * signed in, "+ Add", the pages, and Sign out. Offers, Events and Classes show a red count of the ones waiting for
 * the account to change them (submissions module).
 *
 * @var array $args { current: 'overview' | 'listing' | 'offers' | 'events' | 'classes' | 'newsletter' | 'billing' | 'settings',
 *                    aria: aria-current value, 'page' (the page itself) or 'true' (a page under it, like an edit page) }
 */
defined( 'ABSPATH' ) || exit;
$hub_counts = hub_submissions_needs_changes();
$hub_items  = array(
	'overview'   => array( 'account/', 'home', 'Overview', '' ),
	'listing'    => array( 'account/listings/edit/', 'store', 'My listing', '' ),
	'offers'     => array( 'account/offers/', 'tag', 'Offers', 'offer' ),
	'events'     => array( 'account/events/', 'calendar', 'Events', 'event' ),
	'classes'    => array( 'account/classes/', 'repeat', 'Classes', 'class' ),
	'newsletter' => array( 'account/newsletter/', 'megaphone', 'Newsletter and promotions', '' ),
	'billing'    => array( 'account/billing/', 'card', 'Plan and billing', '' ),
	'settings'   => array( 'account/settings/', 'settings', 'Settings', '' ),
);
$hub_plural = array( 'offer' => 'offers', 'event' => 'events', 'class' => 'classes' );
?>
<aside class="dash-nav" aria-label="Account">
    <div class="dash-nav__who">
      <?php hub_view_account_who(); ?>
    </div>
    <div class="dash-add">
      <!-- "+ Add" on every account page (client plan, Nov 2026): an event, a class or an offer, one tap from anywhere. -->
      <details class="add-menu" data-add-menu>
        <summary class="ho-btn ho-btn--primary ho-btn--sm"><svg class="ho-icon"><use href="#i-plus"/></svg>Add</summary>
        <div class="add-menu__pop">
          <p class="add-menu__label">Add to the Hub</p>
          <a class="add-menu__item" href="<?php echo esc_url( hub_url( 'account/events/edit/' ) ); ?>"><span class="add-menu__icon"><svg class="ho-icon"><use href="#i-calendar"/></svg></span><span><strong>Event</strong><small>A one-off date, on What&rsquo;s On</small></span></a>
          <a class="add-menu__item" href="<?php echo esc_url( hub_url( 'account/classes/edit/' ) ); ?>"><span class="add-menu__icon"><svg class="ho-icon"><use href="#i-repeat"/></svg></span><span><strong>Class</strong><small>Runs every week, on Classes</small></span></a>
          <a class="add-menu__item" href="<?php echo esc_url( hub_url( 'account/offers/edit/' ) ); ?>"><span class="add-menu__icon"><svg class="ho-icon"><use href="#i-tag"/></svg></span><span><strong>Offer</strong><small>A deal for Harrow Online readers</small></span></a>
        </div>
      </details>
    </div>

    <ul>
<?php
foreach ( $hub_items as $hub_key => $hub_i ) :
	$hub_n = '' !== $hub_i[3] ? (int) $hub_counts[ $hub_i[3] ] : 0;
	?>
      <li><a href="<?php echo esc_url( hub_url( $hub_i[0] ) ); ?>"<?php echo $hub_key === $args['current'] ? ' aria-current="' . esc_attr( $args['aria'] ) . '"' : ''; ?>><svg class="ho-icon"><use href="#i-<?php echo esc_attr( $hub_i[1] ); ?>"/></svg><?php echo esc_html( $hub_i[2] ); ?><?php if ( $hub_n ) : ?><span class="num"><?php echo (int) $hub_n; ?><span class="ho-sr"> <?php echo esc_html( 1 === $hub_n ? $hub_i[3] . ' needs changes' : $hub_plural[ $hub_i[3] ] . ' need changes' ); ?></span></span><?php endif; ?></a></li>
<?php endforeach; ?>
    </ul>
    <hr>
    <ul>
      <li><a href="<?php echo esc_url( hub_view_sign_out_url() ); ?>"><svg class="ho-icon"><use href="#i-logout"/></svg>Sign out</a></li>
    </ul>
  </aside>
