<?php
require_once(__DIR__.'/../includes/config.php');

// Scent Duo

$fullsize_by_option = ['Arrow' => 31022048003,'Capri' => 5541512970271,'Coral' => 26812012355,'Isle' => 31022109635,'Meadow' => 26812085955,'Willow' => 8328726413399, 'Salt Air' => 31146959568983];
$rollies_by_option = ['Arrow' => 30258959482967,'Capri' => 30258951389271,'Coral' => 30258952175703,'Isle' => 30258950996055,'Meadow' => 30258958958679,'Willow' => 30258961973335, 'Salt Air' => 31146997448791];
$wash_by_option = ['Capri' => 29452417695831,'Coral' => 29452435914839,'Isle' => 29452443615319,'Meadow' => 29452444401751];
$lotion_by_option = ['Capri' => 29452551028823,'Coral' => 29452557516887,'Isle' => 29452561449047,'Meadow' => 29452565151831];
$handcream_by_option = ['Capri' => 29532336750679,'Coral' => 29533587505239,'Isle' => 29533581738071,'Meadow' => 29533584719959];

$stmt_insert_kit = $db->prepare("INSERT INTO variant_kits (parent_variant, child_variant) VALUES (:parent_variant, :child_variant)");

$product = $sc->get('/products/rollie-duo.json');
echo $product['title'].PHP_EOL;
switch($product['title']){
	default:
		echo "Options not mapped";
		exit;
	case 'Scent Duo':
		$option_mappings = [
			$fullsize_by_option,
			$fullsize_by_option
		];
		break;
	case 'Scent Squad':
		$option_mappings = [
			$fullsize_by_option,
			$rollies_by_option
		];
		break;
	case 'Rollie Duo':
		$option_mappings = [
			$rollies_by_option,
			$rollies_by_option
		];
		break;
}

$stmt_check_existing = $db->prepare("SELECT 1 FROM variant_kits WHERE parent_variant=?");
foreach($product['variants'] as $shopify_variant){
	echo "Setting ".$shopify_variant['title']."... ";
	// Duos, options are in the variant name
	$options = explode(' / ', $shopify_variant['title']);
	$parent_variant = get_variant($db, $shopify_variant['id']);
	$stmt_check_existing->execute([$parent_variant['id']]);
	if($stmt_check_existing->rowCount() != 0){
		echo "Skipping, already set".PHP_EOL;
		continue;
	}
	if(count($options) > 1){
		echo "Treating as duo".PHP_EOL;
		foreach($options as $index=>$option){
			$stmt_insert_kit->execute([
				'parent_variant' => $parent_variant['id'],
				'child_variant' => get_variant($db, $option_mappings[$index][$option])['id'],
			]);
		}
		continue;
	}
	die();

	echo "Treating as single";
	// Single products, options are in the product name
	$option = explode(' ', $product['title'])[0];
	if($option = "The"){
		$option = explode(' ', $product['title'])[1];
	}
	foreach($option_mappings as $option_mapping){
		$stmt_insert_kit->execute([
			'parent_variant' => $parent_variant['id'],
			'child_variant' => get_variant($db, $option_mapping[$option])['id'],
		]);
	}
}