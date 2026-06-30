<?php
session_start();
$mysqli = new mysqli('localhost', 'root', '', 'avtosalon');
if ($mysqli->connect_error) {
    die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

echo '<link rel="stylesheet" href="style.css">';

function safe($value) {
    global $mysqli;
    return $mysqli->real_escape_string(strip_tags(trim($value)));
}

$brands_menu_result = $mysqli->query("SELECT brand_id, brand_name FROM brands ORDER BY brand_name");
$brands_menu = [];
while ($row = $brands_menu_result->fetch_assoc()) {
    $brands_menu[] = $row;
}

$brand_icons = [
    'Toyota' => '🇯🇵',
    'Ford' => '🇺🇸',
    'BMW' => '🇩🇪',
    'Lamborghini' => '🇮🇹',
    'Porsche' => '🇩🇪',
    'Lada' => '🇷🇺',
    'Audi' => '🇩🇪',
    'Mitsubishi' => '🇯🇵',
    'Mercedes-Benz' => '🇩🇪'
];

$cars_per_page = 3; 
if (isset($_POST['page'])) {
    $current_page = intval($_POST['page']);
    if ($current_page < 1) {
        $current_page = 1;
    }
} else {
    $current_page = 1;
}
$offset = ($current_page - 1) * $cars_per_page;

if(isset($_SESSION['user_id'])){
    if(!isset($_COOKIE['recently_viewed'])){
        if(isset($_GET['car_id'])){
            setcookie('recently_viewed', $_GET['car_id']);
        }
    } else {
        if(isset($_GET['car_id'])) {
            $val = $_COOKIE['recently_viewed'];
            $arr = explode(';', $val);
            $arr = array_filter($arr, function($id) {
                return $id != $_GET['car_id'];
            });
            array_unshift($arr, $_GET['car_id']);
            $arr = array_slice($arr, 0, 3);
            $newval = implode(';', $arr); 
            setcookie('recently_viewed', $newval);
        }
    }
}

if (isset($_POST['like_car']) && isset($_SESSION['user_id'])) {
    $product_id = safe($_POST['like_car']);
    $user_id = $_SESSION['user_id'];
    
    $check_stmt = $mysqli->prepare("SELECT * FROM likes WHERE user_id = ? AND product_id = ?");
    if ($check_stmt) {
        $check_stmt->bind_param("ii", $user_id, $product_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows == 0) {
            $insert_stmt = $mysqli->prepare("INSERT INTO likes (user_id, product_id) VALUES (?, ?)");
            if ($insert_stmt) {
                $insert_stmt->bind_param("ii", $user_id, $product_id);
                if ($insert_stmt->execute()) {
                    echo "<script>alert('Вы оценили товар!'); location.href = location.pathname;</script>";
                    exit;
                }
            }
        } else {
            $delete_stmt = $mysqli->prepare("DELETE FROM likes WHERE user_id = ? AND product_id = ?");
            if ($delete_stmt) {
                $delete_stmt->bind_param("ii", $user_id, $product_id);
                if ($delete_stmt->execute()) {
                    echo "<script>alert('Вы убрали оценку!'); location.href = location.pathname;</script>";
                    exit;
                }
            }
        }
    }
}

if (isset($_POST['add_comment']) && isset($_SESSION['user_id'])) {
    $comment_text = safe($_POST['comment_text']);
    $product_id = safe($_POST['product_id']);
    $user_id = $_SESSION['user_id'];
    
    $insert_stmt = $mysqli->prepare("INSERT INTO comments (user_id, product_id, comment_text, comment_date) VALUES (?, ?, ?, NOW())");
    if ($insert_stmt) {
        $insert_stmt->bind_param("iis", $user_id, $product_id, $comment_text);
        if ($insert_stmt->execute()) {
            echo "<script>alert('Комментарий добавлен!'); location.href = '?car_id=$product_id';</script>";
            exit;
        }
    }
}

function authForm() {
    if (isset($_SESSION['user_id'])) {
        ?>
        <div style="text-align: center; padding: 10px; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); min-width: 180px; max-width: 200px;">
            <div style="margin-bottom: 10px;">
                <? if (!empty($_SESSION['photo'])): ?>
                    <img src="<? echo $_SESSION['photo']; ?>" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid #3498db;">
                <? else: ?>
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: #3498db; display: flex; align-items: center; justify-content: center; margin: 0 auto; color: white; font-size: 18px;">
                        <? echo substr($_SESSION['username'], 0, 1); ?>
                    </div>
                <? endif; ?>
            </div>
            
            <h4 style="margin: 8px 0 3px 0; color: #2c3e50; font-size: 14px;"><? echo $_SESSION['username']; ?></h4>
            
            <? if (!empty($_SESSION['phone'])): ?>
                <div style="color: #7f8c8d; font-size: 11px; margin-bottom: 8px;">
                    📞 <? echo $_SESSION['phone']; ?>
                </div>
            <? endif; ?>
            
            <div style="border-top: 1px solid #ecf0f1; padding-top: 10px; margin-top: 8px;">
                <form method="POST" enctype="multipart/form-data" style="margin-bottom: 8px;">
                    <label style="display: block; font-size: 10px; color: #7f8c8d; margin-bottom: 3px;">Сменить аватар:</label>
                    <input type="file" name="uploadfile" accept="image/*" style="font-size: 10px; margin-bottom: 5px; width: 100%;">
                    <input type="submit" name="upload_avatar" value="Обновить" style="background: #3498db; color: white; border: none; padding: 4px 8px; border-radius: 3px; cursor: pointer; font-size: 10px; width: 100%;">
                </form>
                
                <a href="?logout=1" style="color: #e74c3c; font-size: 10px; text-decoration: none; border: 1px solid #e74c3c; padding: 4px 8px; border-radius: 3px; display: inline-block; width: 100%; text-align: center; box-sizing: border-box;">
                    Выйти
                </a>
            </div>
        </div>
        <?
    } else {
        ?>
        <div style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); min-width: 180px; max-width: 200px;">
            <div style="margin-bottom: 15px;">
                <h4 style="margin: 0 0 10px 0; color: #2c3e50; text-align: center; font-size: 14px;">Вход в систему</h4>
                <form method="POST" action="">
                    <input type="text" name="login_username" placeholder="Логин" required 
                           style="width: 100%; padding: 6px; margin-bottom: 6px; border: 1px solid #bdc3c7; border-radius: 3px; box-sizing: border-box; font-size: 12px;">
                    <input type="password" name="login_pass" placeholder="Пароль" required 
                           style="width: 100%; padding: 6px; margin-bottom: 10px; border: 1px solid #bdc3c7; border-radius: 3px; box-sizing: border-box; font-size: 12px;">
                    <button type="submit" name="login_submit" 
                            style="width: 100%; background: #2ecc71; color: white; border: none; padding: 8px; border-radius: 3px; cursor: pointer; font-size: 12px;">
                        Войти
                    </button>
                </form>
            </div>
            
            <div style="border-top: 1px solid #ecf0f1; padding-top: 12px;">
                <h5 style="margin: 0 0 10px 0; color: #2c3e50; text-align: center; font-size: 12px;">Нет аккаунта?</h5>
                <form method="POST" action="">
                    <input type="text" name="register_username" placeholder="Логин" required 
                           style="width: 100%; padding: 6px; margin-bottom: 6px; border: 1px solid #bdc3c7; border-radius: 3px; box-sizing: border-box; font-size: 12px;">
                    <input type="password" name="register_pass" placeholder="Пароль" required 
                           style="width: 100%; padding: 6px; margin-bottom: 6px; border: 1px solid #bdc3c7; border-radius: 3px; box-sizing: border-box; font-size: 12px;">
                    <input type="text" name="register_phone" placeholder="Телефон" required 
                           style="width: 100%; padding: 6px; margin-bottom: 10px; border: 1px solid #bdc3c7; border-radius: 3px; box-sizing: border-box; font-size: 12px;">
                    <button type="submit" name="register_submit" 
                            style="width: 100%; background: #3498db; color: white; border: none; padding: 8px; border-radius: 3px; cursor: pointer; font-size: 12px;">
                        Регистрация
                    </button>
                </form>
            </div>
        </div>
        <?
    }
}

