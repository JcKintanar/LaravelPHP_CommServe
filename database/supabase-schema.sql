-- CommServe PostgreSQL Schema for Supabase
-- Convert MySQL/MariaDB schema to PostgreSQL

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Table: regions
CREATE TABLE IF NOT EXISTS regions (
  id SERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  code VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: provinces
CREATE TABLE IF NOT EXISTS provinces (
  id SERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  region_id INTEGER NOT NULL REFERENCES regions(id) ON DELETE CASCADE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(name, region_id)
);

-- Table: municipalities
CREATE TABLE IF NOT EXISTS municipalities (
  id SERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  province VARCHAR(100) DEFAULT 'Cebu',
  region_id INTEGER REFERENCES regions(id) ON DELETE SET NULL,
  province_id INTEGER REFERENCES provinces(id) ON DELETE SET NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: barangays
CREATE TABLE IF NOT EXISTS barangays (
  id SERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  municipality_id INTEGER NOT NULL REFERENCES municipalities(id) ON DELETE CASCADE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(name, municipality_id)
);

-- Table: users
CREATE TABLE IF NOT EXISTS users (
  id SERIAL PRIMARY KEY,
  lastName VARCHAR(50) NOT NULL,
  firstName VARCHAR(50) NOT NULL,
  middleName VARCHAR(50),
  region VARCHAR(10) NOT NULL,
  region_id INTEGER REFERENCES regions(id) ON DELETE SET NULL,
  province VARCHAR(100) NOT NULL,
  province_id INTEGER REFERENCES provinces(id) ON DELETE SET NULL,
  cityMunicipality VARCHAR(50) NOT NULL,
  municipality_id INTEGER REFERENCES municipalities(id) ON DELETE SET NULL,
  barangay VARCHAR(50) NOT NULL,
  barangay_id INTEGER REFERENCES barangays(id) ON DELETE SET NULL,
  sitio VARCHAR(50),
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(100) NOT NULL UNIQUE,
  phoneNumber VARCHAR(20) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  resetToken VARCHAR(255),
  resetExpires TIMESTAMP,
  role VARCHAR(20) NOT NULL DEFAULT 'user' CHECK (role IN ('user', 'admin', 'official')),
  createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  dateOfBirth DATE,
  civilStatus VARCHAR(20) CHECK (civilStatus IN ('Single', 'Married', 'Widowed', 'Divorced', 'Separated')),
  yearResidency INTEGER
);

-- Create index on users for faster lookups
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_barangay ON users(barangay_id);

-- Table: announcements
CREATE TABLE IF NOT EXISTS announcements (
  id SERIAL PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  icon VARCHAR(100) DEFAULT 'bi-megaphone-fill',
  color VARCHAR(50) DEFAULT 'primary',
  image_path VARCHAR(500),
  barangay VARCHAR(100),
  cityMunicipality VARCHAR(100),
  barangay_id INTEGER REFERENCES barangays(id) ON DELETE SET NULL,
  createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  municipality_id INTEGER,
  region VARCHAR(100),
  region_id INTEGER,
  province VARCHAR(100),
  province_id INTEGER,
  scope_level VARCHAR(50) DEFAULT 'ALL',
  target_id INTEGER,
  created_by INTEGER REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_announcements_barangay ON announcements(barangay_id);
CREATE INDEX idx_announcements_created ON announcements(createdAt DESC);

-- Table: emergency_hotlines
CREATE TABLE IF NOT EXISTS emergency_hotlines (
  id SERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  barangay VARCHAR(100) NOT NULL,
  cityMunicipality VARCHAR(50) NOT NULL,
  number VARCHAR(30) NOT NULL,
  description VARCHAR(255),
  createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  scope_level VARCHAR(50) DEFAULT 'BARANGAY',
  target_id INTEGER,
  barangay_id INTEGER,
  municipality_id INTEGER,
  province_id INTEGER,
  region_id INTEGER,
  created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
  province VARCHAR(100),
  region VARCHAR(100)
);

CREATE INDEX idx_hotlines_barangay ON emergency_hotlines(barangay_id);

-- Table: document_requests
CREATE TABLE IF NOT EXISTS document_requests (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  document_type VARCHAR(100) NOT NULL,
  full_name VARCHAR(255) NOT NULL DEFAULT '',
  date_of_birth DATE,
  civil_status VARCHAR(20) CHECK (civil_status IN ('Single', 'Married', 'Widowed', 'Divorced', 'Separated')),
  sitio_address VARCHAR(255),
  years_of_residency INTEGER,
  valid_id_path VARCHAR(500),
  uploaded_pdf_path VARCHAR(500),
  purpose TEXT,
  status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'ready', 'released', 'rejected')),
  remarks TEXT,
  requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_doc_requests_user_status ON document_requests(user_id, status);

-- Table: messages
CREATE TABLE IF NOT EXISTS messages (
  id SERIAL PRIMARY KEY,
  sender_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  receiver_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  message TEXT NOT NULL,
  is_read BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_messages_sender ON messages(sender_id);
CREATE INDEX idx_messages_receiver ON messages(receiver_id);
CREATE INDEX idx_messages_read ON messages(is_read);

-- Table: password_resets
CREATE TABLE IF NOT EXISTS password_resets (
  id SERIAL PRIMARY KEY,
  user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
  token VARCHAR(64),
  expires TIMESTAMP
);

CREATE INDEX idx_password_resets_token ON password_resets(token);

-- Table: user_notification_preferences
CREATE TABLE IF NOT EXISTS user_notification_preferences (
  user_id INTEGER PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
  announcements BOOLEAN DEFAULT TRUE,
  hotlines BOOLEAN DEFAULT TRUE,
  documents BOOLEAN DEFAULT TRUE,
  events BOOLEAN DEFAULT TRUE,
  email_notifications BOOLEAN DEFAULT TRUE,
  push_notifications BOOLEAN DEFAULT TRUE,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: user_preferences
CREATE TABLE IF NOT EXISTS user_preferences (
  user_id INTEGER PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
  language VARCHAR(10) DEFAULT 'en',
  theme VARCHAR(20) DEFAULT 'light',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Function to update updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Triggers for updated_at
CREATE TRIGGER update_document_requests_updated_at BEFORE UPDATE ON document_requests 
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_user_notification_preferences_updated_at BEFORE UPDATE ON user_notification_preferences 
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_user_preferences_updated_at BEFORE UPDATE ON user_preferences 
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Insert sample regions (Philippines)
INSERT INTO regions (name, code) VALUES
('Region I - Ilocos Region', 'R01'),
('Region II - Cagayan Valley', 'R02'),
('Region III - Central Luzon', 'R03'),
('Region IV-A - CALABARZON', 'R04A'),
('Region IV-B - MIMAROPA', 'R04B'),
('Region V - Bicol Region', 'R05'),
('Region VI - Western Visayas', 'R06'),
('Region VII - Central Visayas', 'R07'),
('Region VIII - Eastern Visayas', 'R08'),
('Region IX - Zamboanga Peninsula', 'R09'),
('Region X - Northern Mindanao', 'R10'),
('Region XI - Davao Region', 'R11'),
('Region XII - SOCCSKSARGEN', 'R12'),
('Region XIII - Caraga', 'R13'),
('NCR - National Capital Region', 'NCR'),
('CAR - Cordillera Administrative Region', 'CAR'),
('BARMM - Bangsamoro Autonomous Region in Muslim Mindanao', 'BARMM')
ON CONFLICT (name) DO NOTHING;

-- Insert sample provinces for Region VII (Central Visayas)
INSERT INTO provinces (name, region_id)
SELECT 'Cebu', id FROM regions WHERE code = 'R07'
ON CONFLICT DO NOTHING;

INSERT INTO provinces (name, region_id)
SELECT 'Bohol', id FROM regions WHERE code = 'R07'
ON CONFLICT DO NOTHING;

-- Insert sample municipalities/cities
INSERT INTO municipalities (name, province, region_id, province_id)
SELECT 'Cebu City', 'Cebu', r.id, p.id
FROM regions r, provinces p
WHERE r.code = 'R07' AND p.name = 'Cebu'
ON CONFLICT (name) DO NOTHING;

INSERT INTO municipalities (name, province, region_id, province_id)
SELECT 'Mandaue City', 'Cebu', r.id, p.id
FROM regions r, provinces p
WHERE r.code = 'R07' AND p.name = 'Cebu'
ON CONFLICT (name) DO NOTHING;

INSERT INTO municipalities (name, province, region_id, province_id)
SELECT 'Lapu-Lapu City', 'Cebu', r.id, p.id
FROM regions r, provinces p
WHERE r.code = 'R07' AND p.name = 'Cebu'
ON CONFLICT (name) DO NOTHING;

-- Enable Row Level Security (RLS) for better security
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE announcements ENABLE ROW LEVEL SECURITY;
ALTER TABLE emergency_hotlines ENABLE ROW LEVEL SECURITY;
ALTER TABLE document_requests ENABLE ROW LEVEL SECURITY;
ALTER TABLE messages ENABLE ROW LEVEL SECURITY;

-- RLS Policies (users can read their own data)
CREATE POLICY "Users can view their own profile" ON users
  FOR SELECT USING (auth.uid()::text = id::text);

CREATE POLICY "Users can update their own profile" ON users
  FOR UPDATE USING (auth.uid()::text = id::text);

-- Public read for announcements and hotlines
CREATE POLICY "Anyone can view announcements" ON announcements
  FOR SELECT USING (true);

CREATE POLICY "Anyone can view hotlines" ON emergency_hotlines
  FOR SELECT USING (true);

-- Users can view their own document requests
CREATE POLICY "Users can view own document requests" ON document_requests
  FOR SELECT USING (auth.uid()::text = user_id::text);

CREATE POLICY "Users can create document requests" ON document_requests
  FOR INSERT WITH CHECK (auth.uid()::text = user_id::text);

-- Users can view messages sent to them
CREATE POLICY "Users can view their messages" ON messages
  FOR SELECT USING (
    auth.uid()::text = sender_id::text OR 
    auth.uid()::text = receiver_id::text
  );

-- Success message
DO $$
BEGIN
  RAISE NOTICE 'CommServe database schema created successfully!';
  RAISE NOTICE 'Tables created: regions, provinces, municipalities, barangays, users, announcements, emergency_hotlines, document_requests, messages, password_resets, user_notification_preferences, user_preferences';
  RAISE NOTICE 'Sample data inserted for Region VII (Central Visayas)';
END $$;
