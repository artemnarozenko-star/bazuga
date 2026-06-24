<?session_start();
$mysqli = new mysqli('localhost', 'root', '', 'avtosalon');
if ($mysqli->connect_error) {
    die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

#echo '<link rel="stylesheet" href="style.css">';

function safe($value) {
    global $mysqli;
    return $mysqli->real_escape_string(strip_tags(trim($value)));
}

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
    }
}
    else{
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
            if ($user['password'] === $pass) {
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
                        $photo = '';
                        $stmt = $mysqli->prepare("INSERT INTO users (username, password, phone, photo) VALUES (?, ?, ?, ?)");
                        if ($stmt) {
                            $stmt->bind_param("ssss", $username, $pass, $phone, $photo);
                            
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
    
    echo '<div style="position:absolute; top:0; right:0; padding:5px; background:#f0f0f0; border:1px solid #ccc;">';
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
    
    echo '<div style="position:absolute; top:0; right:0; padding:5px; background:#f0f0f0; border:1px solid #ccc;">';
        authForm();
    echo '</div>';
    ?>
    
    <div style="margin-top: 80px; padding: 20px;">
        <a href="?" style="text-decoration: none; color: #3498db; font-size: 16px;">← Назад к каталогу</a>
        
        <div style="display: flex; gap: 30px; margin-top: 20px;">
            <div style="flex: 1;">
                <h2><? echo $car['brand'] . ' ' . $car['model'] . ' (' . $car['year'] . ')'; ?></h2>
                
                <? if (!empty($images)): ?>
                    <div style="margin-bottom: 15px;">
                        <? 
                        $main_image_found = false;
                        foreach ($images as $image) {
                            if ($image['main_image'] == 1) {
                                echo '<img src="' . $image['image'] . '" width="500" height="400" class="im" style="border-radius: 8px; object-fit: cover;"><br>';
                                $main_image_found = true;
                                break;
                            }
                        }
                        if (!$main_image_found && !empty($images[0])) {
                            echo '<img src="' . $images[0]['image'] . '" width="500" height="400" class="im" style="border-radius: 8px; object-fit: cover;"><br>';
                        }
                        ?>
                    </div>
                    
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <? foreach ($images as $image): ?>
                            <img src="<? echo $image['image']; ?>" 
                                 class="im" 
                                 onClick="f(this)"
                                 style="width: 80px; height: 60px; object-fit: cover; cursor: pointer; border: 2px solid #ddd; border-radius: 4px;" 
                                 onmouseover="this.style.borderColor='#3498db'" 
                                 onmouseout="this.style.borderColor='#ddd'">
                        <? endforeach; ?>
                    </div>
                <? else: ?>
                    <div style="width: 100%; height: 300px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
                        Нет фото
                    </div>
                <? endif; ?>
            </div>
            
            <div style="flex: 1;">
                <div style="font-size: 28px; color: #e74c3c; font-weight: bold; margin-bottom: 20px;">
                    $<? echo number_format($car['price']); ?>
                </div>
                
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <h3>Характеристики</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <?
                        echo '
                        <p><strong>Год :</strong> ' . $car['year'] . '</p>
                        <p><strong>Цвет:</strong> ' . $car['color'] . '</p>
                        <p><strong>Двигатель:</strong> ' . $car['engine'] . '</p>
                        <p><strong>Мощность:</strong> ' . $car['horsepower'] . ' л.с.</p>
                        <p><strong>КПП:</strong> ' . $car['transmission'] . '</p>
                        <p><strong>Привод:</strong> ' . $car['drive'] . '</p>
                        <p><strong>Топливо:</strong> ' . $car['fuel_type'] . '</p>';?>

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
                                  style="width: 100%; height: 80px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px;"></textarea>
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
    
    <script>
    function f(obj) {
        document.getElementsByClassName('im')[0].src = obj.src;
    }
    </script>
    
    <?
    exit; 
}

echo '<div style="position:absolute; top:0; right:0; padding:5px; background:#f0f0f0; border:1px solid #ccc;">';
    authForm();
echo '</div>';

if (isset($_SESSION['user_id'])) {
    echo '<a href="?cart=1" style="position:absolute; top:0; left:0; padding:10px; background:#3498db; color:white; text-decoration:none; border-radius:0 0 5px 0;">🛒 Корзина</a>';
    echo '<a href="?orders=1" style="position:absolute; top:0; left:120px; padding:10px; background:#3498db; color:white; text-decoration:none; border-radius:0 0 5px 5px;">Мои заказы</a>';
}

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
    
    echo '<div style="position:absolute; top:0; right:0; padding:5px; background:#f0f0f0; border:1px solid #ccc;">';
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

$filter_conditions = "WHERE 1=1";
if(isset($_GET['brand']) && $_GET['brand'] != '') {
    $filter_conditions .= " AND brand LIKE '%".safe($_GET['brand'])."%'";
}
if(isset($_GET['model']) && $_GET['model'] != '') {
    $filter_conditions .= " AND model LIKE '%".safe($_GET['model'])."%'";
}
if(isset($_GET['year_from']) && $_GET['year_from'] != '') {
    $filter_conditions .= " AND year >= ".(int)$_GET['year_from'];
}
if(isset($_GET['year_to']) && $_GET['year_to'] != '') {
    $filter_conditions .= " AND year <= ".(int)$_GET['year_to'];
}
if(isset($_GET['price_from']) && $_GET['price_from'] != '') {
    $filter_conditions .= " AND price >= ".(int)$_GET['price_from'];
}
if(isset($_GET['price_to']) && $_GET['price_to'] != '') {
    $filter_conditions .= " AND price <= ".(int)$_GET['price_to'];
}
if(isset($_GET['color']) && $_GET['color'] != '') {
    $filter_conditions .= " AND color LIKE '%".safe($_GET['color'])."%'";
}
if(isset($_GET['fuel_type']) && $_GET['fuel_type'] != '') {
    $filter_conditions .= " AND fuel_type LIKE '%".safe($_GET['fuel_type'])."%'";
}
if(isset($_GET['transmission']) && $_GET['transmission'] != '') {
    $filter_conditions .= " AND transmission = '".safe($_GET['transmission'])."'";
}
?>

<div style="margin-top: 20px; padding: 20px;">
    
    
    <?
echo '
<div class="filters-section">
    
    <h3>Подобрать автомобиль</h3>
    <form method="GET" action="">
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; align-items: end;">
            <div class="filter-group">
                <label>Марка:</label>
                <input type="text" name="brand" value="'.($_GET['brand']??'').'" style="padding: 8px; width: 100%; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div class="filter-group">
                <label>Модель:</label>
                <input type="text" name="model" value="'.($_GET['model']??'').'" style="padding: 8px; width: 100%; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div class="filter-group">
                <label>Год от:</label>
                <input type="number" name="year_from" value="'.($_GET['year_from']??'').'" style="padding: 8px; width: 100%; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div class="filter-group">
                <label>Год до:</label>
                <input type="number" name="year_to" value="'.($_GET['year_to']??'').'" style="padding: 8px; width: 100%; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div class="filter-group">
                <label>Цена от:</label>
                <input type="number" name="price_from" value="'.($_GET['price_from']??'').'" style="padding: 8px; width: 100%; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div class="filter-group">
                <label>Цена до:</label>
                <input type="number" name="price_to" value="'.($_GET['price_to']??'').'" style="padding: 8px; width: 100%; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div class="filter-group">
                <label>Цвет:</label>
                <input type="text" name="color" value="'.($_GET['color']??'').'" style="padding: 8px; width: 100%; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div class="filter-group">
                <label>Топливо:</label>
                <input type="text" name="fuel_type" value="'.($_GET['fuel_type']??'').'" style="padding: 8px; width: 100%; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div class="filter-group">
                <label>КПП:</label>
                <select name="transmission" style="padding: 8px; width: 100%; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">Все КПП</option>
                    <option value="Автомат" '.((isset($_GET['transmission']) && $_GET['transmission'] == 'Автомат') ? 'selected' : '').'>Автомат</option>
                    <option value="Механика" '.((isset($_GET['transmission']) && $_GET['transmission'] == 'Механика') ? 'selected' : '').'>Механика</option>
                </select>
            </div>
            <div class="filter-group" style="grid-column: span 2;">
                <button type="submit" class="btn btn-primary" >Применить фильтр</button>
                <a href="?" class="btn btn-secondary" >Сбросить</a>
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
                    <div style="border: 1px solid #ddd; padding: 10px; width: 250px; background: white; border-radius: 5px; cursor: pointer;" 
                         onclick="location.href=\'?car_id=' . $car['product_id'] . '\'">
                        <h4 style="margin: 0 0 8px 0;">' . $car['brand'] . ' ' . $car['model'] . '</h4>';
                    
                    if (!empty($car['image'])) {
                        echo '<img src="' . $car['image'] . '" style="width: 100%; height: 150px; object-fit: cover; margin-bottom: 8px;">';
                    } else {
                        echo '<div style="width: 100%; height: 150px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; margin-bottom: 8px; font-size: 12px;">Нет фото</div>';
                    }
                    
                    echo '
                        <p style="margin: 2px 0; font-size: 13px;"><strong>Год:</strong> ' . $car['year'] . '</p>
                        <p style="margin: 2px 0; font-size: 13px;"><strong>Двигатель:</strong> ' . $car['engine'] . '</p>
                        <div style="font-size: 18px; color: #e74c3c; font-weight: bold; margin-top: 8px;">
                            $' . number_format($car['price']) . '
                        </div>
                        <div style="color: #666; margin-top: 5px; font-size: 12px;">
                            ♥ ' . $likes_count . ' 
                        </div>
                        <div style="color: #3498db; margin-top: 8px; text-align: center; font-size: 12px;">
                            Нажмите для подробностей →
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
                    <div style="border: 1px solid #e0e0e0; padding: 10px; background: white; border-radius: 5px; cursor: pointer;" 
                         onclick="location.href=\'?car_id=' . $product['product_id'] . '\'">
                        <div style="display: flex; gap: 10px;">
                            <div style="flex-shrink: 0;">';
                    
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
            <div style="border: 1px solid #ddd; padding: 15px; width: 300px; background: white; border-radius: 5px; cursor: pointer;" 
                 onclick="location.href=\'?car_id=' . $car['product_id'] . '\'">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <h3>' . $car['brand'] . ' ' . $car['model'] . '</h3>
                    ' . (isset($_SESSION['user_id']) ? '
                    <form method="POST" style="margin: 0;" onclick="event.stopPropagation()">
                        <input type="hidden" name="like_car" value="' . $car['product_id'] . '">
                        <button type="submit" style="background: none; border: none; cursor: pointer; font-size: 24px; color: ' . ($is_liked ? '#e74c3c' : '#ccc') . ';">
                            ♥
                        </button>
                    </form>' : '') . '
            </div>';
            
            if (!empty($car['image'])) {
                echo '<img src="' . $car['image'] . '" style="width: 100%; height: 200px; object-fit: cover; margin-bottom: 10px;">';
            } else {
                echo '<div style="width: 100%; height: 200px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">Нет фото</div>';
            }
            
            echo '
                <p><strong>Год:</strong> ' . $car['year'] . '</p>
                <p><strong>Цвет:</strong> ' . $car['color'] . '</p>
                <p><strong>Двигатель:</strong> ' . $car['engine'] . '</p>
                <p><strong>Мощность:</strong> ' . $car['horsepower'] . ' л.с.</p>
                <p><strong>КПП:</strong> ' . $car['transmission'] . '</p>
                <p><strong>Привод:</strong> ' . $car['drive'] . '</p>
                <p><strong>Топливо:</strong> ' . $car['fuel_type'] . '</p>';
            
            if (!empty($car['description'])) {
                echo '<p><strong>Описание:</strong> ' . substr($car['description'], 0, 100) . '...</p>';
            }
            
            echo '
                <div style="font-size: 24px; color: #e74c3c; font-weight: bold; margin-top: 10px;">
                    $' . number_format($car['price']) . '
                </div>
                <div style="color: #666; margin-top: 5px;">
                    ♥ ' . $likes_count . ' 
                </div>
                <div style="color: #3498db; margin-top: 10px; text-align: center;">
                    Нажмите для подробностей →
                </div>';
            
            echo '</div>';
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