-- Database structure for Sustainability e-Commerce Project

CREATE DATABASE IF NOT EXISTS eco_market;
USE eco_market;

-- Markets table
CREATE TABLE IF NOT EXISTS markets (
    market_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    city VARCHAR(50) NOT NULL,
    district VARCHAR(50) NOT NULL,
    verified BOOLEAN DEFAULT FALSE,
    verification_code VARCHAR(6) DEFAULT NULL,
    remember_token VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Consumers table
CREATE TABLE IF NOT EXISTS consumers (
    consumer_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    fullname VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    city VARCHAR(50) NOT NULL,
    district VARCHAR(50) NOT NULL,
    verified BOOLEAN DEFAULT FALSE,
    verification_code VARCHAR(6) DEFAULT NULL,
    remember_token VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    market_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    stock INT NOT NULL,
    normal_price DECIMAL(10, 2) NOT NULL,
    discounted_price DECIMAL(10, 2) NOT NULL,
    expiration_date DATE NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (market_id) REFERENCES markets(market_id) ON DELETE CASCADE
);

-- Shopping cart table
CREATE TABLE IF NOT EXISTS cart_items (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    consumer_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (consumer_id) REFERENCES consumers(consumer_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (consumer_id, product_id)
);

-- Sample Data
-- Insert sample market data (passwords are hashed version of '123456')
INSERT INTO markets (email, name, password, city, district, verified) VALUES
('migros@example.com', 'Migros - Çankaya', '$2y$10$gx.XcjFRQikuJ2sJ447mkuIr8OQSSqYs0wKANH9MBZpho/InSxJJ2', 'Ankara', 'Çankaya', TRUE),
('carrefour@example.com', 'Carrefour - Kadıköy', '$2y$10$gx.XcjFRQikuJ2sJ447mkuIr8OQSSqYs0wKANH9MBZpho/InSxJJ2', 'Ankara', 'Çankaya', TRUE),
('macro@example.com', 'Macro Center - Etiler', '$2y$10$gx.XcjFRQikuJ2sJ447mkuIr8OQSSqYs0wKANH9MBZpho/InSxJJ2', 'Istanbul', 'Beşiktaş', TRUE),
('a101@example.com', 'A101 - Keçiören', '$2y$10$gx.XcjFRQikuJ2sJ447mkuIr8OQSSqYs0wKANH9MBZpho/InSxJJ2', 'Ankara', 'Keçiören', TRUE),
('sok@example.com', 'Şok Market - Bahçelievler', '$2y$10$gx.XcjFRQikuJ2sJ447mkuIr8OQSSqYs0wKANH9MBZpho/InSxJJ2', 'Istanbul', 'Bahçelievler', TRUE),
('bim@example.com', 'BİM - Ataşehir', '$2y$10$gx.XcjFRQikuJ2sJ447mkuIr8OQSSqYs0wKANH9MBZpho/InSxJJ2', 'Istanbul', 'Ataşehir', TRUE);

-- Insert sample consumers (passwords are hashed version of '123456')
INSERT INTO consumers (email, fullname, password, city, district, verified) VALUES
('ahmet@example.com', 'Ahmet Yılmaz', '$2y$10$gx.XcjFRQikuJ2sJ447mkuIr8OQSSqYs0wKANH9MBZpho/InSxJJ2', 'Istanbul', 'Kadıköy', TRUE),
('ayse@example.com', 'Ayşe Demir', '$2y$10$gx.XcjFRQikuJ2sJ447mkuIr8OQSSqYs0wKANH9MBZpho/InSxJJ2', 'Istanbul', 'Beşiktaş', TRUE),
('mehmet@example.com', 'Mehmet Çelik', '$2y$10$gx.XcjFRQikuJ2sJ447mkuIr8OQSSqYs0wKANH9MBZpho/InSxJJ2', 'Ankara', 'Çankaya', TRUE),
('fatma@example.com', 'Fatma Şahin', '$2y$10$gx.XcjFRQikuJ2sJ447mkuIr8OQSSqYs0wKANH9MBZpho/InSxJJ2', 'Ankara', 'Keçiören', TRUE),
('mustafa@example.com', 'Mustafa Kaya', '$2y$10$gx.XcjFRQikuJ2sJ447mkuIr8OQSSqYs0wKANH9MBZpho/InSxJJ2', 'Istanbul', 'Ataşehir', TRUE),
('zeynep@example.com', 'Zeynep Öztürk', '$2y$10$gx.XcjFRQikuJ2sJ447mkuIr8OQSSqYs0wKANH9MBZpho/InSxJJ2', 'Istanbul', 'Bahçelievler', TRUE);

-- Insert sample products with expiration dates (some soon, some later)
INSERT INTO products (market_id, title, stock, normal_price, discounted_price, expiration_date, image_path) VALUES
(1, 'Pınar Süt 1L', 15, 17.99, 9.99, DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY), 'uploads/products/milk.jpg'),
(1, 'Sütaş Yoğurt 1kg', 8, 29.99, 19.99, DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY), 'uploads/products/yogurt.jpg'),
(2, 'Eti Burçak Bisküvi 3lü', 20, 12.50, 8.75, DATE_ADD(CURRENT_DATE, INTERVAL -5 DAY), 'uploads/products/biscuit.jpg'),
(2, 'Ülker Çikolatalı Gofret', 25, 8.99, 5.99, DATE_ADD(CURRENT_DATE, INTERVAL 14 DAY), 'uploads/products/wafer.jpg'),
(3, 'Tadım Kavrulmuş Fındık 200g', 10, 65.00, 39.90, DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY), 'uploads/products/hazelnuts.jpg'),
(3, 'Organik Elma 1kg', 12, 25.00, 15.00, DATE_ADD(CURRENT_DATE, INTERVAL 4 DAY), 'uploads/products/apple.jpg'),
(4, 'Banvit Tavuk Göğüs 500g', 6, 38.50, 25.99, DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY), 'uploads/products/chicken.jpg'),
(4, 'İçim Peynir 600g', 8, 59.90, 39.90, DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY), 'uploads/products/cheese.jpg'),
(5, 'Ekmek 350g', 30, 4.00, 2.00, DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY), 'uploads/products/bread.jpg'),
(5, 'Dardanel Ton Balığı', 15, 32.90, 24.90, DATE_ADD(CURRENT_DATE, INTERVAL 60 DAY), 'uploads/products/tuna.jpg'),
(6, 'Büyük Boy Muz 1kg', 8, 34.90, 24.90, DATE_ADD(CURRENT_DATE, INTERVAL 4 DAY), 'uploads/products/banana.jpg'),
(6, 'Reis Pirinç 2kg', 10, 75.50, 59.90, DATE_ADD(CURRENT_DATE, INTERVAL 90 DAY), 'uploads/products/rice.jpg');

-- Insert sample cart items
INSERT INTO cart_items (consumer_id, product_id, quantity) VALUES
(1, 3, 2),
(1, 5, 1),
(2, 6, 3),
(3, 2, 2),
(3, 10, 1),
(4, 8, 1),
(5, 11, 2); 