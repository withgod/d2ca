# Desitny2 Clan Activity List

## install

for development

```
git git@github.com:withgod/d2ca.git
cd d2ca
cp ./.env.sample ./.env
vi ./.env # https://www.bungie.net/en/Application
vagrant up
vagrant ssh
cd /vagrant
php -r "readfile('https://getcomposer.org/installer');" | php
./composer.phar install
cat ./files/create.sql | mysql -uroot -ppassword
open https://127.0.0.1/d2ca/ # chrome://flags/#allow-insecure-localhost
```

## link

* https://github.com/richard4339/destiny2-php
  * https://github.com/withgod/destiny2-php
* https://www.bungie.net/en/Application
* https://bungie-net.github.io/multi/
