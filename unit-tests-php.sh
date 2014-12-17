#!/bin/sh

# start xvfb at boot ???
#@reboot sh -c 'Xvfb :99 -ac -screen 0 1024x768x8 > /tmp/xvfb.log 2>&1 &'
#Xvfb :99 -ac -screen 0 1024x768x8 > /tmp/xvfb.log 2>&1 &

SELENIUM_BROWSER=firefox
java -jar unit-test-tools/selenium-server-standalone.jar &

phpunit unit-tests/demo1
