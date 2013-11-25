<?php
include 'future.php';
function vector($n)
{
	$out = [];
	for($i = 0; $i < $n; $i++) $out[] = future::rnd();
	return $out;
}

function dotproduct($a,$b)
{
	$out = 0;
	for($i = 0; $i < count($a); $i++)
		$out += $a[$i] * $b[$i];
	return $out;
}

function parallel_dotproduct($vector1,$vector2,$start,$stop,$thresh)
{
	$out = 0;
	if($stop-$start <= $thresh)
		if($stop-$start > 0)
			for($i = $start; $i <= $stop; $i++)
				$out += $vector1[$i] * $vector2[$i];
		else		$out = $vector1[$start] * $vector2[$start];
	else
	{
		$left = future::start("parallel_dotproduct",[$vector1,$vector2,$start,intval(($start+$stop)/2),$thresh]);
		$right = future::start("parallel_dotproduct",[$vector1,$vector2,intval(($start+$stop)/2)+1,$stop,$thresh]);
		$out = future::wait($left) + future::wait($right);
	}
	return $out;
}

//Length of vectors
$len = 4000000;

//Minimum threshold
$minthresh = $len/16;

//Initialization of vectors
echo "Initializing Vector 1...\n";
$a = vector($len);
echo "Initializing Vector 2...\n";
$b = vector($len);

//Serial Test
{
	$x = microtime(true);
	$c = dotproduct($a,$b);
	$y = microtime(true);
	$n = $y-$x;
	echo "Result: $c\n";
	echo "Serial: $n\n\n";
}

//Parallel Tests
for($thresh = $len*2; $thresh >= ceil($minthresh); $thresh /= 2)
{
	$x = microtime(true);
	$c = parallel_dotproduct($a,$b,0,count($a)-1,$thresh);
	$y = microtime(true);
	$m = $y-$x;
	echo "Result: $c\n";
	echo "Parallel: $m (t=$thresh)\n";
	$q = @$n/$m;
	echo "Ratio: $q\n\n";
}
?>
