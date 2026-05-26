-- 1. ตารางผู้ใช้งาน
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('bidder', 'seller', 'admin') DEFAULT 'bidder',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. ตารางยืนยันตัวตน (e-KYC)
CREATE TABLE kyc_verifications (
    user_id INT PRIMARY KEY,
    real_name VARCHAR(100) NOT NULL,
    id_card_no VARCHAR(13) NOT NULL,
    bank_name VARCHAR(50),
    bank_acc_no VARCHAR(20),
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    admin_note TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- 3. ตารางสินค้าภาพวาด
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_price DECIMAL(15, 2) NOT NULL,
    min_step DECIMAL(15, 2) NOT NULL,
    current_price DECIMAL(15, 2) NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM('active', 'ended', 'sold', 'cancelled') DEFAULT 'active',
    winner_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id),
    FOREIGN KEY (winner_id) REFERENCES users(id),
    INDEX (status, end_time) -- เพิ่ม Index เพื่อให้ Query สินค้าที่กำลังประมูลได้เร็ว
) ENGINE=InnoDB;

-- 4. ตารางประวัติการบิดราคา (Transaction Log)
CREATE TABLE bids (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

