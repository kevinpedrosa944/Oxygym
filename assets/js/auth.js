// Tab switching function
function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    const element = document.getElementById(tabName);
    if (element) {
        element.classList.add('active');
    }
}

// Show signup tab when link is clicked
const showSignupLink = document.getElementById('showSignup');
if (showSignupLink) {
    showSignupLink.addEventListener('click', function(e) {
        e.preventDefault();
        switchTab('signup');
    });
}

// ========== LOGIN FORM ==========
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    const loginError = document.getElementById('loginError');
    
    loginForm.addEventListener('submit', async function(event) {
        event.preventDefault();
        
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        
        loginError.textContent = '';
        
        if (!username || !password) {
            loginError.textContent = 'Username and password are required.';
            return;
        }
        
        try {
            const res = await fetch('/Oxygym/api/Login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, password })
            });
            
            const data = await res.json();
            
            console.log('Login response:', data);
            
            if (res.ok && data.success) {
                console.log('Login successful, redirecting to:', data.redirect);
                // Use window.location.href for guaranteed redirect
                window.location.href = data.redirect;
            } else {
                loginError.textContent = data.error || data.message || 'Login failed.';
            }
        } catch (error) {
            loginError.textContent = 'Network error: ' + error.message;
            console.error('Fetch error:', error);
        }
    });
}

// ========== SIGNUP FORM ==========
const signupForm = document.getElementById('signupForm');
if (signupForm) {
    const signupError = document.getElementById('signupError');
    
    signupForm.addEventListener('submit', async function(event) {
        event.preventDefault();
        
        const firstName = document.getElementById('signupFirstName').value.trim();
        const lastName = document.getElementById('signupLastName').value.trim();
        const email = document.getElementById('signupEmail').value.trim();
        const username = firstName.toLowerCase() + lastName.toLowerCase();
        const password = document.getElementById('signupPassword').value;
        
        signupError.textContent = '';
        
        if (!firstName || !lastName || !email || !password) {
            signupError.textContent = 'All fields are required.';
            return;
        }
        
        if (password.length < 6) {
            signupError.textContent = 'Password must be at least 6 characters.';
            return;
        }
        
        try {
            const res = await fetch('/Oxygym/api/Register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    firstName, 
                    lastName, 
                    email, 
                    username,
                    password 
                })
            });
            
            const data = await res.json();
            
            console.log('Register response:', data);
            
            if (res.ok && data.success) {
                alert('Account created successfully! Redirecting to profile...');
                console.log('Registration successful, redirecting to:', data.redirect);
                window.location.href = data.redirect;
            } else {
                signupError.textContent = data.error || data.message || 'Sign up failed.';
            }
        } catch (error) {
            signupError.textContent = 'Network error: ' + error.message;
            console.error('Fetch error:', error);
        }
    });
}