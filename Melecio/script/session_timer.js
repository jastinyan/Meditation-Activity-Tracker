let totalSeconds = 60;
let remainingSeconds = totalSeconds;
let timerInterval = null;
let running = false;

let selectedFeeling = "Happy";
let selectedLocation = "Home";

const timerDisplay = document.getElementById("timerDisplay");
const timerCircle = document.getElementById("timerCircle");
const playPauseBtn = document.getElementById("playPauseBtn");
const minuteText = document.getElementById("minuteText");

const detailsModal = document.getElementById("detailsModal");
const closeDetailsBtn = document.getElementById("closeDetailsBtn");

const saveBtn = document.getElementById("saveBtn");
const discardBtn = document.getElementById("discardBtn");

const modalSessionName = document.getElementById("modalSavedName");
const customSessionInput = document.getElementById("customSessionName");

/* ================= INIT MODAL ================= */
detailsModal.style.display = "none";

/* ================= SUCCESS TOAST ================= */
function showSuccessAnimation() {
    let toast = document.getElementById("successToast");

    if (!toast) {
        toast = document.createElement("div");
        toast.id = "successToast";
        toast.innerText = "✔ Session Saved Successfully";
        toast.style.position = "fixed";
        toast.style.top = "30px";
        toast.style.right = "30px";
        toast.style.background = "linear-gradient(45deg,#00c853,#00e676)";
        toast.style.padding = "15px 25px";
        toast.style.borderRadius = "12px";
        toast.style.color = "white";
        toast.style.fontWeight = "bold";
        toast.style.boxShadow = "0 8px 20px rgba(0,0,0,0.3)";
        toast.style.transform = "translateX(150%)";
        toast.style.transition = "0.5s ease";
        toast.style.zIndex = "5000";
        document.body.appendChild(toast);
    }

    toast.style.transform = "translateX(0)";

    setTimeout(() => {
        toast.style.transform = "translateX(150%)";
    }, 3000);
}

/* ================= FORMAT TIME ================= */
function formatTime(seconds) {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return String(mins).padStart(2, "0") + ":" + String(secs).padStart(2, "0");
}

/* ================= UPDATE UI ================= */
function updateMinuteText() {
    minuteText.textContent = `${Math.floor(totalSeconds / 60)} Minutes`;
}

function updateTimerUI() {
    timerDisplay.textContent = formatTime(remainingSeconds);

    let progress = ((totalSeconds - remainingSeconds) / totalSeconds) * 360;
    timerCircle.style.background =
        `conic-gradient(rgb(181,65,7) ${progress}deg, rgba(255,255,255,0.15) 0deg)`;
}

/* ================= SHOW SAVE/DISCARD ================= */
function toggleSaveDiscardButtons() {
    if (!running && (totalSeconds - remainingSeconds) > 0) {
        saveBtn.style.display = "inline-block";
        discardBtn.style.display = "inline-block";
    } else {
        saveBtn.style.display = "none";
        discardBtn.style.display = "none";
    }
}

