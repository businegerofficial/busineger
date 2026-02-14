document.getElementById('registration-form').addEventListener('submit', function (event) {
  event.preventDefault();

  const name = document.getElementById('name').value.trim();
  const email = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value.trim();
  const captchaInput = document.getElementById('captcha-input').value.trim();

  // Basic email format validation
  if (!validateEmail(email)) {
    showErrorModal('Invalid Email', 'Please enter a valid email address.');
    return; // Stop further execution
  }

  // Validate CAPTCHA
  if (captchaInput !== currentCaptcha) {
    showErrorModal('Incorrect CAPTCHA', 'The CAPTCHA you entered is incorrect. Please try again.');
    currentCaptcha = generateCaptcha();
    document.getElementById('captcha').textContent = currentCaptcha;
    document.getElementById('captcha-input').value = '';
    return;
  }

  // Send the data to Google Apps Script Web App
  fetch('https://script.google.com/macros/s/AKfycbwaLszyVvO0p2jdhgeVVh_x-kgRiqnfuw41LuCohc0BJjbK6eLadSM8d0yWkdEPQNPEEQ/exec', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `name=${name}&email=${email}&password=${password}`
  })
    .then(response => response.json())
    .then(data => {
      // Show popup message after successful submission
      if (data.result === 'success') {
        document.getElementById('popup-title').textContent = 'Please Process Next';
        document.getElementById('popup-message').textContent = `Welcome, ${name}! Your registration is complete.`;
        document.getElementById('popup').style.display = 'flex';

        // Update user information in localStorage
        localStorage.setItem('userName', name);
        localStorage.setItem('userProfilePic', 'https://static.vecteezy.com/system/resources/previews/008/302/513/non_2x/eps10-blue-user-icon-or-logo-in-simple-flat-trendy-modern-style-isolated-on-white-background-free-vector.jpg'); // Replace with actual profile pic URL

        // Update the displayed user's name and profile picture
        updateUserAccount(name);

        // Wait for 5 seconds before redirecting to the next page
        setTimeout(function () {
          window.location.href = 'mandipayment.php'; // Replace with your next HTML page URL
        }, 5000); // Delay redirection for 5 seconds to show the popup message

      } else if (data.result === 'email_not_found') {
        showErrorModal('Error', 'The provided email address does not exist. Please try again.');
      } else {
        showErrorModal('Error', 'An error occurred. Please try again.');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      showErrorModal('Error', 'Network error. Please try again.');
    });
});

function validateEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

// Generate CAPTCHA
function generateCaptcha() {
  const characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
  let captcha = "";
  for (let i = 0; i < 6; i++) {
    captcha += characters.charAt(Math.floor(Math.random() * characters.length));
  }
  return captcha;
}

// Initialize CAPTCHA
const captchaDisplay = document.getElementById('captcha');
let currentCaptcha = generateCaptcha();
captchaDisplay.textContent = currentCaptcha;

// Refresh CAPTCHA
document.getElementById('refresh-captcha').addEventListener('click', () => {
  currentCaptcha = generateCaptcha();
  captchaDisplay.textContent = currentCaptcha;
  document.getElementById('captcha-input').value = '';
  document.getElementById('captcha-error').textContent = '';
  document.getElementById('captcha-input').classList.remove('error');
  document.getElementById('captcha-error').classList.remove('show');
});

// Show error modal
function showErrorModal(title, message) {
  const modal = document.createElement("div");
  modal.className = "error-modal";
  modal.innerHTML = `
    <div class="error-modal-content">
      <h2>${title}</h2>
      <p>${message}</p>
      <button id="close-modal" class="modal-button">Close</button>
    </div>
  `;
  document.body.appendChild(modal);

  document.getElementById('close-modal').addEventListener('click', () => {
    modal.remove();
  });
}

