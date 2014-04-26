#!/bin/sh


export SELENIUM_SERVER_JAR="tools/selenium-server-standalone.jar"
SELENIUM_BROWSER=firefox

#npm test selenium-webdriver -g

node unit-tests/demo-test.js


