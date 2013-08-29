<?php
include_once dirname ( __FILE__ ) . '/../core/Thread.php';
 
// test to see if threading is available
if( ! Thread::available() ) {
    die( 'Threads not supported' );
}
 
// function to be ran on separate threads
function paralel( $_limit, $_name ) {
    for ( $index = 0; $index < $_limit; $index++ ) {
        echo 'Now running thread ' . $_name . PHP_EOL;
        sleep( 1 );
    }
}
 
// create 2 thread objects
$t1 = new Thread( 'paralel' );
$t2 = new Thread( 'paralel' );
 
// start them
$t1->start( 10, 't1' );
$t2->start( 10, 't2' );
 
// keep the program running until the threads finish
while( $t1->isAlive() && $t2->isAlive() ) {
 
}
?>