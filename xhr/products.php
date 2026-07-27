<?php // Product AJAX handlers for market creation, editing, and order workflows.
if (!function_exists('Wo_ProductColumnExists')) {
    function Wo_ProductColumnExists($column_name = '') {
        global $sqlConnect;

        if (empty($column_name)) {
            return false;
        }

        static $product_columns = null;
        if ($product_columns === null) {
            $product_columns = array();
            $query = mysqli_query($sqlConnect, "SHOW COLUMNS FROM " . T_PRODUCTS);
            if ($query) {
                while ($column = mysqli_fetch_assoc($query)) {
                    if (!empty($column['Field'])) {
                        $product_columns[$column['Field']] = true;
                    }
                }
            }
        }

        return !empty($product_columns[$column_name]);
    }
}
if (!function_exists('Wo_ProductPriceColumnSupportsJson')) {
    function Wo_ProductPriceColumnSupportsJson() {
        global $sqlConnect;

        static $supports_json = null;
        if ($supports_json !== null) {
            return $supports_json;
        }

        $supports_json = false;
        $query = mysqli_query($sqlConnect, "SHOW COLUMNS FROM " . T_PRODUCTS . " LIKE 'price'");
        if ($query && ($column = mysqli_fetch_assoc($query)) && !empty($column['Type'])) {
            $supports_json = (bool) preg_match('/text|char|json|blob/i', $column['Type']);
        }

        return $supports_json;
    }
}
if (!function_exists('Wo_NormalizeProductPhotoUploads')) {
    function Wo_NormalizeProductPhotoUploads(&$uploads) {
        $invalid_files = array();
        if (empty($uploads['name']) || !is_array($uploads['name'])) {
            return $invalid_files;
        }

        $native_formats = array(
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif'
        );
        $convertible_formats = array(
            'image/webp' => 'webp',
            'image/avif' => 'avif',
            'image/heic' => 'heic',
            'image/heif' => 'heif',
            'image/heic-sequence' => 'heic',
            'image/heif-sequence' => 'heif'
        );
        $extension_mimes = array(
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'avif' => 'image/avif',
            'heic' => 'image/heic',
            'heif' => 'image/heif'
        );

        foreach ($uploads['name'] as $index => $original_name) {
            if ($original_name === '') {
                continue;
            }

            $upload_error = isset($uploads['error'][$index]) ? (int) $uploads['error'][$index] : UPLOAD_ERR_OK;
            $tmp_name = isset($uploads['tmp_name'][$index]) ? $uploads['tmp_name'][$index] : '';
            if ($upload_error !== UPLOAD_ERR_OK || $tmp_name === '' || !is_file($tmp_name)) {
                $invalid_files[] = $original_name;
                continue;
            }

            $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            $mime = '';
            if (function_exists('finfo_open')) {
                $file_info = @finfo_open(FILEINFO_MIME_TYPE);
                if ($file_info) {
                    $mime = (string) @finfo_file($file_info, $tmp_name);
                    @finfo_close($file_info);
                }
            }
            if ($mime === '' && !empty($uploads['type'][$index])) {
                $mime = strtolower(trim((string) $uploads['type'][$index]));
            }
            if (!isset($native_formats[$mime]) && !isset($convertible_formats[$mime]) && isset($extension_mimes[$extension])) {
                $mime = $extension_mimes[$extension];
            }

            if (isset($native_formats[$mime])) {
                $image_info = @getimagesize($tmp_name);
                if (empty($image_info['mime']) || !isset($native_formats[$image_info['mime']])) {
                    $invalid_files[] = $original_name;
                    continue;
                }
                $mime = $image_info['mime'];
                $normalized_extension = $native_formats[$mime];
                $base_name = pathinfo($original_name, PATHINFO_FILENAME);
                $uploads['name'][$index] = ($base_name !== '' ? $base_name : 'product-image') . '.' . $normalized_extension;
                $uploads['type'][$index] = $mime;
                continue;
            }

            if (!isset($convertible_formats[$mime])) {
                $invalid_files[] = $original_name;
                continue;
            }

            $image = false;
            if ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
                $image = @imagecreatefromwebp($tmp_name);
            } elseif ($mime === 'image/avif' && function_exists('imagecreatefromavif')) {
                $image = @imagecreatefromavif($tmp_name);
            }

            $converted_path = @tempnam(sys_get_temp_dir(), 'wo_product_');
            $converted = false;
            if ($image && $converted_path) {
                $converted = @imagejpeg($image, $converted_path, 90);
                @imagedestroy($image);
            } elseif (class_exists('Imagick') && $converted_path) {
                try {
                    $imagick = new Imagick($tmp_name);
                    $imagick->setIteratorIndex(0);
                    $imagick->setImageFormat('jpeg');
                    $imagick->setImageCompressionQuality(90);
                    $imagick->stripImage();
                    $converted = $imagick->writeImage($converted_path);
                    $imagick->clear();
                    $imagick->destroy();
                } catch (Throwable $exception) {
                    $converted = false;
                }
            }

            if (!$converted || !$converted_path || !is_file($converted_path) || @filesize($converted_path) < 1) {
                if ($converted_path && is_file($converted_path)) {
                    @unlink($converted_path);
                }
                $invalid_files[] = $original_name;
                continue;
            }

            $converted_contents = @file_get_contents($converted_path);
            @unlink($converted_path);
            if ($converted_contents === false || @file_put_contents($tmp_name, $converted_contents) === false) {
                $invalid_files[] = $original_name;
                continue;
            }

            $base_name = pathinfo($original_name, PATHINFO_FILENAME);
            $uploads['name'][$index] = ($base_name !== '' ? $base_name : 'product-image') . '.jpg';
            $uploads['type'][$index] = 'image/jpeg';
            $uploads['size'][$index] = @filesize($tmp_name);
        }

        return $invalid_files;
    }
}

