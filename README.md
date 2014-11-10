PHP-mVPDPTW
===========

A PHP genetic algorithm solving the multiple vehicle pick-up and delivery problem with time windows

This code is currently only programmed for single vehicle PDPTW. It will be rewritten in OOP before adding multiple vehicle functionality with a general genetic class for extensibility to other NP-hard problems with genetic applications.
Also, array position of the inputs correspond to the pick-up and drop-off id which needs to be generalized to support random integer (database) values 

Inputs are:

// a time distance matrix between every pick-up and drop-off location. <b>Consult the Google Distance Matrix API</b>

	$time = array(array(0 ,0 ,0 ,0 ,0 ,0 ,0 ,0 ,0 ,0),

				array(5 ,0 ,0 ,0 ,0 ,0 ,0 ,0 ,0 ,0),
							
				array(6 ,1 ,0 ,0 ,0 ,0 ,0 ,0 ,0 ,0),
							
				array(10,5 ,4 ,0 ,0 ,0 ,0 ,0 ,0 ,0),
							
				array(7 ,7 ,5 ,4 ,0 ,0 ,0 ,0 ,0 ,0),
							
				array(8 ,11,11,9 ,5 ,0 ,0 ,0 ,0 ,0),
							
				array(3 ,6 ,7 ,7 ,4 ,5 ,0 ,0 ,0 ,0),
							
				array(3 ,6 ,6 ,7 ,4 ,5 ,1 ,0 ,0 ,0),
							
				array(3 ,5 ,6 ,7 ,5 ,6 ,2 ,1 ,0 ,0),
							
				array(4 ,5 ,5 ,6 ,4 ,6 ,2 ,2 ,1 ,0));
							

// time from vehicle to each location

	$time_vehicle = array(array( 7, 4),
								array(10, 5),
								array(10, 5),
								array( 8, 6),
								array( 4, 4),
								array( 2, 6),
								array( 4, 2),
								array( 4, 2),
								array( 5, 2),
								array( 5, 1));

// oid = order number (reference to some id in your database)

// pu = pick-up location (multiple pick-up locations allowed per drop-off)

// put = latest pick-up time for all locations for a given order in minutes after 12am (i.e. max(puta))

// do = drop-off location

// dtl = latest drop-off time in minutes after 12am (i.e. 45 minutes after order time)

// puta = array of all pickup-times for a given order (corresponds to pu)

	$order_data = array(	array('oid' => 1, 'pu' => array(8,7), 'put' => 871, 'do' => 1, 'dt' => 890, 'puta' => array(865,871)),
	
									array('oid' => 2, 'pu' => array(9),	'put' => 870, 'do' => 2, 'dt' => 880),
									
									array('oid' => 3, 'pu' => array(9),	'put' => 880, 'do' => 3, 'dt' => 895),
									
									array('oid' => 4, 'pu' => array(10),'put' => 850, 'do' => 4, 'dt' => 870),
									
									array('oid' => 5, 'pu' => array(7), 'put' => 850, 'do' => 5, 'dt' => 870),
									
									array('oid' => 6, 'pu' => array(9), 'put' => 860, 'do' => 6, 'dt' => 885));

