<?php
session_start();
require_once __DIR__ . '/userAccounts/config.php';
require_once __DIR__ . '/vendor/autoload.php';

// User guard - must be logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: /pages/loginPage.php');
  exit;
}

$user_id = $_SESSION['user_id'];
$request_id = (int)($_GET['id'] ?? 0);

if ($request_id <= 0) {
  die('Invalid request ID');
}

// Get request details - must belong to user and be in 'ready' status
$stmt = $conn->prepare("SELECT dr.*, u.barangay, u.cityMunicipality, u.province 
                        FROM document_requests dr 
                        JOIN users u ON dr.user_id = u.id 
                        WHERE dr.id = ? AND dr.user_id = ? AND dr.status = 'ready'");
$stmt->bind_param("ii", $request_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();
$stmt->close();

if (!$request) {
  die('Request not found or not ready for download');
}

// Create PDF
class PDF extends FPDF {
  function Header() {
    // Logo/Header space
    $this->SetFont('Arial', 'B', 16);
    $this->Cell(0, 10, 'REPUBLIC OF THE PHILIPPINES', 0, 1, 'C');
    $this->SetFont('Arial', '', 12);
    $this->Cell(0, 6, 'Province: ' . strtoupper($GLOBALS['request']['province'] ?? 'N/A'), 0, 1, 'C');
    $this->Cell(0, 6, 'Municipality/City: ' . strtoupper($GLOBALS['request']['cityMunicipality'] ?? 'N/A'), 0, 1, 'C');
    $this->SetFont('Arial', 'B', 14);
    $this->Cell(0, 8, 'BARANGAY ' . strtoupper($GLOBALS['request']['barangay'] ?? 'N/A'), 0, 1, 'C');
    $this->Ln(5);
    
    // Divider line
    $this->SetLineWidth(0.5);
    $this->Line(20, $this->GetY(), 190, $this->GetY());
    $this->Ln(10);
  }
  
  function Footer() {
    $this->SetY(-25);
    $this->SetFont('Arial', 'I', 8);
    $this->Cell(0, 5, 'This is a computer-generated document. No signature required.', 0, 1, 'C');
    $this->Cell(0, 5, 'Document ID: DOC-' . str_pad($GLOBALS['request']['id'], 6, '0', STR_PAD_LEFT), 0, 1, 'C');
    $this->Cell(0, 5, 'Generated on: ' . date('F d, Y h:i A'), 0, 0, 'C');
  }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 11);

// Document Title
$pdf->SetFont('Arial', 'B', 18);
$document_title = strtoupper($request['document_type']);
$pdf->Cell(0, 12, $document_title, 0, 1, 'C');
$pdf->Ln(5);

// TO WHOM IT MAY CONCERN
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'TO WHOM IT MAY CONCERN:', 0, 1, 'L');
$pdf->Ln(5);

// Body content
$pdf->SetFont('Arial', '', 11);
$pdf->MultiCell(0, 7, "        This is to certify that " . strtoupper($request['full_name']) . ", " . 
                $request['civil_status'] . ", " . 
                calculateAge($request['date_of_birth']) . " years old, born on " . 
                date('F d, Y', strtotime($request['date_of_birth'])) . 
                ", is a bonafide resident of " . $request['sitio_address'] . ", Barangay " . 
                $request['barangay'] . ", " . $request['cityMunicipality'] . ", " . 
                $request['province'] . ".");

$pdf->Ln(5);
$pdf->MultiCell(0, 7, "        The above-named person has been residing in this barangay for approximately " . 
                $request['years_of_residency'] . " year(s) and is known to be of good moral character.");

$pdf->Ln(5);
$pdf->MultiCell(0, 7, "        This certification is issued upon the request of the above-named person for " . 
                strtoupper($request['purpose']) . ".");

$pdf->Ln(10);
$pdf->Cell(0, 7, "Issued this " . date('jS') . " day of " . date('F Y') . " at Barangay " . 
           $request['barangay'] . ", " . $request['cityMunicipality'] . ", " . $request['province'] . ".", 0, 1, 'L');

// Signature section
$pdf->Ln(15);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 7, '_________________________________', 0, 1, 'R');
$pdf->Cell(0, 5, 'Barangay Captain/Authorized Official', 0, 1, 'R');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 5, 'Barangay ' . $request['barangay'], 0, 1, 'R');

// Output PDF
$pdf->Output('D', sanitizeFilename($request['document_type'] . '_' . $request['full_name'] . '.pdf'));

function calculateAge($birthdate) {
  $birth = new DateTime($birthdate);
  $today = new DateTime();
  return $birth->diff($today)->y;
}

function sanitizeFilename($filename) {
  $filename = preg_replace('/[^a-zA-Z0-9_\- ]/', '', $filename);
  $filename = str_replace(' ', '_', $filename);
  return $filename;
}
?>
