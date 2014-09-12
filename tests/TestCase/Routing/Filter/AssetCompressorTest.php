<?php
namespace AssetCompress\Test\TestCase\Routing\Filter;

use AssetCompress\AssetConfig;
use AssetCompress\Routing\Filter\AssetCompressor;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Response;
class AssetsCompressorTest extends CakeTestCase {

	public function setUp() {
		parent::setUp();
		$this->_pluginPath = App::pluginPath('AssetCompress');
		$this->testConfig = $this->_pluginPath . 'Test' . DS . 'test_files' . DS . 'Config' . DS . 'integration.ini';

		$map = array(
			'TEST_FILES/' => $this->_pluginPath . 'Test' . DS . 'test_files' . DS
		);
		App::build(array(
			'Plugin' => array($map['TEST_FILES/'] . 'Plugin' . DS )
		));
		Plugin::load('TestAssetIni');

		AssetConfig::clearAllCachedKeys();

		$config = AssetConfig::buildFromIniFile($this->testConfig, $map);
		$config->filters('js', null, array());
		$this->Compressor = $this->getMock('AssetCompressor', array('_getConfig'));
		$this->Compressor->expects($this->atLeastOnce())
			->method('_getConfig')
			->will($this->returnValue($config));

		$this->request = new Request(null, false);
		$this->response = $this->getMock('Response', array('checkNotModified', 'type', 'send'));
		Configure::write('debug', 2);
	}

	public function tearDown() {
		parent::tearDown();
		Plugin::unload('TestAssetIni');
	}

	public function testDynamicBuildFile() {
		$this->response
			->expects($this->once())->method('type')
			->with($this->equalTo('js'));

		$this->request->url = 'cache_js/dynamic.js';
		$this->request->query['file'] = array('library_file.js', 'lots_of_comments.js');
		$data = array('request' => $this->request, 'response' => $this->response);
		$event = new Event('Dispatcher.beforeDispatch', $this, $data);
		$this->assertSame($this->response, $this->Compressor->beforeDispatch($event));

		$this->assertRegExp('/function test/', $this->response->body());
		$this->assertRegExp('/multi line comments/', $this->response->body());
		$this->assertTrue($event->isStopped());
	}

	public function testPluginIniBuildFile() {
		$this->response
			->expects($this->once())->method('type')
			->with($this->equalTo('js'));

		$this->request->url = 'cache_js/TestAssetIni.libs.js';
		$data = array('request' => $this->request, 'response' => $this->response);
		$event = new Event('Dispatcher.beforeDispatch', $this, $data);
		$this->assertSame($this->response, $this->Compressor->beforeDispatch($event));

		$this->assertRegExp('/var BaseClass = new Class/', $this->response->body());
		$this->assertRegExp('/var Template = new Class/', $this->response->body());
		$this->assertTrue($event->isStopped());
	}

	public function testDynamicBuildFileCheckNotModified() {
		$this->response
			->expects($this->once())->method('checkNotModified')
			->with($this->request)
			->will($this->returnValue(true));

		$this->request->url = 'cache_js/dynamic.js';
		$this->request->query['file'] = array('library_file.js', 'lots_of_comments.js');
		$data = array('request' => $this->request, 'response' => $this->response);
		$event = new Event('Dispatcher.beforeDispatch', $this, $data);
		$this->assertSame($this->response, $this->Compressor->beforeDispatch($event));

		$this->assertEquals('', $this->response->body());
		$this->assertTrue($event->isStopped());
	}

/**
 * When debug mode is off, dynamic build files should not be dispatched, this is to try and mitigate
 * the ability to DOS attack an app, by hammering expensive to generate resources.
 *
 */
	public function testDynamicBuildFileDebugOff() {
		Configure::write('debug', 0);
		$data = array('request' => $this->request, 'response' => $this->response);
		$event = new Event('Dispatcher.beforeDispatch', $this, $data);
		$this->request->url = 'cache_js/dynamic.js';
		$this->request->query['file'] = array('library_file.js', 'lots_of_comments.js');
		$this->assertEmpty($this->Compressor->beforeDispatch($event));
	}

}
