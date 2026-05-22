<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../phpdb/script.php?dir=css&file=register.css">
    <link rel="shortcut icon" href="../img/med-logo.png" type="image/x-icon">
    <title>Register</title>
</head>
<body>
    <div class="navbar">
        <p class="system-title container">Meditation Activity Tracker</p> 
        <div class="buttons-container">
            <button type="button" class="btn1"><a href="../phpdb/homepage.php">Home</a></button>
            <button type="button" class="btn2"><a href="../phpdb/login.php">Log In</a></button>
        </div>
    </div>

    <div class="register-box container">
        <h2 class="register-title">Registration Form</h2>
        <form name="form" id="form" method="POST">
            
            <!-- Personal Information -->
            <p class="personal-title">Personal Information</p>
            <div class="personal-info">
                <div class="input-field">
                    <label for="" class="label">ID No</label>
                    <input type="text" name="id_no" class="input" placeholder="XXXX-XXXX" id="id_no" maxlength="9"> 
                </div>  
                <div class="input-field">
                    <label for="" class="label">First Name</label>
                    <input type="text" class="input" name="f_name" placeholder="" id="f_name">
                </div> 
                <div class="input-field">
                    <label for="" class="label">Middle Initial</label>
                    <input type="text" class="input" name="m_initial" placeholder="" id="m_initial"> 
                </div> 
                <div class="input-field">
                    <label for="" class="label">Last Name</label>
                    <input type="text" class="input" name="l_name" placeholder="" id="l_name">
                </div> 
            </div>

            <div class="personal-info">
                <div class="input-field">
                    <label for="" class="label">Extension Name</label>
                    <input type="text" name="extension" class="input" placeholder="" id="extension">
                </div> 
                <div class="input-field">
                    <label for="birthday" class="label">Birthday</label>
                    <input type="date" name="birthday" class="input" id="birthday">
                </div>
                <div class="input-field">
                    <label for="age" class="label">Age</label>
                    <input type="text" name="ageDisplay" class="input" id="age" readonly>
                    <div id="ageMessage" style="color: red"></div>
                    <input type="hidden" id="ageHidden" name="age">
                </div>
                <div class="input-field">
                    <label class="label">Sex </label>
                    <select name="sex" id="sex">
                        <option value=""></option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div> 
            </div>

            <!-- Security Information -->
            <p class="account-title">Security Information</p>
            <div class="account-info">
                <div class="input-field">
                    <label for="" class="label">Username</label>
                    <input type="text" class="input" name="username" placeholder="" id="username">
                </div>  
                <div class="input-field">
                    <label for="" class="label">Password</label>
                    <input type="password" class="input" name="password" placeholder="" id="password">
                    <div id="pwStrength" style="color:red"></div>
                </div>  
                <div class="input-field">
                    <label for="" class="label">Re-enter Password</label>
                    <input type="password" class="input" name="confirm-pass" placeholder="" id="confirmPassword">
                    <div id="pwMatch" style="color:green"></div>
                </div>  
                <div class="input-field">
                    <label for="" class="label">Email</label>
                    <input type="email" class="input" name="email" placeholder="" id="email">
                </div>  
            </div>

            <!-- Address Information -->
            <p class="account-title">Address Information</p>
            <div class="address-info">
                <div class="input-field">
                    <label for="" class="label">Purok/Street</label>
                    <input type="text" class="input" name="purok" placeholder="" id="purok">
                </div>  
                <div class="input-field">
                    <label for="" class="label">Barangay</label>
                    <input type="text" class="input" name="barangay" placeholder="" id="barangay">
                </div>  
                <div class="input-field">
                    <label for="" class="label">Municipality/City</label>
                    <input type="text" class="input" name="city" placeholder="" id="city">
                </div>  
            </div>
            <div class="address-info">
                <div class="input-field">
                    <label for="" class="label">Province</label>
                    <input type="text" class="input" name="province" placeholder="" id="province">
                </div> 
                <div class="input-field">
                    <label for="" class="label">Country</label>
                    <input type="text" class="input" name="country" placeholder="" id="country">
                </div> 
                <div class="input-field">
                    <label for="" class="label">Zip Code</label>
                    <input type="text" class="input" name="zipcode" placeholder="" id="zipcode" maxlength="4">
                </div> 
            </div>

            <!-- Security Questions (Dropdown Questions + Answers) -->
