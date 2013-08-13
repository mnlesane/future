<?php
/* Copyright (c) 2013 Michael LeSane
 *
 * "future", a simple asynchronous parallelization library written in PHP.
 *
 * Derived in part from PLEAC-PHP code that may be found here:
 *  http://pleac.sourceforge.net/pleac_php/processmanagementetc.html
 *
 * which is released under the GNU Free Documentation License:
 *  https://www.gnu.org/copyleft/fdl.html
*/

class future
{
	//TODO: Safeguards and type safety.

	static function rnd()
	{
		$out = (rand(0,1000000000)/1000000000);
		return($out);
	}

	static $substitutes = [];

	function nothing($args)
	{
		return $args;
	}
	function start($f,$args,$serial = 0)
	{
		if(!function_exists("pcntl_fork")||$serial == 1) //For browsers...
			return future::ready(call_user_func_array($f,$args));

		$sockets = array();
		if(!socket_create_pair(AF_UNIX,SOCK_STREAM,0,$sockets)) 
			die(socket_strerror(socket_last_error()));

		list($reader, $writer) = $sockets;

		$pid = pcntl_fork();
		if($pid == -1) die('cannot fork');
		elseif($pid)
		{
			socket_close($writer);
			return array($pid,$reader);
		}
		else
		{
			socket_close($reader);
			$result = call_user_func_array($f,$args);
			$str = serialize($result);
			$line = sprintf(base64_encode($str)."\n", getmypid());
			if(!socket_write($writer,$line,strlen($line)))
			{
				socket_close($writer);
				die(socket_strerror(socket_last_error()));
			}
			socket_close($writer); // this will happen anyway
			exit(0);
		}
	}

	function socket_read_n($socket,$type)
	{
		$result = "";
		while (($currentByte = socket_read($socket, 1, $type)) != "") {
		    $result .= $currentByte;
		}
		return $result;
	}

	function wait($info,$serial = 0)
	{
		if(!function_exists("pcntl_fork")||$serial == 1) //For browsers...
			return future::$substitutes[$info];
		$line = future::socket_read_n($info[1],PHP_BINARY_READ);
		$out = unserialize(base64_decode($line));
		pcntl_waitpid($info[0], $status);
		socket_close($info[1]);
		return $out;
	}

	function running($info)
	{
		return future::check($info);
	}

	function terminated($info)
	{
		return !future::check($info);
	}

	function check($info) //1 if running, 0 if not
	{
		if(!is_array($info)) return 0;
		$pid = $info[0];
		exec("ps -A -o pid,s | grep " . escapeshellarg($pid), $output);
		if (count($output) && preg_match("~(\d+)\s+(\w+)$~", trim($output[0]), $m))
		if (in_array(trim($m[2]) /*status*/, array("D","R","S"))) return 1;
		return 0;
	}
	function ready($data)
	{
		return future::start("future::nothing",[$data]);
	}

	function after($lambda)
	{
		$args = func_get_args();
		array_shift($args);
		$f = function($f,$args)
		{
			foreach($args as $i=>$v)
			{
				$args[$i] = future::wait($v);
			}
			return call_user_func_array($f,$args);
		};
		return future::start($f,[$lambda,$args]);
	}
}
?>
