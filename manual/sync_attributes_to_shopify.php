<?php
require_once(__DIR__.'/../includes/config.php');

$attributes = ['scent', 'product_type', 'format'];
$new_attributes = [];
$attribute_values = [
	'product_type' => $db->query("SELECT code, p.* FROM product_types p")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE),
	'scent' => $db->query("SELECT code, s.* FROM scents s")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE),
	'format' => $db->query("SELECT code, p.* FROM product_formats p")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE),
];


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
			echo "Error!";
			print_r($sc->last_error);
			die();
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