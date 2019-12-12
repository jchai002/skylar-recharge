<?php
global $sc, $db;

$all_products = $sc->get('/admin/products.json', [
	'limit' => 250,
	'published_status' => 'published',
]);

$product_attributes = json_decode('{
	"scent": {
		"arrow": {
			"id": 1,
			"handle": "arrow",
			"title": "Arrow",
			"swatch_title": "Arrow (Spicy)"
		},
		"capri": {
			"id": 2,
			"handle": "capri",
			"title": "Capri",
			"swatch_title": "Capri (Citrus)"
		},
		"coral": {
			"id": 3,
			"handle": "coral",
			"title": "Coral",
			"swatch_title": "Coral (Fruity)"
		},
		"isle": {
			"id": 4,
			"handle": "isle",
			"title": "Isle",
			"swatch_title": "Isle (Beachy)"
		},
		"meadow": {
			"id": 5,
			"handle": "meadow",
			"title": "Meadow",
			"swatch_title": "Meadow (Floral)"
		},
		"willow": {
			"id": 6,
			"handle": "willow",
			"title": "Willow",
			"swatch_title": "Willow (Woodsy)"
		},
		"sprinkles": {
			"id": 7,
			"handle": "sprinkles",
			"title": "Sprinkles",
			"swatch_title": "Sprinkles"
		},
		"scent_club_spring": {
			"id": 8,
			"handle": "scent_club_spring",
			"title": "Best Of Scent Club Spring",
			"swatch_title": "Spring"
		},
		"scent_club_summer": {
			"id": 9,
			"handle": "scent_club_summer",
			"title": "Best Of Scent Club Summer",
			"swatch_title": "Summer"
		},
		"scent_club_fall": {
			"id": 10,
			"handle": "scent_club_fall",
			"title": "Best Of Scent Club Fall",
			"swatch_title": "Fall"
		},
		"scent_club_winter": {
			"id": 11,
			"handle": "scent_club_winter",
			"title": "Best Of Scent Club Winter",
			"swatch_title": "Winter"
		}
	},
	"format": {
		"fullsize": {
			"id": 1,
			"handle": "fullsize",
			"title": "Full Size"
		},
		"rollie": {
			"id": 2,
			"handle": "rollie",
			"title": "Rollie"
		},
		"candle": {
			"id": 3,
			"handle": "candle",
			"title": "Candle"
		},
		"sample": {
			"id": 4,
			"handle": "sample",
			"title": "Sample"
		}
	},
	"product_type": {
		"fragrance": {
			"id": 1,
			"handle": "fragrance",
			"title": "Fragrance"
		},
		"rollie": {
			"id": 2,
			"handle": "rollie",
			"title": "Rollie"
		},
		"candle": {
			"id": 3,
			"handle": "candle",
			"title": "Candle"
		},
		"wash": {
			"id": 4,
			"handle": "wash",
			"title": "Body Wash"
		},
		"lotion": {
			"id": 5,
			"handle": "lotion",
			"title": "Body Lotion"
		},
		"bundle": {
			"id": 6,
			"handle": "bundle",
			"title": "Bundle"
		},
		"bundle_onetime": {
			"id": 7,
			"handle": "bundle_onetime",
			"title": "Bundle (One-Time)"
		},
		"wash_body_cream_bundle": {
			"id": 8,
			"handle": "wash_body_cream_bundle",
			"title": "Best Of Body Bundle"
		},
		"travel_set": {
			"id": 9,
			"handle": "travel_set",
			"title": "Travel Set"
		},
		"hydration_kit": {
			"id": 10,
			"handle": "hydration_kit",
			"title": "Hydration Essentials"
		},
		"shower_set": {
			"id": 11,
			"handle": "shower_set",
			"title": "Shower Set"
		},
		"best_of_scent_club": {
			"id": 12,
			"handle": "best_of_scent_club",
			"title": "Best Of Scent Club"
		},
		"favorites": {
			"id": 13,
			"handle": "skylar_favorites",
			"title": "Skylar Favorites"
		},
		"travel_duo": {
			"id": 14,
			"handle": "travel_duo",
			"title": "Travel Duo"
		},
		"care_trio": {
			"id": 15,
			"handle": "care_trio",
			"title": "Care Trio"
		},
		"body_fragrance_duo": {
			"id": 16,
			"handle": "body_fragrance_duo",
			"title": "Body & Fragrance Duo"
		}
	}
}', true);
$variant_attributes = json_decode('[
	{
		"variant_id": 31022048003,
		"scent": "arrow",
		"format": "fullsize",
		"product_type": "fragrance"
	},
	{
		"variant_id": 5541512970271,
		"scent": "capri",
		"format": "fullsize",
		"product_type": "fragrance"
	},
	{
		"variant_id": 26812012355,
		"scent": "coral",
		"format": "fullsize",
		"product_type": "fragrance"
	},
	{
		"variant_id": 31022109635,
		"scent": "isle",
		"format": "fullsize",
		"product_type": "fragrance"
	},
	{
		"variant_id": 26812085955,
		"scent": "meadow",
		"format": "fullsize",
		"product_type": "fragrance"
	},
	{
		"variant_id": 8328726413399,
		"scent": "willow",
		"format": "fullsize",
		"product_type": "fragrance"
	},
	{
		"variant_id": 30258959482967,
		"scent": "arrow",
		"format": "rollie",
		"product_type": "fragrance"
	},
	{
		"variant_id": 30258951389271,
		"scent": "capri",
		"format": "rollie",
		"product_type": "fragrance"
	},
	{
		"variant_id": 30258952175703,
		"scent": "coral",
		"format": "rollie",
		"product_type": "fragrance"
	},
	{
		"variant_id": 30258950996055,
		"scent": "isle",
		"format": "rollie",
		"product_type": "fragrance"
	},
	{
		"variant_id": 30258958958679,
		"scent": "meadow",
		"format": "rollie",
		"product_type": "fragrance"
	},
	{
		"variant_id": 30258961973335,
		"scent": "willow",
		"format": "rollie",
		"product_type": "fragrance"
	},
	{
		"variant_id": 12550121390167,
		"scent": "sprinkles",
		"format": "candle",
		"product_type": "candle"
	},
	{
		"variant_id": 5680985538591,
		"scent": "isle",
		"format": "candle",
		"product_type": "candle"
	},
	{
		"variant_id": 5680951328799,
		"scent": "meadow",
		"format": "candle",
		"product_type": "candle"
	},
	{
		"variant_id": 29452443615319,
		"scent": "isle",
		"format": "fullsize",
		"product_type": "wash"
	},
	{
		"variant_id": 29452444401751,
		"scent": "meadow",
		"format": "fullsize",
		"product_type": "wash"
	},
	{
		"variant_id": 29452417695831,
		"scent": "capri",
		"format": "fullsize",
		"product_type": "wash"
	},
	{
		"variant_id": 29452435914839,
		"scent": "coral",
		"format": "fullsize",
		"product_type": "wash"
	},
	{
		"variant_id": 29452561449047,
		"scent": "isle",
		"format": "fullsize",
		"product_type": "lotion"
	},
	{
		"variant_id": 29452565151831,
		"scent": "meadow",
		"format": "fullsize",
		"product_type": "lotion"
	},
	{
		"variant_id": 29452551028823,
		"scent": "capri",
		"format": "fullsize",
		"product_type": "lotion"
	},
	{
		"variant_id": 29452557516887,
		"scent": "coral",
		"format": "fullsize",
		"product_type": "lotion"
	},
	{
		"variant_id": 29450197565527,
		"scent": "isle",
		"format": "fullsize",
		"product_type": "bundle"
	},
	{
		"variant_id": 29450201464919,
		"scent": "meadow",
		"format": "fullsize",
		"product_type": "bundle"
	},
	{
		"variant_id": 29450181935191,
		"scent": "capri",
		"format": "fullsize",
		"product_type": "bundle"
	},
	{
		"variant_id": 29450196680791,
		"scent": "coral",
		"format": "fullsize",
		"product_type": "bundle"
	},
	{
		"variant_id": 31128964661335,
		"scent": "isle",
		"format": "fullsize",
		"product_type": "bundle_onetime"
	},
	{
		"variant_id": 31167600754775,
		"scent": "meadow",
		"format": "fullsize",
		"product_type": "bundle_onetime"
	},
	{
		"variant_id": 31167596822615,
		"scent": "capri",
		"format": "fullsize",
		"product_type": "bundle_onetime"
	},
	{
		"variant_id": 31167598100567,
		"scent": "coral",
		"format": "fullsize",
		"product_type": "bundle_onetime"
	},
	{
		"variant_id": 30709997109335,
		"scent": "isle",
		"format": "fullsize",
		"product_type": "wash_body_cream_bundle"
	},
	{
		"variant_id": 30739628687447,
		"scent": "capri",
		"format": "fullsize",
		"product_type": "wash_body_cream_bundle"
	},
	{
		"variant_id": 31000238456919,
		"scent": "coral",
		"format": "fullsize",
		"product_type": "wash_body_cream_bundle"
	},
	{
		"variant_id": 31000244125783,
		"scent": "meadow",
		"format": "fullsize",
		"product_type": "wash_body_cream_bundle"
	},
	{
		"variant_id": 30739686031447,
		"scent": "isle",
		"format": "fullsize",
		"product_type": "travel_set"
	},
	{
		"variant_id": 30739707723863,
		"scent": "capri",
		"format": "fullsize",
		"product_type": "travel_set"
	},
	{
		"variant_id": 31000208441431,
		"scent": "coral",
		"format": "fullsize",
		"product_type": "travel_set"
	},
	{
		"variant_id": 31000225284183,
		"scent": "meadow",
		"format": "fullsize",
		"product_type": "travel_set"
	},
	{
		"variant_id": 30739728302167,
		"scent": "capri",
		"format": "fullsize",
		"product_type": "hydration_kit"
	},
	{
		"variant_id": 30739749044311,
		"scent": "coral",
		"format": "fullsize",
		"product_type": "hydration_kit"
	},
	{
		"variant_id": 30739739639895,
		"scent": "isle",
		"format": "fullsize",
		"product_type": "hydration_kit"
	},
	{
		"variant_id": 30739754385495,
		"scent": "meadow",
		"format": "fullsize",
		"product_type": "hydration_kit"
	},
	{
		"variant_id": 30975442157655,
		"scent": "capri",
		"format": "fullsize",
		"product_type": "shower_set"
	},
	{
		"variant_id": 30975469912151,
		"scent": "coral",
		"format": "fullsize",
		"product_type": "shower_set"
	},
	{
		"variant_id": 30975480987735,
		"scent": "isle",
		"format": "fullsize",
		"product_type": "shower_set"
	},
	{
		"variant_id": 30975479218263,
		"scent": "meadow",
		"format": "fullsize",
		"product_type": "shower_set"
	},
	{
		"variant_id": 30975488065623,
		"scent": "scent_club_spring",
		"format": "fullsize",
		"product_type": "best_of_scent_club"
	},
	{
		"variant_id": 30975494226007,
		"scent": "scent_club_summer",
		"format": "fullsize",
		"product_type": "best_of_scent_club"
	},
	{
		"variant_id": 30975495405655,
		"scent": "scent_club_fall",
		"format": "fullsize",
		"product_type": "best_of_scent_club"
	},
	{
		"variant_id": 30975499272279,
		"scent": "scent_club_winter",
		"format": "fullsize",
		"product_type": "best_of_scent_club"
	},
	{
		"variant_id": 29533581738071,
		"scent": "isle",
		"format": "fullsize",
		"product_type": "handcream"
	},
	{
		"variant_id": 29533584719959,
		"scent": "meadow",
		"format": "fullsize",
		"product_type": "handcream"
	},
	{
		"variant_id": 29532336750679,
		"scent": "capri",
		"format": "fullsize",
		"product_type": "handcream"
	},
	{
		"variant_id": 29533587505239,
		"scent": "coral",
		"format": "fullsize",
		"product_type": "handcream"
	},
	{
		"variant_id": 31215683797079,
		"scent": "isle",
		"format": "fullsize",
		"product_type": "skylar_favorites"
	},
	{
		"variant_id": 31215681994839,
		"scent": "meadow",
		"format": "fullsize",
		"product_type": "skylar_favorites"
	},
	{
		"variant_id": 31215645360215,
		"scent": "capri",
		"format": "fullsize",
		"product_type": "skylar_favorites"
	},
	{
		"variant_id": 31215646277719,
		"scent": "coral",
		"format": "fullsize",
		"product_type": "skylar_favorites"
	},
	{
		"variant_id": 31298406318167,
		"scent": "isle",
		"format": "fullsize",
		"product_type": "travel_duo"
	},
	{
		"variant_id": 31298416541783,
		"scent": "meadow",
		"format": "fullsize",
		"product_type": "travel_duo"
	},
	{
		"variant_id": 31298396323927,
		"scent": "capri",
		"format": "fullsize",
		"product_type": "travel_duo"
	},
	{
		"variant_id": 31298409758807,
		"scent": "coral",
		"format": "fullsize",
		"product_type": "travel_duo"
	},
	{
		"variant_id": 31298420441175,
		"scent": "isle",
		"format": "fullsize",
		"product_type": "care_trio"
	},
	{
		"variant_id": 31298421456983,
		"scent": "meadow",
		"format": "fullsize",
		"product_type": "care_trio"
	},
	{
		"variant_id": 31298420015191,
		"scent": "capri",
		"format": "fullsize",
		"product_type": "care_trio"
	},
	{
		"variant_id": 31298420867159,
		"scent": "coral",
		"format": "fullsize",
		"product_type": "care_trio"
	},
	{
		"variant_id": 31298509602903,
		"scent": "isle",
		"format": "fullsize",
		"product_type": "body_fragrance_duo"
	},
	{
		"variant_id": 31298512355415,
		"scent": "meadow",
		"format": "fullsize",
		"product_type": "body_fragrance_duo"
	},
	{
		"variant_id": 31298500395095,
		"scent": "capri",
		"format": "fullsize",
		"product_type": "body_fragrance_duo"
	},
	{
		"variant_id": 31298504097879,
		"scent": "coral",
		"format": "fullsize",
		"product_type": "body_fragrance_duo"
	}
]', true);

$attributes_by_variant = [];

foreach($variant_attributes as $attribute_list){
	$attributes_by_variant[$attribute_list['variant_id']] = $attribute_list;
	unset($attributes_by_variant[$attribute_list['variant_id']]['variant_id']);
}

$products_by_id = [];
$stmt_product_metafields = $db->prepare("SELECT namespace, `key`, `value` FROM metafields WHERE owner_resource='product' AND owner_id=?");
$stmt_variant_metafields = $db->prepare("SELECT namespace, `key`, `value` FROM metafields WHERE owner_resource='variant' AND owner_id=?");
foreach($all_products as $product){
	$variants = [];
	$product['metafields'] = new ArrayObject();
	$stmt_product_metafields->execute([$product['id']]);
	foreach($stmt_product_metafields->fetchAll() as $metafield){
		if(!array_key_exists($metafield['namespace'], $product['metafields'])){
			$product['metafields'][$metafield['namespace']] = [];
		}
		$product['metafields'][$metafield['namespace']][$metafield['key']] = $metafield['value'];
		continue;
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
			$variant['metafields'][$metafield['namespace']][$metafield['key']] = $metafield['value'];
			continue;
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

echo json_encode([
	'products' => $products_by_id,
	'attributes' => $product_attributes,
]);