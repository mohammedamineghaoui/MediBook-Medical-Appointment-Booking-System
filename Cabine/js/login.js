// Login/Register Page JavaScript

// Check if register tab should be active
/*const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get("register") === "true") {
  switchToRegister();
}*/

// Tab switching
const loginTab = document.getElementById("loginTab"); // value by id
const registerTab = document.getElementById("registerTab");
const loginForm = document.getElementById("loginForm");
const registerForm = document.getElementById("registerForm");
loginTab.addEventListener("click", () => {
  switchToLogin();
});

registerTab.addEventListener("click", () => {
  switchToRegister();
});

function switchToLogin() {
  loginTab.classList.add("active");
  registerTab.classList.remove("active");
  loginForm.classList.add("active");
  registerForm.classList.remove("active");
}

function switchToRegister() {
  registerTab.classList.add("active");
  loginTab.classList.remove("active");
  registerForm.classList.add("active");
  loginForm.classList.remove("active");
}

// Toggle password visibility
function togglePassword(inputId) {
  const input = document.getElementById(inputId);
  const button = input.nextElementSibling;
  const icon = button.querySelector("i");

  if (input.type === "password") {
    input.type = "text";
    icon.classList.remove("fa-eye");
    icon.classList.add("fa-eye-slash");
  } else {
    input.type = "password";
    icon.classList.remove("fa-eye-slash");
    icon.classList.add("fa-eye");
  }
}
