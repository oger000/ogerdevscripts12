
var webdriver = require('selenium-webdriver');
var firefox = require('selenium-webdriver/firefox');
var driver = new firefox.Driver();


/*
driver.get('http://en.wikipedia.org/wiki/Wiki');
driver.findElements(webdriver.By.css('[href^="/wiki/"]')).then(function(links){
    console.log('Found', links.length, 'Wiki links.' )
    //driver.quit();
});
*/


driver.get('http://localhost/gerhard/src/ogerfibs-dev/repo/web/');

var promise = "";


var btn = driver.executeScript(
  //"return Ext.ComponentQuery.query('logonWindow #logonButton')[0]"
  "return 'bla'"
);

console.log(JSON.stringify(btn, null, 2));

btn.click();
driver.wait(webdriver.until.titleIs('webdriver - Google Search'), 10000);



/*
driver.get('http://www.google.com/ncr');
driver.findElement(webdriver.By.name('q')).sendKeys('webdriver');
driver.findElement(webdriver.By.name('btnG')).click();
driver.wait(webdriver.until.titleIs('webdriver - Google Searchxxx'), 10000);
driver.quit();
*/
