#!/usr/bin/env sh
set -e

echo "Copying default XDEBUG ini"
cp /var/www/xdebug/xdebug-default.ini /usr/local/etc/php/conf.d/xdebug.ini

# for bash: if [[ $MODES == *"profile"* ]]; then
if [ $MODES != "${MODES/profile/}" ]; then
    echo "Appending profile ini"
    cat /var/www/xdebug/xdebug-profile.ini >> /usr/local/etc/php/conf.d/xdebug.ini
fi

if [ $MODES != "${MODES/debug/}" ]; then
    echo "Appending debug ini"
    cat /var/www/xdebug/xdebug-debug.ini >> /usr/local/etc/php/conf.d/xdebug.ini

    echo "Setting Client Host to: $CLIENT_HOST"
    sed -i -e 's/xdebug.client_host = localhost/xdebug.client_host = '"${CLIENT_HOST}"'/g' /usr/local/etc/php/conf.d/xdebug.ini

    echo "Setting Client Port to: $CLIENT_PORT"
    sed -i -e 's/xdebug.client_port = 9000/xdebug.client_port = '"${CLIENT_PORT}"'/g' /usr/local/etc/php/conf.d/xdebug.ini

    echo "Setting IDE Key to: $IDEKEY"
    sed -i -e 's/xdebug.idekey = docker/xdebug.idekey = '"${IDEKEY}"'/g' /usr/local/etc/php/conf.d/xdebug.ini
fi

if [ $MODES != "${MODES/trace/}" ]; then
    echo "Appending trace ini"
    cat /var/www/xdebug/xdebug-trace.ini >> /usr/local/etc/php/conf.d/xdebug.ini
fi

# for bash: if [[ "off" == $MODES || -z $MODES ]]; then
if [ $MODES != "${MODES/off/}" ] || [ -z $MODES ]; then
    echo "Disabling XDEBUG";
    cp /var/www/xdebug/xdebug-off.ini /usr/local/etc/php/conf.d/xdebug.ini
else
    echo "Setting XDEBUG mode: $MODES"
    echo "xdebug.mode = $MODES" >> /usr/local/etc/php/conf.d/xdebug.ini
fi;
