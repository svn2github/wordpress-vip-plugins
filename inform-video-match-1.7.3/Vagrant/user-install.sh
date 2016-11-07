#!/usr/bin/env bash

USER='user'
PASSWORD='password'
ADMIN_EMAIL='test@test.com'
DOMAIN='ndnplugintestdemo.dev'
LOG_FILE='/home/vagrant/user-install.log'

trap ctrl_c INT
ctrl_c() {
  tput bold >&3; tput setaf 1 >&3; echo -e '\nCancelled by user' >&3; echo -e '\nCancelled by user'; tput sgr0 >&3; if [ -n "$!" ]; then kill $!; fi; exit 1
}

log2file() {
  exec 3>&1 4>&2
  trap 'exec 2>&4 1>&3' 0 1 2 3
  exec 1>${LOG_FILE} 2>&1
}

log2file

echo "---- set up WordPress and add/setup our demo plugin ----" >&3
cd /vagrant
if [ ! -f /vagrant/wp-config.php ]; then
  wp core download  --path="/vagrant"
  wp core config --dbname=ndnplugintestdemo --dbuser=root --dbpass=root
  wp core install --title="Your Blog Title" --admin_user="$USER" --admin_password="$PASSWORD" --admin_email="$ADMIN_EMAIL"  --path="/vagrant" --url="$DOMAIN"
  cp -r /vagrant/ndn-plugin/ /vagrant/wp-content/plugins/
  wp plugin activate ndn-plugin --path="/vagrant"

  echo "---- add composer to environment PATH, source ~/.bash_profile ----" >&3
  echo 'export PATH=~/.composer/vendor/bin/:$PATH' >>~/.bash_profile
  source ~/.bash_profile

  echo "---- globally install phpunit ----" >&3
  composer global require "phpunit/phpunit=4.2.*"
fi

echo "---- done with vagrant user stuff, logged to ${LOG_FILE} ----" >&3
