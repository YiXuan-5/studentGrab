<?php
session_start();
include 'dbConnection.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['UserID']) || $_SESSION['UserRole'] !== 'admin') {
    header("Location: loginPsgr.php");
    exit;
}

// Fetch passenger ID from the request (e.g., from a button click)
$passengerID = $_GET['passengerID'] ?? null;

if ($passengerID) {
    // Fetch passenger data
    $stmt = $connMe->prepare("
        SELECT u.FullName, u.EmailAddress, u.PhoneNo, u.Gender, u.BirthDate, u.EmailSecCode, u.ProfilePicture,
               p.Username, p.Password, p.FavPickUpLoc, p.FavDropOffLoc, p.Role
        FROM USER u
        JOIN PASSENGER p ON u.UserID = p.UserID
        WHERE p.PsgrID = ?
    ");
    $stmt->bind_param("s", $passengerID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Passenger not found");
    }

    $passengerData = $result->fetch_assoc();
} else {
    throw new Exception("Passenger ID not provided");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Passenger</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/fontawesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/solid.min.css">
    <style>
        /* Add your styles here */
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Passenger Details</h2>
        <button onclick="editSection('account')">Edit Account Information</button>
        <button onclick="editSection('personal')">Edit Personal Information</button>
        <button onclick="editSection('preferences')">Edit Preferences</button>

        <!-- Modal for editing passenger details -->
        <div id="editPassengerModal" class="modal" style="display: none;">
            <div class="modal-content">
                <h2>Edit Passenger Information</h2>
                <form id="editPassengerForm">
                    <!-- Account Information Section -->
                    <div id="accountSection">
                        <h3>Account Information</h3>
                        <label>Username:</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($passengerData['Username']); ?>" required>
                        <label>Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo strtolower($passengerData['EmailAddress']); ?>" required>
                        <label>Password:</label>
                        <input type="password" id="password" name="password" placeholder="Leave blank to keep current">
                    </div>

                    <!-- Personal Information Section -->
                    <div id="personalSection">
                        <h3>Personal Information</h3>
                        <label>Full Name:</label>
                        <input type="text" id="fullName" name="fullName" value="<?php echo ucwords(strtolower($passengerData['FullName'])); ?>" required>
                        <label>Phone Number:</label>
                        <input type="text" id="phoneNo" name="phoneNo" value="<?php echo htmlspecialchars($passengerData['PhoneNo']); ?>" required>
                        <label>Gender:</label>
                        <select id="gender" name="gender">
                            <option value="M" <?php echo $passengerData['Gender'] === 'M' ? 'selected' : ''; ?>>Male</option>
                            <option value="F" <?php echo $passengerData['Gender'] === 'F' ? 'selected' : ''; ?>>Female</option>
                        </select>
                        <label>Birthday:</label>
                        <input type="date" id="birthDate" name="birthDate" value="<?php echo date('Y-m-d', strtotime($passengerData['BirthDate'])); ?>" required>
                    </div>

                    <!-- Preferences Section -->
                    <div id="preferencesSection">
                        <h3>Preferences</h3>
                        <label>Favorite Pickup Location:</label>
                        <input type="text" id="favPickUpLoc" name="favPickUpLoc" value="<?php echo ucwords(strtolower($passengerData['FavPickUpLoc'])); ?>" required>
                        <label>Favorite Drop-off Location:</label>
                        <input type="text" id="favDropOffLoc" name="favDropOffLoc" value="<?php echo ucwords(strtolower($passengerData['FavDropOffLoc'])); ?>" required>
                    </div>

                    <button type="button" onclick="saveChanges()">Save Changes</button>
                    <button type="button" onclick="closeModal()">Cancel</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editSection(section) {
            // Show the appropriate section in the modal
            document.getElementById('accountSection').style.display = section === 'account' ? 'block' : 'none';
            document.getElementById('personalSection').style.display = section === 'personal' ? 'block' : 'none';
            document.getElementById('preferencesSection').style.display = section === 'preferences' ? 'block' : 'none';
            document.getElementById('editPassengerModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editPassengerModal').style.display = 'none';
        }

        async function saveChanges() {
            const formData = {
                updateType: 'personal', // or 'account' or 'preferences' based on the section
                username: document.getElementById('username').value,
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
                fullName: document.getElementById('fullName').value,
                phoneNo: document.getElementById('phoneNo').value,
                gender: document.getElementById('gender').value,
                birthDate: document.getElementById('birthDate').value,
                favPickUpLoc: document.getElementById('favPickUpLoc').value,
                favDropOffLoc: document.getElementById('favDropOffLoc').value,
                psgrId: '<?php echo $passengerID; ?>' // Pass the passenger ID
            };

            try {
                const response = await fetch('updateAccountPsgr.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();
                if (data.status === 'success') {
                    alert('Passenger information updated successfully');
                    location.reload(); // Reload the page to show updated information
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while updating passenger information');
            }
        }
    </script>
</body>
</html> 