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
 
namespace Disabled\Models;

use Vegas\Db\Decorator\CollectionAbstract;

class Fake extends CollectionAbstract
{
    public function getSource()
    {
        return 'fake';
    }

    protected $mappings = array(
        'arraydata' =>  'json'
    );
} 