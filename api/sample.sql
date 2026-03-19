-- データベース作成
CREATE DATABASE IF NOT EXISTS picking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE picking;

-- modelテーブル
CREATE TABLE IF NOT EXISTS model (
    id INT AUTO_INCREMENT PRIMARY KEY,
    model VARCHAR(50) NOT NULL COMMENT 'モデル名',
    block VARCHAR(100) NOT NULL COMMENT '工程ブロック名',
    matome TINYINT(1) DEFAULT 0 COMMENT '0:投入順, 1:まとめ',
    reg_time DATETIME DEFAULT NOW() COMMENT '登録日時',
    INDEX idx_model (model),
    INDEX idx_block (block)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='工程ブロックマスタ';

-- 初期データ投入
INSERT INTO model (model, block, matome) VALUES
('sword', 'PWSub', 0),
('sword', 'D-Sub', 0),
('sword', 'P-Sub', 0),
('sword', 'P-Subまとめ', 1),
('sword', 'LCD', 0),
('sword', 'P-Sub両面テープ', 0),
('sword', 'SSD', 0),
('sword', 'Palmまとめ', 1),
('sword', 'LCDまとめ', 1),
('sword', 'FOOT', 0),
('sword', 'Palm', 0),
('sword', 'BASE', 0);

-- locationテーブル
CREATE TABLE IF NOT EXISTS location (
    id INT AUTO_INCREMENT PRIMARY KEY,
    part_number VARCHAR(50) NOT NULL COMMENT '部品番号',
    location VARCHAR(50) NOT NULL COMMENT 'ロケーション',
    part_name VARCHAR(200) COMMENT '部品名称',
    update_at DATETIME DEFAULT NOW() ON UPDATE NOW() COMMENT '更新日時',
    INDEX idx_part_number (part_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='部品ロケーション';

-- parts_ruleテーブル（投入順部品用）
CREATE TABLE IF NOT EXISTS parts_rule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    koutei VARCHAR(100) COMMENT '工程ブロック',
    type VARCHAR(50) COMMENT '種別（基準部品など）',
    part_number VARCHAR(50) COMMENT '部品番号',
    series VARCHAR(50) COMMENT 'シリーズ名',
    part_name VARCHAR(200) COMMENT '部品名称',
    box_qty INT DEFAULT 1 COMMENT '1箱入数',
    partition_qty INT DEFAULT 1 COMMENT '箱内仕切数',
    partition_content INT DEFAULT 1 COMMENT '仕切入数',
    partition_blank INT DEFAULT 0 COMMENT '仕切ブランク',
    daisha_qty INT DEFAULT 1 COMMENT '台車積載箱数',
    required_qty INT DEFAULT 1 COMMENT '所要数',
    tag_comment VARCHAR(200) COMMENT 'TAGコメント',
    model_tag VARCHAR(50) COMMENT '機種TAG分割',
    tag_comment2 VARCHAR(200) COMMENT 'TAGコメント2',
    update_at DATETIME DEFAULT NOW() ON UPDATE NOW(),
    INDEX idx_koutei (koutei),
    INDEX idx_part_number (part_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='投入順部品ルール';

-- parts_matome_ruleテーブル（まとめ部品用）
CREATE TABLE IF NOT EXISTS parts_matome_rule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    koutei VARCHAR(100) COMMENT '工程ブロック',
    part_number VARCHAR(50) COMMENT '部品番号',
    part_name VARCHAR(200) COMMENT '部品名称',
    min_stock INT DEFAULT 0 COMMENT '最低ライン在庫数',
    box_qty INT DEFAULT 1 COMMENT '1箱入数',
    update_at DATETIME DEFAULT NOW() ON UPDATE NOW(),
    INDEX idx_koutei (koutei),
    INDEX idx_part_number (part_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='まとめ部品ルール';

-- サンプルデータ（parts_rule）
INSERT INTO parts_rule (koutei, type, part_number, series, part_name, box_qty, partition_qty, daisha_qty, required_qty) VALUES
('LCD', '基準部品', 'Pic15', '', 'ASSY BEZEL*', 20, 20, 4, 1),
('LCD', '', 'Pic7', '', 'WLAN_ANTENNA_AUX*', 20, 20, 4, 1),
('LCD', '', 'Pic6', '', 'WLAN_ANTENNA_MAIN*', 20, 20, 4, 1);

-- サンプルデータ（parts_matome_rule）
INSERT INTO parts_matome_rule (koutei, part_number, part_name, min_stock, box_qty) VALUES
('LCDまとめ', '1-V01-543-01', 'HD CAMERA(YHJD-1)NO SPONGE', 40, 40),
('LCDまとめ', '1-V01-616-01', 'HELLO CAM RPL(YHSG-5)', 600, 600);

-- サンプルロケーションデータ
INSERT INTO location (part_number, location, part_name) VALUES
('Pic15', '1D6-01-02-02', 'ASSY BEZEL 8880'),
('Pic7', '1D6-08-02', 'WLAN_ANTENNA_AUX_8940'),
('Pic6', '1D6-08-01', 'WLAN_ANTENNA_MAIN_8940'),
('1-V01-543-01', '1M6-03-13-13', 'HD CAMERA(YHJD-1)NO SPONGE'),
('1-V01-616-01', '1M6-02-12-12', 'HELLO CAM RPL(YHSG-5)');