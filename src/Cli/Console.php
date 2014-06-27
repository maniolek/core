<?php
/**
 * This file is part of Vegas package
 *
 * @author Slawomir Zytko <slawomir.zytko@gmail.com>
 * @copyright Amsterdam Standard Sp. Z o.o.
 * @homepage http://vegas-cmf.github.io
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Vegas\Cli;

class Console extends \Phalcon\CLI\Console
{

    /**
     * Register an array of modules present in the application
     *
     *<code>
     *	$this->registerModules(array(
     *		'frontend' => array(
     *			'className' => 'Multiple\Frontend\Module',
     *			'path' => '../apps/frontend/Module.php'
     *		),
     *		'backend' => array(
     *			'className' => 'Multiple\Backend\Module',
     *			'path' => '../apps/backend/Module.php'
     *		)
     *	));
     *</code>
     *
     * @param array $modules
     */
    public function registerModules($modules)
    {
        $this->registerSharedData($modules);
        parent::registerModules($modules);
    }

    /**
     * Registers namespaces for models and services within modules
     *
     * @param $modules
     */
    private function registerSharedData($modules)
    {
        $loader = new \Phalcon\Loader();

        foreach ($modules As $name => $module)
        {
            $loader->registerNamespaces(
                array(
                    $name.'\Models'   => dirname($module['path']).'/models/',
                    $name.'\Services' => dirname($module['path']).'/services/'
                ), true
            );
        }

        $loader->register();
    }
}
 