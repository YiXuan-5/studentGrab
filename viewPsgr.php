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
                justify-content: space-between;
            }

            .passenger-info {
                display: flex;
                flex-direction: column;
                align-items: center;
                margin-bottom: 15px;
                border: 1px solid #ddd;
                padding: 15px;
                border-radius: 8px;
                width: calc(50% - 10px);
                flex: 0 0 calc(50% - 10px);
                min-width: 250px;
                box-sizing: border-box;
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

        </style>
    </head>
    <body>
        <!-- Navigation Bar -->
        <nav class="navbar">
            <a href="homePageAdm.php" class="nav-item">
                <i class="fas fa-home"></i>
                Home
            </a>
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
                        <option value="psgrID">Passenger ID</option>
                        <option value="role">Role</option>
                        <option value="username">Username</option>
                        <option value="gender">Gender</option>
                        <option value="fullName">Full Name</option>
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
                                <input type="text" id="username">
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
                            <input type="text" id="fullName">
                        </div>

                        <div id="pickupLocationField" style="display: none;">
                            <label for="pickupLocation">Favourite Pick Up Location:</label>
                            <input type="text" id="pickupLocation">
                        </div>

                        <div id="dropoffLocationField" style="display: none;">
                            <label for="dropoffLocation">Favourite Drop Off Location:</label>
                            <input type="text" id="dropoffLocation">
                        </div>
                    </div>

                    <button class="button" onclick="searchPassengers()">Search</button>
                </div>
            </div>

            <!-- Right Section for Passenger Results -->
            <div class="right-section">
                <h2 class="section-title">Passenger Matching Result</h2>
                <div id="resultContainer" class="result-container">
                    <div id="psgrDetails"></div>
                    <div id="noResults" class="no-results" style="display: none;">Results not found</div>
                </div>
            </div>
        </div>

        <script>
            function showInputFields() {
                const criteria = document.getElementById('criteria').value;
                const inputFields = document.getElementById('inputFields');
                inputFields.style.display = 'block'; // Show input fields container

                // Hide all input fields initially
                document.getElementById('psgrIDField').style.display = 'none';
                document.getElementById('roleField').style.display = 'none';
                document.getElementById('usernameField').style.display = 'none';
                document.getElementById('genderField').style.display = 'none';
                document.getElementById('fullNameField').style.display = 'none';
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
                } else if (criteria === 'pickupLocation') {
                    document.getElementById('pickupLocationField').style.display = 'block';
                } else if (criteria === 'dropoffLocation') {
                    document.getElementById('dropoffLocationField').style.display = 'block';
                }
            }

            function searchPassengers() {
                const criteria = document.getElementById('criteria').value;
                const psgrID = document.getElementById('psgrID').value;
                const username = document.getElementById('username').value;
                const fullName = document.getElementById('fullName').value;
                const pickupLocation = document.getElementById('pickupLocation').value;
                const dropoffLocation = document.getElementById('dropoffLocation').value;
                const role = document.querySelector('input[name="role"]:checked') ? document.querySelector('input[name="role"]:checked').value : '';
                const gender = document.querySelector('input[name="gender"]:checked') ? document.querySelector('input[name="gender"]:checked').value : '';
                
                // Log all data being sent
                console.log('Sending search data:', {
                    criteria: criteria,
                    psgrID: psgrID,
                    username: username,
                    fullName: fullName,
                    pickupLocation: pickupLocation,
                    dropoffLocation: dropoffLocation,
                    role: role,
                    gender: gender
                });

                const data = {
                    criteria: criteria,
                    psgrID: psgrID,
                    username: username,
                    fullName: fullName,
                    pickupLocation: pickupLocation,
                    dropoffLocation: dropoffLocation,
                    role: role,
                    gender: gender
                };

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
                    psgrDetails.innerHTML = '';
                    noResults.style.display = 'none';

                    // Check if the results are empty or not an array
                    if (!results || results.error || results.message || !Array.isArray(results) || results.length === 0) {
                        console.log('No results condition met:', results);
                        noResults.style.display = 'block';
                    } else {
                        // If results is an array, process each passenger
                        results.forEach(passenger => {
                            // Format the full name to capitalize first letter of each word
                            const formattedFullName = passenger.FullName
                                .toLowerCase()
                                .split(' ') // .split(' ') means split the string into an array of words
                                .map(word => word.charAt(0).toUpperCase() + word.slice(1)) // .map(word => word.charAt(0).toUpperCase() + word.slice(1)) means map each word to its first letter capitalized and the rest of the word
                                .join(' '); // .join(' ') means join the array of words back into a string

                            console.log('Processing passenger:', passenger);
                            //Display the passenger details in the result container
                            psgrDetails.innerHTML += `
                                <div class="passenger-info">
                                    <img src="data:image/jpeg;base64,${passenger.ProfilePicture}" alt="Profile Picture">
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
                                            <button class="button" onclick="editPassenger('${passenger.PsgrID}')">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button class="button" onclick="deletePassenger('${passenger.PsgrID}')">
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
                    console.error('Full error details:', error);
                    document.getElementById('noResults').style.display = 'block';
                });
            }

            function editPassenger(psgrID) {
                // Implement edit functionality
                alert('Edit passenger: ' + psgrID);
            }

            function deletePassenger(psgrID) {
                // Implement delete functionality
                alert('Delete passenger: ' + psgrID);
            }
        </script>
    </body>
    </html> 