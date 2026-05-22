let currentPage = 1;
let totalPages = 1;
let itemsPerPage = 10;
let totalItems = 0;
let currentFilters = {
    feeling: '',
    location: '',
    search: '',
    sort: 'newest'
};

// DOM Elements
const sessionsList = document.getElementById('sessionsList');
const searchInput = document.getElementById('searchInput');
const feelingFilter = document.getElementById('feelingFilter');
const locationFilter = document.getElementById('locationFilter');
const sortBy = document.getElementById('sortBy');
const pagination = document.getElementById('pagination');
const totalSessionsEl = document.getElementById('totalSessions');
const totalTimeEl = document.getElementById('totalTime');
const avgTimeEl = document.getElementById('avgTime');

// Modals
const editModal = document.getElementById('editModal');
const deleteModal = document.getElementById('deleteModal');
const viewModal = document.getElementById('viewModal');

// Toast elements
const successToast = document.getElementById('successToast');
const errorToast = document.getElementById('errorToast');

// ================= INITIAL LOAD =================
document.addEventListener('DOMContentLoaded', () => {
    loadSessions();
    setupEventListeners();
});

// ================= SETUP EVENT LISTENERS =================
function setupEventListeners() {
    // Filter events
    searchInput.addEventListener('input', debounce(() => {
        currentFilters.search = searchInput.value;
        currentPage = 1;
        loadSessions();
    }, 500));

    feelingFilter.addEventListener('change', () => {
        currentFilters.feeling = feelingFilter.value;
        currentPage = 1;
        loadSessions();
    });

    locationFilter.addEventListener('change', () => {
        currentFilters.location = locationFilter.value;
        currentPage = 1;
        loadSessions();
    });

    sortBy.addEventListener('change', () => {
        currentFilters.sort = sortBy.value;
        loadSessions();
    });

    // Modal close buttons
    document.getElementById('closeEditBtn')?.addEventListener('click', () => closeModal(editModal));
    document.getElementById('closeDeleteBtn')?.addEventListener('click', () => closeModal(deleteModal));
    document.getElementById('closeViewBtn')?.addEventListener('click', () => closeModal(viewModal));
    document.getElementById('closeViewDetailsBtn')?.addEventListener('click', () => closeModal(viewModal));
    document.getElementById('cancelEditBtn')?.addEventListener('click', () => closeModal(editModal));
    document.getElementById('cancelDeleteBtn')?.addEventListener('click', () => closeModal(deleteModal));

    // Edit form submission
    document.getElementById('editForm')?.addEventListener('submit', handleEditSubmit);
    
    // Delete confirmation
    document.getElementById('confirmDeleteBtn')?.addEventListener('click', handleDelete);

    // Handle custom session name dropdown
    const sessionSelect = document.getElementById('editSessionName');
    if (sessionSelect) {
        sessionSelect.addEventListener('change', function() {
            const customInput = document.getElementById('editCustomSession');
            if (this.value === 'Custom') {
                customInput.style.display = 'block';
                customInput.focus();
            } else {
                customInput.style.display = 'none';
                customInput.value = '';
            }
        });
    }

    // Click outside modals to close
    window.addEventListener('click', (e) => {
        if (e.target === editModal) closeModal(editModal);
        if (e.target === deleteModal) closeModal(deleteModal);
        if (e.target === viewModal) closeModal(viewModal);
    });
}

