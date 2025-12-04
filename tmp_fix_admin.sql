UPDATE users SET password = '$2y$10$hxPIm97alW9nNMzYyrOYy.02uX25gEleolv7bvCfd6H22b8YLjCoC' WHERE email = 'admin@example.com';
SELECT id,name,password,email,is_admin FROM users;
