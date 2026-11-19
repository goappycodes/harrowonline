<?php
/**
 * Fill the business fields (plain post meta, edited in wp-admin) from the imported Hub data (CLI). Safe to run again;
 * it replaces wp-admin edits to businesses with the imported data. import-local.php runs it too.
 * Then commit wp-content/database/hub.sqlite and wp-content/uploads/.
 *
 *   php wp-local/hub/fields-migrate-local.php
 */
$_SERVER['HTTP_HOST']   = 'localhost:8020';
$_SERVER['REQUEST_URI'] = '/hub/';
require __DIR__ . '/wp-load.php';

hub_fields_migrate();
