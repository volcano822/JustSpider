#/bin/sh

ps -ef | grep lj | awk '{print $2;}' | xargs -i kill -9 {}
