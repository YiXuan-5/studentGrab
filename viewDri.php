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
                    <option value="stickerExpDate">Sticker Expiry Date</option>
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
                </div>

                <button class="button" onclick="searchDrivers()">Search</button>
            </div>
        </div>

        <!-- Right Section for Driver Results -->
        <div class="right-section">
            <h2 class="section-title">Driver Matching Result</h2>
            <div id="resultContainer" class="result-container">
                <div id="driverDetails"></div>
                <div id="noResults" class="no-results" style="display: none;">Results not found</div>
            </div>
        </div>
    </div>

    <script>
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
            }
            // Note: stickerExpDate doesn't need an input field
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

            const data = {
                criteria: criteria,
                driverID: driverID,
                username: username,
                fullName: fullName,
                gender: gender,
                availability: availability
            };

            // If "All" is selected, call loadAllDrivers directly
            if (criteria === 'all') {
                loadAllDrivers();
                return;
            }

            fetch('fetchDris.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(results => {
                const driverDetails = document.getElementById('driverDetails');
                const noResults = document.getElementById('noResults');
                driverDetails.innerHTML = '';
                noResults.style.display = 'none';

                if (!results || results.error || results.message || !Array.isArray(results) || results.length === 0) {
                    noResults.style.display = 'block';
                } else {
                    results.forEach(driver => {
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

        // Add this function to load all drivers when the page loads
        function loadAllDrivers() {
            fetch('fetchDris.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ criteria: 'all' }) // Send 'all' as criteria
            })
            .then(response => response.json())
            .then(results => {
                const driverDetails = document.getElementById('driverDetails');
                const noResults = document.getElementById('noResults');
                driverDetails.innerHTML = '';
                noResults.style.display = 'none';

                if (!results || results.error || !Array.isArray(results) || results.length === 0) {
                    noResults.style.display = 'block';
                } else {
                    results.forEach(driver => {
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
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('noResults').style.display = 'block';
            });
        }

        // Call the function when the page loads
        window.onload = loadAllDrivers;
    </script>
</body>
</html> 