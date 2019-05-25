<?php
namespace AssetCompress\Test\TestCase\Filter;

use AssetCompress\Filter\Sprockets;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

class SprocketsTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();
        $this->_testFiles = APP;
        $this->_jsDir = $this->_testFiles . 'js' . DS;

        $this->filter = new Sprockets();
        $settings = [
            'paths' => [
                $this->_jsDir,
                $this->_jsDir . 'classes' . DS,
            ]
        ];
        $this->filter->settings($settings);
    }

    public function testThemeAndPluginInclusion()
    {
        $this->loadPlugins(['TestAsset', 'Red']);

        $settings = [
            'paths' => [],
            'theme' => 'Red',
        ];
        $this->filter->settings($settings);

        $this->_themeDir = $this->_testFiles . 'Plugin' . DS . $settings['theme'] . DS;

        $content = file_get_contents($this->_themeDir . 'webroot' . DS . 'theme.js');
        $result = $this->filter->input('theme.js', $content);
        $expected = <<<TEXT
var Theme = new Class({

});
var ThemeInclude = new Class({

});

var Plugin = new Class({

});


TEXT;
        $this->assertTextEquals($expected, $result);
    }
}
