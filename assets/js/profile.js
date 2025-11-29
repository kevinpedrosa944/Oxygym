// Check if user is authenticated
async function checkAuthentication() {
    try {
        const response = await fetch('/Oxygym/api/check_auth.php');
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
        const response = await fetch('/Oxygym/api/profile.php');
        
        if (!response.ok) {
            if (response.status === 401) {
                window.location.href = '/Oxygym/Login.html';
            }
            throw new Error('Failed to load profile');
        }

        const data = await response.json();
        populateProfile(data);
    } catch (error) {
        console.error('Error loading profile:', error);
        alert('Error loading profile. Please try again.');
    }
}

// Populate profile with data
function populateProfile(data) {
    const { member, subscription } = data;

    // Profile header
    document.getElementById('memberName').textContent = `${member.firstName} ${member.lastName}`;
    document.getElementById('memberEmail').textContent = member.email;

    // Personal info
    const personalInfoHTML = `
        <div class="detail-item">
            <span class="detail-label">First Name</span>
            <span class="detail-value">${escapeHtml(member.firstName)}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Last Name</span>
            <span class="detail-value">${escapeHtml(member.lastName)}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Email</span>
            <span class="detail-value">${escapeHtml(member.email)}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Phone</span>
            <span class="detail-value">${escapeHtml(member.phone)}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Gender</span>
            <span class="detail-value">${escapeHtml(member.gender)}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Date of Birth</span>
            <span class="detail-value">${member.age > 0 ? `${member.birthdate} (${member.age} years old)` : 'Not provided'}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Member Since</span>
            <span class="detail-value">${member.joinDate}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Days Active</span>
            <span class="detail-value">${member.daysActive} days</span>
        </div>
    `;
    document.getElementById('personalInfo').innerHTML = personalInfoHTML;

    // Subscription section
    document.getElementById('planName').textContent = escapeHtml(subscription.planName);

    const subscriptionStatsHTML = `
        <div class="subscription-stat">
            <div class="stat-number">${subscription.daysRemaining}</div>
            <div class="stat-label">Days Remaining</div>
        </div>
        <div class="subscription-stat">
            <div class="stat-number">₱${parseFloat(subscription.price).toFixed(2)}</div>
            <div class="stat-label">Price per ${subscription.duration} days</div>
        </div>
        <div class="subscription-stat">
            <div class="stat-number"><span class="status-badge">${escapeHtml(subscription.status)}</span></div>
            <div class="stat-label">Status</div>
        </div>
    `;
    document.getElementById('subscriptionStats').innerHTML = subscriptionStatsHTML;

    const subscriptionDetailsHTML = `
        <div class="detail-item">
            <span class="detail-label">Plan Name</span>
            <span class="detail-value">${escapeHtml(subscription.planName)}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Price</span>
            <span class="detail-value"><span class="price-tag">₱${parseFloat(subscription.price).toFixed(2)}</span></span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Duration</span>
            <span class="detail-value">${subscription.duration} days</span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Start Date</span>
            <span class="detail-value">${subscription.startDate}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Expiry Date</span>
            <span class="detail-value">${subscription.endDate}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Status</span>
            <span class="detail-value"><span class="status-badge">${escapeHtml(subscription.status)}</span></span>
        </div>
    `;
    document.getElementById('subscriptionDetails').innerHTML = subscriptionDetailsHTML;

    // Warning banner
    if (subscription.daysRemaining <= 7 && subscription.daysRemaining > 0) {
        const warningBanner = document.getElementById('warningBanner');
        document.getElementById('warningText').textContent = `Your subscription expires in ${subscription.daysRemaining} days.`;
        warningBanner.style.display = 'flex';
    }

    // Load reviews
    loadReviews();
}

// Load and display reviews
async function loadReviews() {
    try {
        const response = await fetch('/Oxygym/api/Review.php');
        
        if (!response.ok) {
            throw new Error('Failed to load reviews');
        }

        const data = await response.json();
        displayReviews(data.reviews);
    } catch (error) {
        console.error('Error loading reviews:', error);
        document.getElementById('reviewsList').innerHTML = '<p style="color: #999;">Unable to load reviews</p>';
    }
}

// Display reviews
function displayReviews(reviews) {
    const reviewsList = document.getElementById('reviewsList');
    
    if (reviews.length === 0) {
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
                        <h4>${escapeHtml(review.reviewer)}</h4>
                        <small>${review.createdAt}</small>
                    </div>
                </div>
                <div class="review-rating">
                    ${Array(5).fill(0).map((_, i) => `
                        <i class="fas fa-star" style="color: ${i < review.rating ? '#ffc107' : '#ddd'};"></i>
                    `).join('')}
                </div>
            </div>
            <div class="review-title">
                <h3>${escapeHtml(review.title)}</h3>
            </div>
            <div class="review-body">
                <p>${escapeHtml(review.body)}</p>
            </div>
            <div class="review-footer">
                <small>${review.createdAt}</small>
                <div class="review-actions">
                    <button class="review-btn" onclick="editReview(${review.id}, '${escapeHtml(review.title)}', '${escapeHtml(review.body)}', ${review.rating})">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="review-btn danger" onclick="deleteReview(${review.id})">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

// Edit review
function editReview(id, title, body, rating) {
    document.getElementById('reviewTitle').value = title;
    document.getElementById('reviewBody').value = body;
    document.querySelector(`input[name="rating"][value="${rating}"]`).checked = true;
    
    const form = document.getElementById('reviewForm');
    form.dataset.editId = id;
    form.dataset.isEdit = 'true';
    
    modal.classList.add('show');
}

// Delete review
async function deleteReview(id) {
    if (!confirm('Are you sure you want to delete this review?')) {
        return;
    }

    try {
        const response = await fetch('/Oxygym/api/Review.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        });

        if (!response.ok) {
            throw new Error('Failed to delete review');
        }

        alert('Review deleted successfully');
        loadReviews();
    } catch (error) {
        console.error('Error deleting review:', error);
        alert('Error deleting review. Please try again.');
    }
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Modal handlers
const modal = document.getElementById('reviewModal');
const addReviewBtn = document.getElementById('addReviewBtn');
const closeBtn = document.querySelector('.close-btn');
const cancelReviewBtn = document.getElementById('cancelReviewBtn');
const reviewForm = document.getElementById('reviewForm');

// Open modal
if (addReviewBtn) {
    addReviewBtn.addEventListener('click', () => {
        reviewForm.dataset.isEdit = 'false';
        reviewForm.reset();
        modal.classList.add('show');
    });
}

// Close modal
function closeModal() {
    modal.classList.remove('show');
    reviewForm.reset();
    delete reviewForm.dataset.editId;
    delete reviewForm.dataset.isEdit;
}

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
if (reviewForm) {
    reviewForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        
        const rating = document.querySelector('input[name="rating"]:checked');
        const title = document.getElementById('reviewTitle').value;
        const body = document.getElementById('reviewBody').value;

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
    const isAuthenticated = await checkAuthentication();
    if (isAuthenticated) {
        loadProfile();
    }
});