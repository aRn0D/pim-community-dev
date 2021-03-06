<?php

namespace Pim\Component\ReferenceData;

use Pim\Component\ReferenceData\Model\Configuration;
use Pim\Component\ReferenceData\Model\ConfigurationInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Reference data configuration registry
 *
 * @author    Julien Janvier <jjanvier@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ConfigurationRegistry implements ConfigurationRegistryInterface
{
    /** @var ConfigurationInterface[] */
    protected static $configurations = [];

    /**
     * {@inheritdoc}
     */
    public function register(ConfigurationInterface $configuration, $name)
    {
        $configuration->setName($name);
        self::$configurations[$name] = $configuration;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function registerRaw(array $rawConfiguration, $name)
    {
        $this->checkRawConfiguration($rawConfiguration);

        $configuration = new Configuration();
        $configuration->setType($rawConfiguration['type']);
        $configuration->setClass($rawConfiguration['class']);

        return $this->register($configuration, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        return self::$configurations[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function has($name)
    {
        return isset(self::$configurations[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        return self::$configurations;
    }

    /**
     * {@inheritdoc}
     */
    public function unregister($name)
    {
        unset(self::$configurations[$name]);

        return $this;
    }

    /**
     * @param array $rawConfiguration
     */
    protected function checkRawConfiguration(array $rawConfiguration)
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired(['class', 'type'])
            ->setAllowedTypes('class', 'string')
            ->setAllowedTypes('type', 'string');

        $resolver->resolve($rawConfiguration);
    }
}
