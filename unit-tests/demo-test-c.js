
/*
var webdriver = require('selenium-webdriver'),
    SeleniumServer = require('selenium-webdriver/remote').SeleniumServer;

var server = new SeleniumServer('tools/selenium-server-standalone.jar', {
  port: 4444
});

server.start();

var driver = new webdriver.Builder().
    usingServer(server.address()).
    withCapabilities(webdriver.Capabilities.firefox()).
    build();
*/


/*
 * DEMO

driver.get('http://www.google.com');
driver.findElement(webdriver.By.name('q')).sendKeys('webdriver');
driver.findElement(webdriver.By.name('btnG')).click();
driver.wait(function() {
  return driver.getTitle().then(function(title) {
    return true;
    return /webdriver - Google Suche/i.test($title);
  });
}, 1000);

*/


//var driver = require('chromedriver');
//driver.start();



var webdriver = require("selenium-webdriver");
var chrome = require("selenium-webdriver/chrome");

var capabilities = webdriver.Capabilities.chrome();

// Make sure the PATH is set to find ChromeDriver. I'm on a Unix
// system. You'll need to adapt to whatever is needed for
// Windows. Actually, since you say that you can get a browser to show
// up if you don't try to specify options, your ChromeDriver is
// probably already on your PATH, so you can probably skip this.
//process.env["PATH"] += ":/home/user/src/selenium/";

var options = new chrome.Options();

// Commented out because they are obviously not what you want.
// Uncomment and adapt as needed:
//
// options.setChromeBinaryPath("/tmp/foo");
// options.addArguments(["--blah"]);

var driver = new webdriver.Builder().
   withCapabilities(options.toCapabilities()).build();





driver.get('http://localhost/gerhard/src/ogerfibs-dev/repo/web/');

//driver.executeScript("alert('bla');");  // this works

// this does work, but the result is not as expected:
// the view is destroyed and does not load main.php
driver.executeScript(
  "var btn = Ext.ComponentQuery.query('logonWindow #logonButton')[0];" +
  "btn.fireEvent('click', btn);"
  console.log('bla');
);




//driver.get('http://localhost/gerhard/src/ogerfibs/repo/web/main.php?_LOGONID=_1');


//driver.stop();
//driver.quit();


