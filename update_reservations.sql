-- Update existing reservations to calculate total_price and deposit_amount
-- Columns should already exist from db.sql schema
UPDATE reservations r
JOIN rooms rm ON r.room_id = rm.id
SET 
  r.total_price = rm.price * DATEDIFF(r.end_date, r.start_date),
  r.deposit_amount = rm.price * DATEDIFF(r.end_date, r.start_date) * 0.20
WHERE r.total_price = 0 OR r.deposit_amount = 0;
