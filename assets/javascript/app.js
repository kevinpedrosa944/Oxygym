// Check if user is logged in and update button
function checkLoginStatus() {
    fetch('check_session.php')
        .then(res => res.json())
        .then(data => {
            const authButtons = document.getElementById('authButtons');
            if (data.loggedIn) {
                authButtons.innerHTML = '<a href="logout.php" class="register-btn">Logout</a>';
            } else {
                authButtons.innerHTML = '<a href="Login.html" class="register-btn">Sign Up</a>';
            }
        })
        .catch(err => console.log('Session check failed:', err));
}

// Check on page load
checkLoginStatus();

// Subscribe button handler
function subscribePlan(plan) {
    window.location.href = 'pages/subs.php';
}