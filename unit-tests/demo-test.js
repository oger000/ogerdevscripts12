

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


driver.get('http://localhost/gerhard/src/ogerfibs/repo/web/');

//driver.executeScript("alert('bla');");  // this works

// this does work, but the result is not as expected:
// the view is destroyed and does not load main.php
driver.executeScript(
  "var btn = Ext.ComponentQuery.query('logonWindow #logonButton')[0];" +
  "btn.fireEvent('click', btn);"
);




//driver.get('http://localhost/gerhard/src/ogerfibs/repo/web/main.php?_LOGONID=_1');



//driver.quit();
