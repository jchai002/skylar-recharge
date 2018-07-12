<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Credentials: true");
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Max-Age: 1000');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization');
/*
 * 
 * 
 * skylar maven
 * 
 */

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

function fetchFrequencyBySubscriptionId($subId) {
    $subscriptionId = $subId;
    $subscriptionData = initGetRequest("https://api.rechargeapps.com/subscriptions/$subscriptionId");
    return $subscriptionData['subscription'];
}

function fetchChargeDetail($chargeId) {
    $chargeData = initGetRequest("https://api.rechargeapps.com/charges/$chargeId");
    return $chargeData;
}

function fetchAddressByAddressId($addressId) {
    $addressData = initGetRequest("https://api.rechargeapps.com/addresses/$addressId");
    return $addressData;
}

if (isset($_GET['index']) && $_GET['index'] == 1) {
    $customerId = $_GET['customer_id'];
    if (isset($_GET['customer_id']) && $_GET['customer_id']) {
        $subscriptionList = initGetRequest("https://api.rechargeapps.com/subscriptions?shopify_customer_id=$customerId");
        echo json_encode($subscriptionList);
    }
}

if (isset($_GET['index']) && $_GET['index'] == 7) {
    $subscriptionId = $_GET['subscription_id'];
    $singleDetail = initGetRequest("https://api.rechargeapps.com/subscriptions/$subscriptionId");
    $properties = $singleDetail['subscription']['properties'];
    $orderIntervalCount = '';
    $totalItems = ''; 
    
//        $properties= array(
//        array(
//            "name" =>"charge_delay",
//            "value" => 17
//        ),
//        array(
//            "name" =>"qty_1",
//            "value" => 1
//        ),
//        array(
//            "name" =>"handle_1",
//            "value" => "isle-5"
//        ),
//        array(
//            "name" =>"shipping_interval_frequency",
//            "value" => 6
//        ),
//        array(
//            "name" =>"recurring_price",
//            "value" => 49
//        ),
//        array(
//            "name" =>"total_items",
//            "value" => 1
//        ),       
//        array(
//            "name" =>"shipping_interval_unit_type",
//            "value" => "Months"
//        ),
//        array(
//            "name" =>"order_interval_count",
//            "value" => 1
//        ),
//        
//    );
//    $data = [
//        "price" => 49.3,         
//        "properties" => $properties
//    ];
//    initPutRequest("https://api.rechargeapps.com/subscriptions/$subscriptionId", $data);
//    exit;
    
    
    foreach ($properties as $key => $value) {
        $arr = explode("_", $value['name']);
        if ($arr[0] == "handle") {
            $lineItemArray[$arr[1]]['handle'] = $value['value'];
        }
        if ($arr[0] == "qty") {
            $lineItemArray[$arr[1]]['quantity'] = $value['value'];
        }
        if ($value['name'] == 'order_interval_count') {
            $orderIntervalCount = $value['value'];
        }
        if ($value['name'] == 'total_items') {
            $totalItems = $value['value'];
        }
    }
    $address['address'] = fetchAddressByAddressId($singleDetail['subscription']['address_id']);
    $singleDetail['subscription']['address'] = $address['address'];
    $singleDetail['subscription']['line_items'] = $lineItemArray;
    $singleDetail['subscription']['order_interval_count'] = $orderIntervalCount;
    $singleDetail['subscription']['total_items'] = $totalItems;
    echo json_encode($singleDetail);
}

if (isset($_GET['subscription_id']) && isset($_GET['frequency']) && $_GET['frequency'] && $_GET['subscription_id']) {
    $subscriptionId = $_GET['subscription_id'];
    $frequency = $_GET['frequency'];
    $subscriptionData = initGetRequest("https://api.rechargeapps.com/subscriptions/$subscriptionId");
    $properties = array();

//    $quantity = $subscriptionData['subscription']['quantity'];
    $quantity = '';
    
    $status = $subscriptionData['subscription']['status'];

    $orderIntervalCount = '';
    $properties = $subscriptionData['subscription']['properties'];
    foreach ($subscriptionData['subscription']['properties'] as $key => $value) {
        if ($value['name'] == "shipping_interval_frequency") {
            $value['value'] = $frequency;
        }
        
        if ($value['name'] == "order_interval_count") {
            $orderIntervalCount = $value['value'];
        }
        
        if ($value['name'] == "total_items") {
            $quantity = $value['value'];
        }
        $properties[$key] = $value;
    }

    if ($orderIntervalCount > 1) {
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
    } elseif ($orderIntervalCount == 1) {
        if ($quantity == 1) {
            $price = 49.30;
        } elseif ($quantity == 2) {
            $price = 85;
        } elseif ($quantity == 3) {
            $price = 134.30;
        } elseif ($quantity == 4) {
            $price = 153;
        } else {
            $price = 0;
        }
    } else {
        if ($quantity == 1) {
            $price = 58;
        } elseif ($quantity == 2) {
            $price = 100;
        } elseif ($quantity == 3) {
            $price = 158;
        } elseif ($quantity == 4) {
            $price = 180;
        } else {
            $price = 0;
        }
    }
    
    $data = [
        "order_interval_unit" => "month",
        "order_interval_frequency" => $frequency,
        "charge_interval_frequency" => $frequency,
        "price" => $price,
        "properties" => $properties,
        "status" => "ACTIVE"
    ];

    initPutRequest("https://api.rechargeapps.com/subscriptions/$subscriptionId", $data);
    $result = "Product frequency updated successfully";
    $subscriptionData = initGetRequest("https://api.rechargeapps.com/subscriptions/$subscriptionId");
    echo json_encode($subscriptionData);
}