/* ================= LOAD HISTORY ================= */
async function loadHistory() {
    try {
        const response = await fetch('fetch_recent_session.php');
        const sessions = await response.json();
        
        const historyList = document.getElementById('historyList');
        historyList.innerHTML = ''; // Clear existing rows
        
        if (sessions.length === 0) {
            // Show a message if no sessions
            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = '<td colspan="6" style="text-align: center; padding: 20px;">No sessions recorded yet</td>';
            historyList.appendChild(emptyRow);
            return;
        }
        
        sessions.forEach(session => {
            const row = document.createElement('tr');
            
            // Format time_spent from seconds to MM:SS
            const minutes = Math.floor(session.time_spent / 60);
            const seconds = session.time_spent % 60;
            const formattedTime = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            
            // Format date
            const date = new Date(session.created_at);
            const formattedDate = date.toLocaleString();
            
            // Get emoji for feeling
            const feelingEmoji = getFeelingEmoji(session.feeling);
            
            // Get emoji for location
            const locationEmoji = getLocationEmoji(session.location);
            
            // Truncate notes if too long (optional)
            let notes = session.notes || '';
            if (notes.length > 30) {
                notes = notes.substring(0, 30) + '...';
            }
            
            row.innerHTML = `
                <td>${escapeHtml(session.session_name)}</td>
                <td>${formattedTime}</td>
                <td>${formattedDate}</td>
                <td>${feelingEmoji} ${escapeHtml(session.feeling)}</td>
                <td>${locationEmoji} ${escapeHtml(session.location)}</td>
                <td>${escapeHtml(notes) || '—'}</td>
            `;
            
            historyList.appendChild(row);
        });
        
    } catch (error) {
        console.error('Error loading history:', error);
        const historyList = document.getElementById('historyList');
        historyList.innerHTML = '<tr><td colspan="6" style="text-align: center; color: red;">Error loading sessions</td></tr>';
    }
}

// Helper function to get emoji for feeling
function getFeelingEmoji(feeling) {
    const emojis = {
        'Happy': '😊',
        'Distracted': '😅',
        'Boring': '😐',
        'Normal': '🙂'
    };
    return emojis[feeling] || '😊';
}

// Helper function to get emoji for location
function getLocationEmoji(location) {
    const emojis = {
        'Home': '🏠',
        'Work': '🏢',
        'Center': '🧘',
        'Outside': '🌿'
    };
    return emojis[location] || '🏠';
}

// Helper function to escape HTML special characters
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/* ================= PLAY / PAUSE ================= */
playPauseBtn.addEventListener("click", () => {

    if (!running) {

        running = true;
        playPauseBtn.textContent = "⏸ Pause";
        playPauseBtn.classList.add("pause-mode");
        toggleSaveDiscardButtons();

        timerInterval = setInterval(() => {
            if (remainingSeconds > 0) {
                remainingSeconds--;
                updateTimerUI();
            } else {
                clearInterval(timerInterval);
                running = false;
                playPauseBtn.textContent = "▶ Play";
                playPauseBtn.classList.remove("pause-mode");
                toggleSaveDiscardButtons();
            }
        }, 1000);

    } else {

        running = false;
        clearInterval(timerInterval);
        playPauseBtn.textContent = "▶ Play";
        playPauseBtn.classList.remove("pause-mode");
        toggleSaveDiscardButtons();
    }
});

/* ================= EMOJI SELECTION ================= */
function setEmojiActive(groupId, setter) {
    document.querySelectorAll(`#${groupId} .emoji-btn`).forEach(btn => {
        btn.addEventListener("click", () => {
            document.querySelectorAll(`#${groupId} .emoji-btn`)
                .forEach(b => b.classList.remove("active"));
            btn.classList.add("active");
            setter(btn.dataset.value);
        });
    });
}

setEmojiActive("modalFeelingGroup", val => selectedFeeling = val);
setEmojiActive("modalLocationGroup", val => selectedLocation = val);

/* ================= SAVE SESSION (OPEN MODAL ONLY) ================= */
saveBtn.addEventListener("click", () => {

    const timeSpent = totalSeconds - remainingSeconds;

    if (timeSpent <= 0) {
        return;
    }

    // Reset modal fields to default values
    modalSessionName.value = "Mindfulness Meditation";
    customSessionInput.style.display = "none";
    customSessionInput.value = "";
    document.getElementById("modalSavedNotes").value = "";
    
    // Reset emoji selections to default (Happy and Home)
    document.querySelectorAll("#modalFeelingGroup .emoji-btn").forEach(btn => {
        if (btn.dataset.value === "Happy") {
            btn.classList.add("active");
        } else {
            btn.classList.remove("active");
        }
    });
    
    document.querySelectorAll("#modalLocationGroup .emoji-btn").forEach(btn => {
        if (btn.dataset.value === "Home") {
            btn.classList.add("active");
        } else {
            btn.classList.remove("active");
        }
    });
    
    // Reset selected values
    selectedFeeling = "Happy";
    selectedLocation = "Home";

    document.getElementById("modalSavedTime").value = formatTime(timeSpent);
    document.getElementById("modalSavedDateTime").value =
        new Date().toLocaleString();

    detailsModal.style.display = "flex";
});

