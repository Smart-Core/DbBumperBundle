<?php

namespace SmartCore\Bundle\DbDumperBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder("smart_db_dumper");

        if (method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $rootNode = $treeBuilder->root("smart_db_dumper");
        }

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        $rootNode
            ->children()
                ->scalarNode('backups_dir')->defaultValue('%kernel.project_dir%/var/db_dumps/')->end()
                ->scalarNode('timeout')->defaultValue(300)->end()
                ->scalarNode('filename')->defaultNull()->end()
                ->booleanNode('make_copy_to_project_root')->defaultFalse()->end()
                ->booleanNode('make_dump_before_restore')->defaultTrue()->end()
                ->enumNode('archive')->values([null, 'none', 'gz', 'zip'])->defaultValue('gz')->end() // @todo , 'tar', 'zip', '7z'
                ->integerNode('compression_ratio')->defaultValue(6)->min(0)->max(100)->end()
            ->end();

        return $treeBuilder;
    }
}
