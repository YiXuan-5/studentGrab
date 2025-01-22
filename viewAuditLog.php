<?php
session_start();
include 'dbConnection.php';

// Check if user is logged in
if (!isset($_SESSION['AdminID'])) {
    header('Location: loginAdm.php');
    exit();
}

// Fetch unique UserIDs for the dropdown
$userIDs = [];
try {
    $stmt = $connMe->prepare("SELECT DISTINCT UserID FROM AUDIT_LOG ORDER BY UserID ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $userIDs[] = $row['UserID'];
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Audit Log</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/fontawesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/solid.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <style>
        /* Copy styles from viewAdm.php */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        .navbar {
            background-color: #4caf50;
            padding: 10px 20px;
            position: fixed;
            width: 100%;
            top: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
        }

        .nav-item {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            display: flex;
            gap: 5px;
            transition: background-color 0.3s;
        }

        .nav-item:hover {
            background-color: #388e3c;
        }

        .container {
            display: flex;
            height: calc(100vh - 60px);
            padding-top: 60px;
        }

        .left-section {
            flex: 1;
            padding: 15px;
            max-width: 300px;
            background-color: #fff3cd;
        }

        .right-section {
            flex: 3;
            padding: 20px;
            background-color: white;
            overflow-x: auto;
            overflow-y: auto;
            width: 85%;
            max-width: 85%;
        }

        .section-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #2e7d32;
        }

        .search-criteria {
            margin-bottom: 20px;
        }

        .search-criteria label {
            font-size: 18px;
            display: block;
            margin-bottom: 5px;
        }

        .search-criteria select,
        .search-criteria input[type="text"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 15px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .radio-option input[type="radio"] {
            margin: 0;
        }

        .button {
            background-color: #4caf50;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }

        .button:hover {
            background-color: #388e3c;
        }

        .result-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
        }

        .result-table th,
        .result-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        .result-table th {
            background-color: #4caf50;
            color: white;
            cursor: pointer;
        }

        .result-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .result-table tr:hover {
            background-color: #f5f5f5;
        }

        #noResults {
            text-align: center;
            padding: 20px;
            font-size: 16px;
            color: #666;
            display: none; /* Initially hidden */
        }

        #totalLogs {
            margin: 20px 0;
            font-size: 16px;
            color: #000;
        }

        @media print {
            .result-table th { 
                background-color: #4caf50 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
            }
            @page {
                size: landscape;
            }
        }

        .generate-report-btn {
            background-color: #4caf50;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
        }

        .generate-report-btn:hover {
            background-color: #388e3c;
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #4caf50;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 5px;
            margin-top: 5px;
        }

        .dropdown-content a {
            color: white;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }

        .dropdown-content a:hover {
            background-color: #388e3c;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        .no-results {
            color: #ff0000;
            padding: 20px;
            text-align: left;
            font-size: 16px;
        }

        .nav-items {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .header-label {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
        }

        .header-label span {
            color: #000;
            transition: color 0.3s;
        }

        .header-label span:hover {
            color: #4caf50;
        }

        .header-label strong {
            color: #000;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-items">
            <a href="homePageAdm.php" class="nav-item">
                <i class="fas fa-home"></i>
                Home
            </a>
            <div class="dropdown">
                <a href="javascript:void(0)" class="nav-item">
                    <i class="fas fa-users-cog"></i>
                    User Management
                </a>
                <div class="dropdown-content">
                    <a href="viewPsgr.php">Passenger</a>
                    <a href="viewDri.php">Driver</a>
                    <a href="viewAdm.php">Admin</a>
                    <a href="viewAuditLog.php">Audit Log</a>
                    <a href="viewAuditTrail.php">Audit Trail</a>
                </div>
            </div>
        </div>
        <div class="nav-items right">
            <a href="profileAdm.php" class="nav-item">
                <i class="fas fa-user"></i>
                Profile
            </a>
        </div>
    </nav>

    <div class="container">
        <!-- Left Section -->
        <div class="left-section">
            <h2 class="section-title">Search Criteria</h2>
            
            <!-- Search Options -->
            <div class="search-criteria">
                <label for="searchCriteria">Search By:</label>
                <select id="searchCriteria" onchange="toggleSearchFields()">
                    <option value="all">All</option>
                    <option value="userID">User ID</option>
                    <option value="fullName">Full Name</option>
                    <option value="userType">User Type</option>
                    <option value="action">Action</option>
                    <option value="status">Status</option>
                </select>

                <!-- UserID Dropdown -->
                <div id="userIDField" style="display: none;">
                    <label for="userIDSelect">Select...</label>
                    <select id="userIDSelect">
                        <?php foreach ($userIDs as $id): ?>
                            <option value="<?php echo htmlspecialchars($id); ?>">
                                <?php echo htmlspecialchars($id); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Full Name Field -->
                <div id="fullNameField" style="display: none;">
                    <label for="fullNameInput">Full Name:</label>
                    <input type="text" id="fullNameInput">
                </div>

                <!-- User Type Radio Buttons -->
                <div id="userTypeField" style="display: none;">
                    <label>Select User Type:</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="passenger" name="userType" value="PASSENGER">
                            <label for="passenger">Passenger</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="driver" name="userType" value="DRIVER">
                            <label for="driver">Driver</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="admin" name="userType" value="ADMIN">
                            <label for="admin">Admin</label>
                        </div>
                    </div>
                </div>

                <!-- Action Radio Buttons -->
                <div id="actionField" style="display: none;">
                    <label>Select Action:</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="login" name="action" value="LOGIN">
                            <label for="login">Login</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="logout" name="action" value="LOGOUT">
                            <label for="logout">Logout</label>
                        </div>
                    </div>
                </div>

                <!-- Status Radio Buttons -->
                <div id="statusField" style="display: none;">
                    <label>Select Status:</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="success" name="status" value="SUCCESS">
                            <label for="success">Success</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="failedDeactivated" name="status" value="FAILED - ACCOUNT DEACTIVATED">
                            <label for="failedDeactivated">Failed - Account Deactivated</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="failed" name="status" value="FAILED">
                            <label for="failed">Failed</label>
                        </div>
                    </div>
                </div>

                <button class="button" onclick="searchLogs()">Search</button>
            </div>
        </div>

        <!-- Right Section -->
        <div class="right-section">
            <h2 class="section-title">Audit Log Records</h2>
            <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 15px;">
                <div id="totalLogs"></div>
                <button onclick="generateReport()" class="generate-report-btn">
                    <i class="fas fa-file-pdf"></i>
                    Generate Report
                </button>
            </div>
            <div class="header-label" style="margin-bottom: 20px;">
                <strong>Sort by:</strong>
                <span onclick="toggleSortOrder('LogID')" style="cursor: pointer;">Log ID <i class="fas fa-sort"></i></span> |
                <span onclick="toggleSortOrder('UserID')" style="cursor: pointer;">User ID <i class="fas fa-sort"></i></span> |
                <span onclick="toggleSortOrder('FullName')" style="cursor: pointer;">Full Name <i class="fas fa-sort"></i></span>
            </div>
            <div id="resultContainer">
                <div id="noResults">No matching records found</div>
            </div>
        </div>
    </div>

    <script>
        let originalLogData = [];
        let currentSortField = 'UserID';
        let currentSortOrder = 'asc';
        let sortOrder = 'asc';
        let sortCriterion = 'LogID';

        function toggleSearchFields() {
            const criteria = document.getElementById('searchCriteria').value;
            
            // Hide all fields first
            document.getElementById('userIDField').style.display = 'none';
            document.getElementById('fullNameField').style.display = 'none';
            document.getElementById('userTypeField').style.display = 'none';
            document.getElementById('actionField').style.display = 'none';
            document.getElementById('statusField').style.display = 'none';
            
            // Show the selected field
            if (criteria !== 'all') {
                document.getElementById(criteria + 'Field').style.display = 'block';
            }
        }

        function searchLogs() {
            const criteria = document.getElementById('searchCriteria').value;
            const data = { criteria };
            
            switch (criteria) {
                case 'userID':
                    data.userID = document.getElementById('userIDSelect').value;
                    break;
                case 'fullName':
                    data.fullName = document.getElementById('fullNameInput').value;
                    break;
                case 'userType':
                    const selectedType = document.querySelector('input[name="userType"]:checked');
                    if (selectedType) data.userType = selectedType.value;
                    break;
                case 'action':
                    const selectedAction = document.querySelector('input[name="action"]:checked');
                    if (selectedAction) data.action = selectedAction.value;
                    break;
                case 'status':
                    const selectedStatus = document.querySelector('input[name="status"]:checked');
                    if (selectedStatus) data.status = selectedStatus.value;
                    break;
            }

            fetch('fetchAuditLogs.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }
                
                originalLogData = data;
                renderLogs(data);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while fetching the logs');
            });
        }

        function renderLogs(logs) {
            const container = document.getElementById('resultContainer');
            const noResults = document.getElementById('noResults');
            const generateReportBtn = document.querySelector('.generate-report-btn');
            
            if (!logs || !Array.isArray(logs) || logs.length === 0) {
                document.getElementById('totalLogs').innerHTML = '<strong>Total logs: 0</strong>';
                container.innerHTML = '<p class="no-results">Results not found</p>';
                generateReportBtn.style.display = 'none';
                return;
            }
            
            document.getElementById('totalLogs').innerHTML = `<strong>Total logs: ${logs.length}</strong>`;
            generateReportBtn.style.display = 'flex';

            let tableHTML = `
                <table class="result-table">
                    <tr>
                        <th onclick="sortLogs('LogID')">Log ID</th>
                        <th onclick="sortLogs('UserID')">User ID</th>
                        <th onclick="sortLogs('FullName')">Full Name</th>
                        <th onclick="sortLogs('UserTypeForLog')">User Type</th>
                        <th onclick="sortLogs('Action')">Action</th>
                        <th onclick="sortLogs('Status')">Status</th>
                        <th onclick="sortLogs('IPAddress')">IP Address</th>
                        <th onclick="sortLogs('DeviceInfo')">Device Info</th>
                        <th onclick="sortLogs('TimeStamp')">Timestamp</th>
                    </tr>
            `;

            logs.forEach(log => {
                const timestamp = new Date(log.TimeStamp).toLocaleString('en-GB');
                tableHTML += `
                    <tr>
                        <td>${log.LogID}</td>
                        <td>${log.UserID}</td>
                        <td>${log.FullName ? log.FullName.toUpperCase() : '-'}</td>
                        <td>${log.UserTypeForLog}</td>
                        <td>${log.Action}</td>
                        <td>${log.Status}</td>
                        <td>${log.IPAddress}</td>
                        <td>${log.DeviceInfo}</td>
                        <td>${timestamp}</td>
                    </tr>
                `;
            });

            tableHTML += '</table>';
            container.innerHTML = tableHTML;
        }

        function toggleSortOrder(criterion) {
            if (sortCriterion === criterion) {
                sortOrder = sortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                sortCriterion = criterion;
                sortOrder = 'asc';
            }
            sortLogs(criterion);
        }

        function sortLogs(field) {
            const sortedData = [...originalLogData].sort((a, b) => {
                let valueA = a[field] || '';
                let valueB = b[field] || '';

                if (field === 'TimeStamp') {
                    valueA = new Date(valueA);
                    valueB = new Date(valueB);
                } else if (typeof valueA === 'string') {
                    valueA = valueA.toUpperCase();
                    valueB = valueB.toUpperCase();
                }

                if (valueA < valueB) return sortOrder === 'asc' ? -1 : 1;
                if (valueA > valueB) return sortOrder === 'asc' ? 1 : -1;
                return 0;
            });

            renderLogs(sortedData);
        }

        function generateReport() {
            // Create a new window for printing
            const printWindow = window.open('', '_blank');
            
            // Get the currently displayed data
            const currentData = [...document.querySelectorAll('.result-table tr')]
                .slice(1) // Skip header row
                .map(row => ({
                    LogID: row.cells[0].textContent,
                    UserID: row.cells[1].textContent,
                    FullName: row.cells[2].textContent.toUpperCase(),
                    UserTypeForLog: row.cells[3].textContent,
                    Action: row.cells[4].textContent,
                    Status: row.cells[5].textContent,
                    IPAddress: row.cells[6].textContent,
                    DeviceInfo: row.cells[7].textContent,
                    TimeStamp: row.cells[8].textContent
                }));

            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Audit Log Report</title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #4caf50; color: white; }
                        tr:nth-child(even) { background-color: #f9f9f9; }
                        .report-header { margin-bottom: 20px; }
                        @media print {
                            th { 
                                background-color: #4caf50 !important;
                                color: white !important;
                                -webkit-print-color-adjust: exact;
                            }
                            @page { size: landscape; }
                        }
                    </style>
                </head>
                <body>
                    <div class="report-header">
                        <h2>Audit Log Report</h2>
                        <p>Generated on: ${new Date().toLocaleString()}</p>
                        <p>Total Logs: ${currentData.length}</p>
                    </div>
                    <table>
                        <tr>
                            <th>No</th>
                            <th>Log ID</th>
                            <th>User ID</th>
                            <th>Full Name</th>
                            <th>User Type</th>
                            <th>Action</th>
                            <th>Status</th>
                            <th>IP Address</th>
                            <th>Device Info</th>
                            <th>Timestamp</th>
                        </tr>
                        ${currentData.map((log, index) => `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${log.LogID}</td>
                                <td>${log.UserID}</td>
                                <td>${log.FullName}</td>
                                <td>${log.UserTypeForLog}</td>
                                <td>${log.Action}</td>
                                <td>${log.Status}</td>
                                <td>${log.IPAddress}</td>
                                <td>${log.DeviceInfo}</td>
                                <td>${log.TimeStamp}</td>
                            </tr>
                        `).join('')}
                    </table>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.onload = function() {
                printWindow.print();
            };
        }

        // Initial search for all logs when page loads
        document.addEventListener('DOMContentLoaded', () => {
            searchLogs();
        });
    </script>
</body>
</html> 