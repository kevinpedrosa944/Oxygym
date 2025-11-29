// Check if user is authenticated
async function checkAuthentication() {
    try {
        const response = await fetch('/Oxygym/api/check_session.php');
        const data = await response.json();

        if (!data.authenticated) {
            console.log('Not authenticated, redirecting to login...');
            window.location.href = '/Oxygym/Login.html';
            return false;
        }

        return true;
    } catch (error) {
        console.error('Error checking authentication:', error);
        window.location.href = '/Oxygym/Login.html';
        return false;
    }
}

// Load profile data
async function loadProfile() {
    try {
        console.log('📤 Loading profile...');
        const response = await fetch('/Oxygym/api/profile.php');
        
        if (!response.ok) {
            if (response.status === 401) {
                window.location.href = '/Oxygym/Login.html';
            }
            throw new Error('Failed to load profile');
        }

        const data = await response.json();
        console.log('📦 Profile data:', data);
        
        if (data.success) {
            populateProfile(data.profile, data.subscription);
        } else {
            throw new Error(data.error || 'Failed to load profile');
        }
    } catch (error) {
        console.error('Error loading profile:', error);
        alert('Error loading profile: ' + error.message);
    }
}

// Populate profile with data
function populateProfile(profile, subscription) {
    console.log('👤 Populating profile:', profile);
    console.log('💳 Populating subscription:', subscription);

    // Profile header
    if (document.getElementById('memberName')) {
        document.getElementById('memberName').textContent = `${profile.first_name} ${profile.last_name}`;
    }
    if (document.getElementById('memberEmail')) {
        document.getElementById('memberEmail').textContent = profile.email;
    }

    // Personal info
    if (document.getElementById('personalInfo')) {
        const personalInfoHTML = `
            <div class="detail-item">
                <span class="detail-label">First Name</span>
                <span class="detail-value">${escapeHtml(profile.first_name)}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Last Name</span>
                <span class="detail-value">${escapeHtml(profile.last_name)}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Email</span>
                <span class="detail-value">${escapeHtml(profile.email)}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Phone</span>
                <span class="detail-value">${escapeHtml(profile.phone || 'Not provided')}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Member Since</span>
                <span class="detail-value">${profile.join_date}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Days Active</span>
                <span class="detail-value">${profile.days_active} days</span>
            </div>
        `;
        document.getElementById('personalInfo').innerHTML = personalInfoHTML;
    }

    // Subscription section
    if (document.getElementById('planName')) {
        document.getElementById('planName').textContent = escapeHtml(subscription.plan_name || 'No Plan');
    }

    if (document.getElementById('subscriptionStats')) {
        const subscriptionStatsHTML = `
            <div class="subscription-stat">
                <div class="stat-number">${subscription.days_remaining}</div>
                <div class="stat-label">Days Remaining</div>
            </div>
            <div class="subscription-stat">
                <div class="stat-number">₱${parseFloat(subscription.price || 0).toFixed(2)}</div>
                <div class="stat-label">Plan Price</div>
            </div>
            <div class="subscription-stat">
                <div class="stat-number"><span class="status-badge status-${(subscription.status || 'inactive').toLowerCase()}">${escapeHtml(subscription.status || 'No Plan')}</span></div>
                <div class="stat-label">Status</div>
            </div>
        `;
        document.getElementById('subscriptionStats').innerHTML = subscriptionStatsHTML;
    }

    if (document.getElementById('subscriptionDetails')) {
        const subscriptionDetailsHTML = `
            <div class="detail-item">
                <span class="detail-label">Plan Name</span>
                <span class="detail-value">${escapeHtml(subscription.plan_name || 'No Active Plan')}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Price</span>
                <span class="detail-value"><span class="price-tag">₱${parseFloat(subscription.price || 0).toFixed(2)}</span></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Start Date</span>
                <span class="detail-value">${subscription.start_date || 'Not set'}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">End Date</span>
                <span class="detail-value">${subscription.end_date || 'Not set'}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Status</span>
                <span class="detail-value"><span class="status-badge status-${(subscription.status || 'inactive').toLowerCase()}">${escapeHtml(subscription.status || 'Inactive')}</span></span>
            </div>
        `;
        document.getElementById('subscriptionDetails').innerHTML = subscriptionDetailsHTML;
    }

    // Warning banner
    if (subscription.days_remaining <= 7 && subscription.days_remaining > 0) {
        const warningBanner = document.getElementById('warningBanner');
        if (warningBanner) {
            const warningText = document.getElementById('warningText');
            if (warningText) {
                warningText.textContent = `Your subscription expires in ${subscription.days_remaining} days.`;
            }
            warningBanner.style.display = 'flex';
        }
    }

    // Load reviews if function exists
    if (typeof loadReviews === 'function') {
        loadReviews();
    }
}

