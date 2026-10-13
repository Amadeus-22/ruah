-- Runs once, when the data volume is first created: a second database for the test suite.
CREATE DATABASE IF NOT EXISTS ruah_test;
GRANT ALL PRIVILEGES ON ruah_test.* TO 'ruah'@'%';
