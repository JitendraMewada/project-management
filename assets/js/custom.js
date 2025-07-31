// Custom JavaScript for Interior Project Management System

// Global variables
let currentUser = null;
let notificationInterval = null;

// Initialize application
document.addEventListener("DOMContentLoaded", function () {
  initializeApp();
});

function initializeApp() {
  // Load user preferences
  loadUserPreferences();

  // Initialize components
  initializeComponents();

  // Start background processes
  startBackgroundProcesses();

  // Set up event listeners
  setupEventListeners();
}

function loadUserPreferences() {
  // Load theme preference
  const savedTheme = localStorage.getItem("theme") || "light";
  document.body.setAttribute("data-theme", savedTheme);

  // Load sidebar state
  const sidebarState = localStorage.getItem("sidebarCollapsed");
  if (sidebarState === "true") {
    document.querySelector(".sidebar")?.classList.add("collapsed");
  }
}

function initializeComponents() {
  // Initialize Bootstrap components
  initializeBootstrapComponents();

  // Initialize custom components
  initializeCustomComponents();
}

function initializeBootstrapComponents() {
  // Initialize tooltips
  const tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // Initialize popovers
  const popoverTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="popover"]')
  );
  popoverTriggerList.map(function (popoverTriggerEl) {
    return new bootstrap.Popover(popoverTriggerEl);
  });

  // Initialize modals
  const modalList = [].slice.call(document.querySelectorAll(".modal"));
  modalList.map(function (modalEl) {
    return new bootstrap.Modal(modalEl);
  });
}

function initializeCustomComponents() {
  // Initialize progress bars with animation
  const progressBars = document.querySelectorAll(".progress-bar[data-animate]");
  progressBars.forEach((bar) => {
    const targetValue = parseInt(bar.getAttribute("aria-valuenow"));
    animateProgressBar(bar, targetValue);
  });

  // Initialize data tables
  initializeDataTables();

  // Initialize form validation
  initializeFormValidation();
}

function startBackgroundProcesses() {
  // Start notification checking
  startNotificationCheck();

  // Start session monitoring
  startSessionMonitoring();
}

function setupEventListeners() {
  // Sidebar toggle
  const sidebarToggle = document.querySelector(".sidebar-toggle");
  if (sidebarToggle) {
    sidebarToggle.addEventListener("click", toggleSidebar);
  }

  // Theme toggle
  const themeToggle = document.querySelector(".theme-toggle");
  if (themeToggle) {
    themeToggle.addEventListener("click", toggleTheme);
  }

  // Delete confirmations
  const deleteButtons = document.querySelectorAll(".delete-btn");
  deleteButtons.forEach((btn) => {
    btn.addEventListener("click", confirmDelete);
  });

  // Form submissions
  const forms = document.querySelectorAll("form[data-validate]");
  forms.forEach((form) => {
    form.addEventListener("submit", validateForm);
  });

  // Auto-save functionality
  const autoSaveForms = document.querySelectorAll("form[data-autosave]");
  autoSaveForms.forEach((form) => {
    setupAutoSave(form);
  });
}

// Theme management
function toggleTheme() {
  const body = document.body;
  const currentTheme = body.getAttribute("data-theme");
  const newTheme = currentTheme === "dark" ? "light" : "dark";

  body.setAttribute("data-theme", newTheme);
  localStorage.setItem("theme", newTheme);

  // Update theme toggle icon
  updateThemeToggleIcon(newTheme);
}

function updateThemeToggleIcon(theme) {
  const themeIcon = document.querySelector(".theme-toggle i");
  if (themeIcon) {
    themeIcon.className = theme === "dark" ? "fas fa-sun" : "fas fa-moon";
  }
}

// Sidebar management
function toggleSidebar() {
  const sidebar = document.querySelector(".sidebar");
  const mainContent = document.querySelector(".main-content");

  if (sidebar && mainContent) {
    sidebar.classList.toggle("collapsed");
    mainContent.classList.toggle("expanded");

    // Save state
    const isCollapsed = sidebar.classList.contains("collapsed");
    localStorage.setItem("sidebarCollapsed", isCollapsed);
  }
}

