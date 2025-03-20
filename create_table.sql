-- Tạo cơ sở dữ liệu
CREATE DATABASE IF NOT EXISTS WebDesignCommunity;
-- Sử dụng cơ sở dữ liệu
USE WebDesignCommunity;

-- Bảng users (Quản lý người dùng)
CREATE TABLE IF NOT EXISTS users (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50) UNIQUE NOT NULL,
    Password VARCHAR(100) NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    Role VARCHAR(20) DEFAULT 'User',  -- (User, Admin)
    JoinDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    Status VARCHAR(20) DEFAULT 'Active',
    Avatar VARCHAR(255) DEFAULT 'images/default-avatar.png',
    Bio TEXT
);

-- Bảng templates (Quản lý mẫu thiết kế)
CREATE TABLE IF NOT EXISTS templates (
    TemplateID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    TemplateName VARCHAR(100) NOT NULL,
    Description VARCHAR(500),
    PreviewImage VARCHAR(255),
    Price DECIMAL(18,2) DEFAULT 0,
    Status VARCHAR(20) DEFAULT 'Pending',  -- (Pending, Approved, Rejected)
    CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    ApprovedDate DATETIME NULL,
    FOREIGN KEY (UserID) REFERENCES users(UserID)
);

-- Bảng purchases (Quản lý giao dịch)
CREATE TABLE IF NOT EXISTS purchases (
    PurchaseID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    TemplateID INT NOT NULL,
    PurchaseDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    Amount DECIMAL(18,2) NOT NULL,
    FOREIGN KEY (UserID) REFERENCES users(UserID),
    FOREIGN KEY (TemplateID) REFERENCES templates(TemplateID)
);

-- Bảng reviews (Đánh giá mẫu thiết kế)
CREATE TABLE IF NOT EXISTS reviews (
    ReviewID INT AUTO_INCREMENT PRIMARY KEY,
    TemplateID INT NOT NULL,
    UserID INT NOT NULL,
    Rating INT CHECK (Rating BETWEEN 1 AND 5),
    Comment VARCHAR(500),
    ReviewDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (TemplateID) REFERENCES templates(TemplateID),
    FOREIGN KEY (UserID) REFERENCES users(UserID)
);

-- Bảng community_designs (Quản lý thiết kế cộng đồng)
CREATE TABLE IF NOT EXISTS community_designs (
    DesignID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    TemplateID INT NOT NULL,
    DesignName VARCHAR(100) NOT NULL,
    Description VARCHAR(500),
    PreviewImage VARCHAR(255),
    Likes INT DEFAULT 0,
    Views INT DEFAULT 0,
    Comments INT DEFAULT 0,
    CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES users(UserID),
    FOREIGN KEY (TemplateID) REFERENCES templates(TemplateID)
);

-- Thêm các cột mới vào bảng templates
ALTER TABLE templates 
ADD COLUMN Styles JSON,
ADD COLUMN LastModified DATETIME,
ADD COLUMN AutoSaved BOOLEAN DEFAULT TRUE;


ALTER TABLE users ADD COLUMN Avatar VARCHAR(255) DEFAULT 'images/default-avatar.png';
ALTER TABLE templates ADD COLUMN HTMLContent LONGTEXT;
ALTER TABLE templates ADD COLUMN Category VARCHAR(50);
ALTER TABLE users ADD COLUMN Bio TEXT;
ALTER TABLE templates ADD COLUMN Views INT DEFAULT 0;

CREATE TABLE IF NOT EXISTS followers (
    FollowerID INT,
    FollowedID INT,
    FollowDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (FollowerID, FollowedID),
    FOREIGN KEY (FollowerID) REFERENCES users(UserID),
    FOREIGN KEY (FollowedID) REFERENCES users(UserID)
);


ALTER TABLE users 
ADD COLUMN TwoFactorEnabled BOOLEAN DEFAULT FALSE,
ADD COLUMN NotificationSettings JSON,
ADD COLUMN AppearanceSettings JSON,
ADD COLUMN CurrentPlan VARCHAR(50) DEFAULT 'Free';

CREATE TABLE IF NOT EXISTS payment_methods (
    PaymentID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT,
    CardType VARCHAR(50),
    LastFourDigits VARCHAR(4),
    ExpiryDate DATE,
    FOREIGN KEY (UserID) REFERENCES users(UserID)
);

CREATE TABLE IF NOT EXISTS billing_history (
    BillingID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT,
    Amount DECIMAL(10,2),
    Description VARCHAR(255),
    Status VARCHAR(50),
    BillingDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES users(UserID)
);


ALTER TABLE templates 
ADD COLUMN CSSContent LONGTEXT AFTER HTMLContent,
ADD COLUMN JSContent LONGTEXT AFTER CSSContent;


-- Xóa bảng cũ nếu tồn tại
DROP TABLE IF EXISTS template_likes;

-- Tạo lại bảng template_likes
CREATE TABLE template_likes (
    UserID INT,
    TemplateID INT,
    LikeDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (UserID, TemplateID),
    FOREIGN KEY (UserID) REFERENCES users(UserID) ON DELETE CASCADE,
    FOREIGN KEY (TemplateID) REFERENCES templates(TemplateID) ON DELETE CASCADE
);

-- Thêm cột Likes vào bảng templates nếu chưa có
ALTER TABLE templates ADD COLUMN IF NOT EXISTS Likes INT DEFAULT 0;

-- Reset lại số lượng likes
UPDATE templates SET Likes = 0;




-- Kiểm tra và tạo lại bảng template_likes nếu cần
CREATE TABLE IF NOT EXISTS template_likes (
    UserID INT,
    TemplateID INT,
    LikeDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (UserID, TemplateID),
    FOREIGN KEY (UserID) REFERENCES users(UserID) ON DELETE CASCADE,
    FOREIGN KEY (TemplateID) REFERENCES templates(TemplateID) ON DELETE CASCADE
);

-- Thêm cột Likes vào bảng templates nếu chưa có
ALTER TABLE templates ADD COLUMN IF NOT EXISTS Likes INT DEFAULT 0;


-- Tạo bảng lưu lịch sử chat với AI
CREATE TABLE IF NOT EXISTS design_webs_ai (
    ChatID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    UserMessage TEXT NOT NULL,
    AIResponse LONGTEXT NOT NULL,
    HTMLCode LONGTEXT,
    CSSCode LONGTEXT,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES users(UserID)
);

CREATE TABLE `purchases` (
    `PurchaseID` int(11) NOT NULL AUTO_INCREMENT,
    `UserID` int(11) NOT NULL,
    `TemplateID` int(11) NOT NULL,
    `TransactionID` varchar(50) NOT NULL,
    `Amount` decimal(10,2) NOT NULL,
    `Status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
    `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `UpdatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`PurchaseID`)
);


CREATE TABLE payment_proofs (
    ProofID INT AUTO_INCREMENT PRIMARY KEY,
    PurchaseID INT NOT NULL,
    ImageProof VARCHAR(255) NOT NULL,
    SubmitDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    Status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    AdminNote TEXT,
    FOREIGN KEY (PurchaseID) REFERENCES purchases(PurchaseID)
);