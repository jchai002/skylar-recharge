<?php
// Too much data to output buffer - just kill for performance
ob_end_clean();
global $sc, $db;

$all_products = $sc->get('/admin/products.json', [
	'limit' => 250,
	'published_status' => 'published',
]);

$product_attributes = [
	'product_type' => $db->query("SELECT code, p.* FROM product_types p")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE),
	'scent' => $db->query("SELECT code, s.* FROM scents s")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE),
	'format' => $db->query("SELECT code, p.* FROM product_formats p")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE),
];
$variant_attributes = $db->query("SELECT * FROM variant_attribute_codes")->fetchAll();

$attributes_by_variant = [];

foreach($variant_attributes as $attribute_list){
	$attributes_by_variant[$attribute_list['variant_id']] = $attribute_list;
	unset($attributes_by_variant[$attribute_list['variant_id']]['variant_id']);
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
]);
die();