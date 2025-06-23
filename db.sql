CREATE TABLE scripts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL
);

CREATE TABLE scenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    script_id INT NOT NULL,
    scene_number INT NOT NULL,
    scene_title VARCHAT(70) NOT NULL,
    is_completed TINYINT(1) DEFAULT 0,
    FOREIGN KEY (script_id) REFERENCES scripts(id) ON DELETE CASCADE
);

CREATE TABLE scene_contents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scene_id INT NOT NULL,
    content_type ENUM('paragraph', 'dialog') NOT NULL,
    content TEXT NOT NULL,
    is_completed TINYINT(1) DEFAULT 0,
    FOREIGN KEY (scene_id) REFERENCES scenes(id) ON DELETE CASCADE
);