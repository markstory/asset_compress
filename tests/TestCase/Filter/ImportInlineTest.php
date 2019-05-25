<?php
declare(strict_types=1);

namespace AssetCompress\Test\TestCase\Filter;

use AssetCompress\Filter\ImportInline;
use Cake\TestSuite\TestCase;

class ImportInlineTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->loadPlugins(['Red']);
        $this->filter = new ImportInline();
        $settings = [
            'paths' => [
                APP . 'css/',
            ],
            'theme' => 'Red',
        ];
        $this->filter->settings($settings);
    }

    public function testReplacementNestedAndTheme()
    {
        $content = file_get_contents(APP . 'css' . DS . 'has_import.css');
        $result = $this->filter->input('has_import.css', $content);
        $expected = <<<TEXT
* {
    margin:0;
    padding:0;
}
#nav {
    width:100%;
}

body {
    color: red !important;
}

body {
    color:#f00;
    background:#000;
}

TEXT;
        $this->assertEquals($expected, $result);
    }
}
