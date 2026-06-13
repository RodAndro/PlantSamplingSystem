// Event Management System JavaScript

document.addEventListener("DOMContentLoaded", () => {
  // Initialize tooltips
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  var tooltipList = tooltipTriggerList.map((tooltipTriggerEl) => new window.bootstrap.Tooltip(tooltipTriggerEl))

  // Form validation
  const forms = document.querySelectorAll(".needs-validation")
  Array.from(forms).forEach((form) => {
    form.addEventListener("submit", (event) => {
      if (!form.checkValidity()) {
        event.preventDefault()
        event.stopPropagation()
      }
      form.classList.add("was-validated")
    })
  })

  // Initialize registration buttons
  initializeRegistrationButtons()

  // Search functionality
  const searchInput = document.getElementById("eventSearch")
  if (searchInput) {
    searchInput.addEventListener("input", function () {
      filterEvents(this.value)
    })
  }
})

function initializeRegistrationButtons() {
  // Use event delegation to handle dynamically created buttons
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('register-btn')) {
      e.preventDefault()
      const eventId = e.target.dataset.eventId
      if (eventId) {
        registerForEvent(eventId, e.target)
      }
    }

    if (e.target.classList.contains('unregister-btn')) {
      e.preventDefault()
      const eventId = e.target.dataset.eventId
      if (eventId) {
        unregisterFromEvent(eventId, e.target)
      }
    }
  })

  // Check registration status for all events on page load (only for event listing pages)
  if (document.querySelectorAll("[data-event-id]").length > 1) {
    checkAllRegistrationStatuses()
  }
}

function checkAllRegistrationStatuses() {
  const eventCards = document.querySelectorAll("[data-event-id]")
  eventCards.forEach((card) => {
    const eventId = card.dataset.eventId
    if (eventId) {
      checkRegistrationStatus(eventId)
    }
  })
}