if ($f == 'products') {
    $data['status'] = 400;
    if ($s == 'create' && Wo_CheckSession($hash_id) === true && $wo['config']['can_use_market']) {
        $currency = 0;
        if (isset($_POST['currency'])) {
            if (in_array($_POST['currency'], array_keys($wo['currencies']))) {
                $currency = Wo_Secure($_POST['currency']);
            }
        }
        $parsed_price = false;
        if (isset($_POST['price'])) {
            $parsed_price = Wo_ParsePriceByCurrency($_POST['price'], $currency);
        }
        $parsed_point = 0;
        if (isset($_POST['point']) && trim((string) $_POST['point']) !== '') {
            $parsed_point = preg_replace('/[^\d]/', '', (string) $_POST['point']);
            $parsed_point = ($parsed_point === '' || !is_numeric($parsed_point)) ? false : (float) $parsed_point;
        }
        if (!empty($_FILES['postPhotos'])) {
            $invalid_product_photos = Wo_NormalizeProductPhotoUploads($_FILES['postPhotos']);
            foreach ($invalid_product_photos as $invalid_product_photo) {
                $errors[] = $error_icon . $wo['lang']['please_upload_image'] . ' (JPG, PNG, GIF, WEBP, HEIC, HEIF, AVIF): ' . Wo_Secure($invalid_product_photo);
            }
        }
        $has_product_photo = false;
        if (!empty($_FILES['postPhotos']['name']) && is_array($_FILES['postPhotos']['name'])) {
            foreach ($_FILES['postPhotos']['name'] as $photo_name) {
                if (!empty($photo_name)) {
                    $has_product_photo = true;
                    break;
                }
            }
        }
        if ($wo['config']['who_upload'] == 'pro' && $wo['user']['is_pro'] == 0 && !Wo_IsAdmin() && !empty($_FILES['postPhotos'])) {
            $errors[] = $error_icon . $wo['lang']['free_plan_upload_pro'];
        }
        if (trim((string)($_POST['name'] ?? '')) === '') {
            $errors[] = $error_icon . 'Thiếu tên sản phẩm.';
        } else if (trim((string)($_POST['category'] ?? '')) === '') {
            $errors[] = $error_icon . 'Thiếu danh mục sản phẩm.';
        } else if (trim((string)($_POST['description'] ?? '')) === '') {
            $errors[] = $error_icon . 'Thiếu mô tả sản phẩm.';
        } else if (empty($_POST['price'])) {
            $errors[] = $error_icon . $wo['lang']['please_choose_price'];
        } else if ($parsed_price === false) {
            $errors[] = $error_icon . $wo['lang']['please_choose_c_price'];
        } else if ((float) $parsed_price <= 0) {
            $errors[] = $error_icon . $wo['lang']['please_choose_price'];
        } else if ($parsed_point === false) {
            $errors[] = $error_icon . 'Điểm sản phẩm không hợp lệ.';
        } else if (!$has_product_photo) {
            $errors[] = $error_icon . $wo['lang']['please_upload_image'];
        } else if($wo['config']['store_system'] == 'on' && (empty($_POST['units']) || !is_numeric($_POST['units']) || $_POST['units'] < 1)){
            $errors[] = $error_icon . $wo['lang']['total_item_not_empty'];
        } else if (!Wo_ProductPriceColumnSupportsJson()) {
            $errors[] = $error_icon . 'Cần đổi cột Wo_Products.price sang TEXT để lưu JSON giá + điểm.';
        }
        if (isset($_FILES['postPhotos']['name'])) {
            $allowed = array(
                'gif',
                'png',
                'jpg',
                'jpeg'
            );
            for ($i = 0; $i < count($_FILES['postPhotos']['name']); $i++) {
                if (empty($_FILES['postPhotos']['name'][$i])) {
                    continue;
                }
                $new_string = pathinfo($_FILES['postPhotos']['name'][$i]);
                if (empty($new_string['extension']) || !in_array(strtolower($new_string['extension']), $allowed)) {
                    $errors[] = $error_icon . 'Ảnh sản phẩm không hợp lệ: ' . Wo_Secure($_FILES['postPhotos']['name'][$i]);
                }
            }
        }
        $type = 0;
        if (!empty($_POST['type'])) {
            $type = 1;
        }
        $units = 0;
        if (!empty($_POST['units']) && is_numeric($_POST['units']) && $_POST['units'] > 0) {
            $units = Wo_Secure($_POST['units']);
        }
        if (empty($errors)) {
            $sub_category = '';
            if (!empty($_POST['product_sub_category']) && !empty($wo['products_sub_categories'][$_POST['category']])) {
                foreach ($wo['products_sub_categories'][$_POST['category']] as $key => $value) {
                    if ($value['id'] == $_POST['product_sub_category']) {
                        $sub_category = $value['id'];
                    }
                }
            }
            $lat = '';
            $lng = '';
            if (!empty($_POST['lat-product'])) {
                if (is_numeric($_POST['lat-product'])) {
                    $lat = $_POST['lat-product'];
                }
            }
            if (!empty($_POST['lng-product'])) {
                if (is_numeric($_POST['lng-product'])) {
                    $lng = $_POST['lng-product'];
                }
            }
            $place_id = '';
            if (!empty($_POST['place-id-product'])) {
                $place_id = Wo_Secure($_POST['place-id-product']);
            }
            $page_id = 0;
            $page_data = array();
            if (!empty($_POST['page_id']) && is_numeric($_POST['page_id']) && $_POST['page_id'] > 0 && Wo_IsPageOnwer(Wo_Secure($_POST['page_id']))) {
                $page_id = Wo_Secure($_POST['page_id']);
                $page_data = Wo_PageData($page_id);
            }
            $location = Wo_Secure($_POST['location']);
            if (!empty($page_data['address'])) {
                $location = Wo_Secure($page_data['address'], 1);
            }
            $has_manual_product_location = (
                (isset($_POST['lat-product']) && $_POST['lat-product'] !== '') ||
                (isset($_POST['lng-product']) && $_POST['lng-product'] !== '') ||
                (isset($_POST['place-id-product']) && $_POST['place-id-product'] !== '')
            );
            if ($page_id > 0 && !empty($page_data) && !$has_manual_product_location) {
                $lat = (!empty($page_data['lat']) && is_numeric($page_data['lat'])) ? Wo_Secure($page_data['lat']) : '';
                $lng = (!empty($page_data['lng']) && is_numeric($page_data['lng'])) ? Wo_Secure($page_data['lng']) : '';
                $place_id = !empty($page_data['place_id']) ? Wo_Secure($page_data['place_id']) : '';
            }
            $price              = mysqli_real_escape_string($sqlConnect, Wo_BuildPricePointJson($parsed_price, $parsed_point));
            $product_data_array = array(
                'user_id' => $wo['user']['user_id'],
                'name' => Wo_Secure($_POST['name'],1),
                'category' => Wo_Secure($_POST['category']),
                'sub_category' => $sub_category,
                'description' => Wo_Secure($_POST['description'],1,true,1),
                'time' => Wo_Secure(time()),
                'price' => $price,
                'type' => $type,
                'location' => $location,
                'currency' => $currency,
                'active' => ($wo['config']['store_review_system'] == 'off' ? 1 : 0),
                'lat' => Wo_Secure($lat),
                'lng' => Wo_Secure($lng),
                'units' => $units,
                'page_id' => $page_id
            );
            if (Wo_ProductColumnExists('place_id')) {
                $product_data_array['place_id'] = $place_id;
            }
            $fields = Wo_GetCustomFields('product'); 
            if (!empty($fields)) {
                foreach ($fields as $key => $field) {
                    $field_value = isset($_POST['fid_'.$field['id']]) ? trim((string) $_POST['fid_'.$field['id']]) : '';
                    if ($field['required'] == 'on' && $field_value === '') {
                        $errors[] = $error_icon . 'Thiếu trường tuỳ chỉnh: ' . $field['name'];
                        header("Content-type: application/json");
                        echo json_encode(array(
                            'errors' => $errors
                        ));
                        exit();
                    }
                    elseif ($field_value !== '') {
                        $product_data_array['fid_'.$field['id']] = Wo_Secure($_POST['fid_'.$field['id']]);
                    }
                }
            }
            $product_data       = Wo_RegisterProduct($product_data_array);
            $product_id         = 0;
            if (!$product_data) {
                $db_error = mysqli_error($sqlConnect);
                $errors[] = $error_icon . 'Không thể lưu sản phẩm.' . (!empty($db_error) ? ' Lỗi DB: ' . $db_error : ' Kiểm tra cột Wo_Products.price đã đổi sang TEXT để lưu JSON chưa.');
                header("Content-type: application/json");
                echo json_encode(array(
                    'errors' => $errors
                ));
                exit();
            }
            $product_id = $product_data;
            $post_data  = array(
                'user_id' => Wo_Secure($wo['user']['user_id']),
                'product_id' => Wo_Secure($product_id),
                'postPrivacy' => Wo_Secure(0),
                'time' => time(),
                'page_id' => $page_id,
                'active' => ($wo['config']['store_review_system'] == 'off' ? 1 : 0),
            );
            $id         = Wo_RegisterPost($post_data);
            if (empty($id) || !is_numeric($id)) {
                $db_error = mysqli_error($sqlConnect);
                $errors[] = $error_icon . 'Không thể tạo bài đăng cho sản phẩm.' . (!empty($db_error) ? ' Lỗi DB: ' . $db_error : '');
                header("Content-type: application/json");
                echo json_encode(array(
                    'errors' => $errors
                ));
                exit();
            }
            if (count($_FILES['postPhotos']['name']) > 0 && !empty($id) && $id > 0) {
                for ($i = 0; $i < count($_FILES['postPhotos']['name']); $i++) {
                    if (empty($_FILES['postPhotos']['name'][$i])) {
                        continue;
                    }
                    $fileInfo = array(
                        'file' => $_FILES["postPhotos"]["tmp_name"][$i],
                        'name' => $_FILES['postPhotos']['name'][$i],
                        'size' => $_FILES["postPhotos"]["size"][$i],
                        'type' => $_FILES["postPhotos"]["type"][$i],
                        'types' => 'jpg,png,jpeg,gif'
                    );
                    $file     = Wo_ShareFile($fileInfo, 1);
                    if (!empty($file)) {
                        $media_album = Wo_RegisterProductMedia($product_id, $file['filename']);
                    }
                }
            }
            $data = array(
                'status' => 200,
                'href' => Wo_SeoLink('index.php?link1=post&id=' . $id)
            );
            if ($wo['config']['store_review_system'] != 'off') {
                $data['message'] = $wo['lang']['your_product_is_under_review'];
            }
        }
        header("Content-type: application/json");
        if (isset($errors)) {
            echo json_encode(array(
                'errors' => $errors
            ));
        } else {
            echo json_encode($data);
        }
        exit();
    }
    if ($s == 'edit' && Wo_CheckSession($hash_id) === true) {
        $currency = 0;
        if (isset($_POST['currency'])) {
            if (in_array($_POST['currency'], array_keys($wo['currencies']))) {
                $currency = Wo_Secure($_POST['currency']);
            }
        }
        $parsed_price = false;
        if (isset($_POST['price'])) {
            $parsed_price = Wo_ParsePriceByCurrency($_POST['price'], $currency);
        }
        $parsed_point = 0;
        if (isset($_POST['point']) && trim((string) $_POST['point']) !== '') {
            $parsed_point = preg_replace('/[^\d]/', '', (string) $_POST['point']);
            $parsed_point = ($parsed_point === '' || !is_numeric($parsed_point)) ? false : (float) $parsed_point;
        }
        if (!empty($_FILES['postPhotos'])) {
            $invalid_product_photos = Wo_NormalizeProductPhotoUploads($_FILES['postPhotos']);
            foreach ($invalid_product_photos as $invalid_product_photo) {
                $errors[] = $error_icon . $wo['lang']['please_upload_image'] . ' (JPG, PNG, GIF, WEBP, HEIC, HEIF, AVIF): ' . Wo_Secure($invalid_product_photo);
            }
        }
        if (trim((string)($_POST['name'] ?? '')) === '') {
            $errors[] = $error_icon . 'Thiếu tên sản phẩm.';
        } else if (trim((string)($_POST['category'] ?? '')) === '') {
            $errors[] = $error_icon . 'Thiếu danh mục sản phẩm.';
        } else if (trim((string)($_POST['description'] ?? '')) === '') {
            $errors[] = $error_icon . 'Thiếu mô tả sản phẩm.';
        } else if (empty($_POST['price'])) {
            $errors[] = $error_icon . $wo['lang']['please_choose_price'];
        } else if ($parsed_price === false) {
            $errors[] = $error_icon . $wo['lang']['please_choose_c_price'];
        } else if ((float) $parsed_price <= 0) {
            $errors[] = $error_icon . $wo['lang']['please_choose_price'];
        } else if ($parsed_point === false) {
            $errors[] = $error_icon . 'Điểm sản phẩm không hợp lệ.';
        } else if($wo['config']['store_system'] == 'on' && (empty($_POST['units']) || !is_numeric($_POST['units']) || $_POST['units'] < 1)){
            $errors[] = $error_icon . $wo['lang']['total_item_not_empty'];
        } else if (!Wo_ProductPriceColumnSupportsJson()) {
            $errors[] = $error_icon . 'Cần đổi cột Wo_Products.price sang TEXT để lưu JSON giá + điểm.';
        }
        if (isset($_FILES['postPhotos']['name'])) {
            $allowed = array(
                'gif',
                'png',
                'jpg',
                'jpeg'
            );
            for ($i = 0; $i < count($_FILES['postPhotos']['name']); $i++) {
                if (empty($_FILES['postPhotos']['name'][$i])) {
                    continue;
                }
                $new_string = pathinfo($_FILES['postPhotos']['name'][$i]);
                if (empty($new_string['extension']) || !in_array(strtolower($new_string['extension']), $allowed)) {
                    $errors[] = $error_icon . 'Ảnh sản phẩm không hợp lệ: ' . Wo_Secure($_FILES['postPhotos']['name'][$i]);
                }
            }
        }
        $type = 0;
        if (!empty($_POST['type'])) {
            $type = 1;
        }
        $units = 0;
        if (!empty($_POST['units']) && is_numeric($_POST['units']) && $_POST['units'] > 0) {
            $units = Wo_Secure($_POST['units']);
        }
        if (empty($errors)) {
            $sub_category = '';
            if (!empty($_POST['product_sub_category']) && !empty($wo['products_sub_categories'][$_POST['category']])) {
                foreach ($wo['products_sub_categories'][$_POST['category']] as $key => $value) {
                    if ($value['id'] == $_POST['product_sub_category']) {
                        $sub_category = $value['id'];
                    }
                }
            }
            $lat = '';
            $lng = '';
            if (isset($_POST['lat-product']) && $_POST['lat-product'] !== '' && is_numeric($_POST['lat-product'])) {
                $lat = Wo_Secure($_POST['lat-product']);
            }
            if (isset($_POST['lng-product']) && $_POST['lng-product'] !== '' && is_numeric($_POST['lng-product'])) {
                $lng = Wo_Secure($_POST['lng-product']);
            }
            $place_id = '';
            if (!empty($_POST['place-id-product'])) {
                $place_id = Wo_Secure($_POST['place-id-product']);
            }
            $page_data = array();
            $page_id = 0;
            $post_id = Wo_GetPostIDFromProdcutID($_POST['product_id']);
            if (!empty($post_id)) {
                $post_data = Wo_PostData($post_id);
                if (!empty($post_data['page_id']) && Wo_IsPageOnwer($post_data['page_id'])) {
                    $page_id = (int) $post_data['page_id'];
                    $page_data = Wo_PageData($page_id);
                }
            }
            $location = $_POST['location'];
            if (!empty($page_data['address'])) {
                $location = Wo_Secure($page_data['address'], 1);
            }
            $has_manual_product_location = (
                (isset($_POST['lat-product']) && $_POST['lat-product'] !== '') ||
                (isset($_POST['lng-product']) && $_POST['lng-product'] !== '') ||
                (isset($_POST['place-id-product']) && $_POST['place-id-product'] !== '')
            );
            if ($page_id > 0 && !empty($page_data) && !$has_manual_product_location) {
                $lat = (!empty($page_data['lat']) && is_numeric($page_data['lat'])) ? Wo_Secure($page_data['lat']) : '';
                $lng = (!empty($page_data['lng']) && is_numeric($page_data['lng'])) ? Wo_Secure($page_data['lng']) : '';
                $place_id = !empty($page_data['place_id']) ? Wo_Secure($page_data['place_id']) : '';
            }
            $price              = mysqli_real_escape_string($sqlConnect, Wo_BuildPricePointJson($parsed_price, $parsed_point));
            $product_data_array = array(
                'name' => $_POST['name'],
                'category' => $_POST['category'],
                'sub_category' => $sub_category,
                'description' => $_POST['description'],
                'price' => $price,
                'location' => $location,
                'type' => $type,
                'currency' => $currency,
                'units' => $units,
                'lat' => $lat,
                'lng' => $lng,
            );
            if (Wo_ProductColumnExists('place_id')) {
                $product_data_array['place_id'] = $place_id;
            }

            $fields = Wo_GetCustomFields('product'); 
            if (!empty($fields)) {
                foreach ($fields as $key => $field) {
                    $field_value = isset($_POST['fid_'.$field['id']]) ? trim((string) $_POST['fid_'.$field['id']]) : '';
                    if ($field['required'] == 'on' && $field_value === '') {
                        $errors[] = $error_icon . 'Thiếu trường tuỳ chỉnh: ' . $field['name'];
                        header("Content-type: application/json");
                        echo json_encode(array(
                            'errors' => $errors
                        ));
                        exit();
                    }
                    elseif ($field_value !== '') {
                        $product_data_array['fid_'.$field['id']] = Wo_Secure($_POST['fid_'.$field['id']]);
                    }
                }
            }

            $product_data       = Wo_UpdateProductData($_POST['product_id'], $product_data_array);
            $product_id         = $_POST['product_id'];
            if (!$product_data) {
                $db_error = mysqli_error($sqlConnect);
                $errors[] = $error_icon . 'Không thể cập nhật sản phẩm.' . (!empty($db_error) ? ' Lỗi DB: ' . $db_error : ' Kiểm tra cột Wo_Products.price đã đổi sang TEXT để lưu JSON chưa.');
                header("Content-type: application/json");
                echo json_encode(array(
                    'errors' => $errors
                ));
                exit();
            }
            $id = Wo_GetPostIDFromProdcutID($product_id);
            if (isset($_FILES['postPhotos']['name'])) {
                if (count($_FILES['postPhotos']['name']) > 0 && !empty($id) && $id > 0) {
                    for ($i = 0; $i < count($_FILES['postPhotos']['name']); $i++) {
                        if (empty($_FILES['postPhotos']['name'][$i])) {
                            continue;
                        }
                        $fileInfo = array(
                            'file' => $_FILES["postPhotos"]["tmp_name"][$i],
                            'name' => $_FILES['postPhotos']['name'][$i],
                            'size' => $_FILES["postPhotos"]["size"][$i],
                            'type' => $_FILES["postPhotos"]["type"][$i],
                            'types' => 'jpg,png,jpeg,gif'
                        );
                        $file     = Wo_ShareFile($fileInfo, 1);
                        if (!empty($file)) {
                            $media_album = Wo_RegisterProductMedia($product_id, $file['filename']);
                        }
                    }
                }
            }
            if (!empty($_POST['deleted_images_ids'])) {
                $images_array = explode(',', $_POST['deleted_images_ids']);
                if (!empty($images_array)) {
                    $product_data = Wo_GetProduct($product_id);
                    foreach ($images_array as $key => $value) {
                        $image = Wo_ProductImageData(array('id' => $value));
                        if (!empty($image)) {
                            $explode2    = @end(explode('.', $image['image']));
                            $explode3    = @explode('.', $image['image']);
                            $small_image = $explode3[0] . '_small.' . $explode2;

                            $product = Wo_GetProduct($image['product_id']);
                            if (!empty($product) && $wo['user']['id'] == $product['user_id']) {
                                $deleted = Wo_DeleteProductImage($value);
                                if ($deleted) {
                                    if (($wo['config']['amazone_s3'] == 1 || $wo['config']['wasabi_storage'] == 1 || $wo['config']['ftp_upload'] == 1 || $wo['config']['spaces'] == 1 || $wo['config']['cloud_upload'] == 1 || $wo['config']['backblaze_storage'] == 1)) {
                                        Wo_DeleteFromToS3($image['image']);
                                        Wo_DeleteFromToS3($small_image);
                                    }
                                    @unlink($image['image']);
                                    @unlink($small_image);
                                }
                            }
                        }
                    }
                }
            }
            $data = array(
                'status' => 200,
                'href' => Wo_SeoLink('index.php?link1=post&id=' . $id)
            );
        }
        header("Content-type: application/json");
        if (isset($errors)) {
            echo json_encode(array(
                'errors' => $errors
            ));
        } else {
            echo json_encode($data);
        }
        exit();
    }
    if ($s == 'add_cart') {
        if (!empty($_POST['product_id']) && is_numeric($_POST['product_id']) && $_POST['product_id'] > 0) {
            $is_added = $db->where('product_id', Wo_Secure($_POST['product_id']))->where('user_id',$wo['user']['user_id'])->getOne(T_USERCARD);
            if (!empty($is_added)) {
                $product_data = Wo_GetProduct(Wo_Secure($_POST['product_id']));
                if (!empty($product_data) && !empty($product_data['units']) && $product_data['units'] > $is_added->units) {
                    $db->where('id',$is_added->id)->update(T_USERCARD,array('units' => $db->inc(1)));
                }
                // $db->where('product_id',Wo_Secure($_POST['product_id']))->where('user_id',$wo['user']['user_id'])->delete(T_USERCARD);
                $data['status'] = 200;
                $data['type'] = 'removed';
            }
            else{
                $qty = 1;
                if (!empty($_POST['qty']) && is_numeric($_POST['qty']) && $_POST['qty'] > 0) {
                    $qty = Wo_Secure($_POST['qty']);
                }
                $db->insert(T_USERCARD,array('user_id' => $wo['user']['user_id'],
                                         'units' => $qty,
                                         'product_id' => Wo_Secure($_POST['product_id'])));
                $data['status'] = 200;
                $data['type'] = 'added';
            }
            $data['count'] = $db->where('user_id',$wo['user']['user_id'])->getValue(T_USERCARD,'COUNT(*)');
            if ($data['count'] < 1) {
                $data['count'] = '';
            }
        }
        else{
            $data['message'] = $error_icon . $wo['lang']['please_check_details'];
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'change_qty') {
        if (!empty($_POST['product_id']) && is_numeric($_POST['product_id']) && $_POST['product_id'] > 0 && !empty($_POST['qty']) && is_numeric($_POST['qty']) && $_POST['qty'] > 0) {
            $product = Wo_GetProduct(Wo_Secure($_POST['product_id']));
            $qty = Wo_Secure($_POST['qty']);
            if ($product['units'] >= $qty) {
                $db->where('product_id',$product['id'])->where('user_id',$wo['user']['user_id'])->update(T_USERCARD,array('units' => $qty));
                $data['status'] = 200;
            }
        }
        else{
            $data['message'] = $error_icon . $wo['lang']['please_check_details'];
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'remove_cart') {
        if (!empty($_POST['product_id']) && is_numeric($_POST['product_id']) && $_POST['product_id'] > 0) {
            $is_added = $db->where('product_id', Wo_Secure($_POST['product_id']))->where('user_id',$wo['user']['user_id'])->getOne(T_USERCARD);
            if (!empty($is_added)) {
                $db->where('product_id',Wo_Secure($_POST['product_id']))->where('user_id',$wo['user']['user_id'])->delete(T_USERCARD);
                $data['status'] = 200;
                $data['count'] = $db->where('user_id',$wo['user']['user_id'])->getValue(T_USERCARD,'COUNT(*)');
                if ($data['count'] < 1) {
                    $data['count'] = '';
                }
            }
        }
        else{
            $data['message'] = $error_icon . $wo['lang']['please_check_details'];
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'check_wallet') {
        $items = $db->where('user_id',$wo['user']['user_id'])->get(T_USERCARD);
        $data['topup'] = 'show';
        $total = 0;
        $total_points = 0;
        if (!empty($items)) {
            foreach ($items as $key => $item) {
                $product = Wo_GetProduct($item->product_id);
                $total += ($product['price'] * $item->units);
                $total_points += (!empty($product['point']) ? ((float) $product['point'] * $item->units) : 0);
            }
            $wallet_data = Wo_NormalizeWalletValue(!empty($wo['user']['wallet']) ? $wo['user']['wallet'] : 0);
            $data['topup'] = ($wallet_data['price'] < $total || $wallet_data['point'] < $total_points ? 'show' : 'hide');
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'buy') {
        $data['status'] = 400;
        if (!empty($_POST['address_id']) && is_numeric($_POST['address_id']) && $_POST['address_id'] > 0) {
            $address = $db->where('id',Wo_Secure($_POST['address_id']))->where('user_id',$wo['user']['user_id'])->getOne(T_USER_ADDRESS);
            if (!empty($address)) {
                $items = $db->where('user_id',$wo['user']['user_id'])->get(T_USERCARD);
                $html = '';
                $total = 0;
                $insert = array();
                $wo['main_product'] = '';

                if (!empty($items)) {
                    foreach ($items as $key => $item) {
                        $product = $wo['main_product'] = Wo_GetProduct($item->product_id);
                        if ($item->units <= $product['units']) {
                            if (!empty($wo['currencies']) && !empty($wo['currencies'][$product['currency']]) && $wo['currencies'][$product['currency']]['text'] != $wo['config']['currency'] && !empty($wo['config']['exchange']) && !empty($wo['config']['exchange'][$wo['currencies'][$product['currency']]['text']])) {
                                $total += (($product['price'] / $wo['config']['exchange'][$wo['currencies'][$product['currency']]['text']]) * $item->units);
                            }
                            else{
                                $total += ($product['price'] * $item->units);
                            }
                            if (!in_array($product['user_id'], array_keys($insert))) {
                                $f_price = $product['price'];
                                if (!empty($wo['config']['exchange']) && !empty($wo['config']['exchange'][$wo['currencies'][$product['currency']]['text']])) {
                                    $f_price = ($product['price'] / $wo['config']['exchange'][$wo['currencies'][$product['currency']]['text']]);
                                }
                                $insert[$product['user_id']] = array();
                                $insert[$product['user_id']][] = array('product_id' => $product['id'],
                                                                       'product_name' => $product['name'],
                                                                       'price' => $f_price,
                                                                       'point' => !empty($product['point']) ? (float) $product['point'] : 0,
                                                                       'units' => $item->units);
                            }
                            else{
                                $f_price = $product['price'];
                                if (!empty($wo['config']['exchange']) && !empty($wo['config']['exchange'][$wo['currencies'][$product['currency']]['text']])) {
                                    $f_price = ($product['price'] / $wo['config']['exchange'][$wo['currencies'][$product['currency']]['text']]);
                                }
                                $insert[$product['user_id']][] = array('product_id' => $product['id'],
                                                                       'product_name' => $product['name'],
                                                                       'price' => $f_price,
                                                                       'point' => !empty($product['point']) ? (float) $product['point'] : 0,
                                                                       'units' => $item->units);
                            }
                        }
                        else{
                            $data['message'] = $error_icon . $wo['lang']['some_products_units'];
                            header('Content-Type: application/json');
                            echo json_encode($data);
                            exit();
                        }
                    }

                    if (!empty($insert)) {
                        foreach ($insert as $key => $value) {
                            $hash_id = uniqid(rand(11111,999999));
                            $total = 0;
                            $total_points = 0;
                            $total_commission = 0;
                            $total_final_price = 0;
                            $order_items_text = '';
                            foreach ($value as $key2 => $value2) {
                                $db->where('id',$value2['product_id'])->update(T_PRODUCTS,array('units' => $db->dec($value2['units'])));
                                $store_commission = 0;
                                if (!empty($wo['config']['store_commission'])) {
                                    $store_commission = round((($wo['config']['store_commission'] * ($value2['price'] * $value2['units'])) / 100), 2);
                                }
                                $total += ($value2['price'] * $value2['units']);
                                $total_points += (!empty($value2['point']) ? ((float) $value2['point'] * $value2['units']) : 0);
                                $total_commission += $store_commission;
                                $total_final_price += ($value2['price'] * $value2['units']) - $store_commission;
                                    
                                $db->insert(T_USER_ORDERS,array('user_id' => $wo['user']['user_id'],
                                                           'product_owner_id' => $key,
                                                           'product_id' => $value2['product_id'],
                                                           'price' => ($value2['price'] * $value2['units']),
                                                           'commission' => $store_commission,
                                                           'final_price' => ($value2['price'] * $value2['units']) - $store_commission,
                                                           'hash_id' => $hash_id,
                                                           'units' => $value2['units'],
                                                           'status' => 'placed',
                                                           'address_id' => $address->id,
                                                           'time' => time()));

                                // Build order items text for message
                                $item_total = $wo['config']['currency'] . number_format(($value2['price'] * $value2['units']), 2);
                                if (!empty($value2['point'])) {
                                    $item_total .= '+' . Wo_FormatProductPoint((float) $value2['point'] * $value2['units']) . ' VNSEEA';
                                }
                                $order_items_text .= "- " . $value2['product_name'] . " x" . $value2['units'] . " = " . $item_total . "\n";
                            }

                            cache($wo['user']['user_id'], 'users', 'delete');
                            $db->insert(T_PURCHAES,array('user_id' => $wo['user']['user_id'],
                                                             'order_hash_id' => $hash_id,
                                                             'price' => $total,
                                                             'data' => json_encode(array('name' => !empty($wo['main_product']) && !empty($wo['main_product']['name']) ? $wo['main_product']['name'] : '')),
                                                             'commission' => $total_commission,
                                                             'final_price' => $total_final_price,
                                                             'time' => time()));
                            $notification_data_array = array(
                                'notifier_id' => $wo['user']['user_id'],
                                'recipient_id' => $key,
                                'type' => 'new_orders',
                                'url' => 'index.php?link1=orders',
                                'time' => time()
                            );
                            $db->insert(T_NOTIFICATION,$notification_data_array);

                            // Send order info to seller via chat message
                            $order_total_text = $wo['config']['currency'] . number_format($total, 2);
                            if (!empty($total_points)) {
                                $order_total_text .= '+' . Wo_FormatProductPoint($total_points) . ' VNSEEA';
                            }
                            $buyer_name = $wo['user']['name'];
                            $buyer_phone = !empty($address->phone) ? $address->phone : $wo['user']['phone_number'];
                            $address_text = $address->address . ', ' . $address->city . ', ' . $address->country;

                            $message_text = "📦 ĐƠN HÀNG MỚI #" . $hash_id . "\n\n";
                            $message_text .= "👤 Người đặt: " . $buyer_name . "\n";
                            $message_text .= "📞 SĐT: " . $buyer_phone . "\n";
                            $message_text .= "📍 Địa chỉ: " . $address_text . "\n\n";
                            $message_text .= "🛒 Sản phẩm:\n" . $order_items_text . "\n";
                            $message_text .= "💰 Tổng: " . $order_total_text;

                            Wo_RegisterMessage(array(
                                'from_id' => Wo_Secure($wo['user']['user_id']),
                                'to_id' => Wo_Secure($key),
                                'text' => Wo_Secure($message_text, 1),
                                'media' => '',
                                'mediaFileName' => '',
                                'time' => time(),
                                'stickers' => ''
                            ));
                        }

                        $db->where('user_id',$wo['user']['user_id'])->delete(T_USERCARD);
                        $data['status'] = 200;
                        $data['message'] = $wo["lang"]["your_order_has_been_placed_successfully"];
                        $data['users'] = array_keys($insert);
                    }
                    else{
                        $data['message'] = $error_icon . $wo["lang"]["something_wrong"];
                    }
                }
                else{
                    $data['message'] = $error_icon . $wo["lang"]["card_is_empty"];
                }
            }
            else{
                $data['message'] = $error_icon . $wo["lang"]["address_not_found"];
            }
        }
        else{
            $data['message'] = $error_icon . $wo["lang"]["address_can_not_be_empty"];
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'download') {
        if (!empty($_POST['id'])) {
            $id = Wo_Secure($_POST['id']);
            $wo['purchase'] = $db->where('order_hash_id',$id)->getOne(T_PURCHAES);
            if (!empty($wo['purchase'])) {
                $orders = $db->where('hash_id',$wo['purchase']->order_hash_id)->get(T_USER_ORDERS);
                if (!empty($orders)) {
                    $wo['total'] = 0;
                    $wo['total_commission'] = 0;
                    $wo['total_final_price'] = 0;
                    $wo['address_id'] = 0;
                    $user_id = 0;
                    $wo['html'] = '';
                    $wo['main_product'] = '';
                    foreach ($orders as $key => $order) {
                        $order->product = Wo_GetProduct($order->product_id);
                        if (empty($wo['main_product'])) {
                            $wo['main_product'] = $order->product;
                            $wo['main_product']['in_title'] = url_slug($wo['main_product']['name'], array(
                                'delimiter' => '-',
                                'limit' => 100,
                                'lowercase' => true,
                                'replacements' => array(
                                    '/\b(an)\b/i' => 'a',
                                    '/\b(example)\b/i' => 'Test'
                                )
                            ));
                        }
                        $wo['total'] += $order->price;
                        $wo['total_commission'] += $order->commission;
                        $wo['total_final_price'] += $order->final_price;
                        $wo['address_id'] = $order->address_id;
                        $user_id = $order->product['user_id'];
                        $wo['html'] .= '<tr><td><h6 class="mb-0">'.$wo['main_product']['name'].'</h6></td><td>'.number_format(($order->price/$order->units),2).'</td><td>'.$order->units.'</td><td><span class="font-weight-semibold">'.$wo['config']['currency'].number_format(($order->price),2).'</span></td></tr>';
                    }
                    $wo['product_owner'] = Wo_UserData($user_id);
                    $wo['address'] = $db->where('id',$wo['address_id'])->getOne(T_USER_ADDRESS);
                    $wo['total'] = number_format($wo['total'],2);
                    $wo['total_commission'] = number_format($wo['total_commission'],2);
                    $wo['total_final_price'] = number_format($wo['total_final_price'],2);
                    $wo['html'] = Wo_LoadPage('pdf/invoice');
                    $data['status'] = 200;
                    $data['html'] = $wo['html'];
                }
                else{
                    $data['message'] = $error_icon . $wo["lang"]["order_not_found"];
                }
            }
            else{
                $data['message'] = $error_icon . $wo["lang"]["you_are_not_purchased"];
            }
        }
        else{
            $data['message'] = $error_icon . $wo["lang"]["id_can_not_empty"];
        } 
        $data['status'] = 200;
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'tracking') {
        if (!empty($_POST['tracking_url']) && !empty($_POST['tracking_id']) && !empty($_POST['order_hash']) && filter_var($_POST['tracking_url'], FILTER_VALIDATE_URL)) {
            $hash_id = Wo_Secure($_POST['order_hash']);
            $tracking_url = Wo_Secure($_POST['tracking_url']);
            $tracking_id = Wo_Secure($_POST['tracking_id']);
            $order = $db->where('hash_id',$hash_id)->where('product_owner_id',$wo['user']['user_id'])->getOne(T_USER_ORDERS);
            if (!empty($order)) {
                $db->where('hash_id',$hash_id)->update(T_USER_ORDERS,array('tracking_url' => $tracking_url,
                                                                      'tracking_id' => $tracking_id));
                $notification_data_array = array(
                    'notifier_id' => $wo['user']['user_id'],
                    'recipient_id' => $order->user_id,
                    'type' => 'added_tracking',
                    'url' => 'index.php?link1=customer_order&id='.$hash_id,
                    'time' => time()
                );
                $db->insert(T_NOTIFICATION,$notification_data_array);
                $data['status'] = 200;
                $data['message'] = $wo['lang']['tracking_info_has_been_saved_successfully'];
            }
            else{
                $data['message'] = $wo['lang']['order_not_found'];
            }
        }
        else{
            if (empty($_POST['tracking_url'])) {
                $data['message'] = $wo['lang']['tracking_url_can_not_be_empty'];
            }
            elseif (empty($_POST['tracking_id'])) {
                $data['message'] = $wo['lang']['tracking_number_can_not_be_empty'];
            }
            elseif (!filter_var($_POST['tracking_url'], FILTER_VALIDATE_URL)) {
                $data['message'] = $wo['lang']['please_enter_valid_url'];
            }
            else{
                $data['message'] = $wo['lang']['please_check_details'];
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'change_status') {
        if (!empty($_POST['hash_order']) && !empty($_POST['status'])) {
            $hash_id = Wo_Secure($_POST['hash_order']);
            $status = Wo_Secure($_POST['status']);
            $order = $db->where('hash_id',$hash_id)->getOne(T_USER_ORDERS);
            if (!empty($order)) {
                $types = array();
                if ($order->product_owner_id == $wo['user']['user_id']) {
                    if ($order->status == 'placed') {
                        $types = array('canceled','accepted','packed','shipped');
                    }
                    if ($order->status == 'accepted') {
                        $types = array('packed','shipped');
                    }
                    if ($order->status == 'packed') {
                        $types = array('shipped');
                    }
                    if ($order->status == 'shipped') {
                        $types = array('delivered');
                    }
                }
                elseif ($order->user_id == $wo['user']['user_id']) {
                    if ($order->status == 'shipped') {
                        $types = array('delivered');
                    }
                }
                if (in_array($status, $types)) {
                    $db->where('hash_id',$hash_id)->update(T_USER_ORDERS,array('status' => $status));
                    if ($status == 'delivered') {
                        $total = $db->where('hash_id',$hash_id)->getValue(T_USER_ORDERS,'SUM(final_price)');
                        $db->where('user_id',$order->product_owner_id)->update(T_USERS,array('balance' => $db->inc($total)));

                        cache($order->product_owner_id, 'users', 'delete');

                        $notification_data_array = array(
                            'notifier_id' => $wo['user']['user_id'],
                            'recipient_id' => $order->product_owner_id,
                            'type' => 'status_changed',
                            'url' => 'index.php?link1=order&id='.$hash_id,
                            'time' => time()
                        );
                        $db->insert(T_NOTIFICATION,$notification_data_array);
                        $data['recipient_id'] = $order->product_owner_id;
                    }
                    else{
                        if ($status == 'canceled') {
                            // Wallet refund removed - no longer deducting from wallet on checkout
                        }
                        $notification_data_array = array(
                            'notifier_id' => $wo['user']['user_id'],
                            'recipient_id' => $order->user_id,
                            'type' => 'status_changed',
                            'url' => 'index.php?link1=customer_order&id='.$hash_id,
                            'time' => time()
                        );
                        $db->insert(T_NOTIFICATION,$notification_data_array);
                        $data['recipient_id'] = $order->user_id;
                    }
                        
                    $data['status'] = 200;
                }
            }
            else{
                $data['message'] = $wo['lang']['order_not_found'];
            }
        }
        else{
            $data['message'] = $wo['lang']['please_check_details'];
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'refund') {
        if (!empty($_POST['hash_order']) && !empty($_POST['message'])) {
            $hash = Wo_Secure($_POST['hash_order']);
            $message = Wo_Secure($_POST['message'],1);
            $order = $db->where('hash_id',$hash)->where('user_id',$wo['user']['user_id'])->getOne(T_USER_ORDERS);
            if (!empty($order)) {
                $db->insert(T_REFUND,array('order_hash_id' => $hash,
                                          'user_id' => $wo['user']['user_id'],
                                          'description' => $message,
                                          'time' => time()));
                $notif_data = array(
                    'recipient_id' => 0,
                    'type' => 'refund',
                    'admin' => 1,
                    'time' => time()
                );
                $db->insert(T_NOTIFICATION,$notif_data);
                $data['status'] = 200;
                $data['message'] = $wo['lang']['your_request_is_under_review'];

            }
            else{
                $data['message'] = $wo['lang']['order_not_found'];
            }
        }
        else{
            if (empty($_POST['message'])) {
                $data['message'] = $wo['lang']['please_explain_the_reason'];
            }
            else{
                $data['message'] = $wo['lang']['please_check_details'];
            } 
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'review') {
        if (!empty($_POST['rating']) && in_array($_POST['rating'], array(1,2,3,4,5)) && !empty($_POST['review']) && !empty($_POST['product_id']) && is_numeric($_POST['product_id']) && $_POST['product_id'] > 0) {
            $product_id = Wo_Secure($_POST['product_id']);
            $rating = Wo_Secure($_POST['rating']);
            $review = Wo_Secure($_POST['review'],1);
            $files = array();
            if (!empty($_FILES['images'])) {
                foreach ($_FILES['images']['name'] as $key => $value) {
                    $file_info = array(
                        'file' => $_FILES['images']['tmp_name'][$key],
                        'size' => $_FILES['images']['size'][$key],
                        'name' => $_FILES['images']['name'][$key],
                        'type' => $_FILES['images']['type'][$key]
                    );
                    $file_upload = Wo_ShareFile($file_info);
                    if (!empty($file_upload) && !empty($file_upload['filename'])) {
                        $files[] = $file_upload['filename'];
                    }
                }
            }
            $id = $db->insert(T_PRODUCT_REVIEW,array('user_id' => $wo['user']['user_id'],
                                           'product_id' => $product_id,
                                           'review' => $review,
                                           'time' => time(),
                                           'star' => $rating));
            if (!empty($id)) {
                if (!empty($files)) {
                    foreach ($files as $key => $value) {
                        $db->insert(T_ALBUMS_MEDIA,array('review_id' => $id,
                                                         'image' => $value));
                    }
                }
                $product = Wo_GetProduct($product_id);
                $notification_data_array = array(
                    'notifier_id' => $wo['user']['user_id'],
                    'recipient_id' => $product['user_id'],
                    'type' => 'new_review',
                    'url' => 'index.php?link1=post&id='.$product['seo_id'],
                    'time' => time()
                );
                $db->insert(T_NOTIFICATION,$notification_data_array);
                    
                $data['status'] = 200;
                $data['message'] = $wo["lang"]["review_has_been_sent_successfully"];
                $data['recipient_id'] = $product['user_id'];
            }
            else{
                $data['message'] = $wo["lang"]["something_wrong"];
            }
        }
        else{
            if (empty($_POST['rating'])) {
                $data['message'] = $wo['lang']['rating_can_not_be_empty'];
            }
            elseif (empty($_POST['review'])) {
                $data['message'] = $wo['lang']['review_can_not_be_empty'];
            }
            else{
                $data['message'] = $wo['lang']['please_check_details'];
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'load_purchase') {
        $wo['html'] = '';
        if (!empty($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] > 0) {
            $wo['purchased'] = $db->where('user_id', $wo['user']['user_id'])->where('id',Wo_Secure($_POST['id']),'<')->orderBy('id', 'DESC')->get(T_PURCHAES, 20);
            if (!empty($wo['purchased'])) {
                foreach ($wo['purchased'] as $key => $wo['purchase']) {
                    $wo['purchase']->data = json_decode($wo['purchase']->data,true);
                    $wo['purchase']->type = $wo['lang']['order'];
                    $wo['purchase']->date = Wo_Time_Elapsed_String($wo['purchase']->time);
                    $wo['purchase']->url = Wo_SeoLink('index.php?link1=customer_order&id=' . $wo['purchase']->order_hash_id);
                    $wo['html']     .= Wo_LoadPage('purchased/list');
                }
                $data['status'] = 200;
            }
        }
        $data['html'] = $wo['html'];
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'load_orders') {
        $wo['html'] = '';
        if (!empty($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] > 0) {
            $wo['orders'] = $db->where('product_owner_id',$wo['user']['user_id'])->where('id',Wo_Secure($_POST['id']),'<')->orderBy('id', 'DESC')->groupBy('hash_id')->get(T_USER_ORDERS,10);

            if (!empty($wo['orders'])) {
                foreach ($wo['orders'] as $key => $wo['order']) {
                    $wo['product'] = $db->where('id', $wo['order']->product_id)->getOne(T_PRODUCTS, array('name'));
                    $wo['count'] = $db->where('hash_id',$wo['order']->hash_id)->getValue(T_USER_ORDERS,'count(*)');
                    $wo['items_count'] = $db->where('hash_id',$wo['order']->hash_id)->getValue(T_USER_ORDERS,'sum(units)');
                    $wo['price'] = $db->where('hash_id',$wo['order']->hash_id)->getValue(T_USER_ORDERS,'sum(price)');
                    $wo['price'] = number_format($wo['price'],2);
                    $wo['html'] .= Wo_LoadPage('orders/list');
                }
                $data['status'] = 200;
            }
        }
        $data['html'] = $wo['html'];
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'load_reviews') {
        $wo['html'] = '';
        if (!empty($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] > 0 && !empty($_POST['product_id']) && is_numeric($_POST['product_id']) && $_POST['product_id'] > 0) {
            $wo['reviews'] = $db->where('product_id',Wo_Secure($_POST['product_id']))->where('id',Wo_Secure($_POST['id']),'<')->orderBy('id', 'DESC')->get(T_PRODUCT_REVIEW,20);

            if (!empty($wo['reviews'])) {
                foreach ($wo['reviews'] as $key => $value) {
                    $wo['review_class'] = 'five_star';
                    $wo['review_stars'] = '5 ★★★★★';
                    if ($value->star == 1) {
                        $wo['review_class'] = 'one_star';
                        $wo['review_stars'] = '1 ★';
                    } else if ($value->star == 2) {
                        $wo['review_stars'] = '2 ★★';
                        $wo['review_class'] = 'two_star';
                    } else if ($value->star == 3) {
                        $wo['review_stars'] = '3 ★★★';
                        $wo['review_class'] = 'three_star';
                    } else if ($value->star == 4) {
                        $wo['review_stars'] = '4 ★★★★';
                        $wo['review_class'] = 'four_star';
                    }
                    $wo['review'] = GetReview($value->id);
                    $wo['html'] .= Wo_LoadPage('products/review');
                }
                $data['status'] = 200;
            }
        }
        $data['html'] = $wo['html'];
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_reviews') {
        $data['status'] = 400;
        $wo['html'] = '';
        if (!empty($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] > 0) {
            $wo['reviews'] = $db->where('product_id',Wo_Secure($_POST['id']))->orderBy('id', 'DESC')->get(T_PRODUCT_REVIEW,20);
            if (!empty($wo['reviews'])) {
                foreach ($wo['reviews'] as $key => $value) {
                    $wo['review_class'] = 'five_star';
                    $wo['review_stars'] = '5 ★★★★★';
                    if ($value->star == 1) {
                        $wo['review_class'] = 'one_star';
                        $wo['review_stars'] = '1 ★';
                    } else if ($value->star == 2) {
                        $wo['review_stars'] = '2 ★★';
                        $wo['review_class'] = 'two_star';
                    } else if ($value->star == 3) {
                        $wo['review_stars'] = '3 ★★★';
                        $wo['review_class'] = 'three_star';
                    } else if ($value->star == 4) {
                        $wo['review_stars'] = '4 ★★★★';
                        $wo['review_class'] = 'four_star';
                    }
                    $wo['review'] = GetReview($value->id);
                    $wo['html'] .= Wo_LoadPage('products/review');
                }
                $data['html'] = $wo['html'];
                $data['status'] = 200;
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'load_users_products') {
        $data['status'] = 400;
        $wo['html'] = '';
        if (!empty($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] > 0 && !empty($_POST['user_id']) && is_numeric($_POST['user_id']) && $_POST['user_id'] > 0) {
            $products = Wo_GetProducts(array('user_id' => Wo_Secure($_POST['user_id']) , 'limit' => 20 , 'after_id' => Wo_Secure($_POST['id'])));
            if (!empty($products)) {
                foreach ($products as $key => $wo['product']) {
                    $wo['html'] .= Wo_LoadPage('timeline/product-list');
                }
                $data['html'] = $wo['html'];
                $data['status'] = 200;
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete') {
        $data['status'] = 400;
        if (!empty($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] > 0) {
            $wo['story'] = $db->where('product_id',Wo_Secure($_POST['id']))->where('user_id',$wo['user']['user_id'])->getOne(T_POSTS);
            if (!empty($wo['story'])) {
                if (Wo_DeletePost($wo['story']->id) === true) {
                    if (!empty($wo['story'])) {
                        $text          = $wo['story']->postText;
                        $hashtag_regex = '/(#\[([0-9]+)\])/i';
                        preg_match_all($hashtag_regex, $text, $matches);
                        $match_i = 0;
                        foreach ($matches[1] as $match) {
                            $hashkey = $matches[2][$match_i];
                            if (!empty($hashkey)) {
                                $db->where('id', $hashkey)->update(T_HASHTAGS, array(
                                    'trend_use_num' => $db->dec(1)
                                ));
                            }
                            $match_i++;
                        }
                    }
                    $wo['user_profile'] = Wo_UserData($wo['story']->user_id);
                    $user_data          = Wo_UpdateUserDetails($wo['story']->user_id, true, false, true, true);
                    Wo_CleanCache();
                    $data = array(
                        'status' => 200,
                        'post_count' => $user_data['details']['post_count']
                    );
                }
            }
        }
        else{
            $data['message'] = $wo['lang']['id_empty'];
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    // if ($s == 'delete_image_by_id') {
    //     $data['status'] = 400;
    //     if (!empty($_POST['image_id']) && is_numeric($_POST['image_id']) && $_POST['image_id'] > 0) {
    //         $image = Wo_ProductImageData(array('id' => $_POST['image_id']));
    //         if (!empty($image)) {
    //             $product = Wo_GetProduct($image['product_id']);
    //             if (!empty($product) && $wo['user']['id'] == $product['user_id']) {
    //                 $deleted = Wo_DeleteProductImage($_POST['image_id']);
    //                 if ($deleted) {
    //                     @unlink($image['image']);
    //                     $data['status'] = 200;
    //                 }
    //             }
    //         }
    //     }
    //     header("Content-type: application/json");
    //     echo json_encode($data);
    //     exit();
    // }
}
