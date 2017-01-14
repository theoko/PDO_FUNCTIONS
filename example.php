<?php

require_once 'class.database.php';

// HOST, DATABASE, USERNAME, PASSWORD, DEBUG(OPTIONAL)
$db = new Database('localhost', 'database', 'user', 'pass', true);

// ONLY IF DEBUG IS ENABLED -- DISPLAYS SENSITIVE INFO
$db->debug();

/*
* get data from table 'links' where title(string) = 'Untitled' OR url(string) LIKE '%http%'
*/
$data = $db->get('links', [
  // what should we select -- default: *
  'type' => [
    'id',
    'title',
    'url',
  ],
  'filter' => [
    'id' => [
      'type' => 'integer', // type -- optional -- default: string
      'value' => 1, // value - required
      'operator' => 'or', // operator: and, or -- optional
    ],
    'title' => [
      'type' => 'string', // type -- optional -- default: string
      'value' => 'Untitled', // value - required
      'operator' => 'or', // operator: and, or -- optional
    ],
    'url' => [
      'type' => 'string', // type -- optional -- default: string
      'value' => 'http', // value - required
      'search' => true, // enable search (LIKE in sql query)
    ],
  ],
  'sort' => [
    'by' => 'id', // sort results by id
    'order' => 'desc', // descending (last first)
  ],
  'count' => 10, // how many records should we return (LIMIT)
]);

foreach($data as $key => $d) {
  var_dump($d);
}

$db->customQuery("insert into links(title, url) values('Test', 'example.com')");

$db->update('links', [
  'id' => 1,
  'field' => 'url',
  'value' => 'example.org',
  'type' => 'string', // OPTIONAL
]);

$db->multiUpdate('links', [1, 2, 3, 4, 5], [
  'field' => 'url',
  'value' => 'example.org',
  'type' => 'string', // OPTIONAL
]);

$db->insert('links', [
	'title' => 'Test',
	'url' => [
		'type' => 'string', // by default it is 'string', you can use 'integer' as well
		'value' => 'example.com',
	],
]);

$status = $db->insertOrFail('links', [
	'title' => 'Test',
	'url' => [
		'type' => 'string', // by default it is 'string', you can use 'integer' as well
		'value' => 'example.com',
	],
]);

if($status) {
	echo "success"; // it was inserted as it isn't already in the database
} else {
	echo "exists!"; // it wasn't inserted as it is already in the database
}
