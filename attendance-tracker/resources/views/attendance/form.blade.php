@extends('layouts.app')

@section('title', 'Mark Attendance - University of Deakin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="fas fa-check-circle me-2"></i>Mark Daily Attendance
                </h4>
            </div>
            <div class="card-body">
                <form id="attendance-form">
                    @csrf
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label for="department-select" class="form-label">
                                <i class="fas fa-building me-1"></i>Department *
                            </label>
                            <select class="form-select" id="department-select" required>
                                <option value="">Choose a department...</option>
                                <option value="Business">Business</option>
                                <option value="IT">IT</option>
                                <option value="Science">Science</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="subject-select" class="form-label">
                                <i class="fas fa-book me-1"></i>Select Subject *
                            </label>
                            <select class="form-select" id="subject-select" required>
                                <option value="">Choose a subject...</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="attendance-date" class="form-label">
                                <i class="fas fa-calendar me-1"></i>Date *
                            </label>
                            <input type="date" class="form-control" id="attendance-date" 
                                   value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label for="teacher-select" class="form-label">
                                <i class="fas fa-user me-1"></i>Teacher *
                            </label>
                            <select class="form-select" id="teacher-select" required>
                                <option value="">Select teacher...</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher }}">{{ $teacher }}</option>
                                @endforeach
                                <option value="other">Other (type name)</option>
                            </select>
                            <input type="text" class="form-control mt-2" id="teacher-custom" 
                                   placeholder="Enter teacher name" style="display: none;">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"> </label>
                            <div class="d-grid">
                                <button type="button" class="btn btn-primary" id="load-students">
                                    <i class="fas fa-users me-2"></i>Load Students
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Students Container -->
                <div id="students-container" class="mt-4" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i>Students List
                            <span id="students-count" class="badge bg-secondary ms-2"></span>
                        </h5>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-success btn-sm" id="mark-all-present">
                                <i class="fas fa-check-double me-1"></i>All Present
                            </button>
                            <button type="button" class="btn btn-warning btn-sm" id="mark-all-absent">
                                <i class="fas fa-times-circle me-1"></i>All Absent
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th width="15%">Registration Number</th>
                                    <th width="25%">Student Name</th>
                                    <th width="30%">Attendance Status</th>
                                    <th width="30%">Remarks</th>
                                </tr>
                            </thead>
                            <tbody id="students-table-body">
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 text-center">
                        <button type="button" class="btn btn-primary btn-lg" id="save-attendance">
                            <i class="fas fa-save me-2"></i>Save Attendance
                        </button>
                    </div>
                </div>

                <!-- Loading State -->
                <div class="loading text-center py-5" id="loading-students" style="display: none;">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted">Loading students...</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let currentStudents = [];
    let currentSubjectId = null;
    let currentDate = null;

    // Handle teacher dropdown change
    $('#teacher-select').change(function() {
        const selectedValue = $(this).val();
        if (selectedValue === 'other') {
            $('#teacher-custom').show().attr('required', true);
            $('#teacher-select').removeAttr('required');
        } else {
            $('#teacher-custom').hide().removeAttr('required');
            $('#teacher-select').attr('required', true);
        }
        updateLoadButtonState();
    });

    // Handle custom teacher input
    $('#teacher-custom').on('input', function() {
        updateLoadButtonState();
    });

    // Get selected teacher name
    function getSelectedTeacher() {
        const teacherSelect = $('#teacher-select').val();
        if (teacherSelect === 'other') {
            return $('#teacher-custom').val().trim();
        }
        return teacherSelect;
    }

    // Update load button state
    function updateLoadButtonState() {
        const subjectId = $('#subject-select').val();
        const date = $('#attendance-date').val();
        const teacher = getSelectedTeacher();
        
        $('#load-students').prop('disabled', !(subjectId && date && teacher));
    }

    // Load subjects based on department
    $('#department-select').change(function() {
        const department = $(this).val();
        if (!department) {
            $('#subject-select').html('<option value="">Choose a subject...</option>');
            return;
        }

        $.ajax({
            url: '/attendance/subjects-by-department',
            method: 'GET',
            data: { department: department },
            success: function(response) {
                if (response.success) {
                    const $select = $('#subject-select').html('<option value="">Choose a subject...</option>');
                    response.subjects.forEach(subject => {
                        $select.append(`<option value="${subject.id}">${subject.subject_code} - ${subject.subject_name}</option>`);
                    });
                } else {
                    showAlert('Error loading subjects: ' + response.error, 'danger');
                }
            },
            error: function(xhr) {
                showAlert('Failed to load subjects', 'danger');
            }
        });
        updateLoadButtonState();
    });

    // Load students when button is clicked
    $('#load-students').click(function() {
        const subjectId = $('#subject-select').val();
        const date = $('#attendance-date').val();
        const teacher = getSelectedTeacher();

        if (!subjectId || !date || !teacher) {
            showAlert('Please fill in all required fields', 'warning');
            return;
        }

        loadStudents(subjectId, date);
    });

    // Load students function
    function loadStudents(subjectId, date) {
        $('#loading-students').show();
        $('#students-container').hide();
        
        currentSubjectId = subjectId;
        currentDate = date;

        $.ajax({
            url: '/attendance/students',
            method: 'GET',
            data: {
                subject_id: subjectId,
                date: date
            },
            success: function(response) {
                if (response.success) {
                    currentStudents = response.students;
                    renderStudentsTable();
                    $('#students-count').text(currentStudents.length + ' students');
                    $('#loading-students').hide();
                    $('#students-container').show();
                    
                    showAlert(`Loaded ${currentStudents.length} students successfully`, 'success');
                } else {
                    showAlert('Error: ' + response.error, 'danger');
                    $('#loading-students').hide();
                }
            },
            error: function(xhr) {
                let errorMessage = 'Failed to load students';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage = xhr.responseJSON.error;
                }
                showAlert(errorMessage, 'danger');
                $('#loading-students').hide();
            }
        });
    }

    // Render students table
    function renderStudentsTable() {
        const tbody = $('#students-table-body');
        tbody.empty();

        if (currentStudents.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        <i class="fas fa-users fa-2x mb-2"></i><br>
                        No students enrolled in this subject
                    </td>
                </tr>
            `);
            return;
        }

        currentStudents.forEach(function(student) {
            const presentChecked = student.current_attendance === true ? 'checked' : '';
            const absentChecked = student.current_attendance === false ? 'checked' : '';
            const defaultChecked = student.current_attendance === null ? 'checked' : '';

            const row = `
                <tr data-student-id="${student.id}">
                    <td>
                        <strong>${student.registration_number}</strong>
                    </td>
                    <td>
                        ${student.first_name} ${student.last_name}
                        <br><small class="text-muted">${student.email}</small>
                    </td>
                    <td>
                        <div class="attendance-status">
                            <label class="text-success">
                                <input type="radio" name="attendance_${student.id}" 
                                       value="1" ${presentChecked || defaultChecked}>
                                <i class="fas fa-check-circle me-1"></i>Present
                            </label>
                            <label class="text-danger">
                                <input type="radio" name="attendance_${student.id}" 
                                       value="0" ${absentChecked}>
                                <i class="fas fa-times-circle me-1"></i>Absent
                            </label>
                        </div>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm" 
                               name="remarks_${student.id}" 
                               placeholder="Optional remarks"
                               value="${student.current_remarks || ''}"
                               maxlength="500">
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // Mark all present
    $('#mark-all-present').click(function() {
        $('input[type="radio"][value="1"]').prop('checked', true);
        showAlert('All students marked as present', 'info', 2000);
    });

    // Mark all absent
    $('#mark-all-absent').click(function() {
        $('input[type="radio"][value="0"]').prop('checked', true);
        showAlert('All students marked as absent', 'info', 2000);
    });

    // Save attendance
    $('#save-attendance').click(function() {
        const teacher = getSelectedTeacher();

        if (!currentSubjectId || !currentDate || !teacher) {
            showAlert('Please fill in all required fields', 'warning');
            return;
        }

        if (currentStudents.length === 0) {
            showAlert('No students to save attendance for', 'warning');
            return;
        }

        const attendanceData = [];

        currentStudents.forEach(function(student) {
            const presentRadio = $(`input[name="attendance_${student.id}"]:checked`);
            const remarks = $(`input[name="remarks_${student.id}"]`).val().trim();

            if (presentRadio.length > 0) {
                const isPresent = presentRadio.val() === '1';
                attendanceData.push({
                    student_id: parseInt(student.id),
                    present: isPresent,
                    remarks: remarks || null
                });
            }
        });

        if (attendanceData.length === 0) {
            showAlert('Please mark attendance for at least one student', 'warning');
            return;
        }

        const saveBtn = $('#save-attendance');
        const originalText = saveBtn.html();
        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...');

        $.ajax({
            url: '/attendance/store',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({
                subject_id: parseInt(currentSubjectId),
                attendance_date: currentDate,
                marked_by: teacher,
                attendance: attendanceData
            }),
            success: function(response) {
                if (response.success) {
                    showAlert(response.message, 'success');
                    $('#students-container').hide();
                    $('#department-select').val('');
                    $('#subject-select').html('<option value="">Choose a subject...</option>');
                    $('#teacher-select').val('');
                    $('#teacher-custom').hide().val('');
                    $('#attendance-date').val(new Date().toISOString().split('T')[0]);
                    currentStudents = [];
                    currentSubjectId = null;
                    currentDate = null;
                    updateLoadButtonState();
                } else {
                    showAlert('Error: ' + response.error, 'danger');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Failed to save attendance';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage = xhr.responseJSON.error;
                }
                showAlert(errorMessage, 'danger');
            },
            complete: function() {
                saveBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Form validation on input change
    $('#department-select, #subject-select, #attendance-date').on('change', updateLoadButtonState);

    // Initialize form state
    updateLoadButtonState();
});
</script>
@endsection