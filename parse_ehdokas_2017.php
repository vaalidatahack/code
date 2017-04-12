<?php

// Write Once, Read Never -tyyppisesti kirjoitettu scripti Wikidatassa jo olevien ehdokkaiden ja 
// vuoden 2017 kunnallisvaaleissa valittujen henkilöiden tietojen yhdistämiseen.
// Tulostustus on TSV:tä. 

function get_items() 
{

// Source: https://quarry.wmflabs.org/query/17950;
$file=file_get_contents("/home/zache/Downloads/quarry-16045-untitled-run148878.tsv");
$rows=explode("\n", $file);

/*
    [0] => Anna-Leena_Härkönen
    [1] => Q4178461
    [2] => Suomalaiset_elokuvakäsikirjoittajat,Suomalaiset_kirjailijat,Suomalaiset_näyttelijät,Suomalaiset_sanoittajat,Suomalaiset_televisiokäsikirjoittajat
    [3] => Vuonna_1965_syntyneet
    [4] => 
    [5] => Elävät_henkilöt
*/


$items=array();
foreach($rows as $k=>$row)
{
        if ($k==0) continue;
        if (trim($row)=="") continue;

        $cols=explode("\t", $row);
        $item=array();
        $item['wikidata_id']=$cols[1];
        $item['wikipedia_label']=$cols[0];
        $item['wikipedia_label_lower']=strtolower($cols[0]);

        if (preg_match("/Vuonna_([0-9]+)_syntyneet/", $cols[3], $m))
        {
                $item['wikipedia_birthyear']=$m[1];
        }
        elseif (trim($cols[3])!="")
        {
                print_r($cols);
                die("ERROR: Wikipedia birthyear");
        }

        if (preg_match("/Vuonna_([0-9]+)_kuolleet/", $cols[4], $m))
        {
                $item['wikipedia_deathyear']=$m[1];
        }
        elseif (trim($cols[4])!="")
        {
                print_r($cols);
                die("ERROR: Wikipedia deathyear");
        }

        if (trim($cols[5])=="Elävät_henkilöt") 
        {
                $item['wikipedia_alive']=1;
        }
        $items[$item['wikidata_id']]=$item;
}

function get_summary($title)
{
	$url="https://fi.wikipedia.org/api/rest_v1/page/summary/" . urlencode($title);
	$file=file_get_contents($url);
        $json=json_decode($file, true);

	return str_replace("\r", " ", str_replace("\n", "; ", $json['extract']));

}

$file=file_get_contents("/home/zache/Downloads/query.tsv");
$rows=explode("\n", $file);
foreach($rows as $k=>$row)
{
        if ($k==0) continue;
        if (trim($row)=="") continue;

        $cols=explode("\t", $row);

        if (preg_match("|http://www.wikidata.org/entity/(Q[0-9]+)|ism", $cols[1],$m))
        {
                $wikidata_id=$m[1];
        }
        else
        {
                print $row;

                print_r($cols);
                die("ERROR: Wikidata id");
        }
        $item=array();
        $item['wikidata_id']=$wikidata_id;

        if (isset($items[$wikidata_id])) $item=$items[$wikidata_id];
        $item['wikidata_label']=$cols[0];
        $item['wikidata_label_lower']=strtolower($cols[0]);
        $item['wikidata_birthyear']=$cols['5'];
        $item['wikidata_deathyear']=$cols['6'];

        $items[$wikidata_id]=$item;

}

return $items;
}

$items=get_items();

