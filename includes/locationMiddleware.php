<?php
/**
 * Location-Based Access Control Middleware
 * Validates user's location ID on every request to prevent unauthorized access
 */

function validateLocationAccess($conn, $user_id, $user_role, $required_barangay_id = null) {
  // Get user's assigned location
  $stmt = $conn->prepare("
    SELECT u.id, u.barangay_id, u.municipality_id, u.role,
           b.name as barangay_name, b.municipality_id as barangay_municipality_id,
           m.name as municipality_name
    FROM users u
    LEFT JOIN barangays b ON u.barangay_id = b.id
    LEFT JOIN municipalities m ON u.municipality_id = m.id
    WHERE u.id = ?
  ");
  $stmt->bind_param('i', $user_id);
  $stmt->execute();
  $user_location = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$user_location) {
    return ['authorized' => false, 'message' => 'User not found'];
  }

  // Admin has access to all locations
  if ($user_role === 'admin') {
    return ['authorized' => true, 'user_location' => $user_location];
  }

  // Official: restricted to their assigned barangay only
  if ($user_role === 'official') {
    if (!$user_location['barangay_id']) {
      return ['authorized' => false, 'message' => 'Official must be assigned to a barangay'];
    }
    
    // If checking specific barangay access
    if ($required_barangay_id && $user_location['barangay_id'] != $required_barangay_id) {
      return ['authorized' => false, 'message' => 'Access denied: You can only manage your assigned barangay'];
    }
    
    return ['authorized' => true, 'user_location' => $user_location];
  }

  // Resident: can only view their own barangay data
  if ($user_role === 'resident') {
    if (!$user_location['barangay_id']) {
      return ['authorized' => false, 'message' => 'Resident must be registered to a barangay'];
    }
    
    // If checking specific barangay access
    if ($required_barangay_id && $user_location['barangay_id'] != $required_barangay_id) {
      return ['authorized' => false, 'message' => 'Access denied: You can only view your registered barangay'];
    }
    
    return ['authorized' => true, 'user_location' => $user_location];
  }

  return ['authorized' => false, 'message' => 'Invalid role'];
}

/**
 * Build SQL WHERE clause for location-based filtering
 */
function getLocationWhereClause($user_role, $user_barangay_id, $table_alias = '') {
  $prefix = $table_alias ? $table_alias . '.' : '';
  
  if ($user_role === 'admin') {
    return '1=1'; // Admin sees all
  }
  
  if ($user_role === 'official' || $user_role === 'resident') {
    return "{$prefix}barangay_id = " . (int)$user_barangay_id;
  }
  
  return '1=0'; // Deny access by default
}

/**
 * Validate barangay access for data queries
 */
function validateBarangayDataAccess($conn, $user_id, $user_role, $data_barangay_id) {
  if ($user_role === 'admin') {
    return true; // Admin can access all barangays
  }
  
  // Get user's barangay
  $stmt = $conn->prepare("SELECT barangay_id FROM users WHERE id = ?");
  $stmt->bind_param('i', $user_id);
  $stmt->execute();
  $result = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  
  if (!$result || !$result['barangay_id']) {
    return false;
  }
  
  // Check if user's barangay matches data's barangay
  return ($result['barangay_id'] == $data_barangay_id);
}
