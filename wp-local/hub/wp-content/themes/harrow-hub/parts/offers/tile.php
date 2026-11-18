<?php
/**
 * Value-first offer tile (homepage, "More Local Offers" on an offer page): big value and the business.
 *
 * $args: offer (offers shape)
 */
defined( 'ABSPATH' ) || exit;

$o = $args['offer'];
?>
<a class="offer-tile" href="<?php echo esc_url( $o['url'] ); ?>">
        <?php if ( $o['subscriber_only'] ) : ?><span class="offer-tile__lock" title="Subscribers only"><?php echo hub_icon( 'lock', 'ho-icon ho-icon--sm' ); ?><span class="offer-tile__lock-text">Subscribers</span></span><?php endif; ?>

        <span class="offer-tile__biz"><?php echo esc_html( $o['business']['name'] ); ?>, <?php echo esc_html( $o['business']['area']['name'] ); ?></span>
        <strong class="offer-tile__value"><?php echo esc_html( $o['value'] ); ?></strong>
        <span class="offer-tile__title"><?php echo esc_html( $o['title'] ); ?></span>
      </a>
