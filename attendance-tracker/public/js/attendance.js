$(document).ready(() => {
    let currentStudents = [];

    // Load students when button is clicked
    $("#load-students").click(() => {
        const subjectId = $("#subject-select").val();
        const date = $("#attendance-date").val();
        const markedBy = $("#marked-by").val();

        if (!subjectId) {
            alert("Please select a subject");
            return;
        }

        if (!markedBy.trim()) {
            alert("Please enter teacher name");
            return;
        }

        loadStudents(subjectId, date);
    });

    function loadStudents(subjectId, date) {
        $(".loading").show();
        $("#students-container").hide();

        $.ajax({
            url: "{{ route('attendance.students') }}", // Use named route
            method: "GET",
            data: {
                subject_id: subjectId,
                date: date,
            },
            success: (response) => {
                if (response.success) {
                    currentStudents = response.students;
                    renderStudentsTable();
                } else {
                    alert("Error: " + (response.error || "Failed to load students"));
                }
                $(".loading").hide();
                $("#students-container").show();
            },
            error: (xhr) => {
                $(".loading").hide();
                alert("Error loading students: " + (xhr.responseJSON?.error || "Unknown error"));
            },
        });
    }

    function renderStudentsTable() {
        const tbody = $("#students-table-body");
        tbody.empty();

        currentStudents.forEach((student) => {
            const presentChecked = student.current_attendance === true ? "checked" : "";
            const absentChecked = student.current_attendance === false ? "checked" : "";
            const defaultChecked = student.current_attendance === null ? "checked" : "";

            const row = `
                <tr data-student-id="${student.id}">
                    <td>${student.registration_number}</td>
                    <td>${student.first_name} ${student.last_name}</td>
                    <td>
                        <div class="attendance-status">
                            <label>
                                <input type="radio" name="attendance_${student.id}" 
                                       value="1" ${presentChecked || defaultChecked}>
                                <span class="text-success">Present</span>
                            </label>
                            <label>
                                <input type="radio" name="attendance_${student.id}" 
                                       value="0" ${absentChecked}>
                                <span class="text-danger">Absent</span>
                            </label>
                        </div>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm" 
                               name="remarks_${student.id}" placeholder="Optional remarks" 
                               value="${student.current_attendance === false ? student.remarks || '' : ''}">
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // Mark all present
    $("#mark-all-present").click(() => {
        $('input[type="radio"][value="1"]').prop("checked", true);
    });

    // Mark all absent
    $("#mark-all-absent").click(() => {
        $('input[type="radio"][value="0"]').prop("checked", true);
    });

    // Save attendance
    $("#save-attendance").click(() => {
        const subjectId = $("#subject-select").val();
        const date = $("#attendance-date").val();
        const markedBy = $("#marked-by").val();

        if (!subjectId || !date || !markedBy.trim()) {
            alert("Please fill in all required fields");
            return;
        }

        const attendanceData = [];

        currentStudents.forEach((student) => {
            const present = $(`input[name="attendance_${student.id}"]:checked`).val();
            const remarks = $(`input[name="remarks_${student.id}"]`).val();

            attendanceData.push({
                student_id: student.id,
                present: present === "1",
                remarks: remarks || null,
            });
        });

        // Show loading
        const saveBtn = $("#save-attendance");
        const originalText = saveBtn.html();
        saveBtn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...');

        $.ajax({
            url: "{{ route('attendance.store') }}", // Use named route
            method: "POST",
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'), // CSRF protection
                subject_id: subjectId,
                attendance_date: date,
                marked_by: markedBy,
                attendance: attendanceData,
            },
            success: (response) => {
                if (response.success) {
                    alert("Attendance saved successfully!");
                    // Reset form
                    $("#students-container").hide();
                    $("#subject-select").val("");
                    $("#attendance-date").val("");
                    $("#marked-by").val("");
                    currentStudents = [];
                    $("#students-table-body").empty();
                } else {
                    alert("Error: " + (response.error || "Failed to save attendance"));
                }
            },
            error: (xhr) => {
                alert("Error saving attendance: " + (xhr.responseJSON?.error || "Unknown error"));
            },
            complete: () => {
                saveBtn.prop("disabled", false).html(originalText);
            },
        });
    });
});