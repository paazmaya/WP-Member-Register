<?php


function download_file()
{
	// get should contain download: dir / basename
	
	
	header("Content-Type: application/force-download");
	header("Content-Disposition: attachment; filename=myfile.txt");
	header("Content-length: ".(string) mb_strlen($payload, '8bit') );
	header("Expires: ".gmdate("D, d M Y H:i:s", mktime(date("H")+2, date("i"), date("s"), date("m"), date("d"), date("Y")))." GMT");
	header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");
}
