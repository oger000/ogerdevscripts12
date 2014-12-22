<?PHP

//set_include_path(get_include_path() . ":");
//require_once 'PHPUnit/Extensions/Selenium2TestCase.php';
//require_once("PHPUnit/Autoload.php");



/*
class WebTest extends PHPUnit_Extensions_Selenium2TestCase
{
    public static $browsers = array(
        array(
            '...
            'browserName' => 'iexplorer',
            'sessionStrategy' => 'shared',
            ...
        )
    );

     protected function setUp()
    {
        $this->setBrowser('firefox');
        $this->setBrowserUrl('http://www.example.com/');
        $this->shareSession(true);
    }

    public function testTitle()
    {
        $this->url('http://www.example.com/');
        $this->assertEquals('Example WWW Page', $this->title());
    }

}
*/



require_once 'PHPUnit/Extensions/SeleniumTestCase.php';

class WebTest extends PHPUnit_Extensions_SeleniumTestCase
{
    protected function setUp()
    {
        $this->setBrowser('*firefox');
        $this->setBrowserUrl('http://www.example.com/');
        $this->shareSession(true);
    }

    public function testTitle1()
    {
        $this->open('http://www.example.com/');
        $this->assertTitle('Example WWW Page');
    }

    public function testTitle2()
    {
        $this->assertTitle('Example Domain');
    }
}



?>
