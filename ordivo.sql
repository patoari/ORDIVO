-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 21, 2026 at 01:35 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ordivo`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_activity_logs`
--

CREATE TABLE `admin_activity_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_permissions`
--

CREATE TABLE `admin_permissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `module` varchar(50) NOT NULL COMMENT 'users, vendors, products, orders, etc.',
  `action` varchar(50) NOT NULL COMMENT 'create, read, update, delete, manage',
  `resource` varchar(100) DEFAULT '*' COMMENT 'specific resource or * for all',
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional conditions' CHECK (json_valid(`conditions`)),
  `granted_by` int(11) DEFAULT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `advertisements`
--

CREATE TABLE `advertisements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `ad_type` enum('banner','popup','sidebar','inline','video','native','sponsored') NOT NULL,
  `ad_format` enum('image','video','html','text','carousel','interactive') DEFAULT 'image',
  `ad_size` varchar(50) DEFAULT NULL COMMENT 'e.g., 728x90, 300x250, 320x50',
  `content` longtext DEFAULT NULL COMMENT 'HTML content or image URL',
  `media_url` varchar(500) DEFAULT NULL,
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `click_url` varchar(500) DEFAULT NULL,
  `target_url` varchar(500) DEFAULT NULL,
  `call_to_action` varchar(100) DEFAULT NULL,
  `advertiser_name` varchar(150) DEFAULT NULL,
  `advertiser_contact` varchar(100) DEFAULT NULL,
  `advertiser_email` varchar(100) DEFAULT NULL,
  `budget_total` decimal(12,2) DEFAULT 0.00,
  `budget_daily` decimal(10,2) DEFAULT 0.00,
  `cost_per_click` decimal(8,2) DEFAULT 0.00,
  `cost_per_impression` decimal(8,4) DEFAULT 0.0000,
  `priority` tinyint(1) DEFAULT 1 COMMENT '1=High, 2=Medium, 3=Low',
  `weight` int(11) DEFAULT 100 COMMENT 'Ad serving weight',
  `max_impressions` int(11) DEFAULT NULL,
  `max_clicks` int(11) DEFAULT NULL,
  `current_impressions` int(11) DEFAULT 0,
  `current_clicks` int(11) DEFAULT 0,
  `current_spend` decimal(12,2) DEFAULT 0.00,
  `conversion_tracking` tinyint(1) DEFAULT 0,
  `conversion_count` int(11) DEFAULT 0,
  `conversion_value` decimal(12,2) DEFAULT 0.00,
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `status` enum('draft','active','paused','completed','cancelled','expired') DEFAULT 'draft',
  `approval_status` enum('pending','approved','rejected','needs_review') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `advertisements`
--

