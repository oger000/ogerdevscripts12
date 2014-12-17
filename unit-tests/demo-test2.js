
var webdriverSe = require('selenium-webdriver');
//var driver = new webdriver.Builder().usingServer().withCapabilities({'browserName': 'chrome' }).build();


var By = webdriverSe.By,
    until = webdriverSe.until;

var webdriver = require('chromedriver');
webdriver.start();

var firefox = require('selenium-webdriver/firefox');
var driver = new firefox.Driver();



/*
var driver = new webdriver.Builder().
    usingServer(server.address()).
    withCapabilities(webdriver.Capabilities.chrome()).
    build();
*/



driver.get('http://en.wikipedia.org/wiki/Wiki');
driver.findElements(webdriver.By.css('[href^="/wiki/"]')).then(function(links){
    console.log('Found', links.length, 'Wiki links.' )
    driver.quit();
});



