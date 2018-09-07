<?php
//
/*
 * 
 * 
 * maven 
 * 
 */
die();
$authToken = '069f9828d19cdfea4f5a73f43d28a891257dab5e5c03afc9f68a1e0e';

function initGetRequest($url) {
    global $authToken;
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_POST => FALSE,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_HTTPHEADER => array(
            'x-recharge-access-token: ' . $authToken,
            'Content-Type: application/json'
        ),
    ));
    $response = curl_exec($ch);
    if ($response === FALSE) {
        die(curl_error($ch));
    }
    return $responseData = json_decode($response, TRUE);
}

function initPostRequest($url, $data) {
    global $authToken;
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_POST => TRUE,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_HTTPHEADER => array(
            'x-recharge-access-token: ' . $authToken,
            'Content-Type: application/json'
        ),
        CURLOPT_POSTFIELDS => json_encode($data)
    ));
    $response = curl_exec($ch);
    if ($response === FALSE) {
        die(curl_error($ch));
    }
    return $responseData = json_decode($response, TRUE);
}

function initPutRequest($url, $data) {
    global $authToken;
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_POST => TRUE,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_HTTPHEADER => array(
            'x-recharge-access-token: ' . $authToken,
            'Content-Type: application/json'
        ),
        CURLOPT_POSTFIELDS => json_encode($data)
    ));
    $response = curl_exec($ch);
    if ($response === FALSE) {
        die(curl_error($ch));
    }
    return $responseData = json_decode($response, TRUE);
}

//insert delay
$timest = time();
$fs = fopen("delay.txt", "r");
$ft = fopen("target-" . $timest . ".txt", "w");
while ($ch = fgets($fs)) {
    fputs($ft, $ch);
}
fclose($fs);
fclose($ft);

if (!unlink("target-" . $timest . ".txt")) {
    echo ("Error deleting");
} else {
    echo ("Deleted");
}

$timest1 = time();
$fs1 = fopen("delay.txt", "r");
$ft1 = fopen("target1-" . $timest1 . ".txt", "w");
while ($ch = fgets($fs1)) {
    fputs($ft1, $ch);
}
fclose($fs1);
fclose($ft1);

if (!unlink("target1-" . $timest1 . ".txt")) {
    echo ("Error deleting");
} else {
    echo ("Deleted");
}
//*********************************************************//


$webhookContent = "";
$webhook = fopen('php://input', 'rb');
while (!feof($webhook)) {
    $webhookContent .= fread($webhook, 4096);
}
fclose($webhook);
$order = json_decode($webhookContent);
$order_id = $order->id;
//$order_id = '510209982551';


$string = file_get_contents("order.txt");
$string = explode(",", $string);
print_r($string);
if(!in_array($order_id, $string)){
    $ft2 = fopen("order.txt", "a");
    fputs($ft2, "," . $order_id);
    fclose($ft2);

    $orders = initGetRequest("https://api.rechargeapps.com/orders?shopify_order_id=$order_id");
    if (!empty($orders['orders'][0]['line_items'])) {
        foreach ($orders['orders'][0]['line_items'] as $lineItem) {
            // 738567520343 - "Full Size Skylar Scent"
            // 738394865751 - "Full Size Skylar Scent"
            // 738567323735 - "Scent Subscription"
            if ($lineItem['shopify_product_id'] == '738567520343' || $lineItem['shopify_product_id'] == '738394865751' || $lineItem['shopify_product_id'] == '738567323735') {
                $subscriptionId = $lineItem['subscription_id'];
                $subscriptionData = initGetRequest("https://api.rechargeapps.com/subscriptions/$subscriptionId");
                $orderInterval = $subscriptionData['subscription']['order_interval_frequency'];
                $quantity = '';
                $price = '';
                $data = array();
                $properties = $subscriptionData['subscription']['properties'];
                if ($orderInterval > 1) {
                    $flag = false;
                    $orderIntervalCount = '';
                    $propKeyCount = 0;
                    foreach ($subscriptionData['subscription']['properties'] as $key => $value) {
                        $propKeyCount++;
                        if ($value['name'] == "order_interval_count") {
                            echo "TRUE";
                            $flag = true;
                            $orderIntervalCount = $value['value'];
                            $value['value'] = $value['value'] + 1;
                        }
                        if ($value['name'] == "total_items") {
                            $quantity = $value['value'];
                        }

                        $properties[$key] = $value;
                    }
                    if ($flag) {

                        echo "<br/>TIME - " . $orderIntervalCount;

                        if ($orderIntervalCount == 1) {
                            if ($quantity == 1) {
                                $price = 66.30;
                            } elseif ($quantity == 2) {
                                $price = 102;
                            } elseif ($quantity == 3) {
                                $price = 151.30;
                            } elseif ($quantity == 4) {
                                $price = 170;
                            } else {
                                $price = 0;
                            }
                            $data = [
                                "price" => $price,
                                "properties" => $properties
                            ];
                        }
                    } else {
                        $propData = array(
                            "name" => 'order_interval_count',
                            "value" => 1
                        );
                        $properties[$propKeyCount] = $propData;
//                    $properties[++$propKeyCount] = $propData1;
                        $data = [
                            "properties" => $properties
                        ];
                    }
                    $result = initPutRequest("https://api.rechargeapps.com/subscriptions/$subscriptionId", $data);
                }
            }
        }
    }
}else{
    echo "EXISTS";
}


