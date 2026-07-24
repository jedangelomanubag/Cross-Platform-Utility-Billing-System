-- Quick Fix: Assign Reader to Meter
-- Run this in phpMyAdmin to fix the issue

-- Assign reader ID 1 (lucas) to meter ID 1 (meter number 2273444242)
UPDATE meter SET ReaderID = 1 WHERE MeterID = 1;

-- Verify the assignment
SELECT 
    m.MeterID,
    m.MeterNumber,
    m.ReaderID,
    m.ConsumerID,
    m.Status,
    CONCAT(c.FirstName, ' ', c.LastName) as ConsumerName,
    CONCAT(r.FirstName, ' ', r.LastName) as ReaderName
FROM meter m
LEFT JOIN consumer c ON m.ConsumerID = c.ConsumerID
LEFT JOIN utilityreader r ON m.ReaderID = r.ReaderID
WHERE m.MeterID = 1;
