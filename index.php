<?php

function run()
{
  header('Access-Control-Allow-Origin: *');
  header('Content-Type: application/json');

  $data = json_decode(file_get_contents(dirname(__FILE__) . '/private/data.json'));

  if (!$data) {
    send_error('unavailable', 'The endpoint is unable to read its configuration file.');
  }

  $result = array();

  if (!isset($_GET['domain']) || !strlen($_GET['domain'])) {
    send_error('invalidParams', 'No domain provided.');
  }

  $domain = strtolower($_GET['domain']);

  if (!isset($data->{$domain})) {
    send_error('noSuchDomain', 'The supplied domain is not served here.');
  }

  $users = $data->{$domain};

  $visited = array();
  while (is_string($users)) {
    if (!isset($data->{$users})) {
      send_error('unavailable', 'Misconfigured domain alias.');
    }

    if (isset($visited[$users])) {
      send_error('unavailable', 'Circular domain alias.');
    } else {
      $visited[$users] = true;
    }

    $users = $data->{$users};
  }

  if (!isset($_GET['user']) || !strlen($_GET['user'])) {
    send_error('invalidParams', 'No username provided.');
  }

  $user = strtolower($_GET['user']);

  if (!isset($users->{$user})) {
    send_error('noSuchUser', 'The supplied user was not found.');
  }

  $result['federation_json'] = array(
      'type' => 'federation_record',
      'user' => $user,
      'destination_address' => $users->{$user},
      'domain' => $domain
  );

  send_result($result);
}

function send_error($errCode, $errMsg) {
  $result = array();

  $result['result'] = 'error';
  $result['error'] = $errCode;
  $result['error_message'] = $errMsg;

  send_result($result);
}

function send_result($result) {
  if (defined('JSON_PRETTY_PRINT')) {
    echo json_encode($result, JSON_PRETTY_PRINT);
  } else {
    echo json_encode($result);
  }
  exit;
}

run();