if (isset($_GET['subscription_id']) && $_GET['subscription_id'] && $_GET['index'] == 8) {
    $subscriptionId = $_GET['subscription_id'];
    $subscriptionById = initGetRequest("https://api.rechargeapps.com/subscriptions/$subscriptionId");
    if (isset($subscriptionById['subscription']) && $subscriptionById['subscription']) {
        echo json_encode($subscriptionById['subscription']);
    } else {
        $msg = 'Subscription not found';
        echo json_encode($msg);
    }
}


if (isset($_GET['subscription_id']) && isset($_GET['quantity']) && $_GET['subscription_id'] && isset($_GET['action']) && $_GET['action'] == 'quantity-update') {
    $subscriptionId = $_GET['subscription_id'];
    $quantity = $_GET['quantity'];
    $index = $_GET['index'];
    $subscriptionData = initGetRequest("https://api.rechargeapps.com/subscriptions/$subscriptionId");
    /**
     * 
     * subscription quantity
     * 
     */
    $subscriptionDataProperties = $subscriptionData['subscription']['properties'];
    $status = $subscriptionData['subscription']['status'];
    $oldQuantity = '';
    $orderIntervalCount = '';
    $totalItems = 0;
    foreach ($subscriptionDataProperties as $key => $value) {
        if($value['value'] == 'capri-sample'){
            $value['value'] = 'capri-subscription';
        }
        if ($value['name'] == 'qty_' . $index) {
            $oldQuantity = $value['value'];
            $value['value'] = $quantity;
        }

        if ($value['name'] == "order_interval_count") {
            $orderIntervalCount = $value['value'];
        }

        $properties[$key] = $value;

        if ($value['name'] == 'total_items') {
            $totalItemKey = $key;
            $totalItemOldValue = $value['value'];
        }
        if(stripos($value['name'], 'qty_') !== false){
            $totalItems += $value['value'];
        }
    };
    $properties[$totalItemKey] = array(
        "name" => "total_items",
        "value" => $totalItems
    );

    $frequency = $subscriptionData['subscription']['order_interval_frequency'];

    if ($orderIntervalCount > 1) {
        if ($totalItems == 1) {
            $price = 66.30;
        } elseif ($totalItems == 2) {
            $price = 102;
        } elseif ($totalItems == 3) {
            $price = 151.30;
        } elseif ($totalItems == 4) {
            $price = 170;
        } else {
            $price = 0;
        }
    } elseif ($orderIntervalCount == 1) {
        if ($totalItems == 1) {
            $price = 49.30;
        } elseif ($totalItems == 2) {
            $price = 85;
        } elseif ($totalItems == 3) {
            $price = 134.30;
        } elseif ($totalItems == 4) {
            $price = 153;
        } else {
            $price = 0;
        }
    } else {
        if ($totalItems == 1) {
            $price = 58;
        } elseif ($totalItems == 2) {
            $price = 100;
        } elseif ($totalItems == 3) {
            $price = 158;
        } elseif ($totalItems == 4) {
            $price = 180;
        } else {
            $price = 0;
        }
    }


    $data = [
        "quantity" => 1,
        "price" => $price,
        "properties" => $properties
    ];

    initPutRequest("https://api.rechargeapps.com/subscriptions/$subscriptionId", $data);
    $subscriptionById = initGetRequest("https://api.rechargeapps.com/subscriptions/$subscriptionId");
    echo json_encode($subscriptionById['subscription']);
}


if (isset($_GET['subscription_id']) && isset($_GET['reason']) && isset($_GET['action']) && $_GET['subscription_id'] && $_GET['reason'] && $_GET['action'] == 'cancel-subscription') {
    $subscriptionId = $_GET['subscription_id'];
    $data = [
        "cancellation_reason" => $_GET['reason']
    ];
    $cancleSubscription = initPostRequest("https://api.rechargeapps.com/subscriptions/$subscriptionId/cancel", $data);
    echo json_encode($cancleSubscription);
}

