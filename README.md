# alttp-bingo

Bingo Generator for A Link to the Past Speedrunners

Much of the frontend code has been taken from the [SpeedRunsLive bingo generators](https://github.com/gustafsonk/SRL/tree/master/srl/pages/tools), specifically the [OoT-Bingo](https://github.com/giuocob/OoT-Bingo) generator. The generator itself has been ported to PHP/MySQL with the idea being to support more advanced features and filters for the board generation in the future.

## Feature Ideas

### Bingo

- Add tooltips for information on the goal itself (ways to achieve it, any other metadata)

### Admin

- Add field for goal notes

## Installation

- Create a MySQL database and run `install/install.sql`
- Upload all files to host
- Copy `inc/db.php.template` to `inc/db.php` and edit the `$db_config` variable to match your environment
- Set up your vhost for apache (or configure your desired web server with PHP support) and point your domain there
