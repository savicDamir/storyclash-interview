CREATE DATABASE storyclash_prod;


CREATE TABLE feeds (
                       id SERIAL PRIMARY KEY,
                       name VARCHAR(255) NOT NULL
);

CREATE TABLE instagram_sources (
                                   id INT PRIMARY KEY,
                                   name VARCHAR(255) NOT NULL,
                                   fan_count INT CHECK ( fan_count >= 0),
                                   FOREIGN KEY (id) REFERENCES feeds(id) ON DELETE cascade
);

CREATE TABLE tiktok_sources (
                                id INT PRIMARY KEY,
                                name VARCHAR(255) NOT NULL,
                                fan_count INT CHECK ( fan_count >= 0),
                                FOREIGN KEY (id) REFERENCES feeds(id) ON DELETE cascade
);

CREATE TABLE posts (
                       id SERIAL PRIMARY KEY,
                       url VARCHAR(255) NOT NULL,
                       feed_id int NOT NULL,
                       FOREIGN KEY (feed_id) REFERENCES feeds(id) ON DELETE cascade
);

-- Insert into feeds table
INSERT INTO feeds (name) VALUES ('John Doe');
INSERT INTO feeds (name) VALUES ('Jane Smith');
INSERT INTO feeds (name) VALUES ('Alice Johnson');

-- Insert into instagram_sources table
INSERT INTO instagram_sources (id, name, fan_count) VALUES (1, 'johndoe_insta', 15000);
INSERT INTO instagram_sources (id, name, fan_count) VALUES (2, 'janesmith_insta', 25000);
-- Note: Alice Johnson does not have an Instagram source

-- Insert into tiktok_sources table
INSERT INTO tiktok_sources (id, name, fan_count) VALUES (1, 'johndoe_tiktok', 30000);
INSERT INTO tiktok_sources (id, name, fan_count) VALUES (3, 'alicejohnson_tiktok', 5000);
-- Note: Jane Smith does not have a TikTok source

-- Insert into posts table
INSERT INTO posts (feed_id, url) VALUES (1, 'https://example.com/post1');
INSERT INTO posts (feed_id, url) VALUES (1, 'https://example.com/post2');
INSERT INTO posts (feed_id, url) VALUES (2, 'https://example.com/post3');
INSERT INTO posts (feed_id, url) VALUES (3, 'https://example.com/post4');
INSERT INTO posts (feed_id, url) VALUES (3, 'https://example.com/post5');
INSERT INTO posts (feed_id, url) VALUES (3, 'https://example.com/post6');
