<?php
/**
 * Reading parameters the same way for a page ($_GET) and an API request ($request->get_params()).
 * Controllers take a plain array of params and read each one through these, so both get identical results.
 */
defined( 'ABSPATH' ) || exit;

/** A slug parameter ("restaurants"), or '' when missing. */
function hub_param_slug( array $params, $key ) {
	return isset( $params[ $key ] ) && is_scalar( $params[ $key ] ) ? sanitize_title( wp_unslash( (string) $params[ $key ] ) ) : '';
}

/** A yes/no parameter: "1", "true", "yes", "on" (and true) count as yes. */
function hub_param_bool( array $params, $key ) {
	if ( ! isset( $params[ $key ] ) ) {
		return false;
	}
	$v = $params[ $key ];
	return true === $v || in_array( strtolower( (string) $v ), array( '1', 'true', 'yes', 'on' ), true );
}

/** A whole-number parameter, kept between $min and $max; $default when missing or not a number. */
function hub_param_int( array $params, $key, $default = 0, $min = 0, $max = PHP_INT_MAX ) {
	if ( ! isset( $params[ $key ] ) || ! is_numeric( $params[ $key ] ) ) {
		return $default;
	}
	return max( $min, min( $max, (int) $params[ $key ] ) );
}

/** A post ID or slug parameter: an int for digits, otherwise a sanitised slug. */
function hub_param_id_or_slug( $value ) {
	if ( is_int( $value ) || ( is_string( $value ) && ctype_digit( $value ) ) ) {
		return (int) $value;
	}
	return sanitize_title( (string) $value );
}
