<?php
include "config.php";

header('Content-type: text/plain');
$state_name_id_mapping = array(
    "Jammu & Kashmir" => "S09",
    "Himachal Pradesh" => "S08",
    "Punjab" => "S19",
    "Chandigarh" => "U02",
    "Uttarakhand" => "S28",
    "Haryana" => "S07",
    "NCT OF Delhi" => "U05",
    "Rajasthan" => "S20",
    "Uttar Pradesh" => "S24",
    "Bihar" => "S04",
    "Sikkim" => "S21",
    "Arunachal Pradesh" => "S02",
    "Nagaland" => "S17",
    "Manipur" => "S14",
    "Mizoram" => "S16",
    "Tripura" => "S23",
    "Meghalaya" => "S15",
    "Assam" => "S03",
    "West Bengal" => "S25",
    "Jharkhand" => "S27",
    "Odisha" => "S18",
    "Chhattisgarh" => "S26",
    "Madhya Pradesh" => "S12",
    "Gujarat" => "S06",
    "Daman & Diu" => "U04",
    "Dadra & Nagar Haveli" => "U03",
    "Maharashtra" => "S13",
    "Karnataka" => "S10",
    "Goa" => "S05",
    "Lakshadweep" => "U06",
    "Kerala" => "S11",
    "Tamil Nadu" => "S22",
    "Puducherry" => "U07", "Puducherry",
    "Andaman & Nicobar Islands" => "U01",
    "Telangana" => "S29",
    "Andhra Pradesh" => "S01"
);

// Handle Passthru parameters from Exotel
$from = $_GET['From'];
$pincode = trim($_GET['digits'], '"');
list($affidavit_url, $doe, $stage) = get_election_details($lat, $long);
if (isset($affidavit_url)) {
    $text = "You are voting on {$doe} and here is your list of candidates - $affidavit_url. Powered by www.exotel.com using crowdsourced data";
    print "$text\n";
    send_sms($text, $from);
}


// Function to send SMS back to the caller
function send_sms($response, $to)
{
    $post_data = array(
        // 'From' doesn't matter; For transactional, this will be replaced with your SenderId;
        'From'   =>  Config::EXO_PHONE,
        'To'    => $to,
        'Body'  => $response
    );

    $exotel_sid = Config::EXOTEL_SID; // Your Exotel SID - Get it from here: http://my.exotel.in/Exotel/settings/site#api-settings
    $exotel_token = Config::EXOTEL_TOKEN; // Your exotel token - Get it from here: http://my.exotel.in/Exotel/settings/site#api-settings

    $url = "https://" . $exotel_sid . ":" . $exotel_token . "@twilix.exotel.in/v1/Accounts/" . $exotel_sid . "/Sms/send";

    do_curl($url, $post_data);
}


// Base Curl function for the APIs we consume
function do_curl($url, $post_data = null)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FAILONERROR, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    if ($post_data) {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post_data));
    }
    $resp = curl_exec($curl);
    curl_close($curl);
    return $resp;
}


// Get Lat Long from Pincode using the GeoCoding API
function get_lat_long($pincode)
{
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$pincode}&key=".Config::GOOGLE_KEY;
    $resp = do_curl($url);
    $resp = json_decode($resp);
    return array($resp->results[0]->geometry->location->lat, $resp->results[0]->geometry->location->lng); 
}


// Get Election details from Lat Long using MapBox API
function get_election_details($lat, $long)
{

    $url = "https://api.mapbox.com/v4/planemad.3picr4b8/tilequery/{$long},{$lat}.json?limit=5&radius=0&dedupe=true&access_token=".Config::MAPBOX_KEY;
    $resp = do_curl($url);
    $affidavit_url = $doe = $stage = null;

    $resp = json_decode($resp);
    print_r($resp);

    foreach ($resp->features as $feature) {
        if ($feature->properties->tilequery->layer == "pc") {
            $affidavit_url = 'https://affidavit.eci.gov.in/showaffidavit/1/' . $state_name_id_mapping[$feature->properties->st_name] . '/' . $feature->properties->pc_no . '/PC';
        }
        if ($feature->properties->tilequery->layer == "polling-schedule") {
            $doe = $feature->properties->{'2019_election_date'};
            $stage = $feature->properties->{'2019_election_phase'};
        }
    }
    return array($affidavit_url, $doe, $stage);
}
