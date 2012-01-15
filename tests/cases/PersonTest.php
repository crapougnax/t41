<?php

require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Person test case.
 */
class PersonTest extends PHPUnit_Framework_TestCase {
	
	
	/**
	 *
	 * @var Person
	 */
	private $Person;
	
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		
		$path = explode(DIRECTORY_SEPARATOR, dirname(__FILE__));
		array_pop($path);
		array_pop($path);
		$path = implode(DIRECTORY_SEPARATOR, $path) . DIRECTORY_SEPARATOR;
		
		set_include_path(get_include_path()
					   . PATH_SEPARATOR . $path . 'library'
				 	   . PATH_SEPARATOR . $path . 'tests/fixtures/model'
						);
		
		require_once 't41/Core.php';
		
		t41\Core::enableAutoloader(array('t41', 'Tests'));
		t41\Config::addPath($path . 'tests/fixtures/configs/', t41\Config::REALM_CONFIGS);

		t41\ObjectModel::loadConfig();
		
		$this->Person = \t41\ObjectModel::factory('Tests\Person'/*, array() of parameters */);
	}
	
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Person = null;
		parent::tearDown();
	}
	
	/**
	 * Constructs the test case.
	 */
	public function __construct() {
		// TODO Auto-generated constructor
	}
	
	public function testPersonInstanceOfModel()
	{
		$this->assertInstanceOf('t41\ObjectModel\ObjectModelAbstract', $this->Person);
	}
	
	public function testSetLastnameProperty()
	{
		$res = $this->Person->setLastname('Doe');
		$this->assertSame($this->Person, $res);
		$this->assertSame('Doe', $this->Person->getLastname());
	}

	public function testSetBirthdateProperty()
	{
		$res = $this->Person->setBirthdate('2002-01-01');
		$this->assertSame($this->Person, $res);
		$this->assertSame('2002-01-01', $this->Person->getBirthdate());
	}
}
