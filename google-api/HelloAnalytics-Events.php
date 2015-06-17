<?php

function getService($service_account_email, $key_file_location)
{
  // Creates and returns the Analytics service object.

  // Load the Google API PHP Client Library.
  require_once 'src/Google/autoload.php';

  // Create and configure a new client object.
  $client = new Google_Client();
  $client->setApplicationName("HelloAnalytics");
  $analytics = new Google_Service_Analytics($client);

  // Read the generated client_secrets.p12 key.
  $key = file_get_contents($key_file_location);
  $cred = new Google_Auth_AssertionCredentials(
      $service_account_email,
      array(Google_Service_Analytics::ANALYTICS_READONLY),
      $key
  );
  $client->setAssertionCredentials($cred);
  if($client->getAuth()->isAccessTokenExpired()) {
    $client->getAuth()->refreshTokenWithAssertion($cred);
  }

  return $analytics;
}

function getFirstprofileId(&$analytics) {
  // Get the user's first view (profile) ID.

  // Get the list of accounts for the authorized user.
  $accounts = $analytics->management_accounts->listManagementAccounts();

  if (count($accounts->getItems()) > 0) {
    $items = $accounts->getItems();
    $firstAccountId = $items[0]->getId();

    // Get the list of properties for the authorized user.
    $properties = $analytics->management_webproperties
        ->listManagementWebproperties($firstAccountId);

    if (count($properties->getItems()) > 0) {
      $items = $properties->getItems();
      $firstPropertyId = $items[0]->getId();

      // Get the list of views (profiles) for the authorized user.
      $profiles = $analytics->management_profiles
          ->listManagementProfiles($firstAccountId, $firstPropertyId);

      if (count($profiles->getItems()) > 0) {
        $items = $profiles->getItems();

        // Return the first view (profile) ID.
        return $items[0]->getId();

      } else {
        throw new Exception('No views (profiles) found for this user.');
      }
    } else {
      throw new Exception('No properties found for this user.');
    }
  } else {
    throw new Exception('No accounts found for this user.');
  }
}

function parseArgs($argc, $argv) {
  if ($argc == 2) {
    if (preg_match('/--config=([a-zA-Z0-9\-\.\_]*)/', $argv[1], $matches)) {
      return array('config' => $matches[1]);
    }
  }
  return array();
}

function parseConfig($configFile) {
  $configFolder = 'config';
  $content = implode("", file($configFolder.'/'.$configFile));
  $config = json_decode($content, true);
  return array(
    'emailAddress' => $config['emailAddress'],
    'keyFileLocation' => $configFolder.'/'.$config['keyFileName'],
    'startDate' => $config['startDate'],
    'endDate' => $config['endDate'],
    'filterEventCategory' => $config['filterEventCategory'],
    'filterEventAction' => $config['filterEventAction'],
    'filterEventLabel' => $config['filterEventLabel'],
    'outputFormat' => (array_key_exists('outputFormat', $config) && $config['outputFormat']) ? $config['outputFormat'] : 'print',
  );
}

function getTotalEvents(&$analytics, $profileId, $options = array()) {
  
  if ($options['startDate']) {
    $startDate = $options['startDate'];
  } else {
    $startDate = '14daysAgo';
  }
  if ($options['endDate']) {
    $endDate = $options['endDate'];
  } else {
    $endDate = 'yesterday';
  }
  $metrics = 'ga:totalEvents,ga:uniqueEvents,ga:eventValue,ga:avgEventValue,ga:sessionsWithEvent,ga:eventsPerSessionWithEvent';
  $params = array('dimensions' => 'ga:eventCategory,ga:eventAction,ga:eventLabel');
  $filters = array();
  if ($options['filterEventCategory']) {
    $filters[] = 'ga:eventCategory'.$options['filterEventCategory'];
  }
  if ($options['filterEventAction']) {
    $filters[] = 'ga:eventAction'.$options['filterEventAction'];
  }
  if ($options['filterEventLabel']) {
    $filters[] = 'ga:eventLabel'.$options['filterEventLabel'];
  }
  $params['filters'] = implode(';', $filters);

  return $analytics->data_ga->get(
    'ga:' . $profileId,
    $startDate,
    $endDate,
    $metrics,
    $params
  );

}

function info($string) {
  return "\033[0;32m".$string."\033[0m";
}

function error($string) {
  return "\033[0;31mERROR: ".$string."\033[0m";
}

function prepareFilename($configFile, $outputFormat) {
  $n = explode(".", $configFile);
  return $n[0].'--'.date("Y-m-d_His").'.'.$outputFormat;
}

function saveCSV($filename, $contentArray, $header) {
  $outputFolder = 'output';
  $delimiter = ';';
  $fp = fopen($outputFolder.'/'.$filename, 'x');
  fputcsv($fp, $header, $delimiter);
  foreach ($contentArray as $row) {
    fputcsv($fp, $row, $delimiter);
  }
  fclose($fp);
  return true;
}

function saveJSON($filename, $content) {
  $outputFolder = 'output';
  $fp = fopen($outputFolder.'/'.$filename, 'x');
  fwrite($fp, $content);
  fclose($fp);
  return true;
}


$cliArgs = parseArgs($argc, $argv);
$configFile = $cliArgs['config'];
$config = parseConfig($configFile);
$analytics = getService($config['emailAddress'], $config['keyFileLocation']);
$profile = getFirstProfileId($analytics);
$events = getTotalEvents($analytics, $profile, $config);
$profileName = $events->getProfileInfo()->getProfileName();

$headers = array();
foreach ($events->getColumnHeaders() as $header) {
  $headers[] = $header['name'];
}

$rows = $events->getRows();
$rowsKeys = array();
$i = 0;
foreach ($rows as $k => $metrics) {
  foreach ($metrics as $id => $m) {
    $rowsKeys[$i][$headers[$id]] = $m;
  }
  $i++;
}

echo info("VIEW: ".$profileName."\n");
echo info("OUTPUT: ".$config['outputFormat']."\n");
switch ($config['outputFormat']) {
  case 'print':
    foreach ($rowsKeys as $k => $metrics) {
      echo "ROW: ".$k."\n";
      foreach ($metrics as $key => $m) {
        echo $key." => ".$m."\n";
      }
      echo "--------------------------------\n\n";
    }
    break;
  
  case 'csv':
    $filename = prepareFilename($configFile, $config['outputFormat']);
    if (saveCSV($filename, $rowsKeys, $headers)) {
      echo info("Saved to file ".$filename."\n");
    }
    break;
    
  case 'json':
    $json = json_encode($rowsKeys);
    $filename = prepareFilename($configFile, $config['outputFormat']);
    if (saveJSON($filename, $json)) {
      echo info("Saved to file ".$filename."\n");
    }
    break;
    
  default:
    echo error("Unknown output format\n");
    break;
}