function checkRegistrationStatus(eventId) {
  fetch(`api/check_registration_status.php?event_id=${eventId}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        updateRegistrationButtons(eventId, data)
      }
    })
    .catch((error) => {
      console.error("Error checking registration status:", error)
    })
}

function updateRegistrationButtons(eventId, statusData) {
  const eventCard = document.querySelector(`[data-event-id="${eventId}"]`)
  if (!eventCard) return

  const buttonContainer = eventCard.querySelector(".registration-buttons")
  if (!buttonContainer) return

  // Clear existing buttons
  const existingButtons = buttonContainer.querySelectorAll('.register-btn, .unregister-btn')
  existingButtons.forEach(btn => btn.remove())

  // Create appropriate button based on status
  let newButton = null

  if (statusData.can_register) {
    newButton = createRegisterButton(eventId)
  } else if (statusData.can_unregister) {
    newButton = createUnregisterButton(eventId)
  } else if (statusData.is_registered && statusData.is_past) {
    newButton = createDisabledButton("Event Completed", "btn-secondary")
  } else if (statusData.is_full) {
    newButton = createDisabledButton("Event Full", "btn-warning")
  } else if (statusData.event_status !== 'approved') {
    newButton = createDisabledButton("Pending Approval", "btn-outline-secondary")
  }

  if (newButton) {
    buttonContainer.appendChild(newButton)
  }
}

function createRegisterButton(eventId) {
  const button = document.createElement("button")
  button.className = "btn btn-primary btn-lg w-100 register-btn"
  button.type = "button"
  button.dataset.eventId = eventId
  button.innerHTML = '<i class="fas fa-user-plus me-2"></i>Register'
  return button
}

function createUnregisterButton(eventId) {
  const button = document.createElement("button")
  button.className = "btn btn-outline-danger btn-lg w-100 unregister-btn"
  button.type = "button"
  button.dataset.eventId = eventId
  button.innerHTML = '<i class="fas fa-user-minus me-2"></i>Unregister'
  return button
}

function createDisabledButton(text, className) {
  const button = document.createElement("button")
  button.className = `btn ${className} btn-lg w-100`
  button.type = "button"
  button.disabled = true
  button.innerHTML = text
  return button
}

function registerForEvent(eventId, button) {
  // Prevent multiple clicks
  if (button.disabled) return

  // Show confirmation dialog
  if (!confirm("Register for this event?")) {
    return
  }

  const originalText = button.innerHTML
  const originalClass = button.className

  button.innerHTML = 'Registering...'
  button.disabled = true
  button.className = 'btn btn-secondary btn-lg w-100'

  fetch("api/register_event.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      event_id: parseInt(eventId),
    }),
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error('Network response was not ok')
      }
      return response.json()
    })
    .then((data) => {
      if (data.success) {
        showAlert("Registered successfully!", "success")

        // Update registration count and available spots
        updateRegistrationCount(eventId, 1)

        // Update registration badge if on event details page
        updateRegistrationBadge(eventId, true)

        // Replace with unregister button
        replaceButtonWithUnregister(eventId, button)

      } else {
        throw new Error(data.message || "Failed to register for event")
      }
    })
    .catch((error) => {
      console.error("Registration error:", error)
      showAlert(error.message || "An error occurred while registering", "danger")

      // Restore original button
      button.innerHTML = originalText
      button.className = originalClass
      button.disabled = false

      // If user needs to login, redirect to login page
      if (error.message && error.message.includes("log in")) {
        setTimeout(() => {
          window.location.href = "login.php?error=3"
        }, 2000)
      }
    })
}

function unregisterFromEvent(eventId, button) {
  // Prevent multiple clicks
  if (button.disabled) return

  // Show confirmation dialog
  if (!confirm("Are you sure you want to unregister from this event?")) {
    return
  }

  const originalText = button.innerHTML
  const originalClass = button.className

  button.innerHTML = 'Unregistering...'
  button.disabled = true
  button.className = 'btn btn-secondary btn-lg w-100'

  fetch("api/unregister_event.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      event_id: parseInt(eventId),
    }),
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error('Network response was not ok')
      }
      return response.json()
    })
    .then((data) => {
      if (data.success) {
        showAlert("Unregistered successfully!", "success")

        // Update registration count and available spots
        updateRegistrationCount(eventId, -1)

        // Update registration badge if on event details page
        updateRegistrationBadge(eventId, false)

        // Replace with register button
        replaceButtonWithRegister(eventId, button)

      } else {
        throw new Error(data.message || "Failed to unregister from event")
      }
    })
    .catch((error) => {
      console.error("Unregistration error:", error)
      showAlert(error.message || "An error occurred while unregistering", "danger")

      // Restore original button
      button.innerHTML = originalText
      button.className = originalClass
      button.disabled = false
    })
}

function replaceButtonWithUnregister(eventId, currentButton) {
  const buttonContainer = currentButton.parentNode
  const newButton = createUnregisterButton(eventId)

  // Remove current button and add new one
  currentButton.remove()
  buttonContainer.appendChild(newButton)
}

function replaceButtonWithRegister(eventId, currentButton) {
  const buttonContainer = currentButton.parentNode
  const newButton = createRegisterButton(eventId)

  // Remove current button and add new one
  currentButton.remove()
  buttonContainer.appendChild(newButton)
}

function updateRegistrationCount(eventId, change) {
  const eventCard = document.querySelector(`[data-event-id="${eventId}"]`)
  if (!eventCard) return

  // Update all registration count elements
  const countElements = eventCard.querySelectorAll(".registration-count")
  let newCount = 0

  countElements.forEach(countElement => {
    const currentCount = parseInt(countElement.textContent) || 0
    newCount = Math.max(0, currentCount + change)
    countElement.textContent = newCount
  })

  // Update available spots if present
  const availableSpotsElements = eventCard.querySelectorAll(".available-spots")
  availableSpotsElements.forEach(availableSpotsElement => {
    const maxAttendees = eventCard.dataset.maxAttendees
    if (maxAttendees && maxAttendees !== 'null' && maxAttendees !== 'undefined') {
      const available = Math.max(0, parseInt(maxAttendees) - newCount)
      availableSpotsElement.textContent = available
    }
  })

  // Also update any other registration count displays on the page
  updateGlobalRegistrationCounts(eventId, newCount)
}

function updateGlobalRegistrationCounts(eventId, newCount) {
  // Update registration counts in other parts of the page (like event listings)
  const allEventElements = document.querySelectorAll(`[data-event-id="${eventId}"]`)

  allEventElements.forEach(eventElement => {
    // Update registration count displays
    const countElements = eventElement.querySelectorAll('.registration-count')
    countElements.forEach(element => {
      element.textContent = newCount
    })

    // Update "X registered" text displays
    const registeredTexts = eventElement.querySelectorAll('.registered-text')
    registeredTexts.forEach(element => {
      element.textContent = `${newCount} registered`
    })

    // Update available spots
    const availableElements = eventElement.querySelectorAll('.available-spots')
    availableElements.forEach(element => {
      const maxAttendees = eventElement.dataset.maxAttendees
      if (maxAttendees && maxAttendees !== 'null') {
        const available = Math.max(0, parseInt(maxAttendees) - newCount)
        element.textContent = available
      }
    })
  })
}

function updateRegistrationBadge(eventId, isRegistered) {
  // Update registration badge if on event details page
  const badge = document.querySelector('.user-registration-badge')

  if (isRegistered && !badge) {
    const badgeContainer = document.querySelector('.d-flex.justify-content-center.gap-2.mb-3')
    if (badgeContainer) {
      const newBadge = document.createElement('span')
      newBadge.className = 'badge bg-success user-registration-badge'
      newBadge.textContent = "You're Registered"
      badgeContainer.appendChild(newBadge)
    }
  } else if (!isRegistered && badge) {
    badge.remove()
  }
}

function showAlert(message, type) {
  const alertContainer = document.getElementById("alertContainer") || createAlertContainer()

  const alert = document.createElement("div")
  alert.className = `alert alert-${type} alert-dismissible fade show`
  alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `

  alertContainer.appendChild(alert)

  // Auto remove after 5 seconds
  setTimeout(() => {
    if (alert.parentNode) {
      alert.remove()
    }
  }, 5000)
}

function createAlertContainer() {
  const container = document.createElement("div")
  container.id = "alertContainer"
  container.style.position = "fixed"
  container.style.top = "20px"
  container.style.right = "20px"
  container.style.zIndex = "9999"
  container.style.maxWidth = "400px"
  document.body.appendChild(container)
  return container
}

function filterEvents(searchTerm) {
  const eventCards = document.querySelectorAll(".event-card")
  const searchLower = searchTerm.toLowerCase()

  eventCards.forEach((card) => {
    const title = card.querySelector(".card-title")?.textContent.toLowerCase() || ""
    const description = card.querySelector(".card-text")?.textContent.toLowerCase() || ""
    const location = card.querySelector(".event-location")?.textContent.toLowerCase() || ""

    if (title.includes(searchLower) || description.includes(searchLower) || location.includes(searchLower)) {
      const col = card.closest(".col")
      if (col) col.style.display = "block"
    } else {
      const col = card.closest(".col")
      if (col) col.style.display = "none"
    }
  })
}

function confirmDelete(eventId, eventTitle, registrationCount) {
  let message = `Are you sure you want to delete the event "${eventTitle}"?`

  if (registrationCount > 0) {
    message += `\n\nWarning: This event has ${registrationCount} registration(s). Deleting it will also remove all registrations and notify registered users.`
  }

  message += "\n\nThis action cannot be undone."

  if (confirm(message)) {
    deleteEvent(eventId)
  }
}

function deleteEvent(eventId) {
  // Find and disable the delete button
  const deleteButton = document.querySelector(`button[onclick*="confirmDelete(${eventId}"]`)
  if (deleteButton) {
    deleteButton.disabled = true
    deleteButton.innerHTML = 'Deleting...'
  }

  fetch("api/delete_event.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ event_id: parseInt(eventId) }),
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error('Network response was not ok')
      }
      return response.json()
    })
    .then((data) => {
      if (data.success) {
        showAlert(data.message, "success")

        // Remove the event card from the page with animation
        const eventCard = document.querySelector(`[data-event-id="${eventId}"]`)
        if (eventCard) {
          const cardContainer = eventCard.closest(".col-lg-4, .col-md-6, .col-lg-6, .col")
          if (cardContainer) {
            cardContainer.style.transition = "opacity 0.3s ease"
            cardContainer.style.opacity = "0"
            setTimeout(() => {
              cardContainer.remove()

              // Check if no events left
              const remainingCards = document.querySelectorAll("[data-event-id]")
              if (remainingCards.length === 0) {
                setTimeout(() => location.reload(), 1000)
              }
            }, 300)
          }
        } else {
          // If card not found, reload page
          setTimeout(() => location.reload(), 1500)
        }
      } else {
        throw new Error(data.message || "Failed to delete event")
      }
    })
    .catch((error) => {
      console.error("Delete error:", error)
      showAlert(error.message || "An error occurred while deleting the event", "danger")

      // Re-enable the button
      if (deleteButton) {
        deleteButton.disabled = false
        deleteButton.innerHTML = '<i class="fas fa-trash me-1"></i>Delete'
      }
    })
}