/* ================= SAVE MODAL CHANGES ================= */
document.getElementById("saveModalChangesBtn")
.addEventListener("click", async () => {

    // Get session name from dropdown or custom input
    let sessionName = modalSessionName.value;
    if (sessionName === "Custom") {
        sessionName = customSessionInput.value.trim();
        if (!sessionName) {
            alert("Please enter a custom session name");
            return;
        }
    }

    const notes = document.getElementById("modalSavedNotes").value.trim();
    const timeSpent = totalSeconds - remainingSeconds;

    if (timeSpent <= 0) {
        alert("No time spent in this session");
        return;
    }

    const formData = new URLSearchParams();
    formData.append("session_name", sessionName);
    formData.append("time_spent", timeSpent);
    formData.append("feeling", selectedFeeling);
    formData.append("location", selectedLocation);
    formData.append("notes", notes);

    try {
        const res = await fetch("save_meditation_session.php", {
            method: "POST",
            body: formData
        });

        const data = await res.json();

        if (data.success) {

            detailsModal.style.display = "none";

            /* SUCCESS ANIMATION */
            showSuccessAnimation();

            /* RESET MODAL FIELDS */
            modalSessionName.value = "Mindfulness Meditation";
            customSessionInput.style.display = "none";
            customSessionInput.value = "";
            document.getElementById("modalSavedNotes").value = "";

            document.querySelectorAll("#modalFeelingGroup .emoji-btn")
                .forEach(btn => btn.classList.remove("active"));

            document.querySelectorAll("#modalLocationGroup .emoji-btn")
                .forEach(btn => btn.classList.remove("active"));

            // Set default active emojis
            document.querySelector("#modalFeelingGroup .emoji-btn[data-value='Happy']").classList.add("active");
            document.querySelector("#modalLocationGroup .emoji-btn[data-value='Home']").classList.add("active");
            
            selectedFeeling = "Happy";
            selectedLocation = "Home";

            /* RESET TIMER */
            clearInterval(timerInterval);
            running = false;
            totalSeconds = 60;
            remainingSeconds = totalSeconds;

            updateMinuteText();
            updateTimerUI();
            toggleSaveDiscardButtons();
            playPauseBtn.textContent = "▶ Play";
            playPauseBtn.classList.remove("pause-mode");

            /* RELOAD HISTORY */
            await loadHistory();

        } else {
            alert("Error saving session: " + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert("Error saving session. Please try again.");
    }
});

/* ================= DISCARD ================= */
discardBtn.addEventListener("click", () => {

    clearInterval(timerInterval);
    running = false;

    totalSeconds = 60;
    remainingSeconds = totalSeconds;

    updateMinuteText();
    updateTimerUI();
    toggleSaveDiscardButtons();

    playPauseBtn.textContent = "▶ Play";
    playPauseBtn.classList.remove("pause-mode");
});

/* ================= CLOSE MODAL ================= */
closeDetailsBtn.addEventListener("click", () => {
    detailsModal.style.display = "none";
});

window.addEventListener("click", (e) => {
    if (e.target === detailsModal) {
        detailsModal.style.display = "none";
    }
});

// Handle session name dropdown change
if (modalSessionName) {
    modalSessionName.addEventListener("change", function() {
        if (this.value === "Custom") {
            customSessionInput.style.display = "block";
            customSessionInput.focus();
        } else {
            customSessionInput.style.display = "none";
            customSessionInput.value = "";
        }
    });
}

/* ================= INIT ================= */
updateMinuteText();
updateTimerUI();
toggleSaveDiscardButtons();

// Load history on page load
loadHistory();