<?php
$time_delay_pickup = 2;
$time_delay_dropoff = 2;
$probability_of_crossover = 1;
$probability_of_mutation = .5;
$number_of_generations = 100;
$number_of_individuals = 50;

// a time distance matrix between every pick-up and drop-off
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

// oid = order number, pu = pick-up location, put = latest pick-up time for all restaurants in this order,
// do = drop-off location, dtl = latest drop-foo time, puta = array of all pickup-times
$order_data = array(	array('oid' => 1, 'pu' => array(8,7), 'put' => 871, 'do' => 1, 'dt' => 890, 'puta' => array(865,871)),
												array('oid' => 2, 'pu' => array(9),	'put' => 870, 'do' => 2, 'dt' => 880),
												array('oid' => 3, 'pu' => array(9),	'put' => 880, 'do' => 3, 'dt' => 895),
												array('oid' => 4, 'pu' => array(10),'put' => 850, 'do' => 4, 'dt' => 870),
												array('oid' => 5, 'pu' => array(7), 'put' => 850, 'do' => 5, 'dt' => 870),
												array('oid' => 6, 'pu' => array(9), 'put' => 860, 'do' => 6, 'dt' => 885));

$population = constructInitialPopulation($order_data);

// Evolve!
for($i = 0; $i < $number_of_generations; $i++) {
	$fitness[$i] = fitness($population, $order_data, $time, $time_vehicle, $time_delay_pickup, $time_delay_dropoff);
	if($i == 0) {
		$most_fit = array_keys($fitness[0]['value'], min($fitness[0]['value']));
		$best_chromosome = $population[$most_fit[0]];
		$best_chromosome['fitness'] = $fitness[0]['value'][$most_fit[0]];
		$best_chromosome['delinquency'] = $fitness[0]['delinquency'][$most_fit[0]];
		$best_chromosome['total_time'] = $fitness[$i]['total_time'][$most_fit[0]];
		$best_chromosome['minutes_driving'] = $fitness[$i]['minutes_driving'][$most_fit[0]];
	} else {
		$most_fit = array_keys($fitness[$i]['value'], min($fitness[$i]['value']));
		if($fitness[$i]['value'][$most_fit[0]] < $best_chromosome['fitness']) {
			$best_chromosome = $population[$most_fit[0]];
			$best_chromosome['fitness'] = $fitness[$i]['value'][$most_fit[0]];
			$best_chromosome['delinquency'] = $fitness[$i]['delinquency'][$most_fit[0]];
			$best_chromosome['total_time'] = $fitness[$i]['total_time'][$most_fit[0]];
			$best_chromosome['minutes_driving'] = $fitness[$i]['minutes_driving'][$most_fit[0]];
		}
	}
	print $best_chromosome['fitness'] . '<br>';
	if($best_chromosome['fitness'] == 0)
		break;
		
	// evolution actions
	$number_of_offspring = calculateNumberOfOffspring($fitness[$i]);
	$population = crossover($population, $number_of_offspring, $probability_of_crossover, $probability_of_mutation);
}
print_r($best_chromosome);

// *** FUNCTIONS *** //
function constructInitialPopulation($order_data) {

	// sort delivery data by pick-up time
	$number_of_deliveries = 0;
	$number_of_pickups = 0;
	foreach($order_data as $key => $row) {
		$pick_up_time[$key]  = $row['put'];
		$number_of_deliveries += count($row['do']);
		$number_of_pickups += count($row['pu']);
	}
	array_multisort($pick_up_time, SORT_ASC, $order_data);
	
	// build our $population (an array of chromosomes)
	$population = chunkSwap($order_data);
	deliverySwap($population, $number_of_deliveries);
	pickupSwap($population, $number_of_pickups);
	return $population;
}

