<?php
/* Copyright (c) 2013 Michael LeSane
 *
 * Three implementations of the fibonacci function to
 * demonstrate the applications of the future library. 
*/

include 'future.php';

function fib_serial($x)
{
	return ($x<2)?$x:fib_serial($x-1)+fib_serial($x-2);
}

function fib_parallel($x)
{
	if($x<2) return $x;

	$a = future::start("fib_parallel",[$x-1]);
	$b = future::start("fib_parallel",[$x-2]);

	return future::wait($a) + future::wait($b);
}

function fib_future($x)
{
	if($x<2) return future::ready($x);

	$a = fib_future($x-1);
	$b = fib_future($x-2);

	return (future::after
	(
		function($l,$r)
		{
			return $l+$r;
		},
		$a,$b
	));
}

echo
"------------------
Fibonacci, Serial:
------------------\n";
for($i = 0; $i < 10; $i++)
{
	$time_start = microtime(true);
	echo "f($i) = ".fib_serial($i)." (";
	$time_end = microtime(true);
	$diff = $time_end-$time_start;
	echo $diff." seconds)\n";
}
echo
"------------------
Fibonacci, Parallel (No Future Operations):
------------------\n";
for($i = 0; $i < 10; $i++)
{
	$time_start = microtime(true);
	echo "f($i) = ".future::wait(fib_future($i))." (";
	$time_end = microtime(true);
	$diff = $time_end-$time_start;
	echo $diff." seconds)\n";
}
echo
"------------------
Fibonacci, Parallel (Future Operations):
------------------\n";
for($i = 0; $i < 10; $i++)
{
	$time_start = microtime(true);
	echo "f($i) = ".future::wait(fib_future($i))." (";
	$time_end = microtime(true);
	$diff = $time_end-$time_start;
	echo $diff." seconds)\n";
}

