#!/usr/bin/env bash

SITE='ndnplugintestdemo'
LOG_FILE='/home/vagrant/root-install.log'

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

echo "---- update apt-get ----" >&3
apt-get update

echo "---- setup database ----" >&3
debconf-set-selections <<< 'mysql-server mysql-server/root_password password root'
debconf-set-selections <<< 'mysql-server mysql-server/root_password_again password root'

echo "---- install libraries for php5.5 ----" >&3
apt-get install -y vim curl python-software-properties

echo "---- add apt-repository for php5.5 ----" >&3
add-apt-repository -y ppa:ondrej/php5

echo "---- update apt-get (again, now that we have the new repository) ----" >&3
apt-get update

echo "---- install libraries ----" >&3
apt-get install -y subversion php5 apache2 libapache2-mod-php5 php5-curl php5-gd php5-mcrypt mysql-server-5.5 php5-mysql git-core

echo "---- installing and configuring Xdebug ----" >&3
apt-get install -y php5-xdebug
cat << EOF | tee -a /etc/php5/mods-available/xdebug.ini
xdebug.scream=0
xdebug.cli_color=1
xdebug.show_local_vars=1
EOF

echo "---- enable mod rewrite ----" >&3
a2enmod rewrite

echo "---- download composer ----" >&3
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

echo "---- setup root directory ----" >&3
mkdir -p /vagrant/
rm -rf /var/www/html
ln -fs /vagrant/ /var/www/html

echo "---- turn on error reporting ----" >&3
sed -i "s/error_reporting = .*/error_reporting = E_ALL/" /etc/php5/apache2/php.ini
sed -i "s/display_errors = .*/display_errors = On/" /etc/php5/apache2/php.ini

echo "---- set apache configurations ----" >&3
sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

echo "---- restart apache ----" >&3
service apache2 restart

echo "---- create database ----" >&3
mysqladmin -uroot -proot create "${SITE}"
cd /vagrant

echo "---- install wp-cli ----" >&3
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
mv wp-cli.phar /usr/local/bin/wp
chmod u+rwx /usr/local/bin/wp

echo "---- done with root stuff, logged to ${LOG_FILE} ----" >&3
