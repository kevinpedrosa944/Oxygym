// Check if user is logged in and update button
function checkLoginStatus() {
    fetch('/Oxygym/api/check_session.php')
        .then(res => res.json())
        .then(data => {
            const authButtons = document.getElementById('authButtons');
            if (!authButtons) return;
            
            authButtons.innerHTML = data.loggedIn 
                ? `<a href="/Oxygym/profile.php" class="register-btn"><i class="fas fa-user"></i> Profile</a>
                   <a href="#" class="register-btn" onclick="handleLogout(event)"><i class="fas fa-sign-out-alt"></i> Logout</a>`
                : `<a href="/Oxygym/Login.html" class="register-btn"><i class="fas fa-user-plus"></i> Sign Up</a>`;
        })
        .catch(err => console.error('Session check failed:', err));
}

// Handle logout
function handleLogout(event) {
    event.preventDefault();
    window.location.href = '/Oxygym/logout.php';
}

// Check on page load
document.addEventListener('DOMContentLoaded', checkLoginStatus);

// Hero CTA button handler
function heroCTAClick() {
    checkLoginAndRedirect('/Oxygym/pages/subs.php');
}

// Subscribe button handler
function subscribePlan(plan) {
    checkLoginAndRedirect('/Oxygym/pages/subs.php');
}

function checkLoginAndRedirect(redirectUrl) {
    fetch('/Oxygym/api/check_session.php')
        .then(res => res.json())
        .then(data => {
            window.location.href = data.loggedIn ? redirectUrl : '/Oxygym/Login.html';
        })
        .catch(() => window.location.href = '/Oxygym/Login.html');
}