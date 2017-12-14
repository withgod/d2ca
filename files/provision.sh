#!/bin/sh

export DEBIAN_FRONTEND=noninteractive

sed -i.bak -e "s%http://[^ ]\+%http://ftp.jaist.ac.jp/pub/Linux/ubuntu/%g" /etc/apt/sources.list
apt-get update
apt-get -y install language-pack-ja curl jq

ln -sf /usr/share/zoneinfo/Asia/Tokyo /etc/localtime
update-locale
dpkg-reconfigure -f noninteractive tzdata

if [ ! -f /etc/apt/sources.list.d/ondrej-ubuntu-php-xenial.list ]; then
    LC_ALL=C.UTF-8 add-apt-repository -y ppa:ondrej/php
fi
apt-get update
apt-get install -y libapache2-mod-php7.2 php7.2-cli php7.2-zip php7.2-mbstring php7.2-curl php7.2-mysql
chown www-data:www-data /var/lib/php/sessions

echo 'mysql-server mysql-server/root_password password password'       | debconf-set-selections
echo 'mysql-server mysql-server/root_password_again password password' | debconf-set-selections
apt-get install -y mysql-server
cp -f /vagrant/files/sqlmode.cnf /etc/mysql/mysql.conf.d/sqlmode.cnf
service mysql restart

a2enmod rewrite ssl alias
ln -sf /vagrant/files/d2ca.conf /etc/apache2/conf-enabled/d2ca.conf
a2ensite default-ssl.conf
service apache2 restart