// Progress bar animation
function animateProgressBar(element, targetValue) {
  let currentValue = 0;
  const increment = targetValue / 100;
  const duration = 1000; // 1 second
  const stepTime = duration / 100;

  const timer = setInterval(() => {
    currentValue += increment;
    if (currentValue >= targetValue) {
      currentValue = targetValue;
      clearInterval(timer);
    }

    element.style.width = currentValue + "%";
    element.setAttribute("aria-valuenow", currentValue);

    // Update text if present
    const text = element.querySelector(".progress-text");
    if (text) {
      text.textContent = Math.round(currentValue) + "%";
    }
  }, stepTime);
}

// Notification system
function startNotificationCheck() {
  // Check notifications every 30 seconds
  notificationInterval = setInterval(checkNotifications, 30000);

  // Initial check
  checkNotifications();
}

function checkNotifications() {
  fetch("/project-management/api/notifications.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        updateNotificationBadge(data.count);
        if (data.notifications && data.notifications.length > 0) {
          updateNotificationDropdown(data.notifications);
        }
      }
    })
    .catch((error) => {
      console.log("Notification check failed:", error);
    });
}

function updateNotificationBadge(count) {
  const badge = document.querySelector(".notification-badge");
  if (badge) {
    badge.textContent = count > 99 ? "99+" : count;
    badge.style.display = count > 0 ? "inline-block" : "none";
  }
}

function updateNotificationDropdown(notifications) {
  const dropdown = document.querySelector(".notification-dropdown");
  if (dropdown) {
    dropdown.innerHTML = "";

    notifications.forEach((notification) => {
      const item = createNotificationItem(notification);
      dropdown.appendChild(item);
    });
  }
}

function createNotificationItem(notification) {
  const item = document.createElement("div");
  item.className = "dropdown-item notification-item";
  item.innerHTML = `
        <div class="d-flex">
            <div class="notification-icon">
                <i class="fas ${notification.icon} text-${notification.type}"></i>
            </div>
            <div class="notification-content">
                <div class="notification-title">${notification.title}</div>
                <div class="notification-message">${notification.message}</div>
                <div class="notification-time">${notification.time}</div>
            </div>
        </div>
    `;

  // Add click handler
  item.addEventListener("click", () => {
    markNotificationAsRead(notification.id);
    if (notification.url) {
      window.location.href = notification.url;
    }
  });

  return item;
}

function markNotificationAsRead(notificationId) {
  fetch("/project-management/api/notifications.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      action: "mark_read",
      id: notificationId,
    }),
  });
}

// Session management
function startSessionMonitoring() {
  const sessionTimeout = 1800000; // 30 minutes
  const warningTime = 300000; // 5 minutes before timeout

  setTimeout(() => {
    showSessionWarning();
  }, sessionTimeout - warningTime);

  setTimeout(() => {
    handleSessionTimeout();
  }, sessionTimeout);
}

function showSessionWarning() {
  const modal = new bootstrap.Modal(
    document.getElementById("sessionWarningModal")
  );
  modal.show();
}

function handleSessionTimeout() {
  alert("Your session has expired. You will be redirected to the login page.");
  window.location.href = "../auth/login.php";
}

function extendSession() {
  fetch("../api/extend-session.php", {
    method: "POST",
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Restart session monitoring
        startSessionMonitoring();
      }
    });
}

// Form validation
function initializeFormValidation() {
  const forms = document.querySelectorAll(".needs-validation");
  forms.forEach((form) => {
    form.addEventListener("submit", function (event) {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add("was-validated");
    });
  });
}

function validateForm(event) {
  const form = event.target;
  const isValid = form.checkValidity();

  if (!isValid) {
    event.preventDefault();
    event.stopPropagation();

    // Focus on first invalid field
    const firstInvalid = form.querySelector(":invalid");
    if (firstInvalid) {
      firstInvalid.focus();
    }
  }

  form.classList.add("was-validated");
  return isValid;
}