if (isset($_POST['upload_avatar']) && isset($_SESSION['user_id'])) {
    if (isset($_FILES['uploadfile']) && $_FILES['uploadfile']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/avatars/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['uploadfile']['name'], PATHINFO_EXTENSION);
        $new_filename = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_extension;
        $uploadfile = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['uploadfile']['tmp_name'], $uploadfile)) {
            $update_stmt = $mysqli->prepare("UPDATE users SET photo = ? WHERE user_id = ?");
            if ($update_stmt) {
                $update_stmt->bind_param("si", $uploadfile, $_SESSION['user_id']);
                if ($update_stmt->execute()) {
                    $_SESSION['photo'] = $uploadfile;
                    echo "<script>alert('Аватар успешно загружен!'); location.href = location.pathname;</script>";
                    exit;
                }
            }
        } else {
            echo "<script>alert('Ошибка загрузки файла!');</script>";
        }
    } else {
        echo "<script>alert('Выберите файл для загрузки!');</script>";
    }
}

if (isset($_POST['add_to_cart']) && isset($_SESSION['user_id'])) {
    $product_id = safe($_POST['add_to_cart']);
    $user_id = $_SESSION['user_id'];
    
    $check_stmt = $mysqli->prepare("SELECT * FROM cart_items WHERE user_id = ? AND product_id = ?");
    if ($check_stmt) {
        $check_stmt->bind_param("ii", $user_id, $product_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows == 0) {
            $insert_stmt = $mysqli->prepare("INSERT INTO cart_items (user_id, product_id) VALUES (?, ?)");
            if ($insert_stmt) {
                $insert_stmt->bind_param("ii", $user_id, $product_id);
                if ($insert_stmt->execute()) {
                    $return_url = isset($_POST['return_url']) ? $_POST['return_url'] : '';
                    echo "<script>alert('Товар добавлен в корзину!'); location.href = '$return_url';</script>";
                    exit;
                }
            }
        } else {
            echo "<script>alert('Этот товар уже в корзине!');</script>";
        }
    }
}

if (isset($_POST['remove_from_cart'])) {
    $cart_item_id = safe($_POST['remove_from_cart']);
    $delete_stmt = $mysqli->prepare("DELETE FROM cart_items WHERE cart_item_id = $cart_item_id");
    if ($delete_stmt) {
        if ($delete_stmt->execute()) {
            echo "<script>alert('Товар удален из корзины!'); location.href = '?cart=1';</script>";
            exit;
        }
    }
}

if (isset($_POST['order_cars'])) {
    $product_id = safe($_POST['order_cars']);
    $user_id = $_SESSION['user_id'];
    $order_date = date('Y-m-d H:i:s');
    
    $order_stmt = $mysqli->prepare("INSERT INTO orders (user_id, order_date, product_id) VALUES (?,?,?)");
    if ($order_stmt) {
        $order_stmt->bind_param("isi", $user_id, $order_date, $product_id);
        if ($order_stmt->execute()) {
            echo "<script>alert('Товар успешно заказан!'); location.href = '?cart=1';</script>";
            exit;
        }
    }
}

if (isset($_POST['login_submit'])) {
    $username = safe($_POST['login_username']);
    $pass = safe($_POST['login_pass']);
    
    $stmt = $mysqli->prepare("SELECT * FROM users WHERE username = ?");
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user) {
            if (password_verify($pass . $user['salt'], $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['phone'] = $user['phone'];
                $_SESSION['photo'] = $user['photo'];
                echo "<script>alert('Добро пожаловать!'); location.href = location.pathname;</script>";
                exit;
            } else {
                echo "<script>alert('Неверный пароль!');</script>";
            }
        } else {
            echo "<script>alert('Пользователь не найден!');</script>";
        }
    }
}

if (isset($_POST['register_submit'])) {
    $username = safe($_POST['register_username']);
    $pass = safe($_POST['register_pass']);
    $phone = safe($_POST['register_phone']);
    
    $stmt = $mysqli->prepare("SELECT * FROM users WHERE username = ?");
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user) {
            echo "<script>alert('Пользователь с таким логином уже существует!');</script>";
        } else {
            if (preg_match('/^[a-zA-Z0-9]{3,20}$/', $username)) {
                if (strlen($pass) >= 6) {
                    if (preg_match('/^\+7\d{3}\d{3}\d{2}\d{2}$/', $phone)) {
                        $salt = bin2hex(random_bytes(32)); 
                        $hashed_password = password_hash($pass . $salt, PASSWORD_DEFAULT); 
                        
                        $photo = '';
                        $stmt = $mysqli->prepare("INSERT INTO users (username, password, salt, phone, photo) VALUES (?, ?, ?, ?, ?)");
                        if ($stmt) {
                            $stmt->bind_param("sssss", $username, $hashed_password, $salt, $phone, $photo);
                            
                            if ($stmt->execute()) {
                                $_SESSION['user_id'] = $mysqli->insert_id;
                                $_SESSION['username'] = $username;
                                $_SESSION['phone'] = $phone;
                                $_SESSION['photo'] = $photo;
                                echo "<script>alert('Регистрация успешна!'); location.href = location.pathname;</script>";
                                exit;
                            } else {
                                echo "<script>alert('Ошибка регистрации!');</script>";
                            }
                        }
                    } else {
                        echo "<script>alert('Телефон должен быть в формате: +7XXXXXXXXXX');</script>";
                    }
                } else {
                    echo "<script>alert('Пароль должен быть не менее 6 символов!');</script>";
                }
            } else {
                echo "<script>alert('Логин: только латинские буквы и цифры (3-20 символов)!');</script>";
            }
        }
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    echo "<script>alert('Вы вышли!'); location.href = location.pathname;</script>";
    exit;
}

