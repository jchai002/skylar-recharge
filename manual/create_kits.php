<?php
require_once(__DIR__.'/../includes/config.php');

// Load all BOMs from cin7 into cache
$page = 0;
$page_size = 250;
$all_boms = [];
do {
	$page++;
	// Get held orders
	/* @var $res JsonAwareResponse */
	$res = $cc->get('BomMasters', [
		'query' => [
			'rows' => $page_size,
			'page' => $page,
		],
	]);
	sleep(1);
	$cc_boms = $res->getJson();
	foreach($cc_boms as $bom){
		$all_boms[$bom['product']['code']] = $bom['product']['components'];
	}
} while(count($cc_boms) >= $page_size);

// Load shopify variants that are not parents

$rows = $db->query("SELECT v.id as parent_id, v.sku FROM variants v
LEFT JOIN products p ON v.product_id=p.id
LEFT JOIN variant_kits vk ON vk.parent_variant=v.id
WHERE vk.id IS NULL
AND v.sku LIKE '7%'
AND v.sku NOT IN ('70221408-100')
AND v.deleted_at IS NULL
AND p.published_at IS NOT NULL
AND p.type != 'Scent Club Gift'")->fetchAll();

$stmt_get_variant = $db->prepare("
SELECT v.id FROM variants v
LEFT JOIN products p ON v.product_id=p.id
WHERE v.sku=?
AND v.deleted_at IS NULL
AND p.published_at IS NOT NULL
AND p.type NOT IN ('Scent Club Swap')
");
$stmt_get_title = $db->prepare("
SELECT CONCAT(CONCAT(p.title, ': '), v.title) as title
FROM variants v
LEFT JOIN products p ON v.product_id=p.id
WHERE v.id=?
");
$stmt_insert_kit = $db->prepare("INSERT INTO variant_kits (parent_variant, child_variant) VALUES (:parent_variant, :child_variant)");
foreach($rows as $row){
	if(empty($all_boms[$row['sku']])){
		echo "No bom exists for sku ".$row['sku'].PHP_EOL;
		continue;
	}
	$child_ids = [];
	$child_titles = [];
	foreach($all_boms[$row['sku']] as $component){
		$stmt_get_variant->execute([$component['code']]);
		if($stmt_get_variant->rowCount() < 1){
			echo "No child variant exists for sku ".$component['code'].PHP_EOL;
			continue 2;
		}
		if($stmt_get_variant->rowCount() > 1){
			echo "Multiple child variants exists for sku ".$component['code'].": ".print_r($stmt_get_variant->fetchAll(PDO::FETCH_COLUMN), true).PHP_EOL;
			continue 2;
		}
		$child_ids[] = $stmt_get_variant->fetchColumn();
		$stmt_get_title->execute([end($child_ids)]);
		$child_titles[] = $stmt_get_title->fetchColumn();
	}
	$stmt_get_title->execute([$row['parent_id']]);
	echo "Adding children ".implode(',', $child_ids)." to ".$row['parent_id'].PHP_EOL;
	echo "Adding children ".implode(',', $child_titles)." to ".$stmt_get_title->fetchColumn().PHP_EOL;
	foreach($child_ids as $child_id){
		$stmt_insert_kit->execute([
			'parent_variant' => $row['parent_id'],
			'child_variant' => $child_id,
		]);
	}
}







die();
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