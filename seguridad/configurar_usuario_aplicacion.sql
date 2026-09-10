CREATE USER IF NOT EXISTS 'arenacjd_app'@'localhost' IDENTIFIED BY 'Maracaibo24158$';
ALTER USER 'arenacjd_app'@'localhost' IDENTIFIED BY 'Maracaibo24158$';

GRANT SELECT, INSERT, UPDATE, DELETE
ON arenacjd.*
TO 'arenacjd_app'@'localhost';

FLUSH PRIVILEGES;
