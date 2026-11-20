<?php
/**
 * A document page (privacy, terms, sources): breadcrumb, header, the "On this page" card and the text from the page's
 * editor; a draft note and an attribution box when the page has them. $args: page ("privacy" | "terms" | "sources").
 */
defined( 'ABSPATH' ) || exit;

$page = $args['page'];
$t    = hub_pages_text( $page );
$doc  = hub_pages_document( $page );
$k    = function ( $field ) use ( $t, $page ) {
	return isset( $t[ $page . '_' . $field ] ) ? $t[ $page . '_' . $field ] : '';
};
?>
<nav class="crumbs ho-container" aria-label="Breadcrumb">
  <ol><li><a href="<?php echo esc_url( hub_url( '' ) ); ?>">Hub</a></li><li><span aria-current="page"><?php echo esc_html( $k( 'crumb' ) ); ?></span></li></ol>
</nav>

<header class="page-head ho-container">
  <h1 class="page-head__title"><?php echo esc_html( $k( 'title' ) ); ?></h1>
  <p class="page-head__lead"><?php echo esc_html( $k( 'lead' ) ); ?></p>
</header>

<div class="ho-container legal">
  <aside class="legal__side" aria-label="About this page">
    <div class="legal__card">
      <button class="legal__toc-toggle" type="button" aria-expanded="false" aria-controls="legal-toc" data-toc-toggle>
        <span class="legal__toc-label"><?php echo esc_html( $k( 'toc_title' ) ); ?></span>
        <span class="legal__toc-current" data-toc-current></span>
        <svg class="ho-icon" aria-hidden="true"><use href="#i-chevron-down"/></svg>
      </button>
      <nav class="legal__toc legal__toc--first" id="legal-toc" aria-labelledby="toc-title">
    <h2 id="toc-title"><?php echo esc_html( $k( 'toc_title' ) ); ?></h2>
    <ol>
<?php foreach ( $doc['toc'] as $item ) : ?>
      <li><a href="#<?php echo esc_attr( $item[0] ); ?>"><?php echo esc_html( $item[1] ); ?></a></li>
<?php endforeach; ?>
    </ol>
      </nav>
    </div>
  </aside>

  <div class="legal__main">
    <div class="legal__meta">
      <p class="legal__updated"><svg class="ho-icon ho-icon--sm" aria-hidden="true"><use href="#i-calendar"/></svg><?php echo esc_html( $k( 'updated' ) ); ?></p>
<?php if ( '' !== $k( 'draft_title' ) && ! $k( 'draft_hide' ) ) : ?>
      <p class="legal__draft"><svg class="ho-icon ho-icon--sm" aria-hidden="true"><use href="#i-alert"/></svg><span><strong><?php echo esc_html( $k( 'draft_title' ) ); ?></strong> <?php echo esc_html( $k( 'draft_text' ) ); ?></span></p>
<?php endif; ?>
    </div>
<?php if ( '' !== $k( 'attrib_text' ) && ! $k( 'attrib_hide' ) ) : ?>
    <div class="legal__attrib">
      <p class="legal__attrib-label"><?php echo esc_html( $k( 'attrib_label' ) ); ?></p>
      <p class="mb-0"><?php echo wp_kses( hub_html_links( $k( 'attrib_text' ) ), array( 'a' => array( 'href' => true, 'rel' => true, 'target' => true ), 'strong' => array(), 'em' => array(), 'br' => array() ) ); ?></p>
    </div>
<?php endif; ?>
    <div class="prose legal__prose">
<?php echo $doc['html']; // phpcs:ignore WordPress.Security.EscapeOutput -- the page's own content, through the_content ?>
  </div>
  </div>
</div>
