<?php // Product filter endpoint with optional seller filtering. ?>
if ($f == 'get_prodects_by_filter') {
    $data['status'] = 400;
    $cat_id = !empty($_POST['cat_id']) ? Wo_Secure($_POST['cat_id']) : '';
    $distance = !empty($_POST['distance']) ? Wo_Secure($_POST['distance']) : '';
    $price_sort = !empty($_POST['price_sort']) ? Wo_Secure($_POST['price_sort']) : '';
    $sub_id = !empty($_POST['sub_id']) ? Wo_Secure($_POST['sub_id']) : '';
    $user_id = (!empty($_POST['user_id']) && is_numeric($_POST['user_id'])) ? Wo_Secure($_POST['user_id']) : 0;
    $products = Wo_GetProducts(array('c_id' => $cat_id,
                                     'length' => $distance,
                                     'order_by' => $price_sort,
                                     'sub_id' => $sub_id,
                                     'user_id' => $user_id));
    $html = '';
    if (!empty($products)){
        foreach ($products as $key => $wo['product']) {
            $html .= Wo_LoadPage('products/products-list'); 
        }
    }
    if (!empty($html)) {
        $data['status'] = 200;
        $data['html'] = $html;
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}

// NEW STORY 
