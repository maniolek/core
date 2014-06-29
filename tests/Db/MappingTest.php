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
 
namespace Vegas\Tests\Db;

use Phalcon\DI;
use Vegas\Db\Decorator\CollectionAbstract;
use Vegas\Db\Decorator\ModelAbstract;
use Vegas\Db\Exception\InvalidMappingClassException;
use Vegas\Db\Exception\MappingClassNotFoundException;
use Vegas\Db\Mapping\Json;
use Vegas\Db\MappingInterface;
use Vegas\Db\MappingManager;

class Fake extends CollectionAbstract
{
    public function getSource()
    {
        return 'fake';
    }

    protected $mappings = array(
        'somedata'  =>  'json',
        'somecamel' =>  'camelize',
        'encoded'   =>  'decoder',
        'upperstring' => 'upperCase'
    );
}

class FakeModel extends ModelAbstract
{
    public function getSource()
    {
        return 'fake_table';
    }

    protected $mappings = array(
        'somedata'  =>  'json',
        'somecamel' =>  'camelize',
        'encoded'   =>  'decoder',
    );
}

class UpperCase implements MappingInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'upperCase';
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(& $value)
    {
        $value = strtoupper($value);

        return $value;
    }
}

class Decoder implements MappingInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'decoder';
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(& $value)
    {
        $value = base64_decode($value);

        return $value;
    }
}

class MappingTest extends \PHPUnit_Framework_TestCase
{
    public function testMappingManager()
    {
        //define mappings
        $mappingManager = new MappingManager();
        $mappingManager->add(new Json());
        $mappingManager->add('\Vegas\Db\Mapping\Camelize');
        $mappingManager->add(new UpperCase());

        $this->assertNotEmpty(MappingManager::find('json'));
        $this->assertInstanceOf('\Vegas\Db\MappingInterface', MappingManager::find('json'));

        $this->assertNotEmpty(MappingManager::find('camelize'));
        $this->assertInstanceOf('\Vegas\Db\MappingInterface', MappingManager::find('camelize'));

        try {
            $mappingManager->add(new \stdClass());
        } catch (InvalidMappingClassException $e) {
            $m = $e->getMessage();
        }
        $this->assertEquals('Mapping class must be an instance of MappingInterface', $m);

        try {
            $mappingManager->remove('json');
            $mappingManager->find('json');
        } catch (MappingClassNotFoundException $e) {
            $m = $e->getMessage();
        }
        $this->assertEquals(sprintf('Mapping class \'%s\' was not found', 'json'), $m);

        try {
            $mappingManager->find('fake_mapping');
        } catch (MappingClassNotFoundException $e) {
            $m = $e->getMessage();
        }
        $this->assertEquals(sprintf('Mapping class \'%s\' was not found', 'fake_mapping'), $m);
    }

