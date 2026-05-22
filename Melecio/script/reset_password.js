
function checkResetPasswordStrength(){
    const password = document.getElementById('new_password').value;
    const text = document.getElementById('pwStrength');

    const weak = /^[a-zA-Z0-9]{6,}$/;
    const strong = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/;

    if(!weak.test(password)){
        text.innerHTML = "Weak password";
        text.style.color = "red";
    }
    else if(strong.test(password)){
        text.innerHTML = "Strong password";
        text.style.color = "green";
    }
    else{
        text.innerHTML = "Moderate password";
        text.style.color = "orange";
    }
}

// Password match check
function checkResetPasswordMatch(){
    const pw = document.getElementById('new_password').value;
    const cpw = document.getElementById('confirm_password').value;
    const text = document.getElementById('pwMatch');

    if(pw !== cpw){
        text.innerHTML = "Passwords do not match";
        text.style.color = "red";
    } else {
        text.innerHTML = "Passwords match";
        text.style.color = "green";
    }
}

// Event listeners
document.getElementById('new_password').addEventListener('input',checkResetPasswordStrength);
document.getElementById('confirm_password').addEventListener('input',checkResetPasswordMatch);

