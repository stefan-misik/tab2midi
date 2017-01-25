<?php
$dlzka=45;
$strun=6;
$tabov=3;

class midi
 {  
   var $cursor;
   var $mididata;
   var $mthd;
   var $trks;
   var $trkcur;
   var $evt;

   function midi($data = "")
   {
    if($data)
     {
      $this->mididata=$data;
      $this->readhd();
     }
    }
   
   function createhd($tracks,$division)
    {
     if($tracks > 1){$format="1";} else {$format="0";}
     $this->cursor=0;
     $this->mthd["format"]=$format;
     $this->mthd["tracks"]=$tracks;
     $this->mthd["division"]=$division;
     $this->mididata="MThd".$this->hex2str(dechex(6),4).$this->hex2str(dechex($format),2).$this->hex2str(dechex($tracks),2).$this->hex2str(dechex($division),2);
     $this->cursor=strlen($this->mididata);
    }
 
   function addmeta($id,$co,$value="")
    {
     $len=false;
     if($co=="tempo"){$co="51"; $value=dechex($value);$len=3;}
     else if($co=="takt"){$co="58"; $value=dechex($value);$len=4;}
     else if($co=="text"){$co="01";$value=$this->str2hex($value);}
     else if($co=="copyright"){$co="02";$value=$this->str2hex($value);}
     else if($co=="meno"){$co="03";$value=$this->str2hex($value);}
     else if($co=="nastroj"){$co="04";$value=$this->str2hex($value);}
     else if($co=="text_piesne"){$co="05";$value=$this->str2hex($value);}
     else if($co=="marker"){$co="06";$value=$this->str2hex($value);}
     else if($co=="koniec"){$co="2f"; $value="";}
     else {return false;}
     if($len)
      {
       $this->addtotrk($id, chr("0").$this->hex2str("ff").$this->hex2str($co).$this->hex2varlen($len).$this->hex2str($value,$len));
      }
     else
      {
       if($value){$value=$this->hex2str($value);}
       $this->addtotrk($id, chr("0").$this->hex2str("ff").$this->hex2str($co).$this->hex2varlen(dechex(strlen($value))).$value);
      }
    }

   function note_on($id,$delt,$ch,$key,$vel)
    {
     $delt=dechex($delt);
     $key=dechex($key);
     $vel=dechex($vel);
     $ch=$this->chkmididata($ch,15);
     $key=$this->chkmididata($key,127);
     $vel=$this->chkmididata($vel,127);
     $this->addtotrk($id, $this->hex2varlen($delt).$this->hex2str("9".$ch).$this->hex2str($key).$this->hex2str($vel));
     $ret[0]=$id;
     $ret[1]=$ch;
     $ret[2]=$key;
     return $ret;
    }
   
   function note_off($delt,$res,$vel=127)
    {
     $vel=dechex($vel);
     $vel=$this->chkmididata($vel,127);
     $this->addtotrk($res[0], $this->hex2varlen($delt).$this->hex2str("8".$res[1]).$this->hex2str($res[2]).$this->hex2str($vel));
    }

   function instch($id,$ch,$c)
    {
     $c=dechex($c);
     $ch=$this->chkmididata($ch,15);
     $c=$this->chkmididata($c,127);
     $this->addtotrk($id, $this->hex2varlen(0).$this->hex2str("c".$ch).$this->hex2str($c));
    }
  
   function chkmididata($dat,$hi=127,$lo=0)
    {
     $tmp=hexdec($dat);
     if($tmp > $hi){return dechex($hi);}
     else if($tmo<$lo){return dechex($lo);}
     else {return $dat;}
    }

   function writetrks()
    {
     for($i=0;$i<$this->mthd["tracks"];$i++)
      {
       $this->addmeta($i,"koniec");
       $this->mididata.="MTrk".$this->hex2str(dechex(strlen($this->trks[$i])),4).$this->trks[$i];
      }
    }
    
   function addtotrk($id,$co)
    {
     $this->trks[$id].=$co;
    }
   
   function sendmidi()
    {
     $this->writetrks();
     return $this->mididata;
    }

  function jumpcur($jumpto)
   {
    $this->cursor=$jumpto;
   }

  function read($to)
   {
    $dat=substr($this->mididata,$this->cursor, $to);
    $this->cursor += $to;
    return $dat;
   }
  
  function readr($st,$ln)
   {
    return substr($this->mididata,$st, $ln);
   }
  
  function str2hex($str)
   {
    for($i=0;$i<strlen($str);$i++){$tmp.=str_pad(dechex(ord($str{$i})),2, '0', STR_PAD_LEFT);}
    return$tmp;
   }

  function hex2str($tmp,$vel=-1)
   {
    if(strlen($tmp) % 2){$tmp = "0" . $tmp;}
    for($i=0;$i<strlen($tmp);$i+=2)
     {
      $tmp2.=chr(hexdec($tmp{$i} . $tmp{$i+1}));
     }
    if(strlen($tmp2) > $vel and $vel != -1){return false; }
    else if($vel == -1 or strlen($tmp2) == $vel) {return $tmp2; }
    else if(strlen($tmp2) < $vel and $vel != -1) {return str_pad($tmp2,$vel, chr(0), STR_PAD_LEFT);}
   }
  
   function readhd()
   {
    $this->cursor=0;
    $this->mthd["name"]=$this->read(4);
    $this->mthd["len"]=hexdec($this->str2hex($this->read(4)));
    $this->mthd["format"]=hexdec($this->str2hex($this->read(2)));
    $this->mthd["tracks"]=hexdec($this->str2hex($this->read(2)));
    $this->mthd["division"]=hexdec($this->str2hex($this->read(2)));
    $this->loadtrks();
   }
  
  function loadtrks()
   {
    for($i=0;$i<$this->mthd["tracks"];$i++)
     {
      if($this->read(4)=="MTrk")
       { 
        $leng=hexdec($this->str2hex($this->read(4)));
        $this->trks[$i]=$this->read($leng);
       }
      else
       { 
        $this->trks[$i]=false;
       }
       $this->trkcur[$i]=0;
      }
   } 

  function getvarlen($str)
   {
    for($i=0;$i<strlen($str);$i++)
     {
      if(ord($str{$i})<128){return $i+1;}
     }
   }

  function readtrk($idt,$to=false)
   {
    if($to)
     {
      $dat=substr($this->trks[$idt],$this->trkcur[$idt], $to);
      $this->trkcur[$idt] += $to;
      return $dat;
     }
    else
     {

      return substr($this->trks[$idt],$this->trkcur[$idt]);
     }
   }
  
  function hex2varlen($hexx)
   {
    if($hexx==0){return chr("0");}
    $tmp=$this->hexbin($hexx);
    $tmp=eregi_replace("^0+","",$tmp);
    if(strlen($tmp) % 7){$tmp=$this->lstr("0",7-(strlen($tmp)%7)).$tmp;}
    for($i=0;$i<strlen($tmp);$i+=7)
     {
      if($i==(strlen($tmp)-7)){$r="0";} 
      else{$r="1";}
      $tmp2.=chr(bindec($r.substr($tmp,$i,7)));
     }
    return $tmp2;
   }
  
  function varlen2hex($varlen)
   {
    for($i=0;$i<strlen($varlen);$i++)
     {
      $tmp.=substr(str_pad(decbin(ord($varlen{$i})),8, '0', STR_PAD_LEFT),1);
     }
    $tmp=$this->binhex($tmp);
    return $tmp;
   }

  function lstr($ch,$x)
   {
    for($i=0;$i<$x;$i++){$tmp.=$ch;}
    return $tmp;
   }

  function binhex($bi)
   {
    $b = Array("0000","0001","0010","0011","0100","0101","0110","0111","1000","1001","1010","1011","1100","1101","1110","1111");
    $h = Array("0","1","2","3","4","5","6","7","8","9","a","b","c","d","e","f");
    $bi=eregi_replace("^0+","",$bi);
    if(strlen($bi) % 8){$bi=$this->lstr("0",8-(strlen($bi)%8)).$bi;}
    for($i=0;$i<strlen($bi);$i+=4)
     {
      for($j=0;$j<16;$j++)
       {
        if(substr($bi,$i,4)==$b[$j]){$tmp.=$h[$j];}
       }
     }
    return $tmp;
   }

  function hexbin($he)
   {
    $b = Array("0000","0001","0010","0011","0100","0101","0110","0111","1000","1001","1010","1011","1100","1101","1110","1111");
    $h = Array("0","1","2","3","4","5","6","7","8","9","a","b","c","d","e","f");
    $he=eregi_replace("^0+","",$he);
    $he=strtolower($he);
    if(strlen($he) % 2){$he="0".$he;}
    for($i=0;$i<strlen($he);$i++)
     {
      for($j=0;$j<16;$j++)
       {
        if($he{$i}==$h[$j]){$tmp.=$b[$j];}
       }
     }
    return $tmp;
   }

   function ton2note($ton)
    {
     $tony = array_flip(array("c","cis","d","dis","e","f","fis","g","gis","a","as","h"));
     $ton=strtolower($ton);
     $okt=substr($ton,-1);
     $ton=substr($ton,0,-1);
     return 12*($okt+1)+$tony[$ton];
    }
}


