<?php

namespace aintreallydown\DepositBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

class DepositExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');
    }
    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('framework', [
            'translator' => [
                'paths' => [
                    __DIR__ . '/../../translations',
                ],
            ],
        ]);
        $container->prependExtensionConfig('twig', [
            'paths' => [
                __DIR__ . '/../../templates' => 'DepositBundle',
            ],
        ]);
    }
     
}
