<?php
    session_start();
    include 'dbConnection.php';

    // Check if user is logged in
    if (!isset($_SESSION['AdminID'])) {
        header('Location: loginAdm.php');
        exit();
    }

    // Fetch passenger IDs for the dropdown
    $psgrIDs = [];
    try {
        $stmt = $connMe->prepare("SELECT PsgrID FROM PASSENGER ORDER BY PsgrID ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $psgrIDs[] = $row['PsgrID']; // Ensure this matches your column name
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
        <title>View Passengers</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/fontawesome.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/solid.min.css">
        <!--Include library to create a PDF document.-->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <!--Add structured tables (e.g., with rows and columns) to the PDF.-->
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
                width: 100%;
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
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
                width: 100%;
                overflow-x: auto;
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
                text-align: center;
            }

            .radio-group {
                display: flex;
                flex-direction: column; /* Stack items vertically */
                margin-bottom: 10px; /* Space between groups */
                align-items: flex-start; /* Align radio buttons to the left */
            }

            .radio-group label {
                display: flex; /* Use flexbox for alignment */
                align-items: flex-start; /* Align radio button at the top of the label */
                margin: 5px 0; /* Space between each radio button */
            }

            .radio-group input[type="radio"] {
                margin-right: 10px; /* Space between radio button and label */
                cursor: pointer; /* Change cursor to pointer for better usability */
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
                width: 100%;
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

            table {
                width: 100%;
                min-width: max-content;
                border-collapse: collapse;
                margin-top: 10px;
            }

            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
                font-size: 14px;
                max-width: 150px; /* Limit maximum width */
                overflow-wrap: break-word; /* Allow text to wrap */
                word-wrap: break-word;
            }

            th {
                position: sticky;
                top: 0;
                background-color: #4caf50;
                z-index: 10;
            }

            /* Adjust column widths */
            th:nth-child(1), td:nth-child(1) { width: 40px; } /* No */
            th:nth-child(2), td:nth-child(2) { width: 90px; } /* Passenger ID */
            th:nth-child(3), td:nth-child(3) { width: 80px; } /* User ID */
            th:nth-child(4), td:nth-child(4) { width: 90px; } /* Username */
            th:nth-child(5), td:nth-child(5) { width: 120px; } /* Full Name */
            th:nth-child(6), td:nth-child(6) { width: 60px; } /* Status */
            th:nth-child(7), td:nth-child(7) { width: 90px; } /* Matric No */
            th:nth-child(8), td:nth-child(8) { width: 100px; } /* Role */
            th:nth-child(9), td:nth-child(9) { width: 150px; white-space: normal; } /* Email Address */
            th:nth-child(10), td:nth-child(10) { width: 120px; } /* Phone No */
            th:nth-child(11), td:nth-child(11) { width: 90px; } /* Birth Date */
            th:nth-child(12), td:nth-child(12) { width: 60px; } /* Gender */
            th:nth-child(13), td:nth-child(13) { width: 120px; white-space: normal; } /* Fav Pick Up */
            th:nth-child(14), td:nth-child(14) { width: 120px; white-space: normal; } /* Fav Drop Off */
            th:last-child, td:last-child { 
                width: 60px; /* Actions */
                text-align: center;
            }

            th {
                background-color: #4caf50; /* Green background for headers */
                color: white; /* White font color for headers */
            }

            table td .button {
                padding: 4px 8px;
                margin: 0;
                font-size: 13px;
            }

            table td {
                vertical-align: middle;
            }

            /* Adjust the actions column width */
            table th:last-child,
            table td:last-child {
                min-width: 160px;
                text-align: center;
            }

            /* Add hover effect to table rows */
            table tr:hover {
                background-color: #f5f5f5;
            }

            /* Allow text wrapping for long content */
            td {
                white-space: normal;
                word-break: break-word;
                vertical-align: middle;
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

        <!-- Main Content -->
        <div class="container">
            <!-- Left Section for Search Criteria -->
            <div class="left-section">
                <h2 class="section-title">Searching Criteria</h2>
                <div class="search-criteria">
                    <label for="criteria">Select Criteria:</label>
                    <select id="criteria" onchange="showInputFields()">
                        <option value="">Select...</option>
                        <option value="all">All</option>
                        <option value="psgrID">Passenger ID</option>
                        <option value="role">Role</option>
                        <option value="username">Username</option>
                        <option value="gender">Gender</option>
                        <option value="fullName">Full Name</option>
                        <option value="matricNo">Matric Number</option>
                        <option value="status">Status</option>
                        <option value="pickupLocation">Favourite Pick Up Location</option>
                        <option value="dropoffLocation">Favourite Drop Off Location</option>
                    </select>

                    <div id="inputFields" style="display: none;">
                        <div id="psgrIDField" style="display: none;">
                            <label for="psgrID">Passenger ID:</label>
                            <select id="psgrID">
                                <!--Loop through the array of passenger IDs and display them as options in the dropdown-->
                                <?php foreach ($psgrIDs as $id): ?>
                                    <option value="<?php echo $id; ?>"><?php echo $id; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="roleField" style="display: none;">
                            <label>Role:</label>
                            <div class="radio-group">
                                <label>
                                    <input type="radio" name="role" value="Student"> Student
                                </label>
                                <label>
                                    <input type="radio" name="role" value="Staff"> Staff
                                </label>
                                <label>
                                    <input type="radio" name="role" value="Visitor"> Visitor
                                </label>
                            </div>
                        </div>
                        
                        <div id="usernameField" style="display: none;">
                                <label for="username">Username:</label>
                                <input type="text" id="username" autocomplete="off">
                        </div>

                        <div id="genderField" style="display: none;">
                            <label>Gender:</label>
                            <div class="radio-group">
                                <label>
                                    <input type="radio" name="gender" value="Male"> Male
                                </label>
                                <label>
                                    <input type="radio" name="gender" value="Female"> Female
                                </label>
                            </div>
                        </div>

                        <div id="fullNameField" style="display: none;">
                            <label for="fullName">Full Name:</label>
                            <input type="text" id="fullName" autocomplete="off">
                        </div>

                        <div id="matricNoField" style="display: none;">
                            <label for="matricNo">Matric Number:</label>
                            <input type="text" id="matricNo" autocomplete="off">
                        </div>

                        <div id="statusField" style="display: none;">
                            <label>Status:</label>
                            <div class="radio-group">
                                <label>
                                    <input type="radio" name="status" value="Active"> Active
                                </label>
                                <label>
                                    <input type="radio" name="status" value="Deactivated"> Deactivated
                                </label>
                            </div>
                        </div>

                        <div id="pickupLocationField" style="display: none;">
                            <label for="pickupLocation">Favourite Pick Up Location:</label>
                            <input type="text" id="pickupLocation" autocomplete="off">
                        </div>

                        <div id="dropoffLocationField" style="display: none;">
                            <label for="dropoffLocation">Favourite Drop Off Location:</label>
                            <input type="text" id="dropoffLocation" autocomplete="off">
                        </div>
                    </div>

                    <button class="button" onclick="searchPassengers()">Search</button>
                </div>
            </div>

            <!-- Right Section for Passenger Results -->
            <div class="right-section">
                <h2 class="section-title">Passenger Matching Result</h2>
                <div class="result-header">
                    <div id="totalUsers">
                        <strong>Total matching users: 0</strong>
                    </div>
                    <button class="generate-report" onclick="generatePDF()">
                        <i class="fas fa-file-pdf"></i> Generate Report
                    </button>
                </div>
                <div id="resultContainer" class="result-container">
                    <div class="result-header">
                        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <div class="header-label">
                                <strong>Sort by:</strong>
                                <span onclick="toggleSortOrder('psgrID')" style="cursor: pointer;">Passenger ID <i class="fas fa-sort"></i></span> |
                                <span onclick="toggleSortOrder('userID')" style="cursor: pointer;">User ID <i class="fas fa-sort"></i></span> |
                                <span onclick="toggleSortOrder('fullName')" style="cursor: pointer;">Full Name <i class="fas fa-sort"></i></span>
                            </div>
                            <button class="button" onclick="toggleViewStyle()">
                                <i class="fas fa-table"></i> Table View
                            </button>
                        </div>
                    </div>
                    <div id="psgrDetails"></div>
                    <div id="noResults" class="no-results" style="display: none;">Results not found</div>
                </div>
            </div>
        </div>

        <script>
            let sortOrder = 'asc';
            let sortCriterion = 'fullName';
            let isTableView = false; // Default to profile view
            let originalPassengerData = []; // Add this at the top with other variables

            function toggleSortOrder(criterion) {
                console.log(`Sorting by: ${criterion}`); // Debug log
                if (sortCriterion === criterion) {
                    sortOrder = sortOrder === 'asc' ? 'desc' : 'asc';
                } else {
                    sortCriterion = criterion;
                    sortOrder = 'asc';
                }
                sortPassengers();
            }

            function toggleViewStyle() {
                isTableView = !isTableView;
                const button = document.querySelector('.button[onclick="toggleViewStyle()"]');
                if (isTableView) {
                    button.innerHTML = '<i class="fas fa-id-card"></i> Profile View';
                } else {
                    button.innerHTML = '<i class="fas fa-table"></i> Table View';
                }
                renderPassengers();
            }

            function renderPassengers() {
                const psgrDetails = document.getElementById('psgrDetails');
                psgrDetails.innerHTML = '';

                if (isTableView) {
                    // Add a wrapper div for horizontal scrolling
                    psgrDetails.innerHTML = '<div style="overflow-x: auto;">';
                    let tableHTML = `
                        <table>
                            <tr>
                                <th>No</th>
                                <th>Passenger ID</th>
                                <th>User ID</th>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Status</th>
                                <th>Matric No</th>
                                <th>Role</th>
                                <th>Email Address</th>
                                <th>Phone No</th>
                                <th>Birth Date</th>
                                <th>Gender</th>
                                <th>Fav Pick Up</th>
                                <th>Fav Drop Off</th>
                                <th>Actions</th>
                            </tr>`;
                    
                    originalPassengerData.forEach((passenger, index) => {
                        const formattedFullName = passenger.FullName
                            .toLowerCase()
                            .split(' ')
                            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                            .join(' ');
                        
                        // Format birth date
                        const birthDate = new Date(passenger.BirthDate).toLocaleDateString('en-GB');
                        
                        // Format status (ACTIVE/DEACTIVATED to A/DA)
                        const status = passenger.Status === 'ACTIVE' ? 'A' : 'DA';
                        
                        tableHTML += `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${passenger.PsgrID}</td>
                                <td>${passenger.UserID}</td>
                                <td>${passenger.Username}</td>
                                <td>${formattedFullName}</td>
                                <td>${status || '-'}</td>
                                <td>${passenger.MatricNo || '-'}</td>
                                <td>${passenger.Role || '-'}</td>
                                <td>${passenger.EmailAddress || '-'}</td>
                                <td>${passenger.PhoneNo || '-'}</td>
                                <td>${birthDate || '-'}</td>
                                <td>${passenger.Gender || '-'}</td>
                                <td>${passenger.FavPickUpLoc || '-'}</td>
                                <td>${passenger.FavDropOffLoc || '-'}</td>
                                <td>
                                    <button class="button" onclick="window.location.href='editPsgr.php?passengerID=${passenger.PsgrID}'">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                </td>
                            </tr>`;
                    });
                    tableHTML += '</table>';
                    psgrDetails.innerHTML += tableHTML + '</div>';
                } else {
                    originalPassengerData.forEach(passenger => {
                        const formattedFullName = passenger.FullName
                            .toLowerCase()
                            .split(' ')
                            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                            .join(' ');

                        psgrDetails.innerHTML += `
                            <div class="passenger-info">
                                <img src="${passenger.ProfilePicture ? 'data:image/jpeg;base64,' + passenger.ProfilePicture : 'https://img.freepik.com/premium-vector/green-circle-with-white-person-inside-icon_1076610-14570.jpg'}" alt="Profile Picture">
                                <div class="details">
                                    <div class="detail-row">
                                        <span class="detail-label"><strong>Passenger ID:</strong></span>
                                        <span class="detail-value">${passenger.PsgrID}</span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label"><strong>User ID:</strong></span>
                                        <span class="detail-value">${passenger.UserID}</span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label"><strong>Full Name:</strong></span>
                                        <span class="detail-value">${formattedFullName}</span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label"><strong>Username:</strong></span>
                                        <span class="detail-value">${passenger.Username}</span>
                                    </div>
                                    <div class="button-group">
                                        <button class="button" onclick="window.location.href='editPsgr.php?passengerID=${passenger.PsgrID}'">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                }
            }

            function sortPassengers() {
                console.log(`Sorting order: ${sortOrder}`);
                
                originalPassengerData.sort((a, b) => {
                    let valueA, valueB;

                    if (sortCriterion === 'psgrID') {
                        valueA = a.PsgrID;
                        valueB = b.PsgrID;
                    } else if (sortCriterion === 'userID') {
                        valueA = a.UserID;
                        valueB = b.UserID;
                    } else {
                        // For fullName
                        valueA = a.FullName.toUpperCase();
                        valueB = b.FullName.toUpperCase();
                    }

                    if (sortOrder === 'asc') {
                        return valueA.localeCompare(valueB);
                    } else {
                        return valueB.localeCompare(valueA);
                    }
                });

                renderPassengers();
            }

            function showInputFields() {
                const criteria = document.getElementById('criteria').value;
                const inputFields = document.getElementById('inputFields');
                inputFields.style.display = 'block';

                // Hide all input fields initially
                document.getElementById('psgrIDField').style.display = 'none';
                document.getElementById('roleField').style.display = 'none';
                document.getElementById('usernameField').style.display = 'none';
                document.getElementById('genderField').style.display = 'none';
                document.getElementById('fullNameField').style.display = 'none';
                document.getElementById('matricNoField').style.display = 'none';
                document.getElementById('statusField').style.display = 'none';
                document.getElementById('pickupLocationField').style.display = 'none';
                document.getElementById('dropoffLocationField').style.display = 'none';

                // Show the corresponding input field based on selected criteria
                if (criteria === 'psgrID') {
                    document.getElementById('psgrIDField').style.display = 'block';
                } else if (criteria === 'role') {
                    document.getElementById('roleField').style.display = 'block';
                } else if (criteria === 'username') {
                    document.getElementById('usernameField').style.display = 'block';
                } else if (criteria === 'gender') {
                    document.getElementById('genderField').style.display = 'block';
                } else if (criteria === 'fullName') {
                    document.getElementById('fullNameField').style.display = 'block';
                } else if (criteria === 'matricNo') {
                    document.getElementById('matricNoField').style.display = 'block';
                } else if (criteria === 'status') {
                    document.getElementById('statusField').style.display = 'block';
                } else if (criteria === 'pickupLocation') {
                    document.getElementById('pickupLocationField').style.display = 'block';
                } else if (criteria === 'dropoffLocation') {
                    document.getElementById('dropoffLocationField').style.display = 'block';
                }
            }

            function updateTotalUsers(count) {
                document.getElementById('totalUsers').innerHTML = `<strong>Total matching users: ${count}</strong>`;
            }

            function searchPassengers() {
                const criteria = document.getElementById('criteria').value;
                const psgrID = document.getElementById('psgrID').value;
                const username = document.getElementById('username').value;
                const fullName = document.getElementById('fullName').value;
                const matricNo = document.getElementById('matricNo').value;
                const favPickUpLoc = document.getElementById('pickupLocation').value;
                const favDropOffLoc = document.getElementById('dropoffLocation').value;
                const role = document.querySelector('input[name="role"]:checked') ? document.querySelector('input[name="role"]:checked').value : '';
                const gender = document.querySelector('input[name="gender"]:checked') ? document.querySelector('input[name="gender"]:checked').value : '';
                
                const status = document.querySelector('input[name="status"]:checked') ? document.querySelector('input[name="status"]:checked').value.toUpperCase() : '';
                
                // Log all data being sent
                console.log('Sending search data:', {
                    criteria: criteria,
                    psgrID: psgrID,
                    username: username,
                    fullName: fullName,
                    matricNo: matricNo,
                    favPickUpLoc: favPickUpLoc,
                    favDropOffLoc: favDropOffLoc,
                    role: role,
                    gender: gender,
                    status: status
                });

                const data = {
                    criteria: criteria,
                    psgrID: psgrID,
                    username: username,
                    fullName: fullName,
                    matricNo: matricNo,
                    favPickUpLoc: favPickUpLoc,
                    favDropOffLoc: favDropOffLoc,
                    role: role,
                    gender: gender,
                    status: status
                };

                // If "All" is selected, call loadAllPassengers directly
                if (criteria === 'all') {
                    loadAllPassengers();
                    return;
                }

                fetch('fetchPsgrs.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    return response.text();
                })
                .then(text => {
                    console.log('Raw response text:', text); // Log the raw response
                    if (!text) {
                        throw new Error('Empty response received');
                    }
                    try {
                        const results = JSON.parse(text);
                        console.log('Parsed results:', results);
                        return results;
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        throw e;
                    }
                })
                .then(results => {
                    const psgrDetails = document.getElementById('psgrDetails');
                    const noResults = document.getElementById('noResults');
                    const generateReportBtn = document.querySelector('.generate-report');
                    psgrDetails.innerHTML = '';
                    noResults.style.display = 'none';

                    if (!results || results.error || !Array.isArray(results) || results.length === 0) {
                        noResults.style.display = 'block';
                        updateTotalUsers(0);
                        generateReportBtn.style.display = 'none';
                    } else {
                        originalPassengerData = results;
                        updateTotalUsers(results.length);
                        generateReportBtn.style.display = 'flex';
                        renderPassengers();
                    }
                })
                .catch(error => {
                    console.error('Full error details:', error);
                    document.getElementById('noResults').style.display = 'block';
                    document.querySelector('.generate-report').style.display = 'none';
                });
            }

            function deletePassenger(psgrID, userID) {
                if (confirm('Are you sure you want to delete this passenger account? This action cannot be undone.')) {
                    fetch('deleteAccountPsgrByAdm.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            psgrId: psgrID,
                            userId: userID
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert(data.message);
                            location.reload(); // Refresh the page to show updated list
                        } else {
                            alert('Failed to delete account: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while deleting the account');
                    });
                }
            }

            // Add this function to load all passengers when the page loads
            function loadAllPassengers() {
                fetch('fetchPsgrs.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ criteria: 'all' })
                })
                .then(response => response.text())
                .then(text => {
                    try {
                        const results = JSON.parse(text);
                        const psgrDetails = document.getElementById('psgrDetails');
                        const noResults = document.getElementById('noResults');
                        psgrDetails.innerHTML = '';
                        noResults.style.display = 'none';

                        if (!results || results.error || !Array.isArray(results) || results.length === 0) {
                            noResults.style.display = 'block';
                            updateTotalUsers(0);
                        } else {
                            originalPassengerData = results;
                            updateTotalUsers(results.length);
                            renderPassengers();
                        }
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        document.getElementById('noResults').style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('noResults').style.display = 'block';
                });
            }

            // Call the function when the page loads
            window.onload = loadAllPassengers;

            function generatePDF() {
                // Create a new window for printing
                const printWindow = window.open('', '_blank');
                
                // Add print-specific styles
                printWindow.document.write(`
                    <html>
                    <head>
                        <title>Passenger Report</title>
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
                            <h2>Passenger Report</h2>
                            <p>Generated on: ${new Date().toLocaleString()}</p>
                            <p>Total Passengers: ${originalPassengerData.length}</p>
                        </div>
                        <table>
                            <tr>
                                <th>No</th>
                                <th>PsgrID</th>
                                <th>UserID</th>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Status</th>
                                <th>Matric No</th>
                                <th>Role</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Birth Date</th>
                                <th>Gender</th>
                                <th>Fav Pick Up</th>
                                <th>Fav Drop Off</th>
                            </tr>
                            ${originalPassengerData.map((passenger, index) => `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${passenger.PsgrID}</td>
                                    <td>${passenger.UserID}</td>
                                    <td>${passenger.Username}</td>
                                    <td>${passenger.FullName}</td>
                                    <td>${passenger.Status === 'ACTIVE' ? 'A' : 'DA'}</td>
                                    <td>${passenger.MatricNo || '-'}</td>
                                    <td>${passenger.Role || '-'}</td>
                                    <td>${passenger.EmailAddress || '-'}</td>
                                    <td>${passenger.PhoneNo || '-'}</td>
                                    <td>${new Date(passenger.BirthDate).toLocaleDateString('en-GB')}</td>
                                    <td>${passenger.Gender || '-'}</td>
                                    <td>${passenger.FavPickUpLoc || '-'}</td>
                                    <td>${passenger.FavDropOffLoc || '-'}</td>
                                </tr>
                            `).join('')}
                        </table>
                    </body>
                    </html>
                `);
                
                // Wait for content to load then print
                printWindow.document.close();
                printWindow.onload = function() {
                    printWindow.print();
                };
            }
        </script>
    </body>
    </html> 