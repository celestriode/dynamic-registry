<?php namespace Celestriode\DynamicRegistry;

/**
 * A simple implementation of a dynamic registry, when you don't have the need to make your own.
 *
 * @package Celestriode\DynamicRegistry
 */
class SimpleRegistry extends AbstractRegistry
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'registry';
    }
}