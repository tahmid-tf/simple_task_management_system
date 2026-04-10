<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Manager</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">

    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="{{ asset('css/task.css') }}">

</head>

<body>

    <div class="container page-shell">
        <div class="task-card">
            <div class="page-header">
                <div>
                    <h2 class="page-title">Task Management</h2>
                    <p class="page-subtitle">Organize, track, and update your work from one clean dashboard.</p>
                </div>

                <button class="btn btn-primary add-task-btn" id="addTaskBtn">
                    <i class="bi bi-plus-lg"></i> Add Task
                </button>
            </div>

            <table class="table align-middle" id="taskTable">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th width="140">Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <script>
        $(document).ready(function() {

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            let table = $('#taskTable').DataTable({
                ajax: '/tasks/list',
                columns: [{
                        data: 'title'
                    },
                    {
                        data: 'description'
                    },
                    {
                        data: 'status',
                        render: function(data) {
                            let color = 'secondary';
                            let label = data.replace('_', ' ');

                            if (data === 'pending') color = 'warning';
                            if (data === 'in_progress') color = 'info';
                            if (data === 'completed') color = 'success';

                            return `<span class="badge bg-${color} status-badge">${label}</span>`;
                        }
                    },
                    {
                        data: null,
                        render: function() {
                            return `
                                <div class="action-group">
                                    <button class="icon-action editBtn" type="button" title="Edit task" aria-label="Edit task">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="icon-action deleteBtn" type="button" title="Delete task" aria-label="Delete task">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>
                            `;
                        }
                    }
                ]
            });

            $('#addTaskBtn').click(function() {

                Swal.fire({
                    title: 'Add Task',
                    html: `
                        <input id="title" class="swal2-input" placeholder="Title">
                        <textarea id="description" class="swal2-textarea" placeholder="Description"></textarea>
                        <select id="status" class="swal2-select">
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                    `,
                    confirmButtonText: 'Save',
                    showCancelButton: true,
                    preConfirm: () => {
                        return {
                            title: $('#title').val(),
                            description: $('#description').val(),
                            status: $('#status').val()
                        };
                    }
                }).then((result) => {

                    if (result.isConfirmed) {

                        $.post('/tasks', result.value, function() {
                            table.ajax.reload();
                            Swal.fire('Success', 'Task created', 'success');
                        });

                    }

                });

            });

            $('#taskTable').on('click', '.deleteBtn', function() {

                let row = $(this).closest('tr');
                let data = table.row(row).data();

                Swal.fire({
                    title: 'Delete?',
                    text: 'This cannot be undone',
                    icon: 'warning',
                    showCancelButton: true
                }).then((result) => {

                    if (result.isConfirmed) {

                        $.ajax({
                            url: `/tasks/${data.id}`,
                            type: 'DELETE',
                            success: function() {
                                table.row(row).remove().draw();
                                Swal.fire('Deleted!', '', 'success');
                            }
                        });

                    }

                });

            });

            $('#taskTable').on('click', '.editBtn', function() {

                let row = $(this).closest('tr');
                let data = table.row(row).data();

                Swal.fire({
                    title: 'Edit Task',
                    html: `
                        <input id="title" class="swal2-input" value="${data.title}">
                        <textarea id="description" class="swal2-textarea">${data.description ?? ''}</textarea>
                        <select id="status" class="swal2-select">
                            <option value="pending" ${data.status=='pending'?'selected':''}>Pending</option>
                            <option value="in_progress" ${data.status=='in_progress'?'selected':''}>In Progress</option>
                            <option value="completed" ${data.status=='completed'?'selected':''}>Completed</option>
                        </select>
                    `,
                    confirmButtonText: 'Update',
                    showCancelButton: true,
                    preConfirm: () => {
                        return {
                            title: $('#title').val(),
                            description: $('#description').val(),
                            status: $('#status').val()
                        };
                    }
                }).then((result) => {

                    if (result.isConfirmed) {

                        $.post(`/tasks/update/${data.id}`, result.value, function() {
                            table.ajax.reload();
                            Swal.fire('Updated!', '', 'success');
                        });

                    }

                });

            });

        });
    </script>

</body>

</html>
