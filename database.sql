-- Database Schema for Anime Streaming Site

CREATE TABLE `admins` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `username` VARCHAR(50) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `email` VARCHAR(100),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `anime` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `zh_name` VARCHAR(255),
  `slug` VARCHAR(255) UNIQUE NOT NULL,
  `synopsis` TEXT,
  `poster` VARCHAR(255),
  `status` ENUM('ongoing', 'completed') DEFAULT 'ongoing',
  `type` VARCHAR(50),
  `is_hot` BOOLEAN DEFAULT FALSE,
  `is_new` BOOLEAN DEFAULT FALSE,
  `update_date` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `episodes` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `anime_id` INT NOT NULL,
  `episode_number` INT NOT NULL,
  `title` VARCHAR(255),
  `slug` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`anime_id`) REFERENCES `anime`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_episode` (`anime_id`, `episode_number`)
);

CREATE TABLE `servers` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `episode_id` INT NOT NULL,
  `server_name` VARCHAR(50) NOT NULL,
  `server_url` TEXT NOT NULL,
  `quality` VARCHAR(20),
  `is_default` BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (`episode_id`) REFERENCES `episodes`(`id`) ON DELETE CASCADE
);

CREATE TABLE `categories` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE `anime_categories` (
  `anime_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  PRIMARY KEY (`anime_id`, `category_id`),
  FOREIGN KEY (`anime_id`) REFERENCES `anime`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
);

-- Insert default admin (password: admin123 - change this!)
INSERT INTO `admins` (`username`, `password`, `email`) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@vitaanime.com');