// Load and display reviews
async function loadReviews() {
    try {
        const response = await fetch('/Oxygym/api/Review.php');
        
        if (!response.ok) {
            throw new Error('Failed to load reviews');
        }

        const data = await response.json();
        displayReviews(data.reviews || []);
    } catch (error) {
        console.error('Error loading reviews:', error);
        if (document.getElementById('reviewsList')) {
            document.getElementById('reviewsList').innerHTML = '<p style="color: #999;">Unable to load reviews</p>';
        }
    }
}

// Display reviews
function displayReviews(reviews) {
    const reviewsList = document.getElementById('reviewsList');
    if (!reviewsList) return;
    
    if (!reviews || reviews.length === 0) {
        reviewsList.innerHTML = '<p style="color: #999; text-align: center; padding: 2rem;">No reviews yet. Be the first to share your experience!</p>';
        return;
    }

    reviewsList.innerHTML = reviews.map(review => `
        <div class="review-card">
            <div class="review-header">
                <div class="reviewer-info">
                    <div class="reviewer-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h4>${escapeHtml(review.reviewer || 'Anonymous')}</h4>
                        <small>${review.createdAt || 'Just now'}</small>
                    </div>
                </div>
                <div class="review-rating">
                    ${Array(5).fill(0).map((_, i) => `
                        <i class="fas fa-star" style="color: ${i < (review.rating || 0) ? '#ffc107' : '#ddd'};"></i>
                    `).join('')}
                </div>
            </div>
            <div class="review-title">
                <h3>${escapeHtml(review.title || 'Untitled')}</h3>
            </div>
            <div class="review-body">
                <p>${escapeHtml(review.body || '')}</p>
            </div>
            <div class="review-footer">
                <small>${review.createdAt || ''}</small>
            </div>
        </div>
    `).join('');
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

// Modal handlers
function setupReviewModal() {
    const modal = document.getElementById('reviewModal');
    const addReviewBtn = document.getElementById('addReviewBtn');
    const closeBtn = document.querySelector('.close-btn');
    const cancelReviewBtn = document.getElementById('cancelReviewBtn');
    const reviewForm = document.getElementById('reviewForm');

    if (!modal || !reviewForm) return;

    function closeModal() {
        modal.classList.remove('show');
        reviewForm.reset();
        delete reviewForm.dataset.editId;
        delete reviewForm.dataset.isEdit;
    }

    // Open modal
    if (addReviewBtn) {
        addReviewBtn.addEventListener('click', () => {
            reviewForm.dataset.isEdit = 'false';
            reviewForm.reset();
            modal.classList.add('show');
        });
    }

    // Close modal
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    if (cancelReviewBtn) {
        cancelReviewBtn.addEventListener('click', closeModal);
    }

    // Close on outside click
    window.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    // Handle form submission
    reviewForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        
        const rating = document.querySelector('input[name="rating"]:checked');
        const title = document.getElementById('reviewTitle')?.value || '';
        const body = document.getElementById('reviewBody')?.value || '';

        if (!rating) {
            alert('Please select a rating');
            return;
        }

        if (!title || !body) {
            alert('Please fill all fields');
            return;
        }

        try {
            const isEdit = reviewForm.dataset.isEdit === 'true';
            const method = isEdit ? 'PUT' : 'POST';
            const payload = {
                rating: parseInt(rating.value),
                title: title,
                body: body
            };

            if (isEdit) {
                payload.id = parseInt(reviewForm.dataset.editId);
            }

            const response = await fetch('/Oxygym/api/Review.php', {
                method: method,
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.error || 'Failed to save review');
            }

            alert(isEdit ? 'Review updated successfully!' : 'Review posted successfully!');
            closeModal();
            loadReviews();
        } catch (error) {
            console.error('Error saving review:', error);
            alert(`Error: ${error.message}`);
        }
    });
}

// Logout handler
function handleLogout(event) {
    event.preventDefault();
    window.location.href = '/Oxygym/logout.php';
}

// Load profile on page load
document.addEventListener('DOMContentLoaded', async () => {
    console.log('🚀 Profile page loaded');
    const isAuthenticated = await checkAuthentication();
    if (isAuthenticated) {
        loadProfile();
        setupReviewModal();
    }
});