function chunkSwap($order_data) {
	$population = array();
	$number_of_chunks = count($order_data);
	$number_of_chunks_plus_1 = $number_of_chunks + 1;		// so we can insert at 0 or last positions
	
	// our first chromosome
	buildChromosomeFromData(&$population, $order_data);
	
	// add more variants of this chromosome by swapping entire orders (chunks)
	for($i = 0; $i < $number_of_chunks; $i++) {
		$chunk_to_swap = round(mt_rand() / mt_getrandmax() * ($number_of_chunks - 1));
		$chunk_number = $chunk_to_swap - .5;
		
		// create probability distribution
		$max_probability = 2 / $number_of_chunks_plus_1;
		$random_number = mt_rand() / mt_getrandmax();
		$insert_here_probability = 0;
		$insert_here = 0;
		while($random_number > $insert_here_probability) {
			if($insert_here < $chunk_number) {
				$insert_here_probability += $max_probability / ($chunk_number + .5) * ($insert_here + .5);
			} elseif($insert_here == $chunk_number) {
				$insert_here_probability += .5 * $max_probability / ($chunk_number + .5) * ($insert_here + .25) + .5 * $max_probability / ($number_of_chunks + .5 - $chunk_number) * ($number_of_chunks + .75 - $insert_here);
			} else {
				$insert_here_probability += $max_probability / ($number_of_chunks + .5 - $chunk_number) * ($number_of_chunks + .5 - $insert_here);
			}
			$insert_here++;
		}
		$insert_here -= 1;
		
		// move the chunk (if neccessary)
		$chromosome_data = $order_data;
		if($chunk_to_swap != $insert_here) {
			$chunk_data = array_slice($order_data, $chunk_to_swap, 1);		
			unset($chromosome_data[$chunk_to_swap]);
			array_splice($chromosome_data, $insert_here, 0, $chunk_data);
		}
		
		// create chromosome from chunk order
		buildChromosomeFromData(&$population, $chromosome_data);
	}
	return $population;
}

function deliverySwap(&$population, $number_of_deliveries) {
	$number_of_chromosomes = count($population);
	$size_of_chromosome = count($population[0]);
	
	for($i = 0; $i < $number_of_chromosomes; $i++) {
		$chromosome_data = $population[$i];
		$delivery_to_swap = round(mt_rand() / mt_getrandmax() * ($number_of_deliveries - 1)) + 1;
		$position_in_chromosome = 0;
		$delivery_counter = 0;
		foreach($chromosome_data as $key => $action) {
			if(!strcmp($action['type'], 'do')) {
				$delivery_counter++;
				if($delivery_counter == $delivery_to_swap)
					break;
			}
			$position_in_chromosome++;
		}
		$insert_here = round(mt_rand() / mt_getrandmax() * ($size_of_chromosome - $position_in_chromosome - 1) + $position_in_chromosome);
		
		// move the delivery (if neccessary)
		if($delivery_to_swap != $insert_here) {
			$delivery_data = array_slice($chromosome_data, $position_in_chromosome, 1);		
			unset($chromosome_data[$position_in_chromosome]);
			array_splice($chromosome_data, $insert_here, 0, $delivery_data);
		}
		// add chromosome
		array_push($population, $chromosome_data);
	}
}

function pickupSwap(&$population, $number_of_pickups) {
	$number_of_chromosomes = count($population);
	$size_of_chromosome = count($population[0]);
	
	for($i = 0; $i < $number_of_chromosomes; $i++) {
		$chromosome_data = $population[$i];
		$pickup_to_swap = round(mt_rand() / mt_getrandmax() * ($number_of_pickups - 1)) + 1;
		$position_in_chromosome = 0;
		$pickup_counter = 0;
		foreach($chromosome_data as $key => $action) {
			if(!strcmp($action['type'], 'pu')) {
				$pickup_counter++;
				if($pickup_counter == $pickup_to_swap)
					break;
			}
			$position_in_chromosome++;
		}
		$insert_here = floor(mt_rand() / mt_getrandmax() * ($position_in_chromosome + 1));
		$insert_here = ($insert_here == $position_in_chromosome + 1) ? $insert_here - 1 : $insert_here;
		
		// move the pickup (if neccessary)
		if($pickup_to_swap != $insert_here) {
			$pickup_data = array_slice($chromosome_data, $position_in_chromosome, 1);		
			unset($chromosome_data[$position_in_chromosome]);
			array_splice($chromosome_data, $insert_here, 0, $pickup_data);
		}
		// add chromosome
		array_push($population, $chromosome_data);
	}
}

