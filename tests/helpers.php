<?php

$passed = 0;
function check($condition, $message)
{
    global $passed;
    if (!$condition) { throw new RuntimeException($message); }
    $passed++;
}
function failsWith(callable $callback, $expected)
{
    try { $callback(); } catch (RuntimeException $error) {
        check($error->getMessage() === $expected, 'Unexpected error: '.$error->getMessage());
        return;
    }
    throw new RuntimeException('Expected error: '.$expected);
}
