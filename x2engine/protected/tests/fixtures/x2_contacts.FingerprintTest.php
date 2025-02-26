<?php

return array(
	'contact1' => array(
		'id' => 50000,
		'name' => 'ftest ftest',
        'nameId' => 'ftest ftest_50000',
        'company' => 'Black Mesa_1',
		'firstName' => 'testf',
		'lastName' => 'testf',
		'email' => 'testf@testf.com',
		'assignedTo' => 'Anyone',
		'visibility' => 1,
		'phone' => '(234) 918-2348',
		'phone2' => '398-103-6291',
		'trackingKey' => '12345678901234567890',
        'lastUpdated' => 0,
		'fingerprintId' => '10',
	),
    // will turn up as a partial match along with contact1 if threshold is set low enough.
    // lastUpdated is later than contact1
	'contact2' => array(
		'id' => 50001,
		'name' => 'ftest ftest2',
        'nameId' => 'ftest ftest2_50001',
        'company' => 'Black Mesa_1',
		'firstName' => 'testf',
		'lastName' => 'testf2',
		'email' => 'testf@testf.com',
		'assignedTo' => 'Anyone',
		'visibility' => 1,
		'phone' => '(234) 918-2348',
		'phone2' => '398-103-6291',
		'trackingKey' => '12345678901234567890',
        'lastUpdated' => 1,
		'fingerprintId' => '11',
	),
    // about the same as contact 2 but more recently updated
	'contact3' => array(
		'id' => 50002,
		'name' => 'ftest ftest3',
        'nameId' => 'ftest ftest3_50001',
        'company' => 'Black Mesa_1',
		'firstName' => 'testf',
		'lastName' => 'testf3',
		'email' => 'testf@testf.com',
		'assignedTo' => 'Anyone',
		'visibility' => 1,
		'phone' => '(234) 918-2348',
		'phone2' => '398-103-6291',
		'trackingKey' => '12345678901234567890',
        'lastUpdated' => 2,
		'fingerprintId' => '13',
	),
    // about the same as contact 3 but more recently updated
	'contact4' => array(
		'id' => 50003,
		'name' => 'ftest ftest4',
        'nameId' => 'ftest ftest4_50001',
        'company' => 'Black Mesa_1',
		'firstName' => 'testf',
		'lastName' => 'testf4',
		'email' => 'testf@testf.com',
		'assignedTo' => 'Anyone',
		'visibility' => 1,
		'phone' => '(234) 918-2348',
		'phone2' => '398-103-6291',
		'trackingKey' => '12345678901234567890',
        'lastUpdated' => 3,
		'fingerprintId' => '14',
	),
);

?>