<p class="account-title">Security Questions</p>
<div class="account-info">

    <div class="input-field">
        <label class="label">Question 1</label>
        <select name="sec_q1" id="sec_q1" required>
            <option value=""> Select a question </option>
            <option value="What is your mother's first name?">What is your mother's first name?</option>
            <option value="Where did you attend Elementary?">Where did you attend Elementary?</option>
            <option value="Where is your birthplace?">Where is your birthplace?</option>
            <option value="What is your favorite food?">What is your favorite food?</option>
            <option value="What is your favorite color?">What is your favorite color?</option>
            <option value="What is your childhood nickname?">What is your childhood nickname?</option>
            <option value="What is the name of your first pet?">What is the name of your first pet?</option>
            <option value="What is the name of your best friend?">What is the name of your best friend?</option>
            <option value="What city were you born in?">What city were you born in?</option>
            <option value="What is your father's middle name?">What is your father's middle name?</option>
        </select>
        <input type="password" class="input" name="sec_a1" placeholder="Answer" id="sec_a1" required>
    </div>

    <div class="input-field">
        <label class="label">Question 2</label>
        <select name="sec_q2" id="sec_q2" required>
            <option value="">Select a question</option>
            <option value="What is your mother's first name?">What is your mother's first name?</option>
            <option value="Where did you attend Elementary?">Where did you attend Elementary?</option>
            <option value="Where is your birthplace?">Where is your birthplace?</option>
            <option value="What is your favorite food?">What is your favorite food?</option>
            <option value="What is your favorite color?">What is your favorite color?</option>
            <option value="What is your childhood nickname?">What is your childhood nickname?</option>
            <option value="What is the name of your first pet?">What is the name of your first pet?</option>
            <option value="What is the name of your best friend?">What is the name of your best friend?</option>
            <option value="What city were you born in?">What city were you born in?</option>
            <option value="What is your father's middle name?">What is your father's middle name?</option>
        </select>
        <input type="password" class="input" name="sec_a2" placeholder="Answer" id="sec_a2" required>
    </div>

    <div class="input-field">
        <label class="label">Question 3</label>
        <select name="sec_q3" id="sec_q3" required>
            <option value="">Select a question</option>
            <option value="What is your mother's first name?">What is your mother's first name?</option>
            <option value="Where did you attend Elementary?">Where did you attend Elementary?</option>
            <option value="Where is your birthplace?">Where is your birthplace?</option>
            <option value="What is your favorite food?">What is your favorite food?</option>
            <option value="What is your favorite color?">What is your favorite color?</option>
            <option value="What is your childhood nickname?">What is your childhood nickname?</option>
            <option value="What is the name of your first pet?">What is the name of your first pet?</option>
            <option value="What is the name of your best friend?">What is the name of your best friend?</option>
            <option value="What city were you born in?">What city were you born in?</option>
            <option value="What is your father's middle name?">What is your father's middle name?</option>
        </select>
        <input type="password" class="input" name="sec_a3" placeholder="Answer" id="sec_a3" required>
    </div>

</div>


            <!-- Submit Button -->
            <div class="button">
                <button type="submit" class="btn" name="submit_btn">Submit</button>
            </div>
            <div class="div-create">
                <p class="create">Already have an account? <a href="../phpdb/login.php">Login Here</a></p>
            </div>
        </form>
    </div>

    <div class="footer">
        <p class="all"> &copy; 2026 Meditation Activity Tracker. All rights reserved.</p>
    </div>
    <script src="../phpdb/script.php?dir=script&file=register.js" defer></script>
</body>
</html>
