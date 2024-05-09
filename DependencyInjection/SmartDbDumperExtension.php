<?php

namespace SmartCore\Bundle\DbDumperBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SmartDbDumperExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter('smart_db_dumper.backups_dir', $config['backups_dir']);
        $container->setParameter('smart_db_dumper.timeout', $config['timeout']);
        $container->setParameter('smart_db_dumper.filename', $config['filename']);
        $container->setParameter('smart_db_dumper.archive', $config['archive']);
        $container->setParameter('smart_db_dumper.compression_ratio', $config['compression_ratio']);
        $container->setParameter('smart_db_dumper.make_copy_to_project_root', $config['make_copy_to_project_root']);
        $container->setParameter('smart_db_dumper.make_dump_before_restore', $config['make_dump_before_restore']);
    }
}