function buildChromosomeFromData(&$population, $chromosome_data) {
	$chromosome = array();
	foreach($chromosome_data as $key => $order) {
		foreach($order['pu'] as $pickup)
			array_push($chromosome, array($pickup, $order['oid'], 'type' => 'pu'));
		array_push($chromosome, array($order['do'], $order['oid'], 'type' => 'do'));
	}
	array_push($population, $chromosome);
}

function fitness($population, $order_data, $time, $time_vehicle, $time_delay_pickup, $time_delay_dropoff) {
	$starting_time = 845;		// minutes past midnight
	$vehicle_id = 0;
	$total_time_weight_factor = 2;
	$total_driving_time_weight_factor = 1.25;
	
	foreach($population as $chromosome_id => $chromosome) {
		$delinquency_total = 0;
		$delinquency = array();
		$first_action_location_id = $chromosome[0][0];
		$vehicle_to_first_action_time = $time_vehicle[$first_action_location_id][$vehicle_id];
		$minutes_driving = $vehicle_to_first_action_time;
		foreach($order_data as $key => $order)
			$order_data_key[$order['oid']] = $key;

		$time_counter = $starting_time + $vehicle_to_first_action_time;
		foreach($chromosome as $action_id => $action) {
			$location_id = $action[0];
			$order_number = $action[1];
			$location_type = $action['type'];
			$this_order_data = $order_data[$order_data_key[$order_number]];
			
			// wait at restaurant if we get there early
			// also add time expense of transaction 
			if(!strcmp($location_type, 'pu')) {
				$pick_up_time_key = array_keys($this_order_data['pu'], $location_id);
				$pick_up_time = $this_order_data['puta'][$pick_up_time_key[0]];
				$time_counter = max($time_counter, $pick_up_time);
				$time_counter += $time_delay_pickup;
			} else {
				$delinquency_amount = ($time_counter > $this_order_data['dt']) ? $time_counter - $this_order_data['dt'] : 0;
				$delinquency[$action_id] = $delinquency_amount;
				$delinquency_total += $delinquency_amount * $delinquency_amount;
				$time_counter += $time_delay_dropoff;
			}
			// time to next destination if this is not the last action
			if($action_id != count($chromosome)) {
				$next_location_id = $chromosome[$action_id + 1][0];
				$time_to_next_location = $time[max($location_id, $next_location_id)][min($location_id, $next_location_id)];
				$time_counter += $time_to_next_location;
				$minutes_driving += $time_to_next_location; 
			}
		}
		$fitness['total_time'][$chromosome_id] = $time_counter - $starting_time;
		$fitness['minutes_driving'][$chromosome_id] = $minutes_driving;
		$fitness['delinquency'][$chromosome_id] = $delinquency;
		$fitness['value'][$chromosome_id] = 	$total_time_weight_factor * ($time_counter - $starting_time) + 
																$total_driving_time_weight_factor * $minutes_driving + 
																$delinquency_total;
	}
	$fitness['average_population_fitness'] = array_sum($fitness['value']) / count($population);
	$fitness['number_of_chromosomes'] = count($population);
	return $fitness;
}

function crossover($population, $number_of_offspring, $probability_of_crossover, $probability_of_mutation) {
	$new_population = array();
	$number_of_offspring_copy = array();
	// get rid of all elements having zero offspring
	foreach($number_of_offspring as $chromosome_id => $offspring_count) {
		if($offspring_count != 0)
			$number_of_offspring_copy[$chromosome_id] = $number_of_offspring[$chromosome_id];
	}
	foreach($number_of_offspring as $chromosome_id => $offspring_count) {
		$offspring_accounted_for = 0;
		while(isset($number_of_offspring_copy[$chromosome_id])) {
			$number_of_offspring_copy[$chromosome_id]--;
			if($number_of_offspring_copy[$chromosome_id] == 0)
				unset($number_of_offspring_copy[$chromosome_id]);
			$random_crossover_value = mt_rand() / mt_getrandmax();
			
			// time to fornicate - pick a mate
			if($random_crossover_value <= $probability_of_crossover && count($number_of_offspring_copy) > 0) {
				$rand_keys = array_rand($number_of_offspring_copy);
				$new_chromosome = mate($population[$chromosome_id], $population[$rand_keys]);
				array_push($new_population, $new_chromosome);
			} else
				array_push($new_population, $population[$chromosome_id]);
			$offspring_accounted_for++;
		}
		$random_mutation_value = mt_rand() / mt_getrandmax();
		if($random_mutation_value < $probability_of_mutation && count($new_population) > 0)
			mutate($new_population[count($new_population) - 1]);
	}
	return $new_population;
}