function get_wikidata_id_from_item($items, $first_name, $surname, $age)
{
	foreach ($items as $i)
	{
//		if (isset($i['wikipedia_alive']) && ($i['wikipedia_alive']!=1 $i['wikipedia_alive']!="")) continue;
		if (isset($i['wikidata_deathyear']) && $i['wikidata_deathyear']!="") continue;	
		if (isset($i['wikipedia_deathyear']) && $i['wikipedia_deathyear']!="") continue;	

		if (
		    (isset($i['wikipedia_label']) && preg_match("|.*?" . $first_name .".*?" . $surname ."\b|", $i['wikipedia_label'])) 
                    || (isset($i['wikidata_label']) && preg_match("|.*?" . $first_name .".*?" . $surname ."\b|", $i['wikidata_label']))
                )
		{
			if (
				(isset($i['wikipedia_birthyear'])    &&  ((2017-$i['wikipedia_birthyear'])==$age))
				|| (isset($i['wikipedia_birthyear']) &&  ((2017-$i['wikipedia_birthyear']+1)==$age))
				|| (isset($i['wikipedia_birthyear']) &&  ((2017-$i['wikipedia_birthyear']-1)==$age))
				|| (isset($i['wikidata_birthyear']) &&  ((2017-$i['wikidata_birthyear'])==$age))
				|| (isset($i['wikidata_birthyear']) &&  ((2017-$i['wikidata_birthyear']+1)==$age))
				|| (isset($i['wikidata_birthyear']) &&  ((2017-$i['wikidata_birthyear']-1)==$age))
				|| ((!isset($i['wikidata_birthyear'])) && (!isset($i['wikipedia_birthyear'])))
			)
			{

				if (
				    (isset($i['wikipedia_label']) && preg_match("|.*?" . $first_name ."\b.*?" . $surname ."\b|", $i['wikipedia_label'])) 
		                    || (isset($i['wikidata_label']) && preg_match("|.*?" . $first_name ."\b.*?" . $surname ."\b|", $i['wikidata_label']))
                		)
				{
					
				}
				else
				{
					print "DEBUG2\t" . $first_name ."\t" . $surname ."\t" .$i['wikipedia_label'] . "\t" . $i['wikidata_label'] . "\n";
				}


				$label="";
				if (isset($i['wikidata_label'])) $label .= $i['wikidata_label'] ." ";
				if (isset($i['wikipedia_label'])) $label .= $i['wikipedia_label'] ." ";

				print "#DEBUG\t" .$first_name . " " . $surname ."\t" . $label . "\t" . $age ."\n";
				return $i;
			}
		}

	}
	return "";
}

// SPARQL https://query.wikidata.org/#%23Cats%0ASELECT%20%3Fitem%20%3FitemLabel%20%3Fvaalit%20%3Fvotes_received%20%3Fseries_ordinal%20%3Fmunicipality_id%20%3Fage%20%3Fgender%0AWHERE%0A%7B%0A%09%3Fitem%20wdt%3AP3602%2Fwdt%3AP361%2a%20%20wd%3AQ640715%20.%0A%20%20%20%20%3Fitem%20p%3AP3602%20%3Fvaalit%20.%0A%20%20%20%20%3Fvaalit%20pq%3AP1111%20%3Fvotes_received%20.%0A%20%20%20%20%3Fvaalit%20pq%3AP1545%20%3Fseries_ordinal%20.%0A%20%20%20%20%3Fvaalit%20pq%3AP3629%20%20%3Fage%20.%20%20%0A%20%20%20%20%3Fvaalit%20pq%3AP1203%20%3Fmunicipality_id%20.%20%20%20%20%0A%20%20%20%20%3Fitem%20wdt%3AP21%20%3Fgender%0A%09SERVICE%20wikibase%3Alabel%20%7B%20bd%3AserviceParam%20wikibase%3Alanguage%20%22en%2Csv%2Cfi%22%20%7D%0A%7D

function read_wikidata_candidates()
{
        $file=file_get_contents("2012_valitut.json");
        $json=json_decode($file, true);

	return $json['results']['bindings'];
}

function get_candidate($candidates, $municipality_id, $series_ordinal, $age, $votes, $first_name, $surname, $gender)
{

	$ret_failback=array();
	$ret=array();
	$found=0;
	$found2=0;
	foreach ($candidates as $c)
	{

		if (                    
			$c['votes_received']['value']==$votes
			&& ($c['age']['value']==($age-5) || $c['age']['value']==($age-6) || $c['age']['value']==($age-4))                  
			&& preg_match("|" . $first_name ."|ism", $c['itemLabel']['value'])
			&& preg_match("|" . $surname ."|ism", $c['itemLabel']['value'])
		)
		{
			if ($found==0)
			{
				$ret=$c;
				$found=1;
			}		
			else
			{
				print_r($ret);
				print_r($c);
				die("MULTIPLE FOUND");
			}					
		}
		else if (
			$c['votes_received']['value']==$votes
			&& $c['municipality_id']['value']==$municipality_id
			&& ($c['age']['value']==($age-5) || $c['age']['value']==($age-6) || $c['age']['value']==($age-4))
			&& preg_match("|" . $first_name ."|ism", $c['itemLabel']['value'])
		)
		{
			if ($found2==0)
			{
				$ret_failback=$c;
				$found2=1;
			}
			else
			{
				$found2=2;
			}

		}


	}
	$qid="";
	if (isset($ret['item'])) 
	{
		$qid=str_replace("http://www.wikidata.org/entity/", "", $ret['item']['value']);
	}
	else if ($found2==1)
	{
		$qid=str_replace("http://www.wikidata.org/entity/", "", $ret_failback['item']['value']);
		print "FAILBACK: " . $qid ."\n";

	}
	return $qid;

}

