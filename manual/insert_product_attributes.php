<?php
require_once(__DIR__.'/../includes/config.php');

$variant_rows = json_decode('[
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
        "variant_id": 12235409129559,
        "scent": "arrow",
        "format": "rollie",
        "product_type": "fragrance"
    },
    {
        "variant_id": 12235492425815,
        "scent": "capri",
        "format": "rollie",
        "product_type": "fragrance"
    },
    {
        "variant_id": 12235492360279,
        "scent": "coral",
        "format": "rollie",
        "product_type": "fragrance"
    },
    {
        "variant_id": 12235492327511,
        "scent": "isle",
        "format": "rollie",
        "product_type": "fragrance"
    },
    {
        "variant_id": 12235492393047,
        "scent": "meadow",
        "format": "rollie",
        "product_type": "fragrance"
    },
    {
        "variant_id": 12588614484055,
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
    }
]', true);

$stmt = $db->prepare("INSERT INTO variant_attributes (variant_id, scent_id, format_id, product_type_id) VALUES (:variant_id, :scent_id, :format_id, :product_type_id)");
$scents = $db->query("SELECT code, id FROM scents")->fetchAll(PDO::FETCH_KEY_PAIR);
$formats = $db->query("SELECT code, id FROM product_formats")->fetchAll(PDO::FETCH_KEY_PAIR);
$types = $db->query("SELECT code, id FROM product_types")->fetchAll(PDO::FETCH_KEY_PAIR);
$variants = $db->query("SELECT shopify_id, id FROM variants")->fetchAll(PDO::FETCH_KEY_PAIR);
foreach($variant_rows as $row){
	$stmt->execute([
		'variant_id' => $variants[$row['variant_id']],
		'scent_id' => $scents[$row['scent']],
		'format_id' => $formats[$row['format']],
		'product_type_id' => $types[$row['product_type']],
	]);
}