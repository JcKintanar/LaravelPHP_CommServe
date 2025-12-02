# CommServe Location-Based Access Control System

## Overview
This system implements a hierarchical location structure (Municipality → Barangay) with role-based access control that restricts users to their assigned locations.

## Database Schema

### Tables Created

#### 1. `municipalities`
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- name (VARCHAR(100), UNIQUE) - Municipality/City name
- province (VARCHAR(100)) - Province name
- created_at (TIMESTAMP)
```

#### 2. `barangays`
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- name (VARCHAR(100)) - Barangay name
- municipality_id (INT, FOREIGN KEY → municipalities.id)
- created_at (TIMESTAMP)
- UNIQUE(name, municipality_id) - Prevent duplicate barangays in same municipality
```

#### 3. `users` (Updated)
```sql
Added columns:
- barangay_id (INT, FOREIGN KEY → barangays.id)
- municipality_id (INT, FOREIGN KEY → municipalities.id)
- FOREIGN KEY constraints with ON DELETE SET NULL
```

#### 4. `announcements` (Updated)
```sql
Added column:
- barangay_id (INT, FOREIGN KEY → barangays.id)
```

## Seed Data Included

### Municipalities (4 cities in Cebu):
1. Mandaue City
2. Cebu City  
3. Talisay City
4. Lapu-Lapu City

### Barangays by Municipality:

**Mandaue City** (10 barangays):
- Alang-Alang, Bakilid, Banilad, Basak, Cabancalan
- Centro (Poblacion), Cambaro, Canduman, Casili, Casuntingan

**Cebu City** (8 sample barangays):
- Apas, Lahug, Guadalupe, Kamputhaw, Mabolo, Talamban, Tisa, Zapatera

**Talisay City** (6 barangays):
- Bulacao, Dumlog, Lagtang, Lawaan, Tabunok, Tangke

**Lapu-Lapu City** (7 barangays):
- Basak, Buaya, Caubian, Looc, Mactan, Marigondon, Pajo

## User Registration Flow

### 1. Cascading Dropdown Implementation

**Frontend (`/pages/Signup.php`)**:
- City/Municipality dropdown (populated from database)
- Barangay dropdown (filtered by selected city)
- JavaScript handles dynamic filtering
- Hidden fields store names for backwards compatibility

**API Endpoints**:
- `/api/get-municipalities.php` - Returns all municipalities
- `/api/get-barangays.php?municipality_id=X` - Returns barangays for specific municipality

### 2. Registration Process
1. User selects Municipality/City from dropdown
2. Barangays filter dynamically based on selection
3. Both IDs and names are stored in database
4. User account is created with location references

## Role-Based Access Control

### Access Levels:

#### 1. **Admin**
- Can view/manage ALL barangays across all municipalities
- No location restrictions
- Full system access

#### 2. **Official**  
- Restricted to ONLY their assigned `barangay_id`
- Cannot view/modify data from other barangays
- Location validated on every request

#### 3. **Resident**
- Can ONLY view data for their registered `barangay_id`
- Read-only access to own barangay services
- Strictly location-bound

## Security Middleware

### Location Middleware (`/includes/locationMiddleware.php`)

#### Functions:

**1. `validateLocationAccess($conn, $user_id, $user_role, $required_barangay_id)`**
- Validates user's location permissions
- Returns authorization status and user location data
- Checks if user can access specific barangay

**2. `getLocationWhereClause($user_role, $user_barangay_id, $table_alias)`**
- Builds SQL WHERE clause for location filtering
- Automatically restricts queries based on role
- Admin: `1=1` (all data)
- Official/Resident: `barangay_id = X` (own barangay only)

**3. `validateBarangayDataAccess($conn, $user_id, $user_role, $data_barangay_id)`**
- Validates if user can access specific barangay data
- Returns boolean
- Used for individual record access checks

### Usage Example:

