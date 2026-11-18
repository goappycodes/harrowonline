<?php
/**
 * Full offer card (business profile): badges, offer, description, end date, code or unlock form.
 *
 * $args: offer (offers shape), show_business (bool, default true), heading ('h3')
 */
defined( 'ABSPATH' ) || exit;

$o       = $args['offer'];
$b       = $o['business'];
$heading = isset( $args['heading'] ) && in_array( $args['heading'], array( 'h2', 'h3', 'h4' ), true ) ? $args['heading'] : 'h3';
$soon    = $o['ends']['ending_soon'];
$sub     = $o['subscriber_only'];
$cls     = 'offer' . ( $o['featured'] ? ' offer--featured' : '' ) . ( $sub ? ' offer--locked' : '' );
?>
<article class="<?php echo esc_attr( $cls ); ?>" data-cat="<?php echo esc_attr( $b['category']['slug'] ); ?>" data-area="<?php echo esc_attr( $b['area']['slug'] ); ?>" data-sub="<?php echo $sub ? 1 : 0; ?>">
        <div class="offer__body">
          <div class="cluster"><?php
			if ( $o['featured'] ) {
				echo '<span class="badge badge--featured">' . hub_icon( 'star' ) . 'Featured</span>';
			}
			if ( $sub ) {
				echo hub_sub_lock();
			}
			if ( $soon ) {
				echo '<span class="badge badge--soon">' . hub_icon( 'clock' ) . 'Ends soon</span>';
			}
			?><span class="biz-card__cat"><?php echo esc_html( $b['category']['name'] ); ?></span></div>
          <<?php echo $heading; ?> class="offer__title"><a href="<?php echo esc_url( $o['url'] ); ?>"><?php echo esc_html( $o['title'] ); ?></a></<?php echo $heading; ?>>
          <?php if ( ! isset( $args['show_business'] ) || $args['show_business'] ) : ?><a class="offer__biz" href="<?php echo esc_url( $b['url'] ); ?>"><?php echo esc_html( $b['name'] ); ?> &middot; <?php echo esc_html( $b['area']['name'] ); ?></a><?php endif; ?>

          <p class="offer__text"><?php echo esc_html( $o['description'] ); ?></p>
          <div class="offer__foot"><span class="offer__ends<?php echo $soon ? ' offer__ends--soon' : ''; ?>"><?php echo hub_icon( 'calendar', 'ho-icon ho-icon--sm' ); ?> <?php echo esc_html( $o['ends']['label'] ); ?></span><?php
			if ( $sub && $o['has_code'] ) {
				echo '<span class="offer__code offer__code--pending" data-sub-code data-offer="' . esc_attr( $o['slug'] ) . '">Code shown after you confirm your email</span>';
			} elseif ( $o['code'] ) {
				echo '<span class="offer__code">' . esc_html( $o['code'] ) . '</span>';
			} else {
				echo '<span>No code needed</span>';
			}
			?></div>
          <a class="offer__more" href="<?php echo esc_url( $o['url'] ); ?>">Terms and how to redeem <?php echo hub_icon( 'arrow', 'ho-icon ho-icon--sm' ); ?></a><?php
			if ( $sub ) {
				get_template_part( 'parts/offers/unlock-box', null, array( 'offer' => $o ) );
			}
			?>

        </div>
      </article>
