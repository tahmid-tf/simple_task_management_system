<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Manager (Kanban)</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- SortableJS (Drag & Drop) -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <style>
        body {
            background: #f5f7fb;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
        }

        .kanban-column {
            min-height: 400px;
            background: #e9ecef;
            padding: 15px;
            border-radius: 10px;
        }

        .task-card-item {
            background: #fff;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: grab;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .task-title {
            font-weight: 600;
        }

        .task-desc {
            font-size: 14px;
            color: #6c757d;
        }
    </style>
</head>

<body>

    <div class="container">

        <div class="page-header">
            <div>
                <h2>Task Management</h2>
                <p>Drag & drop tasks between columns</p>
            </div>

            <button class="btn btn-primary" id="addTaskBtn">
                <i class="bi bi-plus-lg"></i> Add Task
            </button>
        </div>

        <div class="row mt-4">

            <div class="col-md-4">
                <h5 class="text-warning">Pending</h5>
                <div class="kanban-column" data-status="pending" id="pendingTasks"></div>
            </div>

            <div class="col-md-4">
                <h5 class="text-info">In Progress</h5>
                <div class="kanban-column" data-status="in_progress" id="inProgressTasks"></div>
            </div>

            <div class="col-md-4">
                <h5 class="text-success">Completed</h5>
                <div class="kanban-column" data-status="completed" id="completedTasks"></div>
            </div>

        </div>

    </div>

    <script>
        $(document).ready(function() {

            // CSRF
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            // =========================
            // LOAD TASKS
            // =========================
            function loadTasks() {

                $('#pendingTasks, #inProgressTasks, #completedTasks').html('');

                $.get('/tasks/list', function(res) {

                    res.data.forEach(task => {

                        let card = `
                    <div class="task-card-item" data-id="${task.id}">
                        <div class="task-title">${task.title}</div>
                        <div class="task-desc">${task.description ?? ''}</div>

                        <div class="mt-2 d-flex gap-2">
                            <button class="btn btn-sm btn-warning editBtn">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger deleteBtn">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                `;

                        if (task.status === 'pending') {
                            $('#pendingTasks').append(card);
                        } else if (task.status === 'in_progress') {
                            $('#inProgressTasks').append(card);
                        } else {
                            $('#completedTasks').append(card);
                        }

                    });

                });

            }

            // =========================
            // DRAG & DROP
            // =========================
            function initDrag() {

                document.querySelectorAll('.kanban-column').forEach(column => {

                    new Sortable(column, {
                        group: 'tasks',
                        animation: 150,

                        onEnd: function(evt) {

                            let taskId = $(evt.item).data('id');
                            let newStatus = $(evt.to).data('status');

                            $.post(`/tasks/update/${taskId}`, {
                                status: newStatus
                            });

                        }
                    });

                });

            }

            // =========================
            // ADD TASK
            // =========================
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
                        const title = $('#title').val();
                        const description = $('#description').val();

                        if (!title || !description) {
                            Swal.showValidationMessage('All fields required');
                            return false;
                        }

                        return {
                            title: title,
                            description: description,
                            status: $('#status').val()
                        };
                    }
                }).then(result => {

                    if (result.isConfirmed) {

                        $.post('/tasks', result.value, function() {
                            loadTasks();
                            Swal.fire('Success', 'Task created', 'success');
                        });

                    }

                });

            });

            // =========================
            // DELETE
            // =========================
            $(document).on('click', '.deleteBtn', function() {

                let card = $(this).closest('.task-card-item');
                let id = card.data('id');

                Swal.fire({
                    title: 'Delete?',
                    icon: 'warning',
                    showCancelButton: true
                }).then(result => {

                    if (result.isConfirmed) {

                        $.ajax({
                            url: `/tasks/${id}`,
                            type: 'DELETE',
                            success: function() {
                                card.remove();
                                Swal.fire('Deleted!', '', 'success');
                            }
                        });

                    }

                });

            });

            // =========================
            // EDIT
            // =========================
            $(document).on('click', '.editBtn', function() {

                let card = $(this).closest('.task-card-item');
                let id = card.data('id');

                let title = card.find('.task-title').text();
                let description = card.find('.task-desc').text();

                Swal.fire({
                    title: 'Edit Task',
                    html: `
                <input id="title" class="swal2-input" value="${title}">
                <textarea id="description" class="swal2-textarea">${description}</textarea>
                <select id="status" class="swal2-select">
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                </select>
            `,
                    confirmButtonText: 'Update',
                    showCancelButton: true,
                    preConfirm: () => {

                        const t = $('#title').val();
                        const d = $('#description').val();

                        if (!t || !d) {
                            Swal.showValidationMessage('All fields required');
                            return false;
                        }

                        return {
                            title: t,
                            description: d,
                            status: $('#status').val()
                        };
                    }
                }).then(result => {

                    if (result.isConfirmed) {

                        $.post(`/tasks/update/${id}`, result.value, function() {
                            loadTasks();
                            Swal.fire('Updated!', '', 'success');
                        });

                    }

                });

            });

            // INIT
            loadTasks();
            initDrag();

        });
    </script>

</body>

</html>