// ===== СТРАНИЦА БРЕНДА =====
if (isset($_GET['brand'])) {
    $brand_name = safe($_GET['brand']);
    $brand_info_result = $mysqli->query("SELECT * FROM brands WHERE brand_name = '$brand_name'");
    
    if ($brand_info_result && $brand_info_result->num_rows > 0) {
        $brand_info = $brand_info_result->fetch_assoc();
        
        $cars_count_result = $mysqli->query("SELECT COUNT(*) as count FROM products WHERE brand = '$brand_name'");
        $cars_count = $cars_count_result->fetch_assoc()['count'];
        
        $likes_result = $mysqli->query("
            SELECT COUNT(likes.like_id) as total_likes 
            FROM likes 
            JOIN products ON likes.product_id = products.product_id 
            WHERE products.brand = '$brand_name'
        ");
        $total_likes = $likes_result->fetch_assoc()['total_likes'] ?? 0;
        ?>
        
        <!DOCTYPE html>
        <html>
        <head>
            <link rel="stylesheet" href="style.css">
            <style>
                .brand-header {
                    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
                    color: white;
                    padding: 40px 20px;
                    border-radius: 10px;
                    margin-bottom: 30px;
                }
                .stat-card {
                    background: white;
                    padding: 20px;
                    border-radius: 8px;
                    text-align: center;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }
                .stat-number {
                    font-size: 32px;
                    font-weight: bold;
                    color: #e74c3c;
                }
                .stat-label {
                    color: #7f8c8d;
                    margin-top: 5px;
                }
                .brand-car-card {
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    overflow: hidden;
                    transition: transform 0.3s;
                    background: white;
                }
                .brand-car-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
                }
                .brand-car-card img {
                    width: 100%;
                    height: 200px;
                    object-fit: cover;
                }
                .brand-car-card-body {
                    padding: 15px;
                }
                .info-item {
                    background: #f8f9fa;
                    padding: 10px;
                    border-radius: 5px;
                    margin-bottom: 10px;
                }
                .info-item strong {
                    display: block;
                    color: #7f8c8d;
                    font-size: 12px;
                    margin-bottom: 3px;
                }
                .info-item span {
                    color: #2c3e50;
                    font-weight: 500;
                }
                .brand-menu {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    background: #1a1a2e;
                    padding: 12px 20px;
                    z-index: 1000;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    flex-wrap: wrap;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
                }
                .brand-menu a {
                    color: white;
                    text-decoration: none;
                    padding: 6px 14px;
                    border-radius: 20px;
                    font-size: 14px;
                    transition: all 0.3s;
                }
                .brand-menu a:hover {
                    background: #e74c3c;
                }
                .brand-menu .active {
                    background: #e74c3c;
                }
                .brand-page {
                    margin-top: 80px;
                    padding: 20px;
                    max-width: 1200px;
                    margin-left: auto;
                    margin-right: auto;
                }
                .car-card {
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    overflow: hidden;
                    transition: transform 0.3s;
                    background: white;
                    display: flex;
                    flex-direction: column;
                }
                .car-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
                }
                .car-card img {
                    width: 100%;
                    height: 200px;
                    object-fit: cover;
                }
                .car-card-body {
                    padding: 15px;
                    flex: 1;
                    display: flex;
                    flex-direction: column;
                }
                .car-card-body h4 {
                    margin: 0 0 5px 0;
                    font-size: 18px;
                }
                .car-card-body .price {
                    font-size: 22px;
                    color: #e74c3c;
                    font-weight: bold;
                    margin-top: 10px;
                }
                .car-card-body .likes {
                    color: #666;
                    font-size: 14px;
                    margin-top: 5px;
                }
                .car-card-body .details-link {
                    margin-top: 10px;
                    text-align: center;
                    color: #3498db;
                    font-size: 13px;
                }
                @media (max-width: 768px) {
                    .brand-menu a {
                        font-size: 12px;
                        padding: 4px 10px;
                    }
                    .brand-header h1 {
                        font-size: 32px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="brand-menu">
                <a href="?" style="font-weight:bold; font-size:18px;">🏠 Главная</a>
                <? if (isset($_SESSION['user_id'])): ?>
                    <a href="?cart=1" style="background:#e74c3c;">🛒 Корзина</a>
                    <a href="?orders=1" style="background:#3498db;">📦 Заказы</a>
                <? endif; ?>
                <span style="color:#666; margin:0 5px;">|</span>
                <? foreach ($brands_menu as $brand): 
                    $icon = isset($brand_icons[$brand['brand_name']]) ? $brand_icons[$brand['brand_name']] : '🚗';
                    $is_active = ($brand['brand_name'] == $brand_name) ? 'active' : '';
                ?>
                    <a href="?brand=<? echo urlencode($brand['brand_name']); ?>" class="<? echo $is_active; ?>">
                        <? echo $icon . ' ' . $brand['brand_name']; ?>
                    </a>
                <? endforeach; ?>
            </div>
            
            <div style="position:fixed; top:70px; right:20px; z-index:999;">
                <? authForm(); ?>
            </div>
            
            <div class="brand-page">
                <div class="brand-header">
                    <div style="display: flex; align-items: center; gap: 30px; flex-wrap: wrap;">
                        <div style="font-size: 80px;">🚗</div>
                        <div>
                            <h1 style="margin: 0; font-size: 48px;"><? echo $brand_info['brand_name']; ?></h1>
                            <p style="opacity: 0.8; margin: 5px 0 0 0;"><? echo $brand_info['headquarters'] ?? 'Штаб-квартира не указана'; ?></p>
                        </div>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <div class="stat-card">
                        <div class="stat-number"><? echo $cars_count; ?></div>
                        <div class="stat-label">Автомобилей в продаже</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><? echo $total_likes; ?></div>
                        <div class="stat-label">Всего лайков</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><? echo $brand_info['founded_year'] ?? '—'; ?></div>
                        <div class="stat-label">Год основания</div>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                    <div>
                        <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px;">
                            <h2 style="color: #2c3e50; margin-top: 0;">О бренде</h2>
                            <p style="line-height: 1.8; color: #34495e;"><? echo nl2br($brand_info['description']); ?></p>
                        </div>
                        
                        <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            <h2 style="color: #2c3e50; margin-top: 0;">Все автомобили <? echo $brand_info['brand_name']; ?></h2>
                            
                            <? 
                            $all_cars_result = $mysqli->query("
                                SELECT *, 
                                (SELECT image FROM images WHERE product_id = products.product_id AND main_image = 1 LIMIT 1) as image 
                                FROM products 
                                WHERE brand = '$brand_name' 
                                ORDER BY product_id DESC
                            ");
                            
                            if ($all_cars_result && $all_cars_result->num_rows > 0) {
                                echo '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">';
                                
                                while ($car = $all_cars_result->fetch_assoc()) {
                                    $is_liked = false;
                                    if (isset($_SESSION['user_id'])) {
                                        $check_like = $mysqli->query("SELECT * FROM likes WHERE user_id = " . $_SESSION['user_id'] . " AND product_id = " . $car['product_id']);
                                        $is_liked = $check_like->num_rows > 0;
                                    }
                                    $likes_count = $mysqli->query("SELECT COUNT(*) as count FROM likes WHERE product_id = " . $car['product_id'])->fetch_assoc()['count'];

                                    echo '
                                    <div class="car-card" onclick="location.href=\'?car_id=' . $car['product_id'] . '\'">
                                        <div style="display: flex; justify-content: space-between; align-items: start; padding: 10px 15px 0 15px;">
                                            <h3 style="margin: 0;">' . $car['brand'] . ' ' . $car['model'] . '</h3>
                                            ' . (isset($_SESSION['user_id']) ? '
                                            <div onclick="event.stopPropagation();">
                                                <form method="POST" style="margin: 0; display: inline-block;">
                                                    <input type="hidden" name="like_car" value="' . $car['product_id'] . '">
                                                    <button type="submit" style="background: none; border: none; cursor: pointer; font-size: 24px; color: ' . ($is_liked ? '#e74c3c' : '#ccc') . '; transition: transform 0.2s;" onmouseover="this.style.transform=\'scale(1.2)\'" onmouseout="this.style.transform=\'scale(1)\'">
                                                        ♥
                                                    </button>
                                                </form>
                                            </div>' : '<span style="font-size: 18px; color: #ccc;">♥</span>') . '
                                        </div>
                                        <div style="padding: 0 15px;">';
                                    
                                    if (!empty($car['image'])) {
                                        echo '<img src="' . $car['image'] . '" alt="' . $car['brand'] . ' ' . $car['model'] . '" style="width: 100%; height: 200px; object-fit: cover; border-radius: 4px;">';
                                    } else {
                                        echo '<div style="width: 100%; height: 200px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 4px;">Нет фото</div>';
                                    }
                                    
                                    echo '</div>
                                        <div class="car-card-body">
                                            <p style="margin: 0 0 5px 0; color: #666; font-size: 14px;">' . $car['year'] . ' год • ' . $car['engine'] . '</p>
                                            <div style="margin-top: auto;">
                                                <div class="price">$' . number_format($car['price']) . '</div>
                                                <div class="likes">♥ ' . $likes_count . '</div>
                                                <div class="details-link">Подробнее →</div>
                                            </div>
                                        </div>
                                    </div>';
                                }
                                
                                echo '</div>';
                            } else {
                                echo '<p style="text-align: center; color: #999; padding: 30px;">Нет автомобилей этого бренда в продаже</p>';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div>
                        <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); position: sticky; top: 90px;">
                            <h3 style="color: #2c3e50; margin-top: 0;">Информация</h3>
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <? if (!empty($brand_info['founded_year'])): ?>
                                    <div class="info-item">
                                        <strong>📅 Год основания</strong>
                                        <span><? echo $brand_info['founded_year']; ?></span>
                                    </div>
                                <? endif; ?>
                                
                                <? if (!empty($brand_info['founder'])): ?>
                                    <div class="info-item">
                                        <strong>👤 Основатель</strong>
                                        <span><? echo $brand_info['founder']; ?></span>
                                    </div>
                                <? endif; ?>
                                
                                <? if (!empty($brand_info['headquarters'])): ?>
                                    <div class="info-item">
                                        <strong>📍 Штаб-квартира</strong>
                                        <span><? echo $brand_info['headquarters']; ?></span>
                                    </div>
                                <? endif; ?>
                                
                                <? if (!empty($brand_info['website'])): ?>
                                    <div class="info-item">
                                        <strong>🌐 Официальный сайт</strong>
                                        <span><a href="<? echo $brand_info['website']; ?>" target="_blank" style="color: #3498db; text-decoration: none;"><? echo str_replace(['https://', 'http://'], '', $brand_info['website']); ?></a></span>
                                    </div>
                                <? endif; ?>
                            </div>
                            
                            <? if (!empty($brand_info['website'])): ?>
                                <div style="margin-top: 20px; text-align: center;">
                                    <a href="<? echo $brand_info['website']; ?>" target="_blank" style="background: #27ae60; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; display: inline-block; width: 100%; box-sizing: border-box; text-align: center;">
                                        Перейти на официальный сайт →
                                    </a>
                                </div>
                            <? endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?
        exit;
    } else {
        echo "<script>alert('Бренд не найден!'); location.href = '?';</script>";
        exit;
    }
}

// ===== КОРЗИНА =====
if (isset($_GET['cart'])) {
    if (!isset($_SESSION['user_id'])) {
        echo "<script>alert('Войдите чтобы просмотреть корзину!'); location.href = '';</script>";
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    
    $cart_result = $mysqli->query("
        SELECT DISTINCT products.*, cart_items.cart_item_id,
        (SELECT image FROM images WHERE product_id = products.product_id AND main_image = 1 LIMIT 1) as image
        FROM cart_items 
        JOIN products ON cart_items.product_id = products.product_id 
        WHERE cart_items.user_id = $user_id
    ");
    
    echo '<div style="position:fixed; top:0; left:0; right:0; background:#1a1a2e; padding:12px 20px; z-index:1000; display:flex; align-items:center; gap:10px; flex-wrap:wrap; box-shadow:0 2px 10px rgba(0,0,0,0.3);">';
    echo '<a href="?" style="color:white; text-decoration:none; font-weight:bold; font-size:18px;">🏠 Главная</a>';
    if (isset($_SESSION['user_id'])) {
        echo '<a href="?cart=1" style="color:white; text-decoration:none; padding:5px 12px; background:#e74c3c; border-radius:20px;">🛒 Корзина</a>';
        echo '<a href="?orders=1" style="color:white; text-decoration:none; padding:5px 12px; background:#3498db; border-radius:20px;">📦 Заказы</a>';
    }
    echo '<span style="color:#666; margin:0 5px;">|</span>';
    foreach ($brands_menu as $brand) {
        $icon = isset($brand_icons[$brand['brand_name']]) ? $brand_icons[$brand['brand_name']] : '🚗';
        echo '<a href="?brand=' . urlencode($brand['brand_name']) . '" style="color:white; text-decoration:none; padding:6px 14px; border-radius:20px; font-size:14px; transition:all 0.3s;" onmouseover="this.style.background=\'#e74c3c\'" onmouseout="this.style.background=\'transparent\'">' . $icon . ' ' . $brand['brand_name'] . '</a>';
    }
    echo '</div>';
    
    echo '<div style="position:fixed; top:70px; right:20px; z-index:999;">';
    authForm();
    echo '</div>';
    ?>
    
    <div style="margin-top: 80px; padding: 20px;">
        <a href="?" style="text-decoration: none; color: #3498db; font-size: 16px;">← Назад к каталогу</a>
        <h1>Корзина</h1>
        
        <?
        if ($cart_result && $cart_result->num_rows > 0) {
            echo '<div style="display: flex; flex-wrap: wrap; gap: 20px;">';
            
            while ($item = $cart_result->fetch_assoc()) {
                echo '
                <div style="border: 1px solid #ddd; padding: 15px; width: 300px; background: white; border-radius: 5px;">
                    <h3>' . $item['brand'] . ' ' . $item['model'] . '</h3>';
                
                if (!empty($item['image'])) {
                    echo '<img src="' . $item['image'] . '" style="width: 100%; height: 200px; object-fit: cover; margin-bottom: 10px;">';
                } else {
                    echo '<div style="width: 100%; height: 200px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">Нет фото</div>';
                }
                
                echo '
                    <p><strong>Год:</strong> ' . $item['year'] . '</p>
                    <p><strong>Цвет:</strong> ' . $item['color'] . '</p>
                    <p><strong>Двигатель:</strong> ' . $item['engine'] . '</p>
                    <div style="font-size: 24px; color: #e74c3c; font-weight: bold; margin-top: 10px;">
                        $' . number_format($item['price']) . '
                    </div>
                    <a href="?car_id=' . $item['product_id'] . '" style="color: #3498db; margin-top: 10px; text-align: center; display: block;">
                        Нажмите для подробностей →
                    </a>
                    <form method="POST" style="margin-top: 10px; text-align: center;" onsubmit="return confirm(\'Удалить товар из корзины?\')">
                        <input type="hidden" name="remove_from_cart" value="' . $item['cart_item_id'] . '">
                        <button type="submit" style="color: red; border: none; background: none; cursor: pointer;">🗑️ Удалить из корзины</button>
                    </form>
                    <form method="POST" style="margin-top: 10px; text-align: center;" onsubmit="return confirm(\'Хотите заказать товар?\')">
                        <input type="hidden" name="order_cars" value="' . $item['product_id'] . '">
                        <button type="submit" style="color: green; border: none; background: none; cursor: pointer;">Заказать</button>
                    </form>
                </div>';
            }
            
            echo '</div>';
        } else {
            echo '<p>Корзина пуста</p>';
        }
        ?>
    </div>
    
    <?
    exit;
}

// ===== СТРАНИЦА АВТОМОБИЛЯ =====
if (isset($_GET['car_id'])) {
    $car_id = safe($_GET['car_id']);
    
    $car_result = $mysqli->query("SELECT * FROM products WHERE product_id = $car_id");
    $car = $car_result->fetch_assoc();
    
    $images_result = $mysqli->query("SELECT * FROM images WHERE product_id = $car_id ORDER BY main_image DESC");
    $images = [];
    while ($image = $images_result->fetch_assoc()) {
        $images[] = $image;
    }
    
    $comments_result = $mysqli->query("
        SELECT comments.*, users.username 
        FROM comments 
        JOIN users ON comments.user_id = users.user_id 
        WHERE product_id = $car_id 
        ORDER BY comment_date DESC
    ");
    
    echo '<div style="position:fixed; top:0; left:0; right:0; background:#1a1a2e; padding:12px 20px; z-index:1000; display:flex; align-items:center; gap:10px; flex-wrap:wrap; box-shadow:0 2px 10px rgba(0,0,0,0.3);">';
    echo '<a href="?" style="color:white; text-decoration:none; font-weight:bold; font-size:18px;">🏠 Главная</a>';
    if (isset($_SESSION['user_id'])) {
        echo '<a href="?cart=1" style="color:white; text-decoration:none; padding:5px 12px; background:#e74c3c; border-radius:20px;">🛒 Корзина</a>';
        echo '<a href="?orders=1" style="color:white; text-decoration:none; padding:5px 12px; background:#3498db; border-radius:20px;">📦 Заказы</a>';
    }
    echo '<span style="color:#666; margin:0 5px;">|</span>';
    foreach ($brands_menu as $brand) {
        $icon = isset($brand_icons[$brand['brand_name']]) ? $brand_icons[$brand['brand_name']] : '🚗';
        echo '<a href="?brand=' . urlencode($brand['brand_name']) . '" style="color:white; text-decoration:none; padding:6px 14px; border-radius:20px; font-size:14px; transition:all 0.3s;" onmouseover="this.style.background=\'#e74c3c\'" onmouseout="this.style.background=\'transparent\'">' . $icon . ' ' . $brand['brand_name'] . '</a>';
    }
    echo '</div>';
    
    echo '<div style="position:fixed; top:70px; right:20px; z-index:999;">';
    authForm();
    echo '</div>';
    ?>
    
    <div style="margin-top: 80px; padding: 20px; max-width: 1200px; margin-left: auto; margin-right: auto;">
        <a href="?" style="text-decoration: none; color: #3498db; font-size: 16px; display: inline-block; margin-bottom: 20px;">← Назад к каталогу</a>
        
        <div style="display: flex; flex-wrap: wrap; gap: 30px;">
            <div style="flex: 1; min-width: 300px;">
                <h2 style="margin-top: 0;"><? echo $car['brand'] . ' ' . $car['model'] . ' (' . $car['year'] . ')'; ?></h2>
                
                <? if (!empty($images)): ?>
                    <div style="margin-bottom: 15px; border-radius: 8px; overflow: hidden; background: #f8f9fa;">
                        <img src="<? echo $images[0]['image']; ?>" id="mainImage" style="width:100%; height:400px; object-fit:cover; display:block;">
                    </div>
                    
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <? foreach ($images as $index => $image): ?>
                            <img src="<? echo $image['image']; ?>" 
                                 style="width: 80px; height: 60px; object-fit: cover; border: 2px solid <? echo $index === 0 ? '#3498db' : '#ddd'; ?>; border-radius: 4px; cursor: pointer;" 
                                 onclick="changeMainImage(this.src, this)" 
                                 onmouseover="this.style.borderColor='#3498db'" 
                                 onmouseout="this.style.borderColor='<? echo $index === 0 ? '#3498db' : '#ddd'; ?>'">
                        <? endforeach; ?>
                    </div>
                    
                    <script>
                    function changeMainImage(src, element) {
                        document.getElementById('mainImage').src = src;
                        // Обновляем边框 у всех миниатюр
                        document.querySelectorAll('.gallery-thumbnail').forEach(function(img) {
                            img.style.borderColor = '#ddd';
                        });
                        element.style.borderColor = '#3498db';
                    }
                    // Добавляем класс для миниатюр
                    document.querySelectorAll('.gallery-thumbnail').forEach(function(img) {
                        img.className = 'gallery-thumbnail';
                    });
                    </script>
                <? else: ?>
                    <div style="width: 100%; height: 300px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
                        Нет фото
                    </div>
                <? endif; ?>
            </div>
            
            <div style="flex: 1; min-width: 300px;">
                <div style="font-size: 32px; color: #e74c3c; font-weight: bold; margin-bottom: 20px;">
                    $<? echo number_format($car['price']); ?>
                </div>
                
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <h3>Характеристики</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <p><strong>Год:</strong> <? echo $car['year']; ?></p>
                        <p><strong>Цвет:</strong> <? echo $car['color']; ?></p>
                        <p><strong>Двигатель:</strong> <? echo $car['engine']; ?></p>
                        <p><strong>Мощность:</strong> <? echo $car['horsepower']; ?> л.с.</p>
                        <p><strong>КПП:</strong> <? echo $car['transmission']; ?></p>
                        <p><strong>Привод:</strong> <? echo $car['drive']; ?></p>
                        <p><strong>Топливо:</strong> <? echo $car['fuel_type']; ?></p>
                        <? if (!empty($car['mileage'])): ?>
                            <p><strong>Пробег:</strong> <? echo number_format($car['mileage']); ?> км</p>
                        <? endif; ?>
                    </div>
                </div>
                
                <? if (!empty($car['description'])): ?>
                    <div style="margin-top: 20px;">
                        <h3>Описание</h3>
                        <p style="line-height: 1.6;"><? echo nl2br($car['description']); ?></p>
                    </div>
                <? endif; ?>
                
                <div style="margin-top: 30px;">
                    <? if (isset($_SESSION['user_id'])): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="add_to_cart" value="<? echo $car_id; ?>">
                            <input type="hidden" name="return_url" value="?car_id=<? echo $car_id; ?>">
                            <button type="submit" style="background: #27ae60; color: white; border: none; padding: 15px 30px; font-size: 18px; border-radius: 5px; cursor: pointer; margin-right: 10px;">
                                Добавить в корзину
                            </button>
                        </form>
                    <? else: ?>
                        <button style="background: #95a5a6; color: white; border: none; padding: 15px 30px; font-size: 18px; border-radius: 5px;" disabled>
                            Войдите чтобы купить
                        </button>
                    <? endif; ?>
                </div>

                <div style="margin-top: 40px;">
                    <h3>Комментарии</h3>
                    
                    <? if (isset($_SESSION['user_id'])): ?>
                    <form method="POST" style="margin-bottom: 20px;">
                        <input type="hidden" name="add_comment" value="1">
                        <input type="hidden" name="product_id" value="<? echo $car_id; ?>">
                        <textarea name="comment_text" placeholder="Оставьте ваш комментарий..." required 
                                  style="width: 100%; height: 80px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px; box-sizing: border-box;"></textarea>
                        <button type="submit" style="background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">
                            Добавить комментарий
                        </button>
                    </form>
                    <? else: ?>
                    <p>Войдите, чтобы оставить комментарий</p>
                    <? endif; ?>

                    <?
                    if ($comments_result && $comments_result->num_rows > 0) {
                        while ($comment = $comments_result->fetch_assoc()) {
                            echo '
                            <div style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 8px; background: white;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                    <strong>' . $comment['username'] . '</strong>
                                    <div style="color: #666; font-size: 12px;">' . $comment['comment_date'] . '</div>
                                </div>
                                <p style="margin: 0; line-height: 1.5;">' . $comment['comment_text'] . '</p>
                            </div>';
                        }
                    } else {
                        echo '<p style="text-align: center; color: #666;">Пока нет комментариев. Будьте первым!</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <?
    exit; 
}

// ===== ЗАКАЗЫ =====
if (isset($_GET['orders'])) {
    if (!isset($_SESSION['user_id'])) {
        echo "<script>alert('Войдите чтобы просмотреть заказы!'); location.href = '';</script>";
        exit;
    }
    
    $user_id = $_SESSION['user_id']; 
    
    $order_result = $mysqli->query("
        SELECT orders.*, products.*, 
       (SELECT image FROM images WHERE product_id = products.product_id AND main_image = 1 LIMIT 1) as image
        FROM orders 
        JOIN products ON orders.product_id = products.product_id 
        WHERE user_id = $user_id
        ORDER BY order_date DESC");
    
    echo '<div style="position:fixed; top:0; left:0; right:0; background:#1a1a2e; padding:12px 20px; z-index:1000; display:flex; align-items:center; gap:10px; flex-wrap:wrap; box-shadow:0 2px 10px rgba(0,0,0,0.3);">';
    echo '<a href="?" style="color:white; text-decoration:none; font-weight:bold; font-size:18px;">🏠 Главная</a>';
    if (isset($_SESSION['user_id'])) {
        echo '<a href="?cart=1" style="color:white; text-decoration:none; padding:5px 12px; background:#e74c3c; border-radius:20px;">🛒 Корзина</a>';
        echo '<a href="?orders=1" style="color:white; text-decoration:none; padding:5px 12px; background:#3498db; border-radius:20px;">📦 Заказы</a>';
    }
    echo '<span style="color:#666; margin:0 5px;">|</span>';
    foreach ($brands_menu as $brand) {
        $icon = isset($brand_icons[$brand['brand_name']]) ? $brand_icons[$brand['brand_name']] : '🚗';
        echo '<a href="?brand=' . urlencode($brand['brand_name']) . '" style="color:white; text-decoration:none; padding:6px 14px; border-radius:20px; font-size:14px; transition:all 0.3s;" onmouseover="this.style.background=\'#e74c3c\'" onmouseout="this.style.background=\'transparent\'">' . $icon . ' ' . $brand['brand_name'] . '</a>';
    }
    echo '</div>';
    
    echo '<div style="position:fixed; top:70px; right:20px; z-index:999;">';
    authForm();
    echo '</div>';
    ?>
    
    <div style="margin-top: 80px; padding: 20px;">
        <a href="?" style="text-decoration: none; color: #3498db; font-size: 16px;">← Назад к каталогу</a>
        <h1>Мои заказы</h1>
        
        <?
        if ($order_result && $order_result->num_rows > 0) {
            echo '<div style="display: flex; flex-wrap: wrap; gap: 20px;">';
            
            while ($order = $order_result->fetch_assoc()) {
                echo '
                <div style="border: 1px solid #ddd; padding: 15px; width: 300px; background: white; border-radius: 5px;">
                    <h3>' . $order['brand'] . ' ' . $order['model'] . '</h3>';
                
                if (!empty($order['image'])) {
                    echo '<img src="' . $order['image'] . '" style="width: 100%; height: 200px; object-fit: cover; margin-bottom: 10px;">';
                } else {
                    echo '<div style="width: 100%; height: 200px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">Нет фото</div>';
                }
                
                echo '
                    <p><strong>Год:</strong> ' . $order['year'] . '</p>
                    <p><strong>Цвет:</strong> ' . $order['color'] . '</p>
                    <p><strong>Двигатель:</strong> ' . $order['engine'] . '</p>
                    <div style="font-size: 24px; color: #e74c3c; font-weight: bold; margin-top: 10px;">
                        $' . number_format($order['price']) . '
                    </div>
                    <p><strong>Дата заказа:</strong> ' . $order['order_date'] . '</p>
                    <a href="?car_id=' . $order['product_id'] . '" style="color: #3498db; margin-top: 10px; text-align: center; display: block;">
                        Подробнее о машине →
                    </a>
                </div>';
            }
            
            echo '</div>';
        } else {
            echo '
            <div style="text-align: center; padding: 50px;">
                <h2 style="color: #666;">У вас нет заказов</h2>
                <p style="color: #999;">Вернитесь в каталог, чтобы выбрать автомобиль</p>
                <a href="?" style="display: inline-block; margin-top: 20px; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px;">
                    Перейти в каталог
                </a>
            </div>';
        }
        ?>
    </div>
    
    <?
    exit;
}

// ===== ГЛАВНАЯ СТРАНИЦА =====
// Получаем параметры фильтрации
$filter_brand = isset($_GET['filter_brand']) ? safe($_GET['filter_brand']) : '';
$filter_model = isset($_GET['model']) ? safe($_GET['model']) : '';
$filter_year_from = isset($_GET['year_from']) ? (int)$_GET['year_from'] : '';
$filter_year_to = isset($_GET['year_to']) ? (int)$_GET['year_to'] : '';
$filter_price_from = isset($_GET['price_from']) ? (int)$_GET['price_from'] : '';
$filter_price_to = isset($_GET['price_to']) ? (int)$_GET['price_to'] : '';
$filter_color = isset($_GET['color']) ? safe($_GET['color']) : '';
$filter_fuel_type = isset($_GET['fuel_type']) ? safe($_GET['fuel_type']) : '';
$filter_transmission = isset($_GET['transmission']) ? safe($_GET['transmission']) : '';

$filter_conditions = "WHERE 1=1";
if($filter_brand != '') {
    $filter_conditions .= " AND brand = '" . $filter_brand . "'";
}
if($filter_model != '') {
    $filter_conditions .= " AND model LIKE '%" . $filter_model . "%'";
}
if($filter_year_from != '') {
    $filter_conditions .= " AND year >= " . $filter_year_from;
}
if($filter_year_to != '') {
    $filter_conditions .= " AND year <= " . $filter_year_to;
}
if($filter_price_from != '') {
    $filter_conditions .= " AND price >= " . $filter_price_from;
}
if($filter_price_to != '') {
    $filter_conditions .= " AND price <= " . $filter_price_to;
}
if($filter_color != '') {
    $filter_conditions .= " AND color LIKE '%" . $filter_color . "%'";
}
if($filter_fuel_type != '') {
    $filter_conditions .= " AND fuel_type LIKE '%" . $filter_fuel_type . "%'";
}
if($filter_transmission != '') {
    $filter_conditions .= " AND transmission = '" . $filter_transmission . "'";
}

echo '<div style="position:fixed; top:0; left:0; right:0; background:#1a1a2e; padding:12px 20px; z-index:1000; display:flex; align-items:center; gap:10px; flex-wrap:wrap; box-shadow:0 2px 10px rgba(0,0,0,0.3);">';
echo '<a href="?" style="color:white; text-decoration:none; font-weight:bold; font-size:18px;">🏠 Главная</a>';
if (isset($_SESSION['user_id'])) {
    echo '<a href="?cart=1" style="color:white; text-decoration:none; padding:5px 12px; background:#e74c3c; border-radius:20px;">🛒 Корзина</a>';
    echo '<a href="?orders=1" style="color:white; text-decoration:none; padding:5px 12px; background:#3498db; border-radius:20px;">📦 Заказы</a>';
}
echo '<span style="color:#666; margin:0 5px;">|</span>';
foreach ($brands_menu as $brand) {
    $icon = isset($brand_icons[$brand['brand_name']]) ? $brand_icons[$brand['brand_name']] : '🚗';
    echo '<a href="?brand=' . urlencode($brand['brand_name']) . '" style="color:white; text-decoration:none; padding:6px 14px; border-radius:20px; font-size:14px; transition:all 0.3s;" onmouseover="this.style.background=\'#e74c3c\'" onmouseout="this.style.background=\'transparent\'">' . $icon . ' ' . $brand['brand_name'] . '</a>';
}
echo '</div>';

echo '<div style="position:fixed; top:70px; right:20px; z-index:999;">';
authForm();
echo '</div>';
?>

<div style="margin-top: 80px; padding: 20px;">
    
    <div style="margin: 20px 0; display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
        <span style="font-weight: bold; color: #2c3e50;">Быстрый фильтр:</span>
        <a href="?" style="background: #3498db; padding: 5px 15px; border-radius: 15px; text-decoration: none; color: white; font-size: 14px;">Все</a>
        <? foreach ($brands_menu as $brand): ?>
            <a href="?filter_brand=<? echo urlencode($brand['brand_name']); ?>" style="background: #ecf0f1; padding: 5px 15px; border-radius: 15px; text-decoration: none; color: #2c3e50; font-size: 14px; transition: 0.3s;" onmouseover="this.style.background='#3498db'; this.style.color='white'" onmouseout="this.style.background='#ecf0f1'; this.style.color='#2c3e50'">
                <? echo $brand['brand_name']; ?>
            </a>
        <? endforeach; ?>
    </div>

    <?php
    echo '
    <div class="filters-section">
        <h3>Подобрать автомобиль</h3>
        <form method="GET" action="">
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; align-items: end;">
                <div class="filter-group">
                    <label>Марка:</label>
                    <input type="text" name="filter_brand" value="' . ($_GET['filter_brand']??'') . '" style="padding: 8px; width: 100%; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div class="filter-group">
                    <label>Модель:</label>
                    <input type="text" name="model" value="' . ($_GET['model']??'') . '" style="padding: 8px; width: 100%; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div class="filter-group">
                    <label>Год от:</label>
                    <input type="number" name="year_from" value="' . ($_GET['year_from']??'') . '" style="padding: 8px; width: 100%; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div class="filter-group">
                    <label>Год до:</label>
                    <input type="number" name="year_to" value="' . ($_GET['year_to']??'') . '" style="padding: 8px; width: 100%; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div class="filter-group">
                    <label>Цена от:</label>
                    <input type="number" name="price_from" value="' . ($_GET['price_from']??'') . '" style="padding: 8px; width: 100%; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div class="filter-group">
                    <label>Цена до:</label>
                    <input type="number" name="price_to" value="' . ($_GET['price_to']??'') . '" style="padding: 8px; width: 100%; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div class="filter-group">
                    <label>Цвет:</label>
                    <input type="text" name="color" value="' . ($_GET['color']??'') . '" style="padding: 8px; width: 100%; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div class="filter-group">
                    <label>Топливо:</label>
                    <input type="text" name="fuel_type" value="' . ($_GET['fuel_type']??'') . '" style="padding: 8px; width: 100%; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div class="filter-group">
                    <label>КПП:</label>
                    <select name="transmission" style="padding: 8px; width: 100%; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">Все КПП</option>
                        <option value="Автомат" ' . ((isset($_GET['transmission']) && $_GET['transmission'] == 'Автомат') ? 'selected' : '') . '>Автомат</option>
                        <option value="Механика" ' . ((isset($_GET['transmission']) && $_GET['transmission'] == 'Механика') ? 'selected' : '') . '>Механика</option>
                    </select>
                </div>
                <div class="filter-group" style="grid-column: span 2;">
                    <button type="submit" class="btn btn-primary">Применить фильтр</button>
                    <a href="?" class="btn btn-secondary">Сбросить</a>
                </div>
            </div>
        </form>
    </div>';

    if(isset($_SESSION['user_id']) && isset($_COOKIE['recently_viewed'])){
        $val = $_COOKIE['recently_viewed'];
        $arr = explode(';', $val);
        
        if(!empty($arr)){
            echo '<div style="margin-bottom: 40px;">';
            echo '<h2>Недавно просмотренные</h2>';
            echo '<div style="display: flex; flex-wrap: wrap; gap: 15px;">';
            
            foreach($arr as $car_id){
                $car_result = $mysqli->query("SELECT *, (SELECT image FROM images WHERE product_id = products.product_id AND main_image = 1 LIMIT 1) as image 
                    FROM products 
                    WHERE product_id = $car_id");
                if($car_result && $car = $car_result->fetch_assoc()){
                    $likes_count = $mysqli->query("SELECT COUNT(*) as count FROM likes WHERE product_id = $car_id")->fetch_assoc()['count'];
                    
                    echo '
                    <div class="car-card" onclick="location.href=\'?car_id=' . $car['product_id'] . '\'">
                        <div style="display: flex; justify-content: space-between; align-items: start; padding: 10px 15px 0 15px;">
                            <h4 style="margin: 0;">' . $car['brand'] . ' ' . $car['model'] . '</h4>
                        </div>
                        <div style="padding: 0 15px;">';
                    
                    if (!empty($car['image'])) {
                        echo '<img src="' . $car['image'] . '" alt="' . $car['brand'] . ' ' . $car['model'] . '">';
                    } else {
                        echo '<div style="width: 100%; height: 150px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; font-size: 12px;">Нет фото</div>';
                    }
                    
                    echo '</div>
                        <div class="car-card-body">
                            <p style="margin: 2px 0; font-size: 13px;"><strong>Год:</strong> ' . $car['year'] . '</p>
                            <p style="margin: 2px 0; font-size: 13px;"><strong>Двигатель:</strong> ' . $car['engine'] . '</p>
                            <div>
                                <div class="price">$' . number_format($car['price']) . '</div>
                                <div class="likes">♥ ' . $likes_count . '</div>
                                <div class="details-link">Нажмите для подробностей →</div>
                            </div>
                        </div>
                    </div>';
                }
            }
            
            echo '</div>';
            echo '</div>';
        }
    }
    
    echo '<div style="margin-bottom: 40px;">';
    echo '<h2>Популярные категории</h2>';
    
    $popular_categories_query = "
        SELECT brand, COUNT(order_id) as order_count 
        FROM orders 
        JOIN products ON orders.product_id = products.product_id 
        GROUP BY brand 
        ORDER BY order_count DESC 
        LIMIT 3
    ";
    
    $categories_result = $mysqli->query($popular_categories_query);
    
    if ($categories_result && $categories_result->num_rows > 0) {
        echo '<div style="display: flex; flex-wrap: wrap; gap: 30px;">';
        
        while ($category = $categories_result->fetch_assoc()) {
            $brand = $category['brand'];
            $order_count = $category['order_count'];
            
            echo '<div style="flex: 1; min-width: 300px;">';
            echo '<div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">';
            echo '<h3 style="margin: 0 0 5px 0; color: #2c3e50;">' . $brand . '</h3>';
            echo '<p style="margin: 0; color: #7f8c8d; font-size: 14px;">' . $order_count . ' заказов</p>';
            echo '</div>';
            
            $random_products_query = "
                SELECT * FROM products 
                WHERE brand = '$brand' 
                ORDER BY RAND() 
                LIMIT 3
            ";
            
            $products_result = $mysqli->query($random_products_query);
            
            if ($products_result && $products_result->num_rows > 0) {
                echo '<div style="display: flex; flex-direction: column; gap: 10px;">';
                
                while ($product = $products_result->fetch_assoc()) {
                    $image_result = $mysqli->query("SELECT image FROM images WHERE product_id = " . $product['product_id'] . " AND main_image = 1 LIMIT 1");
                    $image = $image_result->fetch_assoc();
                    $image_url = $image ? $image['image'] : '';
                    
                    $likes_count = $mysqli->query("SELECT COUNT(*) as count FROM likes WHERE product_id = " . $product['product_id'])->fetch_assoc()['count'];
                    
                    echo '
                    <div class="car-card" onclick="location.href=\'?car_id=' . $product['product_id'] . '\'">
                        <div style="display: flex; gap: 10px; padding: 10px;">
                            <div style="flex-shrink: 0; width: 80px; height: 60px;">';
                    
                    if (!empty($image_url)) {
                        echo '<img src="' . $image_url . '" style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px;">';
                    } else {
                        echo '<div style="width: 80px; height: 60px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 4px; font-size: 10px; color: #666;">Нет фото</div>';
                    }
                    
                    echo '</div>
                            <div style="flex-grow: 1;">
                                <h4 style="margin: 0 0 5px 0; font-size: 14px;">' . $product['model'] . '</h4>
                                <p style="margin: 2px 0; font-size: 12px; color: #666;">' . $product['year'] . ' год • ' . $product['engine'] . '</p>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 5px;">
                                    <div style="font-size: 16px; color: #e74c3c; font-weight: bold;">
                                        $' . number_format($product['price']) . '
                                    </div>
                                    <div style="color: #666; font-size: 11px;">
                                        ♥ ' . $likes_count . ' 
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>';
                }
                
                echo '</div>';
            } else {
                echo '<p style="color: #999; font-style: italic; padding: 10px;">Товары не найдены</p>';
            }
            
            echo '</div>';
        }
        
        echo '</div>';
    } else {
        echo '<p style="color: #999; font-style: italic; padding: 20px; text-align: center;">Нет данных о популярных категориях</p>';
    }
    
    echo '</div>';
    
    echo '<h1 style="margin-bottom: 20px;">Автомобили в продаже</h1>';
    $total_cars_result = $mysqli->query("SELECT COUNT(*) as total FROM products $filter_conditions");
    $total_cars = $total_cars_result->fetch_assoc()['total'];
    $total_pages = ceil($total_cars / $cars_per_page);
    
    $products_result = $mysqli->query("
        SELECT *, 
       (SELECT image FROM images WHERE product_id = products.product_id AND main_image = 1 LIMIT 1) as image
        FROM products 
        $filter_conditions
        ORDER BY product_id 
        LIMIT $cars_per_page OFFSET $offset");
    
    if ($products_result && $products_result->num_rows > 0) {
        echo '<div style="display: flex; flex-wrap: wrap; gap: 20px;">';
        
        while ($car = $products_result->fetch_assoc()) {
            $is_liked = false;
            if (isset($_SESSION['user_id'])) {
                $check_like = $mysqli->query("SELECT * FROM likes WHERE user_id = " . $_SESSION['user_id'] . " AND product_id = " . $car['product_id']);
                $is_liked = $check_like->num_rows > 0;
            }
            
            $likes_count = $mysqli->query("SELECT COUNT(*) as count FROM likes WHERE product_id = " . $car['product_id'])->fetch_assoc()['count'];
            
            echo '
            <div class="car-card" onclick="location.href=\'?car_id=' . $car['product_id'] . '\'">
                <div style="display: flex; justify-content: space-between; align-items: start; padding: 10px 15px 0 15px;">
                    <h3 style="margin: 0;">' . $car['brand'] . ' ' . $car['model'] . '</h3>
                    ' . (isset($_SESSION['user_id']) ? '
                    <div onclick="event.stopPropagation();">
                        <form method="POST" style="margin: 0; display: inline-block;">
                            <input type="hidden" name="like_car" value="' . $car['product_id'] . '">
                            <button type="submit" style="background: none; border: none; cursor: pointer; font-size: 24px; color: ' . ($is_liked ? '#e74c3c' : '#ccc') . '; transition: transform 0.2s;" onmouseover="this.style.transform=\'scale(1.2)\'" onmouseout="this.style.transform=\'scale(1)\'">
                                ♥
                            </button>
                        </form>
                    </div>' : '<span style="font-size: 18px; color: #ccc;">♥</span>') . '
                </div>
                <div style="padding: 0 15px;">';
            
            if (!empty($car['image'])) {
                echo '<img src="' . $car['image'] . '" alt="' . $car['brand'] . ' ' . $car['model'] . '">';
            } else {
                echo '<div style="width: 100%; height: 200px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 4px;">Нет фото</div>';
            }
            
            echo '</div>
                <div class="car-card-body">
                    <p style="margin: 0 0 5px 0; color: #666; font-size: 14px;">' . $car['year'] . ' год • ' . $car['engine'] . '</p>
                    <div style="margin-top: auto;">
                        <div class="price">$' . number_format($car['price']) . '</div>
                        <div class="likes">♥ ' . $likes_count . '</div>
                        <div class="details-link">Нажмите для подробностей →</div>
                    </div>
                </div>
            </div>';
        }
        
        echo '</div>';
        
        echo '<div style="margin-top: 30px; text-align: center;">';
        echo '<div style="display: inline-block; background: #f8f9fa; padding: 15px; border-radius: 8px;">';
        
        if ($total_pages > 1) {
            if ($current_page > 1) {
                echo '<form method="POST" style="display: inline;">';
                echo '<input type="hidden" name="page" value="' . ($current_page - 1) . '">';
                echo '<button type="submit" style="padding: 8px 15px; margin: 0 5px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer;">← Назад</button>';
                echo '</form>';
            }
            
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++) {
                if ($i == $current_page) {
                    echo '<span style="padding: 8px 15px; margin: 0 5px; background: #e74c3c; color: white; border-radius: 4px; font-weight: bold;">' . $i . '</span>';
                } else {
                    echo '<form method="POST" style="display: inline;">';
                    echo '<input type="hidden" name="page" value="' . $i . '">';
                    echo '<button type="submit" style="padding: 8px 15px; margin: 0 5px; background: #95a5a6; color: white; border: none; border-radius: 4px; cursor: pointer;">' . $i . '</button>';
                    echo '</form>';
                }
            }
            
            if ($current_page < $total_pages) {
                echo '<form method="POST" style="display: inline;">';
                echo '<input type="hidden" name="page" value="' . ($current_page + 1) . '">';
                echo '<button type="submit" style="padding: 8px 15px; margin: 0 5px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer;">Вперед →</button>';
                echo '</form>';
            }
        }
        
        echo '</div>';
        echo '</div>';
          
    } else {
        echo '<p>Автомобилей нет в наличии</p>';
    }
    ?>
</div>