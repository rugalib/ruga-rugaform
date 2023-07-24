<?php
/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
declare(strict_types=1);

namespace Ruga\Skeleton\Test\PHPUnit;

use Laminas\ServiceManager\ServiceManager;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ConfigAggregator\PhpFileProvider;
use PHPUnit\Framework\TestCase;

/**
 * Common setup for all PHPUnit tests that use the common configuration and a container.
 * Loads configuration and creates a service manager.
 *
 * @author   Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 */
abstract class AbstractTestSetUp extends TestCase
{
    private $config;
    
    /** @var ServiceManager */
    private $container;
    
    
    
    protected function setUp(): void
    {
        parent::setUp();
    }
    
    
    
    /**
     * Return the cached config.
     *
     * @return array
     */
    protected function getConfig()
    {
        if (!$this->config) {
            $this->config = $this->configProvider();
        }
        return $this->config;
    }
    
    
    
    /**
     * Return the test specific merged config.
     *
     * @return array
     */
    public function configProvider()
    {
        $config = new ConfigAggregator(
            [
                new PhpFileProvider(__DIR__ . "/../../config/config.php"),
                new PhpFileProvider(__DIR__ . "/../../config/config.local.php"),
            ], null, []
        );
        return $config->getMergedConfig();
    }
    
    
    
    /**
     * Create and return the service manager.
     *
     * @return ServiceManager
     */
    public function getContainer(): ServiceManager
    {
        if (!$this->container) {
            $dependencies = $this->getConfig()['dependencies'];
            $dependencies['services']['config'] = $this->getConfig();
            $this->container = new ServiceManager($dependencies);
        }
        return $this->container;
    }
}