if (isset($_GET['subscription_id']) && isset($_GET['action']) && $_GET['subscription_id'] && $_GET['action'] == 'activate-subscription') {
    $subscriptionId = $_GET['subscription_id'];
    $data = [
        "status" => "ACTIVE"
    ];
    $activateSubscription = initPostRequest("https://api.rechargeapps.com/subscriptions/$subscriptionId/activate", $data);
    echo json_encode($activateSubscription);
}


if (isset($_GET['subscription_id']) && isset($_GET['action']) && isset($_GET['date']) && $_GET['subscription_id'] && $_GET['date'] && $_GET['action'] == 'update-next-charge-date') {
    $subscriptionId = $_GET['subscription_id'];
    $data = [
        "date" => $_GET['date']
    ];   
    $result = initPostRequest("https://api.rechargeapps.com/subscriptions/$subscriptionId/set_next_charge_date", $data);
    echo json_encode($result);
}


if (isset($_GET['data']) && $_GET['data'] && $_GET['action'] == 'add-subscription') {
    $data = $_GET['data'];
    $data = json_decode($data);
    $result = initPostRequest("https://api.rechargeapps.com/subscriptions", $data);
    echo json_encode($result);
}


if (isset($_GET['customer_id']) && isset($_GET['action']) && $_GET['customer_id'] && $_GET['action'] == 'fetch-customer-address') {
    $customerId = $_GET['customer_id'];
    $customerDetail = initGetRequest("https://api.rechargeapps.com/customers?shopify_customer_id=$customerId");
    $rechargeAppCustomerid = $customerDetail['customers'][0]['id'];
    $customerAddress = initGetRequest("https://api.rechargeapps.com/customers/$rechargeAppCustomerid/addresses");
    echo json_encode($customerAddress['addresses']);
}

if (isset($_GET['address_id']) && isset($_GET['action']) && $_GET['address_id'] && $_GET['action'] == 'fetch-customer-address-by-id') {
    $addressId = $_GET['address_id'];
    $address = initGetRequest("https://api.rechargeapps.com/addresses/$addressId");
    echo json_encode($address['address']);
}

if (isset($_POST['form_data']) && isset($_POST['action']) && $_POST['form_data'] && $_POST['action'] == 'add-and-update-customer-address') {
    $customerAddress = $_POST['form_data'];
    parse_str($customerAddress, $data);
    if (!empty($data)) {
        $id = $data['id'];
        unset($data['id']);
        $result = initPutRequest("https://api.rechargeapps.com/addresses/$id", $data);
        echo json_encode($result);
    } else {
        $result = 'Error in form submission.';
        echo json_encode($result);
    }
}

if (isset($_GET['subscription_id']) && isset($_GET['handle']) && isset($_GET['quantity']) && $_GET['subscription_id'] && $_GET['handle'] && $_GET['quantity'] && $_GET['action'] == 'update-line-item-quantity') {
    $subscriptionId = $_GET['subscription_id'];
    $index = $_GET['index'];
    $quantity = $_GET['quantity'];
    $subscriptionById = initGetRequest("https://api.rechargeapps.com/subscriptions/$subscriptionId");
    $subscriptionDataProperties = $subscriptionById['subscription']['properties'];
    $properties = array();
    $subscriptionQuantity = 0;
    foreach ($subscriptionDataProperties as $key => $value) {
        if ($value['name'] == 'qty_' . $index) {
            $oldQuantity = $value['value'];
            $value['value'] = $quantity;
        }
        if ($value['name'] == 'total_items') {
            $totalitems = ($value['value'] - $oldQuantity) + $quantity;
            $value['value'] = $totalitems;
        }
        $properties[$key] = $value;
    }
//    $quantity = $subscriptionById['subscription']['quantity'] + $quantity;
    $data = [
        'quantity' => 1,
        "properties" => $properties
    ];
    initPutRequest("https://api.rechargeapps.com/subscriptions/$subscriptionId", $data);
    $subscriptionById = initGetRequest("https://api.rechargeapps.com/subscriptions/$subscriptionId");
    echo json_encode($subscriptionById);
}