function mate($chromosome1, $chromosome2) {
	$new_chromosome = array_fill(0, count($chromosome1), 0);
	for($i = 0; $i < count($chromosome1); $i++) {
		$chrom1_order_id = $chromosome1[$i][1];
		$chrom2_order_id = $chromosome2[$i][1];
		if(!isset($order_positions[$chrom1_order_id][0]))
			$order_positions[$chrom1_order_id][0] = array();
		if(!isset($order_positions[$chrom2_order_id][1]))
			$order_positions[$chrom2_order_id][1] = array();
		array_push($order_positions[$chrom1_order_id][0], $i);
		array_push($order_positions[$chrom2_order_id][1], $i);
	}
	foreach($order_positions as $order_id => $position_in_chromosomes) {
		if(rand(0,1) == 0) {
			for($i = 0; $i < count($position_in_chromosomes[0]); $i++) {
				$chrom1_index = $position_in_chromosomes[0][$i];
				if($new_chromosome[$chrom1_index] == 0)
					$new_chromosome[$chrom1_index] = $chromosome1[$chrom1_index];
				else
					array_splice($new_chromosome, $chrom1_index, 0, array($chromosome1[$chrom1_index]));
			}
		}	else {
			for($i = 0; $i < count($position_in_chromosomes[1]); $i++) {
				$chrom2_index = $position_in_chromosomes[1][$i];
				if($new_chromosome[$chrom2_index] == 0)
					$new_chromosome[$chrom2_index] = $chromosome2[$chrom2_index];
				else
					array_splice($new_chromosome, $chrom2_index, 0, array($chromosome2[$chrom2_index]));
			}
		}
	}
	foreach($new_chromosome as $key => $basepair) {
		if($basepair == 0)
			unset($new_chromosome[$key]);
	}
	$new_chromosome = array_values($new_chromosome);
	checkChromosomeOrdering($new_chromosome);
	return $new_chromosome;
}

function mutate(&$chromosome) {
	$grab_basepair = rand(0, count($chromosome) - 1);
	$put_basepair = rand(0, count($chromosome) - 1);
	$basepair = array_slice($chromosome, $grab_basepair, 1);		
	unset($chromosome[$grab_basepair]);
	array_splice($chromosome, $put_basepair, 0, $basepair);
	$chromosome = array_values($chromosome);
	checkChromosomeOrdering($chromosome);
}

function calculateNumberOfOffspring($fitness) {
	$offspring_constant = 0;
	$accumulator = 0;
	
	foreach($fitness['value'] as $chromosome_id => $fitness_score)
		$offspring_constant += 1 / $fitness_score;
	$offspring_constant = $fitness['number_of_chromosomes'] / $offspring_constant;

	foreach($fitness['value'] as $chromosome_id => $fitness_score)
		$number_of_offspring[$chromosome_id] = $offspring_constant / $fitness_score;
	arsort($number_of_offspring);
	
	foreach($number_of_offspring as $chromosome_id => $offspring) {
		$accumulator += $offspring - floor($offspring); 
		$number_of_offspring[$chromosome_id] = floor($offspring);
	}
	$accumulator = round($accumulator);
	
	foreach($number_of_offspring as $chromosome_id => $offspring) {
		if($accumulator > 0) {
			$number_of_offspring[$chromosome_id]++;
			$accumulator--;
		}
	}
	ksort($number_of_offspring);
	return $number_of_offspring;
}

function checkChromosomeOrdering(&$chromosome) {
	$orders = array();
	foreach($chromosome as $position_in_chromosome => $basepair) {
		$orders[$basepair[1]][$basepair['type']][$position_in_chromosome] = $position_in_chromosome;
	}
	foreach($orders as $order_number => $order) {
		$last_pickup = max($order['pu']);
		$dropoff = max($order['do']);
		if($dropoff < $last_pickup) {
			$dropoff_data = array_slice($chromosome, $dropoff, 1);		
			unset($chromosome[$dropoff]);
			array_splice($chromosome, $last_pickup, 0, $dropoff_data);
		}
	}
}

?>