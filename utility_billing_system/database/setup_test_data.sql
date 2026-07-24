-- SQL Script to setup test data for the portals
-- Run this script to assign readers to meters for testing

-- Assign reader ID 1 (lucas) to meter ID 1
UPDATE meter SET ReaderID = 1 WHERE MeterID = 1;

-- Make sure the meter is active
UPDATE meter SET Status = 'active' WHERE MeterID = 1;

-- Optional: Create some sample test passwords
-- All passwords below are: password123

-- Update reader passwords (if needed for testing)
-- UPDATE utilityreader SET Password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE ReaderID = 1;

-- Update consumer passwords (if needed for testing)
-- UPDATE consumer SET Password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE ConsumerID = 2;

-- Verify the assignments
SELECT 
    m.MeterID,
    m.MeterNumber,
    CONCAT(c.FirstName, ' ', c.LastName) as Consumer,
    CONCAT(r.FirstName, ' ', r.LastName) as AssignedReader,
    r.Username as ReaderUsername
FROM meter m
LEFT JOIN consumer c ON m.ConsumerID = c.ConsumerID
LEFT JOIN utilityreader r ON m.ReaderID = r.ReaderID;