if (isset($_GET['subscription_id']) && isset($_GET['product_handle']) && isset($_GET['action']) && $_GET['subscription_id'] && $_GET['product_handle'] && $_GET['action'] == 'update-subscription') {
    $subscriptionId = $_GET['subscription_id'];
    $productHandle = $_GET['product_handle'];
    $subscriptionData = initGetRequest("https://api.rechargeapps.com/subscriptions/$subscriptionId");
    $subscriptionDataProperties = $subscriptionData['subscription']['properties'];
    $status = $subscriptionData['subscription']['status'];
    
    $quantity = $_GET['quantity'];
    
    $properties = array();
    $flag = false;
    $index = '';
    if ($quantity < 4) {
        $i = 0;
        $orderIntervalCount = '';
        foreach ($subscriptionDataProperties as $key => $value) {
            $i++;
            $arr = explode("_", $value['name']);
            if ($arr[0] == "handle") {
                $index = $arr[1];
            }

            if ($value['name'] == "order_interval_count") {
                $orderIntervalCount = $value['value'];
            }

            $properties[$key] = $value;

            if ($value['name'] == 'total_items') {
                $totalItemKey = $key;
                $totalItems = $value['value'];
            }

            if ($arr[0] == "handle") {
                if ($value['value'] == $productHandle) {
                    $flag = true;
                }
            }
        }
        $index = $index + 1;
        if (!$flag) {
            $totalItems = $totalItems + 1;
            $properties[$i] = array(
                "name" => "handle_" . $index,
                "value" => $productHandle
            );
            $i++;
            $properties[$i] = array(
                "name" => "qty_" . $index,
                "value" => 1
            );
            $properties[$totalItemKey] = array(
                "name" => 'total_items',
                "value" => $totalItems
            );
            $frequency = $subscriptionData['subscription']['order_interval_frequency'];            
            if ($orderIntervalCount > 1) {
                if ($totalItems == 1) {
                        $price = 66.30;
                } elseif ($totalItems == 2) {
                        $price = 102;
                } elseif ($totalItems == 3) {
                        $price = 151.30;
                } elseif ($totalItems == 4) {
                        $price = 170;
                } else {
                        $price = 0;
                }
            } elseif($orderIntervalCount == 1) {
                if ($totalItems == 1) {
                        $price = 49.30;
                } elseif ($totalItems == 2) {
                        $price = 85;
                } elseif ($totalItems == 3) {
                        $price = 134.30;
                } elseif ($totalItems == 4) {
                        $price = 153;
                } else {
                        $price = 0;
                }
            } else {
                if ($totalItems == 1) {
                        $price = 58;
                } elseif ($totalItems == 2) {
                        $price = 100;
                } elseif ($totalItems == 3) {
                        $price = 158;
                } elseif ($totalItems == 4) {
                        $price = 180;
                } else {
                        $price = 0;
                }
            }            
            $data = array(
                "quantity" => 1,
                "price" => $price,
                "properties" => $properties
            );
            initPutRequest("https://api.rechargeapps.com/subscriptions/$subscriptionId", $data);
            $subscriptionById = initGetRequest("https://api.rechargeapps.com/subscriptions/$subscriptionId");
            echo json_encode($subscriptionById);
        } else {
            $result = "Already added";
            echo json_encode($result);
        }
    } else {
        $result = 'quantity exceeded';
        echo json_encode($result);
    }
}

if (isset($_GET['subscription_id']) && $_GET['subscription_id'] && isset($_GET['new_product_handle']) && $_GET['new_product_handle'] && isset($_GET['old_product_handle']) && $_GET['old_product_handle'] && $_GET['action'] == 'change-product-subscription') {
    $subscriptionId = $_GET['subscription_id'];
    $oldProductHandle = $_GET['old_product_handle'];
    $newProductHandle = $_GET['new_product_handle'];
    $subscriptionData = initGetRequest("https://api.rechargeapps.com/subscriptions/$subscriptionId");
    $subscriptionDataProperties = $subscriptionData['subscription']['properties'];
    $properties = array();
    $flag = false;
    $itemKey = '';
    $index = $_GET['index'];
    foreach ($subscriptionDataProperties as $key => $value) {
        $arr = explode("_", $value['name']);
        if ($arr[0] == "handle") {
            if ($value['value'] == $newProductHandle) {
                $flag = true;
            }
            if ($value['value'] == $oldProductHandle) {
                $itemKey = $key;
            }
        }
        $properties[$key] = $value;
    }
    if (!$flag) {        
        $properties[$itemKey] = array(
            "name" => "handle_" . $index,
            "value" => $newProductHandle
        );
        $data = array(
            "properties" => $properties
        );
        initPutRequest("https://api.rechargeapps.com/subscriptions/$subscriptionId", $data);
        $subscriptionById = initGetRequest("https://api.rechargeapps.com/subscriptions/$subscriptionId");
        echo json_encode($subscriptionById);
        exit;
    } else {
        $result = "Product already added.";
        echo json_encode($result);
        exit;
    }
}

?>