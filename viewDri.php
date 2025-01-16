<?php
    session_start();
    include 'dbConnection.php';

    // Check if user is logged in
    if (!isset($_SESSION['AdminID'])) {
        header('Location: loginAdm.php');
        exit();
    }

    // Fetch driver IDs for the dropdown
    $driverIDs = [];
    try {
        $stmt = $connMe->prepare("SELECT DriverID FROM DRIVER ORDER BY DriverID ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $driverIDs[] = $row['DriverID'];
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
    <title>View Drivers</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/fontawesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/solid.min.css">
    <!-- Same CSS as viewPsgr.php -->
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
            padding: 20px;
            background-color: #fff3cd;
        }

        .right-section {
            flex: 2;
            padding: 20px;
            background-color: white;
            overflow-y: auto;
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
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
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
        }

        .driver-info {
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

        .driver-info img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-bottom: 25px;
            object-fit: cover;
            border: 3px solid #4caf50;
        }

        .driver-info .details {
            width: 100%;
            text-align: left;
        }

        .driver-info .button-group {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            justify-content: center;
            width: 100%;
        }

        .driver-info .button {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 15px;
        }

        .driver-info .button i {
            font-size: 14px;
        }

        .no-results {
            color: red;
            font-size: 18px;
            text-align: center;
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

        .driver-info:last-child:nth-child(odd) {
            margin-right: auto;
        }

        .detail-row {
            display: flex;
            margin-bottom: 5px;
        }

        .detail-label {
            width: 120px;
            flex-shrink: 0;
        }

        .detail-value {
            flex-grow: 1;
        }

        #driverDetails {
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

        .dropdown-content:hover,
        .nav-dropdown:hover .dropdown-content {
            display: block;
        }

        .nav-dropdown > a:focus + .dropdown-content {
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
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .header-label {
            margin-right: 5px;
        }

        .fas.fa-sort {
            cursor: pointer;
            color: #000;
        }

        .fas.fa-table {
            cursor: pointer;
            color: #000;
            margin-left: auto;
        }

        /* Add these table styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #4caf50;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #ddd;
        }

        table td .button {
            padding: 5px 10px;
            margin: 2px;
            font-size: 14px;
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
                    <option value="driverID">Driver ID</option>
                    <option value="username">Username</option>
                    <option value="gender">Gender</option>
                    <option value="fullName">Full Name</option>
                    <option value="stickerExpiry">Sticker Expiry Date</option>
                    <option value="availability">Availability</option>
                </select>

                <div id="inputFields" style="display: none;">
                    <div id="driverIDField" style="display: none;">
                        <label for="driverID">Driver ID:</label>
                        <select id="driverID">
                            <?php foreach ($driverIDs as $id): ?>
                                <option value="<?php echo $id; ?>"><?php echo $id; ?></option>
                            <?php endforeach; ?>
                        </select>
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

                    <div id="availabilityField" style="display: none;">
                        <label>Availability:</label>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="availability" value="Available"> Available
                            </label>
                            <label>
                                <input type="radio" name="availability" value="Not Available"> Not Available
                            </label>
                        </div>
                    </div>

                    <div id="stickerExpiryField" style="display: none;">
                        <label>Sticker Expiry Date Range:</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <div>
                                <label for="startDate" style="font-size: 14px;">From:</label>
                                <input type="date" id="startDate" onchange="validateDateRange()" style="font-size: 14px; padding: 4px;">
                            </div>
                            <div>
                                <label for="endDate" style="font-size: 14px;">To:</label>
                                <input type="date" id="endDate" onchange="validateDateRange()" style="font-size: 14px; padding: 4px;">
                            </div>
                        </div>
                        <span id="dateError" style="color: red; font-size: 12px;"></span>
                    </div>
                </div>

                <button class="button" onclick="searchDrivers()">Search</button>
            </div>
        </div>

        <!-- Right Section for Driver Results -->
        <div class="right-section">
            <h2 class="section-title">Driver Matching Result</h2>
            <div id="totalUsers" style="font-size: 16px; color: #4caf50; margin-bottom: 15px;">
                <strong>Total matching users: 0</strong>
            </div>
            <div id="resultContainer" class="result-container">
                <div class="result-header">
                    <span class="header-label"><strong>Driver ID</strong></span>
                    <i class="fas fa-sort" onclick="toggleSortOrder('driverID')"></i>
                    <span class="header-label"><strong>User ID</strong></span>
                    <i class="fas fa-sort" onclick="toggleSortOrder('userID')"></i>
                    <span class="header-label"><strong>Full Name</strong></span>
                    <i class="fas fa-sort" onclick="toggleSortOrder('fullName')"></i>
                    <span class="header-label"><strong>Sticker Expiry</strong></span>
                    <i class="fas fa-sort" onclick="toggleSortOrder('stickerExpiry')"></i>
                    <i class="fas fa-table" onclick="toggleViewStyle()" title="Toggle Table View"></i>
                </div>
                <div id="driverDetails"></div>
                <div id="noResults" class="no-results" style="display: none;">Results not found</div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadAllDrivers();
        });

        function showInputFields() {
            const criteria = document.getElementById('criteria').value;
            const inputFields = document.getElementById('inputFields');
            inputFields.style.display = 'block';

            // Hide all input fields initially
            document.getElementById('driverIDField').style.display = 'none';
            document.getElementById('usernameField').style.display = 'none';
            document.getElementById('genderField').style.display = 'none';
            document.getElementById('fullNameField').style.display = 'none';
            document.getElementById('availabilityField').style.display = 'none';
            document.getElementById('stickerExpiryField').style.display = 'none';

            // Show the corresponding input field based on selected criteria
            if (criteria === 'driverID') {
                document.getElementById('driverIDField').style.display = 'block';
            } else if (criteria === 'username') {
                document.getElementById('usernameField').style.display = 'block';
            } else if (criteria === 'gender') {
                document.getElementById('genderField').style.display = 'block';
            } else if (criteria === 'fullName') {
                document.getElementById('fullNameField').style.display = 'block';
            } else if (criteria === 'availability') {
                document.getElementById('availabilityField').style.display = 'block';
            } else if (criteria === 'stickerExpiry') {
                document.getElementById('stickerExpiryField').style.display = 'block';
            }
        }

        function searchDrivers() {
            const criteria = document.getElementById('criteria').value;
            const driverID = document.getElementById('driverID').value;
            const username = document.getElementById('username').value;
            const fullName = document.getElementById('fullName').value;
            const gender = document.querySelector('input[name="gender"]:checked') ? 
                          document.querySelector('input[name="gender"]:checked').value : '';
            const availability = document.querySelector('input[name="availability"]:checked') ? 
                                document.querySelector('input[name="availability"]:checked').value : '';

            // If "All" is selected, call loadAllDrivers directly
            if (criteria === 'all') {
                loadAllDrivers();
                return;
            }

            let data = {
                criteria: criteria,
                driverID: driverID,
                username: username,
                fullName: fullName,
                gender: gender,
                availability: availability
            };

            // Handle sticker expiry date range
            if (criteria === 'stickerExpiry' || criteria === 'stickerExpDate') {
                const startDate = document.getElementById('startDate').value;
                const endDate = document.getElementById('endDate').value;
                
                if (!validateDateRange()) {
                    return;
                }

                data.startDate = startDate;
                data.endDate = endDate;
            }

            fetch('fetchDris.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.text())
            .then(text => {
                try {
                    const results = JSON.parse(text);
                    const driverDetails = document.getElementById('driverDetails');
                    const noResults = document.getElementById('noResults');
                    driverDetails.innerHTML = '';
                    noResults.style.display = 'none';

                    if (!results || results.error || !Array.isArray(results) || results.length === 0) {
                        noResults.style.display = 'block';
                        updateTotalUsers(0);
                    } else {
                        originalDriverData = results;
                        updateTotalUsers(results.length);
                        renderDrivers();
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

        function deleteDriver(driverID, userID) {
            if (confirm('Are you sure you want to delete this driver account? This action cannot be undone.')) {
                fetch('deleteAccountDriByAdm.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        driverId: driverID,
                        userId: userID
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                        location.reload();
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

        function loadAllDrivers() {
            fetch('fetchDris.php', {
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
                    const driverDetails = document.getElementById('driverDetails');
                    const noResults = document.getElementById('noResults');
                    driverDetails.innerHTML = '';
                    noResults.style.display = 'none';

                    if (!results || results.error || !Array.isArray(results) || results.length === 0) {
                        noResults.style.display = 'block';
                        updateTotalUsers(0);
                    } else {
                        originalDriverData = results;
                        updateTotalUsers(results.length);
                        renderDrivers();
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

        function validateDateRange() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const dateError = document.getElementById('dateError');
            const searchButton = document.querySelector('.button');

            if (startDate && endDate) {
                if (new Date(endDate) < new Date(startDate)) {
                    dateError.textContent = 'End date must be after start date';
                    searchButton.disabled = true;
                    return false;
                } else {
                    dateError.textContent = '';
                    searchButton.disabled = false;
                    // Format dates to match MySQL format (YYYY-MM-DD)
                    document.getElementById('startDate').value = new Date(startDate).toISOString().split('T')[0];
                    document.getElementById('endDate').value = new Date(endDate).toISOString().split('T')[0];
                    return true;
                }
            }
            return true;
        }

        let sortOrder = 'asc';
        let sortCriterion = 'fullName';
        let isTableView = false;
        let originalDriverData = [];

        function toggleSortOrder(criterion) {
            if (sortCriterion === criterion) {
                sortOrder = sortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                sortCriterion = criterion;
                sortOrder = 'asc';
            }
            sortDrivers();
        }

        function toggleViewStyle() {
            isTableView = !isTableView;
            renderDrivers();
        }

        function sortDrivers() {
            originalDriverData.sort((a, b) => {
                let valueA, valueB;

                if (sortCriterion === 'driverID') {
                    valueA = a.DriverID;
                    valueB = b.DriverID;
                } else if (sortCriterion === 'userID') {
                    valueA = a.UserID;
                    valueB = b.UserID;
                } else if (sortCriterion === 'stickerExpiry') {
                    valueA = new Date(a.StickerExpDate);
                    valueB = new Date(b.StickerExpDate);
                    return sortOrder === 'asc' ? valueA - valueB : valueB - valueA;
                } else {
                    valueA = a.FullName.toUpperCase();
                    valueB = b.FullName.toUpperCase();
                }

                if (sortOrder === 'asc') {
                    return valueA.localeCompare(valueB);
                } else {
                    return valueB.localeCompare(valueA);
                }
            });

            renderDrivers();
        }

        function renderDrivers() {
            const driverDetails = document.getElementById('driverDetails');
            driverDetails.innerHTML = '';

            if (isTableView) {
                let tableHTML = `
                    <table>
                        <tr>
                            <th>No</th>
                            <th>Driver ID</th>
                            <th>User ID</th>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Sticker Expiry</th>
                            <th>Actions</th>
                        </tr>`;
                
                originalDriverData.forEach((driver, index) => {
                    const formattedFullName = driver.FullName
                        .toLowerCase()
                        .split(' ')
                        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                        .join(' ');
                    
                    tableHTML += `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${driver.DriverID}</td>
                            <td>${driver.UserID}</td>
                            <td>${formattedFullName}</td>
                            <td>${driver.Username}</td>
                            <td>${driver.StickerExpDate}</td>
                            <td>
                                <button class="button" onclick="window.location.href='editDri.php?driverID=${driver.DriverID}'">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="button" onclick="deleteDriver('${driver.DriverID}', '${driver.UserID}')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>`;
                });
                tableHTML += '</table>';
                driverDetails.innerHTML = tableHTML;
            } else {
                // Profile view rendering
                originalDriverData.forEach(driver => {
                    const formattedFullName = driver.FullName
                        .toLowerCase()
                        .split(' ')
                        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                        .join(' ');

                    driverDetails.innerHTML += `
                        <div class="driver-info">
                            <img src="${driver.ProfilePicture ? 'data:image/jpeg;base64,' + driver.ProfilePicture : 'https://img.freepik.com/premium-vector/green-circle-with-white-person-inside-icon_1076610-14570.jpg'}" alt="Profile Picture">
                            <div class="details">
                                <div class="detail-row">
                                    <span class="detail-label"><strong>Driver ID:</strong></span>
                                    <span class="detail-value">${driver.DriverID}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label"><strong>User ID:</strong></span>
                                    <span class="detail-value">${driver.UserID}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label"><strong>Full Name:</strong></span>
                                    <span class="detail-value">${formattedFullName}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label"><strong>Username:</strong></span>
                                    <span class="detail-value">${driver.Username}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label"><strong>Sticker Expiry:</strong></span>
                                    <span class="detail-value">${driver.StickerExpDate}</span>
                                </div>
                                <div class="button-group">
                                    <button class="button" onclick="window.location.href='editDri.php?driverID=${driver.DriverID}'">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="button" onclick="deleteDriver('${driver.DriverID}', '${driver.UserID}')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });
            }
        }

        // Add this function to update total users count
        function updateTotalUsers(count) {
            document.getElementById('totalUsers').innerHTML = `<strong>Total matching users: ${count}</strong>`;
        }
    </script>
</body>
</html> 