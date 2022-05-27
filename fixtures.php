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

return $fixtures;