// Update or display the user's name and profile picture in the top-right corner
function updateUserAccount(name) {
  let userAccountSection = document.querySelector('.user-account');

  if (!userAccountSection) {
    userAccountSection = document.createElement('div');
    userAccountSection.classList.add('user-account');
    document.body.appendChild(userAccountSection);
  }

  const userProfilePic = localStorage.getItem('userProfilePic');

  userAccountSection.innerHTML = `
    <img id="user-profile-pic" src="${userProfilePic}" alt="User Profile" />
    <span id="user-name">${name}</span>
  `;

  const style = document.createElement('style');
  style.innerHTML = `
    .user-account {
      position: fixed;
      top: 100px;
      right: 50px;
      display: flex;
      align-items: center;
      background: linear-gradient(135deg, #f0f8ff, #87cefa);
      padding: 10px 15px;
      border-radius: 30px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25);
      z-index: 1000;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .user-account:hover {
      transform: scale(1.05);
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
    }

    #user-profile-pic {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      margin-right: 10px;
      border: 2px solid #fff;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }

    #user-name {
      font-size: 14px;
      font-weight: bold;
      color: #333;
      text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
    }
  `;
  document.head.appendChild(style);
}

// Display user account information on page load (if available in localStorage)
window.onload = function () {
  const userName = localStorage.getItem('userName');
  const profilePic = localStorage.getItem('userProfilePic');

  // Delay the display of user profile for one second
  setTimeout(() => {
    if (userName && profilePic) {
      updateUserAccount(userName);
    }
  }, 1000);

  // Clear user data (for the next user)
  localStorage.removeItem('userName');
  localStorage.removeItem('userProfilePic');
};







// "Forgot Password" functionality
document.getElementById("forgot-password").addEventListener("click", function () {
  createModal(
    "Forgot Password",
    "Please enter your registered email to reset your password:",
    (email) => {
      if (email) {
        // Simulate API call for email verification
        setTimeout(() => {
          const isEmailValid = true; // Simulated email validation result
          if (isEmailValid) {
            createModal(
              "Set New Password",
              "Enter your new password:",
              (newPassword) => {
                if (newPassword) {
                  createModal(
                    "Confirm Password",
                    "Confirm your new password:",
                    (confirmPassword) => {
                      if (
                        newPassword &&
                        confirmPassword &&
                        newPassword === confirmPassword
                      ) {
                        // Simulate API call to reset password
                        setTimeout(() => {
                          showSuccessMessage("Your password has been reset successfully!");
                        }, 1000);
                      } else {
                        showErrorMessage("Passwords do not match. Please try again.");
                      }
                    },
                    true
                  );
                }
              },
              true
            );
          } else {
            showErrorMessage("No account found with this email. Please try again.");
          }
        }, 1000);
      }
    }
  );
});

// Success message modal
function showSuccessMessage(message) {
  const modal = document.createElement("div");
  modal.className = "custom-modal success";

  modal.innerHTML = `
    <div class="modal-content success-content">
      <h2>Success!</h2>
      <p>${message}</p>
      <button id="modal-close">Close</button>
    </div>
  `;

  document.body.appendChild(modal);

  document.getElementById("modal-close").addEventListener("click", () => {
    modal.remove();
  });
}

// Error message modal
function showErrorMessage(message) {
  const modal = document.createElement("div");
  modal.className = "custom-modal error";

  modal.innerHTML = `
    <div class="modal-content error-content">
      <h2>Error!</h2>
      <p>${message}</p>
      <button id="modal-close">Close</button>
    </div>
  `;

  document.body.appendChild(modal);

  document.getElementById("modal-close").addEventListener("click", () => {
    modal.remove();
  });
}






















// Close popup function
function closePopup() {
  document.getElementById('popup').style.display = 'none';
}












// Toggle password visibility
document.getElementById('toggle-password').addEventListener('click', function () {
  const passwordField = document.getElementById('password');
  const icon = this.querySelector('i');

  if (passwordField.type === 'password') {
    passwordField.type = 'text';
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
  } else {
    passwordField.type = 'password';
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
  }
});










