<?php

declare(strict_types=1);

$fixtures = [
    '$global' => [
        'functions' => [],
        'classes' => [],
        'interfaces' => [],
    ],
];

$fixtures['$global']['functions']['apply_filters'] = <<<'PHP'
/**
 * @param string $hook_name
 * @param mixed $value
 * @param mixed ...$args
 * @return mixed
 */
function apply_filters($hook_name, $value, ...$args) {};
PHP;

$fixtures['$global']['functions']['do_action'] = <<<'PHP'
/**
 * @param string $hook_name
 * @param mixed ...$args
 * @return true
 */
function do_action($hook_name, ...$args) {};
PHP;

$fixtures['$global']['functions']['wp_die'] = <<<'PHP'
/**
 * @template T
 * @param string|WP_Error $message
 * @param string|int $title
 * @param string|array{exit?: T}|int $args
 * @psalm-return (T is null|true ? never : void)
 */
function wp_die($message = '', $title = '', $args = []) {};
PHP;

$fixtures['$global']['functions']['wp_send_json'] = <<<'PHP'
/**
 * @param mixed $response
 * @param int|null $status_code
 * @param int $options
 * @psalm-return never
 */
function wp_send_json($response, $status_code = null, $options = 0) {};
PHP;

$fixtures['$global']['functions']['wp_send_json_success'] = <<<'PHP'
/**
 * @param mixed $data
 * @param int|null $status_code
 * @param int $options
 * @psalm-return never
 */
function wp_send_json_success($data = null, $status_code = null, $options = 0) {};
PHP;

$fixtures['$global']['functions']['wp_send_json_error'] = <<<'PHP'
/**
 * @param mixed $data
 * @param int|null $status_code
 * @param int $options
 * @psalm-return never
 */
function wp_send_json_error($data = null, $status_code = null, $options = 0) {};
PHP;

$fixtures['$global']['functions']['is_wp_error'] = <<<'PHP'
/**
 * @param mixed $thing
 * @return bool
 * @psalm-assert-if-true \WP_Error $thing
 */
function is_wp_error($thing) {};
PHP;

$fixtures['$global']['functions']['get_post_types'] = <<<'PHP'
/**
 * @template T of 'names'|'objects'
 * @param array $args
 * @param T $output
 * @param string $operator
 * @return (T is 'names' ? array<string> : array<\WP_Post_Type>)
 */
function get_post_types($args = array(), $output = 'names', $operator = 'and') {};
PHP;

$fixtures['$global']['functions']['get_taxonomies'] = <<<'PHP'
/**
 * @template T of 'names'|'objects'
 * @param array $args
 * @param T $output
 * @param string $operator
 * @return (T is 'names' ? array<string> : array<\WP_Taxonomy>)
 */
function get_taxonomies( $args = array(), $output = 'names', $operator = 'and' ) {};
PHP;

$fixtures['$global']['functions']['esc_sql'] = <<<'PHP'
/**
 * @param string|array $data
 * @return ($data is string ? string : array)
 */
function esc_sql( $data ) {};
PHP;

$fixtures['$global']['functions']['wp_parse_url'] = <<<'PHP'
/**
 * @param string $url
 * @param int $component
 * @return ($component is -1 ? false|array<string|int> : false|null|string|int)
 */
function wp_parse_url( $url, $component = -1 ) {};
PHP;

return $fixtures;
