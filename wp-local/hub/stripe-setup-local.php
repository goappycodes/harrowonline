<?php
/**
 * Create or update the Hub's products and prices in Stripe (CLI). Uses the keys in the repository's .env (test keys
 * locally). Safe to run again: unchanged prices are left alone, a changed amount gets a new price.
 *
 *   C:\php\php.exe wp-local/hub/stripe-setup-local.php
 */
$_SERVER['HTTP_HOST']   = 'localhost:8020';
$_SERVER['REQUEST_URI'] = '/hub/';
require __DIR__ . '/wp-load.php';

$r = hub_billing_setup();
echo ( $r['ok'] ? 'Stripe (' . $r['mode'] . ' mode):' : 'Not done:' ) . "\n  " . implode( "\n  ", $r['done'] ) . "\n";
