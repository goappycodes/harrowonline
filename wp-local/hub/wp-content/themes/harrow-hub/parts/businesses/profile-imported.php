<?php
/**
 * An imported (unclaimed) business's page: the open-data details, related listings and the claim box.
 *
 * $args: business (profile shape), related (businesses with counts)
 */
defined( 'ABSPATH' ) || exit;

$b     = $args['business'];
$name  = $b['name'];
$catn  = $b['category']['name'];
$arn   = $b['area']['name'];
$maps  = hub_view_maps_url( $name . ' ' . $b['address']['postcode'] );
$claim = hub_url( 'claim/?listing=' . $b['slug'] );
$cat_u = hub_url( 'category/' . $b['category']['slug'] . '/' );
$rel   = implode( '', array_map( function ( $r ) { return hub_part( 'businesses/row', array( 'business' => $r ) ); }, $args['related'] ) );
?>
<nav class="crumbs ho-container" aria-label="Breadcrumb"><ol><li><a href="<?php echo esc_url( hub_url() ); ?>">Hub</a></li><li><a href="<?php echo esc_url( $cat_u ); ?>"><?php echo esc_html( $catn ); ?></a></li><li><a href="<?php echo esc_url( hub_url( 'area/' . $b['area']['slug'] . '/' ) ); ?>"><?php echo esc_html( $arn ); ?></a></li><li><span aria-current="page"><?php echo esc_html( $name ); ?></span></li></ol></nav>

<section class="profile-head ho-container">
  <div class="profile-head__row">
    <span class="mono mono--lg mono--soft" aria-hidden="true"><?php echo esc_html( hub_initials( $name ) ); ?></span>
    <div class="profile-head__main">
      <h1 class="profile-head__title"><?php echo hub_view_name_with_tick( $b, false ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h1>
      <p class="profile-head__meta">
        <a href="<?php echo esc_url( $cat_u ); ?>"><?php echo hub_icon( 'tag' ) . esc_html( $catn ); ?></a>
        <?php if ( $b['subcategory'] ) : ?><span><?php echo esc_html( $b['subcategory'] ); ?></span><?php endif; ?>

        <a href="<?php echo esc_url( hub_url( 'area/' . $b['area']['slug'] . '/' ) ); ?>"><?php echo hub_icon( 'pin' ) . esc_html( $arn ); ?></a>
      </p>
      <div class="profile-head__actions">
        <?php if ( $b['phones'] ) : ?><a class="ho-btn ho-btn--primary" href="tel:<?php echo esc_attr( $b['phones'][0]['tel'] ); ?>"><?php echo hub_icon( 'phone' ); ?>Call</a><?php endif; ?>

        <?php if ( $b['website'] ) : ?><a class="ho-btn ho-btn--outline" href="<?php echo esc_url( $b['website'] ); ?>" target="_blank" rel="noopener nofollow"><?php echo hub_icon( 'globe' ); ?>Website</a><?php endif; ?>

        <a class="ho-btn ho-btn--outline" href="<?php echo esc_url( $maps ); ?>" target="_blank" rel="noopener"><?php echo hub_icon( 'map' ); ?>Directions</a>
      </div>
    </div>
  </div>
</section>

<div class="ho-container layout-aside">
  <div>
    <section class="profile-section">
      <h2>About <?php echo esc_html( $name ); ?></h2>
      <div class="limited-note"><?php echo hub_icon( 'info' ); ?><p class="mb-0">This business hasn&rsquo;t added a description, opening hours or photos yet. The details below come from open map data and may be out of date. <a href="<?php echo esc_url( $claim ); ?>">Is this your business? Claim it free</a> to update it.</p></div>
    </section>

    <section class="profile-section">
      <h2>Contact and location</h2>
      <div class="grid grid-2">
        <ul class="info-list"><?php
			echo '<li>' . hub_icon( 'pin' ) . '<div><address>' . esc_html( implode( ', ', array_filter( array( $b['address']['street'], $arn, $b['address']['postcode'] ) ) ) ) . '</address><a class="small" href="' . esc_url( $maps ) . '" target="_blank" rel="noopener">Get directions</a></div></li>';
			foreach ( $b['phones'] as $ph ) {
				echo '<li>' . hub_icon( 'phone' ) . '<a href="tel:' . esc_attr( $ph['tel'] ) . '">' . esc_html( $ph['display'] ) . '</a></li>';
			}
			if ( $b['website'] ) {
				echo '<li>' . hub_icon( 'globe' ) . '<a href="' . esc_url( $b['website'] ) . '" target="_blank" rel="noopener nofollow">' . esc_html( hub_view_short_url( $b['website'] ) ) . '</a></li>';
			}
			if ( $b['email'] ) {
				echo '<li>' . hub_icon( 'mail' ) . '<a href="' . esc_url( 'mailto:' . $b['email'] ) . '">' . esc_html( $b['email'] ) . '</a></li>';
			}
			?></ul>
        <a class="ph ph--map" href="<?php echo esc_url( $maps ); ?>" target="_blank" rel="noopener" aria-label="Open <?php echo esc_attr( $name ); ?> in Google Maps"><?php echo hub_icon( 'pin' ); ?></a>
      </div>
    </section>

    <section class="profile-section">
      <h2>Opening hours</h2>
      <p class="muted mb-0">Not added yet. Owners can add opening hours after claiming this listing.</p>
    </section>

    <section class="profile-section">
      <div class="sec-head"><div><h2 class="mb-0">More <?php echo esc_html( strtolower( $catn ) ); ?> in <?php echo esc_html( $arn ); ?></h2></div><a class="link-arrow" href="<?php echo esc_url( $cat_u ); ?>">All <?php echo esc_html( strtolower( $catn ) ); ?> in Harrow <?php echo hub_icon( 'arrow' ); ?></a></div>
      <?php echo '' !== $rel ? $rel : '<p class="muted">No other listings in this area yet.</p>'; // phpcs:ignore WordPress.Security.EscapeOutput ?>

    </section>

    <p class="mt-4"><a class="report-link" href="<?php echo esc_url( hub_url( 'report/?listing=' . $b['slug'] ) ); ?>"><?php echo hub_icon( 'flag' ); ?>Report a mistake or ask for this listing to be removed</a></p>
  </div>

  <aside class="sticky">
    <div class="claim-box">
      <h2>Is this your business?</h2>
      <p>Claim this listing for free to add photos, opening hours and a description, and to get a verified badge.</p>
      <ul class="ticks"><li>Free forever on the Basic plan</li><li>Ranked above unclaimed listings</li><li>Offers, events and classes on Featured or Local Partner</li></ul>
      <a class="ho-btn ho-btn--primary ho-btn--block" href="<?php echo esc_url( $claim ); ?>">Claim this business</a>
      <p class="xsmall mt-3 mb-0 muted">Takes about 2 minutes. We check every claim before it goes live.</p>
    </div>
    <div class="panel mt-4">
      <h3 class="panel__title">Get Harrow&rsquo;s best offers</h3>
      <p class="small muted">Local Offers and what&rsquo;s on, every morning in the Harrow Online Newsletter.</p>
      <a class="link-arrow" href="https://harrowonline.org/newsletter/">Sign up free <?php echo hub_icon( 'arrow' ); ?></a>
    </div>
  </aside>
</div>
