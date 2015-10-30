# NDN Wordpress Plugin

## Info

Wordpress Plugin for Inform videos for the admin post editor. Allows user to insert Inform videos in their posts.

MAMP:

- Webserver: Apache
    - `Port 80`
- MySQL
    - `Port 8899`

Wordpress:

- Version: `4.2.2`


## Installation

Install [MAMP](https://www.mamp.info/en/)

Download [Wordpress](https://wordpress.org/download/)

Unzip the Wordpress zip file and set the `Document Root` to the Wordpress Install directory. Press `Start Servers` on MAMP and you can see changes on `localhost`.

## Debugging

In development, debugging is on by default. Go to `/wp-content` folder in your Wordpress site and `tail -f debug.log` to follow your server logs.

## Testing

Go to phpMyAdmin from MAMP. `http://localhost/MAMP/ ▸ Tools ▸ phpMyAdmin`. Create a database called `phpunit_test`. You can keep the default setting of `Collation`.

Then, bootstrap the tests.

```shell
# go to plugin directory
bash bin/install-wp-tests.sh phpunit_test <db_user> <db_pass> "localhost:/Applications/MAMP/tmp/mysql/mysql.sock" latest
```

Credits to [this Wordpress Stack Exchange Question](http://wordpress.stackexchange.com/questions/97430/how-can-i-debug-my-database-connection-for-unit-testing) for answering my localhost MySQL connection issue when using MAMP.

Replace `db_user` and `db_pass` with the username and password which you set-up your database when setting up MAMP.

PHPUnit configuration settings can be found in `phpunit.xml`.

Install `phpunit` if you haven't done so already. Run your tests by running `phpunit`. The following is the installtion process for UNIX systems. If you're on PC, follow the installation process on the [PHPUnit website](https://phpunit.de/manual/current/en/installation.html).

``` shell
wget https://phar.phpunit.de/phpunit.phar
chmod +x phpunit.phar
sudo mv phpunit.phar /usr/local/bin/phpunit
phpunit --version

# Run tests in main directory
phpunit
```

## Vagrant

The project can be compiled into its own vm on your local machine using Vagrant. Does not need MAMP as it runs its own server and database store.

```shell
cd vagrant
vagrant up
# Wait for build
```

Go to `ndnplugintestdemo.dev` to start using the dev sandbox.

### Using VM for Testing

Follow similar instructions from the `Testing` section. Use the bash script to implement the test suite and start testing using `phpunit`.
