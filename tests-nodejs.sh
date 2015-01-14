#!/bin/sh

# start xvfb at boot ???
#@reboot sh -c 'Xvfb :99 -ac -screen 0 1024x768x8 > /tmp/xvfb.log 2>&1 &'
#Xvfb :99 -ac -screen 0 1024x768x8 > /tmp/xvfb.log 2>&1 &

#export SELENIUM_SERVER_JAR="tests-tools/selenium-server-standalone.jar"
#SELENIUM_BROWSER=firefox
##SELENIUM_BROWSER=chrome

# test failes, but webdriver looks like working
#npm test selenium-webdriver -g

nodejs tests/nodejs/test3.js


