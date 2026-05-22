document.addEventListener("DOMContentLoaded", () => {
    const calendarDiv = document.getElementById("calendar");

    const sessionModal = document.getElementById("sessionModal");
    const viewModal = document.getElementById("viewModal");
    const editModal = document.getElementById("editModal");

    const openSessionFormBtn = document.getElementById("openSessionFormBtn");
    const closeModalBtn = document.getElementById("closeModalBtn");
    const closeViewBtn = document.getElementById("closeViewBtn");
    const closeEditBtn = document.getElementById("closeEditBtn");

    const sessionForm = document.getElementById("sessionForm");
    const editForm = document.getElementById("editForm");

    const sessionSelect = document.getElementById("session_name");
    const customSession = document.getElementById("custom_session");

    const viewSessionDetails = document.getElementById("viewSessionDetails");

    let currentDate = new Date(); // controls month navigation

    // ================= Calendar Generator =================
    function createCalendar(dateObj) {
        const monthNames = [
            "January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"
        ];

        const days = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

        const year = dateObj.getFullYear();
        const month = dateObj.getMonth();

        const today = new Date();
        const isThisMonth =
            today.getFullYear() === year &&
            today.getMonth() === month;

        const firstDay = new Date(year, month, 1).getDay();
        const lastDate = new Date(year, month + 1, 0).getDate();

        let table = "<table class='calendar-table'>";

        // HEADER WITH NAV BUTTONS
        table += `
            <tr>
                <th colspan="7" class="calendar-header">
                    <button class="nav-btn" id="prevMonthBtn">&#10094;</button>
                    <span class="month-title">${monthNames[month]} ${year}</span>
                    <button class="nav-btn" id="nextMonthBtn">&#10095;</button>
                </th>
            </tr>
        `;

        // DAYS ROW
        table += "<tr>" + days.map(d => `<th>${d}</th>`).join("") + "</tr>";

        let day = 1;

        for (let i = 0; i < 6; i++) {
            table += "<tr>";

            for (let j = 0; j < 7; j++) {
                if (i === 0 && j < firstDay) {
                    table += "<td></td>";
                } else if (day > lastDate) {
                    table += "<td></td>";
                } else {
                    const fullDate = `${year}-${String(month + 1).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
                    const todayClass = (isThisMonth && day === today.getDate()) ? "today" : "";

                    table += `
                        <td class="${todayClass}" data-date="${fullDate}">
                            <div class="day-number">${day}</div>
                            <div class="dots" id="dots-${fullDate}"></div>
                        </td>
                    `;
                    day++;
                }
            }

            table += "</tr>";
        }

        table += "</table>";
        calendarDiv.innerHTML = table;

        // NAVIGATION BUTTON EVENTS
        document.getElementById("prevMonthBtn").addEventListener("click", () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            createCalendar(currentDate);
        });

        document.getElementById("nextMonthBtn").addEventListener("click", () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            createCalendar(currentDate);
        });

        loadSessions();
        attachDayClick();
    }

    // ================= Fetch and Display Dots =================
    function loadSessions() {
        fetch("fetch_sessions.php")
            .then(res => res.json())
            .then(data => {
                data.forEach(session => {
                    const dotsDiv = document.getElementById("dots-" + session.session_date);

                    if (dotsDiv) {
                        const dot = document.createElement("span");
                        dot.classList.add("dot");
                        dot.style.backgroundColor = session.color;
                        dotsDiv.appendChild(dot);
                    }
                });
            })
            .catch(err => console.error("Fetch error:", err));
    }

    // ================= Day Click =================
    function attachDayClick() {
        document.querySelectorAll("td[data-date]").forEach(cell => {
            cell.addEventListener("click", () => {
            const selectedDate = cell.getAttribute("data-date");

            // AUTO FILL DATE INPUT
            document.getElementById("session_date").value = selectedDate;

            fetch("fetch_sessions.php?date=" + selectedDate)
                .then(res => res.json())
                .then(data => {

                    // IF NO SESSIONS: OPEN ADD MODAL DIRECTLY
                    if (data.length === 0) {
                        sessionModal.style.display = "flex";
                        return;
                    }

                    // IF HAS SESSIONS: SHOW VIEW MODAL
                    let html = "";

                    data.forEach(session => {
                        html += `
                            <div class="session-card">
                                <p><b>${session.session_name}</b></p>
                                <p>${session.session_date} | ${session.session_time}</p>

                                <div class="session-actions">
                                    <button class="edit-btn" 
                                        data-id="${session.id}"
                                        data-name="${session.session_name}"
                                        data-date="${session.session_date}"
                                        data-time="${session.session_time}"
                                        data-color="${session.color}">
                                        Edit
                                    </button>

                                    <button class="delete-btn" data-id="${session.id}">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        `;
                    });

                    viewSessionDetails.innerHTML = html;
                    viewModal.style.display = "flex";

                    attachEditDeleteButtons();
                });
        });

        });
    }

    // ================= Attach Edit/Delete Buttons =================
    function attachEditDeleteButtons() {
        document.querySelectorAll(".edit-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                document.getElementById("edit_id").value = btn.dataset.id;
                document.getElementById("edit_session_name").value = btn.dataset.name;
                document.getElementById("edit_session_date").value = btn.dataset.date;
                document.getElementById("edit_session_time").value = btn.dataset.time;
                document.getElementById("edit_color").value = btn.dataset.color;

                viewModal.style.display = "none";
                editModal.style.display = "flex";
            });
        });

        document.querySelectorAll(".delete-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                const id = btn.dataset.id;

                if (confirm("Are you sure you want to delete this session?")) {
                    fetch("delete_session.php?id=" + id)
                        .then(res => res.json())
                        .then(data => {
                            alert(data.message);
                            viewModal.style.display = "none";
                            createCalendar(currentDate);
                        });
                }
            });
        });
    }

    // ================= Custom Session Toggle =================
    sessionSelect.addEventListener("change", () => {
        if (sessionSelect.value === "Custom") {
            customSession.style.display = "block";
        } else {
            customSession.style.display = "none";
        }
    });

    // ================= Open Modal =================
    openSessionFormBtn.addEventListener("click", () => {
        sessionModal.style.display = "flex";
    });

    closeModalBtn.addEventListener("click", () => sessionModal.style.display = "none");
    closeViewBtn.addEventListener("click", () => viewModal.style.display = "none");
    closeEditBtn.addEventListener("click", () => editModal.style.display = "none");

    // ================= Add Session =================
    sessionForm.addEventListener("submit", (e) => {
        e.preventDefault();

        let session_name = sessionSelect.value;
        if (session_name === "Custom") {
            session_name = customSession.value.trim();
        }

        const session_date = document.getElementById("session_date").value;
        const session_time = document.getElementById("session_time").value;
        const color = document.getElementById("color").value;

        if (!session_name) {
            alert("Please enter session name.");
            return;
        }

        fetch("add_session.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `session_name=${encodeURIComponent(session_name)}&session_date=${session_date}&session_time=${session_time}&color=${encodeURIComponent(color)}`
        })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                sessionModal.style.display = "none";
                sessionForm.reset();
                createCalendar(currentDate);
            });
    });

    // ================= Update Session =================
    editForm.addEventListener("submit", (e) => {
        e.preventDefault();

        const id = document.getElementById("edit_id").value;
        const session_name = document.getElementById("edit_session_name").value;
        const session_date = document.getElementById("edit_session_date").value;
        const session_time = document.getElementById("edit_session_time").value;
        const color = document.getElementById("edit_color").value;

        fetch("edit_session.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `id=${id}&session_name=${encodeURIComponent(session_name)}&session_date=${session_date}&session_time=${session_time}&color=${encodeURIComponent(color)}`
        })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                editModal.style.display = "none";
                createCalendar(currentDate);
            });
    });

    // ================= Init =================
    createCalendar(currentDate);
});
