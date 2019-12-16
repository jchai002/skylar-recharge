<?php
require_once(__DIR__.'/../includes/config.php');

$attributes = ['scent', 'product_type', 'format'];
$new_attributes = [];
$attribute_values = [
	'product_type' => $db->query("SELECT code, p.* FROM product_types p")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE),
	'scent' => $db->query("SELECT code, s.* FROM scents s")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE),
	'format' => $db->query("SELECT code, p.* FROM product_formats p")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE),
];


$variant_attributes = $db->query("SELECT vc.* FROM variant_attribute_codes vc LEFT JOIN variants v ON vc.variant_id=v.shopify_id WHERE v.deleted_at IS NULL")->fetchAll();

$stmt_get_metafield = $db->prepare("SELECT shopify_id, value FROM metafields WHERE owner_resource='variant' AND owner_id=? AND namespace='skylar' AND `key`='attributes' AND deleted_at IS NULL");
foreach($variant_attributes as $variant_attribute){
	echo "Checking ".$variant_attribute['variant_id']."... ";
	$new_value = [];
	foreach($attributes as $attribute){
		$new_value[$attribute] = $variant_attribute[$attribute];
		if(empty($attribute_values[$attribute][$variant_attribute[$attribute]])){
			$new_attributes[$attribute][] = $variant_attribute[$attribute];
		}
	}
	$stmt_get_metafield->execute([$variant_attribute['variant_id']]);
	if($stmt_get_metafield->rowCount() > 0){
		$row = $stmt_get_metafield->fetch();
		$db_attributes = json_decode($row['value'], true);
		$match = true;
		foreach($new_value as $attribute=>$value){
			if($db_attributes[$attribute] != $value){
				$match = false;
				break;
			}
		}
		if($match){
			echo "Skipping, it matches".PHP_EOL;
			continue;
		}
		echo "Updating existing metafield... ";
		$res = $sc->put('/admin/api/2019-10/metafields/'.$row['shopify_id'].'.json', [ 'metafield' => [
			'id' => $row['shopify_id'],
			'value' => json_encode($new_value),
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
	echo "Creating new metafield... ";
	$res = $sc->post('/admin/api/2019-10/variants/'.$variant_attribute['variant_id'].'/metafields.json', [ 'metafield' => [
		'value' => json_encode($new_value),
		'value_type' => 'json_string',
		'namespace' => 'skylar',
		'key' => 'attributes',
	]]);
	if(empty($res)){
		echo "Error!";
		print_r($sc->last_error);
		die();
	}
	echo $res['id'].PHP_EOL;
}
if(!empty($new_attributes)){
	echo "New Attributes: ";
	print_r($new_attributes);
}