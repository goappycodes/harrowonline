<?php
/**
 * An account's offers, events or classes (page-templates/account-offers.php, account-events.php, account-classes.php):
 * the tabs and the table, or what to do when there's nothing yet. Every row and count is the account's own.
 *
 * @var array $args { list: hub_submissions_list() }
 */
defined( 'ABSPATH' ) || exit;
$hub_l     = $args['list'];
$hub_kind  = $hub_l['kind'];
$hub_names = array(
	'offer' => array( 'offers', 'Offer', 'tag', 'Add an offer', 'Add a deal for Harrow Online readers. We check every offer before it goes live, usually within 2 working days.' ),
	'event' => array( 'events', 'Event', 'calendar', 'Add an event', 'Add a one-off date, like a workshop or an open day. We check every event before it goes on What&rsquo;s On.' ),
	'class' => array( 'classes', 'Class', 'repeat', 'Add a class', 'Add something you run every week. We check every class before it goes on Classes.' ),
);
$hub_n    = $hub_names[ $hub_kind ];
$hub_add  = hub_url( HUB_SUBMISSIONS_KINDS[ $hub_kind ][2] . 'edit/' );
$hub_why  = '' !== $hub_l['message'] ? wp_kses( $hub_l['message'], array() ) : $hub_n[4];   // may hold &rsquo;
// What it can do next when it can't add one: [ label, url ] buttons
$hub_next = array(
	'ready'       => array( array( $hub_n[3], $hub_add ) ),
	'upgrade'     => array( array( 'See plans', hub_url( 'pricing/' ) ) ),
	'unconfirmed' => array( array( 'Go to my account', hub_url( 'account/' ) ) ),
	'not_allowed' => 'organiser' === $hub_l['account_type']
		? array()   // offers are for businesses
		: array( array( 'Find your business', hub_url( 'search/' ) ), array( 'Add your business', hub_url( 'add-business/' ) ) ),
);
$hub_next = isset( $hub_next[ $hub_l['state'] ] ) ? $hub_next[ $hub_l['state'] ] : array();
?>
<?php if ( $hub_l['total'] ) : ?>
<?php if ( 'ready' !== $hub_l['state'] ) : ?>
    <div class="notice notice--warn mb-5" role="status"><svg class="ho-icon"><use href="#i-alert"/></svg><div><p class="mb-0"><?php echo $hub_why; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_kses above ?></p></div></div>
<?php endif; ?>
    <nav class="tabs" aria-label="Filter <?php echo esc_attr( $hub_n[0] ); ?>">
<?php foreach ( $hub_l['tabs'] as $hub_t ) : ?>
      <a href="<?php echo esc_url( $hub_t['url'] ); ?>"<?php echo $hub_t['current'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $hub_t['label'] ); ?> <span class="count"><?php echo (int) $hub_t['count']; ?></span></a>
<?php endforeach; ?>
    </nav>

<?php if ( $hub_l['items'] ) : ?>
    <div class="table-wrap">
      <table class="table table--stack">
        <thead>
          <tr><th scope="col"><?php echo esc_html( $hub_n[1] ); ?></th><th scope="col">Status</th><th scope="col">Featured</th><th scope="col"><span class="ho-sr">Actions</span></th></tr>
        </thead>
        <tbody>
<?php foreach ( $hub_l['items'] as $hub_r ) : ?>
          <tr>
            <td>
              <div class="table__item">
                <div><strong><?php echo esc_html( $hub_r['title'] ); ?></strong><span><?php if ( $hub_r['weekly'] ) : ?><span class="badge badge--outline"><svg class="ho-icon"><use href="#i-repeat"/></svg>Weekly</span> <?php endif; ?><?php echo esc_html( $hub_r['line'] ); ?></span><?php if ( '' !== $hub_r['note'] ) : ?><span class="table__note"><strong>What to change:</strong> <?php echo esc_html( $hub_r['note'] ); ?></span><?php endif; ?></div>
              </div>
            </td>
            <td data-label="Status"><span class="status status--<?php echo esc_attr( $hub_r['state_class'] ); ?>"><?php echo esc_html( $hub_r['state_label'] ); ?></span></td>
            <td data-label="Featured"><?php echo $hub_r['featured'] ? '<span class="badge badge--featured">Featured</span>' : '<span class="muted">No</span>'; ?></td>
            <td>
              <div class="table__actions"><?php if ( $hub_r['action'] ) : ?><a class="ho-btn ho-btn--sm ho-btn--outline" href="<?php echo esc_url( $hub_r['action']['url'] ); ?>"><svg class="ho-icon"><use href="#i-<?php echo esc_attr( $hub_r['action']['icon'] ); ?>"/></svg><?php echo esc_html( $hub_r['action']['label'] ); ?></a><?php endif; ?></div>
            </td>
          </tr>
<?php endforeach; ?>
        </tbody>
      </table>
    </div>
<?php else : ?>
    <p class="muted">None right now. <a href="<?php echo esc_url( $hub_l['tabs'][0]['url'] ); ?>">See all your <?php echo esc_html( $hub_n[0] ); ?></a></p>
<?php endif; ?>
<?php if ( wp_list_filter( $hub_l['items'], array( 'mine' => false ) ) ) : ?>
    <p class="small muted mt-3 mb-0">&ldquo;Added by Harrow Online&rdquo; means our team put it on your listing. To change one of those, email <a href="mailto:hub@harrowonline.org">hub@harrowonline.org</a>.</p>
<?php endif; ?>
<?php else : ?>
    <div class="empty">
      <svg class="ho-icon"><use href="#i-<?php echo esc_attr( $hub_n[2] ); ?>"/></svg>
      <h3>No <?php echo esc_html( $hub_n[0] ); ?> yet</h3>
      <p><?php echo $hub_why; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_kses above, or plain text ?></p>
<?php foreach ( $hub_next as $hub_i => $hub_b ) : ?>
      <a class="ho-btn <?php echo 0 === $hub_i ? 'ho-btn--primary' : 'ho-btn--outline'; ?>" href="<?php echo esc_url( $hub_b[1] ); ?>"><?php echo 0 === $hub_i && 'ready' === $hub_l['state'] ? '<svg class="ho-icon"><use href="#i-plus"/></svg>' : ''; ?><?php echo esc_html( $hub_b[0] ); ?></a>
<?php endforeach; ?>
    </div>
<?php endif; ?>
