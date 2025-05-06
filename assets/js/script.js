// Wait for the DOM to be fully loaded
document.addEventListener("DOMContentLoaded", () => {
    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll(".alert")
    alerts.forEach((alert) => {
      setTimeout(() => {
        const closeButton = alert.querySelector(".btn-close")
        if (closeButton) {
          closeButton.click()
        }
      }, 5000)
    })
  
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    if (typeof bootstrap === "undefined") {
      bootstrap = window.bootstrap // Assign the global bootstrap object
    }
    if (typeof bootstrap !== "undefined") {
      tooltipTriggerList.map((tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl))
    }
  
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    if (typeof bootstrap === "undefined") {
      bootstrap = window.bootstrap // Assign the global bootstrap object
    }
    if (typeof bootstrap !== "undefined") {
      popoverTriggerList.map((popoverTriggerEl) => new bootstrap.Popover(popoverTriggerEl))
    }
  
    // Add active class to current nav item
    const currentLocation = window.location.href
    const navLinks = document.querySelectorAll(".nav-link")
    navLinks.forEach((link) => {
      if (currentLocation.includes(link.href)) {
        link.classList.add("active")
      }
    })
  
    // Form validation
    const forms = document.querySelectorAll(".needs-validation")
    Array.from(forms).forEach((form) => {
      form.addEventListener(
        "submit",
        (event) => {
          if (!form.checkValidity()) {
            event.preventDefault()
            event.stopPropagation()
          }
          form.classList.add("was-validated")
        },
        false,
      )
    })
  
    // Bug status change handler
    const statusSelect = document.getElementById("status")
    if (statusSelect) {
      statusSelect.addEventListener("change", function () {
        const assignedToField = document.getElementById("assigned-to-field")
        if (this.value === "assigned" && assignedToField) {
          assignedToField.classList.remove("d-none")
        } else if (assignedToField) {
          assignedToField.classList.add("d-none")
        }
      })
    }
  
    // GitHub repository selection handler
    const repoSelect = document.getElementById("repository")
    if (repoSelect) {
      repoSelect.addEventListener("change", function () {
        const repoInfo = document.getElementById("repo-info")
        if (this.value && repoInfo) {
          repoInfo.classList.remove("d-none")
        } else if (repoInfo) {
          repoInfo.classList.add("d-none")
        }
      })
    }
  
    // Comment form toggle
    const commentToggle = document.getElementById("comment-toggle")
    if (commentToggle) {
      commentToggle.addEventListener("click", function () {
        const commentForm = document.getElementById("comment-form")
        if (commentForm) {
          commentForm.classList.toggle("d-none")
          this.textContent = commentForm.classList.contains("d-none") ? "Add Comment" : "Cancel"
        }
      })
    }
  
    // File input preview
    const fileInput = document.getElementById("screenshot")
    if (fileInput) {
      fileInput.addEventListener("change", function () {
        const previewContainer = document.getElementById("file-preview")
        if (previewContainer) {
          previewContainer.innerHTML = ""
  
          if (this.files && this.files[0]) {
            const file = this.files[0]
            const reader = new FileReader()
  
            reader.onload = (e) => {
              const preview = document.createElement("div")
              preview.classList.add("mt-2")
  
              if (file.type.startsWith("image/")) {
                const img = document.createElement("img")
                img.src = e.target.result
                img.classList.add("img-thumbnail", "mt-2")
                img.style.maxHeight = "200px"
                preview.appendChild(img)
              }
  
              const fileInfo = document.createElement("p")
              fileInfo.classList.add("mb-0", "mt-1")
              fileInfo.textContent = `File: ${file.name} (${(file.size / 1024).toFixed(2)} KB)`
              preview.appendChild(fileInfo)
  
              previewContainer.appendChild(preview)
            }
  
            reader.readAsDataURL(file)
          }
        }
      })
    }
  
    // Search functionality
    const searchInput = document.getElementById("search-input")
    if (searchInput) {
      searchInput.addEventListener("keyup", function () {
        const searchTerm = this.value.toLowerCase()
        const tableRows = document.querySelectorAll("tbody tr")
  
        tableRows.forEach((row) => {
          const text = row.textContent.toLowerCase()
          if (text.includes(searchTerm)) {
            row.style.display = ""
          } else {
            row.style.display = "none"
          }
        })
      })
    }
  
    // Print bug details
    const printButton = document.getElementById("print-bug")
    if (printButton) {
      printButton.addEventListener("click", () => {
        window.print()
      })
    }
  })
  