// ================= LOAD SESSIONS =================
async function loadSessions() {
    try {
        sessionsList.innerHTML = '<tr><td colspan="7" class="loading-message"><i class="fas fa-spinner fa-spin"></i> Loading sessions...</td></tr>';
        
        const params = new URLSearchParams({
            page: currentPage,
            limit: itemsPerPage,
            ...currentFilters
        });
        
        const response = await fetch(`fetch_history_sessions.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            displaySessions(data.sessions);
            updateStats(data.stats);
            totalItems = data.pagination?.total || 0;
            totalPages = data.pagination?.totalPages || 1;
            renderPagination();
        } else {
            showError('Failed to load sessions');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Error loading sessions');
        sessionsList.innerHTML = '<tr><td colspan="7" class="error-message">Failed to load sessions</td></tr>';
    }
}

// ================= DISPLAY SESSIONS =================
function displaySessions(sessions) {
    if (!sessions || sessions.length === 0) {
        sessionsList.innerHTML = '<tr><td colspan="7" class="no-results"><i class="fas fa-folder-open"></i> No sessions found</td></tr>';
        return;
    }

    let html = '';
    sessions.forEach(session => {
        const date = new Date(session.created_at);
        const formattedDate = date.toLocaleString();
        const duration = formatTime(session.time_spent);
        const feelingEmoji = getFeelingEmoji(session.feeling);
        const locationEmoji = getLocationEmoji(session.location);
        const notes = session.notes ? escapeHtml(session.notes.substring(0, 50)) + (session.notes.length > 50 ? '...' : '') : '—';
        
        html += `
            <tr>
                <td class="session-name">${escapeHtml(session.session_name)}</td>
                <td class="duration"><span class="duration-badge"><i class="fas fa-hourglass-half"></i> ${duration}</span></td>
                <td class="datetime-cell"><i class="far fa-calendar-alt"></i> ${formattedDate}</td>
                <td class="feeling"><span class="feeling-badge">${feelingEmoji} ${escapeHtml(session.feeling)}</span></td>
                <td class="location"><span class="location-badge">${locationEmoji} ${escapeHtml(session.location)}</span></td>
                <td class="notes-cell" title="${escapeHtml(session.notes || '')}"><i class="fas fa-sticky-note"></i> ${notes}</td>
                <td class="actions-cell">
                    <div class="action-btns">
                        <button class="btn-icon btn-view" onclick="viewSession(${session.id})" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-icon btn-edit" onclick="editSession(${session.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon btn-delete" onclick="confirmDelete(${session.id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    sessionsList.innerHTML = html;
}

// ================= RENDER PAGINATION =================
function renderPagination() {
    if (!pagination) return;
    
    if (totalPages <= 1) {
        pagination.innerHTML = '';
        return;
    }
    
    let html = '<div class="history-pagination">';
    
    // Previous button
    html += `<button class="page-btn ${currentPage === 1 ? 'disabled' : ''}" 
        onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
        <i class="fas fa-chevron-left"></i> Prev
    </button>`;
    
    // Page numbers
    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
    
    if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    
    // First page
    if (startPage > 1) {
        html += `<button class="page-btn" onclick="changePage(1)">1</button>`;
        if (startPage > 2) {
            html += `<span class="page-dots">...</span>`;
        }
    }
    
    // Page numbers
    for (let i = startPage; i <= endPage; i++) {
        html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" 
            onclick="changePage(${i})">${i}</button>`;
    }
    
    // Last page
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            html += `<span class="page-dots">...</span>`;
        }
        html += `<button class="page-btn" onclick="changePage(${totalPages})">${totalPages}</button>`;
    }
    
    // Next button
    html += `<button class="page-btn ${currentPage === totalPages ? 'disabled' : ''}" 
        onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
        Next <i class="fas fa-chevron-right"></i>
    </button>`;
    
    html += '</div>';
    
    // Add page info and items per page selector
    html += `<div class="history-page-info">
        <i class="fas fa-file"></i> Page ${currentPage} of ${totalPages} | 
        <i class="fas fa-database"></i> Total: ${totalItems} sessions
    </div>`;
    
    html += `<div class="history-per-page">
        <span>Show:</span>
        <select onchange="changeItemsPerPage(this.value)">
            <option value="5" ${itemsPerPage === 5 ? 'selected' : ''}>5</option>
            <option value="10" ${itemsPerPage === 10 ? 'selected' : ''}>10</option>
            <option value="25" ${itemsPerPage === 25 ? 'selected' : ''}>25</option>
            <option value="50" ${itemsPerPage === 50 ? 'selected' : ''}>50</option>
        </select>
        <span>per page</span>
    </div>`;
    
    pagination.innerHTML = html;
}

// ================= CHANGE PAGE =================
function changePage(page) {
    if (page >= 1 && page <= totalPages && page !== currentPage) {
        currentPage = page;
        loadSessions();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

// ================= CHANGE ITEMS PER PAGE =================
function changeItemsPerPage(value) {
    itemsPerPage = parseInt(value);
    currentPage = 1;
    loadSessions();
}

// ================= UPDATE STATS =================
function updateStats(stats) {
    if (stats) {
        totalSessionsEl.textContent = stats.totalSessions || 0;
        totalTimeEl.textContent = formatTime(stats.totalTime || 0);
        avgTimeEl.textContent = formatTime(stats.avgTime || 0);
    }
}

// ================= VIEW SESSION =================
async function viewSession(sessionId) {
    try {
        const response = await fetch(`get_history_session.php?id=${sessionId}`);
        const data = await response.json();
        
        if (data.success) {
            const session = data.session;
            const date = new Date(session.created_at);
            
            const html = `
                <div class="detail-item">
                    <strong><i class="fas fa-tag"></i> Session Name:</strong>
                    <span>${escapeHtml(session.session_name)}</span>
                </div>
                <div class="detail-item">
                    <strong><i class="fas fa-hourglass-half"></i> Duration:</strong>
                    <span>${formatTime(session.time_spent)}</span>
                </div>
                <div class="detail-item">
                    <strong><i class="far fa-calendar-alt"></i> Date & Time:</strong>
                    <span>${date.toLocaleString()}</span>
                </div>
                <div class="detail-item">
                    <strong><i class="fas fa-smile"></i> Feeling:</strong>
                    <span>${getFeelingEmoji(session.feeling)} ${escapeHtml(session.feeling)}</span>
                </div>
                <div class="detail-item">
                    <strong><i class="fas fa-map-marker-alt"></i> Location:</strong>
                    <span>${getLocationEmoji(session.location)} ${escapeHtml(session.location)}</span>
                </div>
                <div class="detail-item">
                    <strong><i class="fas fa-sticky-note"></i> Notes:</strong>
                    <p class="notes-text">${escapeHtml(session.notes) || '—'}</p>
                </div>
            `;
            
            document.getElementById('sessionDetails').innerHTML = html;
            viewModal.style.display = 'flex';
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Failed to load session details');
    }
}

// ================= EDIT SESSION =================
async function editSession(sessionId) {
    try {
        const response = await fetch(`get_history_session.php?id=${sessionId}`);
        const data = await response.json();
        
        if (data.success) {
            const session = data.session;
            
            // Fill the edit form
            document.getElementById('editSessionId').value = session.id;
            
            // Handle session name dropdown
            const sessionSelect = document.getElementById('editSessionName');
            const customInput = document.getElementById('editCustomSession');
            
            // Check if session name is in the dropdown options
            const options = Array.from(sessionSelect.options).map(opt => opt.value);
            if (options.includes(session.session_name)) {
                sessionSelect.value = session.session_name;
                customInput.style.display = 'none';
                customInput.value = '';
            } else {
                sessionSelect.value = 'Custom';
                customInput.style.display = 'block';
                customInput.value = session.session_name;
            }
            
            document.getElementById('editTimeSpent').value = session.time_spent;
            
            // Format datetime for input
            const date = new Date(session.created_at);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            document.getElementById('editDateTime').value = `${year}-${month}-${day}T${hours}:${minutes}`;
            
            // Set feeling
            document.querySelectorAll('#editFeelingGroup .emoji-btn').forEach(btn => {
                if (btn.dataset.value === session.feeling) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
            
            // Set location
            document.querySelectorAll('#editLocationGroup .emoji-btn').forEach(btn => {
                if (btn.dataset.value === session.location) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
            
            document.getElementById('editNotes').value = session.notes || '';
            
            // Setup emoji selection for edit modal
            setupEmojiSelection('editFeelingGroup');
            setupEmojiSelection('editLocationGroup');
            
            editModal.style.display = 'flex';
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Failed to load session for editing');
    }
}

// ================= HANDLE EDIT SUBMIT =================
async function handleEditSubmit(e) {
    e.preventDefault();
    
    const sessionId = document.getElementById('editSessionId').value;
    let sessionName = document.getElementById('editSessionName').value;
    
    if (sessionName === 'Custom') {
        sessionName = document.getElementById('editCustomSession').value.trim();
        if (!sessionName) {
            showError('Please enter a custom session name');
            return;
        }
    }
    
    const timeSpent = document.getElementById('editTimeSpent').value;
    const dateTime = document.getElementById('editDateTime').value;
    const notes = document.getElementById('editNotes').value;
    
    // Get selected feeling
    const feelingBtn = document.querySelector('#editFeelingGroup .emoji-btn.active');
    const feeling = feelingBtn ? feelingBtn.dataset.value : 'Happy';
    
    // Get selected location
    const locationBtn = document.querySelector('#editLocationGroup .emoji-btn.active');
    const location = locationBtn ? locationBtn.dataset.value : 'Home';
    
    const formData = new URLSearchParams();
    formData.append('session_id', sessionId);
    formData.append('session_name', sessionName);
    formData.append('time_spent', timeSpent);
    formData.append('feeling', feeling);
    formData.append('location', location);
    formData.append('notes', notes);
    formData.append('created_at', dateTime.replace('T', ' ') + ':00');
    
    try {
        const response = await fetch('update_history_session.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            closeModal(editModal);
            showSuccess('Session updated successfully');
            loadSessions();
        } else {
            showError(data.message || 'Failed to update session');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Error updating session');
    }
}

// ================= DELETE SESSION =================
function confirmDelete(sessionId) {
    document.getElementById('deleteSessionId').value = sessionId;
    
    // Get session info for the modal
    fetch(`get_history_session.php?id=${sessionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const session = data.session;
                const sessionInfo = document.getElementById('sessionInfo');
                sessionInfo.innerHTML = `
                    <p><i class="fas fa-tag"></i> <strong>Session:</strong> ${escapeHtml(session.session_name)}</p>
                    <p><i class="far fa-calendar-alt"></i> <strong>Date:</strong> ${new Date(session.created_at).toLocaleString()}</p>
                `;
            }
        });
    
    deleteModal.style.display = 'flex';
}

async function handleDelete() {
    const sessionId = document.getElementById('deleteSessionId').value;
    
    const formData = new URLSearchParams();
    formData.append('session_id', sessionId);
    
    try {
        const response = await fetch('delete_history_session.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            closeModal(deleteModal);
            showSuccess('Session deleted successfully');
            loadSessions();
        } else {
            showError(data.message || 'Failed to delete session');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Error deleting session');
    }
}

// ================= HELPER FUNCTIONS =================
function formatTime(seconds) {
    if (!seconds) return '00:00';
    const hours = Math.floor(seconds / 3600);
    const mins = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    
    if (hours > 0) {
        return `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }
    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
}

function getFeelingEmoji(feeling) {
    const emojis = {
        'Happy': '😊',
        'Distracted': '😅',
        'Boring': '😐',
        'Normal': '🙂'
    };
    return emojis[feeling] || '😊';
}

function getLocationEmoji(location) {
    const emojis = {
        'Home': '🏠',
        'Work': '🏢',
        'Center': '🧘',
        'Outside': '🌿'
    };
    return emojis[location] || '🏠';
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function setupEmojiSelection(groupId) {
    document.querySelectorAll(`#${groupId} .emoji-btn`).forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll(`#${groupId} .emoji-btn`)
                .forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });
}

function closeModal(modal) {
    modal.style.display = 'none';
}

function showSuccess(message) {
    if (successToast) {
        const messageSpan = document.getElementById('toastMessage') || successToast;
        if (typeof messageSpan === 'object') {
            messageSpan.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
        }
        successToast.classList.add('show');
        setTimeout(() => {
            successToast.classList.remove('show');
        }, 3000);
    }
}

function showError(message) {
    if (errorToast) {
        const messageSpan = document.getElementById('errorToastMessage') || errorToast;
        if (typeof messageSpan === 'object') {
            messageSpan.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
        }
        errorToast.classList.add('show');
        setTimeout(() => {
            errorToast.classList.remove('show');
        }, 3000);
    }
}