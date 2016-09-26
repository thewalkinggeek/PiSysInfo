<?php
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
	header("Pragma: no-cache");

	function NumberWithCommas($in)
	{
		return number_format($in);
	}
	function  WriteToStdOut($text)
	{
		$stdout = fopen('php://stdout','w') or die($php_errormsg);
		fputs($stdout, "\n" . $text);
	}
	date_default_timezone_set('US/Eastern');
	
	$current_time = date('d M Y') . '<br />' . date('h:i:s T');
	$frequency = NumberWithCommas(file_get_contents("/sys/devices/system/cpu/cpu0/cpufreq/scaling_cur_freq") / 1000);
	$processor = str_replace("-compatible processor", "", explode(": ", chop(file("/proc/cpuinfo")[1]))[1]);
	$cpu_temperature = round(file_get_contents("/sys/class/thermal/thermal_zone0/temp") / 1000, 1);
	//$RX = exec("ifconfig eth0 | grep 'RX bytes'| cut -d: -f2 | cut -d' ' -f1");
	//$TX = exec("ifconfig eth0 | grep 'TX bytes'| cut -d: -f3 | cut -d' ' -f1");
	$system = chop(file_get_contents("/proc/sys/kernel/ostype"));
	$host = chop(file_get_contents("/proc/sys/kernel/hostname"));
	$kernel = chop(file_get_contents("/proc/sys/kernel/osrelease"));
	
	//Uptime
	$uptime_array = explode(" ", file_get_contents("/proc/uptime"));
	$seconds = round($uptime_array[0], 0);
	$minutes = $seconds / 60;
	$hours = $minutes / 60;
	$days = floor($hours / 24);
	$hours = sprintf('%02d', floor($hours - ($days * 24)));
	$minutes = sprintf('%02d', floor($minutes - ($days * 24 * 60) - ($hours * 60)));
	if ($days == 0):
		$uptime = $hours . ":" .  $minutes . " (hh:mm)";
	elseif($days == 1):
		$uptime = $days . " day, " .  $hours . ":" .  $minutes . " (hh:mm)";
	else:
		$uptime = $days . " days, " .  $hours . ":" .  $minutes . " (hh:mm)";
	endif;
	
	//CPU Usage
	$output1 = null;
	$output2 = null;
	//First sample
	$output1 = file("/proc/stat");
	//Sleep before second sample
	sleep(1);
	//Second sample
	$output2 = file("/proc/stat");
	$cpuload = 0;
	for ($i=0; $i < 1; $i++)
	{
		//First row
		$cpu_stat_1 = explode(" ", $output1[$i+1]);
		$cpu_stat_2 = explode(" ", $output2[$i+1]);
		//Init arrays
		$info1 = array("user"=>$cpu_stat_1[1], "nice"=>$cpu_stat_1[2], "system"=>$cpu_stat_1[3], "idle"=>$cpu_stat_1[4]);
		$info2 = array("user"=>$cpu_stat_2[1], "nice"=>$cpu_stat_2[2], "system"=>$cpu_stat_2[3], "idle"=>$cpu_stat_2[4]);
		$idlesum = $info2["idle"] - $info1["idle"] + $info2["system"] - $info1["system"];
		$sum1 = array_sum($info1);
		$sum2 = array_sum($info2);
		//Calculate the cpu usage as a percent
		$load = (1 - ($idlesum / ($sum2 - $sum1))) * 100;
		$cpuload += $load;
	}
	$cpuload = round($cpuload, 1); //One decimal place
	
	//Memory Utilisation
	$meminfo = file("/proc/meminfo");
	for ($i = 0; $i < count($meminfo); $i++)
	{
		list($item, $data) = split(":", $meminfo[$i], 2);
		$item = trim(chop($item));
		$data = intval(preg_replace("/[^0-9]/", "", trim(chop($data)))); //Remove non numeric characters
		switch($item)
		{
			case "MemTotal": $total_mem = $data; break;
			case "MemFree": $free_mem = $data; break;
			case "SwapTotal": $total_swap = $data; break;
			case "SwapFree": $free_swap = $data; break;
			case "Buffers": $buffer_mem = $data; break;
			case "Cached": $cache_mem = $data; break;
			default: break;
		}
	}
	$used_mem = $total_mem - $free_mem;
	$used_swap = $total_swap - $free_swap;
	$percent_free = round(($free_mem / $total_mem) * 100);
	$percent_used = round(($used_mem / $total_mem) * 100);
	$percent_swap = round((($total_swap - $free_swap ) / $total_swap) * 100);
	$percent_swap_free = round(($free_swap / $total_swap) * 100);
	$percent_buff = round(($buffer_mem / $total_mem) * 100);
	$percent_cach = round(($cache_mem / $total_mem) * 100);
	$used_mem = NumberWithCommas($used_mem);
	$used_swap = NumberWithCommas($used_swap);
	$total_mem = NumberWithCommas($total_mem);
	$free_mem = NumberWithCommas($free_mem);
	$total_swap = NumberWithCommas($total_swap);
	$free_swap = NumberWithCommas($free_swap);
	$buffer_mem = NumberWithCommas($buffer_mem);
	$cache_mem = NumberWithCommas($cache_mem);
	
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title>Raspberry Pi System Information</title>
		<style type="text/css">
			body {
				margin:3px 0px; padding:0px;
				text-align:center;
				}
			#Content {
				width:320px;
				margin:0px auto;
				text-align:left;
				padding:15px;
				border:1px dashed #333;
				background-color:#eee;
			}
			a {
				color:black;
				padding-top:5px;
				display:block;
			}
			a:hover {
				text-decoration:none;
			}
			td {
				font-family:"DejaVu Sans",Arial,Helvetica,sans-serif;
				font-size:12px;
				vertical-align:top;
				padding-left:2px;
				padding-right:2px;
				background:#FFFFFF;
			}
			p {
				font-family:"DejaVu Sans", Arial, Helvetica, sans-serif;
				font-size:12px;
			}
			h1 {
				font-family:"DejaVu Sans", Arial, Helvetica, sans-serif;
				font-size:18px;
				text-align:center;
			}
			td.center {
				text-align:center;
			}
			td.head {
				font-weight:bold;
				padding-top:3px;
				padding-bottom:3px;
			}
			td.right {
				text-align:right;
				padding-right:6px;
			}
			table {
				width: 320px; border-spacing:0;
				border-collapse:collapse;
			}
			html,body,.darkbackground {
				background:#CCCCCC;
			}
			body {
				color:#000000;
			}
			td.column1 {
				width:60px;
			}
			td.column3 {
				width:120px;
			}
			td.column4 {
				width:30px;
			}
			div#bar1, div#bar2, div#bar3, div#bar4, div#bar5, div#bar6 {
				height:12px;
				width:0px;
				transition:width 2s;
				<?php
					$agent = "";
					if(isset($_SERVER['HTTP_USER_AGENT']))
					{
						$agent = $_SERVER['HTTP_USER_AGENT'];
					}
					if(strlen(stristr($agent,"applewebkit")) > 0 ) echo "\n\t\t\t\t-webkit-transition:width 2s;\n";
					else if(strlen(stristr($agent,"gecko")) > 0 ) echo "\n\t\t\t\t-moz-transition:width 2s;\n";
					else if(strlen(stristr($agent,"opera")) > 0 ) echo "\n\t\t\t\t-o-transition:width 2s;\n";
				?>
			}
			div#bar1 { background-color:#D78787; }
			div#bar2 { background-color:#AFD787; }
			div#bar3 { background-color:#F7F7AF; }
			div#bar4 { background-color:#87AFD7; }
			div#bar5 { background-color:#D7AFD7; }
			div#bar6 { background-color:#AFD7D7; }
		</style>
		<script type="text/javascript">
			function updateText(objectId, text)
			{
				document.getElementById(objectId).textContent = text;
			}
			function updateHTML(objectId, html)
			{
				document.getElementById(objectId).innerHTML = html;
			}
			function updateDisplay()
			{
<?php
				echo "\n\t\t\t\tupdateText(\"host\",\"$host\");";
				echo "\n\t\t\t\tupdateHTML(\"time\",\"$current_time\");";
				echo "\n\t\t\t\tupdateText(\"kernel\",\"$system\" + \" \" + \"$kernel\");";
				echo "\n\t\t\t\tupdateText(\"processor\",\"$processor\");";
				echo "\n\t\t\t\tupdateText(\"freq\",\"$frequency\" + \"MHz\");";
				echo "\n\t\t\t\tupdateText(\"cpuload\",\"$cpuload%\");";
				echo "\n\t\t\t\tupdateHTML(\"cpu_temperature\",\"$cpu_temperature\" + \"&#x2103;\");";
				echo "\n\t\t\t\tupdateText(\"uptime\",\"$uptime\");";

				echo "\n\t\t\t\tupdateText(\"total_mem\",\"$total_mem\" );";
				echo "\n\t\t\t\tupdateText(\"used_mem\",\"$used_mem\" );";
				echo "\n\t\t\t\tupdateText(\"percent_used\",\"$percent_used%\");";
				echo "\n\t\t\t\tupdateText(\"free_mem\",\"$free_mem\" );";
				echo "\n\t\t\t\tupdateText(\"percent_free\",\"$percent_free%\");";
				echo "\n\t\t\t\tupdateText(\"buffer_mem\",\"$buffer_mem\" );";
				echo "\n\t\t\t\tupdateText(\"percent_buff\",\"$percent_buff%\");";
				echo "\n\t\t\t\tupdateText(\"cache_mem\",\"$cache_mem\" );";
				echo "\n\t\t\t\tupdateText(\"percent_cach\",\"$percent_cach%\");";

				echo "\n\t\t\t\tupdateText(\"total_swap\",\"$total_swap\" );";
				echo "\n\t\t\t\tupdateText(\"used_swap\",\"$used_swap\" );";
				echo "\n\t\t\t\tupdateText(\"percent_swap\",\"$percent_swap%\");";
				echo "\n\t\t\t\tupdateText(\"free_swap\",\"$free_swap\" );";
				echo "\n\t\t\t\tupdateText(\"percent_swap_free\",\"$percent_swap_free%\");\n";
?>
				document.getElementById("bar1").style.width = "<?php echo $percent_used; ?>px";
				document.getElementById("bar2").style.width = "<?php echo $percent_free; ?>px";
				document.getElementById("bar3").style.width = "<?php echo $percent_buff; ?>px";
				document.getElementById("bar4").style.width = "<?php echo $percent_cach; ?>px";
				document.getElementById("bar5").style.width = "<?php echo $percent_swap; ?>px";
				document.getElementById("bar6").style.width = "<?php echo $percent_swap_free; ?>px";
			}
		</script>
	</head>
	<body onload="Javascript: updateDisplay();">
	<div id="Content">
	<h1>Pinix @ Phoenix Gateway</br>(iam.uberleet.com)</h1><h1>System Information</h1>
		<table>
			<tr>
				<td colspan="4" class="head center">General Info</td>
			</tr>
			<tr>
				<td colspan="2">Hostname</td>
				<td colspan="2" id="host"></td>
			</tr>
			<tr>
				<td colspan="2">System Time</td>
				<td colspan="2" id="time"></td>
			</tr>
			<tr>
				<td colspan="2">Kernel</td>
				<td colspan="2" id="kernel"></td>
			</tr>
			<tr>
				<td colspan="2">Processor</td>
				<td colspan="2" id="processor"></td>
			</tr>
			<tr>
				<td colspan="2">CPU Frequency</td>
				<td colspan="2" id="freq"></td>
			</tr>
			<tr>
				<td colspan="2">CPU Load</td>
				<td colspan="2" id="cpuload"></td>
			</tr>
			<tr>
				<td colspan="2">CPU Temperature</td>
				<td colspan="2" id="cpu_temperature"></td>
			</tr>
			<tr>
				<td colspan="2">Uptime</td>
				<td colspan="2" id="uptime"></td>
			</tr>
			<tr>
				<td colspan="4" class="darkbackground">&nbsp;</td>
			</tr>
			<tr>
				<td colspan="2" class="head right">Memory:</td>
				<td colspan="2" class="head" id="total_mem"><?php echo $total_mem . " kB";?></td>
			</tr>
			<tr>
				<td class="column1">Used</td>
				<td class="right" id="used_mem"></td>
				<td class="column3"><div id="bar1">&nbsp;</div></td>
				<td class="right column4" id="percent_used"></td>
			</tr>
			<tr>
				<td>Free</td>
				<td class="right" id="free_mem"></td>
				<td><div id="bar2"></div></td>
				<td class="right" id="percent_free"></td>
			</tr>
			<tr>
				<td>Buffered</td>
				<td class="right" id="buffer_mem"></td>
				<td><div id="bar3"></div></td>
				<td class="right" id="percent_buff"></td>
			</tr>
			<tr>
				<td>Cached</td>
				<td class="right" id="cache_mem"></td>
				<td><div id="bar4"></div></td>
				<td class="right" id="percent_cach"></td>
			</tr>
			<tr>
				<td colspan="4" class="darkbackground">&nbsp;</td>
			</tr>
			<tr>
				<td colspan="2" class="head right">Swap:</td>
				<td colspan="2" class="head" id="total_swap"></td>
			</tr>
			<tr>
				<td>Used</td>
				<td class="right" id="used_swap"></td>
				<td><div id="bar5"></div></td>
				<td class="right" id="percent_swap"></td>
			</tr>
			<tr>
				<td>Free</td>
				<td class="right" id="free_swap"></td>
				<td><div id="bar6"></div></td>
				<td class="right" id="percent_swap_free"></td>
			</tr>
		</table>
		<table><tr><td colspan="4" class="darkbackground">&nbsp;</td>
			</tr></table><table><tr>
				<td colspan="2" class="head center">Current Services</td>
	
			</tr></table><table><td colspan="4" class="left">Apache<br><br>SSH<br><br>Plex Media Server<br><br>Kodi<br><br></tr></table>
<table>
			<tr>
				<td class="right darkbackground"><center><a href="javascript:location.reload(true);" title="Refresh">Refresh</a></center></td>
			</tr>
			<tr>
				<td class="right darkbackground"><br><center><i>This system and network are monitored</i></center></td>
			</tr>
		</table>
	</div>
	</body>
</html>


