// Check if user is logged in and update button
function checkLoginStatus() {
    fetch('/Oxygym/check_session.php')
        .then(res => res.json())
        .then(data => {
            const authButtons = document.getElementById('authButtons');
            if (data.loggedIn) {
                // User IS logged in - show Profile & Logout buttons
                authButtons.innerHTML = `
                    <a href="/Oxygym/profile.php" class="register-btn">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <a href="#" class="register-btn" onclick="handleLogout(event)">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                `;
            } else {
                // User NOT logged in - show Sign Up button
                authButtons.innerHTML = `
                    <a href="/Oxygym/Login.html" class="register-btn">
                        <i class="fas fa-user-plus"></i> Sign Up
                    </a>
                `;
            }
        })
        .catch(err => console.log('Session check failed:', err));
}

// Handle logout with page refresh
function handleLogout(event) {
    event.preventDefault();  // ← ADD THIS LINE
    window.location.href = '/Oxygym/logout.php';
}

// Check on page load
document.addEventListener('DOMContentLoaded', checkLoginStatus);

// Hero CTA button handler
function heroCTAClick() {
    fetch('/Oxygym/check_session.php')
        .then(res => res.json())
        .then(data => {
            if (data.loggedIn) {
                window.location.href = '/Oxygym/pages/subs.php';
            } else {
                window.location.href = '/Oxygym/Login.html';
            }
        })
        .catch(err => window.location.href = '/Oxygym/Login.html');
}

// Subscribe button handler
function subscribePlan(plan) {
    fetch('/Oxygym/check_session.php')
        .then(res => res.json())
        .then(data => {
            if (data.loggedIn) {
                window.location.href = '/Oxygym/pages/subs.php';
            } else {
                window.location.href = '/Oxygym/Login.html';
            }
        })
        .catch(err => window.location.href = '/Oxygym/Login.html');
}