INSERT INTO `advertisements` (`id`, `title`, `description`, `ad_type`, `ad_format`, `ad_size`, `content`, `media_url`, `thumbnail_url`, `click_url`, `target_url`, `call_to_action`, `advertiser_name`, `advertiser_contact`, `advertiser_email`, `budget_total`, `budget_daily`, `cost_per_click`, `cost_per_impression`, `priority`, `weight`, `max_impressions`, `max_clicks`, `current_impressions`, `current_clicks`, `current_spend`, `conversion_tracking`, `conversion_count`, `conversion_value`, `start_date`, `end_date`, `status`, `approval_status`, `approved_by`, `approved_at`, `rejection_reason`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'বিশেষ ঈদ অফার - ৫০% ছাড়', 'সব ধরনের খাবারে বিশেষ ছাড়। সীমিত সময়ের জন্য।', 'banner', 'image', '728x90', '<div class=\"eid-offer-banner\" style=\"background: linear-gradient(135deg, #d4af37, #ffd700); color: #2f4f4f; padding: 20px; text-align: center; border-radius: 10px;\"><h2>ঈদ মুবারক! ৫০% ছাড়</h2><p>সব খাবারে বিশেষ অফার</p></div>', NULL, NULL, '/offers/eid-special', NULL, 'অফার দেখুন', 'ORDIVO Marketing', NULL, NULL, 50000.00, 1000.00, 0.00, 0.0000, 1, 100, 100000, NULL, 0, 0, 0.00, 0, 0, 0.00, '2026-02-04 13:13:58', '2026-02-11 13:13:58', 'active', 'approved', NULL, NULL, NULL, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(2, 'নতুন রেস্তোরাঁ - ঢাকা বিরিয়ানি হাউস', 'খাঁটি বাংলাদেশি বিরিয়ানি এবং ঐতিহ্যবাহী খাবার', 'sidebar', 'image', '300x250', '<div class=\"restaurant-ad\" style=\"background: #fff; border: 2px solid #e21b70; padding: 15px; border-radius: 8px;\"><img src=\"/images/biriyani-house.jpg\" style=\"width: 100%; border-radius: 5px;\"><h3 style=\"color: #e21b70; margin: 10px 0;\">ঢাকা বিরিয়ানি হাউস</h3><p>সেরা বিরিয়ানি শহরে</p></div>', NULL, NULL, '/vendor/dhaka-biriyani-house', NULL, 'অর্ডার করুন', 'ঢাকা বিরিয়ানি হাউস', NULL, NULL, 25000.00, 500.00, 0.00, 0.0000, 2, 100, 50000, NULL, 0, 0, 0.00, 0, 0, 0.00, '2026-02-04 13:13:58', '2026-03-06 13:13:58', 'active', 'approved', NULL, NULL, NULL, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(3, 'ফ্রি ডেলিভারি ক্যাম্পেইন', '৫০০ টাকার উপরে অর্ডারে ফ্রি ডেলিভারি', 'popup', 'html', '400x300', '<div class=\"free-delivery-popup\" style=\"background: #4caf50; color: white; padding: 30px; text-align: center; border-radius: 15px;\"><h2>🚚 ফ্রি ডেলিভারি!</h2><p>৫০০ টাকার উপরে অর্ডারে</p><button style=\"background: white; color: #4caf50; border: none; padding: 10px 20px; border-radius: 5px; font-weight: bold;\">এখনই অর্ডার করুন</button></div>', NULL, NULL, '/order-now', NULL, 'অর্ডার করুন', 'ORDIVO', NULL, NULL, 30000.00, 800.00, 0.00, 0.0000, 1, 100, 75000, NULL, 0, 0, 0.00, 0, 0, 0.00, '2026-02-04 13:13:58', '2026-02-19 13:13:58', 'active', 'approved', NULL, NULL, NULL, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(4, 'গ্রোসারি স্পেশাল - তাজা সবজি', 'দৈনন্দিন প্রয়োজনীয় সব কিছু ঘরে বসে পান', 'inline', 'image', '320x100', '<div class=\"grocery-ad\" style=\"background: linear-gradient(90deg, #4caf50, #8bc34a); color: white; padding: 15px; border-radius: 10px; display: flex; align-items: center;\"><div style=\"flex: 1;\"><h3>তাজা সবজি ও ফল</h3><p>সরাসরি খামার থেকে</p></div><div><button style=\"background: white; color: #4caf50; border: none; padding: 8px 15px; border-radius: 5px;\">কিনুন</button></div></div>', NULL, NULL, '/grocery', NULL, 'এখনই কিনুন', 'ফ্রেশ মার্ট', NULL, NULL, 20000.00, 400.00, 0.00, 0.0000, 2, 100, 40000, NULL, 0, 0, 0.00, 0, 0, 0.00, '2026-02-04 13:13:58', '2026-02-24 13:13:58', 'active', 'approved', NULL, NULL, NULL, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58');

-- --------------------------------------------------------

--
-- Table structure for table `advertisement_analytics`
--

CREATE TABLE `advertisement_analytics` (
  `id` int(11) NOT NULL,
  `ad_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `impressions` int(11) DEFAULT 0,
  `clicks` int(11) DEFAULT 0,
  `conversions` int(11) DEFAULT 0,
  `spend` decimal(10,2) DEFAULT 0.00,
  `revenue` decimal(10,2) DEFAULT 0.00,
  `ctr` decimal(5,4) DEFAULT 0.0000 COMMENT 'Click-through rate',
  `cpc` decimal(8,2) DEFAULT 0.00 COMMENT 'Cost per click',
  `cpm` decimal(8,2) DEFAULT 0.00 COMMENT 'Cost per mille',
  `conversion_rate` decimal(5,4) DEFAULT 0.0000,
  `roas` decimal(8,2) DEFAULT 0.00 COMMENT 'Return on ad spend',
  `unique_users` int(11) DEFAULT 0,
  `bounce_rate` decimal(5,2) DEFAULT 0.00,
  `avg_time_on_page` int(11) DEFAULT 0 COMMENT 'Seconds',
  `mobile_impressions` int(11) DEFAULT 0,
  `desktop_impressions` int(11) DEFAULT 0,
  `tablet_impressions` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `advertisement_categories`
--

CREATE TABLE `advertisement_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT '#007bff',
  `icon` varchar(100) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `advertisement_categories`
--

INSERT INTO `advertisement_categories` (`id`, `name`, `description`, `color`, `icon`, `sort_order`, `is_active`, `created_by`, `created_at`) VALUES
(1, 'খাবার ও পানীয়', 'Food and beverage advertisements', '#ff5722', 'fas fa-utensils', 1, 1, NULL, '2026-02-04 13:13:58'),
(2, 'গ্রোসারি ও দৈনন্দিন', 'Grocery and daily essentials', '#4caf50', 'fas fa-shopping-basket', 2, 1, NULL, '2026-02-04 13:13:58'),
(3, 'ইলেকট্রনিক্স', 'Electronics and gadgets', '#2196f3', 'fas fa-mobile-alt', 3, 1, NULL, '2026-02-04 13:13:58'),
(4, 'ফ্যাশন ও পোশাক', 'Fashion and clothing', '#e91e63', 'fas fa-tshirt', 4, 1, NULL, '2026-02-04 13:13:58'),
(5, 'স্বাস্থ্য ও সৌন্দর্য', 'Health and beauty products', '#9c27b0', 'fas fa-heart', 5, 1, NULL, '2026-02-04 13:13:58'),
(6, 'শিক্ষা ও প্রশিক্ষণ', 'Education and training services', '#ff9800', 'fas fa-graduation-cap', 6, 1, NULL, '2026-02-04 13:13:58'),
(7, 'ভ্রমণ ও পর্যটন', 'Travel and tourism', '#00bcd4', 'fas fa-plane', 7, 1, NULL, '2026-02-04 13:13:58'),
(8, 'আর্থিক সেবা', 'Financial services', '#795548', 'fas fa-coins', 8, 1, NULL, '2026-02-04 13:13:58');

-- --------------------------------------------------------

--
-- Table structure for table `advertisement_category_relations`
--

CREATE TABLE `advertisement_category_relations` (
  `id` int(11) NOT NULL,
  `ad_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `advertisement_clicks`
--

CREATE TABLE `advertisement_clicks` (
  `id` int(11) NOT NULL,
  `ad_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `device_type` enum('desktop','mobile','tablet') DEFAULT NULL,
  `browser` varchar(100) DEFAULT NULL,
  `os` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `referrer_url` varchar(500) DEFAULT NULL,
  `page_url` varchar(500) DEFAULT NULL,
  `click_position` varchar(100) DEFAULT NULL,
  `conversion_tracked` tinyint(1) DEFAULT 0,
  `conversion_value` decimal(10,2) DEFAULT 0.00,
  `clicked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `advertisement_placements`
--

CREATE TABLE `advertisement_placements` (
  `id` int(11) NOT NULL,
  `ad_id` int(11) NOT NULL,
  `page_type` varchar(50) NOT NULL COMMENT 'homepage, product_page, vendor_page, etc.',
  `position` varchar(100) NOT NULL COMMENT 'header, sidebar, footer, between_products, etc.',
  `display_order` int(11) DEFAULT 0,
  `is_sticky` tinyint(1) DEFAULT 0,
  `show_on_mobile` tinyint(1) DEFAULT 1,
  `show_on_tablet` tinyint(1) DEFAULT 1,
  `show_on_desktop` tinyint(1) DEFAULT 1,
  `frequency_cap` int(11) DEFAULT NULL COMMENT 'Max times to show per user per day',
  `min_time_between_shows` int(11) DEFAULT 0 COMMENT 'Seconds between shows',
  `custom_css` text DEFAULT NULL,
  `animation_type` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `advertisement_placements`
--

INSERT INTO `advertisement_placements` (`id`, `ad_id`, `page_type`, `position`, `display_order`, `is_sticky`, `show_on_mobile`, `show_on_tablet`, `show_on_desktop`, `frequency_cap`, `min_time_between_shows`, `custom_css`, `animation_type`, `is_active`, `created_at`) VALUES
(1, 1, 'homepage', 'top_banner', 1, 0, 1, 1, 1, NULL, 0, NULL, NULL, 1, '2026-02-04 13:13:58'),
(2, 2, 'homepage', 'sidebar', 1, 0, 0, 1, 1, NULL, 0, NULL, NULL, 1, '2026-02-04 13:13:58'),
(3, 3, 'homepage', 'popup', 1, 0, 1, 1, 1, NULL, 0, NULL, NULL, 1, '2026-02-04 13:13:58'),
(4, 4, 'product_page', 'between_products', 1, 0, 1, 1, 1, NULL, 0, NULL, NULL, 1, '2026-02-04 13:13:58');

-- --------------------------------------------------------

--
-- Table structure for table `advertisement_targeting`
--

CREATE TABLE `advertisement_targeting` (
  `id` int(11) NOT NULL,
  `ad_id` int(11) NOT NULL,
  `target_type` enum('location','demographic','behavior','interest','device','time') NOT NULL,
  `target_key` varchar(100) NOT NULL COMMENT 'country_id, age_group, device_type, etc.',
  `target_value` varchar(255) NOT NULL,
  `target_operator` enum('equals','not_equals','contains','in','not_in','greater','less') DEFAULT 'equals',
  `is_include` tinyint(1) DEFAULT 1 COMMENT '1=Include, 0=Exclude',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `advertisement_targeting`
--

INSERT INTO `advertisement_targeting` (`id`, `ad_id`, `target_type`, `target_key`, `target_value`, `target_operator`, `is_include`, `created_at`) VALUES
(1, 1, 'location', 'city_id', '1', 'equals', 1, '2026-02-04 13:13:58'),
(2, 2, 'location', 'area_id', '1,2,3', 'equals', 1, '2026-02-04 13:13:58'),
(3, 3, 'demographic', 'user_type', 'new_customer', 'equals', 1, '2026-02-04 13:13:58'),
(4, 4, 'behavior', 'previous_orders', 'grocery', 'equals', 1, '2026-02-04 13:13:58');

-- --------------------------------------------------------

--
-- Table structure for table `advertisement_variants`
--

CREATE TABLE `advertisement_variants` (
  `id` int(11) NOT NULL,
  `ad_id` int(11) NOT NULL,
  `variant_name` varchar(100) NOT NULL,
  `variant_type` enum('title','description','image','cta','color','layout') NOT NULL,
  `original_value` text DEFAULT NULL,
  `variant_value` text DEFAULT NULL,
  `traffic_split` decimal(5,2) DEFAULT 50.00 COMMENT 'Percentage of traffic',
  `impressions` int(11) DEFAULT 0,
  `clicks` int(11) DEFAULT 0,
  `conversions` int(11) DEFAULT 0,
  `is_winner` tinyint(1) DEFAULT 0,
  `test_start_date` timestamp NULL DEFAULT NULL,
  `test_end_date` timestamp NULL DEFAULT NULL,
  `status` enum('draft','running','completed','paused') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `areas`
--

CREATE TABLE `areas` (
  `id` int(11) NOT NULL,
  `city_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `delivery_charge` decimal(8,2) DEFAULT 0.00,
  `status` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `areas`
--

INSERT INTO `areas` (`id`, `city_id`, `name`, `postal_code`, `delivery_charge`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'ধানমন্ডি', '1205', 30.00, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(2, 1, 'গুলশান', '1212', 40.00, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(3, 1, 'বনানী', '1213', 40.00, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(4, 1, 'উত্তরা', '1230', 50.00, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(5, 1, 'মিরপুর', '1216', 35.00, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(6, 1, 'বসুন্ধরা আবাসিক এলাকা', '1229', 45.00, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(7, 1, 'ওয়ারী', '1203', 25.00, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(8, 1, 'পুরান ঢাকা', '1100', 25.00, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(9, 1, 'তেজগাঁও', '1215', 30.00, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(10, 1, 'মোহাম্মদপুর', '1207', 30.00, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(11, 1, 'পান্থপথ', '1205', 35.00, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(12, 1, 'শান্তিনগর', '1217', 25.00, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(13, 1, 'এলিফ্যান্ট রোড', '1205', 25.00, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(14, 1, 'নিউ মার্কেট', '1205', 25.00, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(15, 1, 'ফার্মগেট', '1215', 30.00, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(16, 1, 'কারওয়ান বাজার', '1215', 30.00, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(17, 1, 'মতিঝিল', '1000', 25.00, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(18, 1, 'রমনা', '1000', 30.00, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(19, 1, 'আজিমপুর', '1205', 25.00, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(20, 1, 'লালমাটিয়া', '1207', 30.00, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(21, 1, 'যাত্রাবাড়ী', '1204', 35.00, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(22, 1, 'রামপুরা', '1219', 35.00, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(23, 1, 'বাড্ডা', '1212', 40.00, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(24, 1, 'খিলগাঁও', '1219', 35.00, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(25, 1, 'মালিবাগ', '1217', 30.00, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(26, 1, 'বারিধারা', '1212', 50.00, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58');

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `country_of_origin` varchar(100) DEFAULT 'Bangladesh',
  `established_year` year(4) DEFAULT NULL,
  `social_media` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_media`)),
  `is_active` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`, `slug`, `logo`, `description`, `website`, `country_of_origin`, `established_year`, `social_media`, `is_active`, `is_featured`, `sort_order`, `created_at`) VALUES
(1, 'ACI', 'aci', NULL, 'Leading Bangladeshi consumer goods company', NULL, 'Bangladesh', NULL, NULL, 1, 1, 1, '2026-02-04 13:13:58'),
(2, 'Square', 'square', NULL, 'Bangladeshi pharmaceutical and consumer products', NULL, 'Bangladesh', NULL, NULL, 1, 1, 2, '2026-02-04 13:13:58'),
(3, 'PRAN', 'pran', NULL, 'Popular food and beverage brand in Bangladesh', NULL, 'Bangladesh', NULL, NULL, 1, 1, 3, '2026-02-04 13:13:58'),
(4, 'Fresh', 'fresh', NULL, 'Dairy and food products brand', NULL, 'Bangladesh', NULL, NULL, 1, 1, 4, '2026-02-04 13:13:58'),
(5, 'Radhuni', 'radhuni', NULL, 'Spices and food ingredients brand', NULL, 'Bangladesh', NULL, NULL, 1, 1, 5, '2026-02-04 13:13:58'),
(6, 'Teer', 'teer', NULL, 'Soybean oil and cooking oil brand', NULL, 'Bangladesh', NULL, NULL, 1, 1, 6, '2026-02-04 13:13:58'),
(7, 'Bashundhara', 'bashundhara', NULL, 'Tissue paper and household products', NULL, 'Bangladesh', NULL, NULL, 1, 1, 7, '2026-02-04 13:13:58'),
(8, 'Meril', 'meril', NULL, 'Personal care and cosmetics brand', NULL, 'Bangladesh', NULL, NULL, 1, 1, 8, '2026-02-04 13:13:58'),
(9, 'Nestle', 'nestle', NULL, 'International food and beverage company', NULL, 'Switzerland', NULL, NULL, 1, 0, 9, '2026-02-04 13:13:58'),
(10, 'Unilever', 'unilever', NULL, 'International consumer goods company', NULL, 'Netherlands', NULL, NULL, 1, 0, 10, '2026-02-04 13:13:58');

-- --------------------------------------------------------

--
-- Table structure for table `business_types`
--

CREATE TABLE `business_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT 15.00,
  `min_commission_rate` decimal(5,2) DEFAULT 5.00,
  `max_commission_rate` decimal(5,2) DEFAULT 25.00,
  `icon` varchar(255) DEFAULT NULL,
  `color` varchar(7) DEFAULT '#ff5722',
  `requires_license` tinyint(1) DEFAULT 0,
  `requires_food_permit` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `business_types`
--

INSERT INTO `business_types` (`id`, `name`, `slug`, `description`, `commission_rate`, `min_commission_rate`, `max_commission_rate`, `icon`, `color`, `requires_license`, `requires_food_permit`, `is_active`, `sort_order`) VALUES
(1, 'Restaurant', 'restaurant', 'Traditional restaurants and food preparation business', 15.00, 10.00, 20.00, 'fas fa-utensils', '#ff5722', 1, 1, 1, 1),
(2, 'Fast Food', 'fast-food', 'Quick service restaurants and fast food chains', 12.00, 8.00, 18.00, 'fas fa-hamburger', '#ff9800', 1, 1, 1, 2),
(3, 'Pizza', 'pizza', 'Pizza restaurants and Italian cuisine', 15.00, 10.00, 20.00, 'fas fa-pizza-slice', '#f44336', 1, 1, 1, 3),
(4, 'Chinese', 'chinese', 'Chinese and Asian cuisine restaurants', 15.00, 10.00, 20.00, 'fas fa-fish', '#4caf50', 1, 1, 1, 4),
(5, 'Indian', 'indian', 'Indian cuisine and spicy food restaurants', 15.00, 10.00, 20.00, 'fas fa-pepper-hot', '#ff5722', 1, 1, 1, 5),
(6, 'Cafe', 'cafe', 'Coffee shops, tea houses and light meal cafes', 10.00, 5.00, 15.00, 'fas fa-coffee', '#795548', 0, 1, 1, 6),
(7, 'Bakery', 'bakery', 'Bakeries, confectioneries and sweet shops', 12.00, 8.00, 18.00, 'fas fa-birthday-cake', '#e91e63', 1, 1, 1, 7),
(8, 'Grocery Store', 'grocery', 'Grocery stores and daily essentials', 8.00, 5.00, 12.00, 'fas fa-shopping-basket', '#4caf50', 1, 0, 1, 8),
(9, 'Pharmacy', 'pharmacy', 'Pharmacies and medicine stores', 5.00, 3.00, 8.00, 'fas fa-pills', '#2196f3', 1, 0, 1, 9),
(10, 'Butcher Shop', 'butcher', 'Meat and poultry shops', 10.00, 8.00, 15.00, 'fas fa-drumstick-bite', '#795548', 1, 1, 1, 10);

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `addons` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Selected addons' CHECK (json_valid(`addons`)),
  `special_instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `banner_image` varchar(255) DEFAULT NULL,
  `category_type` enum('food','grocery','both') DEFAULT 'both',
  `level` tinyint(1) DEFAULT 1 COMMENT '1=Main, 2=Sub, 3=Sub-sub',
  `commission_rate` decimal(5,2) DEFAULT NULL COMMENT 'Override default commission',
  `tax_rate` decimal(5,2) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `seo_keywords` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `description`, `image`, `icon`, `banner_image`, `category_type`, `level`, `commission_rate`, `tax_rate`, `sort_order`, `is_active`, `is_featured`, `seo_title`, `seo_description`, `seo_keywords`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Bengali Cuisine', 'bengali-cuisine', 'Traditional Bengali dishes and local favorites', 'uploads/categories/category_1770495195_69879cdb13ec6.jpg', 'fas fa-fish', NULL, 'food', 1, NULL, NULL, 1, 1, 1, NULL, NULL, NULL, '2026-02-04 13:13:58', '2026-02-07 15:13:15'),
(2, NULL, 'Rice &amp; Biriyani', 'rice-biriyani', 'Rice dishes, biriyani, pulao and traditional rice meals', 'uploads/categories/category_1770495294_69879d3e04118.png', 'fas fa-seedling', NULL, 'food', 1, NULL, NULL, 2, 1, 1, NULL, NULL, NULL, '2026-02-04 13:13:58', '2026-02-07 15:14:54'),
(3, NULL, 'Curry &amp; Gravy', 'curry-gravy', 'Various curries, dal, and gravy-based dishes', 'uploads/categories/category_1770495403_69879dab8fb8b.jpg', 'fas fa-bowl-food', NULL, 'food', 1, NULL, NULL, 3, 1, 1, NULL, NULL, NULL, '2026-02-04 13:13:58', '2026-02-07 15:16:43'),
(4, NULL, 'Fish &amp; Seafood', 'fish-seafood', 'Fresh fish, prawns and seafood dishes', 'uploads/categories/category_1770495466_69879dead76fd.jpg', 'fas fa-fish', NULL, 'food', 1, NULL, NULL, 4, 1, 1, NULL, NULL, NULL, '2026-02-04 13:13:58', '2026-02-07 15:17:46'),
(5, NULL, 'Meat &amp; Poultry', 'meat-poultry', 'Chicken, beef, mutton and other meat dishes', 'uploads/categories/category_1770495543_69879e3793583.png', 'fas fa-drumstick-bite', NULL, 'food', 1, NULL, NULL, 5, 1, 1, NULL, NULL, NULL, '2026-02-04 13:13:58', '2026-02-07 15:19:03'),
(6, NULL, 'Vegetarian', 'vegetarian', 'Pure vegetarian dishes and plant-based meals', 'uploads/categories/category_1770495576_69879e58abf77.jpg', 'fas fa-leaf', NULL, 'food', 1, NULL, NULL, 6, 1, 1, NULL, NULL, NULL, '2026-02-04 13:13:58', '2026-02-07 15:19:36'),
(7, NULL, 'Street Food', 'street-food', 'Popular Bangladeshi street food and snacks', 'uploads/categories/category_1770495608_69879e78e25df.jpg', 'fas fa-hotdog', NULL, 'food', 1, NULL, NULL, 7, 1, 1, NULL, NULL, NULL, '2026-02-04 13:13:58', '2026-02-07 15:20:08'),
(8, NULL, 'Sweets &amp; Desserts', 'sweets-desserts', 'Traditional Bengali sweets and desserts', 'uploads/categories/category_1770495683_69879ec3ce12b.png', 'fas fa-ice-cream', NULL, 'food', 1, NULL, NULL, 8, 1, 1, NULL, NULL, NULL, '2026-02-04 13:13:58', '2026-02-07 15:21:23'),
(9, NULL, 'Beverages', 'beverages', 'Tea, coffee, juices and traditional drinks', 'uploads/categories/category_1770495715_69879ee3df201.jpg', 'fas fa-glass-whiskey', NULL, 'both', 1, NULL, NULL, 9, 1, 1, NULL, NULL, NULL, '2026-02-04 13:13:58', '2026-02-07 15:21:55'),
(10, NULL, 'Breakfast', 'breakfast', 'Traditional breakfast items and morning meals', 'uploads/categories/category_1770495748_69879f0403187.jpg', 'fas fa-egg', NULL, 'food', 1, NULL, NULL, 10, 1, 1, NULL, NULL, NULL, '2026-02-04 13:13:58', '2026-02-07 15:22:28'),
(11, NULL, 'Fast Food', 'fast-food', 'Burgers, pizza, fried chicken and quick meals', 'uploads/categories/category_1770495819_69879f4bcf1c9.png', 'fas fa-hamburger', NULL, 'food', 1, NULL, NULL, 11, 1, 1, NULL, NULL, NULL, '2026-02-04 13:13:58', '2026-02-07 15:23:39'),
(12, NULL, 'International', 'international', 'Chinese, Indian, Thai and other international cuisines', 'uploads/categories/category_1770495861_69879f75b58e5.jpeg', 'fas fa-globe-asia', NULL, 'food', 1, NULL, NULL, 12, 1, 1, NULL, NULL, NULL, '2026-02-04 13:13:58', '2026-02-07 15:24:21'),
(13, NULL, 'Fresh Produce', 'fresh-produce', 'Fresh vegetables, fruits and seasonal produce', 'uploads/categories/category_1770497170_6987a4928c39f.png', 'fas fa-apple-alt', NULL, 'grocery', 1, NULL, NULL, 13, 1, 1, NULL, NULL, NULL, '2026-02-04 13:13:58', '2026-02-07 15:46:10'),
(14, NULL, 'Rice &amp; Grains', 'rice-grains', 'Rice, wheat, lentils and grain products', 'uploads/categories/category_1770497234_6987a4d2aea75.png', 'fas fa-seedling', NULL, 'grocery', 1, NULL, NULL, 14, 1, 1, NULL, NULL, NULL, '2026-02-04 13:13:58', '2026-02-07 15:47:14'),
(15, NULL, 'Spices &amp; Seasonings', 'spices-seasonings', 'Spices, masala, salt and cooking seasonings', 'uploads/categories/category_1770497269_6987a4f52df52.jpg', 'fas fa-pepper-hot', NULL, 'grocery', 1, NULL, NULL, 15, 1, 1, NULL, NULL, NULL, '2026-02-04 13:13:58', '2026-02-07 15:47:49'),
(16, NULL, 'Dairy &amp; Eggs', 'dairy-eggs', 'Milk, yogurt, cheese, butter and fresh eggs', 'uploads/categories/category_1770497102_6987a44e426e4.png', 'fas fa-cheese', NULL, 'grocery', 1, NULL, NULL, 16, 1, 1, NULL, NULL, NULL, '2026-02-04 13:13:58', '2026-02-07 15:45:02'),
(17, NULL, 'Meat &amp; Fish', 'meat-fish', 'Fresh meat, chicken, fish and frozen items', 'uploads/categories/category_1770496949_6987a3b5b2c75.png', 'fas fa-fish', NULL, 'grocery', 1, NULL, NULL, 17, 1, 1, NULL, NULL, NULL, '2026-02-04 13:13:58', '2026-02-07 15:42:29'),
(18, NULL, 'Cooking Oil', 'cooking-oil', 'Mustard oil, soybean oil and cooking fats', 'uploads/categories/category_1770497006_6987a3ee354af.png', 'fas fa-tint', NULL, 'grocery', 1, NULL, NULL, 18, 1, 1, NULL, NULL, NULL, '2026-02-04 13:13:58', '2026-02-07 15:43:26'),
(19, NULL, 'Snacks &amp; Biscuits', 'snacks-biscuits', 'Packaged snacks, biscuits and ready-to-eat items', 'uploads/categories/category_1770496892_6987a37c28fda.jpg', 'fas fa-cookie-bite', NULL, 'grocery', 1, NULL, NULL, 19, 1, 1, NULL, NULL, NULL, '2026-02-04 13:13:58', '2026-02-07 15:41:32'),
(20, NULL, 'Personal Care', 'personal-care', 'Soap, shampoo, toothpaste and hygiene products', 'uploads/categories/category_1770496857_6987a3591ebb7.png', 'fas fa-pump-soap', NULL, 'grocery', 1, NULL, NULL, 20, 1, 1, NULL, NULL, NULL, '2026-02-04 13:13:58', '2026-02-07 15:40:57'),
(21, NULL, 'Household Items', 'household-items', 'Cleaning supplies, detergent and home essentials', 'uploads/categories/category_1770496792_6987a318481f1.jpg', 'fas fa-home', NULL, 'grocery', 1, NULL, NULL, 21, 1, 1, NULL, NULL, NULL, '2026-02-04 13:13:58', '2026-02-07 15:39:52'),
(22, NULL, 'Baby Care', 'baby-care', 'Baby food, diapers and child care products', 'uploads/categories/category_1770496759_6987a2f7610b8.jpg', 'fas fa-baby', NULL, 'grocery', 1, NULL, NULL, 22, 1, 1, NULL, NULL, NULL, '2026-02-04 13:13:58', '2026-02-07 15:39:19'),
(23, NULL, 'Grocery', '', '', 'uploads/categories/category_1770569519_6988bf2fb4293.png', '', NULL, 'both', 1, NULL, NULL, 0, 1, 0, NULL, NULL, NULL, '2026-02-08 11:51:59', '2026-02-08 11:51:59');

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `id` int(11) NOT NULL,
  `state_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `timezone` varchar(50) DEFAULT 'Asia/Dhaka',
  `postal_code_format` varchar(50) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`id`, `state_id`, `name`, `latitude`, `longitude`, `timezone`, `postal_code_format`, `status`) VALUES
(1, 1, 'Dhaka', 23.81030000, 90.41250000, 'Asia/Dhaka', NULL, 1),
(2, 1, 'Gazipur', 23.99990000, 90.42030000, 'Asia/Dhaka', NULL, 1),
(3, 1, 'Narayanganj', 23.62380000, 90.49900000, 'Asia/Dhaka', NULL, 1),
(4, 1, 'Savar', 23.85830000, 90.26670000, 'Asia/Dhaka', NULL, 1),
(5, 2, 'Chittagong', 22.35690000, 91.78320000, 'Asia/Dhaka', NULL, 1),
(6, 2, 'Coxs Bazar', 21.42720000, 92.00580000, 'Asia/Dhaka', NULL, 1),
(7, 3, 'Rajshahi', 24.37450000, 88.60420000, 'Asia/Dhaka', NULL, 1),
(8, 4, 'Khulna', 22.84560000, 89.54030000, 'Asia/Dhaka', NULL, 1),
(9, 5, 'Barisal', 22.70100000, 90.35350000, 'Asia/Dhaka', NULL, 1),
(10, 6, 'Sylhet', 24.89490000, 91.86870000, 'Asia/Dhaka', NULL, 1),
(11, 7, 'Rangpur', 25.74390000, 89.27520000, 'Asia/Dhaka', NULL, 1),
(12, 8, 'Mymensingh', 24.74710000, 90.42030000, 'Asia/Dhaka', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `color_palettes`
--

CREATE TABLE `color_palettes` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `palette_type` enum('primary','festival','seasonal','brand','custom') DEFAULT 'custom',
  `primary_color` varchar(7) NOT NULL,
  `secondary_color` varchar(7) DEFAULT NULL,
  `accent_color` varchar(7) DEFAULT NULL,
  `background_color` varchar(7) DEFAULT NULL,
  `text_color` varchar(7) DEFAULT NULL,
  `success_color` varchar(7) DEFAULT '#28a745',
  `warning_color` varchar(7) DEFAULT '#ffc107',
  `error_color` varchar(7) DEFAULT '#dc3545',
  `info_color` varchar(7) DEFAULT '#17a2b8',
  `additional_colors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional color definitions' CHECK (json_valid(`additional_colors`)),
  `is_active` tinyint(1) DEFAULT 1,
  `is_default` tinyint(1) DEFAULT 0,
  `usage_count` int(11) DEFAULT 0,
  `preview_image` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `color_palettes`
--

INSERT INTO `color_palettes` (`id`, `name`, `description`, `palette_type`, `primary_color`, `secondary_color`, `accent_color`, `background_color`, `text_color`, `success_color`, `warning_color`, `error_color`, `info_color`, `additional_colors`, `is_active`, `is_default`, `usage_count`, `preview_image`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'ordivo_original', 'Original ORDIVO color scheme', 'brand', '#e21b70', '#ff6b9d', '#ff5722', '#ffffff', '#333333', '#28a745', '#ffc107', '#dc3545', '#17a2b8', NULL, 1, 1, 0, NULL, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(2, 'eid_golden', 'Golden theme for Eid celebrations', 'festival', '#d4af37', '#ffd700', '#ff6347', '#f8f8ff', '#2f4f4f', '#28a745', '#ffc107', '#dc3545', '#17a2b8', NULL, 1, 0, 0, NULL, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(3, 'boishakh_traditional', 'Traditional Bengali colors for Pohela Boishakh', 'festival', '#ff6b35', '#f7931e', '#ffcc02', '#fff8dc', '#8b4513', '#28a745', '#ffc107', '#dc3545', '#17a2b8', NULL, 1, 0, 0, NULL, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(4, 'durga_vibrant', 'Vibrant colors for Durga Puja', 'festival', '#ff1744', '#ffc107', '#4caf50', '#fff3e0', '#d32f2f', '#28a745', '#ffc107', '#dc3545', '#17a2b8', NULL, 1, 0, 0, NULL, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(5, 'bangladesh_patriotic', 'Green and red patriotic colors', 'festival', '#006a4e', '#f42a41', '#ffcc02', '#ffffff', '#2e7d32', '#28a745', '#ffc107', '#dc3545', '#17a2b8', NULL, 1, 0, 0, NULL, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(6, 'ramadan_serene', 'Peaceful colors for Ramadan', 'seasonal', '#4a90e2', '#7b68ee', '#20b2aa', '#f0f8ff', '#2f4f4f', '#28a745', '#ffc107', '#dc3545', '#17a2b8', NULL, 1, 0, 0, NULL, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59');

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(3) NOT NULL,
  `currency` varchar(10) DEFAULT 'BDT',
  `currency_symbol` varchar(5) DEFAULT '৳',
  `phone_code` varchar(10) DEFAULT '+880',
  `flag_icon` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`id`, `name`, `code`, `currency`, `currency_symbol`, `phone_code`, `flag_icon`, `status`, `sort_order`) VALUES
(1, 'Bangladesh', 'BD', 'BDT', '৳', '+880', NULL, 1, 1),
(2, 'India', 'IN', 'INR', '₹', '+91', NULL, 1, 2),
(3, 'Pakistan', 'PK', 'PKR', '₨', '+92', NULL, 1, 3);

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('fixed','percentage') NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) DEFAULT 0.00,
  `max_discount_amount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_limit_per_customer` int(11) DEFAULT 1,
  `used_count` int(11) DEFAULT 0,
  `applicable_to` enum('all','vendors','products','categories') DEFAULT 'all',
  `applicable_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'IDs of applicable vendors/products/categories' CHECK (json_valid(`applicable_ids`)),
  `valid_from` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `valid_until` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `name`, `description`, `type`, `value`, `min_order_amount`, `max_discount_amount`, `usage_limit`, `usage_limit_per_customer`, `used_count`, `applicable_to`, `applicable_ids`, `valid_from`, `valid_until`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'WELCOME100', 'স্বাগতম অফার', 'নতুন গ্রাহকদের জন্য ১০০ টাকা ছাড়', 'fixed', 100.00, 300.00, NULL, 1000, 1, 0, 'all', NULL, '2026-02-04 13:13:59', '2026-03-06 13:13:59', 1, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(2, 'SAVE20', '২০% ছাড়', '৫০০ টাকার উপরে অর্ডারে ২০% ছাড়', 'percentage', 20.00, 500.00, NULL, 500, 1, 0, 'all', NULL, '2026-02-04 13:13:59', '2026-02-19 13:13:59', 1, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(3, 'FREEDELIVERY', 'ফ্রি ডেলিভারি', '৪০০ টাকার উপরে অর্ডারে ফ্রি ডেলিভারি', 'fixed', 50.00, 400.00, NULL, 200, 1, 0, 'all', NULL, '2026-02-04 13:13:59', '2026-02-11 13:13:59', 1, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(4, 'FRIDAY50', 'জুমার অফার', 'প্রতি জুমবারে ৫০ টাকা ছাড়', 'fixed', 50.00, 250.00, NULL, 100, 2, 0, 'all', NULL, '2026-02-04 13:13:59', '2026-03-06 13:13:59', 1, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(5, 'BIRIYANI15', 'বিরিয়ানি অফার', 'বিরিয়ানিতে ১৫% ছাড়', 'percentage', 15.00, 200.00, NULL, 300, 1, 0, 'all', NULL, '2026-02-04 13:13:59', '2026-02-14 13:13:59', 1, '2026-02-04 13:13:59', '2026-02-04 13:13:59');

-- --------------------------------------------------------

--
-- Table structure for table `coupon_usage`
--

CREATE TABLE `coupon_usage` (
  `id` int(11) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_addresses`
--

CREATE TABLE `customer_addresses` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `type` enum('home','work','other') DEFAULT 'home',
  `label` varchar(50) DEFAULT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `landmark` varchar(100) DEFAULT NULL,
  `area_id` int(11) DEFAULT NULL,
  `city_id` int(11) NOT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `delivery_instructions` text DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_favorites`
--

CREATE TABLE `customer_favorites` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `type` enum('vendor','product') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `custom_styles`
--

CREATE TABLE `custom_styles` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `style_type` enum('css','javascript','html') NOT NULL,
  `target_pages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Pages where this style should be applied' CHECK (json_valid(`target_pages`)),
  `content` longtext NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `load_order` int(11) DEFAULT 0,
  `is_minified` tinyint(1) DEFAULT 0,
  `file_path` varchar(255) DEFAULT NULL COMMENT 'Path to compiled/minified file',
  `dependencies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Required CSS/JS dependencies' CHECK (json_valid(`dependencies`)),
  `responsive_breakpoints` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`responsive_breakpoints`)),
  `browser_compatibility` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`browser_compatibility`)),
  `version` varchar(20) DEFAULT '1.0',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_assignments`
--

CREATE TABLE `delivery_assignments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `rider_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `accepted_at` timestamp NULL DEFAULT NULL,
  `picked_up_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `status` enum('assigned','accepted','picked_up','delivered','cancelled','failed') DEFAULT 'assigned',
  `pickup_location` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pickup_location`)),
  `delivery_location` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`delivery_location`)),
  `estimated_distance` decimal(8,2) DEFAULT NULL,
  `actual_distance` decimal(8,2) DEFAULT NULL,
  `delivery_fee` decimal(10,2) DEFAULT 0.00,
  `rider_commission` decimal(10,2) DEFAULT 0.00,
  `cancellation_reason` text DEFAULT NULL,
  `delivery_notes` text DEFAULT NULL,
  `customer_rating` tinyint(1) DEFAULT NULL,
  `customer_feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_riders`
--

CREATE TABLE `delivery_riders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vehicle_type` enum('bicycle','motorcycle','car','van') DEFAULT 'motorcycle',
  `vehicle_number` varchar(50) DEFAULT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `license_expiry` date DEFAULT NULL,
  `insurance_number` varchar(100) DEFAULT NULL,
  `insurance_expiry` date DEFAULT NULL,
  `current_location_lat` decimal(10,8) DEFAULT NULL,
  `current_location_lng` decimal(11,8) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `is_online` tinyint(1) DEFAULT 0,
  `total_deliveries` int(11) DEFAULT 0,
  `successful_deliveries` int(11) DEFAULT 0,
  `rating` decimal(3,2) DEFAULT 0.00,
  `total_earnings` decimal(10,2) DEFAULT 0.00,
  `commission_rate` decimal(5,2) DEFAULT 10.00,
  `max_delivery_distance` decimal(8,2) DEFAULT 15.00,
  `emergency_contact` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`emergency_contact`)),
  `documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'License, insurance, etc.' CHECK (json_valid(`documents`)),
  `status` enum('active','inactive','suspended','pending_verification') DEFAULT 'pending_verification',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `design_components`
--

CREATE TABLE `design_components` (
  `id` int(11) NOT NULL,
  `component_type` varchar(50) NOT NULL COMMENT 'card, button, form, header, footer, etc.',
  `component_name` varchar(100) NOT NULL,
  `display_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `html_template` longtext DEFAULT NULL,
  `css_styles` longtext DEFAULT NULL,
  `js_behavior` longtext DEFAULT NULL,
  `default_props` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Default properties/attributes' CHECK (json_valid(`default_props`)),
  `customizable_props` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Properties that can be customized' CHECK (json_valid(`customizable_props`)),
  `responsive_styles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`responsive_styles`)),
  `is_active` tinyint(1) DEFAULT 1,
  `category` varchar(50) DEFAULT NULL COMMENT 'navigation, content, form, etc.',
  `preview_image` varchar(255) DEFAULT NULL,
  `usage_count` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `design_components`
--

INSERT INTO `design_components` (`id`, `component_type`, `component_name`, `display_name`, `description`, `html_template`, `css_styles`, `js_behavior`, `default_props`, `customizable_props`, `responsive_styles`, `is_active`, `category`, `preview_image`, `usage_count`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'card', 'restaurant_card', 'Restaurant Card', 'Card component for displaying restaurant information', '<div class=\"restaurant-card\"><img src=\"{image}\" alt=\"{name}\"><div class=\"card-content\"><h3>{name}</h3><p>{description}</p><div class=\"rating\">{rating}</div></div></div>', '.restaurant-card { border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden; transition: transform 0.2s; } .restaurant-card:hover { transform: translateY(-2px); }', NULL, NULL, NULL, NULL, 1, 'content', NULL, 0, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(2, 'button', 'primary_button', 'Primary Button', 'Main action button with ORDIVO styling', '<button class=\"btn-primary\" onclick=\"{action}\">{text}</button>', '.btn-primary { background: #e21b70; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; } .btn-primary:hover { background: #c91660; transform: translateY(-1px); }', NULL, NULL, NULL, NULL, 1, 'form', NULL, 0, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(3, 'header', 'main_header', 'Main Header', 'Site header with navigation and search', '<header class=\"main-header\"><div class=\"logo\">{logo}</div><nav>{navigation}</nav><div class=\"search\">{search}</div><div class=\"user-menu\">{user_menu}</div></header>', '.main-header { background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }', NULL, NULL, NULL, NULL, 1, 'navigation', NULL, 0, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59');

-- --------------------------------------------------------

--
-- Table structure for table `file_access_logs`
--

CREATE TABLE `file_access_logs` (
  `id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `access_type` enum('view','download','upload','delete') NOT NULL,
  `referrer` varchar(500) DEFAULT NULL,
  `response_code` int(11) DEFAULT NULL,
  `bytes_served` bigint(20) DEFAULT NULL,
  `access_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `file_associations`
--

CREATE TABLE `file_associations` (
  `id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `entity_type` varchar(50) NOT NULL COMMENT 'product, vendor, user, order, etc.',
  `entity_id` int(11) NOT NULL,
  `association_type` varchar(50) DEFAULT 'primary' COMMENT 'primary, gallery, thumbnail, document',
  `sort_order` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `file_uploads`
--

CREATE TABLE `file_uploads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `file_type` enum('image','document','video','audio','other') NOT NULL DEFAULT 'image',
  `category` varchar(50) DEFAULT NULL COMMENT 'avatar, product, vendor, document, etc.',
  `original_name` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) DEFAULT NULL COMMENT 'Size in bytes',
  `mime_type` varchar(100) DEFAULT NULL,
  `file_extension` varchar(10) DEFAULT NULL,
  `dimensions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'width, height for images' CHECK (json_valid(`dimensions`)),
  `alt_text` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `is_compressed` tinyint(1) DEFAULT 0,
  `compression_ratio` decimal(5,2) DEFAULT NULL,
  `thumbnail_path` varchar(500) DEFAULT NULL,
  `medium_path` varchar(500) DEFAULT NULL,
  `large_path` varchar(500) DEFAULT NULL,
  `download_count` int(11) DEFAULT 0,
  `last_accessed` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'EXIF data, etc.' CHECK (json_valid(`metadata`)),
  `virus_scan_status` enum('pending','clean','infected','error') DEFAULT 'pending',
  `virus_scan_date` timestamp NULL DEFAULT NULL,
  `storage_provider` enum('local','aws_s3','cloudinary','other') DEFAULT 'local',
  `external_url` varchar(500) DEFAULT NULL,
  `status` enum('active','deleted','archived') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ingredients`
--

CREATE TABLE `ingredients` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL COMMENT 'Vegetables, Meat, Dairy, Spices, Rice, Fish, etc.',
  `unit` varchar(20) NOT NULL COMMENT 'kg, g, l, ml, pcs, etc.',
  `base_cost` decimal(10,2) DEFAULT 0.00,
  `current_market_price` decimal(10,2) DEFAULT 0.00,
  `shelf_life_days` int(11) DEFAULT NULL,
  `storage_instructions` text DEFAULT NULL,
  `allergen_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allergen_info`)),
  `nutritional_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`nutritional_info`)),
  `is_perishable` tinyint(1) DEFAULT 1,
  `requires_refrigeration` tinyint(1) DEFAULT 0,
  `seasonal_availability` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Months when available' CHECK (json_valid(`seasonal_availability`)),
  `origin_country` varchar(100) DEFAULT 'Bangladesh',
  `is_organic` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ingredients`
--

INSERT INTO `ingredients` (`id`, `name`, `category`, `unit`, `base_cost`, `current_market_price`, `shelf_life_days`, `storage_instructions`, `allergen_info`, `nutritional_info`, `is_perishable`, `requires_refrigeration`, `seasonal_availability`, `origin_country`, `is_organic`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'বাসমতি চাল', 'Rice', 'kg', 80.00, 85.00, 365, NULL, NULL, NULL, 0, 0, NULL, 'Bangladesh', 0, 1, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(2, 'মুরগির মাংস', 'Meat', 'kg', 180.00, 200.00, 2, NULL, NULL, NULL, 1, 1, NULL, 'Bangladesh', 0, 1, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(3, 'পেঁয়াজ', 'Vegetables', 'kg', 25.00, 30.00, 30, NULL, NULL, NULL, 1, 0, NULL, 'Bangladesh', 0, 1, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(4, 'রসুন', 'Vegetables', 'kg', 120.00, 140.00, 60, NULL, NULL, NULL, 1, 0, NULL, 'Bangladesh', 0, 1, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(5, 'আদা', 'Spices', 'kg', 80.00, 90.00, 45, NULL, NULL, NULL, 1, 1, NULL, 'Bangladesh', 0, 1, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(6, 'হলুদ গুঁড়া', 'Spices', 'kg', 200.00, 220.00, 730, NULL, NULL, NULL, 0, 0, NULL, 'Bangladesh', 0, 1, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(7, 'লাল মরিচের গুঁড়া', 'Spices', 'kg', 300.00, 320.00, 365, NULL, NULL, NULL, 0, 0, NULL, 'Bangladesh', 0, 1, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(8, 'গরম মসলা', 'Spices', 'kg', 400.00, 450.00, 365, NULL, NULL, NULL, 0, 0, NULL, 'Bangladesh', 0, 1, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(9, 'সরিষার তেল', 'Oil', 'l', 120.00, 130.00, 365, NULL, NULL, NULL, 0, 0, NULL, 'Bangladesh', 0, 1, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(10, 'টমেটো', 'Vegetables', 'kg', 40.00, 50.00, 7, NULL, NULL, NULL, 1, 0, NULL, 'Bangladesh', 0, 1, '2026-02-04 13:13:59', '2026-02-04 13:13:59');

-- --------------------------------------------------------

--
-- Table structure for table `ingredient_usage_optimization`
--

CREATE TABLE `ingredient_usage_optimization` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `optimization_type` enum('portion_control','batch_cooking','cross_utilization','substitution') NOT NULL,
  `current_usage_rate` decimal(10,3) DEFAULT NULL COMMENT 'Usage per day/week',
  `optimal_usage_rate` decimal(10,3) DEFAULT NULL,
  `waste_reduction_potential` decimal(5,2) DEFAULT NULL COMMENT 'Percentage',
  `cost_saving_potential` decimal(10,2) DEFAULT NULL,
  `implementation_difficulty` enum('easy','medium','hard') DEFAULT 'medium',
  `suggested_actions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`suggested_actions`)),
  `implementation_status` enum('suggested','planned','in_progress','implemented','rejected') DEFAULT 'suggested',
  `implemented_by` int(11) DEFAULT NULL,
  `implemented_at` timestamp NULL DEFAULT NULL,
  `results_tracked` tinyint(1) DEFAULT 0,
  `actual_waste_reduction` decimal(5,2) DEFAULT NULL,
  `actual_cost_savings` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_alerts`
--

CREATE TABLE `inventory_alerts` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `alert_type` enum('low_stock','out_of_stock','expiring_soon','expired','overstocked','reorder_point','quality_check','temperature_breach') NOT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `message` text NOT NULL,
  `current_stock` decimal(10,3) DEFAULT NULL,
  `threshold_value` decimal(10,3) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `days_until_expiry` int(11) DEFAULT NULL,
  `is_resolved` tinyint(1) DEFAULT 0,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `notification_sent` tinyint(1) DEFAULT 0,
  `notification_sent_at` timestamp NULL DEFAULT NULL,
  `auto_generated` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_batches`
--

CREATE TABLE `inventory_batches` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `batch_number` varchar(50) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `quantity` decimal(10,3) NOT NULL,
  `remaining_quantity` decimal(10,3) NOT NULL,
  `cost_per_unit` decimal(10,2) NOT NULL,
  `purchase_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `manufacturing_date` date DEFAULT NULL,
  `lot_number` varchar(100) DEFAULT NULL,
  `quality_grade` enum('A','B','C','D') DEFAULT 'A',
  `status` enum('active','expired','consumed','wasted','returned') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_categories`
--

CREATE TABLE `inventory_categories` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT '#e21b70' COMMENT 'Hex color code for UI',
  `icon` varchar(50) DEFAULT NULL COMMENT 'Font Awesome icon class',
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL COMMENT 'Stock Keeping Unit',
  `barcode` varchar(100) DEFAULT NULL,
  `unit` varchar(50) NOT NULL DEFAULT 'pcs' COMMENT 'kg, ltr, pcs, box, etc.',
  `current_stock` decimal(10,3) NOT NULL DEFAULT 0.000,
  `minimum_stock` decimal(10,3) NOT NULL DEFAULT 0.000,
  `maximum_stock` decimal(10,3) DEFAULT NULL,
  `reorder_point` decimal(10,3) DEFAULT NULL,
  `cost_per_unit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(10,2) DEFAULT NULL,
  `supplier_name` varchar(200) DEFAULT NULL,
  `supplier_contact` varchar(100) DEFAULT NULL,
  `supplier_email` varchar(100) DEFAULT NULL,
  `storage_location` varchar(100) DEFAULT NULL COMMENT 'Freezer, Dry Storage, etc.',
  `storage_temperature` varchar(50) DEFAULT NULL COMMENT 'Temperature requirements',
  `is_perishable` tinyint(1) DEFAULT 0,
  `shelf_life_days` int(11) DEFAULT NULL COMMENT 'Shelf life in days',
  `expiry_date` date DEFAULT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `manufacturing_date` date DEFAULT NULL,
  `allergen_info` text DEFAULT NULL COMMENT 'Allergen information',
  `nutritional_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Nutritional facts' CHECK (json_valid(`nutritional_info`)),
  `image_url` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `vendor_id`, `category_id`, `name`, `description`, `sku`, `barcode`, `unit`, `current_stock`, `minimum_stock`, `maximum_stock`, `reorder_point`, `cost_per_unit`, `selling_price`, `supplier_name`, `supplier_contact`, `supplier_email`, `storage_location`, `storage_temperature`, `is_perishable`, `shelf_life_days`, `expiry_date`, `batch_number`, `manufacturing_date`, `allergen_info`, `nutritional_info`, `image_url`, `notes`, `is_active`, `last_updated_by`, `created_at`, `updated_at`) VALUES
(1, 4, NULL, 'atta', 'asdfg', 'asdf', NULL, 'kg', 10.000, 8.000, NULL, NULL, 55.00, NULL, 'asdfg', '0184576565', NULL, 'asdf', NULL, 0, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, NULL, 1, 4, '2026-02-11 19:41:45', '2026-02-11 19:41:45');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transactions`
--

CREATE TABLE `inventory_transactions` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `transaction_type` enum('purchase','usage','waste','adjustment','transfer') NOT NULL,
  `quantity` decimal(10,3) NOT NULL COMMENT 'Positive for inward, negative for outward',
  `reference_type` varchar(50) DEFAULT NULL COMMENT 'order, purchase_order, waste_log, etc.',
  `reference_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_waste`
--

CREATE TABLE `inventory_waste` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `ingredient_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `quantity_wasted` decimal(10,3) NOT NULL,
  `waste_reason` enum('expired','spoiled','damaged','overproduction','contaminated','other') NOT NULL,
  `reason_description` text DEFAULT NULL,
  `cost_impact` decimal(10,2) DEFAULT 0.00,
  `reported_by` int(11) NOT NULL,
  `date_recorded` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kitchen_equipment`
--

CREATE TABLE `kitchen_equipment` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `station_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `equipment_type` enum('cooking','refrigeration','preparation','cleaning','storage','other') NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `last_maintenance` date DEFAULT NULL,
  `next_maintenance` date DEFAULT NULL,
  `maintenance_frequency` int(11) DEFAULT NULL COMMENT 'Days between maintenance',
  `status` enum('operational','maintenance_required','under_maintenance','out_of_order','retired') DEFAULT 'operational',
  `energy_rating` varchar(10) DEFAULT NULL,
  `capacity` varchar(100) DEFAULT NULL,
  `operating_cost_per_hour` decimal(8,2) DEFAULT NULL,
  `maintenance_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kitchen_equipment`
--

INSERT INTO `kitchen_equipment` (`id`, `vendor_id`, `station_id`, `name`, `equipment_type`, `brand`, `model`, `serial_number`, `purchase_date`, `warranty_expiry`, `last_maintenance`, `next_maintenance`, `maintenance_frequency`, `status`, `energy_rating`, `capacity`, `operating_cost_per_hour`, `maintenance_notes`, `created_at`, `updated_at`) VALUES
(1, 0, 2, 'গ্যাস চুলা ১', 'cooking', 'Butterfly', NULL, NULL, NULL, NULL, NULL, '2026-03-06', NULL, 'operational', NULL, NULL, NULL, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(2, 0, 2, 'প্রেশার কুকার', 'cooking', 'Hawkins', NULL, NULL, NULL, NULL, NULL, '2026-04-05', NULL, 'operational', NULL, NULL, NULL, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(3, 0, NULL, 'রেফ্রিজারেটর', 'refrigeration', 'Walton', NULL, NULL, NULL, NULL, NULL, '2026-05-05', NULL, 'operational', NULL, NULL, NULL, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59');

-- --------------------------------------------------------

--
-- Table structure for table `kitchen_performance_metrics`
--

CREATE TABLE `kitchen_performance_metrics` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `total_orders_processed` int(11) DEFAULT 0,
  `average_preparation_time` decimal(8,2) DEFAULT NULL COMMENT 'Minutes',
  `on_time_completion_rate` decimal(5,2) DEFAULT NULL COMMENT 'Percentage',
  `quality_score` decimal(3,2) DEFAULT NULL COMMENT 'Average quality rating',
  `waste_percentage` decimal(5,2) DEFAULT NULL,
  `ingredient_utilization_rate` decimal(5,2) DEFAULT NULL,
  `energy_consumption` decimal(10,2) DEFAULT NULL COMMENT 'kWh',
  `labor_hours` decimal(8,2) DEFAULT NULL,
  `cost_per_order` decimal(8,2) DEFAULT NULL,
  `revenue_per_order` decimal(8,2) DEFAULT NULL,
  `profit_margin` decimal(5,2) DEFAULT NULL,
  `customer_satisfaction_score` decimal(3,2) DEFAULT NULL,
  `equipment_downtime_minutes` int(11) DEFAULT 0,
  `staff_productivity_score` decimal(3,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kitchen_stations`
--

CREATE TABLE `kitchen_stations` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `station_type` enum('prep','cooking','plating','packaging','cleaning') NOT NULL,
  `capacity` int(11) DEFAULT 1 COMMENT 'Number of orders can handle simultaneously',
  `equipment_list` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`equipment_list`)),
  `assigned_staff` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Staff IDs assigned to this station' CHECK (json_valid(`assigned_staff`)),
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kitchen_stations`
--

INSERT INTO `kitchen_stations` (`id`, `vendor_id`, `name`, `description`, `station_type`, `capacity`, `equipment_list`, `assigned_staff`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 0, 'প্রিপ স্টেশন', NULL, 'prep', 2, '[\"cutting_board\", \"knives\", \"mixing_bowls\"]', NULL, 1, 1, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(2, 0, 'রান্নার স্টেশন', NULL, 'cooking', 3, '[\"gas_stove\", \"pressure_cooker\", \"frying_pan\"]', NULL, 1, 2, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(3, 0, 'প্লেটিং স্টেশন', NULL, 'plating', 2, '[\"plates\", \"serving_spoons\", \"garnish_tools\"]', NULL, 1, 3, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(4, 0, 'প্যাকেজিং স্টেশন', NULL, 'packaging', 2, '[\"containers\", \"bags\", \"labels\"]', NULL, 1, 4, '2026-02-04 13:13:59', '2026-02-04 13:13:59');

-- --------------------------------------------------------

--
-- Table structure for table `kitchen_workflows`
--

CREATE TABLE `kitchen_workflows` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `current_station_id` int(11) DEFAULT NULL,
  `workflow_status` enum('pending','in_progress','completed','on_hold','cancelled') DEFAULT 'pending',
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `estimated_completion` timestamp NULL DEFAULT NULL,
  `actual_start_time` timestamp NULL DEFAULT NULL,
  `actual_completion_time` timestamp NULL DEFAULT NULL,
  `total_prep_time` int(11) DEFAULT NULL COMMENT 'Total preparation time in minutes',
  `delay_reason` text DEFAULT NULL,
  `quality_check_passed` tinyint(1) DEFAULT NULL,
  `quality_notes` text DEFAULT NULL,
  `assigned_chef` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kitchen_workflow_steps`
--

CREATE TABLE `kitchen_workflow_steps` (
  `id` int(11) NOT NULL,
  `workflow_id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `step_order` int(11) NOT NULL,
  `step_name` varchar(100) NOT NULL,
  `estimated_duration` int(11) DEFAULT NULL COMMENT 'Duration in minutes',
  `actual_duration` int(11) DEFAULT NULL,
  `status` enum('pending','in_progress','completed','skipped','failed') DEFAULT 'pending',
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `quality_score` tinyint(1) DEFAULT NULL COMMENT '1-5 rating'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `location_management_logs`
--

CREATE TABLE `location_management_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` enum('create','update','delete','activate','deactivate') NOT NULL,
  `location_type` enum('country','state','city','town','area','sub_area') NOT NULL,
  `location_id` int(11) NOT NULL,
  `location_name` varchar(100) NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `reason` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional notification data' CHECK (json_valid(`data`)),
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `delivery_address_id` int(11) DEFAULT NULL,
  `delivery_address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Snapshot of delivery address' CHECK (json_valid(`delivery_address`)),
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `delivery_fee` decimal(10,2) DEFAULT 0.00,
  `service_fee` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `coupon_code` varchar(50) DEFAULT NULL,
  `payment_method` enum('cash','card','bkash','nagad','rocket','upay','sure_cash','bank_transfer') DEFAULT 'cash',
  `payment_status` enum('pending','paid','failed','refunded','partially_refunded') DEFAULT 'pending',
  `order_type` enum('delivery','pickup','dine_in') DEFAULT 'delivery',
  `status` enum('pending','confirmed','preparing','ready','out_for_delivery','delivered','cancelled','refunded') DEFAULT 'pending',
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `prepared_at` timestamp NULL DEFAULT NULL,
  `picked_up_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `special_instructions` text DEFAULT NULL,
  `estimated_delivery_time` int(11) DEFAULT NULL COMMENT 'in minutes',
  `actual_delivery_time` int(11) DEFAULT NULL COMMENT 'in minutes',
  `customer_rating` tinyint(1) DEFAULT NULL,
  `customer_review` text DEFAULT NULL,
  `delivery_rider_id` int(11) DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT 15.00,
  `commission_amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `product_name` varchar(150) NOT NULL COMMENT 'Snapshot of product name',
  `variant_name` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `addons` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Selected addons with prices' CHECK (json_valid(`addons`)),
  `special_instructions` text DEFAULT NULL,
  `preparation_status` enum('pending','preparing','ready','served') DEFAULT 'pending',
  `prepared_at` timestamp NULL DEFAULT NULL,
  `prepared_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `page_layouts`
--

CREATE TABLE `page_layouts` (
  `id` int(11) NOT NULL,
  `page_name` varchar(100) NOT NULL COMMENT 'homepage, product_page, vendor_page, etc.',
  `layout_name` varchar(100) NOT NULL,
  `display_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `layout_type` enum('grid','list','card','masonry','custom') DEFAULT 'grid',
  `is_active` tinyint(1) DEFAULT 1,
  `is_default` tinyint(1) DEFAULT 0,
  `layout_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Layout specific configuration' CHECK (json_valid(`layout_config`)),
  `css_classes` text DEFAULT NULL,
  `custom_css` longtext DEFAULT NULL,
  `custom_js` longtext DEFAULT NULL,
  `responsive_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`responsive_config`)),
  `preview_image` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `page_layouts`
--

INSERT INTO `page_layouts` (`id`, `page_name`, `layout_name`, `display_name`, `description`, `layout_type`, `is_active`, `is_default`, `layout_config`, `css_classes`, `custom_css`, `custom_js`, `responsive_config`, `preview_image`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'homepage', 'ordivo_style', 'ORDIVO Style Layout', 'Clean layout with sidebar filters and main content area', 'custom', 1, 1, '{\"sidebar_width\": \"300px\", \"main_content_padding\": \"20px\", \"card_spacing\": \"16px\", \"grid_columns\": 3}', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(2, 'homepage', 'festival_special', 'Festival Special Layout', 'Colorful layout for festival celebrations', 'custom', 1, 0, '{\"sidebar_width\": \"280px\", \"main_content_padding\": \"24px\", \"card_spacing\": \"20px\", \"grid_columns\": 2, \"banner_height\": \"200px\"}', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(3, 'product_page', 'detailed_view', 'Detailed Product View', 'Comprehensive product display with large images', 'grid', 1, 1, '{\"image_gallery_size\": \"large\", \"description_layout\": \"tabs\", \"related_products\": 4}', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(4, 'vendor_page', 'restaurant_style', 'Restaurant Style Layout', 'Layout optimized for restaurant/vendor pages', 'card', 1, 1, '{\"header_style\": \"banner\", \"menu_layout\": \"categories\", \"product_grid\": 3}', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `payment_method` enum('cash','card','bkash','nagad','rocket','upay','sure_cash','bank_transfer','wallet') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'BDT',
  `transaction_id` varchar(100) DEFAULT NULL,
  `gateway_transaction_id` varchar(100) DEFAULT NULL,
  `gateway_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gateway_response`)),
  `status` enum('pending','processing','completed','failed','cancelled','refunded') DEFAULT 'pending',
  `failure_reason` text DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `discounted_price` decimal(10,2) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `unit` varchar(20) DEFAULT 'pcs' COMMENT 'kg, g, l, ml, pcs, pack, plate, bowl, etc.',
  `unit_value` decimal(10,3) DEFAULT NULL COMMENT 'Weight/volume per unit',
  `pack_size` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `gallery` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of additional images' CHECK (json_valid(`gallery`)),
  `preparation_time` int(11) DEFAULT 15 COMMENT 'Preparation time in minutes (for food)',
  `nutritional_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`nutritional_info`)),
  `ingredients` text DEFAULT NULL,
  `allergen_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allergen_info`)),
  `storage_instructions` text DEFAULT NULL,
  `expiry_days` int(11) DEFAULT NULL,
  `is_veg` tinyint(1) DEFAULT 0,
  `is_vegan` tinyint(1) DEFAULT 0,
  `is_gluten_free` tinyint(1) DEFAULT 0,
  `is_spicy` tinyint(1) DEFAULT 0,
  `spice_level` tinyint(1) DEFAULT 0 COMMENT '0=No spice, 1=Mild, 2=Medium, 3=Hot, 4=Very Hot',
  `is_organic` tinyint(1) DEFAULT 0,
  `is_perishable` tinyint(1) DEFAULT 0,
  `requires_refrigeration` tinyint(1) DEFAULT 0,
  `is_available` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_trending` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `rating` decimal(3,2) DEFAULT 0.00,
  `total_reviews` int(11) DEFAULT 0,
  `total_orders` int(11) DEFAULT 0,
  `total_views` int(11) DEFAULT 0,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `vendor_id`, `category_id`, `brand_id`, `name`, `slug`, `description`, `short_description`, `sku`, `barcode`, `price`, `discounted_price`, `cost_price`, `unit`, `unit_value`, `pack_size`, `image`, `gallery`, `preparation_time`, `nutritional_info`, `ingredients`, `allergen_info`, `storage_instructions`, `expiry_days`, `is_veg`, `is_vegan`, `is_gluten_free`, `is_spicy`, `spice_level`, `is_organic`, `is_perishable`, `requires_refrigeration`, `is_available`, `is_featured`, `is_trending`, `sort_order`, `rating`, `total_reviews`, `total_orders`, `total_views`, `meta_title`, `meta_description`, `meta_keywords`, `created_at`, `updated_at`) VALUES
(11, 2, 2, NULL, 'Chicken Biryani', 'chicken-biryani-11-1770491269', '', NULL, 'rb_cb_456', NULL, 250.00, NULL, NULL, 'pcs', NULL, NULL, 'product_69877e39f3c00.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-07 18:02:34', '2026-02-07 19:51:47'),
(34, 2, 2, NULL, 'Mutton Biryani', '', '', NULL, 'rb_mb_456', NULL, 280.00, NULL, NULL, 'pcs', NULL, NULL, 'product_69878e0c9a968.png', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-07 19:10:04', '2026-02-07 19:44:45'),
(37, 2, 2, NULL, 'Hyderabadi Dum Biryani', 'hyderabadi-dum-biryani-1770491685', '', NULL, 'rb_hdb_456', NULL, 1200.00, NULL, NULL, 'pcs', NULL, NULL, 'product_69878f25379d2.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-07 19:14:45', '2026-02-07 19:59:00'),
(38, 2, 2, NULL, 'Persian pilau', 'persian-pilau-1770491748', '', NULL, 'rb_pp_456', NULL, 80.00, NULL, NULL, 'pcs', NULL, NULL, 'product_69878f6433a38.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-07 19:15:48', '2026-02-07 19:58:59'),
(39, 2, 2, NULL, 'mutton kacchi biryani', 'mutton-kacchi-biryani-1770491818', '', NULL, 'rb_mkb_456', NULL, 458.00, NULL, NULL, 'pcs', NULL, NULL, 'product_69878faa189d6.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-07 19:16:58', '2026-02-07 19:58:57'),
(40, 2, 2, NULL, 'Burmese biryani', 'burmese-biryani-1770491984', '', NULL, 'rb_bb_456', NULL, 109.00, NULL, NULL, 'pcs', NULL, NULL, 'product_69879050a1cf2.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-07 19:19:44', '2026-02-07 19:58:55'),
(41, 3, 14, NULL, 'Aarong Bashmati Rice (বাসমতী চাল) 1kg', 'aarong-bashmati-rice-1kg-1770569002', '', NULL, '', NULL, 120.00, NULL, NULL, 'pcs', NULL, NULL, 'product_6988bd2a97d3a.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-08 16:43:22', '2026-02-08 16:43:22'),
(42, 3, 23, NULL, 'Masoor Dal -1kg', 'masoor-dal-1kg-1770569711', '', NULL, 'g_md_456', NULL, 120.00, NULL, NULL, 'pcs', NULL, NULL, 'product_6988bfef8e407.png', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-08 16:55:11', '2026-02-08 16:55:11'),
(139, 3, 1, NULL, 'Vegetable Samosa', 'vegetable-samosa-3-1', 'Crispy pastry filled with spiced potatoes and peas', NULL, '', NULL, 80.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698ce55b054fd.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-13 09:50:12'),
(140, 3, 1, NULL, 'Chicken Pakora', 'chicken-pakora-3-1', 'Deep-fried chicken fritters with Indian spices', NULL, '', NULL, 150.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698ce582e4282.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-11 20:24:34'),
(141, 3, 1, NULL, 'Paneer Tikka', 'paneer-tikka-3-1', 'Grilled cottage cheese marinated in tandoori spices', NULL, '', NULL, 180.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698ce5a432828.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-13 09:50:01'),
(142, 3, 1, NULL, 'Onion Bhaji', 'onion-bhaji-3-1', 'Crispy onion fritters with gram flour', NULL, '', NULL, 90.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698ce5c53b770.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-11 20:25:41'),
(143, 3, 1, NULL, 'Chicken Wings Tandoori', 'chicken-wings-tandoori-3-1', 'Spicy tandoori chicken wings', NULL, '', NULL, 200.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698ce5e97bc97.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-11 20:26:17'),
(144, 3, 2, NULL, 'Butter Chicken', 'butter-chicken-3-2', 'Tender chicken in creamy tomato sauce', NULL, '', NULL, 320.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698ce614869f1.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-13 09:50:04'),
(145, 3, 2, NULL, 'Chicken Tikka Masala', 'chicken-tikka-masala-3-2', 'Grilled chicken in spiced curry sauce', NULL, '', NULL, 340.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698ce63d6f249.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-11 20:27:41'),
(146, 3, 2, NULL, 'Chicken Korma', 'chicken-korma-3-2', 'Mild chicken curry with cashew cream', NULL, '', NULL, 310.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698ce66be5805.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-11 20:28:27'),
(147, 3, 2, NULL, 'Chicken Vindaloo', 'chicken-vindaloo-3-2', 'Spicy Goan-style chicken curry', NULL, '', NULL, 330.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698ce68fca93e.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-11 20:29:03'),
(148, 3, 2, NULL, 'Chicken Jalfrezi', 'chicken-jalfrezi-3-2', 'Stir-fried chicken with peppers and onions', NULL, '', NULL, 300.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698ce6b162d61.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-11 20:29:37'),
(149, 3, 2, NULL, 'Chicken Madras', 'chicken-madras-3-2', 'Hot and spicy South Indian chicken curry', NULL, '', NULL, 320.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698ce6f4b79c1.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-11 20:30:44'),
(150, 3, 2, NULL, 'Chicken Saag', 'chicken-saag-3-2', 'Chicken cooked with spinach and spices', NULL, '', NULL, 310.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698ce7908fbf1.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-11 20:33:20'),
(151, 3, 2, NULL, 'Tandoori Chicken Full', 'tandoori-chicken-full-3-2', 'Full chicken marinated in yogurt and spices', NULL, '', NULL, 550.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698ce7b356fef.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-11 20:33:55'),
(152, 3, 2, NULL, 'Chicken Biryani', 'chicken-biryani-3-2', 'Fragrant basmati rice with spiced chicken', NULL, '', NULL, 280.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698ce7e1db66f.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-11 20:34:41'),
(153, 3, 2, NULL, 'Mutton Rogan Josh', 'mutton-rogan-josh-3-2', 'Aromatic lamb curry from Kashmir', NULL, '', NULL, 450.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698ce81e42905.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-11 20:35:42'),
(154, 3, 2, NULL, 'Mutton Korma', 'mutton-korma-3-2', 'Mild lamb curry with yogurt and cream', NULL, '', NULL, 440.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698ce845752b7.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-11 20:36:21'),
(155, 3, 2, NULL, 'Mutton Vindaloo', 'mutton-vindaloo-3-2', 'Fiery hot lamb curry', NULL, '', NULL, 460.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698ce880b4ef9.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-11 20:37:20'),
(156, 3, 2, NULL, 'Mutton Biryani', 'mutton-biryani-3-2', 'Layered rice with tender lamb pieces', NULL, '', NULL, 380.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698ce8b65780f.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-11 20:38:14'),
(157, 3, 2, NULL, 'Mutton Keema', 'mutton-keema-3-2', 'Minced lamb with peas and spices', NULL, '', NULL, 350.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698ce8d706963.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-11 20:38:47'),
(158, 3, 2, NULL, 'Palak Paneer', 'palak-paneer-3-2', 'Cottage cheese in creamy spinach gravy', NULL, '', NULL, 250.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698ce93c84a58.png', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-11 20:40:28'),
(159, 3, 2, NULL, 'Paneer Butter Masala', 'paneer-butter-masala-3-2', 'Cottage cheese in rich tomato gravy', NULL, '', NULL, 270.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698ce97291adc.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-11 20:41:22'),
(160, 3, 2, NULL, 'Dal Makhani', 'dal-makhani-3-2', 'Black lentils in creamy tomato sauce', NULL, '', NULL, 180.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d79ffd7d20.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 06:58:07'),
(161, 3, 2, NULL, 'Chana Masala', 'chana-masala-3-2', 'Chickpeas in spiced tomato gravy', NULL, '', NULL, 160.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d7a25506db.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 06:58:45'),
(162, 3, 2, NULL, 'Aloo Gobi', 'aloo-gobi-3-2', 'Potato and cauliflower dry curry', NULL, '', NULL, 150.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d7a4754077.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 06:59:19'),
(163, 3, 2, NULL, 'Baingan Bharta', 'baingan-bharta-3-2', 'Smoked eggplant mash with spices', NULL, '', NULL, 170.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d7a829dd31.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:00:18'),
(164, 3, 2, NULL, 'Vegetable Biryani', 'vegetable-biryani-3-2', 'Mixed vegetables with fragrant rice', NULL, '', NULL, 220.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d7aa58200e.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:00:53'),
(165, 3, 2, NULL, 'Malai Kofta', 'malai-kofta-3-2', 'Vegetable dumplings in creamy sauce', NULL, '', NULL, 240.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d7ac46e37c.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:01:24'),
(166, 3, 3, NULL, 'Butter Naan', 'butter-naan-3-3', 'Soft leavened bread with butter', NULL, '', NULL, 40.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d7aeaf387a.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:02:02'),
(167, 3, 3, NULL, 'Garlic Naan', 'garlic-naan-3-3', 'Naan topped with garlic and coriander', NULL, '', NULL, 50.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d7b0b41787.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:02:35'),
(168, 3, 3, NULL, 'Cheese Naan', 'cheese-naan-3-3', 'Naan stuffed with melted cheese', NULL, '', NULL, 80.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d7b3266bf7.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:03:14'),
(169, 3, 3, NULL, 'Tandoori Roti', 'tandoori-roti-3-3', 'Whole wheat flatbread', NULL, '', NULL, 30.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d7b63f259e.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:04:03'),
(170, 3, 3, NULL, 'Paratha', 'paratha-3-3', 'Layered whole wheat flatbread', NULL, '', NULL, 35.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d7b8797733.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:04:39'),
(171, 3, 3, NULL, 'Aloo Paratha', 'aloo-paratha-3-3', 'Paratha stuffed with spiced potatoes', NULL, '', NULL, 60.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d7bb0e6fd0.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:05:20'),
(172, 3, 3, NULL, 'Puri', 'puri-3-3', 'Deep-fried puffed bread', NULL, '', NULL, 40.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d7bd9036d1.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:06:01'),
(173, 3, 3, NULL, 'Kulcha', 'kulcha-3-3', 'Leavened bread with onions', NULL, '', NULL, 45.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d7bf73ff59.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:06:31'),
(174, 3, 4, NULL, 'Steamed Basmati Rice', 'steamed-basmati-rice-3-4', 'Plain aromatic basmati rice', NULL, '', NULL, 80.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d7c1fc36b1.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:07:11'),
(175, 3, 4, NULL, 'Jeera Rice', 'jeera-rice-3-4', 'Cumin-flavored basmati rice', NULL, '', NULL, 100.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d7c45df923.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:07:49'),
(176, 3, 4, NULL, 'Pulao Rice', 'pulao-rice-3-4', 'Spiced rice with vegetables', NULL, '', NULL, 120.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d7c6651a2e.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:08:22'),
(177, 3, 5, NULL, 'Gulab Jamun', 'gulab-jamun-3-5', 'Sweet milk dumplings in sugar syrup', NULL, '', NULL, 100.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d7ca5e7058.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:09:25'),
(178, 3, 5, NULL, 'Rasmalai', 'rasmalai-3-5', 'Cottage cheese patties in sweet milk', NULL, '', NULL, 120.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d7ccf27a13.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:10:07'),
(179, 3, 5, NULL, 'Kheer', 'kheer-3-5', 'Rice pudding with cardamom', NULL, '', NULL, 90.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d7cf16ad88.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:10:41'),
(180, 3, 5, NULL, 'Kulfi', 'kulfi-3-5', 'Traditional Indian ice cream', NULL, '', NULL, 80.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d7d242fa8b.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:11:32'),
(181, 3, 5, NULL, 'Gajar Halwa', 'gajar-halwa-3-5', 'Carrot pudding with nuts', NULL, '', NULL, 110.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d7d4d66342.png', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:12:13'),
(182, 3, 6, NULL, 'Mango Lassi', 'mango-lassi-3-6', 'Sweet yogurt drink with mango', NULL, '', NULL, 80.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d7d6c6e622.webp', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:12:44'),
(183, 3, 6, NULL, 'Sweet Lassi', 'sweet-lassi-3-6', 'Traditional sweet yogurt drink', NULL, '', NULL, 60.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d7d913ca66.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:13:21'),
(184, 3, 6, NULL, 'Salted Lassi', 'salted-lassi-3-6', 'Savory yogurt drink', NULL, '', NULL, 60.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d7dc26da41.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:14:10'),
(185, 3, 6, NULL, 'Masala Chai', 'masala-chai-3-6', 'Spiced Indian tea', NULL, '', NULL, 40.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698ce763aeec5.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-11 20:32:35'),
(186, 3, 6, NULL, 'Fresh Lime Soda', 'fresh-lime-soda-3-6', 'Refreshing lime drink', NULL, '', NULL, 50.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698ce726535df.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-11 20:31:34'),
(187, 4, 2, NULL, 'Whopper', 'whopper-4-2', 'Flame-grilled beef patty with fresh vegetables', NULL, '', NULL, 350.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d82739700e.png', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:34:11'),
(188, 4, 2, NULL, 'Double Whopper', 'double-whopper-4-2', 'Two flame-grilled beef patties', NULL, '', NULL, 480.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d828da49aa.png', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:34:37'),
(189, 4, 2, NULL, 'Whopper Jr', 'whopper-jr-4-2', 'Smaller version of the classic Whopper', NULL, '', NULL, 250.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d82c5a5f4e.png', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:35:33'),
(190, 4, 2, NULL, 'Bacon King', 'bacon-king-4-2', 'Beef patties with crispy bacon', NULL, '', NULL, 520.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d82ebf3be3.png', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:36:12'),
(191, 4, 2, NULL, 'Chicken Royale', 'chicken-royale-4-2', 'Crispy chicken fillet burger', NULL, '', NULL, 320.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d831451cf9.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:36:52'),
(192, 4, 2, NULL, 'Grilled Chicken Burger', 'grilled-chicken-burger-4-2', 'Grilled chicken with mayo', NULL, '', NULL, 300.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d834026e85.webp', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:37:36'),
(193, 4, 2, NULL, 'Veggie Burger', 'veggie-burger-4-2', 'Vegetable patty with fresh veggies', NULL, '', NULL, 220.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d836a20ca7.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:38:18'),
(194, 4, 2, NULL, 'Fish Burger', 'fish-burger-4-2', 'Crispy fish fillet with tartar sauce', NULL, '', NULL, 280.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d838cde355.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:38:52'),
(195, 4, 2, NULL, 'Cheese Burger', 'cheese-burger-4-2', 'Beef patty with melted cheese', NULL, '', NULL, 200.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d83b5280d5.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:39:33'),
(196, 4, 2, NULL, 'Double Cheese Burger', 'double-cheese-burger-4-2', 'Two beef patties with double cheese', NULL, '', NULL, 320.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d83e793f51.jpeg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:40:23'),
(197, 4, 2, NULL, 'BBQ Bacon Burger', 'bbq-bacon-burger-4-2', 'Beef with BBQ sauce and bacon', NULL, '', NULL, 380.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d8417c9f49.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:41:11'),
(198, 4, 2, NULL, 'Mushroom Swiss Burger', 'mushroom-swiss-burger-4-2', 'Beef with mushrooms and Swiss cheese', NULL, '', NULL, 360.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d8438d70f0.webp', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:41:44'),
(199, 4, 2, NULL, 'Spicy Chicken Burger', 'spicy-chicken-burger-4-2', 'Spicy crispy chicken fillet', NULL, '', NULL, 330.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d84cc7e2d0.png', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:44:12'),
(200, 4, 2, NULL, 'Chicken Bacon Burger', 'chicken-bacon-burger-4-2', 'Chicken with crispy bacon', NULL, '', NULL, 350.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d84ea48e76.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:44:42'),
(201, 4, 2, NULL, 'Chicken Nuggets 6pc', 'chicken-nuggets-6pc-4-2', 'Crispy chicken nuggets', NULL, '', NULL, 180.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d852e71251.png', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:45:50'),
(202, 4, 2, NULL, 'Chicken Nuggets 9pc', 'chicken-nuggets-9pc-4-2', 'Crispy chicken nuggets', NULL, '', NULL, 250.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d8538240a8.png', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:46:00'),
(203, 4, 2, NULL, 'Chicken Tenders 3pc', 'chicken-tenders-3pc-4-2', 'Breaded chicken strips', NULL, '', NULL, 220.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d855f8b918.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:46:39'),
(204, 4, 2, NULL, 'Chicken Tenders 5pc', 'chicken-tenders-5pc-4-2', 'Breaded chicken strips', NULL, '', NULL, 340.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d856edd498.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:46:54'),
(205, 4, 2, NULL, 'Chicken Wings 6pc', 'chicken-wings-6pc-4-2', 'Spicy chicken wings', NULL, '', NULL, 280.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d85a2f3514.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:47:46'),
(206, 4, 2, NULL, 'Chicken Wings 12pc', 'chicken-wings-12pc-4-2', 'Spicy chicken wings', NULL, '', NULL, 480.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d85ab74a22.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:47:55'),
(207, 4, 2, NULL, 'Popcorn Chicken', 'popcorn-chicken-4-2', 'Bite-sized crispy chicken', NULL, '', NULL, 200.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d85f152616.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:49:05'),
(208, 4, 3, NULL, 'French Fries Small', 'french-fries-small-4-3', 'Crispy golden fries', NULL, '', NULL, 80.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d861904a8c.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:49:45'),
(209, 4, 3, NULL, 'French Fries Medium', 'french-fries-medium-4-3', 'Crispy golden fries', NULL, '', NULL, 120.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d863bb9aa9.webp', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:50:19'),
(210, 4, 3, NULL, 'French Fries Large', 'french-fries-large-4-3', 'Crispy golden fries', NULL, '', NULL, 150.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d8650e88e8.webp', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:50:40'),
(211, 4, 3, NULL, 'Onion Rings', 'onion-rings-4-3', 'Crispy battered onion rings', NULL, '', NULL, 140.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d86a490441.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:52:04'),
(212, 4, 3, NULL, 'Mozzarella Sticks', 'mozzarella-sticks-4-3', 'Fried cheese sticks', NULL, '', NULL, 180.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d86d06e3be.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:52:48'),
(213, 4, 3, NULL, 'Hash Browns', 'hash-browns-4-3', 'Crispy potato hash browns', NULL, '', NULL, 100.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d86f197697.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:53:21'),
(214, 4, 3, NULL, 'Coleslaw', 'coleslaw-4-3', 'Fresh cabbage salad', NULL, '', NULL, 90.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d873b03b95.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:54:35'),
(215, 4, 3, NULL, 'Garden Salad', 'garden-salad-4-3', 'Fresh mixed vegetables', NULL, '', NULL, 120.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d876900907.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:55:21'),
(216, 4, 5, NULL, 'Chocolate Sundae', 'chocolate-sundae-4-5', 'Soft serve with chocolate sauce', NULL, '', NULL, 120.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d87a008384.webp', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:56:16'),
(217, 4, 5, NULL, 'Strawberry Sundae', 'strawberry-sundae-4-5', 'Soft serve with strawberry sauce', NULL, '', NULL, 120.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d87e7ab783.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:57:27'),
(218, 4, 5, NULL, 'Apple Pie', 'apple-pie-4-5', 'Warm apple pie', NULL, '', NULL, 100.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d881f474ed.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:58:23'),
(219, 4, 5, NULL, 'Chocolate Brownie', 'chocolate-brownie-4-5', 'Rich chocolate brownie', NULL, '', NULL, 130.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d884abb5b7.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 07:59:06'),
(220, 4, 5, NULL, 'Soft Serve Cone', 'soft-serve-cone-4-5', 'Vanilla soft serve ice cream', NULL, '', NULL, 80.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d88ab1f3e5.png', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 08:00:43'),
(221, 4, 6, NULL, 'Coca Cola Small', 'coca-cola-small-4-6', 'Chilled Coca Cola', NULL, '', NULL, 50.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d89129ee98.webp', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 08:02:26'),
(222, 4, 6, NULL, 'Coca Cola Medium', 'coca-cola-medium-4-6', 'Chilled Coca Cola', NULL, '', NULL, 70.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d891d39cdc.webp', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 08:02:37'),
(223, 4, 6, NULL, 'Coca Cola Large', 'coca-cola-large-4-6', 'Chilled Coca Cola', NULL, '', NULL, 90.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d89271270d.webp', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 08:02:47'),
(224, 4, 6, NULL, 'Sprite Small', 'sprite-small-4-6', 'Chilled Sprite', NULL, '', NULL, 50.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d895941447.png', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 08:03:37'),
(225, 4, 6, NULL, 'Fanta Small', 'fanta-small-4-6', 'Chilled Fanta', NULL, '', NULL, 50.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d899129fc4.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 08:04:33'),
(226, 4, 6, NULL, 'Iced Coffee', 'iced-coffee-4-6', 'Cold coffee with ice', NULL, '', NULL, 120.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d89cfad340.webp', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 08:05:35'),
(227, 4, 6, NULL, 'Milkshake Chocolate', 'milkshake-chocolate-4-6', 'Thick chocolate milkshake', NULL, '', NULL, 150.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d89fc3a5d3.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 08:06:20'),
(228, 4, 6, NULL, 'Milkshake Vanilla', 'milkshake-vanilla-4-6', 'Thick vanilla milkshake', NULL, '', NULL, 150.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d8a6280a3e.webp', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 08:08:02'),
(229, 4, 6, NULL, 'Milkshake Strawberry', 'milkshake-strawberry-4-6', 'Thick strawberry milkshake', NULL, '', NULL, 150.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d8a819cffb.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 08:08:33'),
(230, 4, 6, NULL, 'Orange Juice', 'orange-juice-4-6', 'Fresh orange juice', NULL, '', NULL, 100.00, NULL, NULL, 'pcs', NULL, NULL, 'product_698d8ac310d41.jpg', NULL, 15, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.00, 0, 0, 0, NULL, NULL, NULL, '2026-02-11 20:21:40', '2026-02-12 08:09:39');

-- --------------------------------------------------------

--
-- Table structure for table `product_addons`
--

CREATE TABLE `product_addons` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_required` tinyint(1) DEFAULT 0,
  `max_selection` int(11) DEFAULT 1,
  `addon_type` enum('single','multiple','checkbox') DEFAULT 'single',
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_recipes`
--

CREATE TABLE `product_recipes` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `quantity_required` decimal(10,3) NOT NULL COMMENT 'Quantity per product unit',
  `unit` varchar(20) NOT NULL,
  `is_optional` tinyint(1) DEFAULT 0,
  `is_substitute_allowed` tinyint(1) DEFAULT 0,
  `substitute_ingredients` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Alternative ingredients' CHECK (json_valid(`substitute_ingredients`)),
  `cost_percentage` decimal(5,2) DEFAULT NULL COMMENT 'Percentage of total product cost',
  `preparation_step` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'Small, Medium, Large, Half, Full, etc.',
  `sku` varchar(100) DEFAULT NULL,
  `price_adjustment` decimal(10,2) DEFAULT 0.00,
  `unit_value` decimal(10,3) DEFAULT NULL,
  `weight` decimal(8,3) DEFAULT NULL,
  `dimensions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'length, width, height' CHECK (json_valid(`dimensions`)),
  `is_default` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `shipping_cost` decimal(10,2) DEFAULT 0.00,
  `status` enum('draft','sent','confirmed','partially_received','received','cancelled','returned') DEFAULT 'draft',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `order_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `actual_delivery_date` date DEFAULT NULL,
  `payment_terms` varchar(100) DEFAULT NULL,
  `payment_status` enum('pending','partial','paid','overdue') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `terms_conditions` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `quantity_ordered` decimal(10,3) NOT NULL,
  `quantity_received` decimal(10,3) DEFAULT 0.000,
  `unit_price` decimal(10,2) NOT NULL,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `expiry_date` date DEFAULT NULL,
  `batch_number` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `overall_rating` decimal(2,1) NOT NULL,
  `food_rating` decimal(2,1) DEFAULT NULL,
  `service_rating` decimal(2,1) DEFAULT NULL,
  `delivery_rating` decimal(2,1) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Review images' CHECK (json_valid(`images`)),
  `is_verified` tinyint(1) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `helpful_count` int(11) DEFAULT 0,
  `vendor_response` text DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `type` enum('string','number','boolean','json') DEFAULT 'string',
  `group` varchar(50) DEFAULT 'general',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `type`, `group`, `description`, `created_at`, `updated_at`) VALUES
(1, 'app_name', 'ORDIVO', 'string', 'general', 'Application name', '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(2, 'app_version', '1.0.0', 'string', 'general', 'Application version', '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(3, 'app_tagline', 'দ্রুত ডেলিভারি • তাজা পণ্য • সেরা দাম', 'string', 'general', 'Application tagline in Bengali', '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(4, 'currency', 'BDT', 'string', 'general', 'Default currency', '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(5, 'currency_symbol', '৳', 'string', 'general', 'Currency symbol', '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(6, 'timezone', 'Asia/Dhaka', 'string', 'general', 'Application timezone', '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(7, 'language', 'bn', 'string', 'general', 'Default language (Bengali)', '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(8, 'country', 'Bangladesh', 'string', 'general', 'Default country', '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(9, 'default_delivery_fee', '50.00', 'number', 'delivery', 'Default delivery fee in BDT', '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(10, 'free_delivery_above', '500.00', 'number', 'delivery', 'Free delivery threshold in BDT', '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(11, 'commission_rate', '15.00', 'number', 'business', 'Default commission rate percentage', '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(12, 'tax_rate', '0.00', 'number', 'business', 'Default tax rate (VAT) percentage', '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(13, 'min_order_amount', '100.00', 'number', 'business', 'Minimum order amount in BDT', '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(14, 'max_delivery_distance', '25.00', 'number', 'delivery', 'Maximum delivery distance in KM', '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(15, 'order_auto_accept_time', '300', 'number', 'orders', 'Auto accept order time in seconds', '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(16, 'maintenance_mode', '0', 'boolean', 'system', 'Maintenance mode status', '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(17, 'allow_registration', '1', 'boolean', 'system', 'Allow new user registration', '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(18, 'email_verification_required', '0', 'boolean', 'system', 'Require email verification', '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(19, 'phone_verification_required', '1', 'boolean', 'system', 'Require phone verification', '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(20, 'cash_on_delivery', '1', 'boolean', 'payment', 'Enable cash on delivery', '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(21, 'bkash_enabled', '1', 'boolean', 'payment', 'Enable bKash payment', '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(22, 'nagad_enabled', '1', 'boolean', 'payment', 'Enable Nagad payment', '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(23, 'rocket_enabled', '1', 'boolean', 'payment', 'Enable Rocket payment', '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(24, 'card_payment_enabled', '0', 'boolean', 'payment', 'Enable card payment', '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(25, 'delivery_time_slots', '[\"9:00-12:00\",\"12:00-15:00\",\"15:00-18:00\",\"18:00-21:00\"]', 'json', 'delivery', 'Available delivery time slots', '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(26, 'support_phone', '+880-1700-000000', 'string', 'contact', 'Customer support phone number', '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(27, 'support_email', 'support@ordivo.com', 'string', 'contact', 'Customer support email', '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(28, 'facebook_page', 'https://facebook.com/ordivo', 'string', 'social', 'Facebook page URL', '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(29, 'instagram_page', 'https://instagram.com/ordivo', 'string', 'social', 'Instagram page URL', '2026-02-04 13:13:58', '2026-02-04 13:13:58');

-- --------------------------------------------------------

--
-- Table structure for table `site_banners`
--

CREATE TABLE `site_banners` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `banner_type` enum('hero','promotional','announcement','festival','seasonal') NOT NULL,
  `position` varchar(50) DEFAULT 'homepage_hero' COMMENT 'Where to display the banner',
  `content` longtext DEFAULT NULL,
  `background_image` varchar(255) DEFAULT NULL,
  `background_color` varchar(7) DEFAULT NULL,
  `text_color` varchar(7) DEFAULT NULL,
  `button_text` varchar(100) DEFAULT NULL,
  `button_link` varchar(255) DEFAULT NULL,
  `button_style` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`button_style`)),
  `animation_type` varchar(50) DEFAULT NULL COMMENT 'fade, slide, bounce, etc.',
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `is_clickable` tinyint(1) DEFAULT 0,
  `target_audience` enum('all','customers','vendors','new_users') DEFAULT 'all',
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `click_count` int(11) DEFAULT 0,
  `impression_count` int(11) DEFAULT 0,
  `responsive_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Different images for different screen sizes' CHECK (json_valid(`responsive_images`)),
  `custom_css` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_banners`
--

INSERT INTO `site_banners` (`id`, `title`, `subtitle`, `banner_type`, `position`, `content`, `background_image`, `background_color`, `text_color`, `button_text`, `button_link`, `button_style`, `animation_type`, `display_order`, `is_active`, `is_clickable`, `target_audience`, `start_date`, `end_date`, `click_count`, `impression_count`, `responsive_images`, `custom_css`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'স্বাগতম ORDIVO তে', 'দ্রুত ডেলিভারি • তাজা পণ্য • সেরা দাম', 'hero', 'homepage_hero', 'বাংলাদেশের সেরা খাবার ও গ্রোসারি ডেলিভারি সেবা', NULL, '#e21b70', '#ffffff', 'অর্ডার করুন', '/products', NULL, NULL, 1, 1, 0, 'all', NULL, NULL, 0, 0, NULL, NULL, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(2, 'ঈদ মুবারক অফার', '৫০% পর্যন্ত ছাড় সব খাবারে', 'festival', 'homepage_promo', 'ঈদের বিশেষ অফার - সীমিত সময়ের জন্য', NULL, '#d4af37', '#2f4f4f', 'অফার দেখুন', '/offers/eid', NULL, NULL, 2, 0, 0, 'all', '2024-04-10 00:00:00', '2024-04-12 00:00:00', 0, 0, NULL, NULL, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(3, 'পহেলা বৈশাখ উৎসব', 'নববর্ষের শুভেচ্ছা ও বিশেষ ছাড়', 'festival', 'homepage_promo', 'বাংলা নববর্ষ উপলক্ষে বিশেষ অফার', NULL, '#ff6b35', '#ffffff', 'উৎসবে যোগ দিন', '/offers/boishakh', NULL, NULL, 2, 0, 0, 'all', '2024-04-14 00:00:00', '2024-04-14 00:00:00', 0, 0, NULL, NULL, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(4, 'ফ্রি ডেলিভারি', '৫০০ টাকার উপরে অর্ডারে', 'promotional', 'homepage_announcement', 'আজই অর্ডার করুন এবং ফ্রি ডেলিভারি পান', NULL, '#4caf50', '#ffffff', 'এখনই অর্ডার করুন', '/order-now', NULL, NULL, 3, 1, 0, 'all', NULL, NULL, 0, 0, NULL, NULL, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59');

-- --------------------------------------------------------

--
-- Table structure for table `site_configurations`
--

CREATE TABLE `site_configurations` (
  `id` int(11) NOT NULL,
  `section` varchar(50) NOT NULL COMMENT 'general, payment, delivery, etc.',
  `key` varchar(100) NOT NULL,
  `value` longtext DEFAULT NULL,
  `data_type` enum('string','number','boolean','json','text','file') DEFAULT 'string',
  `is_public` tinyint(1) DEFAULT 0 COMMENT 'Can be accessed by frontend',
  `is_editable` tinyint(1) DEFAULT 1 COMMENT 'Can be edited by admin',
  `validation_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`validation_rules`)),
  `description` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_configurations`
--

INSERT INTO `site_configurations` (`id`, `section`, `key`, `value`, `data_type`, `is_public`, `is_editable`, `validation_rules`, `description`, `sort_order`, `updated_by`, `updated_at`, `created_at`) VALUES
(1, 'general', 'site_name', 'ORDIVO', 'string', 1, 1, NULL, 'Site name displayed across the platform', 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(2, 'general', 'site_tagline', 'দ্রুত ডেলিভারি • তাজা পণ্য • সেরা দাম', 'string', 1, 1, NULL, 'Site tagline in Bengali', 2, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(3, 'general', 'site_description', 'বাংলাদেশের সেরা খাবার ও গ্রোসারি ডেলিভারি প্ল্যাটফর্ম', 'text', 1, 1, NULL, 'Site description for SEO', 3, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(4, 'general', 'default_language', 'bn', 'string', 1, 1, NULL, 'Default language code', 4, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(5, 'general', 'timezone', 'Asia/Dhaka', 'string', 0, 1, NULL, 'Default timezone', 5, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(6, 'general', 'currency', 'BDT', 'string', 1, 1, NULL, 'Default currency', 6, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(7, 'general', 'currency_symbol', '৳', 'string', 1, 1, NULL, 'Currency symbol', 7, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(8, 'general', 'maintenance_mode', '0', 'boolean', 0, 1, NULL, 'Enable maintenance mode', 8, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(9, 'files', 'max_file_size', '10485760', 'number', 0, 1, NULL, 'Maximum file size in bytes (10MB)', 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(10, 'files', 'allowed_image_types', '[\"jpg\",\"jpeg\",\"png\",\"gif\",\"webp\"]', 'json', 0, 1, NULL, 'Allowed image file extensions', 2, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(11, 'files', 'allowed_document_types', '[\"pdf\",\"doc\",\"docx\",\"txt\"]', 'json', 0, 1, NULL, 'Allowed document file extensions', 3, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(12, 'files', 'image_quality', '85', 'number', 0, 1, NULL, 'Image compression quality (1-100)', 4, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(13, 'files', 'generate_thumbnails', '1', 'boolean', 0, 1, NULL, 'Auto-generate image thumbnails', 5, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(14, 'files', 'thumbnail_sizes', '{\"small\":{\"width\":150,\"height\":150},\"medium\":{\"width\":300,\"height\":300},\"large\":{\"width\":800,\"height\":600}}', 'json', 0, 1, NULL, 'Thumbnail size configurations', 6, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(15, 'kitchen', 'enable_waste_alerts', '1', 'boolean', 0, 1, NULL, 'Enable automatic waste prevention alerts', 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(16, 'kitchen', 'expiry_warning_days', '3', 'number', 0, 1, NULL, 'Days before expiry to show warning', 2, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(17, 'kitchen', 'quality_check_required', '1', 'boolean', 0, 1, NULL, 'Require quality check for all orders', 3, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(18, 'kitchen', 'auto_assign_orders', '1', 'boolean', 0, 1, NULL, 'Automatically assign orders to kitchen staff', 4, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(19, 'kitchen', 'performance_tracking', '1', 'boolean', 0, 1, NULL, 'Enable kitchen performance tracking', 5, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(20, 'business', 'commission_rate', '15.00', 'number', 0, 1, NULL, 'Default commission rate percentage', 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(21, 'business', 'min_order_amount', '100.00', 'number', 1, 1, NULL, 'Minimum order amount in BDT', 2, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(22, 'business', 'delivery_fee', '50.00', 'number', 1, 1, NULL, 'Default delivery fee in BDT', 3, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(23, 'business', 'free_delivery_above', '500.00', 'number', 1, 1, NULL, 'Free delivery threshold in BDT', 4, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(24, 'business', 'tax_rate', '0.00', 'number', 0, 1, NULL, 'Default tax rate percentage', 5, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(25, 'payment', 'cash_on_delivery', '1', 'boolean', 1, 1, NULL, 'Enable cash on delivery', 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(26, 'payment', 'bkash_enabled', '1', 'boolean', 1, 1, NULL, 'Enable bKash payment', 2, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(27, 'payment', 'nagad_enabled', '1', 'boolean', 1, 1, NULL, 'Enable Nagad payment', 3, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(28, 'payment', 'rocket_enabled', '1', 'boolean', 1, 1, NULL, 'Enable Rocket payment', 4, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(29, 'payment', 'card_payment_enabled', '0', 'boolean', 1, 1, NULL, 'Enable card payment', 5, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(30, 'payment', 'wallet_enabled', '1', 'boolean', 1, 1, NULL, 'Enable wallet payment', 6, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(31, 'delivery', 'max_delivery_distance', '25.00', 'number', 0, 1, NULL, 'Maximum delivery distance in KM', 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(32, 'delivery', 'delivery_time_slots', '[\"9:00-12:00\",\"12:00-15:00\",\"15:00-18:00\",\"18:00-21:00\"]', 'json', 1, 1, NULL, 'Available delivery time slots', 2, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(33, 'delivery', 'auto_assign_riders', '1', 'boolean', 0, 1, NULL, 'Automatically assign delivery riders', 3, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(34, 'delivery', 'rider_tracking', '1', 'boolean', 1, 1, NULL, 'Enable real-time rider tracking', 4, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(35, 'notifications', 'email_notifications', '1', 'boolean', 0, 1, NULL, 'Enable email notifications', 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(36, 'notifications', 'sms_notifications', '1', 'boolean', 0, 1, NULL, 'Enable SMS notifications', 2, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(37, 'notifications', 'push_notifications', '1', 'boolean', 0, 1, NULL, 'Enable push notifications', 3, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(38, 'notifications', 'order_status_updates', '1', 'boolean', 1, 1, NULL, 'Send order status update notifications', 4, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(39, 'theme', 'current_theme_id', '1', 'number', 1, 1, NULL, 'Currently active theme ID', 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(40, 'theme', 'enable_theme_scheduling', '1', 'boolean', 0, 1, NULL, 'Enable automatic theme scheduling', 2, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(41, 'theme', 'festival_auto_themes', '1', 'boolean', 0, 1, NULL, 'Automatically apply festival themes', 3, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(42, 'theme', 'theme_cache_enabled', '1', 'boolean', 0, 1, NULL, 'Enable theme caching for performance', 4, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(43, 'theme', 'custom_css_enabled', '1', 'boolean', 0, 1, NULL, 'Allow custom CSS modifications', 5, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(44, 'theme', 'responsive_design', '1', 'boolean', 1, 1, NULL, 'Enable responsive design features', 6, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(45, 'design', 'enable_custom_layouts', '1', 'boolean', 0, 1, NULL, 'Allow custom page layouts', 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(46, 'design', 'card_animation_enabled', '1', 'boolean', 1, 1, NULL, 'Enable card animations', 2, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(47, 'design', 'banner_auto_rotation', '1', 'boolean', 1, 1, NULL, 'Auto-rotate promotional banners', 3, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(48, 'design', 'banner_rotation_interval', '5000', 'number', 1, 1, NULL, 'Banner rotation interval in milliseconds', 4, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(49, 'design', 'enable_dark_mode', '0', 'boolean', 1, 1, NULL, 'Enable dark mode option', 5, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(50, 'design', 'custom_fonts_enabled', '1', 'boolean', 0, 1, NULL, 'Allow custom font uploads', 6, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(51, 'festivals', 'eid_theme_enabled', '1', 'boolean', 1, 1, NULL, 'Enable Eid festival themes', 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(52, 'festivals', 'pohela_boishakh_theme', '1', 'boolean', 1, 1, NULL, 'Enable Pohela Boishakh themes', 2, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(53, 'festivals', 'durga_puja_theme', '1', 'boolean', 1, 1, NULL, 'Enable Durga Puja themes', 3, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(54, 'festivals', 'victory_day_theme', '1', 'boolean', 1, 1, NULL, 'Enable Victory Day themes', 4, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(55, 'festivals', 'independence_day_theme', '1', 'boolean', 1, 1, NULL, 'Enable Independence Day themes', 5, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(56, 'festivals', 'ramadan_theme', '1', 'boolean', 1, 1, NULL, 'Enable Ramadan special themes', 6, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(57, 'location', 'enable_location_management', '1', 'boolean', 0, 1, NULL, 'Enable location management for super admin', 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(58, 'location', 'auto_detect_location', '1', 'boolean', 1, 1, NULL, 'Enable automatic location detection', 2, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(59, 'location', 'require_postal_code', '0', 'boolean', 1, 1, NULL, 'Require postal code for addresses', 3, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(60, 'location', 'validate_coordinates', '1', 'boolean', 0, 1, NULL, 'Validate latitude/longitude coordinates', 4, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(61, 'location', 'default_delivery_radius', '15.00', 'number', 0, 1, NULL, 'Default delivery radius in KM', 5, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(62, 'location', 'location_cache_duration', '3600', 'number', 0, 1, NULL, 'Location data cache duration in seconds', 6, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(63, 'advertisements', 'enable_advertisements', '1', 'boolean', 1, 1, NULL, 'Enable advertisement system', 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(64, 'advertisements', 'require_ad_approval', '1', 'boolean', 0, 1, NULL, 'Require admin approval for ads', 2, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(65, 'advertisements', 'max_ads_per_page', '5', 'number', 0, 1, NULL, 'Maximum ads per page', 3, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(66, 'advertisements', 'ad_rotation_enabled', '1', 'boolean', 1, 1, NULL, 'Enable automatic ad rotation', 4, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(67, 'advertisements', 'ad_click_tracking', '1', 'boolean', 0, 1, NULL, 'Enable ad click tracking', 5, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(68, 'advertisements', 'ad_impression_tracking', '1', 'boolean', 0, 1, NULL, 'Enable ad impression tracking', 6, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(69, 'advertisements', 'ad_budget_alerts', '1', 'boolean', 0, 1, NULL, 'Send alerts when ad budget is low', 7, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(70, 'advertisements', 'ad_performance_reports', '1', 'boolean', 0, 1, NULL, 'Generate ad performance reports', 8, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(71, 'advertisements', 'allow_video_ads', '1', 'boolean', 1, 1, NULL, 'Allow video advertisements', 9, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(72, 'advertisements', 'allow_popup_ads', '1', 'boolean', 1, 1, NULL, 'Allow popup advertisements', 10, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(73, 'advertisements', 'popup_frequency_limit', '1', 'number', 1, 1, NULL, 'Max popups per user per day', 11, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(74, 'advertisements', 'ad_cache_duration', '1800', 'number', 0, 1, NULL, 'Advertisement cache duration in seconds', 12, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(75, 'user_management', 'auto_approve_customers', '1', 'boolean', 0, 1, NULL, 'Automatically approve customer registrations', 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(76, 'user_management', 'require_vendor_approval', '1', 'boolean', 0, 1, NULL, 'Require super admin approval for vendors', 2, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(77, 'user_management', 'require_email_verification', '1', 'boolean', 1, 1, NULL, 'Require email verification for registration', 3, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(78, 'user_management', 'require_phone_verification', '1', 'boolean', 1, 1, NULL, 'Require phone verification for registration', 4, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(79, 'user_management', 'allow_customer_self_registration', '1', 'boolean', 1, 1, NULL, 'Allow customers to register without approval', 5, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(80, 'user_management', 'vendor_application_review_days', '7', 'number', 0, 1, NULL, 'Days to review vendor applications', 6, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(81, 'user_management', 'max_login_attempts', '5', 'number', 0, 1, NULL, 'Maximum login attempts before lockout', 7, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(82, 'user_management', 'account_lockout_duration', '30', 'number', 0, 1, NULL, 'Account lockout duration in minutes', 8, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(83, 'user_management', 'enable_user_blocking', '1', 'boolean', 0, 1, NULL, 'Enable user blocking by super admin', 9, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(84, 'user_management', 'enable_user_suspension', '1', 'boolean', 0, 1, NULL, 'Enable temporary user suspension', 10, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(85, 'user_management', 'track_user_activity', '1', 'boolean', 0, 1, NULL, 'Track user activity and behavior', 11, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(86, 'user_management', 'enable_risk_assessment', '1', 'boolean', 0, 1, NULL, 'Enable automatic risk assessment', 12, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(87, 'vendor_approval', 'require_business_documents', '1', 'boolean', 0, 1, NULL, 'Require business documents for vendor approval', 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(88, 'vendor_approval', 'require_trade_license', '1', 'boolean', 0, 1, NULL, 'Require trade license for vendors', 2, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(89, 'vendor_approval', 'require_food_license', '1', 'boolean', 0, 1, NULL, 'Require food license for food vendors', 3, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(90, 'vendor_approval', 'require_bank_verification', '1', 'boolean', 0, 1, NULL, 'Require bank account verification', 4, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(91, 'vendor_approval', 'auto_approve_verified_vendors', '0', 'boolean', 0, 1, NULL, 'Auto-approve vendors with all documents', 5, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(92, 'vendor_approval', 'vendor_approval_notification', '1', 'boolean', 0, 1, NULL, 'Send notifications for vendor approvals', 6, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(93, 'vendor_approval', 'vendor_rejection_notification', '1', 'boolean', 0, 1, NULL, 'Send notifications for vendor rejections', 7, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(94, 'customer_management', 'enable_customer_blocking', '1', 'boolean', 0, 1, NULL, 'Allow super admin to block customers', 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(95, 'customer_management', 'customer_block_notification', '1', 'boolean', 1, 1, NULL, 'Notify customers when blocked', 2, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(96, 'customer_management', 'allow_customer_appeals', '1', 'boolean', 1, 1, NULL, 'Allow customers to appeal blocks', 3, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(97, 'customer_management', 'auto_unblock_expired', '1', 'boolean', 0, 1, NULL, 'Automatically unblock when duration expires', 4, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(98, 'customer_management', 'track_suspicious_activity', '1', 'boolean', 0, 1, NULL, 'Track suspicious customer activity', 5, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(99, 'customer_management', 'enable_customer_verification', '0', 'boolean', 1, 1, NULL, 'Enable optional customer verification', 6, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58');

-- --------------------------------------------------------

--
-- Table structure for table `site_images`
--

CREATE TABLE `site_images` (
  `id` int(11) NOT NULL,
  `image_type` varchar(50) NOT NULL COMMENT 'logo, hero, background, favicon, etc.',
  `image_name` varchar(255) NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `site_name` varchar(100) DEFAULT 'ORDIVO',
  `site_tagline` varchar(255) DEFAULT 'দ্রুত ডেলিভারি • তাজা পণ্য • সেরা দাম',
  `logo_url` varchar(255) DEFAULT '?',
  `hero_background_image` varchar(255) DEFAULT NULL,
  `hero_title` varchar(255) DEFAULT 'Welcome to ORDIVO',
  `hero_subtitle` text DEFAULT 'Fast Delivery • Fresh Products • Best Prices',
  `hero_button_text` varchar(100) DEFAULT 'Order Now',
  `hero_button_link` varchar(255) DEFAULT '#',
  `favicon_url` varchar(255) DEFAULT NULL,
  `background_pattern` varchar(50) DEFAULT 'none',
  `primary_color` varchar(7) DEFAULT '#e21b70',
  `secondary_color` varchar(7) DEFAULT '#ff6b9d',
  `accent_color` varchar(7) DEFAULT '#ff5722',
  `theme_mode` enum('light','dark','auto') DEFAULT 'light',
  `current_theme` varchar(50) DEFAULT 'ordivo',
  `theme_start_date` date DEFAULT NULL,
  `theme_end_date` date DEFAULT NULL,
  `navbar_bg` varchar(7) DEFAULT '#ffffff',
  `navbar_text` varchar(7) DEFAULT '#333333',
  `footer_bg` varchar(7) DEFAULT '#1a1a2e',
  `footer_text` varchar(7) DEFAULT '#aaaaaa',
  `card_style` enum('flat','elevated','outlined') DEFAULT 'elevated',
  `card_shadow` enum('none','light','medium','heavy') DEFAULT 'light',
  `card_border_radius` varchar(10) DEFAULT '10px',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `site_name`, `site_tagline`, `logo_url`, `hero_background_image`, `hero_title`, `hero_subtitle`, `hero_button_text`, `hero_button_link`, `favicon_url`, `background_pattern`, `primary_color`, `secondary_color`, `accent_color`, `theme_mode`, `current_theme`, `theme_start_date`, `theme_end_date`, `navbar_bg`, `navbar_text`, `footer_bg`, `footer_text`, `card_style`, `card_shadow`, `card_border_radius`, `created_at`, `updated_at`) VALUES
(1, 'ORDIVO', 'দ্রুত ডেলিভারি • তাজা পণ্য • সেরা দাম', 'uploads/settings/logo_1770475038_69874e1e48045.png', NULL, 'Welcome to ORDIVO', 'Fast Delivery • Fresh Products • Best Prices', 'Order Now', '#', '', 'none', '#e21b70', '#ff6b9d', '#ff5722', 'light', 'ordivo', NULL, NULL, '#ffffff', '#333333', '#1a1a2e', '#aaaaaa', 'elevated', 'light', '10px', '2026-02-04 13:13:58', '2026-02-07 09:37:18');

-- --------------------------------------------------------

--
-- Table structure for table `site_themes`
--

CREATE TABLE `site_themes` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `theme_type` enum('default','festival','seasonal','promotional','custom') DEFAULT 'custom',
  `festival_name` varchar(100) DEFAULT NULL COMMENT 'Eid, Pohela Boishakh, Durga Puja, etc.',
  `is_active` tinyint(1) DEFAULT 0,
  `is_default` tinyint(1) DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `auto_activate` tinyint(1) DEFAULT 0,
  `priority` int(11) DEFAULT 0 COMMENT 'Higher priority themes override lower ones',
  `preview_image` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_themes`
--

INSERT INTO `site_themes` (`id`, `name`, `display_name`, `description`, `theme_type`, `festival_name`, `is_active`, `is_default`, `start_date`, `end_date`, `auto_activate`, `priority`, `preview_image`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'default_ordivo', 'Default ORDIVO Style', 'Clean and modern design inspired by ORDIVO', 'default', NULL, 1, 1, NULL, NULL, 0, 1, NULL, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(2, 'eid_celebration', 'Eid Mubarak Theme', 'Special theme for Eid celebrations with Islamic patterns', 'festival', 'Eid ul-Fitr', 0, 0, '2024-04-10', '2024-04-12', 1, 10, NULL, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(3, 'pohela_boishakh', 'Pohela Boishakh Theme', 'Bengali New Year celebration theme with traditional colors', 'festival', 'Pohela Boishakh', 0, 0, '2024-04-14', '2024-04-14', 1, 10, NULL, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(4, 'durga_puja', 'Durga Puja Theme', 'Colorful theme for Durga Puja celebrations', 'festival', 'Durga Puja', 0, 0, '2024-10-09', '2024-10-13', 1, 10, NULL, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(5, 'victory_day', 'Victory Day Theme', 'Patriotic theme for Bangladesh Victory Day', 'festival', 'Victory Day', 0, 0, '2024-12-16', '2024-12-16', 1, 9, NULL, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(6, 'independence_day', 'Independence Day Theme', 'Green and red theme for Independence Day', 'festival', 'Independence Day', 0, 0, '2024-03-26', '2024-03-26', 1, 9, NULL, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(7, 'ramadan_special', 'Ramadan Kareem Theme', 'Special theme for the holy month of Ramadan', 'seasonal', 'Ramadan', 0, 0, '2024-03-11', '2024-04-09', 1, 8, NULL, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(8, 'winter_special', 'Winter Special Theme', 'Cozy winter theme with warm colors', 'seasonal', NULL, 0, 0, '2024-12-01', '2024-02-28', 1, 5, NULL, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(9, 'summer_fresh', 'Summer Fresh Theme', 'Light and fresh theme for summer season', 'seasonal', NULL, 0, 0, '2024-04-01', '2024-06-30', 1, 5, NULL, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59');

-- --------------------------------------------------------

--
-- Table structure for table `states`
--

CREATE TABLE `states` (
  `id` int(11) NOT NULL,
  `country_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `states`
--

INSERT INTO `states` (`id`, `country_id`, `name`, `code`, `status`) VALUES
(1, 1, 'Dhaka Division', 'DHA', 1),
(2, 1, 'Chittagong Division', 'CTG', 1),
(3, 1, 'Rajshahi Division', 'RAJ', 1),
(4, 1, 'Khulna Division', 'KHU', 1),
(5, 1, 'Barisal Division', 'BAR', 1),
(6, 1, 'Sylhet Division', 'SYL', 1),
(7, 1, 'Rangpur Division', 'RAN', 1),
(8, 1, 'Mymensingh Division', 'MYM', 1);

-- --------------------------------------------------------

--
-- Table structure for table `sub_areas`
--

CREATE TABLE `sub_areas` (
  `id` int(11) NOT NULL,
  `area_id` int(11) DEFAULT NULL,
  `town_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `name_bengali` varchar(100) DEFAULT NULL,
  `area_type` enum('residential','commercial','industrial','mixed','rural') DEFAULT 'residential',
  `postal_code` varchar(20) DEFAULT NULL,
  `landmark` varchar(255) DEFAULT NULL,
  `delivery_charge` decimal(8,2) DEFAULT 0.00,
  `delivery_priority` tinyint(1) DEFAULT 1 COMMENT '1=High, 2=Medium, 3=Low',
  `access_difficulty` enum('easy','medium','difficult') DEFAULT 'easy',
  `special_instructions` text DEFAULT NULL,
  `is_safe_area` tinyint(1) DEFAULT 1,
  `night_delivery_allowed` tinyint(1) DEFAULT 1,
  `status` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sub_areas`
--

INSERT INTO `sub_areas` (`id`, `area_id`, `town_id`, `name`, `name_bengali`, `area_type`, `postal_code`, `landmark`, `delivery_charge`, `delivery_priority`, `access_difficulty`, `special_instructions`, `is_safe_area`, `night_delivery_allowed`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'Dhanmondi 15', 'ধানমন্ডি ১৫', 'residential', NULL, 'Rabindra Sarobar', 30.00, 1, 'easy', NULL, 1, 1, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(2, 1, NULL, 'Dhanmondi 27', 'ধানমন্ডি ২৭', 'commercial', NULL, 'Dhanmondi Lake', 30.00, 1, 'easy', NULL, 1, 1, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(3, 2, NULL, 'Gulshan 1', 'গুলশান ১', 'commercial', NULL, 'Gulshan Circle 1', 40.00, 1, 'easy', NULL, 1, 1, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(4, 2, NULL, 'Gulshan 2', 'গুলশান ২', 'residential', NULL, 'Gulshan Circle 2', 40.00, 1, 'easy', NULL, 1, 1, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(5, 3, NULL, 'Banani Commercial', 'বনানী কমার্শিয়াল', 'commercial', NULL, 'Banani Graveyard', 40.00, 1, 'medium', NULL, 1, 1, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `area_id` int(11) DEFAULT NULL,
  `city_id` int(11) DEFAULT NULL,
  `tin_number` varchar(100) DEFAULT NULL,
  `trade_license` varchar(100) DEFAULT NULL,
  `payment_terms` varchar(100) DEFAULT NULL,
  `credit_limit` decimal(12,2) DEFAULT 0.00,
  `rating` decimal(3,2) DEFAULT 0.00,
  `total_orders` int(11) DEFAULT 0,
  `on_time_delivery_rate` decimal(5,2) DEFAULT 0.00,
  `quality_score` decimal(3,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `is_preferred` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `phone`, `email`, `address`, `area_id`, `city_id`, `tin_number`, `trade_license`, `payment_terms`, `credit_limit`, `rating`, `total_orders`, `on_time_delivery_rate`, `quality_score`, `is_active`, `is_preferred`, `created_at`, `updated_at`) VALUES
(1, 'ঢাকা ফ্রেশ সাপ্লাই', 'আব্দুর রহমান', '+8801712360001', 'supply@dhakafresh.com', 'কাওরান বাজার, ঢাকা', NULL, 1, NULL, NULL, NULL, 0.00, 4.50, 0, 0.00, 0.00, 1, 1, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(2, 'বাংলা স্পাইস কোম্পানি', 'নাসির উদ্দিন', '+8801712360002', 'info@banglaspice.com', 'চকবাজার, ঢাকা', NULL, 1, NULL, NULL, NULL, 0.00, 4.30, 0, 0.00, 0.00, 1, 0, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(3, 'ঢাকা মিট সাপ্লাই', 'করিম মিয়া', '+8801712360003', 'orders@dhakameat.com', 'কাপ্তান বাজার, ঢাকা', NULL, 1, NULL, NULL, NULL, 0.00, 4.60, 0, 0.00, 0.00, 1, 1, '2026-02-04 13:13:59', '2026-02-04 13:13:59');

-- --------------------------------------------------------

--
-- Table structure for table `system_maintenance`
--

CREATE TABLE `system_maintenance` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `maintenance_type` enum('scheduled','emergency','update','backup') NOT NULL,
  `status` enum('planned','in_progress','completed','cancelled') DEFAULT 'planned',
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `affects_modules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'List of affected modules' CHECK (json_valid(`affects_modules`)),
  `downtime_expected` tinyint(1) DEFAULT 0,
  `estimated_duration` int(11) DEFAULT NULL COMMENT 'Duration in minutes',
  `actual_duration` int(11) DEFAULT NULL,
  `scheduled_start` timestamp NULL DEFAULT NULL,
  `scheduled_end` timestamp NULL DEFAULT NULL,
  `actual_start` timestamp NULL DEFAULT NULL,
  `actual_end` timestamp NULL DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `theme_change_history`
--

CREATE TABLE `theme_change_history` (
  `id` int(11) NOT NULL,
  `from_theme_id` int(11) DEFAULT NULL,
  `to_theme_id` int(11) NOT NULL,
  `change_type` enum('manual','scheduled','automatic','festival') NOT NULL,
  `change_reason` varchar(255) DEFAULT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reverted_at` timestamp NULL DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `affected_pages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`affected_pages`)),
  `backup_created` tinyint(1) DEFAULT 0,
  `backup_path` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `theme_configurations`
--

CREATE TABLE `theme_configurations` (
  `id` int(11) NOT NULL,
  `theme_id` int(11) NOT NULL,
  `section` varchar(50) NOT NULL COMMENT 'header, footer, hero, cards, etc.',
  `element` varchar(100) NOT NULL COMMENT 'background_color, logo, banner, etc.',
  `property` varchar(100) NOT NULL COMMENT 'color, image, text, font, etc.',
  `value` longtext DEFAULT NULL,
  `data_type` enum('color','image','text','number','boolean','json','css','html') DEFAULT 'text',
  `is_responsive` tinyint(1) DEFAULT 0,
  `responsive_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Different values for mobile, tablet, desktop' CHECK (json_valid(`responsive_values`)),
  `css_selector` varchar(255) DEFAULT NULL,
  `css_property` varchar(100) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `theme_configurations`
--

INSERT INTO `theme_configurations` (`id`, `theme_id`, `section`, `element`, `property`, `value`, `data_type`, `is_responsive`, `responsive_values`, `css_selector`, `css_property`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 1, 'header', 'background', 'color', '#ffffff', 'color', 0, NULL, NULL, NULL, 0, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(2, 1, 'header', 'text', 'color', '#333333', 'color', 0, NULL, NULL, NULL, 0, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(3, 1, 'header', 'logo', 'height', '40px', 'text', 0, NULL, NULL, NULL, 0, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(4, 1, 'hero', 'background', 'gradient', 'linear-gradient(135deg, #e21b70 0%, #ff6b9d 100%)', 'css', 0, NULL, NULL, NULL, 0, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(5, 1, 'hero', 'text', 'color', '#ffffff', 'color', 0, NULL, NULL, NULL, 0, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(6, 1, 'cards', 'background', 'color', '#ffffff', 'color', 0, NULL, NULL, NULL, 0, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(7, 1, 'cards', 'border', 'radius', '12px', 'text', 0, NULL, NULL, NULL, 0, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(8, 1, 'cards', 'shadow', 'style', '0 2px 8px rgba(0,0,0,0.1)', 'css', 0, NULL, NULL, NULL, 0, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(9, 1, 'footer', 'background', 'color', '#1a1a2e', 'color', 0, NULL, NULL, NULL, 0, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(10, 1, 'footer', 'text', 'color', '#aaaaaa', 'color', 0, NULL, NULL, NULL, 0, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(11, 2, 'header', 'background', 'color', '#d4af37', 'color', 0, NULL, NULL, NULL, 0, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(12, 2, 'header', 'text', 'color', '#2f4f4f', 'color', 0, NULL, NULL, NULL, 0, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(13, 2, 'hero', 'background', 'gradient', 'linear-gradient(135deg, #d4af37 0%, #ffd700 100%)', 'css', 0, NULL, NULL, NULL, 0, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(14, 2, 'hero', 'text', 'color', '#2f4f4f', 'color', 0, NULL, NULL, NULL, 0, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(15, 2, 'cards', 'background', 'color', '#f8f8ff', 'color', 0, NULL, NULL, NULL, 0, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(16, 2, 'cards', 'border', 'color', '#d4af37', 'color', 0, NULL, NULL, NULL, 0, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(17, 2, 'body', 'background', 'pattern', 'url(/assets/patterns/islamic-pattern.svg)', 'css', 0, NULL, NULL, NULL, 0, '2026-02-04 13:13:59', '2026-02-04 13:13:59');

-- --------------------------------------------------------

--
-- Table structure for table `towns`
--

CREATE TABLE `towns` (
  `id` int(11) NOT NULL,
  `city_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `name_bengali` varchar(100) DEFAULT NULL,
  `upazila_code` varchar(20) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `population` int(11) DEFAULT NULL,
  `area_sq_km` decimal(8,2) DEFAULT NULL,
  `delivery_available` tinyint(1) DEFAULT 1,
  `delivery_charge` decimal(8,2) DEFAULT 0.00,
  `delivery_time_hours` decimal(4,2) DEFAULT 2.00,
  `is_urban` tinyint(1) DEFAULT 0,
  `development_status` enum('developed','developing','rural') DEFAULT 'developing',
  `economic_zone` varchar(100) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `towns`
--

INSERT INTO `towns` (`id`, `city_id`, `name`, `name_bengali`, `upazila_code`, `postal_code`, `latitude`, `longitude`, `population`, `area_sq_km`, `delivery_available`, `delivery_charge`, `delivery_time_hours`, `is_urban`, `development_status`, `economic_zone`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'Dhanmondi Thana', 'ধানমন্ডি থানা', 'DH01', '1205', NULL, NULL, NULL, NULL, 1, 30.00, 2.00, 1, 'developed', NULL, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(2, 1, 'Gulshan Thana', 'গুলশান থানা', 'GU01', '1212', NULL, NULL, NULL, NULL, 1, 40.00, 2.00, 1, 'developed', NULL, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(3, 1, 'Uttara Thana', 'উত্তরা থানা', 'UT01', '1230', NULL, NULL, NULL, NULL, 1, 50.00, 2.00, 1, 'developed', NULL, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(4, 2, 'Chittagong Sadar', 'চট্টগ্রাম সদর', 'CS01', '4000', NULL, NULL, NULL, NULL, 1, 60.00, 2.00, 1, 'developed', NULL, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58'),
(5, 3, 'Rajshahi Sadar', 'রাজশাহী সদর', 'RS01', '6000', NULL, NULL, NULL, NULL, 1, 70.00, 2.00, 1, 'developed', NULL, 1, NULL, '2026-02-04 13:13:58', '2026-02-04 13:13:58');

-- --------------------------------------------------------

--
-- Table structure for table `typography_settings`
--

CREATE TABLE `typography_settings` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `font_family_primary` varchar(100) DEFAULT 'Inter, sans-serif',
  `font_family_secondary` varchar(100) DEFAULT 'Roboto, sans-serif',
  `font_family_bengali` varchar(100) DEFAULT 'Kalpurush, SolaimanLipi, sans-serif',
  `heading_font` varchar(100) DEFAULT NULL,
  `body_font` varchar(100) DEFAULT NULL,
  `font_sizes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Font sizes for different elements' CHECK (json_valid(`font_sizes`)),
  `line_heights` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`line_heights`)),
  `font_weights` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`font_weights`)),
  `letter_spacing` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`letter_spacing`)),
  `text_transform` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`text_transform`)),
  `is_active` tinyint(1) DEFAULT 1,
  `is_default` tinyint(1) DEFAULT 0,
  `google_fonts_url` varchar(500) DEFAULT NULL,
  `custom_font_files` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_font_files`)),
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `typography_settings`
--

INSERT INTO `typography_settings` (`id`, `name`, `description`, `font_family_primary`, `font_family_secondary`, `font_family_bengali`, `heading_font`, `body_font`, `font_sizes`, `line_heights`, `font_weights`, `letter_spacing`, `text_transform`, `is_active`, `is_default`, `google_fonts_url`, `custom_font_files`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'modern_clean', 'Modern and clean typography', 'Inter, sans-serif', 'Roboto, sans-serif', 'Kalpurush, SolaimanLipi, sans-serif', NULL, NULL, '{\"h1\": \"2.5rem\", \"h2\": \"2rem\", \"h3\": \"1.75rem\", \"h4\": \"1.5rem\", \"h5\": \"1.25rem\", \"h6\": \"1rem\", \"body\": \"1rem\", \"small\": \"0.875rem\"}', NULL, NULL, NULL, NULL, 1, 1, NULL, NULL, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(2, 'festival_decorative', 'Decorative fonts for festivals', 'Playfair Display, serif', 'Open Sans, sans-serif', 'Mukti, SolaimanLipi, sans-serif', NULL, NULL, '{\"h1\": \"3rem\", \"h2\": \"2.5rem\", \"h3\": \"2rem\", \"h4\": \"1.75rem\", \"h5\": \"1.5rem\", \"h6\": \"1.25rem\", \"body\": \"1.125rem\", \"small\": \"1rem\"}', NULL, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, '2026-02-04 13:13:59', '2026-02-04 13:13:59');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role` enum('super_admin','vendor','kitchen_manager','kitchen_staff','store_manager','store_staff','delivery_rider','customer') NOT NULL DEFAULT 'customer',
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `cover_photo` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `phone_verified_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','inactive','banned','pending','suspended','under_review') DEFAULT 'active',
  `approval_status` enum('auto_approved','pending_approval','approved','rejected','needs_review') DEFAULT 'auto_approved',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `requires_approval` tinyint(1) DEFAULT 0 COMMENT '1 for vendors, 0 for customers',
  `auto_approve_customers` tinyint(1) DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `login_attempts` tinyint(1) DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `blocked_by` int(11) DEFAULT NULL,
  `blocked_at` timestamp NULL DEFAULT NULL,
  `block_reason` text DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT 0,
  `two_factor_secret` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `registration_ip` varchar(45) DEFAULT NULL,
  `registration_user_agent` text DEFAULT NULL,
  `email_verification_token` varchar(255) DEFAULT NULL,
  `phone_verification_token` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `name`, `email`, `phone`, `password`, `avatar`, `cover_photo`, `date_of_birth`, `gender`, `email_verified_at`, `phone_verified_at`, `status`, `approval_status`, `approved_by`, `approved_at`, `rejection_reason`, `requires_approval`, `auto_approve_customers`, `last_login_at`, `login_attempts`, `locked_until`, `blocked_by`, `blocked_at`, `block_reason`, `two_factor_enabled`, `two_factor_secret`, `remember_token`, `registration_ip`, `registration_user_agent`, `email_verification_token`, `phone_verification_token`, `created_at`, `updated_at`) VALUES
(2, 'vendor', 'আহমেদ রহমান', 'ahmed.rahman@ordivo.com', '+8801712345001', '$2y$10$DW.Hw41nqHCMVFxcQILnVud.Td6KAUS1JQh2NVTBYS91vdL9IPF86', 'uploads/vendors/profile_2_1770486891.png', NULL, NULL, NULL, NULL, NULL, 'active', 'auto_approved', NULL, NULL, NULL, 0, 1, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-04 13:13:59', '2026-02-14 01:36:12'),
(3, 'vendor', 'ফাতিমা খাতুন', 'fatima.khatun@ordivo.com', '+8801712345002', '$2y$10$DW.Hw41nqHCMVFxcQILnVud.Td6KAUS1JQh2NVTBYS91vdL9IPF86', 'uploads/vendors/profile_3_1770880741.jpg', NULL, NULL, NULL, NULL, NULL, 'active', 'auto_approved', NULL, NULL, NULL, 0, 1, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-04 13:13:59', '2026-02-12 07:19:01'),
(4, 'vendor', 'মোহাম্মদ আলী', 'mohammad.ali@ordivo.com', '+8801712345003', '$2y$10$DW.Hw41nqHCMVFxcQILnVud.Td6KAUS1JQh2NVTBYS91vdL9IPF86', 'uploads/vendors/profile_4_1770838395.jpg', NULL, NULL, NULL, NULL, NULL, 'active', 'auto_approved', NULL, NULL, NULL, 0, 1, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-04 13:13:59', '2026-02-11 19:33:15'),
(5, 'vendor', 'রাশিদা বেগম', 'rashida.begum@ordivo.com', '+8801712345004', '$2y$10$DW.Hw41nqHCMVFxcQILnVud.Td6KAUS1JQh2NVTBYS91vdL9IPF86', 'uploads/vendors/profile_5_1770483357.png', NULL, NULL, NULL, NULL, NULL, 'active', 'auto_approved', NULL, NULL, NULL, 0, 1, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-04 13:13:59', '2026-02-07 16:55:57'),
(6, 'vendor', 'করিম উদ্দিন', 'karim.uddin@ordivo.com', '+8801712345005', '$2y$10$DW.Hw41nqHCMVFxcQILnVud.Td6KAUS1JQh2NVTBYS91vdL9IPF86', 'uploads/vendors/profile_6_1770839073.png', NULL, NULL, NULL, NULL, NULL, 'active', 'auto_approved', NULL, NULL, NULL, 0, 1, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-04 13:13:59', '2026-02-11 19:44:33'),
(22, 'customer', 'customar', 'customar@ordivo.com', '01685158766', '$2y$10$gQ/6WwrBWMDNqyYcHSzhQuANafRf65o/ma.z2AORGhXWyRhvuxfVu', NULL, NULL, NULL, NULL, NULL, NULL, 'active', 'auto_approved', NULL, NULL, NULL, 0, 1, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-10 05:34:35', '2026-02-10 05:34:35'),
(23, 'super_admin', 'Admin', 'admin@ordivo.com', '01648872255', '$2y$10$tmU5jTYUk7/LsOtMFF3ILOAJMYzj6evYfSsmzWd3UuA4GNy15N8bu', NULL, NULL, NULL, NULL, NULL, NULL, 'active', 'auto_approved', NULL, NULL, NULL, 0, 1, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-14 06:59:13', '2026-02-14 07:00:07'),
(24, 'kitchen_manager', 'KITCHEN MANAGER', 'kitchen@ordivo.com', '01648876633', '$2y$10$pwwdFg2NNru/S2jMNpVCkes0XpmM1kUI1IS4PsnzoUqAbX5fwxSq.', NULL, NULL, NULL, NULL, NULL, NULL, 'active', 'auto_approved', NULL, NULL, NULL, 0, 1, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-16 11:22:46', '2026-02-16 16:22:46');

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_logs`
--

CREATE TABLE `user_activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `activity_type` varchar(50) NOT NULL COMMENT 'login, logout, registration, order, etc.',
  `activity_description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `device_type` enum('desktop','mobile','tablet','unknown') DEFAULT 'unknown',
  `browser` varchar(100) DEFAULT NULL,
  `os` varchar(100) DEFAULT NULL,
  `location_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Country, city, etc.' CHECK (json_valid(`location_data`)),
  `additional_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_data`)),
  `risk_score` tinyint(1) DEFAULT 0 COMMENT '0-10 risk assessment',
  `flagged_for_review` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_activity_logs`
--

INSERT INTO `user_activity_logs` (`id`, `user_id`, `session_id`, `activity_type`, `activity_description`, `ip_address`, `user_agent`, `device_type`, `browser`, `os`, `location_data`, `additional_data`, `risk_score`, `flagged_for_review`, `created_at`) VALUES
(1, NULL, NULL, 'registration', 'Customer registered successfully', '192.168.1.100', NULL, 'mobile', NULL, NULL, NULL, NULL, 0, 0, '2026-02-04 13:13:59'),
(2, NULL, NULL, 'login', 'Customer logged in', '192.168.1.101', NULL, 'desktop', NULL, NULL, NULL, NULL, 0, 0, '2026-02-04 13:13:59'),
(3, NULL, NULL, 'vendor_application', 'Vendor application submitted', '192.168.1.102', NULL, 'desktop', NULL, NULL, NULL, NULL, 0, 0, '2026-02-04 13:13:59'),
(4, NULL, NULL, 'login', 'Super admin logged in', '192.168.1.1', NULL, 'desktop', NULL, NULL, NULL, NULL, 0, 0, '2026-02-04 13:13:59');

-- --------------------------------------------------------

--
-- Table structure for table `user_approval_requests`
--

CREATE TABLE `user_approval_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_type` enum('registration','role_change','reactivation','vendor_application') NOT NULL,
  `current_status` enum('pending','under_review','approved','rejected','cancelled') DEFAULT 'pending',
  `requested_role` varchar(50) DEFAULT NULL,
  `business_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Business information for vendor applications' CHECK (json_valid(`business_details`)),
  `documents_submitted` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'List of submitted documents' CHECK (json_valid(`documents_submitted`)),
  `admin_notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `auto_approved` tinyint(1) DEFAULT 0,
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_blocks`
--

CREATE TABLE `user_blocks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `blocked_by` int(11) NOT NULL,
  `block_type` enum('temporary','permanent','warning','suspension') NOT NULL,
  `reason` text NOT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `duration_days` int(11) DEFAULT NULL COMMENT 'NULL for permanent blocks',
  `blocked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `unblocked_at` timestamp NULL DEFAULT NULL,
  `unblocked_by` int(11) DEFAULT NULL,
  `unblock_reason` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `appeal_submitted` tinyint(1) DEFAULT 0,
  `appeal_message` text DEFAULT NULL,
  `appeal_reviewed` tinyint(1) DEFAULT 0,
  `appeal_decision` enum('approved','rejected','pending') DEFAULT NULL,
  `internal_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_favorites`
--

CREATE TABLE `user_favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `item_type` enum('product','vendor') NOT NULL,
  `item_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_role_changes`
--

CREATE TABLE `user_role_changes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `from_role` varchar(50) NOT NULL,
  `to_role` varchar(50) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `approval_required` tinyint(1) DEFAULT 0,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `effective_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `device_type` enum('desktop','mobile','tablet') DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_verifications`
--

CREATE TABLE `user_verifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `verification_type` enum('email','phone','identity','business','address','bank_account') NOT NULL,
  `verification_method` varchar(50) DEFAULT NULL COMMENT 'otp, document, manual, etc.',
  `verification_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Verification details and documents' CHECK (json_valid(`verification_data`)),
  `status` enum('pending','verified','rejected','expired','cancelled') DEFAULT 'pending',
  `verified_at` timestamp NULL DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `attempts_count` int(11) DEFAULT 0,
  `max_attempts` int(11) DEFAULT 3,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

CREATE TABLE `vendors` (
  `id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `business_type_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `address` text NOT NULL,
  `area_id` int(11) DEFAULT NULL,
  `city_id` int(11) NOT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `banner_image` varchar(255) DEFAULT NULL,
  `gallery` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of additional images' CHECK (json_valid(`gallery`)),
  `trade_license_number` varchar(100) DEFAULT NULL,
  `tin_number` varchar(100) DEFAULT NULL,
  `food_license_number` varchar(100) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_routing_number` varchar(50) DEFAULT NULL,
  `bkash_number` varchar(20) DEFAULT NULL COMMENT 'bKash mobile banking',
  `nagad_number` varchar(20) DEFAULT NULL COMMENT 'Nagad mobile banking',
  `rocket_number` varchar(20) DEFAULT NULL COMMENT 'Rocket mobile banking',
  `opening_time` time DEFAULT '09:00:00',
  `closing_time` time DEFAULT '22:00:00',
  `preparation_time` int(11) DEFAULT 30 COMMENT 'Average preparation time in minutes',
  `min_order_amount` decimal(10,2) DEFAULT 200.00,
  `commission_rate` decimal(5,2) DEFAULT 15.00,
  `delivery_radius` decimal(8,2) DEFAULT 10.00 COMMENT 'Delivery radius in km',
  `delivery_fee` decimal(10,2) DEFAULT 50.00,
  `free_delivery_above` decimal(10,2) DEFAULT 500.00,
  `rating` decimal(3,2) DEFAULT 0.00,
  `total_reviews` int(11) DEFAULT 0,
  `total_orders` int(11) DEFAULT 0,
  `total_revenue` decimal(12,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `is_verified` tinyint(1) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_open` tinyint(1) DEFAULT 1,
  `verification_documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`verification_documents`)),
  `social_media` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Social media links' CHECK (json_valid(`social_media`)),
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `seo_keywords` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `onboarding_notes` text DEFAULT NULL,
  `operating_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`operating_hours`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vendors`
--

INSERT INTO `vendors` (`id`, `owner_id`, `business_type_id`, `name`, `slug`, `description`, `address`, `area_id`, `city_id`, `postal_code`, `latitude`, `longitude`, `phone`, `email`, `website`, `logo`, `banner_image`, `gallery`, `trade_license_number`, `tin_number`, `food_license_number`, `bank_account_number`, `bank_name`, `bank_routing_number`, `bkash_number`, `nagad_number`, `rocket_number`, `opening_time`, `closing_time`, `preparation_time`, `min_order_amount`, `commission_rate`, `delivery_radius`, `delivery_fee`, `free_delivery_above`, `rating`, `total_reviews`, `total_orders`, `total_revenue`, `is_active`, `is_verified`, `is_featured`, `is_open`, `verification_documents`, `social_media`, `seo_title`, `seo_description`, `seo_keywords`, `created_at`, `updated_at`, `onboarding_notes`, `operating_hours`) VALUES
(1, 2, 1, 'ঢাকা বিরিয়ানি হাউস', 'vendor-u2-v1-t1770489761', 'খাঁটি বাংলাদেশি বিরিয়ানি এবং ঐতিহ্যবাহী খাবার', 'বাড়ি ১৫, রোড ৭, ধানমন্ডি, ঢাকা', 1, 1, NULL, NULL, NULL, '+8801712345001', 'ahmed.rahman@ordivo.com', NULL, 'uploads/vendors/logo_2_1770486907.png', 'uploads/vendors/cover_2_1770486900.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '09:00:00', '22:00:00', 45, 200.00, 15.00, 15.00, 50.00, 500.00, 4.50, 0, 0, 0.00, 1, 1, 1, 1, NULL, NULL, NULL, NULL, NULL, '2026-02-04 13:13:59', '2026-02-07 19:26:16', NULL, NULL),
(2, 3, 8, 'The Rainy Roof Restaurant', 'vendor-u3-v2-t1770489762', 'তাজা সবজি, ফল এবং দৈনন্দিন প্রয়োজনীয় জিনিসপত্র', 'দোকান ২৫, গুলশান এভিনিউ, গুলশান, ঢাকা', 2, 1, NULL, NULL, NULL, '+8801712345002', 'fatima.khatun@ordivo.com', NULL, 'uploads/vendors/logo_3_1770880749.jpg', 'uploads/vendors/cover_3_1770880695.webp', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '09:00:00', '22:00:00', 20, 100.00, 8.00, 10.00, 30.00, 300.00, 4.20, 0, 0, 0.00, 1, 1, 1, 1, NULL, NULL, NULL, NULL, NULL, '2026-02-04 13:13:59', '2026-02-12 02:19:09', NULL, NULL),
(3, 4, 1, 'স্পাইস গার্ডেন রেস্তোরাঁ', 'vendor-u4-v3-t1770489763', 'খাঁটি মসলা দিয়ে ভারতীয় এবং বাংলাদেশি খাবার', 'প্লট ১২, বনানী কমার্শিয়াল এলাকা, ঢাকা', 3, 1, NULL, NULL, NULL, '+8801712345003', 'mohammad.ali@ordivo.com', NULL, 'uploads/vendors/logo_4_1770838386.png', 'uploads/vendors/cover_4_1770838403.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '09:00:00', '22:00:00', 35, 250.00, 15.00, 20.00, 60.00, 600.00, 4.30, 0, 0, 0.00, 1, 1, 1, 1, NULL, NULL, NULL, NULL, NULL, '2026-02-04 13:13:59', '2026-02-11 19:45:28', NULL, NULL),
(4, 5, 7, 'ঢাকা বিরিয়ানি হাউজ', 'vendor-u5-v4-t1770489764', 'তাজা বেকড পণ্য, কেক এবং পেস্ট্রি', '', 4, 1, NULL, NULL, NULL, '', '', NULL, 'uploads/vendors/logo_5_1770492342.png', 'uploads/vendors/cover_5_1770483345.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '09:00:00', '22:00:00', 25, 150.00, 12.00, 12.00, 40.00, 400.00, 4.60, 0, 0, 0.00, 1, 1, 1, 1, NULL, NULL, NULL, NULL, NULL, '2026-02-04 13:13:59', '2026-02-11 19:45:36', NULL, NULL),
(5, 6, 1, 'করিমের রান্নাঘর', 'vendor-u6-v5-t1770489765', 'ঘরোয়া স্টাইলের বাংলা রান্না এবং আরামদায়ক খাবার', 'লেন ৫, ওয়ারী, পুরান ঢাকা', 7, 1, NULL, NULL, NULL, '+8801712345005', 'karim.uddin@ordivo.com', NULL, 'uploads/vendors/logo_6_1770839061.png', 'uploads/vendors/cover_6_1770839083.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '09:00:00', '22:00:00', 30, 180.00, 15.00, 8.00, 45.00, 450.00, 4.10, 0, 0, 0.00, 1, 1, 1, 1, NULL, NULL, NULL, NULL, NULL, '2026-02-04 13:13:59', '2026-02-11 19:45:34', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vendor_hours`
--

CREATE TABLE `vendor_hours` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `day_of_week` tinyint(1) NOT NULL COMMENT '0=Sunday, 1=Monday, ..., 6=Saturday',
  `opening_time` time DEFAULT NULL,
  `closing_time` time DEFAULT NULL,
  `is_closed` tinyint(1) DEFAULT 0,
  `break_start` time DEFAULT NULL,
  `break_end` time DEFAULT NULL,
  `is_24_hours` tinyint(1) DEFAULT 0,
  `special_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Holiday or special day hours' CHECK (json_valid(`special_hours`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendor_inventory`
--

CREATE TABLE `vendor_inventory` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `ingredient_id` int(11) DEFAULT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `current_stock` decimal(10,3) NOT NULL DEFAULT 0.000,
  `reserved_stock` decimal(10,3) DEFAULT 0.000 COMMENT 'Stock reserved for pending orders',
  `min_stock_level` decimal(10,3) DEFAULT 5.000,
  `max_stock_level` decimal(10,3) DEFAULT 1000.000,
  `reorder_point` decimal(10,3) DEFAULT 10.000,
  `reorder_quantity` decimal(10,3) DEFAULT 50.000,
  `cost_per_unit` decimal(10,2) DEFAULT 0.00,
  `average_cost` decimal(10,2) DEFAULT 0.00,
  `location` varchar(100) DEFAULT NULL COMMENT 'Storage location/shelf',
  `last_restocked` timestamp NULL DEFAULT NULL,
  `last_sold` timestamp NULL DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendor_settings`
--

CREATE TABLE `vendor_settings` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `business_name` varchar(255) DEFAULT NULL,
  `business_address` text DEFAULT NULL,
  `business_phone` varchar(20) DEFAULT NULL,
  `business_email` varchar(255) DEFAULT NULL,
  `operating_hours` text DEFAULT NULL,
  `delivery_radius` int(11) DEFAULT 5,
  `min_order_amount` decimal(10,2) DEFAULT 0.00,
  `delivery_fee` decimal(10,2) DEFAULT 0.00,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vendor_settings`
--

INSERT INTO `vendor_settings` (`id`, `vendor_id`, `business_name`, `business_address`, `business_phone`, `business_email`, `operating_hours`, `delivery_radius`, `min_order_amount`, `delivery_fee`, `tax_rate`, `created_at`, `updated_at`) VALUES
(1, 2, 'The Rainy Roof Restaurant', 'দোকান ২৫, গুলশান এভিনিউ, গুলশান, ঢাকা', '+8801712345002', 'fatima.khatun@ordivo.com', '', 5, 0.00, 0.00, 0.00, '2026-02-07 10:07:35', '2026-02-12 02:19:09'),
(2, 5, 'করিমের রান্নাঘর', 'লেন ৫, ওয়ারী, পুরান ঢাকা', '+8801712345005', 'karim.uddin@ordivo.com', '', 5, 0.00, 0.00, 0.00, '2026-02-07 10:15:03', '2026-02-11 14:44:21'),
(3, 4, 'ঢাকা বিরিয়ানি হাউজ', '', '', '', '', 5, 0.00, 0.00, 0.00, '2026-02-07 10:19:21', '2026-02-07 14:25:42'),
(4, 1, 'ঢাকা বিরিয়ানি হাউস', 'বাড়ি ১৫, রোড ৭, ধানমন্ডি, ঢাকা', '+8801712345001', 'ahmed.rahman@ordivo.com', '', 5, 0.00, 0.00, 0.00, '2026-02-07 12:55:07', '2026-02-07 12:55:07'),
(5, 3, 'স্পাইস গার্ডেন রেস্তোরাঁ', 'প্লট ১২, বনানী কমার্শিয়াল এলাকা, ঢাকা', '+8801712345003', 'mohammad.ali@ordivo.com', '', 5, 0.00, 0.00, 0.00, '2026-02-11 14:33:06', '2026-02-11 14:33:06');

-- --------------------------------------------------------

--
-- Table structure for table `vendor_staff`
--

CREATE TABLE `vendor_staff` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Specific permissions for this staff member' CHECK (json_valid(`permissions`)),
  `work_schedule` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Working hours and days' CHECK (json_valid(`work_schedule`)),
  `emergency_contact` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`emergency_contact`)),
  `status` enum('active','inactive','terminated','on_leave') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL COMMENT 'Vendor user who added this staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wallets`
--

CREATE TABLE `wallets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `balance` decimal(12,2) DEFAULT 0.00,
  `pending_balance` decimal(12,2) DEFAULT 0.00 COMMENT 'Pending transactions',
  `total_earned` decimal(12,2) DEFAULT 0.00,
  `total_spent` decimal(12,2) DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'BDT',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wallets`
--

INSERT INTO `wallets` (`id`, `user_id`, `balance`, `pending_balance`, `total_earned`, `total_spent`, `currency`, `is_active`, `created_at`, `updated_at`) VALUES
(3, 2, 2500.00, 0.00, 5000.00, 2500.00, 'BDT', 1, '2026-02-04 13:13:59', '2026-02-04 13:13:59'),
(4, 3, 1800.00, 0.00, 3000.00, 1200.00, 'BDT', 1, '2026-02-04 13:13:59', '2026-02-04 13:13:59');

-- --------------------------------------------------------

--
-- Table structure for table `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `id` int(11) NOT NULL,
  `wallet_id` int(11) NOT NULL,
  `transaction_type` enum('credit','debit') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `balance_before` decimal(12,2) NOT NULL,
  `balance_after` decimal(12,2) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL COMMENT 'order, refund, commission, etc.',
  `reference_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL COMMENT 'External transaction ID',
  `status` enum('pending','completed','failed','cancelled') DEFAULT 'completed',
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `waste_prevention_alerts`
--

CREATE TABLE `waste_prevention_alerts` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `alert_type` enum('expiry_warning','overstock','understock','quality_issue','equipment_failure') NOT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `related_entity_type` varchar(50) DEFAULT NULL COMMENT 'ingredient, product, equipment',
  `related_entity_id` int(11) DEFAULT NULL,
  `suggested_actions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`suggested_actions`)),
  `auto_generated` tinyint(1) DEFAULT 1,
  `is_acknowledged` tinyint(1) DEFAULT 0,
  `acknowledged_by` int(11) DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `is_resolved` tinyint(1) DEFAULT 0,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_activity_logs`
--
ALTER TABLE `admin_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `action` (`action`),
  ADD KEY `module` (`module`),
  ADD KEY `entity` (`entity_type`,`entity_id`),
  ADD KEY `severity` (`severity`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `admin_permissions`
--
ALTER TABLE `admin_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_permission` (`user_id`,`module`,`action`,`resource`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `module` (`module`),
  ADD KEY `action` (`action`),
  ADD KEY `granted_by` (`granted_by`);

--
-- Indexes for table `advertisements`
--
ALTER TABLE `advertisements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ad_type` (`ad_type`),
  ADD KEY `status` (`status`),
  ADD KEY `approval_status` (`approval_status`),
  ADD KEY `start_date` (`start_date`),
  ADD KEY `end_date` (`end_date`),
  ADD KEY `priority` (`priority`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `advertisement_analytics`
--
ALTER TABLE `advertisement_analytics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ad_date` (`ad_id`,`date`),
  ADD KEY `ad_id` (`ad_id`),
  ADD KEY `date` (`date`);

--
-- Indexes for table `advertisement_categories`
--
ALTER TABLE `advertisement_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `advertisement_category_relations`
--
ALTER TABLE `advertisement_category_relations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ad_category` (`ad_id`,`category_id`),
  ADD KEY `ad_id` (`ad_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `advertisement_clicks`
--
ALTER TABLE `advertisement_clicks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ad_id` (`ad_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `clicked_at` (`clicked_at`),
  ADD KEY `device_type` (`device_type`);

--
-- Indexes for table `advertisement_placements`
--
ALTER TABLE `advertisement_placements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ad_id` (`ad_id`),
  ADD KEY `page_type` (`page_type`),
  ADD KEY `position` (`position`),
  ADD KEY `is_active` (`is_active`);

--
-- Indexes for table `advertisement_targeting`
--
ALTER TABLE `advertisement_targeting`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ad_id` (`ad_id`),
  ADD KEY `target_type` (`target_type`),
  ADD KEY `target_key` (`target_key`);

--
-- Indexes for table `advertisement_variants`
--
ALTER TABLE `advertisement_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ad_id` (`ad_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `areas`
--
ALTER TABLE `areas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `city_id` (`city_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `is_featured` (`is_featured`);

--
-- Indexes for table `business_types`
--
ALTER TABLE `business_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_product_variant` (`customer_id`,`product_id`,`variant_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `vendor_id` (`vendor_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `level` (`level`),
  ADD KEY `category_type` (`category_type`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `is_featured` (`is_featured`);

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `state_id` (`state_id`),
  ADD KEY `coordinates` (`latitude`,`longitude`);

--
-- Indexes for table `color_palettes`
--
ALTER TABLE `color_palettes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `palette_type` (`palette_type`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `is_default` (`is_default`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `valid_from` (`valid_from`),
  ADD KEY `valid_until` (`valid_until`);

--
-- Indexes for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coupon_id` (`coupon_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `area_id` (`area_id`),
  ADD KEY `city_id` (`city_id`),
  ADD KEY `coordinates` (`latitude`,`longitude`);

--
-- Indexes for table `customer_favorites`
--
ALTER TABLE `customer_favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_vendor` (`customer_id`,`vendor_id`),
  ADD UNIQUE KEY `customer_product` (`customer_id`,`product_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `vendor_id` (`vendor_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `custom_styles`
--
ALTER TABLE `custom_styles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `style_type` (`style_type`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `load_order` (`load_order`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `delivery_assignments`
--
ALTER TABLE `delivery_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `rider_id` (`rider_id`),
  ADD KEY `status` (`status`),
  ADD KEY `assigned_at` (`assigned_at`);

--
-- Indexes for table `delivery_riders`
--
ALTER TABLE `delivery_riders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `is_available` (`is_available`),
  ADD KEY `is_online` (`is_online`),
  ADD KEY `status` (`status`),
  ADD KEY `location` (`current_location_lat`,`current_location_lng`);

--
-- Indexes for table `design_components`
--
ALTER TABLE `design_components`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_component` (`component_type`,`component_name`),
  ADD KEY `component_type` (`component_type`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `category` (`category`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `file_access_logs`
--
ALTER TABLE `file_access_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `file_id` (`file_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `access_type` (`access_type`),
  ADD KEY `access_time` (`access_time`);

--
-- Indexes for table `file_associations`
--
ALTER TABLE `file_associations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_association` (`file_id`,`entity_type`,`entity_id`,`association_type`),
  ADD KEY `file_id` (`file_id`),
  ADD KEY `entity` (`entity_type`,`entity_id`),
  ADD KEY `association_type` (`association_type`);

--
-- Indexes for table `file_uploads`
--
ALTER TABLE `file_uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `file_type` (`file_type`),
  ADD KEY `category` (`category`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `ingredients`
--
ALTER TABLE `ingredients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category` (`category`),
  ADD KEY `is_perishable` (`is_perishable`),
  ADD KEY `is_active` (`is_active`);

--
-- Indexes for table `ingredient_usage_optimization`
--
ALTER TABLE `ingredient_usage_optimization`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vendor_id` (`vendor_id`),
  ADD KEY `ingredient_id` (`ingredient_id`),
  ADD KEY `optimization_type` (`optimization_type`),
  ADD KEY `implementation_status` (`implementation_status`),
  ADD KEY `fk_optimization_implementer` (`implemented_by`);

--
-- Indexes for table `inventory_alerts`
--
ALTER TABLE `inventory_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vendor_id` (`vendor_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `alert_type` (`alert_type`),
  ADD KEY `severity` (`severity`),
  ADD KEY `is_resolved` (`is_resolved`),
  ADD KEY `resolved_by` (`resolved_by`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `inventory_batches`
--
ALTER TABLE `inventory_batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `batch_number` (`batch_number`),
  ADD KEY `inventory_id` (`inventory_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `expiry_date` (`expiry_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vendor_category_name` (`vendor_id`,`name`),
  ADD KEY `vendor_id` (`vendor_id`),
  ADD KEY `is_active` (`is_active`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vendor_sku` (`vendor_id`,`sku`),
  ADD KEY `vendor_id` (`vendor_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `current_stock` (`current_stock`),
  ADD KEY `minimum_stock` (`minimum_stock`),
  ADD KEY `is_perishable` (`is_perishable`),
  ADD KEY `expiry_date` (`expiry_date`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `last_updated_by` (`last_updated_by`);

--
-- Indexes for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_id` (`inventory_id`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `reference` (`reference_type`,`reference_id`),
  ADD KEY `transaction_type` (`transaction_type`);

--
-- Indexes for table `inventory_waste`
--
ALTER TABLE `inventory_waste`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vendor_id` (`vendor_id`),
  ADD KEY `ingredient_id` (`ingredient_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `reported_by` (`reported_by`),
  ADD KEY `waste_reason` (`waste_reason`);

--
-- Indexes for table `kitchen_equipment`
--
ALTER TABLE `kitchen_equipment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vendor_id` (`vendor_id`),
  ADD KEY `station_id` (`station_id`),
  ADD KEY `equipment_type` (`equipment_type`),
  ADD KEY `status` (`status`),
  ADD KEY `next_maintenance` (`next_maintenance`);

--
-- Indexes for table `kitchen_performance_metrics`
--
ALTER TABLE `kitchen_performance_metrics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vendor_date` (`vendor_id`,`date`),
  ADD KEY `vendor_id` (`vendor_id`),
  ADD KEY `date` (`date`);

--
-- Indexes for table `kitchen_stations`
--
ALTER TABLE `kitchen_stations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vendor_id` (`vendor_id`),
  ADD KEY `station_type` (`station_type`),
  ADD KEY `is_active` (`is_active`);

--
-- Indexes for table `kitchen_workflows`
--
ALTER TABLE `kitchen_workflows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `vendor_id` (`vendor_id`),
  ADD KEY `current_station_id` (`current_station_id`),
  ADD KEY `workflow_status` (`workflow_status`),
  ADD KEY `priority` (`priority`),
  ADD KEY `assigned_chef` (`assigned_chef`);

--
-- Indexes for table `kitchen_workflow_steps`
--
ALTER TABLE `kitchen_workflow_steps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workflow_id` (`workflow_id`),
  ADD KEY `station_id` (`station_id`),
  ADD KEY `status` (`status`),
  ADD KEY `completed_by` (`completed_by`);

--
-- Indexes for table `location_management_logs`
--
ALTER TABLE `location_management_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `action` (`action`),
  ADD KEY `location_type` (`location_type`),
  ADD KEY `location_id` (`location_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `type` (`type`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `vendor_id` (`vendor_id`),
  ADD KEY `delivery_address_id` (`delivery_address_id`),
  ADD KEY `delivery_rider_id` (`delivery_rider_id`),
  ADD KEY `status` (`status`),
  ADD KEY `payment_status` (`payment_status`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `variant_id` (`variant_id`),
  ADD KEY `preparation_status` (`preparation_status`),
  ADD KEY `prepared_by` (`prepared_by`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `changed_by` (`changed_by`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `page_layouts`
--
ALTER TABLE `page_layouts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_page_layout` (`page_name`,`layout_name`),
  ADD KEY `page_name` (`page_name`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `is_default` (`is_default`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`),
  ADD KEY `token` (`token`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `payment_method` (`payment_method`),
  ADD KEY `status` (`status`),
  ADD KEY `transaction_id` (`transaction_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vendor_slug` (`vendor_id`,`slug`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `brand_id` (`brand_id`),
  ADD KEY `sku` (`sku`),
  ADD KEY `is_available` (`is_available`),
  ADD KEY `is_featured` (`is_featured`),
  ADD KEY `is_trending` (`is_trending`),
  ADD KEY `rating` (`rating`),
  ADD KEY `price` (`price`);

--
-- Indexes for table `product_addons`
--
ALTER TABLE `product_addons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `is_active` (`is_active`);

--
-- Indexes for table `product_recipes`
--
ALTER TABLE `product_recipes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_ingredient` (`product_id`,`ingredient_id`),
  ADD KEY `ingredient_id` (`ingredient_id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `is_active` (`is_active`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `po_number` (`po_number`),
  ADD KEY `vendor_id` (`vendor_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `status` (`status`),
  ADD KEY `order_date` (`order_date`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `po_id` (`po_id`),
  ADD KEY `ingredient_id` (`ingredient_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_customer` (`order_id`,`customer_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `vendor_id` (`vendor_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `overall_rating` (`overall_rating`),
  ADD KEY `is_verified` (`is_verified`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`),
  ADD KEY `group` (`group`);

--
-- Indexes for table `site_banners`
--
ALTER TABLE `site_banners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `banner_type` (`banner_type`),
  ADD KEY `position` (`position`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `start_date` (`start_date`),
  ADD KEY `end_date` (`end_date`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `site_configurations`
--
ALTER TABLE `site_configurations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_config` (`section`,`key`),
  ADD KEY `section` (`section`),
  ADD KEY `is_public` (`is_public`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `site_images`
--
ALTER TABLE `site_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `image_type` (`image_type`),
  ADD KEY `is_active` (`is_active`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `site_themes`
--
ALTER TABLE `site_themes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `theme_type` (`theme_type`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `festival_name` (`festival_name`),
  ADD KEY `start_date` (`start_date`),
  ADD KEY `end_date` (`end_date`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `states`
--
ALTER TABLE `states`
  ADD PRIMARY KEY (`id`),
  ADD KEY `country_id` (`country_id`);

--
-- Indexes for table `sub_areas`
--
ALTER TABLE `sub_areas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `area_id` (`area_id`),
  ADD KEY `town_id` (`town_id`),
  ADD KEY `area_type` (`area_type`),
  ADD KEY `delivery_priority` (`delivery_priority`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `area_id` (`area_id`),
  ADD KEY `city_id` (`city_id`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `rating` (`rating`);

--
-- Indexes for table `system_maintenance`
--
ALTER TABLE `system_maintenance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `maintenance_type` (`maintenance_type`),
  ADD KEY `status` (`status`),
  ADD KEY `priority` (`priority`),
  ADD KEY `scheduled_start` (`scheduled_start`),
  ADD KEY `performed_by` (`performed_by`);

--
-- Indexes for table `theme_change_history`
--
ALTER TABLE `theme_change_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `from_theme_id` (`from_theme_id`),
  ADD KEY `to_theme_id` (`to_theme_id`),
  ADD KEY `change_type` (`change_type`),
  ADD KEY `applied_at` (`applied_at`),
  ADD KEY `changed_by` (`changed_by`);

--
-- Indexes for table `theme_configurations`
--
ALTER TABLE `theme_configurations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_theme_config` (`theme_id`,`section`,`element`,`property`),
  ADD KEY `theme_id` (`theme_id`),
  ADD KEY `section` (`section`),
  ADD KEY `element` (`element`);

--
-- Indexes for table `towns`
--
ALTER TABLE `towns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `city_id` (`city_id`),
  ADD KEY `upazila_code` (`upazila_code`),
  ADD KEY `delivery_available` (`delivery_available`),
  ADD KEY `coordinates` (`latitude`,`longitude`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `typography_settings`
--
ALTER TABLE `typography_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `is_default` (`is_default`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role` (`role`),
  ADD KEY `status` (`status`),
  ADD KEY `approval_status` (`approval_status`),
  ADD KEY `email_verified_at` (`email_verified_at`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `blocked_by` (`blocked_by`);

--
-- Indexes for table `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `activity_type` (`activity_type`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `flagged_for_review` (`flagged_for_review`),
  ADD KEY `risk_score` (`risk_score`);

--
-- Indexes for table `user_approval_requests`
--
ALTER TABLE `user_approval_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `request_type` (`request_type`),
  ADD KEY `current_status` (`current_status`),
  ADD KEY `submitted_at` (`submitted_at`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `user_blocks`
--
ALTER TABLE `user_blocks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `blocked_by` (`blocked_by`),
  ADD KEY `block_type` (`block_type`),
  ADD KEY `blocked_at` (`blocked_at`),
  ADD KEY `expires_at` (`expires_at`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `fk_blocks_unblocker` (`unblocked_by`);

--
-- Indexes for table `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favorite` (`user_id`,`item_type`,`item_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `item_type_id` (`item_type`,`item_id`);

--
-- Indexes for table `user_role_changes`
--
ALTER TABLE `user_role_changes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `changed_by` (`changed_by`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_sessions_user_id_index` (`user_id`),
  ADD KEY `user_sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `user_verifications`
--
ALTER TABLE `user_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `verification_type` (`verification_type`),
  ADD KEY `status` (`status`),
  ADD KEY `verified_by` (`verified_by`);

--
-- Indexes for table `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `business_type_id` (`business_type_id`),
  ADD KEY `area_id` (`area_id`),
  ADD KEY `city_id` (`city_id`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `is_featured` (`is_featured`),
  ADD KEY `rating` (`rating`),
  ADD KEY `coordinates` (`latitude`,`longitude`);

--
-- Indexes for table `vendor_hours`
--
ALTER TABLE `vendor_hours`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vendor_day` (`vendor_id`,`day_of_week`);

--
-- Indexes for table `vendor_inventory`
--
ALTER TABLE `vendor_inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vendor_id` (`vendor_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `ingredient_id` (`ingredient_id`),
  ADD KEY `variant_id` (`variant_id`),
  ADD KEY `current_stock` (`current_stock`);

--
-- Indexes for table `vendor_settings`
--
ALTER TABLE `vendor_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_vendor` (`vendor_id`);

--
-- Indexes for table `vendor_staff`
--
ALTER TABLE `vendor_staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vendor_user` (`vendor_id`,`user_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `wallets`
--
ALTER TABLE `wallets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `is_active` (`is_active`);

--
-- Indexes for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wallet_id` (`wallet_id`),
  ADD KEY `transaction_type` (`transaction_type`),
  ADD KEY `reference` (`reference_type`,`reference_id`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `waste_prevention_alerts`
--
ALTER TABLE `waste_prevention_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vendor_id` (`vendor_id`),
  ADD KEY `alert_type` (`alert_type`),
  ADD KEY `severity` (`severity`),
  ADD KEY `is_acknowledged` (`is_acknowledged`),
  ADD KEY `is_resolved` (`is_resolved`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `fk_waste_alerts_acknowledger` (`acknowledged_by`),
  ADD KEY `fk_waste_alerts_resolver` (`resolved_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_activity_logs`
--
ALTER TABLE `admin_activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_permissions`
--
ALTER TABLE `admin_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `advertisements`
--
ALTER TABLE `advertisements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `advertisement_analytics`
--
ALTER TABLE `advertisement_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `advertisement_categories`
--
ALTER TABLE `advertisement_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `advertisement_category_relations`
--
ALTER TABLE `advertisement_category_relations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `advertisement_clicks`
--
ALTER TABLE `advertisement_clicks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `advertisement_placements`
--
ALTER TABLE `advertisement_placements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `advertisement_targeting`
--
ALTER TABLE `advertisement_targeting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `advertisement_variants`
--
ALTER TABLE `advertisement_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `areas`
--
ALTER TABLE `areas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `business_types`
--
ALTER TABLE `business_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `color_palettes`
--
ALTER TABLE `color_palettes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `customer_favorites`
--
ALTER TABLE `customer_favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `custom_styles`
--
ALTER TABLE `custom_styles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_assignments`
--
ALTER TABLE `delivery_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_riders`
--
ALTER TABLE `delivery_riders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `design_components`
--
ALTER TABLE `design_components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `file_access_logs`
--
ALTER TABLE `file_access_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `file_associations`
--
ALTER TABLE `file_associations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `file_uploads`
--
ALTER TABLE `file_uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ingredients`
--
ALTER TABLE `ingredients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `ingredient_usage_optimization`
--
ALTER TABLE `ingredient_usage_optimization`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_alerts`
--
ALTER TABLE `inventory_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_batches`
--
ALTER TABLE `inventory_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_waste`
--
ALTER TABLE `inventory_waste`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kitchen_equipment`
--
ALTER TABLE `kitchen_equipment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `kitchen_performance_metrics`
--
ALTER TABLE `kitchen_performance_metrics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kitchen_stations`
--
ALTER TABLE `kitchen_stations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `kitchen_workflows`
--
ALTER TABLE `kitchen_workflows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kitchen_workflow_steps`
--
ALTER TABLE `kitchen_workflow_steps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `location_management_logs`
--
ALTER TABLE `location_management_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `page_layouts`
--
ALTER TABLE `page_layouts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=231;

--
-- AUTO_INCREMENT for table `product_addons`
--
ALTER TABLE `product_addons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_recipes`
--
ALTER TABLE `product_recipes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `site_banners`
--
ALTER TABLE `site_banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `site_configurations`
--
ALTER TABLE `site_configurations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `site_images`
--
ALTER TABLE `site_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `site_themes`
--
ALTER TABLE `site_themes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `states`
--
ALTER TABLE `states`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `sub_areas`
--
ALTER TABLE `sub_areas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `system_maintenance`
--
ALTER TABLE `system_maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `theme_change_history`
--
ALTER TABLE `theme_change_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `theme_configurations`
--
ALTER TABLE `theme_configurations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `towns`
--
ALTER TABLE `towns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `typography_settings`
--
ALTER TABLE `typography_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_approval_requests`
--
ALTER TABLE `user_approval_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_blocks`
--
ALTER TABLE `user_blocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_favorites`
--
ALTER TABLE `user_favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_role_changes`
--
ALTER TABLE `user_role_changes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_verifications`
--
ALTER TABLE `user_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `vendors`
--
ALTER TABLE `vendors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `vendor_hours`
--
ALTER TABLE `vendor_hours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vendor_inventory`
--
ALTER TABLE `vendor_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vendor_settings`
--
ALTER TABLE `vendor_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `vendor_staff`
--
ALTER TABLE `vendor_staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wallets`
--
ALTER TABLE `wallets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `waste_prevention_alerts`
--
ALTER TABLE `waste_prevention_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_activity_logs`
--
ALTER TABLE `admin_activity_logs`
  ADD CONSTRAINT `fk_admin_logs_user` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admin_permissions`
--
ALTER TABLE `admin_permissions`
  ADD CONSTRAINT `fk_permissions_granter` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_permissions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `advertisements`
--
ALTER TABLE `advertisements`
  ADD CONSTRAINT `fk_ads_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ads_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `advertisement_analytics`
--
ALTER TABLE `advertisement_analytics`
  ADD CONSTRAINT `fk_analytics_ad` FOREIGN KEY (`ad_id`) REFERENCES `advertisements` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `advertisement_categories`
--
ALTER TABLE `advertisement_categories`
  ADD CONSTRAINT `fk_ad_categories_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `advertisement_category_relations`
--
ALTER TABLE `advertisement_category_relations`
  ADD CONSTRAINT `fk_ad_relations_ad` FOREIGN KEY (`ad_id`) REFERENCES `advertisements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ad_relations_category` FOREIGN KEY (`category_id`) REFERENCES `advertisement_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `advertisement_clicks`
--
ALTER TABLE `advertisement_clicks`
  ADD CONSTRAINT `fk_clicks_ad` FOREIGN KEY (`ad_id`) REFERENCES `advertisements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_clicks_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `advertisement_placements`
--
ALTER TABLE `advertisement_placements`
  ADD CONSTRAINT `fk_placements_ad` FOREIGN KEY (`ad_id`) REFERENCES `advertisements` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `advertisement_targeting`
--
ALTER TABLE `advertisement_targeting`
  ADD CONSTRAINT `fk_targeting_ad` FOREIGN KEY (`ad_id`) REFERENCES `advertisements` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `advertisement_variants`
--
ALTER TABLE `advertisement_variants`
  ADD CONSTRAINT `fk_variants_ad` FOREIGN KEY (`ad_id`) REFERENCES `advertisements` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `areas`
--
ALTER TABLE `areas`
  ADD CONSTRAINT `fk_areas_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_areas_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `fk_cart_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cart_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cart_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cities`
--
ALTER TABLE `cities`
  ADD CONSTRAINT `fk_cities_state` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `color_palettes`
--
ALTER TABLE `color_palettes`
  ADD CONSTRAINT `fk_palettes_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  ADD CONSTRAINT `fk_coupon_usage_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_coupon_usage_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_coupon_usage_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD CONSTRAINT `fk_addresses_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_addresses_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_addresses_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_favorites`
--
ALTER TABLE `customer_favorites`
  ADD CONSTRAINT `fk_favorites_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_favorites_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_favorites_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `custom_styles`
--
ALTER TABLE `custom_styles`
  ADD CONSTRAINT `fk_styles_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `delivery_assignments`
--
ALTER TABLE `delivery_assignments`
  ADD CONSTRAINT `fk_assignments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_assignments_rider` FOREIGN KEY (`rider_id`) REFERENCES `delivery_riders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `delivery_riders`
--
ALTER TABLE `delivery_riders`
  ADD CONSTRAINT `fk_riders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `design_components`
--
ALTER TABLE `design_components`
  ADD CONSTRAINT `fk_components_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `file_access_logs`
--
ALTER TABLE `file_access_logs`
  ADD CONSTRAINT `fk_access_logs_file` FOREIGN KEY (`file_id`) REFERENCES `file_uploads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_access_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `file_associations`
--
ALTER TABLE `file_associations`
  ADD CONSTRAINT `fk_associations_file` FOREIGN KEY (`file_id`) REFERENCES `file_uploads` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `file_uploads`
--
ALTER TABLE `file_uploads`
  ADD CONSTRAINT `fk_uploads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ingredient_usage_optimization`
--
ALTER TABLE `ingredient_usage_optimization`
  ADD CONSTRAINT `fk_optimization_implementer` FOREIGN KEY (`implemented_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_optimization_ingredient` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_optimization_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_alerts`
--
ALTER TABLE `inventory_alerts`
  ADD CONSTRAINT `fk_inventory_alerts_item` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inventory_alerts_resolver` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inventory_alerts_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_batches`
--
ALTER TABLE `inventory_batches`
  ADD CONSTRAINT `fk_batches_inventory` FOREIGN KEY (`inventory_id`) REFERENCES `vendor_inventory` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_batches_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  ADD CONSTRAINT `fk_inventory_categories_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `fk_inventory_items_category` FOREIGN KEY (`category_id`) REFERENCES `inventory_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inventory_items_updater` FOREIGN KEY (`last_updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inventory_items_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD CONSTRAINT `fk_inventory_transactions_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_transactions_batch` FOREIGN KEY (`batch_id`) REFERENCES `inventory_batches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_transactions_inventory` FOREIGN KEY (`inventory_id`) REFERENCES `vendor_inventory` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_waste`
--
ALTER TABLE `inventory_waste`
  ADD CONSTRAINT `fk_waste_batch` FOREIGN KEY (`batch_id`) REFERENCES `inventory_batches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_waste_ingredient` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_waste_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_waste_reporter` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_waste_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kitchen_equipment`
--
ALTER TABLE `kitchen_equipment`
  ADD CONSTRAINT `fk_equipment_station` FOREIGN KEY (`station_id`) REFERENCES `kitchen_stations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_equipment_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kitchen_performance_metrics`
--
ALTER TABLE `kitchen_performance_metrics`
  ADD CONSTRAINT `fk_performance_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kitchen_stations`
--
ALTER TABLE `kitchen_stations`
  ADD CONSTRAINT `fk_stations_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kitchen_workflows`
--
ALTER TABLE `kitchen_workflows`
  ADD CONSTRAINT `fk_workflows_chef` FOREIGN KEY (`assigned_chef`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_workflows_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_workflows_station` FOREIGN KEY (`current_station_id`) REFERENCES `kitchen_stations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_workflows_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kitchen_workflow_steps`
--
ALTER TABLE `kitchen_workflow_steps`
  ADD CONSTRAINT `fk_workflow_steps_station` FOREIGN KEY (`station_id`) REFERENCES `kitchen_stations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_workflow_steps_user` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_workflow_steps_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `kitchen_workflows` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `location_management_logs`
--
ALTER TABLE `location_management_logs`
  ADD CONSTRAINT `fk_location_logs_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_address` FOREIGN KEY (`delivery_address_id`) REFERENCES `customer_addresses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_orders_rider` FOREIGN KEY (`delivery_rider_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_orders_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_items_preparer` FOREIGN KEY (`prepared_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_items_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `fk_status_history_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_status_history_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `page_layouts`
--
ALTER TABLE `page_layouts`
  ADD CONSTRAINT `fk_layouts_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD CONSTRAINT `fk_payment_transactions_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_payment_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_products_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_addons`
--
ALTER TABLE `product_addons`
  ADD CONSTRAINT `fk_addons_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_recipes`
--
ALTER TABLE `product_recipes`
  ADD CONSTRAINT `fk_recipes_ingredient` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_recipes_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `fk_variants_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `fk_po_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_po_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_po_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_po_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `fk_po_items_ingredient` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_po_items_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reviews_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `site_banners`
--
ALTER TABLE `site_banners`
  ADD CONSTRAINT `fk_banners_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `site_configurations`
--
ALTER TABLE `site_configurations`
  ADD CONSTRAINT `fk_config_updater` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `site_themes`
--
ALTER TABLE `site_themes`
  ADD CONSTRAINT `fk_themes_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `states`
--
ALTER TABLE `states`
  ADD CONSTRAINT `fk_states_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sub_areas`
--
ALTER TABLE `sub_areas`
  ADD CONSTRAINT `fk_sub_areas_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sub_areas_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sub_areas_town` FOREIGN KEY (`town_id`) REFERENCES `towns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD CONSTRAINT `fk_suppliers_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_suppliers_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `system_maintenance`
--
ALTER TABLE `system_maintenance`
  ADD CONSTRAINT `fk_maintenance_performer` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `theme_change_history`
--
ALTER TABLE `theme_change_history`
  ADD CONSTRAINT `fk_history_changer` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_history_from_theme` FOREIGN KEY (`from_theme_id`) REFERENCES `site_themes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_history_to_theme` FOREIGN KEY (`to_theme_id`) REFERENCES `site_themes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `theme_configurations`
--
ALTER TABLE `theme_configurations`
  ADD CONSTRAINT `fk_theme_configs_theme` FOREIGN KEY (`theme_id`) REFERENCES `site_themes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `towns`
--
ALTER TABLE `towns`
  ADD CONSTRAINT `fk_towns_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_towns_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `typography_settings`
--
ALTER TABLE `typography_settings`
  ADD CONSTRAINT `fk_typography_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_users_blocker` FOREIGN KEY (`blocked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  ADD CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_approval_requests`
--
ALTER TABLE `user_approval_requests`
  ADD CONSTRAINT `fk_approval_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_approval_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_approval_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_blocks`
--
ALTER TABLE `user_blocks`
  ADD CONSTRAINT `fk_blocks_blocker` FOREIGN KEY (`blocked_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_blocks_unblocker` FOREIGN KEY (`unblocked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_blocks_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_role_changes`
--
ALTER TABLE `user_role_changes`
  ADD CONSTRAINT `fk_role_changes_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_role_changes_changer` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_role_changes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_verifications`
--
ALTER TABLE `user_verifications`
  ADD CONSTRAINT `fk_verifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_verifications_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `vendors`
--
ALTER TABLE `vendors`
  ADD CONSTRAINT `fk_vendors_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_vendors_business_type` FOREIGN KEY (`business_type_id`) REFERENCES `business_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_vendors_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_vendors_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_hours`
--
ALTER TABLE `vendor_hours`
  ADD CONSTRAINT `fk_hours_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_inventory`
--
ALTER TABLE `vendor_inventory`
  ADD CONSTRAINT `fk_inventory_ingredient` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inventory_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inventory_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inventory_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_staff`
--
ALTER TABLE `vendor_staff`
  ADD CONSTRAINT `fk_staff_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_staff_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_staff_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wallets`
--
ALTER TABLE `wallets`
  ADD CONSTRAINT `fk_wallets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD CONSTRAINT `fk_wallet_transactions_wallet` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `waste_prevention_alerts`
--
ALTER TABLE `waste_prevention_alerts`
  ADD CONSTRAINT `fk_waste_alerts_acknowledger` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_waste_alerts_resolver` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_waste_alerts_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