if($_POST["tabok"])
{
$n = new midi();

$tun[0]=$n->ton2note("e4");
$tun[1]=$n->ton2note("h3");
$tun[2]=$n->ton2note("g3");
$tun[3]=$n->ton2note("d3");
$tun[4]=$n->ton2note("a2");
$tun[5]=$n->ton2note("e2");

$n->createhd(1,1390);
$n->addmeta(0,"tempo",$_POST["tempo"]);

$n->instch(0,0,25);

$tab=$_POST;
for($k=0;$k<$tabov;$k++)
{
for($i=0;$i<$dlzka;$i++)
 {
  for($j=0;$j<$strun;$j++)
   {
    if(is_numeric($tab["tab".$k."x".$j."x".$i]))
     {
      $hra[$j]=$n->note_on(0,0,0,$tun[$j]+$tab["tab".$k."x".$j."x".$i],127);
     }
   }

    if($hra){$n->note_off(400,$n->note_on(0,0,0,0,0));}

   for($j=0;$j<$strun;$j++)
    {
     if($hra[$j]){$n->note_off(0,$hra[$j]);};
    }
   $hra="";
 }
}


header("Content-Description: File Transfer");
header("Content-Disposition: attachment; filename=\"1.mid\"");
header("Content-Type: audio/midi");
echo $n->sendmidi();
die();
}
else
{
?>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=windows-1250">
    <title>Tab</title>
    <style>
      input.tab
        {
         border: none;
         Width: 20px;
         Text-align: center;
         Font: bold 12px monospace;
         Background-image: url('1.bmp');
        }

      input.nota
        {
         border: none;
         Width: 20px;
         Text-align: center;
         Font: 12px monospace;
        }
    </style>
    <script>
      
      var dlzka=(<?php echo $dlzka;?>-1);
      var strun=(<?php echo $strun;?>-1);
      var tabov=(<?php echo $tabov;?>-1);

      function getpole(idd)
       {
        return document.getElementById('tab'+idd);
       }

      function posun (namep,klav)
       {
        curt=namep.split("tab")[1].split("x")[0];
        curx=namep.split("tab")[1].split("x")[1];
        cury=namep.split("tab")[1].split("x")[2];
        if(isNaN(getpole(curt+"x"+curx+"x"+cury).value)){getpole(curt+"x"+curx+"x"+cury).value="";}
        if(klav==40 && curx == strun && curt<tabov){curx=0;curt++;getpole(curt+"x"+curx+"x"+cury).focus(); return false;}
        if(klav==38 && curx == 0 && curt>0){curx=strun;curt-=1;getpole(curt+"x"+curx+"x"+cury).focus(); return false;}
        if(klav==39 && cury < dlzka){cury++;getpole(curt+"x"+curx+"x"+cury).focus(); return false;}
        if(klav==37 && cury > 0){cury-=1;getpole(curt+"x"+curx+"x"+cury).focus(); return false;}
        if(klav==40 && curx < strun){curx++;getpole(curt+"x"+curx+"x"+cury).focus(); return false;}
        if(klav==38 && curx > 0){curx-=1;getpole(curt+"x"+curx+"x"+cury).focus(); return false;}
        if(klav==8 && cury > 0 && getpole(curt+"x"+curx+"x"+cury).value.length==0){cury-=1;getpole(curt+"x"+curx+"x"+cury).focus(); return;}        
        if(klav==34 && curt<tabov){curt++;getpole(curt+"x"+curx+"x"+cury).focus(); return false;}
        if(klav==33 && curt>0){curt-=1;getpole(curt+"x"+curx+"x"+cury).focus(); return false;}
        
       }
    </script>
  </head>
  <body onload="getpole('0x0').focus();">
   <form method="POST">
   
    <?php
     for($k=0;$k<$tabov;$k++)
     {
     echo "<table cellpadding=0 cellspacing=0 border=0 style='border-left: 3px double black;'><tr>";
       
       for($j=0;$j<$dlzka;$j++)
        {
         echo "<td><input class='nota' type='text' maxlength='4' size='2' name='nota".$k."x".$j."' value='900'></td>";
        }
     echo "</tr>";
     for($i=0;$i<$strun;$i++)
      {
       echo "<tr>";
       for($j=0;$j<$dlzka;$j++)
        {
         echo "<td><input onKeyPress=\"posun(this.name,event.keyCode);\" class = 'tab' type='text' maxlength='2' size='2' id='tab".$k."x".$i."x".$j."' name='tab".$k."x".$i."x".$j."' value='".$_POST["tab".$k."x".$i."x".$j]."'></td>";
        }
       echo "</tr> \n";
      }
      echo "</table><br />\n";
      }
    ?>

   Tempo: <input type="text" name="tempo" value="500000" /><br />
   <input type="submit" value="Ok" name="tabok" />
   <input type="reset" value="Reset" />
   </form>
  </body>
</html>
<?php
}