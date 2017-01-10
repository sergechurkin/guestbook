<?php

$loader = require( __DIR__ . '/vendor/autoload.php' );
//$loader->addPsr4( 'sergechurkin\cform\\', __DIR__ . '/vendor/sergechurkin/cform/' );
$loader->addPsr4( 'guestbook\\', __DIR__ . '/src/' );

use guestbook\Controller;

(new Controller())->run();

