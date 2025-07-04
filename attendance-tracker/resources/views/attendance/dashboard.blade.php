@extends('layouts.app')

@section('title', 'Attendance Dashboard - University of Deakin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i>Attendance Dashboard
                </h4>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label for="subject-filter" class="form-label">
                            <i class="fas fa-book me-1"></i>Filter by Subject
                        </label>
                        <select class="form-select" id="subject-filter">
                            <option value="">All Subjects</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}">
                                    {{ $subject->subject_code }} - {{ $subject->subject_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="start-date" class="form-label">
                            <i class="fas fa-calendar me-1"></i>Start Date
                        </label>
                        <input type="date" class="form-control" id="start-date" 
                               value="{{ \Carbon\Carbon::now()->subWeek()->startOfDay()->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="end-date" class="form-label">
                            <i class="fas fa-calendar me-1"></i>End Date
                        </label>
                        <input type="date" class="form-control" id="end-date" 
                               value="{{ \Carbon\Carbon::now()->endOfDay()->format('Y-m-d') }}">
                    </div>
                </div>

                <!-- Error Message Area -->
                <div id="date-range-error" class="alert alert-danger d-none" role="alert">
                    Please select a date range within June 27, 2025, to July 4, 2025.
                </div>

                <!-- Export Button -->
                <div class="row mb-3">
                    <div class="col-12">
                        <button type="button" class="btn btn-success" id="export-data">
                            <i class="fas fa-download me-2"></i>Export to CSV
                        </button>
                        <small class="text-muted ms-2">Export filtered data to CSV file</small>
                    </div>
                </div>

                <!-- Display Exportable Data as Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="exportable-table">
                        <thead>
                            <tr>
                                <th>Registration Number</th>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Total Classes</th>
                                <th>Present Count</th>
                                <th>Attendance %</th>
                            </tr>
                        </thead>
                        <tbody id="exportable-tbody">
                            <tr><td colspan="6" class="text-muted">Loading data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    const defaultStartDate = new Date('2025-06-27T00:00:00+0530');
    const defaultEndDate = new Date('2025-07-04T23:59:59+0530');

    // Load and display exportable data
    function loadExportableData() {
        const subjectId = $('#subject-filter').val() || '';
        const startDate = new Date($('#start-date').val());
        const endDate = new Date($('#end-date').val());

        // Validate date range
        if (startDate < defaultStartDate || endDate > defaultEndDate) {
            $('#date-range-error').removeClass('d-none').text('Please select a date range within June 27, 2025, to July 4, 2025.');
            $('#exportable-tbody').html('<tr><td colspan="6" class="text-danger">Invalid date range selected.</td></tr>');
            return;
        } else {
            $('#date-range-error').addClass('d-none');
        }

        if (!startDate || !endDate) {
            showAlert('Please select both start and end dates', 'warning');
            return;
        }

        const $tbody = $('#exportable-tbody');
        $tbody.html('<tr><td colspan="6" class="text-muted">Loading data...</td></tr>');

        $.ajax({
            url: '/attendance/export',
            type: 'GET',
            data: {
                start_date: $('#start-date').val(),
                end_date: $('#end-date').val(),
                subject_id: subjectId
            },
            success: function(response) {
                // Check if response is a string (CSV) and parse it
                let data = [];
                if (typeof response === 'string') {
                    let lines = response.trim().split('\n');
                    if (lines.length > 1) {
                        let headers = lines[0].split(',');
                        for (let i = 1; i < lines.length; i++) {
                            let cols = lines[i].split(',');
                            if (cols.length === 7) {
                                data.push({
                                    registration_number: cols[0].trim(),
                                    student_name: cols[1].trim(),
                                    email: cols[2].trim(),
                                    subject: cols[3].trim(),
                                    total_classes: parseInt(cols[4].trim()) || 0,
                                    present_count: parseInt(cols[5].trim()) || 0,
                                    attendance_percentage: cols[6].trim().replace('%', '')
                                });
                            }
                        }
                    }
                } else if (response.error) {
                    $tbody.html('<tr><td colspan="6" class="text-danger">Error: ' + response.error + '</td></tr>');
                    return;
                } else if (Array.isArray(response)) {
                    data = response; // If modified to return JSON
                }

                if (data.length === 0) {
                    $tbody.html('<tr><td colspan="6" class="text-muted">No data available for the current filter...</td></tr>');
                    return;
                }

                $tbody.empty();
                data.forEach(function(row) {
                    const percentage = parseFloat(row.attendance_percentage) || 0;
                    let colorClass = 'text-danger';
                    if (percentage >= 90) colorClass = 'text-success';
                    else if (percentage >= 75) colorClass = 'text-primary';
                    else if (percentage >= 60) colorClass = 'text-warning';

                    $tbody.append(`
                        <tr>
                            <td><strong>${row.registration_number}</strong></td>
                            <td>${row.student_name}</td>
                            <td>${row.email}</td>
                            <td class="text-center">${row.total_classes}</td>
                            <td class="text-center">${row.present_count}</td>
                            <td class="text-center"><span class="${colorClass} fw-bold">${percentage.toFixed(2)}%</span></td>
                        </tr>
                    `);
                });
            },
            error: function(xhr, error, thrown) {
                console.error('Ajax Error:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error,
                    thrown: thrown
                });
                let errorMessage = 'Error loading data';
                if (xhr.status === 500) errorMessage = 'Server error occurred. Please check the logs.';
                else if (xhr.status === 0) errorMessage = 'Network error. Please check your connection.';
                $tbody.html('<tr><td colspan="6" class="text-danger">' + errorMessage + '</td></tr>');
            }
        });
    }

    // Initial load
    loadExportableData();

    // Refresh data when filters change
    $('#subject-filter, #start-date, #end-date').change(function() {
        console.log('Filter changed, reloading data...');
        loadExportableData();
    });

    // Export data to CSV
    $('#export-data').click(function() {
        const subjectId = $('#subject-filter').val();
        const startDate = new Date($('#start-date').val());
        const endDate = new Date($('#end-date').val());

        // Validate date range before export
        if (startDate < defaultStartDate || endDate > defaultEndDate) {
            showAlert('Please select a date range within June 27, 2025, to July 4, 2025.', 'warning');
            return;
        }

        if (!startDate || !endDate) {
            showAlert('Please select both start and end dates', 'warning');
            return;
        }

        const exportBtn = $(this);
        const originalText = exportBtn.html();
        exportBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Exporting...');

        let exportUrl = `/attendance/export?start_date=${$('#start-date').val()}&end_date=${$('#end-date').val()}`;
        if (subjectId) exportUrl += `&subject_id=${subjectId}`;

        const link = document.createElement('a');
        link.href = exportUrl;
        link.download = `attendance_report_${$('#start-date').val()}_to_${$('#end-date').val()}.csv`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        setTimeout(() => {
            exportBtn.prop('disabled', false).html(originalText);
            showAlert('Data exported successfully', 'success', 3000);
        }, 1000);
    });

    // Date validation
    $('#start-date, #end-date').change(function() {
        const startDate = new Date($('#start-date').val());
        const endDate = new Date($('#end-date').val());

        if (startDate > endDate) {
            showAlert('Start date cannot be later than end date', 'warning');
            $('#end-date').val($('#start-date').val());
        }
    });
});
</script>
@endsection