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
                console.log('✅ Login successful');
                console.log('Redirect URL:', data.redirect);
                
                // Convert relative path to absolute URL
                let redirectUrl = data.redirect;
                
                // If redirect doesn't start with http, make it absolute
                if (!redirectUrl.startsWith('http')) {
                    // Handle both /Oxygym/... and api/admin/... paths
                    if (redirectUrl.startsWith('/')) {
                        redirectUrl = window.location.origin + redirectUrl;
                    } else {
                        redirectUrl = window.location.origin + '/Oxygym/' + redirectUrl;
                    }
                }
                
                console.log('Final redirect URL:', redirectUrl);
                window.location.href = redirectUrl;
            } else {
                loginError.textContent = data.error || data.message || 'Login failed.';
                console.error('Login error:', data.error);
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
        
        if (!email.includes('@')) {
            signupError.textContent = 'Please enter a valid email.';
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
                    password 
                })
            });
            
            const data = await res.json();
            
            console.log('Registration response:', data);
            
            if (res.ok && data.success) {
                console.log('✅ Registration successful');
                console.log('Redirect URL:', data.redirect);
                
                // Convert relative path to absolute URL
                let redirectUrl = data.redirect;
                
                // If redirect doesn't start with http, make it absolute
                if (!redirectUrl.startsWith('http')) {
                    // Handle both /Oxygym/... and api/admin/... paths
                    if (redirectUrl.startsWith('/')) {
                        redirectUrl = window.location.origin + redirectUrl;
                    } else {
                        redirectUrl = window.location.origin + '/Oxygym/' + redirectUrl;
                    }
                }
                
                console.log('Final redirect URL:', redirectUrl);
                window.location.href = redirectUrl;
            } else {
                signupError.textContent = data.error || data.message || 'Sign up failed.';
                console.error('Registration error:', data.error);
            }
        } catch (error) {
            signupError.textContent = 'Network error: ' + error.message;
            console.error('Fetch error:', error);
        }
    });
}