// Auto-save functionality
function setupAutoSave(form) {
  const inputs = form.querySelectorAll("input, textarea, select");
  inputs.forEach((input) => {
    input.addEventListener(
      "input",
      debounce(() => {
        autoSaveForm(form);
      }, 2000)
    );
  });
}

function autoSaveForm(form) {
  const formData = new FormData(form);
  formData.append("auto_save", "1");

  fetch(form.action || window.location.href, {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showAutoSaveIndicator();
      }
    })
    .catch((error) => {
      console.log("Auto-save failed:", error);
    });
}

function showAutoSaveIndicator() {
  const indicator = document.createElement("div");
  indicator.className = "auto-save-indicator";
  indicator.innerHTML = '<i class="fas fa-check"></i> Auto-saved';
  document.body.appendChild(indicator);

  setTimeout(() => {
    indicator.remove();
  }, 2000);
}

// Data tables
function initializeDataTables() {
  const tables = document.querySelectorAll(".data-table");
  tables.forEach((table) => {
    // Add search functionality
    addTableSearch(table);

    // Add sorting functionality
    addTableSorting(table);

    // Add pagination
    addTablePagination(table);
  });
}

function addTableSearch(table) {
  const searchInput = table.parentNode.querySelector(".table-search");
  if (searchInput) {
    searchInput.addEventListener(
      "input",
      debounce((e) => {
        filterTable(table, e.target.value);
      }, 300)
    );
  }
}

function filterTable(table, searchTerm) {
  const rows = table.querySelectorAll("tbody tr");
  const term = searchTerm.toLowerCase();

  rows.forEach((row) => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(term) ? "" : "none";
  });
}

// Utility functions
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

function confirmDelete(event) {
  if (
    !confirm(
      "Are you sure you want to delete this item? This action cannot be undone."
    )
  ) {
    event.preventDefault();
    return false;
  }
  return true;
}

function showToast(message, type = "info") {
  const toast = document.createElement("div");
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `
        <div class="toast-header">
            <i class="fas fa-info-circle"></i>
            <strong class="me-auto">Notification</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">${message}</div>
    `;

  document.body.appendChild(toast);
  const bsToast = new bootstrap.Toast(toast);
  bsToast.show();

  toast.addEventListener("hidden.bs.toast", () => {
    toast.remove();
  });
}

// Export functions
function exportTable(format, tableId) {
  const table = document.getElementById(tableId);
  if (!table) return;

  switch (format.toLowerCase()) {
    case "csv":
      exportToCSV(table);
      break;
    case "excel":
      exportToExcel(table);
      break;
    case "pdf":
      exportToPDF(table);
      break;
    default:
      showToast("Export format not supported", "error");
  }
}

function exportToCSV(table) {
  const rows = table.querySelectorAll("tr");
  const csvContent = Array.from(rows)
    .map((row) =>
      Array.from(row.querySelectorAll("th, td"))
        .map((cell) => `"${cell.textContent.replace(/"/g, '""')}"`)
        .join(",")
    )
    .join("\n");

  downloadFile(csvContent, "table-export.csv", "text/csv");
}

function downloadFile(content, filename, contentType) {
  const blob = new Blob([content], { type: contentType });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  window.URL.revokeObjectURL(url);
}

// Print functions
function printPage() {
  window.print();
}

function printElement(elementId) {
  const element = document.getElementById(elementId);
  if (element) {
    const printWindow = window.open("", "_blank");
    printWindow.document.write(`
            <html>
                <head>
                    <title>Print</title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        table { border-collapse: collapse; width: 100%; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                    </style>
                </head>
                <body>
                    ${element.outerHTML}
                </body>
            </html>
        `);
    printWindow.document.close();
    printWindow.print();
  }
}

// Global error handler
window.addEventListener("error", function (event) {
  console.error("JavaScript error:", event.error);
  // Optionally send error to server for logging
});

// Page visibility change handler
document.addEventListener("visibilitychange", function () {
  if (document.hidden) {
    // Page is hidden, pause notifications
    if (notificationInterval) {
      clearInterval(notificationInterval);
    }
  } else {
    // Page is visible, resume notifications
    startNotificationCheck();
  }
});
