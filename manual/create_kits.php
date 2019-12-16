<?php
require_once(__DIR__.'/../includes/config.php');

// Scent Duo

$fullsize_by_option = ['Arrow' => 31022048003,'Capri' => 5541512970271,'Coral' => 26812012355,'Isle' => 31022109635,'Meadow' => 26812085955,'Willow' => 8328726413399];
$rollies_by_option = ['Arrow' => 30258959482967,'Capri' => 30258951389271,'Coral' => 30258952175703,'Isle' => 30258950996055,'Meadow' => 30258958958679,'Willow' => 30258961973335];
$wash_by_option = ['Capri' => 29452417695831,'Coral' => 29452435914839,'Isle' => 29452443615319,'Meadow' => 29452444401751];
$lotion_by_option = ['Capri' => 29452551028823,'Coral' => 29452557516887,'Isle' => 29452561449047,'Meadow' => 29452565151831];
$handcream_by_option = ['Capri' => 29532336750679,'Coral' => 29533587505239,'Isle' => 29533581738071,'Meadow' => 29533584719959];

$stmt_insert_kit = $db->prepare("INSERT INTO variant_kits (parent_variant, child_variant) VALUES (:parent_variant, :child_variant)");

$product = $sc->get('/products/the-coral-wash-lotion-bundle.json');
echo $product['title'];

foreach($product['variants'] as $shopify_variant){
	$parent_variant = get_variant($db, $shopify_variant['id']);
	$option = explode(' ', $product['title'])[0];
	if($option = "The"){
		$option = explode(' ', $product['title'])[1];
	}
	$stmt_insert_kit->execute([
		'parent_variant' => $parent_variant['id'],
		'child_variant' => get_variant($db, $lotion_by_option[$option])['id'],
	]);
	$stmt_insert_kit->execute([
		'parent_variant' => $parent_variant['id'],
		'child_variant' => get_variant($db, $wash_by_option[$option])['id'],
	]);
}

/*
$parent_variant = get_variant($db, $product['variants'][0]['id']);
foreach(['Capri', 'Willow'] as $option){
	$stmt_insert_kit->execute([
		'parent_variant' => $parent_variant['id'],
		'child_variant' => get_variant($db, $fullsize_by_option[$option])['id'],
	]);
}
foreach($fullsize_by_option as $child_variant_id){
	$stmt_insert_kit->execute([
		'parent_variant' => $parent_variant['id'],
		'child_variant' => get_variant($db, $child_variant_id)['id'],
	]);
}

*/
/*

*/