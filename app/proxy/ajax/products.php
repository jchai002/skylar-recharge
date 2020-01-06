<?php
// Too much data to output buffer - just kill for performance
ob_end_clean();
global $sc, $db;

$all_products = $sc->get('/admin/products.json', [
	'limit' => 250,
	'published_status' => 'published',
]);

$product_attributes = [
	'product_type' => $db->query("SELECT code, t.* FROM product_types t")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE),
	'scent' => $db->query("SELECT code, s.* FROM scents s")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE),
	'format' => $db->query("SELECT code, p.* FROM product_formats p")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE),
	'scent_family' => $db->query("SELECT code, f.* FROM scent_families f")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE),
	'category' => $db->query("SELECT code, c.* FROM product_categories c")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE),
];
$meta_attributes = [
	'type_category' => ['map_from' => 'product_type', 'map_to' => 'category', 'values' => $db->query("SELECT * FROM product_type_category_codes t")->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_GROUP)],
	'scent_family' => ['map_from' => 'scent', 'map_to' => 'scent_family', 'values' => $db->query("SELECT * FROM scent_family_codes t")->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_GROUP)],
];
$variant_attributes = $db->query("SELECT * FROM variant_attribute_codes")->fetchAll();

$attributes_by_variant = [];
foreach($variant_attributes as $attribute_list){
	$variant_id = $attribute_list['variant_id'];
	$attributes_by_variant[$variant_id] = $attribute_list;
	unset($attributes_by_variant[$variant_id]['variant_id']);
	echo $variant_id;
	print_r($attributes_by_variant[$variant_id]);
	foreach($meta_attributes as $meta_attribute){
		print_r($meta_attribute);
		// Check if map_from is set
		if(empty($attribute_list[$meta_attribute['map_from']])){
//			$attributes_by_variant[$variant_id][$meta_attribute['map_to']] = [];
			continue;
		}
		// Map values from meta onto variant
		$map_to = $meta_attribute['map_to'];
		$map_from = $meta_attribute['map_from'];
		echo "$map_to => $map_from";
		print_r($meta_attribute['values'][$attributes_by_variant[$map_from]]);
//		$values = $meta_attribute['values'][$product_attributes[$map_from]['id']];
//		$attributes_by_variant[$variant_id][$map_to] = $values;
	}
}
$products_by_id = [];
$exclusions_cluase = "
AND NOT (namespace = 'spr' AND `key` = 'reviews')
AND NOT (namespace = 'yotpo' AND `key` = 'bottomline')
AND NOT (namespace = 'yotpo' AND `key` = 'qa_bottomline')
AND NOT (namespace = 'yotpo' AND `key` = 'catalog_bottomline')
AND NOT (namespace = 'yotpo_reviews' AND `key` = '1000')
AND NOT (namespace = 'yotpo' AND `key` = 'richsnippetshtml')";
$stmt_product_metafields = $db->prepare("SELECT namespace, `key`, `value`, value_type FROM metafields WHERE owner_resource='product' AND  owner_id=? AND deleted_at IS NULL".$exclusions_cluase);
$stmt_variant_metafields = $db->prepare("SELECT namespace, `key`, `value`, value_type FROM metafields WHERE owner_resource='variant' AND owner_id=? AND deleted_at IS NULL".$exclusions_cluase);
$stmt_get_variant_kit = $db->prepare("SELECT vc.shopify_id FROM variant_kits vk LEFT JOIN variants vp ON vk.parent_variant=vp.id LEFT JOIN variants vc ON vk.child_variant=vc.id WHERE vp.shopify_id=?");
foreach($all_products as $product){
	$variants = [];
	$product['metafields'] = new ArrayObject();
	$stmt_product_metafields->execute([$product['id']]);
	foreach($stmt_product_metafields->fetchAll() as $metafield){
		if(!array_key_exists($metafield['namespace'], $product['metafields'])){
			$product['metafields'][$metafield['namespace']] = [];
		}
		switch($metafield['value_type']){
			default:
				$product['metafields'][$metafield['namespace']][$metafield['key']] = $metafield['value'];
				break;
			case 'integer':
				$product['metafields'][$metafield['namespace']][$metafield['key']] = intval($metafield['value']);
				break;
			case 'json_string':
				$product['metafields'][$metafield['namespace']][$metafield['key']] = json_decode($metafield['value']);
				break;

		}
	}
	foreach($product['variants'] as $variant){
		$stmt_get_variant_kit->execute([$variant['id']]);
		$variant['kit_ids'] = $stmt_get_variant_kit->rowCount() > 0 ? $stmt_get_variant_kit->fetchAll(PDO::FETCH_COLUMN) : [];
		$variant['attributes'] = $attributes_by_variant[$variant['id']] ?? new ArrayObject();
		$stmt_variant_metafields->execute([$variant['id']]);
		$variant['metafields'] = new ArrayObject();
		foreach($stmt_product_metafields->fetchAll() as $metafield){
			if(!array_key_exists($metafield['namespace'], $variant['metafields'])){
				$variant['metafields'][$metafield['namespace']] = [];
			}
			switch($metafield['value_type']){
				default:
					$variant['metafields'][$metafield['namespace']][$metafield['key']] = $metafield['value'];
					break;
				case 'integer':
					$variant['metafields'][$metafield['namespace']][$metafield['key']] = intval($metafield['value']);
					break;
				case 'json_string':
					$variant['metafields'][$metafield['namespace']][$metafield['key']] = json_decode($metafield['value']);
					break;

			}
		}
		$variants[$variant['id']] = $variant ?? [];
	}
	$product['variants'] = $variants;
	$products_by_id[$product['id']] = $product;
}

header('Content-Type: application/json');
echo json_encode([
	'products' => $products_by_id,
	'attributes' => $product_attributes,
	'meta_attributes' => $meta_attributes,
]);
die();