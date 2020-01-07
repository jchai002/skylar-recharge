<?php
require_once(__DIR__.'/../includes/config.php');

$new_attributes = [];
$attribute_values = [
	'product_type' => $db->query("SELECT code, t.* FROM product_types t")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE),
	'scent' => $db->query("SELECT code, s.* FROM scents s")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE),
	'format' => $db->query("SELECT code, p.* FROM product_formats p")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE),
	'scent_family' => $db->query("SELECT code, f.* FROM scent_families f")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE),
	'category' => $db->query("SELECT code, c.* FROM product_categories c")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE),
	'size' => $db->query("SELECT code, s.* FROM product_sizes s")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE),
];
$attribute_keys = array_keys($attribute_values);
$meta_attributes = [
	'type_category' => ['map_from' => 'product_type', 'map_to' => 'category', 'values' => $db->query("SELECT * FROM product_type_category_codes t")->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_GROUP)],
	'scent_family' => ['map_from' => 'scent', 'map_to' => 'scent_family', 'values' => $db->query("SELECT * FROM scent_family_codes t")->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_GROUP)],
];


$all_variant_attributes = $db->query("SELECT vc.* FROM variant_attribute_codes vc LEFT JOIN variants v ON vc.variant_id=v.shopify_id WHERE v.deleted_at IS NULL")->fetchAll();

$stmt_get_metafield = $db->prepare("SELECT shopify_id, value FROM metafields WHERE owner_resource='variant' AND owner_id=? AND namespace='skylar' AND `key`='attributes' AND deleted_at IS NULL");

foreach($all_variant_attributes as $attribute_list){
	$variant_id = $attribute_list['variant_id'];
	echo "Checking ".$variant_id."... ";
	// Generate what the value should be based on db
	$variant_attributes = $attribute_list;
	unset($variant_attributes['variant_id']);
	foreach($meta_attributes as $meta_attribute){
		// Check if map_from is set
		$map_to = $meta_attribute['map_to'];
		$map_from = $meta_attribute['map_from'];
		if(empty($variant_attributes[$map_from])){
			$variant_attributes[$meta_attribute['map_to']] = [];
			continue;
		}
		// Map values from meta onto variant
		$values = $meta_attribute['values'][$variant_attributes[$map_from]];
		if(empty($values[0])){
			$values = [];
		}
		$variant_attributes[$map_to] = $values;
	}

	// Check if metafield already exists
	$stmt_get_metafield->execute([$variant_id]);
	if($stmt_get_metafield->rowCount() == 0){
		// Doesn't exist, create it
		echo "Creating new metafield... ";
		$res = $sc->post('/admin/api/2019-10/variants/' . $variant_id . '/metafields.json', ['metafield' => [
			'value' => json_encode($variant_attributes),
			'value_type' => 'json_string',
			'namespace' => 'skylar',
			'key' => 'attributes',
		]]);
		if(empty($res)){
			echo "Error!";
			print_r($sc->last_error);
			die();
		}
		echo $res['id'] . PHP_EOL;
		continue;
	}
	// Metafield does exist, check if it matches what we pulled
	$row = $stmt_get_metafield->fetch();
	if($row['value'] == json_encode($variant_attributes)){
		echo "Skipping, it matches".PHP_EOL;
		continue;
	}
	echo "Updating existing metafield... ".PHP_EOL;
	echo $row['value'].PHP_EOL;
	echo json_encode($variant_attributes).PHP_EOL;
	$res = $sc->put('/admin/api/2019-10/metafields/'.$row['shopify_id'].'.json', [ 'metafield' => [
		'id' => $row['shopify_id'],
		'value' => json_encode($variant_attributes),
	]]);
	if(empty($res)){
		if($sc->last_error['errors'] == 'Not Found'){
			echo "Variant not found in Shopify. Skipping".PHP_EOL;
		} else {
			echo "Error!";
			print_r($sc->last_error);
			die();
		}
	}
	echo $res['id'].PHP_EOL;
	continue;
}
if(!empty($new_attributes)){
	echo "New Attributes: ";
	print_r($new_attributes);
}