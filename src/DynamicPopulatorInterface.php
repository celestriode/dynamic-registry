<?php namespace Celestriode\DynamicRegistry;

/**
 * A populator adds values to a registry if the registry wasn't already populated. Population occurs on-demand, which
 * can save a lot of memory usage as some registries can be quite dense.
 *
 * @package Celestriode\DynamicRegistry
 */
interface DynamicPopulatorInterface
{
    /**
     * Takes in a registry and populates it with values.
     *
     * @param AbstractRegistry $registry
     * @return void
     */
    public function populate(AbstractRegistry $registry): void;
}