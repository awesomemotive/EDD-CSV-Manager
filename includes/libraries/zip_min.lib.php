<?php
/*
* Zip file creation class.
*
Based on :
http://www.zend.com/codex.php?id=535&single=1 By Eric Mueller <eric@themepark.com>
http://www.zend.com/codex.php?id=470&single=1 By Denis125 <webmaster@atlant.ru>
a patch from Peter Listiak <mlady@users.sourceforge.net> for last modified date and time of the compressed file
*
Official ZIP file format: 
http://www.pkware.com/appnote.txt
**
	MODIFIED AND PACKAGED BY JASON STOCKTON 22nd August 2009
	http://thewebdevelopmentblog.com/
	http://www.jasonstockton.com.au/
**
*/
class zipfile { var $datasec = array(); var $ctrl_dir = array(); var $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00"; var $old_offset = 0; function unix2DosTime($unixtime = 0) { $timearray = ($unixtime == 0) ? getdate() : getdate($unixtime); if ($timearray['year'] < 1980) { $timearray['year'] = 1980; $timearray['mon'] = 1; $timearray['mday'] = 1; $timearray['hours'] = 0; $timearray['minutes'] = 0; $timearray['seconds'] = 0; } return (($timearray['year'] - 1980) << 25) | ($timearray['mon'] << 21) | ($timearray['mday'] << 16) | ($timearray['hours'] << 11) | ($timearray['minutes'] << 5) | ($timearray['seconds'] >> 1); } function addFile($data, $name, $time = 0) { $name = str_replace('\\', '/', $name); $dtime = dechex($this->unix2DosTime($time)); $hexdtime = '\x' . $dtime[6] . $dtime[7] . '\x' . $dtime[4] . $dtime[5] . '\x' . $dtime[2] . $dtime[3] . '\x' . $dtime[0] . $dtime[1]; eval('$hexdtime = "' . $hexdtime . '";'); $fr = "\x50\x4b\x03\x04"; $fr .= "\x14\x00"; $fr .= "\x00\x00"; $fr .= "\x08\x00"; $fr .= $hexdtime; $unc_len = strlen($data); $crc = crc32($data); $zdata = gzcompress($data); $zdata = substr(substr($zdata, 0, strlen($zdata) - 4), 2); $c_len = strlen($zdata); $fr .= pack('V', $crc); $fr .= pack('V', $c_len); $fr .= pack('V', $unc_len); $fr .= pack('v', strlen($name)); $fr .= pack('v', 0); $fr .= $name; $fr .= $zdata; $this -> datasec[] = $fr; $cdrec = "\x50\x4b\x01\x02"; $cdrec .= "\x00\x00"; $cdrec .= "\x14\x00"; $cdrec .= "\x00\x00"; $cdrec .= "\x08\x00"; $cdrec .= $hexdtime; $cdrec .= pack('V', $crc); $cdrec .= pack('V', $c_len); $cdrec .= pack('V', $unc_len); $cdrec .= pack('v', strlen($name) ); $cdrec .= pack('v', 0 ); $cdrec .= pack('v', 0 ); $cdrec .= pack('v', 0 ); $cdrec .= pack('v', 0 ); $cdrec .= pack('V', 32 ); $cdrec .= pack('V', $this -> old_offset ); $this -> old_offset += strlen($fr); $cdrec .= $name; $this -> ctrl_dir[] = $cdrec; } function file() { $data = implode('', $this -> datasec); $ctrldir = implode('', $this -> ctrl_dir); return $data . $ctrldir . $this -> eof_ctrl_dir . pack('v', sizeof($this -> ctrl_dir)) . pack('v', sizeof($this -> ctrl_dir)) . pack('V', strlen($ctrldir)) . pack('V', strlen($data)) . "\x00\x00"; } } 
 ?>