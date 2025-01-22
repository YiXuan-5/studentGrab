<?php
session_start();
include 'dbConnection.php';

// Check if user is logged in
if (!isset($_SESSION['AdminID'])) {
    header('Location: loginAdm.php');
    exit();
}

// Fetch unique values for dropdowns
try {
    // Trail IDs
    $trailIDs = [];
    $stmt = $connMe->prepare("SELECT DISTINCT TrailID FROM AUDIT_TRAIL ORDER BY TrailID ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $trailIDs[] = $row['TrailID'];
    }

    // Table Names
    $tableNames = [];
    $stmt = $connMe->prepare("SELECT DISTINCT TableName FROM AUDIT_TRAIL ORDER BY TableName ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $tableNames[] = $row['TableName'];
    }

    // User IDs
    $userIDs = [];
    $stmt = $connMe->prepare("SELECT DISTINCT UserID FROM AUDIT_TRAIL WHERE UserID IS NOT NULL ORDER BY UserID ASC");
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
    <title>View Audit Trail</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/fontawesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/solid.min.css">
    <style>
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

        .no-results {
            color: #ff0000;
            padding: 20px;
            text-align: left;
            font-size: 16px;
        }

        #totalTrails {
            margin: 20px 0;
            font-size: 16px;
            color: #000;
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

        /* Additional styles for pre tags in table cells */
        .result-table td pre {
            margin: 0;
            white-space: pre-wrap;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar (same as viewAuditLog.php) -->
    <nav class="navbar">
        <div class="nav-items">
            <a href="homePageAdm.php" class="nav-item">
                <i class="fas fa-home"></i>
                Home
            </a>
            <div class="dropdown">
                <a href="userMgmt.php" class="nav-item">
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
                    <option value="tableName">Table Name</option>
                    <option value="action">Action</option>
                    <option value="userID">User ID</option>
                </select>

                <!-- Table Name Dropdown -->
                <div id="tableNameField" style="display: none;">
                    <label for="tableNameSelect">Select Table:</label>
                    <select id="tableNameSelect">
                        <?php foreach ($tableNames as $name): ?>
                            <option value="<?php echo htmlspecialchars($name); ?>">
                                <?php echo htmlspecialchars($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Action Dropdown -->
                <div id="actionField" style="display: none;">
                    <label for="actionSelect">Select Action:</label>
                    <select id="actionSelect">
                        <option value="INSERT">INSERT</option>
                        <option value="UPDATE">UPDATE</option>
                        <option value="DELETE">DELETE</option>
                    </select>
                </div>

                <!-- User ID Dropdown -->
                <div id="userIDField" style="display: none;">
                    <label for="userIDSelect">Select User ID:</label>
                    <select id="userIDSelect">
                        <?php foreach ($userIDs as $id): ?>
                            <option value="<?php echo htmlspecialchars($id); ?>">
                                <?php echo htmlspecialchars($id); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button class="button" onclick="searchTrails()">Search</button>
            </div>
        </div>

        <!-- Right Section -->
        <div class="right-section">
            <h2 class="section-title">Audit Trail Records</h2>
            <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px;">
                <div id="totalTrails" style="font-size: 16px; font-weight: bold;">Total trails: 0</div>
                <button onclick="generateReport()" class="generate-report-btn">
                    <i class="fas fa-file-pdf"></i>
                    Generate Report
                </button>
            </div>
            <div class="header-label" style="margin-bottom: 20px;">
                <strong>Sort by:</strong>
                <span onclick="toggleSortOrder('TrailID')" style="cursor: pointer;">Trail ID <i class="fas fa-sort"></i></span> |
                <span onclick="toggleSortOrder('RecordID')" style="cursor: pointer;">Record ID <i class="fas fa-sort"></i></span> |
                <span onclick="toggleSortOrder('UserID')" style="cursor: pointer;">User ID <i class="fas fa-sort"></i></span> |
                <span onclick="toggleSortOrder('FullName')" style="cursor: pointer;">Full Name <i class="fas fa-sort"></i></span>
            </div>
            <div id="resultContainer">
                <div id="noResults" class="no-results">Results not found</div>
            </div>
        </div>
    </div>

    <script>
        let originalTrailData = [];
        let sortOrder = 'asc';
        let sortCriterion = 'TrailID';

        function toggleSearchFields() {
            const criteria = document.getElementById('searchCriteria').value;
            
            // Hide all fields first
            document.getElementById('tableNameField').style.display = 'none';
            document.getElementById('actionField').style.display = 'none';
            document.getElementById('userIDField').style.display = 'none';
            
            // Show the selected field
            if (criteria !== 'all') {
                document.getElementById(criteria + 'Field').style.display = 'block';
            }
        }

        function searchTrails() {
            const criteria = document.getElementById('searchCriteria').value;
            const data = { criteria };
            
            switch (criteria) {
                case 'tableName':
                    data.tableName = document.getElementById('tableNameSelect').value;
                    break;
                case 'action':
                    data.action = document.getElementById('actionSelect').value;
                    break;
                case 'userID':
                    data.userID = document.getElementById('userIDSelect').value;
                    break;
            }

            console.log('Sending request with data:', data); // Add debugging

            fetch('fetchAuditTrails.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                console.log('Received response:', data); // Add debugging
                if (data.error) {
                    alert(data.error);
                    return;
                }
                
                originalTrailData = data;
                renderTrails(data);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while fetching the trails');
            });
        }

        function renderTrails(trails) {
            const container = document.getElementById('resultContainer');
            const generateReportBtn = document.querySelector('.generate-report-btn');
            const totalTrailsDiv = document.getElementById('totalTrails');
            
            console.log('Rendering trails:', trails); // Add debugging
            
            if (!trails || !Array.isArray(trails) || trails.length === 0) {
                totalTrailsDiv.textContent = 'Total trails: 0';
                container.innerHTML = '<p class="no-results">Results not found</p>';
                generateReportBtn.style.display = 'none';
                return;
            }
            
            totalTrailsDiv.textContent = `Total trails: ${trails.length}`;
            generateReportBtn.style.display = 'flex';

            let tableHTML = `
                <table class="result-table">
                    <tr>
                        <th>No</th>
                        <th onclick="toggleSortOrder('TrailID')">Trail ID</th>
                        <th onclick="toggleSortOrder('TableName')">Table Name</th>
                        <th onclick="toggleSortOrder('RecordID')">Record ID</th>
                        <th onclick="toggleSortOrder('UserID')">User ID</th>
                        <th onclick="toggleSortOrder('FullName')">Full Name</th>
                        <th>Old Data</th>
                        <th>New Data</th>
                        <th onclick="toggleSortOrder('TimeStamp')">Timestamp</th>
                    </tr>
            `;

            trails.forEach((trail, index) => {
                const timestamp = new Date(trail.TimeStamp).toLocaleString('en-GB');
                tableHTML += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${trail.TrailID}</td>
                        <td>${trail.TableName}</td>
                        <td>${trail.RecordID}</td>
                        <td>${trail.UserID || '-'}</td>
                        <td>${(trail.FullName || '-').toUpperCase()}</td>
                        <td><pre>${trail.OldData || '-'}</pre></td>
                        <td><pre>${trail.NewData || '-'}</pre></td>
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
            sortTrails(criterion);
        }

        function sortTrails(field) {
            const sortedData = [...originalTrailData].sort((a, b) => {
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

            renderTrails(sortedData);
        }

        function generateReport() {
            // Create a new window for printing
            const printWindow = window.open('', '_blank');
            
            // Get the currently displayed data
            const currentData = [...document.querySelectorAll('.result-table tr')]
                .slice(1) // Skip header row
                .map(row => ({
                    No: row.cells[0].textContent,
                    TrailID: row.cells[1].textContent,
                    TableName: row.cells[2].textContent,
                    RecordID: row.cells[3].textContent,
                    UserID: row.cells[4].textContent,
                    OldData: row.cells[5].textContent,
                    NewData: row.cells[6].textContent,
                    TimeStamp: row.cells[7].textContent
                }));

            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Audit Trail Report</title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #4caf50; color: white; }
                        tr:nth-child(even) { background-color: #f9f9f9; }
                        .report-header { margin-bottom: 20px; }
                        pre { margin: 0; white-space: pre-wrap; }
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
                        <h2>Audit Trail Report</h2>
                        <p>Generated on: ${new Date().toLocaleString()}</p>
                        <p>Total Trails: ${currentData.length}</p>
                    </div>
                    <table>
                        <tr>
                            <th>No</th>
                            <th>Trail ID</th>
                            <th>Table Name</th>
                            <th>Record ID</th>
                            <th>User ID</th>
                            <th>Old Data</th>
                            <th>New Data</th>
                            <th>Timestamp</th>
                        </tr>
                        ${currentData.map((trail, index) => `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${trail.TrailID}</td>
                                <td>${trail.TableName}</td>
                                <td>${trail.RecordID}</td>
                                <td>${trail.UserID}</td>
                                <td><pre>${trail.OldData}</pre></td>
                                <td><pre>${trail.NewData}</pre></td>
                                <td>${trail.TimeStamp}</td>
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

        // Initial search for all trails when page loads
        document.addEventListener('DOMContentLoaded', () => {
            console.log('Page loaded, initiating search'); // Add debugging
            searchTrails();
        });
    </script>
</body>
</html> 