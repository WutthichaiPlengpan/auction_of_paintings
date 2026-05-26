<?php
// ตั้งค่า Headers สำหรับ CORS (เพื่อให้ Frontend เรียกใช้งานข้ามโดเมนได้)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// ดักจับ Method OPTIONS สำหรับ Preflight Request ของ Browser
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config/dbs.php';
require_once 'vendor/autoload.php'; // โหลด JWT Library

use \Firebase\JWT\JWT;

// Secret Key สำหรับเซ็น JWT (ในระบบจริงควรเก็บในไฟล์ .env)
$secret_key = "ArtBids_Super_Secret_Key_2026_For_JWT_Authentication!";


$database = new Database();
$db = $database->getConnection();

// รับค่า route จาก .htaccess
$route = isset($_GET['route']) ? $_GET['route'] : '';

// อ่านข้อมูล JSON ที่ Frontend ส่งมา
$data = json_decode(file_get_contents("php://input"));


// 1. LINE Messaging API (สำหรับแจ้งเตือนรายบุคคล เช่น แจ้ง Seller ว่ามีคนบิดราคา)
function sendLineMessage($target_id, $message_text)
{
    // 💡 นำ Channel Access Token (Long-lived) จาก LINE Developers มาใส่ตรงนี้
    $channel_access_token = 'XCd5E1mGjHRfWzybbnxaFbDuF99tFljNXHJjq61Z20088KSemWajEqLd18OpYqLbb1J1A19CICzVlG0ctMwYQcc4gdcw+52Gsvni/J5XAyBugoh510h3itMCJSby+nT3xexlG+Y9R3P/WP48DZp0HgdB04t89/1O/w1cDnyilFU=';

    $messages = [
        'type' => 'text',
        'text' => $message_text
    ];

    // $target_id สามารถเป็นได้ทั้ง User ID (ขึ้นต้นด้วย U), Group ID (ขึ้นต้นด้วย C), หรือ Room ID (ขึ้นต้นด้วย R)
    $data = [
        'to' => $target_id,
        'messages' => [$messages]
    ];

    $ch = curl_init('https://api.line.me/v2/bot/message/push');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $channel_access_token
    ]);

    $result = curl_exec($ch);


    return $result;
}

// ==========================================
// 🚀 ROUTING: แบ่งการทำงานตาม URL
// ==========================================

