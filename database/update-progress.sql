USE fittrack_gym;
-- Create this table to store related project data.
CREATE TABLE IF NOT EXISTS progress_records(
 id INT AUTO_INCREMENT PRIMARY KEY,
 member_id INT NOT NULL,
 record_date DATE NOT NULL,
 weight DECIMAL(6,2) NOT NULL,
 height DECIMAL(6,2) NULL,
 body_fat DECIMAL(5,2) NULL,
 chest DECIMAL(6,2) NULL,
 waist DECIMAL(6,2) NULL,
 arms DECIMAL(6,2) NULL,
 notes VARCHAR(500),
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(member_id) REFERENCES members(id) ON DELETE CASCADE
);
