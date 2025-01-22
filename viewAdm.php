<?php
    session_start();
    include 'dbConnection.php';

    // Check if user is logged in
    if (!isset($_SESSION['AdminID'])) {
        header('Location: loginAdm.php');
        exit();
    }

    // Fetch admin IDs for the dropdown
    $adminIDs = [];
    try {
        $stmt = $connMe->prepare("SELECT AdminID FROM ADMIN WHERE AdminID != ? ORDER BY AdminID ASC");
        $stmt->bind_param("s", $_SESSION['AdminID']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $adminIDs[] = $row['AdminID'];
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
    <title>View Admins</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/fontawesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/solid.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
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
            .search-criteria input[type="text"],
            .search-criteria input[type="radio"] {
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

            .result-container {
                border: 1px solid #ddd;
                padding: 15px;
                border-radius: 5px;
                gap: 20px;
                min-width: 1200px;
                overflow-x: auto;
                white-space: nowrap;
            }

            .passenger-info {
                flex: 0 1 calc(50% - 20px);
                min-width: 250px;
                margin: 0;
                border: 1px solid #ddd;
                padding: 15px;
                border-radius: 8px;
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .passenger-info img {
                width: 100px;
                height: 100px;
                border-radius: 50%;
                margin-bottom: 25px;
                object-fit: cover;
                border: 3px solid #4caf50;
            }

            .passenger-info .details {
                width: 100%;
                text-align: left;
            }

            .passenger-info .button-group {
                display: flex;
                gap: 10px;
                margin-top: 10px;
                justify-content: center;
                width: 100%;
            }

            .passenger-info .button {
                display: flex;
                align-items: center;
                gap: 5px;
                padding: 8px 15px;
            }

            .passenger-info .button i {
                font-size: 14px;
            }

            .no-results {
                color: red;
                font-size: 18px;
                text-align: left;
                margin-left: 15px;
            }

            .radio-group {
                display: flex;
                flex-direction: column;
                margin-bottom: 10px;
                align-items: flex-start;
            }

            .radio-group label {
                display: flex;
                align-items: flex-start;
                margin: 5px 0;
            }

            .radio-group input[type="radio"] {
                margin-right: 10px;
                cursor: pointer;
                width: auto;
            }

            .passenger-info:last-child:nth-child(odd) {
                margin-right: auto;
            }

            .detail-row {
                display: flex; /*flex is used to align the items in a row*/
                margin-bottom: 5px;
            }

            .detail-label {
                width: 120px; /* Adjust this width based on your longest label */
                flex-shrink: 0; /*flex-shrink is used to shrink the width of the label if the content is too long*/
            }

            .detail-value {
                /*flex-grow:1 means the value will grow to fill the remaining space*/
                flex-grow: 1;
            }

            #psgrDetails {
                width: 100%;
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
            }

            .nav-dropdown {
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

            .nav-dropdown:hover .dropdown-content {
                display: block;
            }

            .dropdown-content a {
                color: white;
                padding: 12px 16px;
                text-decoration: none;
                display: block;
                transition: background-color 0.3s;
            }

            .dropdown-content a:hover {
                background-color: #388e3c;
                border-radius: 5px;
            }

            .nav-center {
                display: flex;
                gap: 15px;
            }

            .result-header {
                min-width: 1200px;
                display: flex;
                align-items: center;
                gap: 20px;
                margin-bottom: 10px;
                padding: 10px;
                background-color: #f8f9fa;
                border-radius: 5px;
            }

            .result-header #totalUsers strong,
            .header-label strong {
                color: #000;
            }

            .header-label {
                display: flex;
                align-items: center;
                gap: 5px;
            }

            .fas.fa-sort {
                cursor: pointer;
                color: #000;
            }

            #totalUsers {
                font-size: 16px;
            }

            .generate-report {
                background-color: #4caf50;
                color: white;
                border: none;
                padding: 8px 15px;
                border-radius: 5px;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 5px;
            }

            .generate-report:hover {
                background-color: #388e3c;
            }

            /* Table styles */
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
                border: 1px solid #ddd;
                table-layout: fixed;
                min-width: 1200px;
            }

            table th,
            table td {
                padding: 12px 15px;
                text-align: left;
                border-right: 1px solid #ddd;
                border-bottom: 1px solid #ddd;
                overflow: hidden;
                white-space: normal;
                word-break: normal;
                word-wrap: break-word;
                vertical-align: middle;
            }

            table th {
                background-color: #4caf50;
                color: white;
                font-weight: bold;
                border-bottom: 2px solid #388e3c;
                white-space: normal;
                word-wrap: break-word;
                line-height: 1.2;
                min-height: 50px;
                word-break: keep-all;
                hyphens: none;
            }

            /* Specific handling for column headers */
            th:nth-child(1) { width: 50px; white-space: nowrap; } /* No */
            th:nth-child(2) { width: 100px; white-space: nowrap; } /* Admin ID */
            th:nth-child(3) { width: 100px; white-space: nowrap; } /* User ID */
            th:nth-child(4) { width: 120px; white-space: nowrap; } /* Username */
            th:nth-child(5) { width: 150px; white-space: nowrap; } /* Full Name */
            th:nth-child(6) { width: 80px; white-space: nowrap; } /* Status */
            th:nth-child(7) { width: 110px; white-space: nowrap; } /* Department */
            th:nth-child(8) { width: 120px; white-space: nowrap; } /* Position */
            th:nth-child(9) { width: 200px; white-space: nowrap; } /* Email Address */
            th:nth-child(10) { width: 120px; white-space: nowrap; } /* Phone No */
            th:nth-child(11) { width: 80px; white-space: nowrap; } /* Gender */
            th:nth-child(12) { width: 100px; white-space: nowrap; } /* Birth Date */
            th:nth-child(13) { width: 70px; white-space: nowrap; } /* Action */

            /* Table row hover effect */
            table tr:nth-child(even) {
                background-color: #f9f9f9;
            }

            table tr:hover {
                background-color: #f5f5f5;
            }

            /* Table cell content */
            td {
                overflow: hidden;
                padding: 12px 8px;
                line-height: 1.4;
                font-size: 14px;
                word-break: normal;
                word-wrap: break-word;
                hyphens: auto;
            }

            /* Email address special handling */
            td:nth-child(9) {
                word-break: break-word;
                word-wrap: break-word;
            }

            .result-container {
                overflow-x: auto;
                padding-bottom: 15px;
            }

            .table-wrapper {
                overflow-x: auto;
                margin-top: 10px;
                min-width: 100%;
            }

            /* Action button in table */
            table .button {
                padding: 6px 12px;
                font-size: 13px;
                margin: 0;
            }

            table .button i {
                margin-right: 5px;
            }

            /* Profile view styles */
            #adminDetails {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
                width: 100%;
                margin-top: 15px;
            }

            .passenger-info {
                width: 100%;
                margin: 0;
                box-sizing: border-box;
            }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-center">
            <a href="homePageAdm.php" class="nav-item">
                <i class="fas fa-home"></i>
                Home
            </a>
            <div class="nav-dropdown">
                <a href="userMgmt.php" class="nav-item">
                    <i class="fas fa-users"></i>
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
        <!-- Left Section (Search Criteria) -->
        <div class="left-section">
            <h2 class="section-title">Search Criteria</h2>

            <!-- Select Search Criteria -->
            <div class="search-criteria">
                <label>Select Criteria:</label>
                <select id="searchCriteria" onchange="showSearchField()">
                    <option value="">Select...</option>
                    <option value="all">All</option>
                    <option value="adminID">Admin ID</option>
                    <option value="username">Username</option>
                    <option value="department">Department</option>
                    <option value="gender">Gender</option>
                    <option value="fullName">Full Name</option>
                    <option value="status">Status</option>
                </select>
            </div>

            <!-- Admin ID - Initially hidden -->
            <div id="adminIDField" class="search-criteria" style="display: none;">
                <label>Admin ID:</label>
                <select id="adminIDSelect">
                    <?php foreach ($adminIDs as $adminID): ?>
                        <option value="<?php echo $adminID; ?>"><?php echo $adminID; ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="button" onclick="searchByAdminID(document.getElementById('adminIDSelect').value)">Search</button>
            </div>

            <!-- Username - Initially hidden -->
            <div id="usernameField" class="search-criteria" style="display: none;">
                <label>Username:</label>
                <input type="text" id="usernameInput">
                <button class="button" onclick="searchByUsername()">Search</button>
            </div>

            <!-- Department - Initially hidden -->
            <div id="departmentField" class="search-criteria" style="display: none;">
                <label>Department:</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="department" value="HEPA"> HEPA
                    </label>
                    <label>
                        <input type="radio" name="department" value="SPKU"> SPKU
                    </label>
                </div>
                <button class="button" onclick="searchByDepartment()">Search</button>
            </div>

            <!-- Gender - Initially hidden -->
            <div id="genderField" class="search-criteria" style="display: none;">
                <label>Gender:</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="gender" value="Male"> Male
                    </label>
                    <label>
                        <input type="radio" name="gender" value="Female"> Female
                    </label>
                </div>
                <button class="button" onclick="searchByGender()">Search</button>
            </div>

            <!-- Full Name - Initially hidden -->
            <div id="fullNameField" class="search-criteria" style="display: none;">
                <label>Full Name:</label>
                <input type="text" id="fullNameInput">
                <button class="button" onclick="searchByFullName()">Search</button>
            </div>

            <!-- Status - Initially hidden -->
            <div id="statusField" class="search-criteria" style="display: none;">
                <label>Status:</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="status" value="ACTIVE"> Active
                    </label>
                    <label>
                        <input type="radio" name="status" value="DEACTIVATED"> Deactivated
                    </label>
                </div>
                <button class="button" onclick="searchByStatus()">Search</button>
            </div>

            <!-- All - Initially hidden -->
            <div id="allField" class="search-criteria" style="display: none;">
                <button class="button" onclick="searchAll()">Search</button>
            </div>
        </div>

        <!-- Right Section (Results) -->
        <div class="right-section">
            <h2 class="section-title">Admin Matching Result</h2>
            <div class="result-header">
                <div id="totalUsers">
                    <strong>Total matching users: 0</strong>
                </div>
                <button class="generate-report" onclick="generatePDF()">
                    <i class="fas fa-file-pdf"></i> Generate Report
                </button>
            </div>
            <div id="resultContainer" class="result-container">
                <div id="noResults" class="no-results" style="display: none;">Results not found</div>
                <!-- Results will be displayed here -->
            </div>
        </div>
    </div>

    <script>
        let originalAdminData = [];
        let currentView = 'profile';
        let currentSortField = '';
        let currentSortOrder = 'asc';
        let currentAdminID = '<?php echo $_SESSION['AdminID']; ?>';

        // Function to fetch admins based on search criteria
        function fetchAdmins(criteria, value = '') {
            const data = { criteria };
            
            switch (criteria) {
                case 'all':
                    data.excludeAdminID = currentAdminID;
                    break;
                case 'adminID':
                    if (!value) {
                        alert('Please select an Admin ID');
                        return;
                    }
                    data.adminID = value;
                    break;
                case 'username':
                    data.username = document.getElementById('usernameInput').value;
                    break;
                case 'department':
                    const selectedDept = document.querySelector('input[name="department"]:checked');
                    if (!selectedDept) {
                        alert('Please select a department');
                        return;
                    }
                    data.department = selectedDept.value;
                    break;
                case 'gender':
                    const selectedGender = document.querySelector('input[name="gender"]:checked');
                    if (!selectedGender) {
                        alert('Please select a gender');
                        return;
                    }
                    data.gender = selectedGender.value;
                    break;
                case 'fullName':
                    data.fullName = document.getElementById('fullNameInput').value;
                    break;
                case 'status':
                    const selectedStatus = document.querySelector('input[name="status"]:checked');
                    if (!selectedStatus) {
                        alert('Please select a status');
                        return;
                    }
                    data.status = selectedStatus.value;
                    break;
            }

            fetch('fetchAdms.php', {
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
                const resultContainer = document.getElementById('resultContainer');
                const noResults = document.getElementById('noResults');
                const generateReportBtn = document.querySelector('.generate-report');
                
                // Clear previous results
                resultContainer.innerHTML = '';
                // Add back the no results div
                resultContainer.appendChild(noResults);
                
                if (!data || data.message || !Array.isArray(data) || data.length === 0) {
                    document.getElementById('totalUsers').innerHTML = '<strong>Total matching users: 0</strong>';
                    noResults.style.display = 'block';
                    generateReportBtn.style.display = 'none';
                    return;
                }
                
                noResults.style.display = 'none';
                generateReportBtn.style.display = 'flex';
                originalAdminData = data;
                originalAdminData = originalAdminData.filter(admin => admin.AdminID !== currentAdminID);
                renderAdmins(originalAdminData);
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('totalUsers').innerHTML = '<strong>Total matching users: 0</strong>';
                document.getElementById('noResults').style.display = 'block';
                document.querySelector('.generate-report').style.display = 'none';
            });
        }

        // Search functions
        function searchByAdminID(value) {
            if (!value) return;
            fetchAdmins('adminID', value);
        }

        function searchByUsername() {
            const username = document.getElementById('usernameInput').value;
            if (!username) {
                alert('Please enter a username');
                return;
            }
            fetchAdmins('username');
        }

        function searchByDepartment() {
            fetchAdmins('department');
        }

        function searchByGender() {
            fetchAdmins('gender');
        }

        function searchByFullName() {
            const fullName = document.getElementById('fullNameInput').value;
            if (!fullName) {
                alert('Please enter a full name');
                return;
            }
            fetchAdmins('fullName');
        }

        function searchByStatus() {
            fetchAdmins('status');
        }

        // Render functions
        function renderAdmins(admins) {
            const container = document.getElementById('resultContainer');
            const noResults = document.getElementById('noResults');
            document.getElementById('totalUsers').innerHTML = `<strong>Total matching users: ${admins.length}</strong>`;

            // Clear previous results but keep the noResults div
            container.innerHTML = '';
            container.appendChild(noResults);
            noResults.style.display = 'none';

            if (currentView === 'profile') {
                renderProfileView(admins);
            } else {
                renderTableView(admins);
            }
        }

        function renderProfileView(admins) {
            const container = document.getElementById('resultContainer');
            const noResults = document.getElementById('noResults');
            
            // Clear previous results but keep the noResults div
            container.innerHTML = '';
            container.appendChild(noResults);
            
            container.innerHTML += `
                <div style="width: 100%; margin-bottom: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div class="header-label">
                            <strong>Sort by:</strong>
                            <span onclick="sortAdmins('AdminID')" style="cursor: pointer;">Admin ID <i class="fas fa-sort"></i></span> |
                            <span onclick="sortAdmins('UserID')" style="cursor: pointer;">User ID <i class="fas fa-sort"></i></span> |
                            <span onclick="sortAdmins('FullName')" style="cursor: pointer;">Full Name <i class="fas fa-sort"></i></span> |
                            <span onclick="sortAdmins('Position')" style="cursor: pointer;">Position <i class="fas fa-sort"></i></span>
                        </div>
                        <button class="button" onclick="toggleView()">
                            <i class="fas fa-table"></i> Table View
                        </button>
                    </div>
                </div>
                <div id="adminDetails">
                    ${admins.map((admin, index) => `
                        <div class="passenger-info">
                            <img src="${admin.ProfilePicture ? 'data:image/jpeg;base64,' + admin.ProfilePicture : 'https://img.freepik.com/premium-vector/green-circle-with-white-person-inside-icon_1076610-14570.jpg'}" alt="Profile Picture">
                            <div class="details">
                                <div class="detail-row">
                                    <span class="detail-label"><strong>Admin ID:</strong></span>
                                    <span class="detail-value">${admin.AdminID}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label"><strong>User ID:</strong></span>
                                    <span class="detail-value">${admin.UserID}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label"><strong>Full Name:</strong></span>
                                    <span class="detail-value">${admin.FullName}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label"><strong>Username:</strong></span>
                                    <span class="detail-value">${admin.Username}</span>
                                </div>
                                <div class="button-group">
                                    <button class="button" onclick="window.location.href='viewAccAdm.php?adminID=${admin.AdminID}'">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        function renderTableView(admins) {
            const container = document.getElementById('resultContainer');
            const noResults = document.getElementById('noResults');
            
            // Clear previous results but keep the noResults div
            container.innerHTML = '';
            container.appendChild(noResults);
            
            let tableHTML = `
                <div style="width: 100%; margin-bottom: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div class="header-label">
                            <strong>Sort by:</strong>
                            <span onclick="sortAdmins('AdminID')" style="cursor: pointer;">Admin ID <i class="fas fa-sort"></i></span> |
                            <span onclick="sortAdmins('UserID')" style="cursor: pointer;">User ID <i class="fas fa-sort"></i></span> |
                            <span onclick="sortAdmins('FullName')" style="cursor: pointer;">Full Name <i class="fas fa-sort"></i></span> |
                            <span onclick="sortAdmins('Position')" style="cursor: pointer;">Position <i class="fas fa-sort"></i></span>
                        </div>
                        <button class="button" onclick="toggleView()">
                            <i class="fas fa-id-card"></i> Profile View
                        </button>
                    </div>
                </div>
                <div class="table-wrapper">
                    <table>
                        <tr>
                            <th title="No">No</th>
                            <th title="Admin ID">Admin ID</th>
                            <th title="User ID">User ID</th>
                            <th title="Username">Username</th>
                            <th title="Full Name">Full Name</th>
                            <th title="Status">Status</th>
                            <th title="Department">Department</th>
                            <th title="Position">Position</th>
                            <th title="Email Address">Email Address</th>
                            <th title="Phone No">Phone No</th>
                            <th title="Gender">Gender</th>
                            <th title="Birth Date">Birth Date</th>
                        </tr>
            `;

            admins.forEach((admin, index) => {
                const birthDate = admin.BirthDate ? new Date(admin.BirthDate).toLocaleDateString('en-GB') : '-';
                tableHTML += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${admin.AdminID}</td>
                        <td>${admin.UserID}</td>
                        <td>${admin.Username}</td>
                        <td>${admin.FullName}</td>
                        <td>${admin.Status}</td>
                        <td>${admin.Department}</td>
                        <td>${admin.Position}</td>
                        <td>${admin.EmailAddress || '-'}</td>
                        <td>${admin.PhoneNo || '-'}</td>
                        <td>${admin.Gender === 'M' ? 'M' : (admin.Gender === 'F' ? 'F' : '-')}</td>
                        <td>${birthDate}</td>
                        <td>
                            <a href="viewAccAdm.php?adminID=${admin.AdminID}">View</a>
                        </td>
                    </tr>
                `;
            });

            tableHTML += `
                    </table>
                </div>
            `;
            container.innerHTML += tableHTML;
        }

        // Toggle view function
        function toggleView() {
            currentView = currentView === 'profile' ? 'table' : 'profile';
            renderAdmins(originalAdminData);
        }

        // Sorting function
        function sortAdmins(field) {
            if (currentSortField === field) {
                currentSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                currentSortField = field;
                currentSortOrder = 'asc';
            }

            const sortedData = [...originalAdminData].sort((a, b) => {
                let valueA = a[field] || '';
                let valueB = b[field] || '';

                if (typeof valueA === 'string') valueA = valueA.toUpperCase();
                if (typeof valueB === 'string') valueB = valueB.toUpperCase();

                if (valueA < valueB) return currentSortOrder === 'asc' ? -1 : 1;
                if (valueA > valueB) return currentSortOrder === 'asc' ? 1 : -1;
                return 0;
            });

            // Update the original data to maintain sort order
            originalAdminData = sortedData;
            
            renderAdmins(sortedData);
        }

        // Generate PDF function
        function generatePDF() {
            const printWindow = window.open('', '_blank');
            
            printWindow.document.write(`
                <html>
                <head>
                    <title>Admin Report</title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        table { 
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 20px;
                        }
                        th, td { 
                            border: 1px solid #ddd;
                            padding: 8px;
                            text-align: left;
                            font-size: 12px;
                        }
                        th { 
                            background-color: #4caf50;
                            color: white;
                        }
                        .report-header {
                            margin-bottom: 20px;
                        }
                        @media print {
                            th { 
                                background-color: #4caf50 !important;
                                color: white !important;
                                -webkit-print-color-adjust: exact;
                            }
                            @page {
                                size: landscape;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="report-header">
                        <h2>Admin Report</h2>
                        <p>Generated on: ${new Date().toLocaleString()}</p>
                        <p>Total Admins: ${originalAdminData.length}</p>
                    </div>
                    <table>
                        <tr>
                            <th>No</th>
                            <th>Admin ID</th>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Status</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Email Address</th>
                            <th>Phone No</th>
                            <th>Gender</th>
                            <th>Birth Date</th>
                        </tr>
                        ${[...originalAdminData].map((admin, index) => `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${admin.AdminID}</td>
                                <td>${admin.UserID}</td>
                                <td>${admin.Username}</td>
                                <td>${admin.FullName}</td>
                                <td>${admin.Status}</td>
                                <td>${admin.Department}</td>
                                <td>${admin.Position}</td>
                                <td>${admin.EmailAddress || '-'}</td>
                                <td>${admin.PhoneNo || '-'}</td>
                                <td>${admin.Gender === 'M' ? 'M' : (admin.Gender === 'F' ? 'F' : '-')}</td>
                                <td>${new Date(admin.BirthDate).toLocaleDateString('en-GB')}</td>
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

        // Function to show/hide search fields based on selected criteria
        function showSearchField() {
            // Hide all fields first
            document.getElementById('allField').style.display = 'none';
            document.getElementById('adminIDField').style.display = 'none';
            document.getElementById('usernameField').style.display = 'none';
            document.getElementById('departmentField').style.display = 'none';
            document.getElementById('genderField').style.display = 'none';
            document.getElementById('fullNameField').style.display = 'none';
            document.getElementById('statusField').style.display = 'none';

            // Get selected criteria
            const selectedCriteria = document.getElementById('searchCriteria').value;
            
            // Show the corresponding field
            if (selectedCriteria) {
                document.getElementById(`${selectedCriteria}Field`).style.display = 'block';
            }
        }

        // Function to search all admins
        function searchAll() {
            fetchAdmins('all');
        }

        // Load all admins when page loads (excluding current admin)
        window.onload = function() {
            searchAll();
        };
    </script>
</body>
</html> 