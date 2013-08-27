<?php
include 'future.php';
function vector($n)
{
	return ($n)?array_merge([future::rnd()],vector($n-1)):[];
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

$len = 10000;

$a = vector($len);
$b = vector($len);

$x = microtime(true);
$c = dotproduct($a,$b);
$y = microtime(true);
$n = $y-$x;
echo "Serial: $n\n";

for($thresh = 1; $thresh <= $len*2; $thresh *= 2)
{
	$x = microtime(true);
	$c = parallel_dotproduct($a,$b,0,count($a)-1,$thresh);
	$y = microtime(true);
	$m = $y-$x;
	echo "Parallel: $m (t=$thresh)\n";
	$q = $n/$m;
	echo "Ratio: $q\n";
}
?>
