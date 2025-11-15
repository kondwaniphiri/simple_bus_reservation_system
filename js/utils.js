// Utility functions for the Bus Reservation System

// Show toast notification
function showToast(message, type = "info") {
  const toast = document.createElement("div");
  toast.className = `toast ${type}`;
  toast.textContent = message;
  document.body.appendChild(toast);

  setTimeout(() => {
    toast.remove();
  }, 4000);
}

// Show loading overlay
function showLoading() {
  let overlay = document.querySelector(".loading-overlay");
  if (!overlay) {
    overlay = document.createElement("div");
    overlay.className = "loading-overlay";
    overlay.innerHTML = '<div class="spinner"></div>';
    document.body.appendChild(overlay);
  }
  overlay.classList.add("active");
}

// Hide loading overlay
function hideLoading() {
  const overlay = document.querySelector(".loading-overlay");
  if (overlay) {
    overlay.classList.remove("active");
  }
}

// Format date to readable format
function formatDate(dateString) {
  const date = new Date(dateString);
  const options = { year: "numeric", month: "long", day: "numeric" };
  return date.toLocaleDateString("en-US", options);
}

// Format time to 12-hour format
function formatTime(timeString) {
  const [hours, minutes] = timeString.split(":");
  const hour = parseInt(hours);
  const ampm = hour >= 12 ? "PM" : "AM";
  const hour12 = hour % 12 || 12;
  return `${hour12}:${minutes} ${ampm}`;
}

// Validate email
function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

// Check if user is logged in
function checkAuth() {
  const username = localStorage.getItem("username");
  const userId = localStorage.getItem("userId");

  if (!username || !userId) {
    window.location.href = "index.html";
    return false;
  }
  return true;
}

// Logout function
function logout() {
  localStorage.removeItem("username");
  localStorage.removeItem("userId");
  window.location.href = "index.html";
}

// Confirm action with custom styling
function confirmAction(message) {
  return confirm(message);
}

// Print reservation ticket
function printTicket(reservationData) {
  const printWindow = window.open("", "_blank");
  printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Bus Ticket - Reservation #${reservationData.reservationId}</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    padding: 20px;
                    max-width: 600px;
                    margin: 0 auto;
                }
                .ticket {
                    border: 2px solid #711313;
                    padding: 20px;
                    border-radius: 10px;
                }
                .header {
                    text-align: center;
                    color: #711313;
                    border-bottom: 2px solid #711313;
                    padding-bottom: 10px;
                    margin-bottom: 20px;
                }
                .row {
                    display: flex;
                    justify-content: space-between;
                    margin: 10px 0;
                    padding: 5px 0;
                }
                .label {
                    font-weight: bold;
                    color: #711313;
                }
                .barcode {
                    text-align: center;
                    margin-top: 20px;
                    font-family: 'Courier New', monospace;
                    font-size: 24px;
                    letter-spacing: 2px;
                }
                @media print {
                    button { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="ticket">
                <div class="header">
                    <h1>Hard Work Logistics</h1>
                    <h3>Bus Ticket</h3>
                </div>
                <div class="row">
                    <span class="label">Reservation ID:</span>
                    <span>${reservationData.reservationId}</span>
                </div>
                <div class="row">
                    <span class="label">Passenger:</span>
                    <span>${reservationData.username}</span>
                </div>
                <div class="row">
                    <span class="label">Date:</span>
                    <span>${reservationData.date}</span>
                </div>
                <div class="row">
                    <span class="label">Route:</span>
                    <span>${reservationData.origin} → ${reservationData.destination}</span>
                </div>
                <div class="row">
                    <span class="label">Departure Time:</span>
                    <span>${reservationData.time}</span>
                </div>
                <div class="row">
                    <span class="label">Seat Number:</span>
                    <span style="font-size: 24px; font-weight: bold; color: #711313;">${reservationData.seat}</span>
                </div>
                <div class="barcode">
                    ||||| ${reservationData.reservationId} |||||
                </div>
                <p style="text-align: center; margin-top: 20px; font-size: 12px; color: #666;">
                    Please arrive 30 minutes before departure time.<br>
                    Keep this ticket for boarding.
                </p>
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <button onclick="window.print()" style="padding: 10px 20px; background-color: #711313; color: white; border: none; border-radius: 5px; cursor: pointer;">Print Ticket</button>
                <button onclick="window.close()" style="padding: 10px 20px; background-color: #999; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">Close</button>
            </div>
        </body>
        </html>
    `);
  printWindow.document.close();
}
