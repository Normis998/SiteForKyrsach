CREATE DATABASE IF NOT EXISTS artmarket
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE artmarket;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('artist','customer') NOT NULL DEFAULT 'customer',
  bio TEXT NULL,
  avatar VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE artworks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  image VARCHAR(255) NOT NULL,
  category VARCHAR(60) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX (user_id)
) ENGINE=InnoDB;

CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  artist_id INT NOT NULL,
  artwork_id INT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  budget DECIMAL(10,2) NOT NULL DEFAULT 0,
  deadline DATE NULL,
  status ENUM('pending','accepted','declined','in_progress','completed','cancelled')
         NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (artist_id)   REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (artwork_id)  REFERENCES artworks(id) ON DELETE SET NULL,
  INDEX (customer_id), INDEX (artist_id), INDEX (status)
) ENGINE=InnoDB;

CREATE TABLE messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  sender_id INT NOT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id)  REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX (order_id)
) ENGINE=InnoDB;

CREATE TABLE reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id  INT NOT NULL UNIQUE,
  author_id INT NOT NULL,
  artist_id INT NOT NULL,
  rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id)  REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (artist_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX (artist_id)
) ENGINE=InnoDB;