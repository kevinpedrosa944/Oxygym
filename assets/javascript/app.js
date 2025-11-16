// Check if user is logged in and update button
function checkLoginStatus() {
    fetch('check_session.php')
        .then(res => res.json())
        .then(data => {
            const authButtons = document.getElementById('authButtons');
            if (data.loggedIn) {
                // User IS logged in - show Logout button
                authButtons.innerHTML = `
                    <a href="profile.php" class="register-btn" style="margin-right: 0.5rem;">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <a href="logout.php" class="register-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                `;
            } else {
                // User NOT logged in - show Sign Up button
                authButtons.innerHTML = `
                    <a href="Login.html" class="register-btn">
                        <i class="fas fa-user-plus"></i> Sign Up
                    </a>
                `;
            }
        })
        .catch(err => console.log('Session check failed:', err));
}

// Check on page load
document.addEventListener('DOMContentLoaded', checkLoginStatus);

// Subscribe button handler
function subscribePlan(plan) {
    window.location.href = 'pages/subs.php';
}