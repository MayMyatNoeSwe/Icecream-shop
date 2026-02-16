-- Add password column to customers table
USE icecream_shop;

ALTER TABLE customers 
ADD COLUMN password VARCHAR(255) DEFAULT NULL AFTER email;

-- Note: Existing users will have NULL passwords
-- They can reset their password or register again with a new email
