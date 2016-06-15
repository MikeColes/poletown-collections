<?php
//////////////////////////////////////////////////////////////////////////////////////////////
// Who   : Coles
// What  : Script to pull down Microsoft's Office365 xml file of designated IPs and networks
//         Once the XML is choked, dump the data into object-group format
// When  : 20160614
// Where : Runs on any PHP install of 5.2+ <- Don't hold me to the version
// Why   : Dude, really... is anything MS easy?
// How   : Just run the script. So easy, a PCSJW could do it. ;P 
//         Looking to add ability to just dump certain sections in v.2
//////////////////////////////////////////////////////////////////////////////////////////////

//Use the live URL for production, use the test file for umm... testing.
$updateURL = "https://support.content.office.net/en-us/static/O365IPAddresses.xml";
//$updateURL = "./test-O365IPAddresses.xml";

//Intake the file, convert to a string, form into XML
$xml_parsed = simplexml_load_string( file_get_contents ($updateURL) );

//Take the XML place it into JSON format
$json = json_encode($xml_parsed);
//Then reverse it. HA! 
$O365_array = json_decode($json, TRUE);


function cidr2mask($netmask) {
// Toss up between a function or lookup table.
// 24 array would be much faster. Maybe next time.
// Too lazy to type it out or too lazy to do the math?
// Never too lazy for comments that are never read.
// I also wasn't to lazy to google and find out that
// LinickX had already done it: 
// https://gist.github.com/linickx/1309388/cf8fb723b51ba60521f92e0cc930b59cb5d2e5d9#file-cidr2mask-php
$netmask_result="";
for($i=1; $i <= $netmask; $i++) {
  $netmask_result .= "1";
}
for($i=$netmask+1; $i <= 32; $i++) {
    $netmask_result .= "0";
}
$netmask_ip_binary_array = str_split( $netmask_result, 8 );
$netmask_ip_decimal_array = array();
// I need to do the php more frequently, => will cause me a trip to my buddy, Google, every time
foreach( $netmask_ip_binary_array as $k => $v ){
    $netmask_ip_decimal_array[$k] = bindec( $v ); // "100" => 4
}
$subnet = join( ".", $netmask_ip_decimal_array );
return $subnet;
}

//Because peeking blind arrays is frustrating
//print_r ($O365_array);
//var_dump($json);
//print_r($json);

//Cycle through the array to look for IPv4 addresses.
foreach ($O365_array['product'] as $i) {
  $product=$i['@attributes']['name'];
  // It's OK to shake your head at this. Iterating through in v.2
  if       ( @strcmp($i['addresslist'][0]['@attributes']['type'],"IPv4") == 0 ) {
    $objectgrouparray[$product]=$i['addresslist'][0]['address'];
  } elseif ( @strcmp($i['addresslist'][1]['@attributes']['type'],"IPv4") == 0 ) {
      $objectgrouparray[$product]=$i['addresslist'][1]['address'];
  } elseif ( @strcmp($i['addresslist'][2]['@attributes']['type'],"IPv4") == 0 ) {
      $objectgrouparray[$product]=$i['addresslist'][2]['address'];
  }

}

// Roll through our freshest array and shove the data out in object-group format
foreach ($objectgrouparray as $name=>$objectgroup) {
  echo "object-group network ".$name."\n";
  foreach ($objectgroup as $entry) {
    //print_r($entry);
    $net = explode("/",$entry);
    if (empty($net[1]) ) {
      echo "network-object host " . $net[0]. "\n";
    } else {
      echo "network-object ".$net[0]." ".cidr2mask($net[1])."\n";
    }
  }
  echo "\n";
} 

// Tada!! Ready to copy & paste or try out those fancy `expect` scripts (They're shiny)
?>
