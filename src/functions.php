<?php
namespace OliverHader\TYPO3Remote;

function applyModifications(array $settings): array
{
    foreach ($settings as &$value) {
        if (is_array($value)) {
            $value = applyModifications($value);
            continue;
        } elseif (!is_string($value)) {
            continue;
        }
        if (strpos($value, '::password-hash::') === 0) {
            $value = applyPasswordHash($value);
        } elseif (strpos($value, '::random-value::') === 0) {
            $value = bin2hex(random_bytes(64));
        }
    }
    return $settings;
}

function applyPasswordHash(string $value): string
{
    $prefix = '::password-hash::';
    if (strpos($value, $prefix) !== 0) {
        return $value;
    }
    $value = substr($value, strlen($prefix));
    if (stripos($value, 'password') !== false) {
        throw new \LogicException(sprintf('(default) password "%s" is too weak, change it', $value));
    }
    return password_hash($value, PASSWORD_ARGON2I);
}