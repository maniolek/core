<?php
/**
 * This file is part of Vegas package
 *
 * @author Slawomir Zytko <slawomir.zytko@gmail.com>
 * @copyright Amsterdam Standard Sp. Z o.o.
 * @homepage https://bitbucket.org/amsdard/vegas-phalcon
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
namespace Vegas\Application;

use Phalcon\DI\FactoryDefault;
use Phalcon\DiInterface;
use Vegas\DI\ServiceProviderLoader;
use Vegas\Mvc\ModuleLoader;
use Vegas\Mvc\SubModule;
use Vegas\Mvc\SubModuleManager;

/**
 * Class Bootstrap
 * @package Vegas\Application
 */
class Bootstrap
{
    /**
     * @var DiInterface
     */
    private $di;

    /**
     * @var \Vegas\Mvc\Application
     */
    private $application;

    /**
     * @var \Phalcon\Config
     */
    private $config;

    /**
     * @param \Phalcon\Config $config
     */
    public function __construct(\Phalcon\Config $config)
    {
        $this->config = $config;
        $this->di = new FactoryDefault();
        $this->application = new \Vegas\Mvc\Application();
    }

    /**
     * Initializes loader
     * Registers library and plugin directory
     */
    protected function initLoader()
    {
        $loader = new \Phalcon\Loader();
        $loader->registerDirs(
            array(
                $this->config->application->libraryDir,
                $this->config->application->pluginDir
            )
        )->register();
    }

    /**
     * Initializes application modules
     */
    protected function initModules()
    {
        //registers sub modules if defined in configuration
        $subModuleManager = new SubModuleManager();
        if (isset($this->config->application->subModules)) {
            foreach ($this->config->application->subModules->toArray() as $subModuleName) {
                $subModuleManager->registerSubModule($subModuleName);
            }
        }

        //registers modules defined in modules.php file
        $modulesFile = $this->config->application->configDir . DIRECTORY_SEPARATOR . 'modules.php';
        if (!file_exists($modulesFile)) {
            ModuleLoader::dump($this->di);
        }
        $this->application->registerModules(require_once $modulesFile);

        //prepares modules configurations
        foreach ($this->application->getModules() as $module) {
            $moduleConfigFile = dirname($module['path']) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
            if (file_exists($moduleConfigFile)) {
                $this->config->merge(require_once $moduleConfigFile);
            }
        }
    }

    /**
     * Initializes services
     */
    protected function initServices()
    {
        ServiceProviderLoader::autoload($this->di);
    }

    /**
     * Initializes routing
     */
    protected function initRoutes()
    {
        //setups router
        $routerAdapter = new \Vegas\Mvc\Router\Adapter\Standard($this->di);
        $router = new \Vegas\Mvc\Router($routerAdapter);

        //adds routes defined in modules
        $modules = $this->application->getModules();
        foreach ($modules as $module) {
            $router->addModuleRoutes($module);
        }

        //adds routes defined in default file
        $defaultRoutesFile = $this->config->application->configDir . DIRECTORY_SEPARATOR . 'routes.php';
        if (file_exists($defaultRoutesFile)) {
            $router->addRoutes(require_once $defaultRoutesFile);
        }

        //setup router rules
        $router->setup();

        $this->di->set('router', $router->getRouter());
    }

    /**
     * Setups application
     *
     * @return $this
     */
    public function setup()
    {
        $this->initLoader();
        $this->initModules();
        $this->initRoutes();
        $this->initServices();

        return $this;
    }

    /**
     * Start handling MVC requests
     *
     * @return string
     */
    public function run()
    {
        $this->di->set('config', $this->config);
        $this->application->setDI($this->di);
        return $this->application->handle()->getContent();
    }
} 