switch ($route) {

    // ----------------------------------------------------
    // 1. ระบบสมัครสมาชิก (REGISTER)
    // ----------------------------------------------------
    case 'v1/auth/register':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            break;

        // เช็คว่ามีข้อมูลส่งมาครบถ้วนหรือไม่ (เพิ่มการเช็ค phone ด้วย)
        if (!empty($data->username) && !empty($data->email) && !empty($data->password) && !empty($data->phone)) {
            try {
                // 1. เช็คว่ามี Email หรือ Username นี้หรือยัง
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
                $stmt->execute([$data->email, $data->username]);

                if ($stmt->rowCount() > 0) {
                    http_response_code(400);
                    echo json_encode(["status" => "error", "message" => "อีเมลหรือชื่อผู้ใช้งานนี้มีในระบบแล้ว"]);
                    exit;
                }

                // 2. เข้ารหัส Password
                $password_hash = password_hash($data->password, PASSWORD_BCRYPT, ['cost' => 12]);

                // 3. กำหนด Role ให้ปลอดภัย (ถ้าไม่ได้เลือกให้เป็น bidder)
                $user_role = (isset($data->role) && $data->role === 'seller') ? 'seller' : 'bidder';

                // 4. บันทึกลงฐานข้อมูล (มี ? 5 ตัว)
                $insert_query = "INSERT INTO users (username, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?)";
                $stmt = $db->prepare($insert_query);

                // execute ก็ต้องมีตัวแปร 5 ตัวเรียงตามลำดับด้านบนเป๊ะๆ
                if (
                    $stmt->execute([
                        $data->username,
                        $data->email,
                        $data->phone,
                        $password_hash,
                        $user_role
                    ])
                ) {
                    http_response_code(201);
                    echo json_encode(["status" => "success", "message" => "สมัครสมาชิกสำเร็จ"]);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "เกิดข้อผิดพลาด: " . $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "กรุณากรอกข้อมูลให้ครบถ้วน"]);
        }
        break;

    // ----------------------------------------------------
    // 2. ระบบเข้าสู่ระบบ (LOGIN & สร้าง JWT)
    // ----------------------------------------------------
    case 'v1/auth/login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            break;

        if (!empty($data->username) && !empty($data->password)) {
            // 🔍 1. ค้นหาผู้ใช้งานเพียงรอบเดียว (ดึง status และข้อมูลจำเป็นทั้งหมดมาเลย)
            $stmt = $db->prepare("SELECT id, username, password_hash, role, two_factor_secret, status FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$data->username, $data->username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // 🛑 2. ตรวจสอบว่ามีผู้ใช้จริงไหม
            if (!$user) {
                http_response_code(401);
                echo json_encode(["status" => "error", "message" => "ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง"]);
                exit;
            }

            // 🚫 3. ตรวจสอบว่าถูกแบนหรือไม่ (Check Status ก่อนทำอย่างอื่น)
            if ($user['status'] === 'banned') {
                http_response_code(403);
                echo json_encode(["status" => "error", "message" => "บัญชีของคุณถูกระงับการใช้งาน กรุณาติดต่อแอดมิน"]);
                exit;
            }

            // 🔐 4. ตรวจสอบรหัสผ่าน
            if (password_verify($data->password, $user['password_hash'])) {

                // 🔐 5. ตรวจสอบ 2FA (ถ้าเปิดใช้งานไว้)
                if (!empty($user['two_factor_secret'])) {
                    if (empty($data->otp)) {
                        http_response_code(200);
                        echo json_encode(["status" => "require_otp", "message" => "กรุณากรอกรหัส OTP"]);
                        exit;
                    } else {
                        $google2fa = new \PragmaRX\Google2FA\Google2FA();
                        $valid = $google2fa->verifyKey($user['two_factor_secret'], $data->otp);

                        if (!$valid) {
                            http_response_code(401);
                            echo json_encode(["status" => "error", "message" => "รหัส OTP ไม่ถูกต้อง หรือหมดอายุแล้ว"]);
                            exit;
                        }
                    }
                }

                // 🎫 6. ออก JWT Token
                $issuedAt = time();
                $expirationTime = $issuedAt + 3600;

                $payload = array(
                    "iat" => $issuedAt,
                    "exp" => $expirationTime,
                    "iss" => "artbids.com",
                    "data" => array(
                        "id" => $user['id'],
                        "username" => $user['username'],
                        "role" => $user['role']
                    )
                );

                $jwt = JWT::encode($payload, $secret_key, 'HS256');
                $is_2fa_setup = !empty($user['two_factor_secret']);

                http_response_code(200);
                echo json_encode([
                    "status" => "success",
                    "message" => "เข้าสู่ระบบสำเร็จ",
                    "token" => $jwt,
                    "role" => $user['role'],
                    "is_2fa_setup" => $is_2fa_setup
                ]);

            } else {
                http_response_code(401);
                echo json_encode(["status" => "error", "message" => "ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "กรุณากรอกข้อมูลให้ครบถ้วน"]);
        }
        break;

    //authenticate แล้ว สร้าง QR Code สำหรับ 2FA
    case 'v1/auth/generate-2fa':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            break;

        // 1. ดึง Token จาก Header เพื่อยืนยันว่าเข้าสู่ระบบแล้ว
        $headers = apache_request_headers();
        $token = '';
        if (isset($headers['Authorization'])) {
            $token = str_replace('Bearer ', '', $headers['Authorization']);
        }

        if (!$token) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "ไม่ได้เข้าสู่ระบบ"]);
            exit;
        }

        try {
            // 2. ถอดรหัส Token เพื่อเอา user_id
            $decoded = JWT::decode($token, new \Firebase\JWT\Key($secret_key, 'HS256'));
            $user_id = $decoded->data->id;

            // 3. ดึงอีเมลผู้ใช้จากฐานข้อมูล
            $stmt = $db->prepare("SELECT email, two_factor_secret FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $google2fa = new \PragmaRX\Google2FA\Google2FA();

            // 4. ถ้ายังไม่มี Secret ให้สร้างใหม่แล้วบันทึกลงฐานข้อมูล
            $two_factor_secret = $user['two_factor_secret'];
            if (!$two_factor_secret) {
                $two_factor_secret = $google2fa->generateSecretKey();
                $updateStmt = $db->prepare("UPDATE users SET two_factor_secret = ? WHERE id = ?");
                $updateStmt->execute([$two_factor_secret, $user_id]);
            }

            // 5. สร้าง URL สำหรับให้แอป Authenticator สแกน
            $qrCodeUrl = $google2fa->getQRCodeUrl('ArtBids', $user['email'], $two_factor_secret);

            // 6. ใช้ BaconQrCode แปลง URL เป็นภาพ SVG
            $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(250),
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
            );
            $writer = new \BaconQrCode\Writer($renderer);
            $qr_svg = $writer->writeString($qrCodeUrl);

            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "secret" => $two_factor_secret,
                "qr_svg" => base64_encode($qr_svg) // แปลงเป็น base64 เพื่อนำไปแสดงหน้าเว็บง่ายๆ
            ]);

        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Token ไม่ถูกต้อง หรือหมดอายุ"]);
        }
        break;
    // seller ส่งข้อมูลยืนยันตัวตน (KYC)
    case 'v1/seller/kyc':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            break;

        // 1. ตรวจสอบ JWT Token
        $headers = apache_request_headers();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';
        if (!$token) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "ไม่ได้เข้าสู่ระบบ"]);
            exit;
        }

        try {
            $decoded = JWT::decode($token, new \Firebase\JWT\Key($secret_key, 'HS256'));
            $user_id = $decoded->data->id;
            $role = $decoded->data->role;

            // ตรวจสอบสิทธิ์ว่าต้องเป็นผู้ขายเท่านั้น
            if ($role !== 'seller') {
                http_response_code(403);
                echo json_encode(["status" => "error", "message" => "สงวนสิทธิ์เฉพาะผู้ลงผลงานประมูลเท่านั้น"]);
                exit;
            }

            // 2. รับข้อมูลแบบ multipart/form-data (ใช้ $_POST และ $_FILES)
            $real_name = $_POST['real_name'] ?? '';
            $id_card_no_raw = $_POST['id_card_no'] ?? '';
            $bank_name = $_POST['bank_name'] ?? '';
            $bank_acc_no_raw = $_POST['bank_acc_no'] ?? '';

            if (!$real_name || !$id_card_no_raw || !$bank_name || !$bank_acc_no_raw || !isset($_FILES['id_card_img']) || !isset($_FILES['selfie_img'])) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "กรุณากรอกข้อมูลและอัปโหลดรูปภาพให้ครบถ้วน"]);
                exit;
            }

            // 🔐 ขั้นตอนการเข้ารหัส (Encryption) ด้วย AES-256-CBC
            $encrypt_method = "AES-256-CBC";
            // กุญแจลับ (ควรตั้งให้ยาวและคาดเดายาก และไม่ควรให้ใครรู้)
            $encrypt_key = hash('sha256', 'ArtBids_Secret_Key_2024!');
            // ตัวแปรสุ่มเริ่มต้น (IV) ต้องใช้ 16 ตัวอักษร
            $encrypt_iv = substr(hash('sha256', 'ArtBids_Secret_IV_2024!'), 0, 16);

            // 💡 แก้ไข: เข้ารหัสปุ๊บ ใช้ได้เลย ไม่ต้อง base64_encode ซ้ำ
            $id_card_encrypted = openssl_encrypt($id_card_no_raw, $encrypt_method, $encrypt_key, 0, $encrypt_iv);
            $checkBan = $db->prepare("SELECT reason FROM banned_id_cards WHERE id_card_encrypted = ?");
            $checkBan->execute([$id_card_encrypted]);
            if ($checkBan->fetch()) {
                http_response_code(403);
                echo json_encode(["status" => "error", "message" => "เลขบัตรประชาชนนี้อยู่ในระบบ Blacklist ไม่สามารถใช้งานได้"]);
                exit;
            }
            $bank_acc_encrypted = openssl_encrypt($bank_acc_no_raw, $encrypt_method, $encrypt_key, 0, $encrypt_iv);
            // เข้ารหัสเลขบัญชีธนาคาร
            // $bank_acc_encrypted = openssl_encrypt($bank_acc_no_raw, $encrypt_method, $encrypt_key, 0, $encrypt_iv);
            // $bank_acc_encrypted = base64_encode($bank_acc_encrypted);

            // 3. จัดการอัปโหลดไฟล์ภาพ
            $upload_dir = '../public/uploads/kyc/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true); // สร้างโฟลเดอร์อัตโนมัติถ้ายังไม่มี
            }

            // สุ่มชื่อไฟล์ใหม่เพื่อป้องกันชื่อซ้ำ
            $id_card_ext = strtolower(pathinfo($_FILES['id_card_img']['name'], PATHINFO_EXTENSION));
            $selfie_ext = strtolower(pathinfo($_FILES['selfie_img']['name'], PATHINFO_EXTENSION));

            // เช็คสกุลไฟล์เบื้องต้น
            $allowed_ext = ['jpg', 'jpeg', 'png'];
            if (!in_array($id_card_ext, $allowed_ext) || !in_array($selfie_ext, $allowed_ext)) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "รองรับเฉพาะไฟล์ JPG และ PNG เท่านั้น"]);
                exit;
            }

            $id_card_filename = 'idcard_' . $user_id . '_' . time() . '.' . $id_card_ext;
            $selfie_filename = 'selfie_' . $user_id . '_' . time() . '.' . $selfie_ext;

            move_uploaded_file($_FILES['id_card_img']['tmp_name'], $upload_dir . $id_card_filename);
            move_uploaded_file($_FILES['selfie_img']['tmp_name'], $upload_dir . $selfie_filename);

            // 4. บันทึกลงฐานข้อมูล (ใช้ ON DUPLICATE KEY เผื่อกรณีแอดมินตีตกแล้วต้องอัปโหลดใหม่)
            $query = "INSERT INTO kyc_verifications (user_id, real_name, id_card_no, bank_name, bank_acc_no, id_card_img, selfie_img, status) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, 'pending') 
                      ON DUPLICATE KEY UPDATE 
                      real_name = ?, id_card_no = ?, bank_name = ?, bank_acc_no = ?, id_card_img = ?, selfie_img = ?, status = 'pending', admin_note = NULL";

            $stmt = $db->prepare($query);
            $stmt->execute([
                $user_id,
                $real_name,
                $id_card_encrypted,
                $bank_name,
                $bank_acc_encrypted,
                $id_card_filename,
                $selfie_filename,
                $real_name,
                $id_card_encrypted,
                $bank_name,
                $bank_acc_encrypted,
                $id_card_filename,
                $selfie_filename
            ]);

            // 🔔 [เพิ่มใหม่] ส่ง LINE Messaging API แจ้งเตือนกลุ่มแอดมิน
            $notify_msg = "🚨 มีผู้ส่งเอกสาร KYC ใหม่เข้ามาครับ!\n";
            $notify_msg .= "ผู้ส่ง: " . $real_name . "\n";
            $notify_msg .= "สถานะ: รอการตรวจสอบ";

            // 💡 นำ Admin User ID หรือ Group ID (ที่เชิญบอทเข้ากลุ่มแล้ว) มาใส่ที่นี่
            $admin_group_id = 'Cxxxx_YOUR_ADMIN_GROUP_ID_xxxx';

            // สั่งยิงข้อความ
            sendLineMessage($admin_group_id, $notify_msg);

            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "ส่งข้อมูลยืนยันตัวตนสำเร็จ กรุณารอแอดมินตรวจสอบ"]);

        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Token ไม่ถูกต้อง หรือหมดอายุ"]);
        }
        break;
    // seller สร้างผลงานประมูลใหม่
    case 'v1/seller/auctions/create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            break;

        // 1. ตรวจสอบ JWT Token
        $headers = apache_request_headers();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

        if (!$token) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "ไม่ได้เข้าสู่ระบบ"]);
            exit;
        }

        try {
            $decoded = JWT::decode($token, new \Firebase\JWT\Key($secret_key, 'HS256'));
            $user_id = $decoded->data->id;
            $role = $decoded->data->role;

            if ($role !== 'seller') {
                http_response_code(403);
                echo json_encode(["status" => "error", "message" => "สงวนสิทธิ์เฉพาะผู้ลงผลงานประมูลเท่านั้น"]);
                exit;
            }

            // 🛑 2. ตรวจสอบสถานะ KYC ของผู้ใช้
            $stmtKyc = $db->prepare("SELECT status FROM kyc_verifications WHERE user_id = ?");
            $stmtKyc->execute([$user_id]);
            $kyc = $stmtKyc->fetch(PDO::FETCH_ASSOC);

            if (!$kyc || $kyc['status'] !== 'verified') {
                http_response_code(403); // Forbidden
                $msg = "คุณต้องยืนยันตัวตน (e-KYC) ให้สำเร็จก่อน จึงจะสามารถลงผลงานประมูลได้";
                if ($kyc && $kyc['status'] === 'pending') {
                    $msg = "ข้อมูลยืนยันตัวตนของคุณกำลังรอแอดมินตรวจสอบ กรุณารอ 1-2 วันทำการ";
                } elseif ($kyc && $kyc['status'] === 'rejected') {
                    $msg = "ข้อมูลยืนยันตัวตนของคุณไม่ผ่านการอนุมัติ กรุณาอัปโหลดเอกสารใหม่";
                }
                echo json_encode(["status" => "error", "message" => $msg]);
                exit;
            }

            // 3. รับข้อมูลจาก Form Data
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $start_price = $_POST['start_price'] ?? 0;
            $min_step = $_POST['min_step'] ?? 0;
            $end_time = $_POST['end_time'] ?? '';

            if (!$title || !$start_price || !$min_step || !$end_time || !isset($_FILES['product_img'])) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "กรุณากรอกข้อมูลและอัปโหลดรูปภาพให้ครบถ้วน"]);
                exit;
            }

            // 4. จัดการอัปโหลดไฟล์ภาพ
            $upload_dir = '../public/uploads/products/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $img_ext = strtolower(pathinfo($_FILES['product_img']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png'];

            if (!in_array($img_ext, $allowed_ext)) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "รองรับเฉพาะไฟล์ JPG และ PNG เท่านั้น"]);
                exit;
            }

            $img_filename = 'product_' . $user_id . '_' . time() . '.' . $img_ext;

            if (!move_uploaded_file($_FILES['product_img']['tmp_name'], $upload_dir . $img_filename)) {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "ไม่สามารถบันทึกไฟล์รูปภาพได้"]);
                exit;
            }

            // 5. บันทึกลงฐานข้อมูล
            $query = "INSERT INTO products (seller_id, title, description, image_filename, start_price, min_step, current_price, end_time, status) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')";

            $stmt = $db->prepare($query);
            $stmt->execute([
                $user_id,
                $title,
                $description,
                $img_filename,
                $start_price,
                $min_step,
                $start_price, // ราคาปัจจุบันจะเท่ากับราคาเริ่มต้นตอนเปิดประมูล
                $end_time
            ]);

            http_response_code(201); // Created
            echo json_encode(["status" => "success", "message" => "ลงผลงานประมูลเรียบร้อยแล้ว"]);

        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Token ไม่ถูกต้อง หรือหมดอายุ: " . $e->getMessage()]);
        }
        break;
    // ----------------------------------------------------
    // 6. ดึงข้อมูล Dashboard ของผู้ขาย (Seller)
    // ----------------------------------------------------
    case 'v1/seller/dashboard':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET')
            break;

        // 1. ตรวจสอบ JWT Token
        $headers = apache_request_headers();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

        if (!$token) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "ไม่ได้เข้าสู่ระบบ"]);
            exit;
        }

        try {
            $decoded = JWT::decode($token, new \Firebase\JWT\Key($secret_key, 'HS256'));
            $user_id = $decoded->data->id;
            $role = $decoded->data->role;

            if ($role !== 'seller') {
                http_response_code(403);
                echo json_encode(["status" => "error", "message" => "ไม่มีสิทธิ์เข้าถึง"]);
                exit;
            }

            // 2. ดึงข้อมูลสถิติ (Stat Cards)
            // ยอดขายรวม (สมมติว่าสถานะ sold คือขายได้แล้ว)
            $stmtSales = $db->prepare("SELECT COALESCE(SUM(current_price), 0) as total_sales FROM products WHERE seller_id = ? AND status = 'sold'");
            $stmtSales->execute([$user_id]);
            $totalSales = $stmtSales->fetch(PDO::FETCH_ASSOC)['total_sales'];

            // จำนวนที่กำลังเปิดประมูล
            $stmtActive = $db->prepare("SELECT COUNT(*) as active_count FROM products WHERE seller_id = ? AND status = 'active'");
            $stmtActive->execute([$user_id]);
            $activeCount = $stmtActive->fetch(PDO::FETCH_ASSOC)['active_count'];

            // จำนวนที่รอจัดส่ง (สถานะ sold)
            $stmtPending = $db->prepare("SELECT COUNT(*) as pending_count FROM products WHERE seller_id = ? AND status = 'sold'");
            $stmtPending->execute([$user_id]);
            $pendingCount = $stmtPending->fetch(PDO::FETCH_ASSOC)['pending_count'];

            // 3. ดึงรายการผลงานทั้งหมดของผู้ขายคนนี้ พร้อมนับจำนวนคนบิดราคา
            $queryProducts = "
                SELECT p.id, p.title, p.current_price, p.end_time, p.status, 
                       (SELECT COUNT(*) FROM bids b WHERE b.product_id = p.id) as bids_count
                FROM products p 
                WHERE p.seller_id = ? 
                ORDER BY p.created_at DESC
            ";
            $stmtProducts = $db->prepare($queryProducts);
            $stmtProducts->execute([$user_id]);
            $products = $stmtProducts->fetchAll(PDO::FETCH_ASSOC);

            // ส่งข้อมูลกลับไปที่หน้าบ้าน
            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "stats" => [
                    "total_sales" => $totalSales,
                    "active_count" => $activeCount,
                    "pending_count" => $pendingCount
                ],
                "products" => $products
            ]);

        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Token ไม่ถูกต้อง หรือหมดอายุ"]);
        }
        break;

    // ----------------------------------------------------
    // 7. ระบบอัปเดตโปรไฟล์ (เปลี่ยนชื่อ / รูป Avatar)
    // ----------------------------------------------------
    case 'v1/user/profile/update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            break;

        $headers = apache_request_headers();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';
        if (!$token) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "ไม่ได้เข้าสู่ระบบ"]);
            exit;
        }

        try {
            $decoded = JWT::decode($token, new \Firebase\JWT\Key($secret_key, 'HS256'));
            $user_id = $decoded->data->id;

            $display_name = $_POST['display_name'] ?? '';
            $address = $_POST['address'] ?? ''; // รับค่าที่อยู่

            if (!$display_name) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "กรุณากรอกชื่อที่ต้องการแสดง"]);
                exit;
            }

            // 💡 ตั้งค่าคำสั่ง SQL เริ่มต้น (อัปเดตแค่ชื่อและที่อยู่)
            $update_query = "UPDATE users SET display_name = ?, address = ? WHERE id = ?";
            $params = [$display_name, $address, $user_id];

            // 💡 ถ้ามีการแนบไฟล์รูปมาด้วย ค่อยบวกคำสั่งอัปเดตรูปเพิ่มเข้าไป
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../public/uploads/avatars/';
                if (!is_dir($upload_dir))
                    mkdir($upload_dir, 0777, true);

                $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
                    http_response_code(400);
                    echo json_encode(["status" => "error", "message" => "รองรับเฉพาะไฟล์ JPG และ PNG เท่านั้น"]);
                    exit;
                }

                $avatar_filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_dir . $avatar_filename);

                // เปลี่ยน Query ให้มีการอัปเดต avatar ด้วย
                $update_query = "UPDATE users SET display_name = ?, address = ?, avatar = ? WHERE id = ?";
                $params = [$display_name, $address, $avatar_filename, $user_id];
            }

            // รันคำสั่ง SQL ครั้งเดียวจบ
            $stmt = $db->prepare($update_query);
            $stmt->execute($params);

            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "อัปเดตโปรไฟล์เรียบร้อยแล้ว"]);

        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Token ไม่ถูกต้อง หรือหมดอายุ"]);
        }
        break;
    // ----------------------------------------------------
    // 8. ดึงข้อมูลโปรไฟล์ผู้ใช้งาน (ชื่อ, รูปภาพ)
    // ----------------------------------------------------
    case 'v1/user/profile':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET')
            break;

        $headers = apache_request_headers();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';
        if (!$token) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "ไม่ได้เข้าสู่ระบบ"]);
            exit;
        }

        try {
            $decoded = JWT::decode($token, new \Firebase\JWT\Key($secret_key, 'HS256'));
            $user_id = $decoded->data->id;

            $stmt = $db->prepare("SELECT id, username, display_name, email, phone, avatar, role, address FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $display_name = !empty($user['display_name']) ? $user['display_name'] : $decoded->data->username;
                $avatar_url = '';
                if (!empty($user['avatar']) && $user['avatar'] !== 'default_avatar.png') {
                    $avatar_url = '/auction_of_paintings/public/uploads/avatars/' . $user['avatar'];
                }

                http_response_code(200);
                echo json_encode([
                    "status" => "success",
                    "data" => [
                        "username" => $user['username'],
                        "display_name" => $display_name,
                        "address" => $user['address'], // 💡 ส่ง address กลับไปให้หน้าบ้านด้วย
                        "avatar_url" => $avatar_url
                    ]
                ]);
            } else {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "ไม่พบข้อมูลผู้ใช้งาน"]);
            }
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Token ไม่ถูกต้อง หรือหมดอายุ"]);
        }
        break;
    // ----------------------------------------------------
    // 9. ดึงข้อมูลสินค้าที่กำลังเปิดประมูล (หน้าแรก)
    // ----------------------------------------------------
    case 'v1/auctions/active':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET')
            break;

        try {
            // ดึงข้อมูลสินค้าเฉพาะสถานะ 'active' และดึงชื่อผู้ขายมาด้วย
            $query = "
                SELECT p.id, p.title, p.current_price, p.end_time, p.image_filename, 
                       COALESCE(u.display_name, u.username) as artist 
                FROM products p 
                JOIN users u ON p.seller_id = u.id 
                WHERE p.status = 'active' 
                ORDER BY p.end_time ASC
            ";
            $stmt = $db->query($query);
            $auctions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $auctions]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "ไม่สามารถดึงข้อมูลได้"]);
        }
        break;
    // ----------------------------------------------------
    // 10. ดึงข้อมูลโปรไฟล์ศิลปินและผลงานทั้งหมด (Public)
    // ----------------------------------------------------
    case 'v1/artist/profile':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET')
            break;

        $artist_id = $_GET['id'] ?? null;

        if (!$artist_id) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "กรุณาระบุรหัสศิลปิน"]);
            exit;
        }

        try {
            // 1. ดึงข้อมูลโปรไฟล์ศิลปิน
            $stmtUser = $db->prepare("SELECT id, username, display_name, avatar, created_at FROM users WHERE id = ? AND role = 'seller' LIMIT 1");
            $stmtUser->execute([$artist_id]);
            $artist = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if (!$artist) {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "ไม่พบข้อมูลศิลปินท่านนี้"]);
                exit;
            }

            // จัดการชื่อและรูปโปรไฟล์
            $artist['display_name'] = !empty($artist['display_name']) ? $artist['display_name'] : $artist['username'];
            $artist['avatar_url'] = (!empty($artist['avatar']) && $artist['avatar'] !== 'default_avatar.png')
                ? '/auction_of_paintings/public/uploads/avatars/' . $artist['avatar']
                : 'https://ui-avatars.com/api/?name=' . urlencode($artist['display_name']) . '&background=random';

            // 2. ดึงสถิติของศิลปิน
            $stmtStats = $db->prepare("
                SELECT 
                    COUNT(*) as total_artworks,
                    SUM(CASE WHEN status = 'sold' THEN 1 ELSE 0 END) as sold_artworks
                FROM products 
                WHERE seller_id = ?
            ");
            $stmtStats->execute([$artist_id]);
            $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

            // 3. ดึงผลงานทั้งหมด (แบ่งกลุ่ม active และ past)
            $stmtProducts = $db->prepare("
                SELECT id, title, current_price, end_time, image_filename, status 
                FROM products 
                WHERE seller_id = ? 
                ORDER BY created_at DESC
            ");
            $stmtProducts->execute([$artist_id]);
            $products = $stmtProducts->fetchAll(PDO::FETCH_ASSOC);

            $active_auctions = [];
            $past_auctions = [];

            foreach ($products as $product) {
                $product['image_url'] = $product['image_filename']
                    ? '/auction_of_paintings/public/uploads/products/' . $product['image_filename']
                    : 'https://via.placeholder.com/500?text=No+Image';

                if ($product['status'] === 'active') {
                    $active_auctions[] = $product;
                } else {
                    $past_auctions[] = $product;
                }
            }

            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "artist" => $artist,
                "stats" => $stats,
                "active_auctions" => $active_auctions,
                "past_auctions" => $past_auctions
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "เกิดข้อผิดพลาดในการโหลดข้อมูล"]);
        }
        break;
    // ----------------------------------------------------
    // 11. ดึงข้อมูลประวัติการประมูลของผู้ซื้อ (My Bids)
    // ----------------------------------------------------
    case 'v1/buyer/bids':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET')
            break;

        // 1. ตรวจสอบ JWT Token
        $headers = apache_request_headers();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

        if (!$token) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "ไม่ได้เข้าสู่ระบบ"]);
            exit;
        }

        try {
            $decoded = JWT::decode($token, new \Firebase\JWT\Key($secret_key, 'HS256'));
            $user_id = $decoded->data->id;

            // 2. ดึงรายการสินค้าที่ User คนนี้เคยเข้าไปบิดราคา
            // ใช้ Subquery เพื่อหาว่าใครคือคนที่ให้ราคาสูงสุด ณ ปัจจุบัน
            $query = "
                SELECT p.id, p.title, p.current_price, p.end_time, p.status, p.image_filename, p.winner_id,
                       MAX(b.amount) as my_max_bid,
                       (SELECT user_id FROM bids WHERE product_id = p.id ORDER BY amount DESC, created_at ASC LIMIT 1) as highest_bidder_id
                FROM products p
                JOIN bids b ON p.id = b.product_id
                WHERE b.user_id = ?
                GROUP BY p.id
                ORDER BY p.end_time ASC
            ";

            $stmt = $db->prepare($query);
            $stmt->execute([$user_id]);
            $bids = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $active_bids = [];
            $past_bids = [];

            // 3. จัดกลุ่มข้อมูลและคำนวณสถานะการประมูล (กำลังชนะ / โดนปาดหน้า / ชนะแล้ว / แพ้)
            foreach ($bids as $bid) {
                $bid['image_url'] = $bid['image_filename']
                    ? '/auction_of_paintings/public/uploads/products/' . $bid['image_filename']
                    : 'https://via.placeholder.com/150';

                if ($bid['status'] === 'active') {
                    // ถ้ายังประมูลอยู่: เช็คว่าคนบิดสูงสุดใช่เราไหม?
                    $bid['bid_status'] = ($bid['highest_bidder_id'] == $user_id) ? 'winning' : 'outbid';
                    $active_bids[] = $bid;
                } else {
                    // ถ้าปิดประมูลแล้ว: เช็คว่าเราคือผู้ชนะ (winner_id) ไหม?
                    $bid['bid_status'] = ($bid['winner_id'] == $user_id) ? 'won' : 'lost';
                    $past_bids[] = $bid;
                }
            }

            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "data" => [
                    "active" => $active_bids,
                    "past" => $past_bids
                ]
            ]);

        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Token ไม่ถูกต้อง"]);
        }
        break;
    // ----------------------------------------------------
    // 12. ดึงรายละเอียดสินค้า 1 รายการ และประวัติการบิดราคา (Public)
    // ----------------------------------------------------
    case 'v1/auctions/detail':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET')
            break;

        $product_id = $_GET['id'] ?? null;

        if (!$product_id) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "กรุณาระบุรหัสสินค้า"]);
            exit;
        }

        try {
            // 1. ดึงข้อมูลสินค้า + ข้อมูลผู้ขาย
            $stmtProduct = $db->prepare("
                SELECT p.*, 
                       u.display_name as seller_name, u.avatar as seller_avatar 
                FROM products p 
                JOIN users u ON p.seller_id = u.id 
                WHERE p.id = ? LIMIT 1
            ");
            $stmtProduct->execute([$product_id]);
            $product = $stmtProduct->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "ไม่พบข้อมูลสินค้า"]);
                exit;
            }

            // จัดการ URL รูปภาพสินค้าและผู้ขาย
            $product['image_url'] = $product['image_filename'] ? '/auction_of_paintings/public/uploads/products/' . $product['image_filename'] : '';
            $product['seller_avatar_url'] = ($product['seller_avatar'] && $product['seller_avatar'] !== 'default_avatar.png')
                ? '/auction_of_paintings/public/uploads/avatars/' . $product['seller_avatar']
                : 'https://ui-avatars.com/api/?name=' . urlencode($product['seller_name']) . '&background=random';

            // 2. ดึงประวัติการบิดราคา (Bid History)
            $stmtBids = $db->prepare("
                SELECT b.amount, b.created_at, 
                       COALESCE(u.display_name, u.username) as bidder_name, u.avatar as bidder_avatar 
                FROM bids b 
                JOIN users u ON b.user_id = u.id 
                WHERE b.product_id = ? 
                ORDER BY b.amount DESC 
                LIMIT 10
            ");
            $stmtBids->execute([$product_id]);
            $bid_history = $stmtBids->fetchAll(PDO::FETCH_ASSOC);

            // แปลง Avatar ของคนบิดราคา
            foreach ($bid_history as &$bid) {
                $bid['bidder_avatar_url'] = ($bid['bidder_avatar'] && $bid['bidder_avatar'] !== 'default_avatar.png')
                    ? '/auction_of_paintings/public/uploads/avatars/' . $bid['bidder_avatar']
                    : 'https://ui-avatars.com/api/?name=' . urlencode($bid['bidder_name']) . '&background=random';
            }

            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "product" => $product,
                "bid_history" => $bid_history
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "เกิดข้อผิดพลาดของระบบ"]);
        }
        break;
    // ----------------------------------------------------
    // 13. ระบบจัดการของผู้ดูแลระบบ (Admin - KYC Management)
    // ----------------------------------------------------
    case 'v1/admin/kyc/pending':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET')
            break;

        // 1. ตรวจสอบสิทธิ์
        $headers = apache_request_headers();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';
        if (!$token) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "ไม่ได้เข้าสู่ระบบ"]);
            exit;
        }

        try {
            // เช็ค Token
            $decoded = JWT::decode($token, new \Firebase\JWT\Key($secret_key, 'HS256'));
            if ($decoded->data->role !== 'admin') {
                http_response_code(403);
                echo json_encode(["status" => "error", "message" => "ไม่อนุญาตให้เข้าถึง สงวนสิทธิ์เฉพาะผู้ดูแลระบบ"]);
                exit;
            }
        } catch (Exception $e) {
            // ถ้า Token พัง ให้จบการทำงานตรงนี้
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Token ไม่ถูกต้อง หรือหมดอายุ"]);
            exit;
        }

        // 2. โซนดึงข้อมูล Database (แยก Try-Catch ออกมาเพื่อให้เห็น Error จริง)
        try {
            // 💡 เพิ่ม u.phone เข้าไปใน SELECT
            $stmt = $db->query("
                SELECT k.*, u.username, u.email, u.phone 
                FROM kyc_verifications k 
                JOIN users u ON k.user_id = u.id 
                WHERE k.status = 'pending' 
                ORDER BY k.updated_at ASC
            ");
            $kycs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 🔐 3. ถอดรหัส (Decrypt) ข้อมูลสำคัญให้ Admin อ่านได้
            $encrypt_method = "AES-256-CBC";
            $encrypt_key = hash('sha256', 'ArtBids_Secret_Key_2024!');
            $encrypt_iv = substr(hash('sha256', 'ArtBids_Secret_IV_2024!'), 0, 16);

            foreach ($kycs as &$k) {
                // พยายามถอดรหัส
                $decrypted_id = openssl_decrypt($k['id_card_no'], $encrypt_method, $encrypt_key, 0, $encrypt_iv);
                $decrypted_bank = openssl_decrypt($k['bank_acc_no'], $encrypt_method, $encrypt_key, 0, $encrypt_iv);

                // 💡 แก้ไข: ถ้าถอดรหัสได้ (ไม่เป็น false) ให้แสดงข้อมูลจริง ถ้าถอดไม่ได้ให้คืนค่าเดิมให้แอดมินเห็น
                $k['id_card_no'] = ($decrypted_id !== false) ? $decrypted_id : $k['id_card_no'];
                $k['bank_acc_no'] = ($decrypted_bank !== false) ? $decrypted_bank : $k['bank_acc_no'];

                // จัดเตรียม URL รูปภาพ
                $k['id_card_url'] = '/auction_of_paintings/public/uploads/kyc/' . $k['id_card_img'];
                $k['selfie_url'] = '/auction_of_paintings/public/uploads/kyc/' . $k['selfie_img'];
            }

            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $kycs]);

        } catch (PDOException $e) {
            // ถ้า Database พัง จะแสดง Error ที่แท้จริงออกมา
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Database Error: " . $e->getMessage()]);
            exit;
        }
        break;

    case 'v1/admin/kyc/action':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            break;

        $headers = apache_request_headers();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

        try {
            $decoded = JWT::decode($token, new \Firebase\JWT\Key($secret_key, 'HS256'));
            if ($decoded->data->role !== 'admin') {
                http_response_code(403);
                echo json_encode(["message" => "ไม่อนุญาต"]);
                exit;
            }

            $data = json_decode(file_get_contents("php://input"), true);
            $kyc_id = $data['kyc_id'] ?? null; // คือ user_id
            $action = $data['action'] ?? '';
            $note = $data['note'] ?? '';

            if (!$kyc_id || !in_array($action, ['approve', 'reject'])) {
                http_response_code(400);
                echo json_encode(["message" => "ข้อมูลไม่ครบถ้วน"]);
                exit;
            }

            if ($action === 'approve') {
                // ✅ กรณีอนุมัติ: อัปเดตสถานะ (เก็บรูปไว้เป็นหลักฐาน)
                $stmt = $db->prepare("UPDATE kyc_verifications SET status = 'verified', admin_note = NULL WHERE user_id = ?");
                $stmt->execute([$kyc_id]);
                $msg = "อนุมัติ KYC เรียบร้อยแล้ว";
            } else {
                // ❌ กรณีปฏิเสธ: ลบไฟล์รูปภาพออกจากเซิร์ฟเวอร์ก่อน แล้วค่อยอัปเดต DB

                // 1. ดึงชื่อไฟล์จากฐานข้อมูลก่อนลบ record/update
                $stmtFile = $db->prepare("SELECT id_card_img, selfie_img FROM kyc_verifications WHERE user_id = ?");
                $stmtFile->execute([$kyc_id]);
                $files = $stmtFile->fetch(PDO::FETCH_ASSOC);

                if ($files) {
                    $upload_dir = '../public/uploads/kyc/';

                    // ลบรูปบัตรประชาชน
                    if (!empty($files['id_card_img']) && file_exists($upload_dir . $files['id_card_img'])) {
                        unlink($upload_dir . $files['id_card_img']);
                    }

                    // ลบรูปเซลฟี่
                    if (!empty($files['selfie_img']) && file_exists($upload_dir . $files['selfie_img'])) {
                        unlink($upload_dir . $files['selfie_img']);
                    }
                }

                // 2. อัปเดตสถานะเป็น rejected เพื่อให้ผู้ใช้ส่งใหม่ได้
                $stmt = $db->prepare("UPDATE kyc_verifications SET status = 'rejected', admin_note = ?, id_card_img = NULL, selfie_img = NULL WHERE user_id = ?");
                $stmt->execute([$note, $kyc_id]);
                $msg = "ปฏิเสธ KYC และลบไฟล์เอกสารที่ไม่ถูกต้องเรียบร้อยแล้ว";
            }

            http_response_code(200);
            echo json_encode(["status" => "success", "message" => $msg]);

        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(["message" => "Token ไม่ถูกต้อง: " . $e->getMessage()]);
        }
        break;

    // ----------------------------------------------------
    // 14. เสนอราคาประมูล (AJAX)
    // ----------------------------------------------------
    case 'v1/auctions/bid':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            break;

        $headers = apache_request_headers();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';
        if (!$token) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "ไม่ได้เข้าสู่ระบบ"]);
            exit;
        }

        try {
            $decoded = JWT::decode($token, new \Firebase\JWT\Key($secret_key, 'HS256'));
            $userId = $decoded->data->id;

            $data = json_decode(file_get_contents("php://input"), true);
            $productId = $data['product_id'] ?? null;
            $bidAmount = floatval($data['amount'] ?? 0);

            if (!$productId || $bidAmount <= 0) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "ข้อมูลไม่ครบถ้วน"]);
                exit;
            }

            // 💡 เพิ่มการดึง seller_id มาตรวจสอบด้วย
            $stmt = $db->prepare("SELECT seller_id, current_price, min_step, end_time, status FROM products WHERE id = ? FOR UPDATE");
            $db->beginTransaction();
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product || $product['status'] !== 'active' || strtotime($product['end_time']) < time()) {
                $db->rollBack();
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "การประมูลจบลงแล้วหรือไม่พบสินค้า"]);
                exit;
            }

            // 🛑 เช็ค: ถ้าคนบิดคือเจ้าของผลงาน ให้ตีตกทันที
            if ($product['seller_id'] == $userId) {
                $db->rollBack();
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "คุณไม่สามารถเสนอราคาในผลงานของตัวเองได้"]);
                exit;
            }

            $minRequired = $product['current_price'] + $product['min_step'];
            if ($bidAmount < $minRequired) {
                $db->rollBack();
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "ราคาบิดต้องมากกว่า " . number_format($minRequired) . " บาท"]);
                exit;
            }

            // บันทึกราคาใหม่
            $updateProduct = $db->prepare("UPDATE products SET current_price = ?, winner_id = ? WHERE id = ?");
            $updateProduct->execute([$bidAmount, $userId, $productId]);

            $insertBid = $db->prepare("INSERT INTO bids (product_id, user_id, amount) VALUES (?, ?, ?)");
            $insertBid->execute([$productId, $userId, $bidAmount]);

            $db->commit();

            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "เสนอราคาสำเร็จ"]);

        } catch (Exception $e) {
            if ($db->inTransaction())
                $db->rollBack();
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Token ไม่ถูกต้อง หรือระบบขัดข้อง"]);
        }
        break;

    // ----------------------------------------------------
    // 15. ดึงราคาอัปเดตล่าสุด (AJAX Polling)
    // ----------------------------------------------------
    case 'v1/auctions/poll':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET')
            break;

        $productId = $_GET['id'] ?? null;
        if (!$productId) {
            http_response_code(400);
            exit;
        }

        try {
            // ดึงราคาสินค้าปัจจุบัน
            $stmt = $db->prepare("SELECT current_price, status FROM products WHERE id = ? LIMIT 1");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            // ดึงประวัติการประมูลล่าสุด
            $stmtBids = $db->prepare("
                SELECT b.amount, b.created_at, 
                       COALESCE(u.display_name, u.username) as bidder_name, u.avatar as bidder_avatar 
                FROM bids b JOIN users u ON b.user_id = u.id 
                WHERE b.product_id = ? ORDER BY b.amount DESC LIMIT 10
            ");
            $stmtBids->execute([$productId]);
            $bid_history = $stmtBids->fetchAll(PDO::FETCH_ASSOC);

            foreach ($bid_history as &$bid) {
                $bid['bidder_avatar_url'] = ($bid['bidder_avatar'] && $bid['bidder_avatar'] !== 'default_avatar.png')
                    ? '/auction_of_paintings/public/uploads/avatars/' . $bid['bidder_avatar']
                    : 'https://ui-avatars.com/api/?name=' . urlencode($bid['bidder_name']) . '&background=random';
            }

            http_response_code(200);
            echo json_encode(["status" => "success", "current_price" => $product['current_price'], "product_status" => $product['status'], "bid_history" => $bid_history]);
        } catch (Exception $e) {
            http_response_code(500);
        }
        break;

    // ----------------------------------------------------
    // 16. ดึงข้อมูลผลงานของผู้ขาย (Seller Dashboard)
    // ----------------------------------------------------
    case 'v1/seller/auctions':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET')
            break;

        $headers = apache_request_headers();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';
        if (!$token) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "ไม่ได้เข้าสู่ระบบ"]);
            exit;
        }

        try {
            $decoded = JWT::decode($token, new \Firebase\JWT\Key($secret_key, 'HS256'));
            $userId = $decoded->data->id;

            // 💡 ดึงข้อมูลสินค้า + จำนวนคนบิด + ข้อมูลผู้ชนะ (Join กับตาราง users)
            $stmt = $db->prepare("
                SELECT p.id, p.title, p.current_price, p.end_time, p.status, p.image_filename,
                       (SELECT COUNT(*) FROM bids WHERE product_id = p.id) as bid_count,
                       w.id as winner_id, COALESCE(w.display_name, w.username) as winner_name, w.phone as winner_phone, w.email as winner_email,
                       t.id as transaction_id, t.payment_status, t.seller_net_amount, 
                       COALESCE(t.shipping_address, w.address) as shipping_address, 
                       t.tracking_number, t.shipping_status
                FROM products p
                LEFT JOIN users w ON p.winner_id = w.id
                LEFT JOIN transactions t ON p.id = t.product_id
                WHERE p.seller_id = ?
                ORDER BY p.created_at DESC
            ");
            $stmt->execute([$userId]);
            $auctions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $now = time();
            foreach ($auctions as &$item) {
                $endTime = strtotime($item['end_time']);

                // 💡 Lazy Check: คำนวณสถานะจริง
                if ($item['status'] === 'active' && $endTime < $now) {
                    $item['real_status'] = 'ended'; // หมดเวลาแล้ว
                } else {
                    $item['real_status'] = $item['status']; // ยังประมูลอยู่
                }

                $item['image_url'] = $item['image_filename'] ? '/auction_of_paintings/public/uploads/products/' . $item['image_filename'] : 'https://via.placeholder.com/150';
            }

            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $auctions]);

        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Token ไม่ถูกต้อง"]);
        }
        break;
    // ----------------------------------------------------
    // 17. ดึงรายชื่อศิลปินทั้งหมด (ทำเนียบศิลปิน)
    // ----------------------------------------------------
    case 'v1/artists/list':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET')
            break;

        try {
            // 💡 แก้ไข: นับ sold_arts จากผลงานที่ status = 'sold' เท่านั้น (จบประมูลและมีคนชนะจริงๆ)
            $stmt = $db->query("
                SELECT u.id, u.display_name, u.avatar, 
                       (SELECT COUNT(*) FROM products WHERE seller_id = u.id) as total_arts,
                       (SELECT COUNT(*) FROM products WHERE seller_id = u.id AND status = 'sold') as sold_arts
                FROM users u
                WHERE u.role = 'seller'
                ORDER BY total_arts DESC
            ");
            $artists = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($artists as &$a) {
                $a['avatar_url'] = ($a['avatar'] && $a['avatar'] !== 'default_avatar.png')
                    ? '/auction_of_paintings/public/uploads/avatars/' . $a['avatar']
                    : 'https://ui-avatars.com/api/?name=' . urlencode($a['display_name']) . '&background=random';
            }

            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $artists]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Database Error"]);
        }
        break;
    // ----------------------------------------------------
    // 18. รายการที่ฉันกำลังสู้ราคา (My Active Bids)
    // ----------------------------------------------------
    case 'v1/buyer/my-bids':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET')
            break;

        $headers = apache_request_headers();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';
        if (!$token) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "ไม่ได้เข้าสู่ระบบ"]);
            exit;
        }

        try {
            $decoded = JWT::decode($token, new \Firebase\JWT\Key($secret_key, 'HS256'));
            $userId = $decoded->data->id;

            // 💡 ดึงเฉพาะสินค้าที่ status = active และ ผู้ใช้คนนี้เคยมีประวัติอยู่ในตาราง bids
            $stmt = $db->prepare("
                SELECT p.id, p.title, p.current_price, p.end_time, p.status, p.image_filename, 
                       COALESCE(u.display_name, u.username) as artist, p.seller_id
                FROM products p
                JOIN users u ON p.seller_id = u.id
                WHERE p.status = 'active' 
                AND p.id IN (SELECT DISTINCT product_id FROM bids WHERE user_id = ?)
                ORDER BY p.end_time ASC
            ");
            $stmt->execute([$userId]);
            $auctions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 🚀 Lazy Check: คัดเอาเฉพาะอันที่เวลายังไม่หมดจริงๆ
            $active_auctions = [];
            $now = time();
            foreach ($auctions as $item) {
                if (strtotime($item['end_time']) > $now) {
                    $active_auctions[] = $item;
                }
            }

            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $active_auctions]);

        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Token ไม่ถูกต้อง"]);
        }
        break;

    // ----------------------------------------------------
    // 19. ระบบตัดจบประมูลอัตโนมัติ (Cron Job / Auto Trigger)
    // ----------------------------------------------------
    case 'v1/system/cron-close-auctions':
        // API นี้สร้างไว้ให้ระบบหลังบ้าน (หรือเรากดรันเอง) เพื่อเช็คของที่หมดเวลา
        try {
            // หาผลงานที่เวลาหมดแล้ว แต่สถานะยังเป็น active
            $stmt = $db->query("SELECT id, seller_id FROM products WHERE status = 'active' AND end_time <= NOW()");
            $expired_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $closed_count = 0;

            foreach ($expired_products as $p) {
                // หาคนบิดสูงสุด
                $bidStmt = $db->prepare("SELECT user_id, amount FROM bids WHERE product_id = ? ORDER BY amount DESC LIMIT 1");
                $bidStmt->execute([$p['id']]);
                $highestBid = $bidStmt->fetch(PDO::FETCH_ASSOC);

                $db->beginTransaction();

                if ($highestBid) {
                    // 🎉 มีคนชนะ
                    $final_price = $highestBid['amount'];
                    $buyer_id = $highestBid['user_id'];
                    $seller_id = $p['seller_id'];

                    // คำนวณเงิน (หัก 17%)
                    $commission_rate = 17.00;
                    $commission_amount = $final_price * ($commission_rate / 100);
                    $seller_net = $final_price - $commission_amount;

                    // 1. อัปเดตสถานะสินค้า
                    $updateProd = $db->prepare("UPDATE products SET status = 'sold', winner_id = ?, current_price = ? WHERE id = ?");
                    $updateProd->execute([$buyer_id, $final_price, $p['id']]);

                    // 2. สร้างบิลแจ้งหนี้ลงตาราง transactions
                    $insertTx = $db->prepare("
                        INSERT INTO transactions (product_id, buyer_id, seller_id, final_price, commission_rate, commission_amount, seller_net_amount, payment_status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
                    ");
                    $insertTx->execute([$p['id'], $buyer_id, $seller_id, $final_price, $commission_rate, $commission_amount, $seller_net]);

                } else {
                    // ⚪ ไม่มีคนบิด จบประมูลแบบเหงาๆ
                    $updateProd = $db->prepare("UPDATE products SET status = 'ended' WHERE id = ?");
                    $updateProd->execute([$p['id']]);
                }

                $db->commit();
                $closed_count++;
            }

            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "ประมวลผลสำเร็จ ปิดประมูลไป $closed_count รายการ"]);
        } catch (Exception $e) {
            if ($db->inTransaction())
                $db->rollBack();
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Database Error: " . $e->getMessage()]);
        }
        break;

    // ----------------------------------------------------
    // 20. ดึงรายการที่ฉันชนะประมูล (ฝั่งผู้ซื้อ)
    // ----------------------------------------------------
    case 'v1/buyer/wins':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET')
            break;
        $headers = apache_request_headers();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';
        if (!$token) {
            http_response_code(401);
            exit;
        }

        try {
            $decoded = JWT::decode($token, new \Firebase\JWT\Key($secret_key, 'HS256'));
            $userId = $decoded->data->id;

            $stmt = $db->prepare("
                SELECT t.*, p.title, p.image_filename 
                FROM transactions t 
                JOIN products p ON t.product_id = p.id 
                WHERE t.buyer_id = ? 
                ORDER BY t.created_at DESC
            ");
            $stmt->execute([$userId]);
            $wins = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($wins as &$w) {
                $w['image_url'] = $w['image_filename'] ? '/auction_of_paintings/public/uploads/products/' . $w['image_filename'] : 'https://via.placeholder.com/150';
            }

            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $wins]);
        } catch (Exception $e) {
            http_response_code(401);
        }
        break;

    // ----------------------------------------------------
    // 21. อัปโหลดสลิปโอนเงิน (ฝั่งผู้ซื้อ)
    // ----------------------------------------------------
    case 'v1/buyer/upload_slip':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            break;
        $headers = apache_request_headers();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';
        if (!$token) {
            http_response_code(401);
            exit;
        }

        try {
            $decoded = JWT::decode($token, new \Firebase\JWT\Key($secret_key, 'HS256'));
            $userId = $decoded->data->id;

            $transaction_id = $_POST['transaction_id'] ?? null;
            if (!$transaction_id || !isset($_FILES['slip_image'])) {
                http_response_code(400);
                echo json_encode(["message" => "ข้อมูลไม่ครบถ้วน"]);
                exit;
            }

            // 💡 เช็คว่าผู้ซื้อกรอกที่อยู่ในโปรไฟล์หรือยัง?
            $stmtUser = $db->prepare("SELECT address FROM users WHERE id = ?");
            $stmtUser->execute([$userId]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if (empty($user['address'])) {
                http_response_code(400);
                echo json_encode(["message" => "กรุณาอัปเดต 'ที่อยู่จัดส่ง' ในหน้าโปรไฟล์ของคุณก่อนทำการแจ้งชำระเงิน"]);
                exit;
            }

            // จัดการอัปโหลดไฟล์
            $upload_dir = '../public/uploads/slips/';
            if (!is_dir($upload_dir))
                mkdir($upload_dir, 0777, true);

            $ext = strtolower(pathinfo($_FILES['slip_image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
                http_response_code(400);
                echo json_encode(["message" => "รับเฉพาะไฟล์รูปภาพ (JPG, PNG)"]);
                exit;
            }

            $filename = 'slip_' . $transaction_id . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['slip_image']['tmp_name'], $upload_dir . $filename);

            // อัปเดตตาราง transactions พร้อมบันทึกที่อยู่จัดส่ง (Snapshot ณ วันที่ซื้อ)
            $stmt = $db->prepare("UPDATE transactions SET payment_slip = ?, payment_status = 'paid_to_admin', shipping_address = ? WHERE id = ? AND buyer_id = ?");
            $stmt->execute([$filename, $user['address'], $transaction_id, $userId]);

            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "อัปโหลดสลิปสำเร็จ รอแอดมินตรวจสอบ"]);
        } catch (Exception $e) {
            http_response_code(401);
        }
        break;
    // ----------------------------------------------------
    // 22. ดึงข้อมูลรายการการเงินทั้งหมด (Admin Finance)
    // ----------------------------------------------------
    case 'v1/admin/finance/list':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET')
            break;

        // 🔐 เช็คสิทธิ์ JWT Admin
        $headers = apache_request_headers();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';
        if (!$token) {
            http_response_code(401);
            echo json_encode(["message" => "ไม่ได้เข้าสู่ระบบ"]);
            exit;
        }

        try {
            $decoded = JWT::decode($token, new \Firebase\JWT\Key($secret_key, 'HS256'));
            if ($decoded->data->role !== 'admin') {
                http_response_code(403);
                echo json_encode(["message" => "ไม่มีสิทธิ์เข้าถึง"]);
                exit;
            }
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(["message" => "Token ไม่ถูกต้อง"]);
            exit;
        }

        try {
            $stmt = $db->query("
                SELECT t.*, p.title, p.image_filename,
                       b.username as buyer_name, b.email as buyer_email,
                       s.username as seller_name, s.email as seller_email,
                       k.bank_name as seller_bank_name, k.bank_acc_no as seller_bank_acc
                FROM transactions t
                JOIN products p ON t.product_id = p.id
                JOIN users b ON t.buyer_id = b.id
                JOIN users s ON t.seller_id = s.id
                LEFT JOIN kyc_verifications k ON s.id = k.user_id
                ORDER BY t.created_at DESC
            ");
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 💡 แก้ไขกุญแจให้ตรงกับชุดที่ถอดรหัสผ่านใน KYC เป๊ะๆ
            $encrypt_method = "AES-256-CBC";
            $encrypt_key = hash('sha256', 'ArtBids_Secret_Key_2024!');
            $encrypt_iv = substr(hash('sha256', 'ArtBids_Secret_IV_2024!'), 0, 16);

            foreach ($transactions as &$tx) {
                $tx['product_image_url'] = $tx['image_filename'] ? '/auction_of_paintings/public/uploads/products/' . $tx['image_filename'] : '';
                $tx['buyer_slip_url'] = $tx['payment_slip'] ? '/auction_of_paintings/public/uploads/slips/' . $tx['payment_slip'] : null;
                $tx['admin_slip_url'] = $tx['admin_transfer_slip'] ? '/auction_of_paintings/public/uploads/slips/admin/' . $tx['admin_transfer_slip'] : null;

                // ถอดรหัสเลขบัญชีผู้ขาย
                if (!empty($tx['seller_bank_acc'])) {
                    $decrypted_bank = openssl_decrypt($tx['seller_bank_acc'], $encrypt_method, $encrypt_key, 0, $encrypt_iv);
                    $tx['seller_bank_acc'] = ($decrypted_bank !== false) ? $decrypted_bank : 'ถอดรหัสไม่ได้ (Key ไม่ตรง)';
                }
            }

            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $transactions]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    // ----------------------------------------------------
    // 23. Admin ตรวจสอบสลิปและยืนยันยอดเงินจากผู้ซื้อ
    // ----------------------------------------------------
    case 'v1/admin/finance/verify-buyer':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            break;

        // 🔐 เช็คสิทธิ์ JWT Admin
        $headers = apache_request_headers();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';
        if (!$token) {
            http_response_code(401);
            exit;
        }

        try {
            $decoded = JWT::decode($token, new \Firebase\JWT\Key($secret_key, 'HS256'));
            if ($decoded->data->role !== 'admin') {
                http_response_code(403);
                exit;
            }
        } catch (Exception $e) {
            http_response_code(401);
            exit;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $transaction_id = $data['transaction_id'] ?? null;
        $action = $data['action'] ?? ''; // 'approve' หรือ 'reject'

        try {
            if ($action === 'approve') {
                $stmt = $db->prepare("UPDATE transactions SET payment_status = 'admin_verified' WHERE id = ?");
                $stmt->execute([$transaction_id]);
                $msg = "ตรวจสอบและรับยอดเงินสำเร็จ";
            } else {
                $stmt = $db->prepare("UPDATE transactions SET payment_status = 'pending', payment_slip = NULL WHERE id = ?");
                $stmt->execute([$transaction_id]);
                $msg = "ปฏิเสธสลิปแล้ว ระบบจะแจ้งให้ผู้ซื้อโอนใหม่";
            }
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => $msg]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Database Error"]);
        }
        break;

    // ----------------------------------------------------
    // 24. Admin โอนเงินให้ผู้ขาย (แนบสลิป)
    // ----------------------------------------------------
    case 'v1/admin/finance/transfer-seller':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            break;

        // 🔐 เช็คสิทธิ์ JWT Admin
        $headers = apache_request_headers();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';
        if (!$token) {
            http_response_code(401);
            exit;
        }

        try {
            $decoded = JWT::decode($token, new \Firebase\JWT\Key($secret_key, 'HS256'));
            if ($decoded->data->role !== 'admin') {
                http_response_code(403);
                exit;
            }
        } catch (Exception $e) {
            http_response_code(401);
            exit;
        }

        try {
            $transaction_id = $_POST['transaction_id'] ?? null;
            if (!$transaction_id || !isset($_FILES['admin_slip'])) {
                http_response_code(400);
                echo json_encode(["message" => "กรุณาอัปโหลดหลักฐานการโอนเงิน"]);
                exit;
            }

            $upload_dir = '../public/uploads/slips/admin/';
            if (!is_dir($upload_dir))
                mkdir($upload_dir, 0777, true);

            $ext = strtolower(pathinfo($_FILES['admin_slip']['name'], PATHINFO_EXTENSION));
            $filename = 'admin_transfer_' . $transaction_id . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['admin_slip']['tmp_name'], $upload_dir . $filename);

            $stmt = $db->prepare("UPDATE transactions SET payment_status = 'transferred_to_seller', admin_transfer_slip = ? WHERE id = ?");
            $stmt->execute([$filename, $transaction_id]);

            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "บันทึกการโอนเงินให้ผู้ขายสำเร็จ"]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Upload Error"]);
        }
        break;
    // ----------------------------------------------------
    // 25. ดึงรายชื่อผู้ใช้ทั้งหมด (Admin User Management)
    // ----------------------------------------------------
    case 'v1/admin/users/list':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET')
            break;
        // ... โค้ดเช็ค JWT Admin (เหมือน API อื่นๆ ของแอดมิน) ...

        try {
            // ดึงข้อมูล User พร้อมข้อมูล KYC (ถ้ามี) เพื่อเอามาดูเลขบัตร
            $stmt = $db->query("
                SELECT u.id, u.username, u.email, u.phone, u.role, u.status, u.created_at,
                       k.id_card_no, k.status as kyc_status
                FROM users u
                LEFT JOIN kyc_verifications k ON u.id = k.user_id
                ORDER BY u.created_at DESC
            ");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // ถอดรหัสเลขบัตรประชาชนให้แอดมินดู
            $encrypt_method = "AES-256-CBC";
            $encrypt_key = hash('sha256', 'ArtBids_Super_Secret_Key_2026_For_JWT_Authentication!');
            $encrypt_iv = substr(hash('sha256', 'ArtBids_Secret_IV_2024!'), 0, 16);

            foreach ($users as &$u) {
                if ($u['id_card_no']) {
                    $decrypted_id = openssl_decrypt($u['id_card_no'], $encrypt_method, $encrypt_key, 0, $encrypt_iv);
                    // ปิดบังตัวเลขบางส่วนเพื่อความปลอดภัย เช่น 11007XXXXX999
                    if ($decrypted_id) {
                        $u['id_card_display'] = substr($decrypted_id, 0, 5) . 'XXXXX' . substr($decrypted_id, -3);
                    } else {
                        $u['id_card_display'] = 'ถอดรหัสไม่ได้';
                    }
                } else {
                    $u['id_card_display'] = '-';
                }
            }

            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $users]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    // ----------------------------------------------------
    // 26. Admin สั่ง แบน / ปลดแบน User และ Blacklist บัตร ปชช.
    // ----------------------------------------------------
    case 'v1/admin/users/toggle-ban':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            break;
        // ... โค้ดเช็ค JWT Admin ...

        $data = json_decode(file_get_contents("php://input"), true);
        $target_user_id = $data['user_id'] ?? null;
        $action = $data['action'] ?? ''; // 'ban' หรือ 'unban'
        $reason = $data['reason'] ?? 'ไม่ระบุเหตุผล';

        try {
            $db->beginTransaction();

            if ($action === 'ban') {
                // 1. แบนบัญชี
                $stmt = $db->prepare("UPDATE users SET status = 'banned' WHERE id = ?");
                $stmt->execute([$target_user_id]);

                // 2. หาเลขบัตรประชาชนของคนๆ นี้ (ถ้ามี) เพื่อจับลง Blacklist
                $stmtKyc = $db->prepare("SELECT id_card_no FROM kyc_verifications WHERE user_id = ?");
                $stmtKyc->execute([$target_user_id]);
                $kyc = $stmtKyc->fetch(PDO::FETCH_ASSOC);

                if ($kyc && !empty($kyc['id_card_no'])) {
                    // เอาเลขบัตรที่เข้ารหัสแล้ว ยัดลง Blacklist เลย (ใช้ ON DUPLICATE เผื่อแบนซ้ำ)
                    $stmtBanId = $db->prepare("INSERT INTO banned_id_cards (id_card_encrypted, reason) VALUES (?, ?) ON DUPLICATE KEY UPDATE reason = ?");
                    $stmtBanId->execute([$kyc['id_card_no'], $reason, $reason]);
                }
                $msg = "ระงับบัญชีผู้ใช้ และแบล็กลิสต์บัตรประชาชนเรียบร้อยแล้ว";

            } else {
                // ปลดแบนบัญชี
                $stmt = $db->prepare("UPDATE users SET status = 'active' WHERE id = ?");
                $stmt->execute([$target_user_id]);

                // ถอน Blacklist บัตรประชาชนด้วย
                $stmtKyc = $db->prepare("SELECT id_card_no FROM kyc_verifications WHERE user_id = ?");
                $stmtKyc->execute([$target_user_id]);
                $kyc = $stmtKyc->fetch(PDO::FETCH_ASSOC);

                if ($kyc && !empty($kyc['id_card_no'])) {
                    $stmtUnbanId = $db->prepare("DELETE FROM banned_id_cards WHERE id_card_encrypted = ?");
                    $stmtUnbanId->execute([$kyc['id_card_no']]);
                }
                $msg = "ปลดระงับบัญชีเรียบร้อยแล้ว ผู้ใช้สามารถกลับมาใช้งานได้ตามปกติ";
            }

            $db->commit();
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => $msg]);
        } catch (Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;
    // ----------------------------------------------------
    // 27. อัปเดตที่อยู่ผู้ใช้ (My Profile)
    // ----------------------------------------------------
    case 'v1/user/update-address':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            break;
        $headers = apache_request_headers();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';
        if (!$token) {
            http_response_code(401);
            exit;
        }

        try {
            $decoded = JWT::decode($token, new \Firebase\JWT\Key($secret_key, 'HS256'));
            $data = json_decode(file_get_contents("php://input"), true);
            $address = $data['address'] ?? '';

            $stmt = $db->prepare("UPDATE users SET address = ? WHERE id = ?");
            $stmt->execute([$address, $decoded->data->id]);

            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "บันทึกที่อยู่เรียบร้อยแล้ว"]);
        } catch (Exception $e) {
            http_response_code(401);
        }
        break;

    // ----------------------------------------------------
    // 28. ผู้ขายแจ้งเลขพัสดุ (Seller)
    // ----------------------------------------------------
    case 'v1/seller/update-tracking':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            break;
        $headers = apache_request_headers();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';
        if (!$token) {
            http_response_code(401);
            exit;
        }

        try {
            $decoded = JWT::decode($token, new \Firebase\JWT\Key($secret_key, 'HS256'));
            if ($decoded->data->role !== 'seller') {
                http_response_code(403);
                exit;
            }

            $transaction_id = $_POST['transaction_id'] ?? null;
            $tracking_number = $_POST['tracking_number'] ?? null;

            if (!$transaction_id || !$tracking_number || !isset($_FILES['shipping_proof'])) {
                http_response_code(400);
                echo json_encode(["message" => "กรุณากรอกเลขพัสดุและแนบรูปหลักฐาน"]);
                exit;
            }

            $upload_dir = '../public/uploads/tracking/';
            if (!is_dir($upload_dir))
                mkdir($upload_dir, 0777, true);

            $ext = strtolower(pathinfo($_FILES['shipping_proof']['name'], PATHINFO_EXTENSION));
            $filename = 'proof_' . $transaction_id . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['shipping_proof']['tmp_name'], $upload_dir . $filename);

            $stmt = $db->prepare("UPDATE transactions SET tracking_number = ?, shipping_proof_img = ?, shipping_status = 'shipped' WHERE id = ? AND seller_id = ?");
            $stmt->execute([$tracking_number, $filename, $transaction_id, $decoded->data->id]);

            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "แจ้งจัดส่งสินค้าเรียบร้อยแล้ว"]);
        } catch (Exception $e) {
            http_response_code(401);
        }
        break;

    // ----------------------------------------------------
    // 29. ผู้ซื้อกดยืนยันรับของ (Buyer)
    // ----------------------------------------------------
    case 'v1/buyer/confirm-receipt':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            break;
        $headers = apache_request_headers();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';
        if (!$token) {
            http_response_code(401);
            exit;
        }

        try {
            $decoded = JWT::decode($token, new \Firebase\JWT\Key($secret_key, 'HS256'));
            $data = json_decode(file_get_contents("php://input"), true);
            $transaction_id = $data['transaction_id'] ?? null;

            $stmt = $db->prepare("UPDATE transactions SET shipping_status = 'received' WHERE id = ? AND buyer_id = ?");
            $stmt->execute([$transaction_id, $decoded->data->id]);

            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "ยืนยันการรับสินค้าสำเร็จ! แอดมินจะโอนเงินให้ผู้ขายต่อไป"]);
        } catch (Exception $e) {
            http_response_code(401);
        }
        break;
    // ----------------------------------------------------
    // เส้นทางไม่ถูกต้อง
    // ----------------------------------------------------
    default:
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "ไม่พบ API Endpoint นี"]);
        break;
}
?>