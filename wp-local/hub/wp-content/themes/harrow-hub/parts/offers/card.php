<?php
/**
 * Offer card for lists (Local Offers page, homepage): value, offer, business, end date. The code, unlock form and
 * terms are on the offer's own page. The data-* attributes drive the hub.js filters.
 *
 * $args: offer (offers shape)
 */
defined( 'ABSPATH' ) || exit;

$o = $args['offer'];
$b = $o['business'];
?>
<a class="offer-card<?php echo $o['featured'] ? ' offer-card--featured' : ''; ?>" href="<?php echo esc_url( $o['url'] ); ?>" data-cat="<?php echo esc_attr( $b['category']['slug'] ); ?>" data-area="<?php echo esc_attr( $b['area']['slug'] ); ?>" data-sub="<?php echo $o['subscriber_only'] ? 1 : 0; ?>">
        <span class="offer-card__top"><span class="biz-card__cat"><?php echo esc_html( $b['category']['name'] ); ?></span><?php
		if ( $o['featured'] ) {
			echo '<span class="badge badge--featured">' . hub_icon( 'star' ) . 'Featured</span>';
		}
		if ( $o['subscriber_only'] ) {
			echo hub_sub_lock( false );
		}
		if ( $o['ends']['ending_soon'] ) {
			echo '<span class="badge badge--soon">' . hub_icon( 'clock' ) . 'Ends soon</span>';
		}
		?></span>
        <strong class="offer-card__value"><?php echo esc_html( $o['value'] ); ?></strong>
        <span class="offer-card__title"><?php echo esc_html( $o['title'] ); ?></span>
        <span class="offer-card__biz"><?php echo esc_html( $b['name'] ); ?> &middot; <?php echo esc_html( $b['area']['name'] ); ?></span>
        <span class="offer-card__foot"><span><?php echo hub_icon( 'calendar', 'ho-icon ho-icon--sm' ) . esc_html( $o['ends']['label'] ); ?></span><span class="offer-card__go">View offer <?php echo hub_icon( 'arrow', 'ho-icon ho-icon--sm' ); ?></span></span>
      </a>
