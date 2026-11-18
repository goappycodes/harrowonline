<?php
/**
 * Directory row: category and area pages, "More ... in ..." on profiles.
 * Claimed businesses show their photo or logo, tagline, today's hours and counts; imported ones a monogram and a
 * Call button.
 *
 * $args: business (businesses shape, with counts { offers, upcoming })
 */
defined( 'ABSPATH' ) || exit;

$b    = $args['business'];
$link = '<a href="' . esc_url( $b['url'] ) . '">' . hub_view_name_with_tick( $b ) . '</a>';
$tel  = $b['phones'] ? $b['phones'][0]['tel'] : '';

if ( ! $b['claimed'] ) :
	$addr = $b['address']['street'] ? esc_html( $b['address']['street'] ) . ', ' : '';
	?>
<article class="biz-row biz-row--mono biz-row--unclaimed">
        <span class="mono mono--soft" aria-hidden="true"><?php echo esc_html( hub_initials( $b['name'] ) ); ?></span>
        <div class="biz-row__main">
          <span class="biz-card__cat"><?php echo esc_html( $b['category']['name'] ); ?><?php echo $b['subcategory'] ? ' · <span class="muted">' . esc_html( $b['subcategory'] ) . '</span>' : ''; ?></span>
          <h3 class="biz-row__title"><?php echo $link; // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
          <p class="card__meta"><span><?php echo hub_icon( 'pin' ) . $addr . esc_html( $b['address']['postcode'] ); ?></span><span><?php echo esc_html( $b['area']['name'] ); ?></span></p>
        </div>
        <div class="biz-row__side"><?php if ( $tel ) : ?><a class="ho-btn ho-btn--sm ho-btn--outline" href="tel:<?php echo esc_attr( $tel ); ?>"><?php echo hub_icon( 'phone' ); ?>Call</a><?php endif; ?></div>
      </article>
<?php
	return;
endif;

$media  = $b['logo'] || $b['images'];
$cls    = 'biz-row' . ( $b['featured'] ? ' biz-row--featured' : '' ) . ( $media ? ' biz-row--media' : '' );
$counts = $b['counts'];
?>
<article class="<?php echo esc_attr( $cls ); ?>">
        <?php echo $b['logo'] ? hub_view_logo( $b ) : '<div class="' . esc_attr( hub_view_ph_cls( $b ) ) . '">' . hub_view_photo( $b ) . '</div>'; ?>

        <div class="biz-row__main">
          <span class="biz-card__cat"><?php echo esc_html( $b['category']['name'] ); ?> · <span class="muted"><?php echo esc_html( $b['subcategory'] ); ?></span></span>
          <h3 class="biz-row__title"><?php echo $link; // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
          <p class="card__meta"><span><?php echo hub_icon( 'pin' ) . esc_html( hub_view_address( $b ) ); ?></span><span><?php echo esc_html( $b['area']['name'] ); ?></span></p>
          <p class="card__text"><?php echo esc_html( $b['tagline'] ); ?></p>
          <p class="card__meta"><?php
			echo hub_view_open_today( $b );
			if ( $counts['offers'] ) {
				echo '<span>' . hub_icon( 'tag' ) . (int) $counts['offers'] . ' offer' . ( 1 !== $counts['offers'] ? 's' : '' ) . '</span>';
			}
			if ( $counts['upcoming'] ) {
				echo '<span>' . hub_icon( 'calendar' ) . (int) $counts['upcoming'] . ' upcoming</span>';
			}
			?></p>
        </div>
        <div class="biz-row__side"><div class="cluster"><?php echo $b['featured'] ? '<span class="badge badge--featured">' . hub_icon( 'star' ) . 'Featured</span>' : ''; ?></div><a class="ho-btn ho-btn--sm ho-btn--outline" href="tel:<?php echo esc_attr( $tel ); ?>"><?php echo hub_icon( 'phone' ); ?>Call</a></div>
      </article>
