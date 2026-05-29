<?php
// DailyPatientHistory.php - Complete 24-Hour Patient History (8 AM to 8 AM)
session_start();

// Check if staff is logged in
if (!isset($_SESSION['staff_logged_in'])) {
    header('Location: StaffLogin.php');
    exit;
}

// Include database connection
include 'dp.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5, user-scalable=yes">
    <title>24-Hour Patient History - Dr. LSK Clinic</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-purple: #7232a0;
            --secondary-purple: #5a2880;
            --light-purple: #e9d5ff;
            --dark-purple: #4a1e6b;
            --light-gray: #f8f9fa;
            --medium-gray: #e9ecef;
            --success-green: #28a745;
            --info-blue: #17a2b8;
            --warning-orange: #ffc107;
            --danger-red: #dc3545;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fb;
            color: #333;
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        .top-navbar {
            background: linear-gradient(90deg, #ffffff 0%, #faf5ff 100%);
            border-bottom: 1px solid rgba(114, 50, 160, 0.15);
            padding: 12px 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .nav-left {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1 1 auto;
            min-width: 250px;
        }
        
        .clinic-logo {
            height: 55px;
            width: auto;
            max-width: 55px;
            object-fit: contain;
            border-radius: 12px;
            transition: transform 0.3s ease;
            box-shadow: 0 4px 10px rgba(114, 50, 160, 0.2);
        }
        
        .clinic-logo:hover {
            transform: scale(1.05);
        }
        
        .hospital-name {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }
        
        .hospital-name-main {
            font-weight: 800;
            font-size: 1.4rem;
            color: #4a1e6b;
            letter-spacing: -0.5px;
        }
        
        .hospital-name-main span {
            color: #7232a0;
        }
        
        .hospital-name-sub {
            font-size: 0.75rem;
            color: #7e5a9a;
            font-weight: 500;
        }
        
        .nav-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        
        .staff-info-card {
            display: flex;
            align-items: center;
            background: linear-gradient(145deg, #faf5ff, #f3e8ff);
            padding: 6px 16px;
            border-radius: 40px;
            border: 1px solid #d9b3ff;
            gap: 10px;
        }
        
        .staff-avatar {
            width: 40px;
            height: 40px;
            background: #7232a0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .staff-avatar i {
            font-size: 1.5rem;
            color: white;
        }
        
        .staff-details {
            line-height: 1.2;
        }
        
        .staff-details h6 {
            margin: 0;
            font-weight: 700;
            color: #4a1e6b;
            font-size: 0.95rem;
        }
        
        .staff-details small {
            color: #5a6b5a;
            font-size: 0.7rem;
        }
        
        .logout-link {
            color: #d32f2f;
            transition: all 0.2s;
            padding: 8px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logout-link:hover {
            background: rgba(211, 47, 47, 0.1);
            transform: translateY(-2px);
        }
        
        .main-container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .patient-select-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--primary-purple);
        }
        
        .selected-patient-row {
            background-color: #e9d5ff !important;
            transition: background-color 0.2s;
        }
        
        .whatsapp-share-section {
            background: linear-gradient(145deg, #25d36610, #075e5420);
            border: 1px solid #25d366;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 25px;
            display: none;
        }
        
        .whatsapp-share-section h5 {
            color: #075e54;
        }
        
        .selected-count-badge {
            background: var(--primary-purple);
            color: white;
            border-radius: 30px;
            padding: 5px 15px;
            font-size: 0.9rem;
        }
        
        .whatsapp-btn {
            background: #25D366;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .whatsapp-btn:hover {
            background: #128C7E;
            transform: translateY(-2px);
            color: white;
        }
        
        .whatsapp-btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .bulk-whatsapp-progress {
            background: #fff3cd;
            border: 1px solid #ffc107;
        }
        
        .progress-bar-whatsapp {
            transition: width 0.3s ease;
        }
        
        .select-all-btn, .deselect-all-btn {
            background: var(--light-purple);
            border: none;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            transition: all 0.2s;
        }
        
        .select-all-btn:hover, .deselect-all-btn:hover {
            background: var(--primary-purple);
            color: white;
        }
        
        .date-selector-card {
            background: white;
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 25px;
            box-shadow: 0 8px 22px rgba(114, 50, 160, 0.08);
            border: 1px solid rgba(114, 50, 160, 0.1);
        }
        
        .date-selector-title {
            color: var(--dark-purple);
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 15px;
        }
        
        .date-selector-title i {
            color: var(--primary-purple);
            margin-right: 10px;
        }
        
        .period-badge {
            background: linear-gradient(145deg, var(--primary-purple), var(--dark-purple));
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
            border-top: 4px solid var(--primary-purple);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: var(--light-purple);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }
        
        .stat-icon i {
            font-size: 1.8rem;
            color: var(--primary-purple);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark-purple);
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 10px 24px;
            border-radius: 30px;
            background: white;
            border: 1px solid #dee2e6;
            color: #666;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .filter-tab:hover {
            border-color: var(--primary-purple);
            color: var(--primary-purple);
        }
        
        .filter-tab.active {
            background: var(--primary-purple);
            border-color: var(--primary-purple);
            color: white;
        }
        
        .visits-table-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 22px rgba(114, 50, 160, 0.08);
            border: 1px solid rgba(114, 50, 160, 0.1);
            overflow-x: auto;
        }
        
        .visits-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .visits-table th {
            background: var(--light-purple);
            color: var(--dark-purple);
            font-weight: 700;
            padding: 14px 12px;
            text-align: left;
            border-bottom: 2px solid #d9b3ff;
            font-size: 0.85rem;
            white-space: nowrap;
        }
        
        .visits-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: middle;
            font-size: 0.9rem;
        }
        
        .visits-table tr:hover {
            background: #faf5ff;
        }
        
        .badge-regular {
            background: var(--info-blue);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-homecare {
            background: var(--primary-purple);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .complaint-cell {
            max-width: 300px;
            word-wrap: break-word;
        }
        
        .btn-view {
            background: var(--light-purple);
            color: var(--dark-purple);
            border: none;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            transition: all 0.2s;
            margin: 2px;
        }
        
        .btn-view:hover {
            background: var(--primary-purple);
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-lab {
            background: #e8f5e9;
            color: #2e7d32;
            border: none;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            transition: all 0.2s;
            margin: 2px;
        }
        
        .btn-lab:hover {
            background: #c8e6c9;
            transform: translateY(-2px);
        }
        
        .btn-view-lab {
            background: linear-gradient(145deg, #9c27b0, #7b1fa2);
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .btn-view-lab:hover {
            background: linear-gradient(145deg, #7b1fa2, #4a148c);
            transform: translateY(-2px);
        }
        
        .no-data {
            text-align: center;
            padding: 50px;
            color: #999;
        }
        
        .no-data i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--light-purple);
        }
        
        .modal-content {
            border-radius: 20px;
            border: none;
        }
        
        .modal-header {
            background: linear-gradient(145deg, var(--primary-purple), var(--dark-purple));
            color: white;
            border-radius: 20px 20px 0 0;
        }
        
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        
        .vital-detail-item {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 10px;
            border-left: 4px solid var(--primary-purple);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .vital-detail-label {
            color: #5a6b5a;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .vital-detail-value {
            color: var(--dark-purple);
            font-size: 1.1rem;
            font-weight: 700;
        }
        
        .patient-info-card {
            background: linear-gradient(145deg, #faf5ff, #f3e8ff);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .patient-info-row {
            display: flex;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .patient-info-label {
            width: 120px;
            font-weight: 600;
            color: var(--dark-purple);
        }
        
        .patient-info-value {
            flex: 1;
            color: #555;
        }
        
        .section-divider {
            background: var(--light-purple);
            padding: 8px 12px;
            border-radius: 8px;
            margin: 15px 0 10px 0;
            font-weight: 600;
            color: var(--dark-purple);
        }
        
        .lab-report-header {
            background: linear-gradient(145deg, #7232a0, #4a1e6b) !important;
        }
        
        .lab-parameter-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .lab-parameter-table th {
            background: #f3e5f5;
            color: #4a1e6b;
            font-weight: 600;
            padding: 12px;
            font-size: 0.9rem;
        }
        
        .lab-parameter-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .lab-parameter-table .normal-value {
            color: #2e7d32;
            font-weight: 600;
        }
        
        .lab-parameter-table .critical-value {
            color: #d32f2f;
            font-weight: 700;
        }
        
        .lab-parameter-table .abnormal-value {
            color: #ff9800;
            font-weight: 600;
        }
        
        .lab-report-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fff3e0;
            color: #e65100;
        }
        
        .status-completed {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-verified {
            background: #e8eaf6;
            color: #3949ab;
        }
        
        .status-forwarded {
            background: #e1f5fe;
            color: #0288d1;
        }
        
        .message-preview {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            font-size: 0.9rem;
            max-height: 150px;
            overflow-y: auto;
            border-left: 3px solid #25D366;
        }
        
        .toast-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        .toast-hide {
            animation: slideOut 0.3s ease forwards;
        }
        
        .instruction-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
        }
        
        .instruction-box ol {
            margin-bottom: 0;
            padding-left: 20px;
        }
        
        .instruction-box li {
            margin: 5px 0;
        }
        
        .auto-send-progress {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-top: 15px;
            border: 2px solid var(--primary-purple);
        }

        .send-stats {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 15px;
        }

        .stat-badge {
            text-align: center;
            padding: 10px;
            border-radius: 10px;
            background: #f8f9fa;
        }

        .failed-list {
            max-height: 150px;
            overflow-y: auto;
            font-size: 0.85rem;
        }
        
        /* WhatsApp Web specific styles */
        .whatsapp-web-instruction {
            background: #e8f5e9;
            border-left: 4px solid #25D366;
        }
        
        .chrome-status {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
        }
        
        @media (max-width: 992px) {
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .stats-row {
                grid-template-columns: 1fr;
            }
            .date-selector-card {
                padding: 20px;
            }
            .period-badge {
                font-size: 0.75rem;
                padding: 6px 12px;
            }
            .filter-tabs {
                justify-content: center;
            }
            .filter-tab {
                padding: 6px 16px;
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 576px) {
            .main-container {
                padding: 0 10px;
            }
            .visits-table th, .visits-table td {
                padding: 10px 8px;
                font-size: 0.8rem;
            }
            .patient-info-label {
                width: 90px;
                font-size: 0.8rem;
            }
        }

        .complaint-search-card {
            transition: all 0.3s ease;
        }
        
        .complaint-search-card input:focus {
            box-shadow: 0 0 0 0.25rem rgba(114, 50, 160, 0.25);
        }
    </style>
</head>
<body>
    <div class="top-navbar">
        <div class="nav-left">
            <a href="DoctorDashboard.php" class="logo-img-link">
                <img src="assets/images/logo1.jpeg" 
                     alt="Dr. LSK Clinic" 
                     class="clinic-logo"
                     onerror="this.onerror=null; this.src='https://via.placeholder.com/55x55/7232a0/ffffff?text=LSK';">
            </a>
            <div class="hospital-name">
                <div class="hospital-name-main">Dr. LSK <span>Clinic</span></div>
                <div class="hospital-name-sub">Excellence in Healthcare</div>
            </div>
        </div>
        <div class="nav-right">
            <div class="staff-info-card">
                <div class="staff-avatar">
                    <i class="fa fa-user-md"></i>
                </div>
                <div class="staff-details">
                    <h6><?php echo $_SESSION['staff_name'] ?? 'Staff'; ?></h6>
                    <small><?php echo $_SESSION['staff_role'] ?? 'Staff'; ?></small>
                </div>
                <a href="logout.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="main-container">
        
    <div class="date-selector-card">
        <div class="row align-items-center">
            <div class="col-md-6">
                <a href="javascript:history.back()" class="btn btn-sm mb-2" style="background: #7232a0; color: white; border-radius: 20px; padding: 5px 15px; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; margin-bottom: 10px !important;">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <div class="date-selector-title">
                    <i class="fas fa-calendar-alt"></i> Select Date (8 AM to 8 AM Period)
                </div>
                <div class="d-flex gap-3 flex-wrap">
                    <input type="date" id="selectedDate" class="form-control w-auto" style="border-color: var(--primary-purple);">
                    <button class="btn" style="background: var(--primary-purple); color: white;" onclick="loadHistory()">
                        <i class="fas fa-search me-2"></i>Load History
                    </button>
                    <button class="btn btn-outline-secondary" onclick="setToday()">
                        <i class="fas fa-calendar-day me-2"></i>Today
                    </button>
                    <button class="btn btn-outline-secondary" onclick="setYesterday()">
                        <i class="fas fa-calendar-week me-2"></i>Yesterday
                    </button>
                </div>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <div class="period-badge" id="periodDisplay">
                    <i class="fas fa-clock me-2"></i>Loading...
                </div>
            </div>
        </div>
    </div>

    <!-- WhatsApp Share Section -->
    <div class="whatsapp-share-section" id="whatsappShareSection">
        <div class="row align-items-center">
            <div class="col-md-4">
                <h5><i class="fab fa-whatsapp me-2"></i>Share via WhatsApp Web</h5>
                <p class="mb-0 small">Send campaign message to selected patients using WhatsApp Web</p>
            </div>
            <div class="col-md-8">
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <div>
                        <span class="selected-count-badge" id="selectedCount">0</span>
                        <span class="ms-2">patient(s) selected</span>
                    </div>
                    <button class="select-all-btn" onclick="selectAllPatients()">
                        <i class="fas fa-check-double me-1"></i>Select All
                    </button>
                    <button class="deselect-all-btn" onclick="deselectAllPatients()">
                        <i class="fas fa-times me-1"></i>Deselect All
                    </button>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-6">
                <label class="fw-bold mb-2"><i class="fas fa-calendar-alt me-2"></i>Campaign Date <span class="text-danger">*</span></label>
                <input type="date" id="campaignDate" class="form-control" style="border-color: #25D366;" required>
            </div>
            <div class="col-md-6">
                <label class="fw-bold mb-2"><i class="fas fa-tag me-2"></i>Campaign Topic <span class="text-danger">*</span></label>
                <input type="text" id="campaignTopic" class="form-control" placeholder="e.g., Free Diabetes Screening Camp, Health Awareness Camp" style="border-color: #25D366;" required>
            </div>
        </div>

        <!-- ADD THIS NEW ROW FOR TIME SELECTION -->
<div class="row mt-3">
    <div class="col-md-6">
        <label class="fw-bold mb-2"><i class="fas fa-clock me-2"></i>Start Time <span class="text-danger">*</span></label>
        <input type="time" id="campaignStartTime" class="form-control" style="border-color: #25D366;" value="09:00" required>
    </div>
    <div class="col-md-6">
        <label class="fw-bold mb-2"><i class="fas fa-clock me-2"></i>End Time <span class="text-danger">*</span></label>
        <input type="time" id="campaignEndTime" class="form-control" style="border-color: #25D366;" value="13:00" required>
    </div>
</div>
        
        <div class="row mt-3">
            <div class="col-12">
                <label class="fw-bold mb-2"><i class="fas fa-edit me-2"></i>Custom Message</label>
                <textarea id="customMessage" class="form-control" rows="3" placeholder="Write your custom message here... (Optional - will be added before the standard message)" style="border-color: #25D366;"></textarea>
                <small class="text-muted mt-1">Optional: Add a personal message before the campaign details</small>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-12">
                <div class="message-preview">
                    <i class="fab fa-whatsapp me-1" style="color: #25D366;"></i>
                    <strong>Message Preview:</strong>
                    <div id="messagePreview" class="mt-2 small"></div>
                </div>
            </div>
        </div>
        
        <!-- WhatsApp Web Auto Send Section (Puppeteer) -->
        <div id="autoSendSection" style="display: none;" class="mt-3">
            <div class="auto-send-progress">
                <h6><i class="fab fa-whatsapp me-2" style="color: #25D366;"></i>WhatsApp Web Auto Sending Progress</h6>
                <div class="progress mb-3" style="height: 30px;">
                    <div id="autoSendProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%; background-color: #25D366;">0%</div>
                </div>
                
                <div class="send-stats">
                    <div class="stat-badge">
                        <i class="fas fa-check-circle text-success"></i>
                        <strong id="successCount">0</strong>
                        <small>Sent</small>
                    </div>
                    <div class="stat-badge">
                        <i class="fas fa-times-circle text-danger"></i>
                        <strong id="failedCount">0</strong>
                        <small>Failed</small>
                    </div>
                    <div class="stat-badge">
                        <i class="fas fa-clock text-warning"></i>
                        <strong id="pendingCount">0</strong>
                        <small>Pending</small>
                    </div>
                </div>
                
                <div id="failedListContainer" style="display: none;" class="mt-3">
                    <hr>
                    <strong><i class="fas fa-exclamation-triangle text-danger me-2"></i>Failed Messages:</strong>
                    <div id="failedList" class="failed-list mt-2"></div>
                </div>
            </div>
            
            <div class="instruction-box whatsapp-web-instruction mt-3">
                <h6><i class="fab fa-whatsapp me-2" style="color: #25D366;"></i>WhatsApp Web Auto-Send (Using WhatsApp Web)</h6>
                <ol>
                    <li>Messages will be sent automatically to all selected patients via WhatsApp Web</li>
                    <li>A Chrome browser window will open - <strong>DO NOT CLOSE IT</strong></li>
                    <li>First time only: Scan the QR code with your phone's WhatsApp</li>
                    <li>Each message is personalized with the patient's name</li>
                    <li>Don't close this page until sending completes</li>
                    <li>Failed messages can be retried later</li>
                </ol>
            </div>
            
            <div class="instruction-box chrome-status mt-3">
                <h6><i class="fab fa-chrome me-2" style="color: #ff9800;"></i>Important Notes:</h6>
                <ol>
                    <li>Make sure <strong>Google Chrome</strong> is installed on this computer</li>
                    <li>Keep your phone nearby (WhatsApp must be open on your phone)</li>
                    <li>First time only: Scan QR code shown in Chrome window</li>
                    <li>Subsequent sends will happen automatically</li>
                </ol>
            </div>
            
            <div class="row mt-3">
                <div class="col-12 text-center">
                    <button class="whatsapp-btn" id="startAutoSendBtn" onclick="startAutoSend()">
                        <i class="fab fa-whatsapp me-2"></i>Send to All (Auto)
                    </button>
                    <button class="btn btn-outline-secondary ms-2" onclick="closeWhatsAppSection()">
                        <i class="fas fa-times me-1"></i>Close
                    </button>
                </div>
            </div>
        </div>
        
        <div class="row mt-3" id="startButtonsRow">
            <div class="col-12 text-center">
                <button class="whatsapp-btn" onclick="showAutoSendSection()">
                    <i class="fab fa-whatsapp me-2"></i>Proceed to Send
                </button>
                <button class="btn btn-outline-secondary ms-2" onclick="closeWhatsAppSection()">
                    <i class="fas fa-times me-1"></i>Close
                </button>
            </div>
        </div>
    </div>

    <!-- Add this after the date selector div -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="complaint-search-card" style="background: linear-gradient(145deg, #faf5ff, #f3e8ff); border-radius: 16px; padding: 20px; margin-top: 15px;">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="flex-grow-1">
                        <input type="text" id="complaintSearchInput" class="form-control" 
                               placeholder="🔍 Search patients by complaint/disease (e.g., Fever, Diabetes, Chest Pain, Cough...)"
                               style="border-color: var(--primary-purple);"
                               autocomplete="off">
                    </div>
                    <button class="btn" style="background: var(--primary-purple); color: white; white-space: nowrap;" onclick="searchPatientsByComplaint()">
                        <i class="fas fa-stethoscope me-2"></i>Search by Complaint
                    </button>
                    <button class="btn btn-outline-secondary" onclick="clearComplaintSearch()" style="white-space: nowrap;">
                        <i class="fas fa-undo-alt me-2"></i>Clear
                    </button>
                    <button class="btn btn-success" onclick="openWhatsAppShare()" style="white-space: nowrap;" id="whatsappShareBtn" disabled>
                        <i class="fab fa-whatsapp me-2"></i>Share Selected via WhatsApp
                    </button>
                </div>
                <div class="mt-2">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Search patients who ever had specific complaints/diseases (e.g., "fever" will find all patients with fever complaints)
                    </small>
                </div>
            </div>
        </div>
    </div>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-value" id="totalCount">0</div>
                <div class="stat-label">Total Patients</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-user-friends"></i></div>
                <div class="stat-value" id="regularCount">0</div>
                <div class="stat-label">Regular Patients</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-home"></i></div>
                <div class="stat-value" id="homecareCount">0</div>
                <div class="stat-label">Home Care Patients</div>
            </div>
        </div>

        <div class="filter-tabs">
            <div class="filter-tab active" data-filter="all" onclick="filterVisits('all')">
                <i class="fas fa-list me-1"></i> All Visits
            </div>
            <div class="filter-tab" data-filter="regular" onclick="filterVisits('regular')">
                <i class="fas fa-user-friends me-1"></i> Regular Only
            </div>
            <div class="filter-tab" data-filter="home_care" onclick="filterVisits('home_care')">
                <i class="fas fa-home me-1"></i> Home Care Only
            </div>
        </div>

        <div class="visits-table-card">
            <h5 class="mb-3" style="color: var(--dark-purple);">
                <i class="fas fa-history me-2"></i>Patient Visits (8 AM - 8 AM)
                <span id="selectionModeIndicator" class="ms-2 small text-muted"></span>
            </h5>
            <div style="overflow-x: auto;">
                <table class="visits-table" id="visitsTable">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAllCheckbox" style="display: none;" onchange="toggleSelectAll()"></th>
                            <th>Date & Time</th>
                            <th>Patient ID</th>
                            <th>Patient Name</th>
                            <th>Age/Gender</th>
                            <th>Type</th>
                            <th>Complaints</th>
                            <th>Actions</th>
                            <th>Lab Reports</th>
                        </thead>
                    <tbody id="visitsTableBody">
                        <tr><td colspan="9" class="no-data"><i class="fas fa-clock"></i><br>Select a date to view history</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Vitals Modal -->
    <div class="modal fade" id="vitalsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-heartbeat me-2"></i>Vital Signs</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="vital-detail-item">
                        <span class="vital-detail-label"><i class="fas fa-gauge-high me-2"></i>Blood Pressure</span>
                        <span class="vital-detail-value" id="modalBP">--/--</span>
                    </div>
                    <div class="vital-detail-item">
                        <span class="vital-detail-label"><i class="fas fa-temperature-high me-2"></i>Temperature</span>
                        <span class="vital-detail-value" id="modalTemp">--°F</span>
                    </div>
                    <div class="vital-detail-item">
                        <span class="vital-detail-label"><i class="fas fa-heart me-2"></i>Heart Rate</span>
                        <span class="vital-detail-value" id="modalHR">-- bpm</span>
                    </div>
                    <div class="vital-detail-item">
                        <span class="vital-detail-label"><i class="fas fa-lungs me-2"></i>SpO₂ Level</span>
                        <span class="vital-detail-value" id="modalO2">--%</span>
                    </div>
                    <div class="vital-detail-item">
                        <span class="vital-detail-label"><i class="fas fa-weight-scale me-2"></i>Weight / Height</span>
                        <span class="vital-detail-value" id="modalWH">-- kg / -- cm</span>
                    </div>
                    <div class="vital-detail-item">
                        <span class="vital-detail-label"><i class="fas fa-droplet me-2"></i>Glucose</span>
                        <span class="vital-detail-value" id="modalGlucose">-- mg/dL</span>
                    </div>
                    <div class="vital-detail-item">
                        <span class="vital-detail-label"><i class="fas fa-lungs me-2"></i>Respiratory Rate</span>
                        <span class="vital-detail-value" id="modalRR">-- rpm</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Patient Details Modal -->
    <div class="modal fade" id="patientDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user me-2"></i>Patient Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="patientDetailsBody">
                    <div class="text-center py-4">
                        <div class="spinner-border" style="color: var(--primary-purple);"></div>
                        <p class="mt-2">Loading patient details...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lab Report Modal -->
    <div class="modal fade" id="labReportModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header lab-report-header">
                    <h5 class="modal-title"><i class="fas fa-flask me-2"></i>Lab Report Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="labReportBody">
                    <div class="text-center py-4">
                        <div class="spinner-border" style="color: var(--primary-purple);"></div>
                        <p class="mt-2">Loading lab report...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let allVisits = [];
        let currentFilter = 'all';
        let labReportsCache = {};
        let selectedPatients = new Set();
        let currentSearchResults = [];
        
        // Auto send variables
        let isAutoSending = false;

        document.getElementById('selectedDate').value = new Date().toISOString().split('T')[0];
        document.getElementById('campaignDate').value = new Date().toISOString().split('T')[0];
        
        document.getElementById('campaignTopic').addEventListener('input', updateMessagePreview);
        document.getElementById('customMessage').addEventListener('input', updateMessagePreview);
        document.getElementById('campaignDate').addEventListener('change', updateMessagePreview);
        
        document.addEventListener('DOMContentLoaded', function() {
            loadHistory();

             // Add time input event listeners
    document.getElementById('campaignStartTime').addEventListener('change', updateMessagePreview);
    document.getElementById('campaignEndTime').addEventListener('change', updateMessagePreview);
        });

        function setToday() {
            document.getElementById('selectedDate').value = new Date().toISOString().split('T')[0];
            loadHistory();
        }

        function setYesterday() {
            const yesterday = new Date();
            yesterday.setDate(yesterday.getDate() - 1);
            document.getElementById('selectedDate').value = yesterday.toISOString().split('T')[0];
            loadHistory();
        }

        async function loadHistory() {
            const date = document.getElementById('selectedDate').value;
            if (!date) {
                showError('Please select a date');
                return;
            }

            showLoading();
            
            try {
                const response = await fetch(`patient_backend.php?action=get_24hr_history&date=${date}`);
                const data = await response.json();
                
                if (data.success) {
                    allVisits = data.visits || [];
                    
                    document.getElementById('periodDisplay').innerHTML = `
                        <i class="fas fa-clock me-2"></i>
                        ${data.period_start || '--'} to ${data.period_end || '--'}
                    `;
                    
                    document.getElementById('totalCount').textContent = data.total_count || 0;
                    document.getElementById('regularCount').textContent = data.regular_count || 0;
                    document.getElementById('homecareCount').textContent = data.homecare_count || 0;
                    
                    await fetchLabReportsForVisits();
                    renderTable();
                } else {
                    showError(data.message || 'Failed to load history');
                    allVisits = [];
                    renderTable();
                }
            } catch (error) {
                console.error('Error loading history:', error);
                showError('Error loading history. Please try again.');
                allVisits = [];
                renderTable();
            }
        }

        async function fetchLabReportsForVisits() {
            if (!allVisits || allVisits.length === 0) return;
            
            for (let i = 0; i < allVisits.length; i++) {
                const visit = allVisits[i];
                const patientMasterId = visit.patient_master_id || visit.id;
                
                if (patientMasterId) {
                    try {
                        if (labReportsCache[patientMasterId]) {
                            const reports = labReportsCache[patientMasterId];
                            const visitDate = new Date(visit.visit_date);
                            const nextDay = new Date(visitDate);
                            nextDay.setDate(nextDay.getDate() + 1);
                            
                            const matchingReports = reports.filter(report => {
                                const reportDate = new Date(report.requested_at || report.created_at);
                                return reportDate >= visitDate && reportDate < nextDay;
                            });
                            
                            allVisits[i].lab_reports = matchingReports;
                        } else {
                            const response = await fetch(`Lab_backend.php?action=get_patient_lab_reports&patient_id=${patientMasterId}`);
                            const data = await response.json();
                            
                            if (data.success && data.reports) {
                                labReportsCache[patientMasterId] = data.reports;
                                
                                const visitDate = new Date(visit.visit_date);
                                const nextDay = new Date(visitDate);
                                nextDay.setDate(nextDay.getDate() + 1);
                                
                                const matchingReports = data.reports.filter(report => {
                                    const reportDate = new Date(report.requested_at || report.created_at);
                                    return reportDate >= visitDate && reportDate < nextDay;
                                });
                                
                                allVisits[i].lab_reports = matchingReports;
                            } else {
                                allVisits[i].lab_reports = [];
                            }
                        }
                    } catch (error) {
                        console.error(`Error fetching lab reports:`, error);
                        allVisits[i].lab_reports = [];
                    }
                } else {
                    allVisits[i].lab_reports = [];
                }
            }
        }

        function filterVisits(filter) {
            currentFilter = filter;
            
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
                if (tab.getAttribute('data-filter') === filter) {
                    tab.classList.add('active');
                }
            });
            
            renderTable();
        }

        function renderTable() {
            const tbody = document.getElementById('visitsTableBody');
            
            let filteredVisits = [...allVisits];
            if (currentFilter !== 'all') {
                filteredVisits = filteredVisits.filter(v => v.patient_type === currentFilter);
            }
            
            if (filteredVisits.length === 0) {
                tbody.innerHTML = `<tr><td colspan="9" class="no-data"><i class="fas fa-calendar-times"></i><br>No visits found for this period</td></tr>`;
                return;
            }
            
            let html = '';
            filteredVisits.forEach(visit => {
                let dateTimeStr = '--';
                if (visit.visit_date) {
                    const d = new Date(visit.visit_date);
                    dateTimeStr = d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                }
                
                const patientType = visit.patient_type === 'home_care' ? 'home_care' : 'regular';
                const typeBadge = patientType === 'home_care' 
                    ? '<span class="badge-homecare"><i class="fas fa-home me-1"></i>Home Care</span>'
                    : '<span class="badge-regular"><i class="fas fa-user me-1"></i>Regular</span>';
                
                const complaint = visit.complaints || '—';
                const truncatedComplaint = complaint.length > 50 ? complaint.substring(0, 47) + '...' : complaint;
                const demographics = `${visit.age || '?'} yrs, ${visit.gender || '?'}`;
                const patientId = visit.display_id || visit.patient_id || '—';
                const patientKey = visit.patient_master_id || visit.id || patientId;
                
                const isSelected = selectedPatients.has(patientKey);
                const rowClass = isSelected ? 'selected-patient-row' : '';
                
                let labButton = '<span class="text-muted">—</span>';
                if (visit.lab_reports && visit.lab_reports.length > 0) {
                    const sortedReports = visit.lab_reports.sort((a, b) => {
                        const dateA = new Date(a.created_at || a.requested_at || 0);
                        const dateB = new Date(b.created_at || b.requested_at || 0);
                        return dateB - dateA;
                    });
                    
                    const report = sortedReports[0];
                    const reportCount = visit.lab_reports.length;
                    
                    if (reportCount === 1) {
                        labButton = `<button class="btn-view-lab" onclick='showLabReportModal(${report.id})'><i class="fas fa-flask me-1"></i>View Report</button>`;
                    } else {
                        labButton = `
                            <div class="d-flex align-items-center gap-1">
                                <button class="btn-view-lab" onclick='showLabReportModal(${report.id})'>
                                    <i class="fas fa-flask me-1"></i>View Report
                                </button>
                                <span class="badge" style="background: #7232a0; font-size: 0.7rem;">+${reportCount - 1}</span>
                            </div>
                        `;
                    }
                }
                
                const patientData = {
                    master_id: patientKey,
                    name: visit.patient_name || '—',
                    phone: visit.phone || '',
                    id: patientId
                };
                
                html += `
                    <tr class="${rowClass}" data-patient-id="${escapeHtml(patientKey)}" data-patient-phone="${escapeHtml(visit.phone || '')}" data-patient-name="${escapeHtml(visit.patient_name || '—')}">
                        <td style="text-align: center;">
                            <input type="checkbox" class="patient-select-checkbox" data-patient-key="${escapeHtml(patientKey)}" ${isSelected ? 'checked' : ''} onchange="togglePatientSelection('${escapeHtml(patientKey)}', this.checked, ${JSON.stringify(patientData).replace(/'/g, "\\'")})">
                        </td>
                        <td><i class="fas fa-clock text-muted me-1"></i>${escapeHtml(dateTimeStr)}</td>
                        <td><span class="fw-bold" style="color: var(--primary-purple);">${escapeHtml(patientId)}</span></td>
                        <td><strong>${escapeHtml(visit.patient_name || '—')}</strong></td>
                        <td>${escapeHtml(demographics)}</td>
                        <td>${typeBadge}</td>
                        <td class="complaint-cell" title="${escapeHtml(complaint)}">${escapeHtml(truncatedComplaint)}</td>
                        <td>
                            <button class="btn-view" onclick='showPatientDetails(${JSON.stringify(visit).replace(/'/g, "\\'")})'>
                                <i class="fas fa-user me-1"></i>View
                            </button>
                            <button class="btn-view" onclick='showVitalsModal(${JSON.stringify(visit).replace(/'/g, "\\'")})'>
                                <i class="fas fa-heartbeat me-1"></i>Vitals
                            </button>
                        </td>
                        <td>${labButton}</td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = html;
            updateSelectionUI();
        }

        function togglePatientSelection(patientKey, isChecked, patientData) {
            if (isChecked) {
                if (!patientData.phone || patientData.phone === '') {
                    if (confirm(`Patient ${patientData.name} has no phone number. Do you want to select them anyway?`)) {
                        selectedPatients.add(patientKey);
                    } else {
                        const checkbox = document.querySelector(`.patient-select-checkbox[data-patient-key="${patientKey}"]`);
                        if (checkbox) checkbox.checked = false;
                        return;
                    }
                } else {
                    selectedPatients.add(patientKey);
                }
            } else {
                selectedPatients.delete(patientKey);
            }
            
            const row = document.querySelector(`tr[data-patient-id="${patientKey}"]`);
            if (row) {
                if (isChecked) {
                    row.classList.add('selected-patient-row');
                } else {
                    row.classList.remove('selected-patient-row');
                }
            }
            
            updateSelectionUI();
        }

        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const isChecked = selectAllCheckbox.checked;
            const checkboxes = document.querySelectorAll('.patient-select-checkbox');
            
            checkboxes.forEach(checkbox => {
                const patientKey = checkbox.getAttribute('data-patient-key');
                const row = document.querySelector(`tr[data-patient-id="${patientKey}"]`);
                const patientPhone = row ? row.getAttribute('data-patient-phone') : '';
                const patientName = row ? row.getAttribute('data-patient-name') : '';
                
                if (isChecked) {
                    if (patientPhone && patientPhone !== '') {
                        checkbox.checked = true;
                        selectedPatients.add(patientKey);
                        if (row) row.classList.add('selected-patient-row');
                    } else if (!patientPhone || patientPhone === '') {
                        if (confirm(`Patient ${patientName} has no phone number. Do you want to select them anyway?`)) {
                            checkbox.checked = true;
                            selectedPatients.add(patientKey);
                            if (row) row.classList.add('selected-patient-row');
                        }
                    }
                } else {
                    checkbox.checked = false;
                    selectedPatients.delete(patientKey);
                    if (row) row.classList.remove('selected-patient-row');
                }
            });
            
            updateSelectionUI();
        }

        function selectAllPatients() {
            const checkboxes = document.querySelectorAll('.patient-select-checkbox');
            let patientsWithoutPhone = [];
            
            checkboxes.forEach(checkbox => {
                const patientKey = checkbox.getAttribute('data-patient-key');
                const row = document.querySelector(`tr[data-patient-id="${patientKey}"]`);
                const patientPhone = row ? row.getAttribute('data-patient-phone') : '';
                const patientName = row ? row.getAttribute('data-patient-name') : '';
                
                if (patientPhone && patientPhone !== '') {
                    checkbox.checked = true;
                    selectedPatients.add(patientKey);
                    if (row) row.classList.add('selected-patient-row');
                } else {
                    patientsWithoutPhone.push(patientName);
                }
            });
            
            if (patientsWithoutPhone.length > 0) {
                if (!confirm(`${patientsWithoutPhone.length} patient(s) have no phone number. Skip them and select only patients with phone numbers?`)) {
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = false;
                        const patientKey = checkbox.getAttribute('data-patient-key');
                        selectedPatients.delete(patientKey);
                        const row = document.querySelector(`tr[data-patient-id="${patientKey}"]`);
                        if (row) row.classList.remove('selected-patient-row');
                    });
                }
            }
            
            updateSelectionUI();
        }

        function deselectAllPatients() {
            const checkboxes = document.querySelectorAll('.patient-select-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
                const patientKey = checkbox.getAttribute('data-patient-key');
                selectedPatients.delete(patientKey);
                const row = document.querySelector(`tr[data-patient-id="${patientKey}"]`);
                if (row) row.classList.remove('selected-patient-row');
            });
            updateSelectionUI();
        }

        function updateSelectionUI() {
            const selectedCount = selectedPatients.size;
            document.getElementById('selectedCount').textContent = selectedCount;
            const whatsappBtn = document.getElementById('whatsappShareBtn');
            
            if (selectedCount > 0) {
                whatsappBtn.disabled = false;
                document.getElementById('selectionModeIndicator').innerHTML = `<span class="badge bg-success">${selectedCount} patient(s) selected for WhatsApp</span>`;
            } else {
                whatsappBtn.disabled = true;
                document.getElementById('selectionModeIndicator').innerHTML = '';
            }
            
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const checkboxes = document.querySelectorAll('.patient-select-checkbox');
            const totalCheckboxes = checkboxes.length;
            const selectedCheckboxes = Array.from(checkboxes).filter(cb => cb.checked).length;
            
            if (totalCheckboxes > 0) {
                if (selectedCheckboxes === totalCheckboxes) {
                    selectAllCheckbox.checked = true;
                    selectAllCheckbox.indeterminate = false;
                } else if (selectedCheckboxes > 0) {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = true;
                } else {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = false;
                }
            }
        }

        function openWhatsAppShare() {
            if (selectedPatients.size === 0) {
                alert('Please select at least one patient to share via WhatsApp');
                return;
            }
            
            const whatsappSection = document.getElementById('whatsappShareSection');
            whatsappSection.style.display = 'block';
            whatsappSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            updateMessagePreview();
        }

        function closeWhatsAppSection() {
            document.getElementById('whatsappShareSection').style.display = 'none';
            document.getElementById('autoSendSection').style.display = 'none';
            document.getElementById('startButtonsRow').style.display = 'block';
            if (isAutoSending) {
                isAutoSending = false;
            }
        }

        function showAutoSendSection() {
            const campaignDate = document.getElementById('campaignDate').value;
            const campaignTopic = document.getElementById('campaignTopic').value;
            
            if (!campaignDate) {
                showToast('Please enter a campaign date', 'error');
                document.getElementById('campaignDate').focus();
                return;
            }
            
            if (!campaignTopic) {
                showToast('Please enter a campaign topic', 'error');
                document.getElementById('campaignTopic').focus();
                return;
            }
            
            document.getElementById('startButtonsRow').style.display = 'none';
            document.getElementById('autoSendSection').style.display = 'block';
            
            // Reset UI
            document.getElementById('successCount').textContent = '0';
            document.getElementById('failedCount').textContent = '0';
            document.getElementById('pendingCount').textContent = selectedPatients.size;
            document.getElementById('autoSendProgressBar').style.width = '0%';
            document.getElementById('autoSendProgressBar').textContent = '0%';
            document.getElementById('failedListContainer').style.display = 'none';
            document.getElementById('failedList').innerHTML = '';
        }

      function updateMessagePreview() {
    const campaignDate = document.getElementById('campaignDate').value;
    const campaignTopic = document.getElementById('campaignTopic').value;
    const customMessage = document.getElementById('customMessage').value;
    const startTime = document.getElementById('campaignStartTime').value;
    const endTime = document.getElementById('campaignEndTime').value;
    
    let preview = '';
    
    if (customMessage) {
        preview += `${customMessage}\n\n`;
    }
    
    if (campaignDate) {
        preview += `📅 **Date:** ${campaignDate}\n`;
    }
    
    if (campaignTopic) {
        preview += `📌 **Topic:** ${campaignTopic}\n`;
    }
    
    // Format time display (convert 24hr to 12hr format for better readability)
    let startTimeFormatted = startTime;
    let endTimeFormatted = endTime;
    if (startTime) {
        const startParts = startTime.split(':');
        let startHour = parseInt(startParts[0]);
        const startMinute = startParts[1];
        const startAmPm = startHour >= 12 ? 'PM' : 'AM';
        startHour = startHour % 12 || 12;
        startTimeFormatted = `${startHour}:${startMinute} ${startAmPm}`;
    }
    if (endTime) {
        const endParts = endTime.split(':');
        let endHour = parseInt(endParts[0]);
        const endMinute = endParts[1];
        const endAmPm = endHour >= 12 ? 'PM' : 'AM';
        endHour = endHour % 12 || 12;
        endTimeFormatted = `${endHour}:${endMinute} ${endAmPm}`;
    }
    
    preview += `🕒 **Time:** ${startTimeFormatted} - ${endTimeFormatted}\n`;
    preview += `📍 **Venue:** Dr. LSK Clinic\n`;
    preview += `📞 **Contact:** For more details, please call the clinic.\n\n`;
    preview += `*This is an automated message from Dr. LSK Clinic. Please confirm your availability.*`;
    
    document.getElementById('messagePreview').innerHTML = preview.replace(/\n/g, '<br>');
}

       // ========== WHATSAPP WEB AUTO SEND (PUPPETEER) ==========
// ========== WHATSAPP WEB AUTO SEND (PUPPETEER) ==========

// Global variables
let statusPollingInterval = null;
let hasShownCompletionToast = false;  // ← NEW: Prevents duplicate completion toast


async function startAutoSend() {
    if (isAutoSending) {
        showToast('Already sending messages. Please wait...', 'warning');
        return;
    }
    
    // Build queue from selected patients
    const patientsToSend = [];
    const checkboxes = document.querySelectorAll('.patient-select-checkbox:checked');
    
    checkboxes.forEach(checkbox => {
        const patientKey = checkbox.getAttribute('data-patient-key');
        const row = document.querySelector(`tr[data-patient-id="${patientKey}"]`);
        const patientPhone = row ? row.getAttribute('data-patient-phone') : '';
        const patientName = row ? row.getAttribute('data-patient-name') : '';
        
        if (patientPhone && patientPhone !== '') {
            patientsToSend.push({
                key: patientKey,
                name: patientName,
                phone: patientPhone
            });
        }
    });
    
    if (patientsToSend.length === 0) {
        showToast('No selected patients have phone numbers', 'error');
        return;
    }
    
    isAutoSending = true;
    
    // Disable send button
    const sendBtn = document.getElementById('startAutoSendBtn');
    sendBtn.disabled = true;
    sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
    
    // Build the message template
    const customMessage = document.getElementById('customMessage').value;
    const campaignDate = document.getElementById('campaignDate').value;
    const campaignTopic = document.getElementById('campaignTopic').value;
    const startTime = document.getElementById('campaignStartTime').value;
    const endTime = document.getElementById('campaignEndTime').value;
    
    // Create form data
    const formData = new FormData();
    formData.append('action', 'send_bulk');
    formData.append('patients', JSON.stringify(patientsToSend));
    formData.append('custom_message', customMessage);
    formData.append('campaign_date', campaignDate);
    formData.append('campaign_topic', campaignTopic);
    formData.append('campaign_start_time', startTime);
    formData.append('campaign_end_time', endTime);
    
    try {
        const response = await fetch('whatsapp_send.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.status === 'completed') {
            document.getElementById('successCount').textContent = result.sent || 0;
            document.getElementById('failedCount').textContent = result.failed || 0;
            document.getElementById('autoSendProgressBar').style.width = '100%';
            document.getElementById('autoSendProgressBar').textContent = '100%';
            document.getElementById('pendingCount').textContent = '0';
            
            if (result.sent > 0) {
                showToast(`✅ Successfully sent to ${result.sent} patient(s)!`, 'success');
            }
            if (result.failed > 0) {
                showToast(`⚠️ Failed to send to ${result.failed} patient(s)`, 'warning');
            }
        } else if (result.status === 'not_ready') {
            showToast('WhatsApp bot not ready. Please ensure QR code is scanned on Render.com', 'error');
            // Show QR instruction
            showQRInstruction();
        } else {
            showToast(result.message || 'Failed to send messages', 'error');
        }
        
    } catch (error) {
        console.error('Error sending messages:', error);
        showToast('Error: ' + error.message, 'error');
    } finally {
        sendBtn.disabled = false;
        sendBtn.innerHTML = '<i class="fab fa-whatsapp me-2"></i>Send to All (Auto)';
        isAutoSending = false;
    }
}

function showQRInstruction() {
    // Create a modal or alert showing how to get QR
    if (confirm('WhatsApp bot not ready. Would you like to view the QR code to connect WhatsApp?')) {
       window.open('https://drlsk-wv9j.onrender.com/status', '_blank');
    }
}

// Updated status polling function with duplicate prevention
function startStatusPolling() {
    // Clear existing interval
    if (statusPollingInterval) {
        clearInterval(statusPollingInterval);
    }
    
    // Reset completion flag
    hasShownCompletionToast = false;
    
    // Poll every 5 seconds
    statusPollingInterval = setInterval(async () => {
        try {
            const response = await fetch('whatsapp_send.php?action=check_status');
            const status = await response.json();
            
            // Update UI based on status
            if (status.completed === true) {
                // ✅ FIXED: Only show completion toast once
                if (!hasShownCompletionToast) {
                    hasShownCompletionToast = true;
                    
                    document.getElementById('successCount').textContent = status.sent || 0;
                    document.getElementById('failedCount').textContent = status.failed || 0;
                    document.getElementById('autoSendProgressBar').style.width = '100%';
                    document.getElementById('autoSendProgressBar').textContent = '100%';
                    document.getElementById('pendingCount').textContent = '0';
                    
                    // Show final result toast (ONLY ONCE)
                    if (status.failed > 0 && status.sent > 0) {
                        showToast(`⚠️ Completed: ${status.sent} sent, ${status.failed} failed`, 'warning');
                    } else if (status.failed > 0) {
                        showToast(`❌ Failed to send to ${status.failed} patient(s)`, 'error');
                    } else if (status.sent > 0) {
                        showToast(`✅ Successfully sent to ${status.sent} patient(s)!`, 'success');
                    }
                    
                    // Reset auto-sending flag
                    isAutoSending = false;
                }
                
                // Stop polling regardless
                clearInterval(statusPollingInterval);
                statusPollingInterval = null;
                
            } else if (status.status === 'processing' || (status.ready === false && status.status !== 'idle')) {
                // Update progress bar if available
                if (status.total && status.total > 0) {
                    const processed = (status.sent || 0) + (status.failed || 0);
                    const percent = Math.round((processed / status.total) * 100);
                    document.getElementById('autoSendProgressBar').style.width = percent + '%';
                    document.getElementById('autoSendProgressBar').textContent = percent + '%';
                    document.getElementById('successCount').textContent = status.sent || 0;
                    document.getElementById('failedCount').textContent = status.failed || 0;
                    document.getElementById('pendingCount').textContent = (status.total - processed) || 0;
                } else {
                    // Show indeterminate progress
                    document.getElementById('autoSendProgressBar').style.width = '60%';
                    document.getElementById('autoSendProgressBar').textContent = 'Sending...';
                }
            } else if (status.status === 'idle') {
                // No active sending, clean up
                if (statusPollingInterval) {
                    clearInterval(statusPollingInterval);
                    statusPollingInterval = null;
                }
                isAutoSending = false;
            }
        } catch (error) {
            console.error('Status check error:', error);
            // Don't stop polling on error, but log it
        }
    }, 5000);
}

function closeWhatsAppSection() {
    document.getElementById('whatsappShareSection').style.display = 'none';
    document.getElementById('autoSendSection').style.display = 'none';
    document.getElementById('startButtonsRow').style.display = 'block';
    if (isAutoSending) {
        isAutoSending = false;
    }
    // Stop status polling
    if (statusPollingInterval) {
        clearInterval(statusPollingInterval);
        statusPollingInterval = null;
    }
}

function showAutoSendSection() {
    const campaignDate = document.getElementById('campaignDate').value;
    const campaignTopic = document.getElementById('campaignTopic').value;
    const startTime = document.getElementById('campaignStartTime').value;
    const endTime = document.getElementById('campaignEndTime').value;
    
    if (!campaignDate) {
        showToast('Please enter a campaign date', 'error');
        document.getElementById('campaignDate').focus();
        return;
    }
    
    if (!campaignTopic) {
        showToast('Please enter a campaign topic', 'error');
        document.getElementById('campaignTopic').focus();
        return;
    }
    
    if (!startTime) {
        showToast('Please enter a start time', 'error');
        document.getElementById('campaignStartTime').focus();
        return;
    }
    
    if (!endTime) {
        showToast('Please enter an end time', 'error');
        document.getElementById('campaignEndTime').focus();
        return;
    }
    
    document.getElementById('startButtonsRow').style.display = 'none';
    document.getElementById('autoSendSection').style.display = 'block';
    
    // Reset UI
    document.getElementById('successCount').textContent = '0';
    document.getElementById('failedCount').textContent = '0';
    document.getElementById('pendingCount').textContent = selectedPatients.size;
    document.getElementById('autoSendProgressBar').style.width = '0%';
    document.getElementById('autoSendProgressBar').textContent = '0%';
    document.getElementById('failedListContainer').style.display = 'none';
    document.getElementById('failedList').innerHTML = '';
    
    // Stop any existing polling
    if (statusPollingInterval) {
        clearInterval(statusPollingInterval);
        statusPollingInterval = null;
    }
}

        function showVitalsModal(visit) {
            document.getElementById('modalBP').textContent = visit.bp || '--/--';
            document.getElementById('modalTemp').textContent = visit.temperature ? `${visit.temperature}°F` : '--°F';
            document.getElementById('modalHR').textContent = visit.heart_rate ? `${visit.heart_rate} bpm` : '-- bpm';
            document.getElementById('modalO2').textContent = visit.oxygen_level ? `${visit.oxygen_level}%` : '--%';
            document.getElementById('modalWH').textContent = `${visit.weight || '--'} kg / ${visit.height || '--'} cm`;
            document.getElementById('modalGlucose').textContent = visit.glucose ? `${visit.glucose} mg/dL` : '-- mg/dL';
            document.getElementById('modalRR').textContent = visit.respiratory_rate ? `${visit.respiratory_rate} rpm` : '-- rpm';
            
            new bootstrap.Modal(document.getElementById('vitalsModal')).show();
        }

        async function showLabReportModal(requestId) {
            if (!requestId) return;
            
            const modalBody = document.getElementById('labReportBody');
            modalBody.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border" style="color: var(--primary-purple);"></div>
                    <p class="mt-2">Loading lab report...</p>
                </div>
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('labReportModal'));
            modal.show();
            
            try {
                const response = await fetch(`Lab_backend.php?action=get_lab_results_for_doctor&request_id=${requestId}`);
                const data = await response.json();
                
                if (data.success) {
                    displayLabReportModal(data);
                } else {
                    modalBody.innerHTML = `
                        <div class="alert alert-warning text-center py-4">
                            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                            <p>${data.message || 'Unable to load lab report details.'}</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading lab report:', error);
                modalBody.innerHTML = `
                    <div class="alert alert-danger text-center py-4">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p>Failed to load lab report. Please try again.</p>
                    </div>
                `;
            }
        }

        function displayLabReportModal(data) {
            const request = data.request;
            const parameters = data.parameters || [];
            const modalBody = document.getElementById('labReportBody');
            const defaultDoctorName = "Dr. L. Senthilkumar MBBS., ACCID., ACMDC";
            let parametersHtml = '';
            
            if (parameters.length > 0) {
                parameters.forEach(param => {
                    let valueClass = '';
                    if (param.status === 'critical') {
                        valueClass = 'critical-value';
                    } else if (param.status === 'low' || param.status === 'high') {
                        valueClass = 'abnormal-value';
                    } else if (param.status === 'normal') {
                        valueClass = 'normal-value';
                    }
                    
                    parametersHtml += `
                        <tr>
                            <td>${escapeHtml(param.parameter)}</td>
                            <td class="${valueClass} fw-bold">${escapeHtml(param.result || '--')}</td>
                            <td>${escapeHtml(param.unit || '')}</td>
                            <td>${escapeHtml(param.normal_range || '')}</td>
                        </tr>
                    `;
                });
            } else {
                parametersHtml = `
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">
                            <i class="fas fa-info-circle me-2"></i>No test results available yet.
                        </td>
                    </tr>
                `;
            }
            
            const requestedDate = request.requested_at ? new Date(request.requested_at).toLocaleString() : 'Not available';
            
            modalBody.innerHTML = `
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="fw-bold">${escapeHtml(request.patient_name || 'Unknown Patient')}</h5>
                            <p class="text-muted small mb-1">
                                ${request.age || ''} years, ${request.gender || ''} · 
                                MRN: ${request.mrn || 'N/A'}
                            </p>
                        <p class="text-muted small mb-1">
    <i class="fas fa-user-md me-1"></i>Requested by: Dr. L. Senthilkumar MBBS., ACCID., ACMDC
</p>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <p class="small text-muted mb-1">
                                <i class="far fa-calendar-alt me-1"></i>
                                <strong>Requested:</strong> ${escapeHtml(requestedDate)}
                            </p>
                        </div>
                    </div>
                </div>
                
                <h6 class="fw-bold mb-3">Test Results</h6>
                <div class="table-responsive">
                    <table class="lab-parameter-table table table-bordered">
                        <thead class="table-light">
                            <tr><th>Parameter</th><th>Result</th><th>Unit</th><th>Reference Range</th></tr>
                        </thead>
                        <tbody>${parametersHtml}</tbody>
                    </table>
                </div>
                
                ${request.doctor_remarks ? `
                <div class="mt-4 p-3 bg-light rounded">
                    <small class="text-muted d-block mb-1 fw-bold">Doctor's Remarks:</small>
                    <p class="mb-0">${escapeHtml(request.doctor_remarks)}</p>
                </div>
                ` : ''}
            `;
        }

        async function showPatientDetails(visit) {
            const modalBody = document.getElementById('patientDetailsBody');
            modalBody.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border" style="color: var(--primary-purple);"></div>
                    <p class="mt-2">Loading patient details...</p>
                </div>
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('patientDetailsModal'));
            modal.show();
            
            try {
                const patientId = visit.patient_id || visit.display_id;
                let patientData = null;
                
                if (patientId) {
                    const response = await fetch(`patient_backend.php?action=get_patient_details&patient_id=${encodeURIComponent(patientId)}`);
                    const data = await response.json();
                    if (data.success) {
                        patientData = data.patient;
                    }
                }
                
                if (!patientData) {
                    patientData = {
                        full_name: visit.patient_name || '—',
                        patient_id: patientId || '—',
                        age: visit.age || '—',
                        gender: visit.gender || '—',
                        date_of_birth: '—',
                        phone: '—',
                        email: '—',
                        address: '—',
                        blood_group: '—',
                        allergy: '—'
                    };
                }
                
                let dobFormatted = '—';
                if (patientData.date_of_birth && patientData.date_of_birth !== '—') {
                    const dob = new Date(patientData.date_of_birth);
                    if (!isNaN(dob.getTime())) {
                        dobFormatted = dob.toLocaleDateString();
                    }
                }
                
                modalBody.innerHTML = `
                    <div class="patient-info-card">
                        <div class="patient-info-row">
                            <div class="patient-info-label"><i class="fas fa-id-card me-2"></i>Patient ID:</div>
                            <div class="patient-info-value">${escapeHtml(patientData.patient_id || '—')}</div>
                        </div>
                        <div class="patient-info-row">
                            <div class="patient-info-label"><i class="fas fa-user me-2"></i>Full Name:</div>
                            <div class="patient-info-value"><strong>${escapeHtml(patientData.full_name || '—')}</strong></div>
                        </div>
                        <div class="patient-info-row">
                            <div class="patient-info-label"><i class="fas fa-calendar me-2"></i>Date of Birth:</div>
                            <div class="patient-info-value">${escapeHtml(dobFormatted)}</div>
                        </div>
                        <div class="patient-info-row">
                            <div class="patient-info-label"><i class="fas fa-chart-line me-2"></i>Age:</div>
                            <div class="patient-info-value">${escapeHtml(patientData.age || '—')} years</div>
                        </div>
                        <div class="patient-info-row">
                            <div class="patient-info-label"><i class="fas fa-venus-mars me-2"></i>Gender:</div>
                            <div class="patient-info-value">${escapeHtml(patientData.gender || '—')}</div>
                        </div>
                    </div>
                    
                    <div class="section-divider"><i class="fas fa-address-book me-2"></i>Contact Information</div>
                    <div class="patient-info-card">
                        <div class="patient-info-row">
                            <div class="patient-info-label"><i class="fas fa-phone me-2"></i>Phone:</div>
                            <div class="patient-info-value">${escapeHtml(patientData.phone || '—')}</div>
                        </div>
                        <div class="patient-info-row">
                            <div class="patient-info-label"><i class="fas fa-envelope me-2"></i>Email:</div>
                            <div class="patient-info-value">${escapeHtml(patientData.email || '—')}</div>
                        </div>
                        <div class="patient-info-row">
                            <div class="patient-info-label"><i class="fas fa-location-dot me-2"></i>Address:</div>
                            <div class="patient-info-value">${escapeHtml(patientData.address || '—')}</div>
                        </div>
                    </div>
                    
                    <div class="section-divider"><i class="fas fa-heartbeat me-2"></i>Medical Information</div>
                    <div class="patient-info-card">
                        <div class="patient-info-row">
                            <div class="patient-info-label"><i class="fas fa-tint me-2"></i>Blood Group:</div>
                            <div class="patient-info-value">${escapeHtml(patientData.blood_group || '—')}</div>
                        </div>
                        <div class="patient-info-row">
                            <div class="patient-info-label"><i class="fas fa-allergies me-2"></i>Allergies:</div>
                            <div class="patient-info-value">${escapeHtml(patientData.allergy || patientData.allergies || '—')}</div>
                        </div>
                    </div>
                    
                    <div class="section-divider"><i class="fas fa-notes-medical me-2"></i>Visit Information</div>
                    <div class="patient-info-card">
                        <div class="patient-info-row">
                            <div class="patient-info-label"><i class="fas fa-clock me-2"></i>Visit Date:</div>
                            <div class="patient-info-value">${visit.visit_date ? new Date(visit.visit_date).toLocaleString() : '—'}</div>
                        </div>
                        <div class="patient-info-row">
                            <div class="patient-info-label"><i class="fas fa-stethoscope me-2"></i>Patient Type:</div>
                            <div class="patient-info-value">${visit.patient_type === 'home_care' ? 'Home Care' : 'Regular'}</div>
                        </div>
                        <div class="patient-info-row">
                            <div class="patient-info-label"><i class="fas fa-file-alt me-2"></i>Complaints:</div>
                            <div class="patient-info-value">${escapeHtml(visit.complaints || '—')}</div>
                        </div>
                    </div>
                `;
                
            } catch (error) {
                console.error('Error loading patient details:', error);
                modalBody.innerHTML = `
                    <div class="alert alert-danger text-center py-4">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p>Failed to load patient details. Please try again.</p>
                    </div>
                `;
            }
        }

        function showLoading() {
            const tbody = document.getElementById('visitsTableBody');
            tbody.innerHTML = `<tr><td colspan="9" class="no-data"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Loading...<\/td></tr>`;
        }
        
        function showError(message) {
            const tbody = document.getElementById('visitsTableBody');
            tbody.innerHTML = `<tr><td colspan="9" class="no-data"><i class="fas fa-exclamation-triangle fa-2x" style="color: #dc3545;"></i><br>${escapeHtml(message)}<\/td></tr>`;
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showToast(message, type = 'success') {
            const toastContainer = document.getElementById('toastContainer');
            const toastId = 'toast_' + Date.now();
            const bgColor = type === 'success' ? '#28a745' : (type === 'error' ? '#dc3545' : '#ffc107');
            const icon = type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle');
            
            const toastHtml = `
                <div id="${toastId}" class="toast-notification" style="background: white; border-left: 4px solid ${bgColor}; border-radius: 8px; padding: 12px 20px; margin-bottom: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); min-width: 300px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class="fas ${icon}" style="color: ${bgColor}; font-size: 20px;"></i>
                        <div style="flex: 1;">
                            <p style="margin: 0; font-size: 14px;">${message}</p>
                        </div>
                        <button onclick="closeToast('${toastId}')" style="background: none; border: none; cursor: pointer; color: #999;">&times;</button>
                    </div>
                </div>
            `;
            
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            
            setTimeout(() => {
                closeToast(toastId);
            }, 4000);
        }
        
        function closeToast(toastId) {
            const toast = document.getElementById(toastId);
            if (toast) {
                toast.classList.add('toast-hide');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }
        }

        // ========== COMPLAINT SEARCH FUNCTIONS ==========

        async function searchPatientsByComplaint() {
            const complaint = document.getElementById('complaintSearchInput').value.trim();
            
            if (!complaint) {
                showToast('Please enter a complaint to search', 'warning');
                return;
            }
            
            showLoading();
            
            try {
                const response = await fetch(`patient_backend.php?action=search_by_complaint&complaint=${encodeURIComponent(complaint)}`);
                const data = await response.json();
                
                if (data.success && data.patients && data.patients.length > 0) {
                    currentSearchResults = data.patients;
                    displayComplaintSearchResults(data.patients, complaint);
                    
                    document.getElementById('totalCount').textContent = data.count;
                    document.getElementById('periodDisplay').innerHTML = `
                        <i class="fas fa-stethoscope me-2"></i>
                        Complaint Search: "${escapeHtml(complaint)}" - Found ${data.count} patient(s)
                    `;
                    
                    document.getElementById('selectAllCheckbox').style.display = 'inline-block';
                } else {
                    document.getElementById('visitsTableBody').innerHTML = `
                        <tr>
                            <td colspan="9" class="no-data">
                                <i class="fas fa-search fa-2x mb-2" style="color: #999;"></i>
                                <h6>No patients found with complaint matching "${escapeHtml(complaint)}"</h6>
                                <p class="text-muted small">Try different keywords like "fever", "diabetes", "cough", etc.</p>
                            </td>
                        </tr>
                    `;
                    
                    document.getElementById('totalCount').textContent = '0';
                    document.getElementById('regularCount').textContent = '0';
                    document.getElementById('homecareCount').textContent = '0';
                    document.getElementById('periodDisplay').innerHTML = `
                        <i class="fas fa-stethoscope me-2"></i>
                        Complaint Search: "${escapeHtml(complaint)}" - No results
                    `;
                    document.getElementById('selectAllCheckbox').style.display = 'none';
                }
            } catch (error) {
                console.error('Complaint search error:', error);
                showToast('Error searching by complaint', 'error');
                showError('Failed to search patients by complaint');
            }
        }

        function displayComplaintSearchResults(patients, searchTerm) {
            const tbody = document.getElementById('visitsTableBody');
            
            if (!patients || patients.length === 0) {
                tbody.innerHTML = `<tr><td colspan="9" class="no-data">No results found<\/td></tr>`;
                return;
            }
            
            let regularCount = 0;
            let homecareCount = 0;
            
            let html = '';
            patients.forEach(patient => {
                const patientType = patient.patient_type === 'home_care' ? 'home_care' : 'regular';
                if (patientType === 'regular') regularCount++;
                else homecareCount++;
                
                const typeBadge = patientType === 'home_care' 
                    ? '<span class="badge-homecare"><i class="fas fa-home me-1"></i>Home Care</span>'
                    : '<span class="badge-regular"><i class="fas fa-user me-1"></i>Regular</span>';
                
                const demographics = `${patient.age || '?'} yrs, ${patient.gender || '?'}`;
                const patientId = patient.display_id || patient.patient_id || '—';
                const complaint = patient.matching_complaint || '—';
                const truncatedComplaint = complaint.length > 50 ? complaint.substring(0, 47) + '...' : complaint;
                const patientKey = patient.master_id || patient.id;
                
                const isSelected = selectedPatients.has(patientKey);
                const rowClass = isSelected ? 'selected-patient-row' : '';
                
                let lastVisitStr = '—';
                if (patient.last_visit_with_complaint) {
                    const d = new Date(patient.last_visit_with_complaint);
                    if (!isNaN(d.getTime())) {
                        lastVisitStr = d.toLocaleDateString();
                    }
                }
                
                const patientData = {
                    master_id: patientKey,
                    name: patient.full_name || '—',
                    phone: patient.phone || '',
                    id: patientId
                };
                
                html += `
                    <tr class="${rowClass}" data-patient-id="${escapeHtml(patientKey)}" data-patient-phone="${escapeHtml(patient.phone || '')}" data-patient-name="${escapeHtml(patient.full_name || '—')}">
                        <td style="text-align: center;">
                            <input type="checkbox" class="patient-select-checkbox" data-patient-key="${escapeHtml(patientKey)}" ${isSelected ? 'checked' : ''} onchange="togglePatientSelection('${escapeHtml(patientKey)}', this.checked, ${JSON.stringify(patientData).replace(/'/g, "\\'")})">
                          </td>
                          <td>
                            <i class="fas fa-calendar-alt text-muted me-1"></i>
                            ${escapeHtml(lastVisitStr)}
                          </td>
                          <td><span class="fw-bold" style="color: var(--primary-purple);">${escapeHtml(patientId)}</span></td>
                          <td><strong>${escapeHtml(patient.full_name || '—')}</strong></td>
                          <td>${escapeHtml(demographics)}</td>
                          <td>${typeBadge}</td>
                        <td class="complaint-cell" title="${escapeHtml(complaint)}">
                            <i class="fas fa-stethoscope me-1" style="color: var(--primary-purple);"></i>
                            ${escapeHtml(truncatedComplaint)}
                          </td>
                          <td>
                            <button class="btn-view" onclick='viewPatientDetailsFromSearch(${JSON.stringify(patient).replace(/'/g, "\\'")})'>
                                <i class="fas fa-user me-1"></i>View
                            </button>

                            
                          </td>
                          <td>
                            <button class="btn-view-lab" onclick='viewPatientHistory(${patient.master_id || patient.id})'>
                                <i class="fas fa-history me-1"></i>History
                            </button>
                          </td>
                          
                      </tr>
                `;
            });
            
            tbody.innerHTML = html;
            
            document.getElementById('totalCount').textContent = patients.length;
            document.getElementById('regularCount').textContent = regularCount;
            document.getElementById('homecareCount').textContent = homecareCount;
            
            document.getElementById('selectAllCheckbox').style.display = 'inline-block';
            
            updateSelectionUI();
        }

        function viewPatientHistory(patientMasterId) {
            if (!patientMasterId) {
                showToast('Invalid patient ID', 'error');
                return;
            }
            window.location.href = `PatientsDetails.php?view_history=${patientMasterId}`;
        }

        function viewPatientDetailsFromSearch(patient) {
            const modalBody = document.getElementById('patientDetailsBody');
            modalBody.innerHTML = `
                <div class="patient-info-card">
                    <div class="patient-info-row">
                        <div class="patient-info-label"><i class="fas fa-id-card me-2"></i>Patient ID:</div>
                        <div class="patient-info-value">${escapeHtml(patient.display_id || patient.patient_id || '—')}</div>
                    </div>
                    <div class="patient-info-row">
                        <div class="patient-info-label"><i class="fas fa-user me-2"></i>Full Name:</div>
                        <div class="patient-info-value"><strong>${escapeHtml(patient.full_name || '—')}</strong></div>
                    </div>
                    <div class="patient-info-row">
                        <div class="patient-info-label"><i class="fas fa-chart-line me-2"></i>Age:</div>
                        <div class="patient-info-value">${escapeHtml(patient.age || '—')} years</div>
                    </div>
                    <div class="patient-info-row">
                        <div class="patient-info-label"><i class="fas fa-venus-mars me-2"></i>Gender:</div>
                        <div class="patient-info-value">${escapeHtml(patient.gender || '—')}</div>
                    </div>
                    <div class="patient-info-row">
                        <div class="patient-info-label"><i class="fas fa-phone me-2"></i>Phone:</div>
                        <div class="patient-info-value">${escapeHtml(patient.phone || '—')}</div>
                    </div>
                    <div class="patient-info-row">
                        <div class="patient-info-label"><i class="fas fa-tint me-2"></i>Blood Group:</div>
                        <div class="patient-info-value">${escapeHtml(patient.blood_group || '—')}</div>
                    </div>
                    <div class="patient-info-row">
                        <div class="patient-info-label"><i class="fas fa-allergies me-2"></i>Allergies:</div>
                        <div class="patient-info-value">${escapeHtml(patient.allergy || '—')}</div>
                    </div>
                </div>
                
                <div class="section-divider"><i class="fas fa-stethoscope me-2"></i>Last Visit Complaint</div>
                <div class="patient-info-card">
                    <div class="patient-info-row">
                        <div class="patient-info-label">Complaint:</div>
                        <div class="patient-info-value">${escapeHtml(patient.matching_complaint || '—')}</div>
                    </div>
                    <div class="patient-info-row">
                        <div class="patient-info-label">Total Visits:</div>
                        <div class="patient-info-value">${patient.visit_count || 0}</div>
                    </div>
                </div>
            `;
            
            new bootstrap.Modal(document.getElementById('patientDetailsModal')).show();
        }

        function clearComplaintSearch() {
            document.getElementById('complaintSearchInput').value = '';
            selectedPatients.clear();
            const checkboxes = document.querySelectorAll('.patient-select-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
                const row = checkbox.closest('tr');
                if (row) row.classList.remove('selected-patient-row');
            });
            document.getElementById('selectAllCheckbox').style.display = 'none';
            document.getElementById('whatsappShareBtn').disabled = true;
            document.getElementById('selectedCount').textContent = '0';
            document.getElementById('selectionModeIndicator').innerHTML = '';
            loadHistory();
        }
        
        window.loadHistory = loadHistory;
        window.setToday = setToday;
        window.setYesterday = setYesterday;
        window.filterVisits = filterVisits;
        window.showVitalsModal = showVitalsModal;
        window.showPatientDetails = showPatientDetails;
        window.showLabReportModal = showLabReportModal;
        window.togglePatientSelection = togglePatientSelection;
        window.toggleSelectAll = toggleSelectAll;
        window.selectAllPatients = selectAllPatients;
        window.deselectAllPatients = deselectAllPatients;
        window.openWhatsAppShare = openWhatsAppShare;
        window.closeWhatsAppSection = closeWhatsAppSection;
        window.showAutoSendSection = showAutoSendSection;
        window.startAutoSend = startAutoSend;
        window.viewPatientHistory = viewPatientHistory;
        window.viewPatientDetailsFromSearch = viewPatientDetailsFromSearch;
        window.closeToast = closeToast;
        window.showToast = showToast;
    </script>
</body>
</html>