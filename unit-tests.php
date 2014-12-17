#!/usr/bin/php
<?PHP

//set_include_path(get_include_path() . ":");
//require_once 'PHPUnit/Extensions/Selenium2TestCase.php';
//require_once("PHPUnit/Autoload.php");


class WebTest extends PHPUnit_Extensions_Selenium2TestCase
{
    protected function setUp()
    {
        $this->setBrowser('firefox');
        $this->setBrowserUrl('http://www.example.com/');
    }

    public function testTitle()
    {
        $this->url('http://www.example.com/');
        $this->assertEquals('Example WWW Page', $this->title());
    }

}


?>
