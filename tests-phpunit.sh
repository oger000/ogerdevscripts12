#!/bin/sh

# start xvfb at boot ???
#@reboot sh -c 'Xvfb :99 -ac -screen 0 1024x768x8 > /tmp/xvfb.log 2>&1 &'
#Xvfb :99 -ac -screen 0 1024x768x8 > /tmp/xvfb.log 2>&1 &

if [ `ps xfa | grep selenium-server-standalone.jar | grep -v grep | wc -l` -eq 0 ] ; then
  echo "Starting selenium server ..."
  #SELENIUM_BROWSER=firefox
  java -jar tests-tools/selenium-server-standalone.jar &
  sleep 30
fi


echo "Starting unit tests ..."
phpunit tests/demo1