$candidates=read_wikidata_candidates();


$file=utf8_encode(file_get_contents("kv-2017_aea_maa.csv"));
$rows=preg_split("/\n/", $file);

$n=0;
$ret_a=array();
$ret_k=array();
foreach ($rows as $row)
{
    
    if (trim($row)=="") continue;
    $cols=preg_split("/;/", $row);
    foreach ($cols as $k=>$v)
    {
       $cols[$k]=trim($v);
    }

    $a=array();
    $a['Vaalilaji']=$cols[0];                   // => K   
    $a['Vaalipiirinumero']=$cols[1];            // => 01
    $a['Kuntanumero']=$cols[2];                 // => 091
    $a['Tietuetyyppi']=$cols[3];                // => A
    $a['Äänestysaluetunnus']=$cols[4];          // => 001A
    $a['Vaalipiirin tunnus suomeksi']=$cols[5]; // => HEL
    $a['Vaalipiirin tunnus ruotsiksi']=$cols[6];// => HEL
    $a['Pysyvä puoluetunniste']=$cols[7];
  
    $a['Vakiopuoluenumero']=$cols[8];           // => 06
    $a['Listapuoluenumero']=$cols[9];           // => 01
    $a['Vaaliliittonumero']=$cols[10];           // => 01


    $a['Puolueen/ryhmän nimilyhenne suomeksi']=$cols[11];   // => VAS   
    $a['Puolueen/ryhmän nimilyhenne ruotsiksi']=$cols[12]; // => V�NST 
    $a['Puolueen/ryhmän nimilyhenne englanniksi']=$cols[13]; // => 


    $a['Ehdokasnumero']=$cols[14];                         // => 0002
    $a['alueen nimi suomeksi']=$cols[15];                  // => Kruununhaka A                           
    $a['alueen nimi ruotsiksi']=$cols[16];                 // => Kronohagen A                            
    $a['Henkilön etunimi']=$cols[17];                      // => Riku                                              
    $a['Henkilön sukunimi']=$cols[18];                     // => Ahola                                             
    $a['Sukupuoli']=$cols[19];                             // => 1
    $a['Ikä vaalipäivänä']=$cols[20];                      // => 031
    $a['Ammatti']=$cols[21];                               // => valtiotieteiden kandidaatti, asiakasneuvoja                                                         
    $a['Kotikunnan koodi']=$cols[22];                      // => 091
    $a['Kotikunnan nimi suomeksi']=$cols[23];              // => Helsinki                                
    $a['Kotikunnan nimi ruotsiksi']=$cols[24];             // => Helsingfors                             
    $a['Ehdokkaan äidinkieli']=$cols[25];

    $a['Europarlamentaarikko']=$cols[26];                  // =>  
    $a['Kansanedustaja']=$cols[27];                        // =>  
    $a['Kunnanvaltuutettu']=$cols[28];                     // =>  
    $a['Maakuntavaltuutettu']=$cols[29];                   // =>  
    $a['Vaalitapahtuman nimilyhenne 1. vert.vaali']=$cols[30];  // =>           
    $a['Ääniä 1. vertailuvaali']=$cols[31];                     // =>        
    $a['Ennakkoäänet lkm']=$cols[32];                           // => 0000000
    $a['Vaalipäivän äänet lkm']=$cols[33];                      // => 0000000
    $a['Äänet yhteensä lkm']=$cols[34];                         // => 0000000
    $a['Ennakkoäänet pros.']=$cols[35];                         // => 0000
    $a['Vaalipäivän äänet pros.']=$cols[36];                    // => 0000
    $a['Äänet yhteensä pros.']=$cols[37];                       // => 0000
    $a['Valintatieto']=$cols[38];                               // => 0
    $a['Vertausluku']=$cols[39];                             // => 0000000000
    $a['Sija']=$cols[40];
    $a['Lopullinen sija']=$cols[41];
    $a['Laskennan tila']=$cols[42];                             // => V
    $a['Laskentavaihe']=$cols[43];                              // => T
    $a['Tiedoston luontiaika']=$cols[44];                       // => 20121029180444
    $a['tmp']=$cols[45]; 


    $id=$a['Vaalilaji'] . "_" . $a['Vaalipiirinumero'] ."_" .  $a['Kuntanumero'] ."_" . $a['Ehdokasnumero'];
//    if ( $a['Henkilön etunimi'] != "Rauha") continue;

//    if ($n>10) continue;
    if ($a['Tietuetyyppi']=="K")
    {
        if (!isset($ret_k[$id]))
        {
		if ($a['Valintatieto']==1)
		{
			$a['wikidata_qid_debug']="none";
			$a['wikipedia_birthyear']="";
			$a['wikidata_birthyear']="";
			$a['summary']="";
			$n++;
		        $a['wikidata_qid']=get_candidate($candidates, ($a['Kuntanumero']*1), ($a['Ehdokasnumero']*1), ($a['Ikä vaalipäivänä']*1), ($a['Ääniä 1. vertailuvaali']*1),  $a['Henkilön etunimi'], $a['Henkilön sukunimi'], $a['Sukupuoli']);
			if ($a['wikidata_qid']!="")
				$a['wikidata_qid_debug']="ok";

			if ($a['wikidata_qid']=="")
			{

				$i=get_wikidata_id_from_item($items, $a['Henkilön etunimi'], $a['Henkilön sukunimi'], ($a['Ikä vaalipäivänä']*1));
				if (isset($i['wikidata_id']))
				{
					$a['wikidata_qid']=$i['wikidata_id'];
					if (isset($i['wikidata_birthyear']))
					{
						$a['wikidata_birthyear']=$i['wikidata_birthyear'];
					}
					if (isset($i['wikipedia_birthyear']))
					{
						$a['wikipedia_birthyear']=$i['wikipedia_birthyear'];
					}
					if (isset($i['wikipedia_label']))
					{
						$a['summary']=get_summary($i['wikipedia_label']);
					}

					if ($a['wikidata_qid']!="")
						$a['wikidata_qid_debug']="fuzzy";
				}
			}
        	        $ret_k[$id]=$a;
		}
        }
        else
        {
                print_r($ret_k[$id]);
                print_r($a);

                die("VIRHE: odottamaton tietuetyyppi");
        }
    }
    elseif ($a['Tietuetyyppi']=="A")
    {

        if (!isset($ret_a[$id]))
        {
                $ret_a[$id]=$a;
        }
        else
        {
                $skip_keys=array("Äänestysaluetunnus", "alueen nimi suomeksi", "alueen nimi ruotsiksi", "Tiedoston luontiaika", "Vaalipäivän äänet pros.", "Ennakkoäänet pros.", "Äänet yhteensä pros.", "Laskennan tila");
                $merge_keys=array("Ennakkoäänet lkm", "Vaalipäivän äänet lkm", "Äänet yhteensä lkm");
                foreach ($a as $k=>$v)
                {
                        if (in_array($k, $merge_keys))
                        {
                                $ret_a[$id][$k]+=$v;
                        }
                        elseif (in_array($k, $skip_keys))
                        {
                        }
                        elseif ($ret_a[$id][$k]!=$v) 
                        {
                                print_r($ret_a[$id]);
                                print_r($a);

                                die("VIRHE: virheellinen tietojen yhdistämine avaimella $k ($id)");
                        }
                }
           }
    }
}


$tk=array();
foreach ($ret_k as $id=>$k)
{
//	if ($k['wikidata_qid_debug']!="none") continue;
	$tk=array_keys($k);
	break;
}

print "\n";

print "OUT" ."\t";
foreach($tk as $key)
{
	print $key ."\t";
}
print "\n";

foreach ($ret_k as $id=>$k)
{
//	if ($k['wikidata_qid_debug']!="none") continue;
	print "OUT" ."\t";
	foreach($tk as $key)
	{
		print $k[$key] ."\t";
	}
	print "valintatieto:" . $k['Valintatieto'] ."\t";
	print "kunnanvaltuutettu:" . $k['Kunnanvaltuutettu'] ."\t";
	print "\n";
}
die(1);


?>
