<?php namespace Celestriode\DynamicRegistry;

/**
 * A slightly extension that only accepts string values for its registry.
 *
 * @package Celestriode\DynamicRegistry
 */
abstract class AbstractStringRegistry extends AbstractRegistry
{
    /**
     * @inheritDoc
     */
    protected function validValue($value): bool
    {
        if (!is_string($value)) {

            return false;
        }

        return parent::validValue($value);
    }
}