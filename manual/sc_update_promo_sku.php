<?php
require_once(__DIR__.'/../includes/config.php');

$rc = new RechargeClient();
$sc = new ShopifyClient();

$f = fopen(__DIR__.'/upcoming_promos.csv', 'r');

$headers = fgetcsv($f);

$rownum = 0;
while($row = fgetcsv($f)) {
    $rownum++;
    $row = array_combine($headers, $row);
    print_r($row);
    $row['order_id'] = $row['recharge shipping id'];

    $res = $rc->get('/orders/'.$row['order_id']);
    if(empty($res['order'])){
        print_r($res);
        echo "ERROR CANNOT FIND ORDER";
        die();
    }
    if(count($res['order']['line_items']) != 1){
        print_r($res);
        echo "Not 1 line item!";
        die();
    }

    $res['order']['line_items'][0]['sku'] = '10213905-113';
    $res['order']['line_items'][0]['product_id'] = $res['order']['line_items'][0]['shopify_product_id'];
    $res['order']['line_items'][0]['variant_id'] = $res['order']['line_items'][0]['shopify_variant_id'];

    $line_item = [
        'sku' => '10213905-113',
        'price' => $res['order']['line_items'][0]['price'],
        'properties' => $res['order']['line_items'][0]['properties'],
        'quantity' => $res['order']['line_items'][0]['quantity'],
        'subscription_id' => $res['order']['line_items'][0]['subscription_id'],
        'title' => $res['order']['line_items'][0]['title'],
        'product_title' => $res['order']['line_items'][0]['product_title'],
        'variant_title' => $res['order']['line_items'][0]['variant_title'],
        'product_id' => $res['order']['line_items'][0]['shopify_product_id'],
        'variant_id' => $res['order']['line_items'][0]['shopify_variant_id'],
    ];

    $res = $rc->put('/orders/'.$row['order_id'], ['line_items' => [$line_item]]);
    print_r($res);
    if(empty($res['order'])){
        print_r($res);
        echo "ERROR CANNOT UPDATE ORDER";
        die();
    }
//    die();
}