```php
<?php
require_once __DIR__ . '/includes/locationMiddleware.php';

// Validate user location access
$location_check = validateLocationAccess($conn, $user_id, $user_role);

if (!$location_check['authorized']) {
  die('Access Denied: ' . $location_check['message']);
}

$user_barangay_id = $location_check['user_location']['barangay_id'];

// Build location-filtered query
$where_clause = getLocationWhereClause($user_role, $user_barangay_id, 'a');

$query = "SELECT * FROM announcements a WHERE $where_clause";
```

## Implementation in Existing Pages

### Pages to Update:

1. **Announcements** (`barangayAnnouncement.php`)
   - Add barangay_id to INSERT/UPDATE
   - Filter SELECT by user's barangay

2. **Document Requests** (`manage-requests.php`)
   - Officials only see requests from their barangay
   - Residents only see own barangay services

3. **Emergency Hotlines** (`emergencyHotlines.php`)
   - Display hotlines for user's barangay only

4. **Messages** (`messages.php`)
   - Already filtering by barangay
   - Enhance with barangay_id validation

## API Request Validation

### Middleware Pattern:

```php
// At the top of every protected page
require_once __DIR__ . '/includes/locationMiddleware.php';

// Validate location access
$access = validateLocationAccess($conn, $_SESSION['user_id'], $_SESSION['role']);

if (!$access['authorized']) {
  http_response_code(403);
  die(json_encode(['error' => 'Access Denied']));
}

// Use location in queries
$barangay_id = $access['user_location']['barangay_id'];
```

## Testing the System

### Demo Page: `/secure-announcements-demo.php`

This page demonstrates:
- Location middleware in action
- Role-based data filtering
- Visual display of access levels
- SQL query filtering by barangay

### Test Scenarios:

1. **As Admin**: See all announcements from all barangays
2. **As Official**: Only see announcements from assigned barangay
3. **As Resident**: Only see announcements from registered barangay

## Migration Path

### For Existing Data:

```sql
-- Update existing users with location IDs
UPDATE users u
JOIN barangays b ON u.barangay = b.name
JOIN municipalities m ON u.cityMunicipality = m.name
SET u.barangay_id = b.id, u.municipality_id = m.id
WHERE u.barangay_id IS NULL;

-- Update existing announcements
UPDATE announcements a
JOIN barangays b ON a.barangay = b.name
SET a.barangay_id = b.id
WHERE a.barangay_id IS NULL;
```

## Best Practices

1. **Always use location middleware** on protected pages
2. **Validate barangay_id** in POST requests
3. **Never trust client-side location data** - validate server-side
4. **Log unauthorized access attempts** for security monitoring
5. **Use prepared statements** with barangay_id parameters
6. **Test with different roles** before deployment

## Security Considerations

### ✅ Implemented:
- Foreign key constraints prevent orphaned records
- Server-side location validation on every request
- Role-based WHERE clauses in SQL queries
- Cascading deletes protect data integrity

### ⚠️ Important:
- Never expose barangay_ids directly in URLs (use session data)
- Validate location on EVERY data-modifying operation
- Admin actions should log barangay accessed for audit trail
- Consider adding rate limiting on API endpoints

## Future Enhancements

1. **Province/Region levels** for larger deployments
2. **Location assignment UI** for admins to assign officials
3. **Audit logging** for cross-barangay access attempts  
4. **Batch operations** with location validation
5. **Export/Import** with location metadata
6. **Mobile app integration** with geofencing

---

## Quick Reference

### File Locations:
- Middleware: `/includes/locationMiddleware.php`
- APIs: `/api/get-municipalities.php`, `/api/get-barangays.php`
- Signup: `/pages/Signup.php`
- Demo: `/secure-announcements-demo.php`

### Database Commands:
```bash
# View structure
mysql -u root commserve -e "DESCRIBE municipalities; DESCRIBE barangays;"

# View seed data
mysql -u root commserve -e "SELECT * FROM municipalities; SELECT * FROM barangays;"

# Check user assignments
mysql -u root commserve -e "SELECT id, username, role, barangay_id FROM users;"
```
