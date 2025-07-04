class AttendanceTracker {
  constructor() {
    this.apiBase = "../controllers/AttendanceController.php"
    this.currentStudents = []
    this.init()
  }

  init() {
    // Set default date to today
    document.getElementById("attendanceDate").value = new Date().toISOString().split("T")[0]

    // Set default date range for dashboard (1 week ago to today)
    const today = new Date()
    const oneWeekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000)

    document.getElementById("dateFrom").value = oneWeekAgo.toISOString().split("T")[0]
    document.getElementById("dateTo").value = today.toISOString().split("T")[0]

    // Load subjects
    this.loadSubjects()

    // Load dashboard data by default
    this.loadDashboardData()

    // Add search functionality
    document
      .getElementById("searchStudent")
      .addEventListener("input", this.debounce(this.filterDashboardResults.bind(this), 300))
  }

  async loadSubjects() {
    try {
      const response = await fetch(`${this.apiBase}?action=subjects`)
      const result = await response.json()

      if (result.success) {
        const subjectSelect = document.getElementById("subjectSelect")
        const dashboardSubject = document.getElementById("dashboardSubject")

        // Clear existing options
        subjectSelect.innerHTML = '<option value="">Choose a subject...</option>'
        dashboardSubject.innerHTML = '<option value="">All Subjects</option>'

        result.data.forEach((subject) => {
          const option1 = new Option(`${subject.subject_code} - ${subject.subject_name}`, subject.id)
          const option2 = new Option(`${subject.subject_code} - ${subject.subject_name}`, subject.id)

          subjectSelect.appendChild(option1)
          dashboardSubject.appendChild(option2)
        })
      }
    } catch (error) {
      this.showAlert("Error loading subjects: " + error.message, "danger")
    }
  }

  async loadStudents() {
    const subjectId = document.getElementById("subjectSelect").value
    const teacherName = document.getElementById("teacherName").value

    if (!subjectId) {
      this.showAlert("Please select a subject first", "warning")
      return
    }

    if (!teacherName.trim()) {
      this.showAlert("Please enter teacher name", "warning")
      return
    }

    this.showLoading(true)

    try {
      const response = await fetch(`${this.apiBase}?action=students&subject_id=${subjectId}`)
      const result = await response.json()

      if (result.success) {
        this.currentStudents = result.data
        this.renderStudentsTable()
        document.getElementById("studentsContainer").style.display = "block"
      } else {
        this.showAlert("Error loading students: " + result.error, "danger")
      }
    } catch (error) {
      this.showAlert("Error loading students: " + error.message, "danger")
    } finally {
      this.showLoading(false)
    }
  }

  renderStudentsTable() {
    const tbody = document.getElementById("studentsTableBody")
    tbody.innerHTML = ""

    this.currentStudents.forEach((student) => {
      const row = document.createElement("tr")
      row.innerHTML = `
                <td>${student.registration_number}</td>
                <td>${student.first_name} ${student.last_name}</td>
                <td>${student.email}</td>
                <td>
                    <div class="attendance-status">
                        <label>
                            <input type="radio" name="attendance_${student.id}" value="present" checked>
                            <span class="text-success">Present</span>
                        </label>
                        <label>
                            <input type="radio" name="attendance_${student.id}" value="absent">
                            <span class="text-danger">Absent</span>
                        </label>
                    </div>
                </td>
            `
      tbody.appendChild(row)
    })
  }

  markAllPresent() {
    this.currentStudents.forEach((student) => {
      const presentRadio = document.querySelector(`input[name="attendance_${student.id}"][value="present"]`)
      if (presentRadio) presentRadio.checked = true
    })
  }

  markAllAbsent() {
    this.currentStudents.forEach((student) => {
      const absentRadio = document.querySelector(`input[name="attendance_${student.id}"][value="absent"]`)
      if (absentRadio) absentRadio.checked = true
    })
  }

  async submitAttendance() {
    const subjectId = document.getElementById("subjectSelect").value
    const date = document.getElementById("attendanceDate").value
    const teacherName = document.getElementById("teacherName").value

    if (!subjectId || !date || !teacherName.trim()) {
      this.showAlert("Please fill in all required fields", "warning")
      return
    }

    const attendanceData = []

    this.currentStudents.forEach((student) => {
      const checkedRadio = document.querySelector(`input[name="attendance_${student.id}"]:checked`)
      if (checkedRadio) {
        attendanceData.push({
          student_id: student.id,
          status: checkedRadio.value,
        })
      }
    })

    if (attendanceData.length === 0) {
      this.showAlert("No attendance data to submit", "warning")
      return
    }

    this.showLoading(true)

    try {
      const response = await fetch(`${this.apiBase}?action=mark`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          subject_id: subjectId,
          date: date,
          attendance: attendanceData,
          marked_by: teacherName,
        }),
      })

      const result = await response.json()

      if (result.success) {
        this.showAlert("Attendance marked successfully!", "success")
        // Reset form
        document.getElementById("studentsContainer").style.display = "none"
        document.getElementById("subjectSelect").value = ""
        document.getElementById("teacherName").value = ""
      } else {
        this.showAlert("Error marking attendance: " + result.error, "danger")
      }
    } catch (error) {
      this.showAlert("Error marking attendance: " + error.message, "danger")
    } finally {
      this.showLoading(false)
    }
  }

  async loadDashboardData() {
    const subjectId = document.getElementById("dashboardSubject").value
    const dateFrom = document.getElementById("dateFrom").value
    const dateTo = document.getElementById("dateTo").value

    if (!dateFrom || !dateTo) {
      this.showAlert("Please select date range", "warning")
      return
    }

    this.showLoading(true)

    try {
      let url = `${this.apiBase}?action=stats&date_from=${dateFrom}&date_to=${dateTo}`
      if (subjectId) {
        url += `&subject_id=${subjectId}`
      }

      const response = await fetch(url)
      const result = await response.json()

      if (result.success) {
        this.renderDashboardTable(result.data)
      } else {
        this.showAlert("Error loading dashboard data: " + result.error, "danger")
      }
    } catch (error) {
      this.showAlert("Error loading dashboard data: " + error.message, "danger")
    } finally {
      this.showLoading(false)
    }
  }

  renderDashboardTable(data) {
    const tbody = document.getElementById("dashboardTableBody")
    tbody.innerHTML = ""

    if (data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" class="text-center">No data found for the selected criteria</td></tr>'
      return
    }

    data.forEach((record) => {
      const percentage = Number.parseFloat(record.attendance_percentage) || 0
      const statusClass = this.getAttendanceStatusClass(percentage)
      const statusText = this.getAttendanceStatusText(percentage)

      const row = document.createElement("tr")
      row.innerHTML = `
                <td>${record.registration_number}</td>
                <td>${record.first_name} ${record.last_name}</td>
                <td>${record.subject_code} - ${record.subject_name}</td>
                <td>${record.total_classes}</td>
                <td>${record.present_count}</td>
                <td>${percentage}%</td>
                <td><span class="status-badge ${statusClass}">${statusText}</span></td>
            `
      tbody.appendChild(row)
    })
  }

  filterDashboardResults() {
    const searchTerm = document.getElementById("searchStudent").value.toLowerCase()
    const rows = document.querySelectorAll("#dashboardTableBody tr")

    rows.forEach((row) => {
      const regNumber = row.cells[0]?.textContent.toLowerCase() || ""
      const studentName = row.cells[1]?.textContent.toLowerCase() || ""

      if (regNumber.includes(searchTerm) || studentName.includes(searchTerm)) {
        row.style.display = ""
      } else {
        row.style.display = "none"
      }
    })
  }

  getAttendanceStatusClass(percentage) {
    if (percentage >= 90) return "status-excellent"
    if (percentage >= 75) return "status-good"
    if (percentage >= 60) return "status-average"
    return "status-poor"
  }

  getAttendanceStatusText(percentage) {
    if (percentage >= 90) return "Excellent"
    if (percentage >= 75) return "Good"
    if (percentage >= 60) return "Average"
    return "Poor"
  }

  exportData() {
    const table = document.getElementById("dashboardTable")
    const rows = Array.from(table.querySelectorAll("tr"))

    let csv = ""
    rows.forEach((row) => {
      const cols = Array.from(row.querySelectorAll("th, td"))
      const rowData = cols.map((col) => `"${col.textContent.replace(/"/g, '""')}"`)
      csv += rowData.join(",") + "\n"
    })

    const blob = new Blob([csv], { type: "text/csv" })
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement("a")
    a.href = url
    a.download = `attendance_report_${new Date().toISOString().split("T")[0]}.csv`
    a.click()
    window.URL.revokeObjectURL(url)
  }

  showMarkAttendance() {
    document.getElementById("markAttendanceSection").style.display = "block"
    document.getElementById("dashboardSection").style.display = "none"

    // Update navbar
    document.querySelectorAll(".nav-link").forEach((link) => link.classList.remove("active"))
    document.querySelector('.nav-link[data-bs-target="#markAttendanceSection"]').classList.add("active")
  }

  showDashboard() {
    document.getElementById("markAttendanceSection").style.display = "none"
    document.getElementById("dashboardSection").style.display = "block"

    // Update navbar
    document.querySelectorAll(".nav-link").forEach((link) => link.classList.remove("active"))
    document.querySelector('.nav-link[data-bs-target="#dashboardSection"]').classList.add("active")

    // Load dashboard data
    this.loadDashboardData()
  }

  showLoading(show) {
    const modal = document.getElementById("loadingModal")
    if (show) {
      modal.style.display = "block"
    } else {
      modal.style.display = "none"
    }
  }

  showAlert(message, type) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll(".alert")
    existingAlerts.forEach((alert) => alert.remove())

    const alertDiv = document.createElement("div")
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`
    alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `

    document.querySelector(".container").insertBefore(alertDiv, document.querySelector(".container").firstChild)

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
      if (alertDiv.parentNode) {
        alertDiv.remove()
      }
    }, 5000)
  }

  debounce(func, wait) {
    let timeout
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout)
        func(...args)
      }
      clearTimeout(timeout)
      timeout = setTimeout(later, wait)
    }
  }
}

// Global functions for HTML onclick events
let app

document.addEventListener("DOMContentLoaded", () => {
  app = new AttendanceTracker()
})

function showMarkAttendance() {
  app.showMarkAttendance()
}

function showDashboard() {
  app.showDashboard()
}

function loadStudents() {
  app.loadStudents()
}

function markAllPresent() {
  app.markAllPresent()
}

function markAllAbsent() {
  app.markAllAbsent()
}

function submitAttendance() {
  app.submitAttendance()
}

function loadDashboardData() {
  app.loadDashboardData()
}

function exportData() {
  app.exportData()
}