    public function testResolveCollectionMappings()
    {
        $mappingManager = new MappingManager();
        $mappingManager->add(new Json());
        $mappingManager->add('\Vegas\Db\Mapping\Camelize');
        $mappingManager->add(new Decoder());
        $mappingManager->add(new UpperCase());

        DI::getDefault()->get('mongo')->selectCollection('fake')->remove(array());

        /**  CREATE RECORD  */
        $fake = new Fake();

        $someData = json_encode(array(1,2,3,4,5,6));
        $fake->somedata = $someData;

        $nonCamelText = 'this_is_non_camel_case_text';
        $fake->somecamel = $nonCamelText;

        $encodedVal = base64_encode('test');
        $fake->encoded = $encodedVal;

        $lowerTxt = 'some lower case text';
        $fake->upperstring = $lowerTxt;

        $this->assertTrue($fake->save());
        /**  CREATE RECORD  */

        /** TEST VALUES */
        $fakeDoc = Fake::findFirst();

        $this->assertInternalType('array', $fakeDoc->readMapped('somedata'));
        $this->assertEquals(\Phalcon\Text::camelize($nonCamelText), $fakeDoc->readMapped('somecamel'));
        $this->assertEquals('test', $fakeDoc->readMapped('encoded'));
        $this->assertEquals(strtoupper($lowerTxt), $fakeDoc->readMapped('upperstring'));

        $this->assertEquals($nonCamelText, $fakeDoc->somecamel);
        $this->assertEquals($someData, $fakeDoc->somedata);
        $this->assertEquals($encodedVal, $fakeDoc->encoded);
        $this->assertEquals($lowerTxt, $fakeDoc->upperstring);
        $this->assertEquals($someData, $fakeDoc->readAttribute('somedata'));
        $this->assertEquals($nonCamelText, $fakeDoc->readAttribute('somecamel'));
        $this->assertEquals($encodedVal, $fakeDoc->readAttribute('encoded'));
        $this->assertEquals($encodedVal, $fakeDoc->readAttribute('encoded'));
        $this->assertEquals($lowerTxt, $fakeDoc->readAttribute('upperstring'));

        $ownMappedValues = array(
            '_id'   =>  $fakeDoc->readMapped('_id'),
            'somedata'   =>  $fakeDoc->readMapped('somedata'),
            'somecamel'   =>  $fakeDoc->readMapped('somecamel'),
            'encoded'   =>  $fakeDoc->readMapped('encoded'),
            'upperstring'   =>  $fakeDoc->readMapped('upperstring')
        );
        $mappedValues = $fakeDoc->toMappedArray();

        $this->assertEquals($mappedValues['somedata'], $ownMappedValues['somedata']);
        $this->assertEquals($mappedValues['somecamel'], $ownMappedValues['somecamel']);
        $this->assertEquals($mappedValues['encoded'], $ownMappedValues['encoded']);
        $this->assertEquals($mappedValues['upperstring'], $ownMappedValues['upperstring']);


        $fakeDoc->removeMapping('somedata');
        $this->assertEquals($someData, $fakeDoc->readMapped('somedata'));

        $fakeDoc->clearMappings();
        $this->assertEquals($nonCamelText, $fakeDoc->readMapped('somecamel'));
    }

    public function testResolveModelMappings()
    {
        $mappingManager = new MappingManager();
        $mappingManager->add(new Json());
        $mappingManager->add('\Vegas\Db\Mapping\Camelize');

        $di = DI::getDefault();
        $di->get('db')->execute('DROP TABLE IF EXISTS fake_table ');
        $di->get('db')->execute(
            'CREATE TABLE fake_table(
            id int not null primary key auto_increment,
            somedata varchar(250) null,
            somecamel varchar(250) null
            )'
        );

        $someData = json_encode(array(1,2,3,4,5,6));
        $fake = new FakeModel();
        $fake->somedata = $someData;
        $nonCamelText = 'this_is_non_camel_case_text';
        $fake->somecamel = $nonCamelText;
        $this->assertTrue($fake->save());

        $fakeRecord = FakeModel::findFirst();

        $this->assertInternalType('array', $fakeRecord->readMapped('somedata'));
        $this->assertEquals(\Phalcon\Text::camelize($nonCamelText), $fakeRecord->readMapped('somecamel'));

        $this->assertEquals($nonCamelText, $fakeRecord->somecamel);
        $this->assertEquals($someData, $fakeRecord->somedata);
        $this->assertEquals($someData, $fakeRecord->readAttribute('somedata'));
        $this->assertEquals($nonCamelText, $fakeRecord->readAttribute('somecamel'));

        $ownMappedValues = array(
            '_id'   =>  $fakeRecord->readMapped('_id'),
            'somedata'   =>  $fakeRecord->readMapped('somedata'),
            'somecamel'   =>  $fakeRecord->readMapped('somecamel'),
        );
        $mappedValues = $fakeRecord->toMappedArray();

        $this->assertEquals($mappedValues['somedata'], $ownMappedValues['somedata']);
        $this->assertEquals($mappedValues['somecamel'], $ownMappedValues['somecamel']);

        $fakeRecord->removeMapping('somedata');
        $this->assertEquals($someData, $fakeRecord->readMapped('somedata'));

        $fakeRecord->clearMappings();
        $this->assertEquals($nonCamelText, $fakeRecord->readMapped('somecamel'));
    }
} 