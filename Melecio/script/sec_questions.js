let lockTimer = null;

// ===================== Fetch Security Questions =====================
async function fetchSecurityQuestions() {
    const id_no = document.getElementById('hidden_id_no2').value;

    if (!id_no) {
        console.log("No ID found for fetching questions.");
        return;
    }

    try {
        const res = await fetch('get_sec_questions_ajax.php', {
            method: 'POST',
            body: new URLSearchParams({ id_no })
        });

        const data = await res.json();

        if (data.status === 'success') {
            document.getElementById('label_q1').innerText = data.questions.sec_q1;
            document.getElementById('label_q2').innerText = data.questions.sec_q2;
            document.getElementById('label_q3').innerText = data.questions.sec_q3;
        } else {
            document.getElementById("sec-msg").innerText = data.message || "Failed to load security questions.";
        }

    } catch (err) {
        console.error(err);
        document.getElementById("sec-msg").innerText = "Error fetching security questions.";
    }
}

// ===================== Step 3: Security Questions Verification =====================
document.getElementById("questionForm").addEventListener("submit", async function(e) {
    e.preventDefault();

    const form = this;
    const formData = new FormData(form);
    const msgBox = document.getElementById("sec-msg");
    const submitBtn = form.querySelector("button[type='submit']");

    msgBox.innerHTML = "";
    msgBox.className = "error";

    try {
        const res = await fetch("sec_questions_ajax.php", {
            method: "POST",
            body: formData
        });

        const data = await res.json();

        clearInterval(lockTimer);

        if (data.status === "success") {
            msgBox.innerText = "";
            submitBtn.disabled = false;

            // Go to step 4
            showStep(4);
        }
        else if (data.status === "failed") {
            msgBox.className = "error";

            if (data.errors && Array.isArray(data.errors)) {
                data.errors.forEach(err => {
                    msgBox.innerHTML += `<p>${err}</p>`;
                });
            }

            if (data.attempts_left !== undefined) {
                msgBox.innerHTML += `<p>Attempts left: ${data.attempts_left}</p>`;
            }

            submitBtn.disabled = false;
        }
        else if (data.status === "locked") {
            submitBtn.disabled = true;
            msgBox.className = "warning";

            let seconds = parseInt(data.locked_for);

            lockTimer = setInterval(() => {
                if (seconds <= 0) {
                    clearInterval(lockTimer);
                    msgBox.className = "error";
                    msgBox.innerHTML = "You may try again now. Attempts reset to 3.";
                    submitBtn.disabled = false;
                    return;
                }

                let m = Math.floor(seconds / 60);
                let s = seconds % 60;

                msgBox.innerHTML = `Too many attempts. Try again in ${m}:${s.toString().padStart(2,'0')}`;
                seconds--;
            }, 1000);
        }
        else if (data.status === "redirect") {
            alert(data.message);

            // Instead of reloading to step 1, just send them back to step 2
            showStep(2);
        }
        else {
            msgBox.className = "error";
            msgBox.innerText = data.message || "Something went wrong.";
            submitBtn.disabled = false;
        }

    } catch {
        msgBox.className = "error";
        msgBox.innerHTML = "Server error. Please try again.";
        submitBtn.disabled = false;
    }
});
