<?php
$addr = '0xeefd4e236dfac8f3e4f76890600ac41cb2eb6286';
$amount = "?amount=50";
$kind = htmlspecialchars("|") . "tp3";
$kind1 = htmlspecialchars("|") . "etp3";
$kind2 = "&kind=tp3";
$kind3 = htmlspecialchars("_") . "20111919172342A9B1";

$qr1 = $addr;
$qr2 = $qr1 . $amount;
$qr3_1 = $qr2 . $kind;
$qr3_2 = $qr2 . $kind1;
$qr3_3 = $qr2 . $kind2;
$qr4_1 = $qr2 . $kind1 . $kind3;

?>
<style type="text/css">
* { font-size: 19pt; text-align: center; }
.title { padding-top: 200px; font-weight: bold; }
.qr_text { padding-bottom: 120px; }
</style>

<center>

<div class="title">< 1 ></div>
<div class="qr_code"><img src="https://chart.googleapis.com/chart?cht=qr&chs=400x400&chl=<?=urlencode($qr1)?>" /></div>
<div class="qr_text"><?=$qr1?></div>


<div class="title">< 2 ></div>
<div class="qr_code"><img src="https://chart.googleapis.com/chart?cht=qr&chs=400x400&chl=<?=urlencode($qr2)?>" /></div>
<div class="qr_text"><?=$qr2?></div>


<div class="title">< 3-0 ></div>
<div class="qr_code"><img src="https://chart.googleapis.com/chart?cht=qr&chs=400x400&chl=<?=urlencode($qr2)?>|" /></div>
<div class="qr_text"><?=$qr2?>|</div>


<div class="title">< 3-1 ></div>
<div class="qr_code"><img src="https://chart.googleapis.com/chart?cht=qr&chs=400x400&chl=<?=urlencode($qr3_1)?>" /></div>
<div class="qr_text"><?=$qr3_1?></div>


<div class="title">< 3-2 ></div>
<div class="qr_code"><img src="https://chart.googleapis.com/chart?cht=qr&chs=400x400&chl=<?=urlencode($qr3_2)?>" /></div>
<div class="qr_text"><?=$qr3_2?></div>


<div class="title">< 3-3 ></div>
<div class="qr_code"><img src="https://chart.googleapis.com/chart?cht=qr&chs=400x400&chl=<?=urlencode($qr3_3)?>" /></div>
<div class="qr_text"><?=$qr3_3?></div>


<div class="title">< 4-1 ></div>
<div class="qr_code"><img src="https://chart.googleapis.com/chart?cht=qr&chs=400x400&chl=<?=urlencode($qr4_1)?>" /></div>
<div class="qr_text"><?=$qr4_1?></div>


</center>
