<?= $this->extend('admin/layout/default') ?>
<?= $this->section('content') ?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Ismétlődő feladatok</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= url('/') ?>">
                            <?= lang('App.home') ?>
                        </a></li>
                    <li class="breadcrumb-item"><a href="<?= url('it_tickets') ?>">Bejelentések</a></li>
                    <li class="breadcrumb-item active">Ismétlődő feladatok</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="card">
        <div class="card-header with-border">
            <h3 class="card-title">Ismétlődő feladatok</h3>
            <div class="card-tools pull-right">
                <?php if (hasPermissions('manage_it_tickets')): ?>
                    <a href="#recurringModal" data-toggle="modal" data-target="#recurringModal" id="btnOpenRecurringModal"
                        class="btn btn-default btn-sm">
                        <i class="fa fa-plus pr-1"></i> Új ismétlődő feladat
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-body">
            <table id="recurringTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Név sablon</th>
                        <th>Terület</th>
                        <th>Kategória</th>
                        <th>Gyakoriság</th>
                        <th>Ütemezés</th>
                        <th>Ettől</th>
                        <th>Eddig</th>
                        <th>Állapot</th>
                        <th>Utolsó futás</th>
                        <th width="180">Műveletek</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</section>

<div class="modal fade" id="recurringModal" role="dialog" data-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ismétlődő feladat</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <?= form_open('it_tickets/saveRecurringTask', ['id' => 'recurringForm']) ?>
            <?= csrf_field() ?>

            <div class="modal-body">
                <input type="hidden" name="id" id="recurring-id">

                <div class="form-group">
                    <label for="recurring-name-template" class="required">Feladat megnevezése sablon</label>
                    <input type="text" name="name_template" id="recurring-name-template" class="form-control" required>
                    <small class="text-muted">Pl.: Fluktuációszámítás - {prev_month_name}</small>
                </div>

                <div class="form-group">
                    <label for="recurring-description-template">Leírás sablon</label>
                    <textarea name="description_template" id="recurring-description-template" rows="4"
                        class="form-control"></textarea>
                </div>

                <div class="form-group">
                    <label for="recurring-area" class="required">Terület</label>
                    <select name="area" id="recurring-area" class="form-control" required></select>
                </div>

                <div class="form-group">
                    <label for="recurring-category" class="required">Kategória</label>
                    <select name="category" id="recurring-category" class="form-control" required></select>
                </div>

                <div class="form-group">
                    <label for="recurring-participants">Résztvevők</label>
                    <select name="participants[]" id="recurring-participants" class="form-control" multiple></select>
                </div>

                <div class="form-group">
                    <label for="recurring-frequency" class="required">Gyakoriság</label>
                    <select name="frequency" id="recurring-frequency" class="form-control" required>
                        <option value="daily">Napi</option>
                        <option value="weekly">Heti</option>
                        <option value="monthly">Havi</option>
                    </select>
                </div>

                <div class="form-group" id="group-day-of-week" style="display:none;">
                    <label for="recurring-day-of-week">Hét napja</label>
                    <select name="day_of_week" id="recurring-day-of-week" class="form-control">
                        <option value="">-- Válassz --</option>
                        <option value="1">Hétfő</option>
                        <option value="2">Kedd</option>
                        <option value="3">Szerda</option>
                        <option value="4">Csütörtök</option>
                        <option value="5">Péntek</option>
                        <option value="6">Szombat</option>
                        <option value="7">Vasárnap</option>
                    </select>
                </div>

                <div class="form-group" id="group-day-of-month" style="display:none;">
                    <label for="recurring-day-of-month">Hónap napja</label>
                    <input type="number" min="1" max="31" name="day_of_month" id="recurring-day-of-month"
                        class="form-control">
                    <small class="text-muted">Ha a nap nem létezik az adott hónapban, a hónap utolsó napján jön
                        létre.</small>
                </div>

                <div class="form-group">
                    <label for="recurring-start-date" class="required">Aktív ettől</label>
                    <input type="date" name="start_date" id="recurring-start-date" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="recurring-end-date">Aktív eddig</label>
                    <input type="date" name="end_date" id="recurring-end-date" class="form-control">
                </div>

                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="recurring-is-active" class="form-check-input"
                            value="1" checked>
                        <label class="form-check-label" for="recurring-is-active">Aktív</label>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-default">Mentés</button>
            </div>

            <?= form_close() ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const areaSelect = $("#recurring-area");
        const categorySelect = $("#recurring-category");
        const participantsSelect = $("#recurring-participants");

        function resetRecurringModal() {
            $('#recurringForm')[0].reset();
            $('#recurring-id').val('');

            areaSelect.val(null).trigger('change');
            categorySelect.val(null).trigger('change');
            participantsSelect.val(null).trigger('change');

            $('#recurring-is-active').prop('checked', true);
            $('#recurring-frequency').val('daily').trigger('change');
        }

        function setSelect2Value($select, value, text) {
            if (!value) return;
            const option = new Option(text, value, true, true);
            $select.append(option).trigger('change');
        }

        function toggleRecurringFields() {
            const frequency = $('#recurring-frequency').val();

            $('#group-day-of-week').hide();
            $('#group-day-of-month').hide();

            if (frequency === 'weekly') {
                $('#group-day-of-week').show();
            }

            if (frequency === 'monthly') {
                $('#group-day-of-month').show();
            }
        }

        areaSelect.select2({
            placeholder: "Terület kiválasztása...",
            allowClear: true,
            ajax: {
                url: '<?= base_url("it_tickets/getAreas") ?>',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term || '' };
                },
                processResults: function (data) {
                    return { results: data.results };
                }
            },
            dropdownParent: $('#recurringModal')
        });

        categorySelect.select2({
            placeholder: "Kategória kiválasztása...",
            allowClear: true,
            ajax: {
                url: '<?= base_url("it_tickets/getCategoriesByArea") ?>',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        areaId: areaSelect.val(),
                        q: params.term || ''
                    };
                },
                processResults: function (data) {
                    return { results: data.results };
                }
            },
            dropdownParent: $('#recurringModal')
        });

        participantsSelect.select2({
            placeholder: "Résztvevők kiválasztása...",
            multiple: true,
            allowClear: true,
            ajax: {
                url: '<?= base_url("it_tickets/getUsers") ?>',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term || '' };
                },
                processResults: function (data) {
                    return { results: data.results };
                }
            },
            templateResult: function (user) {
                if (!user.id) return user.text;
                return $(`<span>${user.text}</span> <small>(${user.antraid})</small>`);
            },
            templateSelection: function (user) {
                if (!user.id) return user.text;
                return user.antraid ? `${user.text} (${user.antraid})` : user.text;
            },
            dropdownParent: $('#recurringModal')
        });

    areaSelect.on("change", function () {
        categorySelect.val(null).trigger("change");
    });

    $('#recurring-frequency').on('change', toggleRecurringFields);
    toggleRecurringFields();

    $('#btnOpenRecurringModal').on('click', function () {
        resetRecurringModal();
        $('#recurringModal .modal-title').text('Új ismétlődő feladat');
    });

    $('#recurringModal').on('hidden.bs.modal', function () {
        resetRecurringModal();
    });

    var table = $("#recurringTable").DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        language: { url: '//cdn.datatables.net/plug-ins/1.11.4/i18n/hu.json' },
        ajax: {
            url: "<?= url('it_tickets/recurringDatatable') ?>",
            type: "POST",
            data: function (d) {
                d["<?= csrf_token() ?>"] = "<?= csrf_hash() ?>";
                return d;
            }
        },
        columns: [
            { data: 'name_template' },
            { data: 'area_name' },
            { data: 'category_name' },
            { data: 'frequency' },
            { data: 'schedule_value' },
            { data: 'start_date' },
            { data: 'end_date' },
            { data: 'is_active' },
            { data: 'last_run' },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });

    $("#recurringForm").validate({
        submitHandler: function () {
            $.ajax({
                data: $('#recurringForm').serialize(),
                url: $('#recurringForm').attr('action'),
                method: "POST",
                dataType: 'json',
                success: function (res) {
                    if (res.status === 'success') {
                        $('#recurringModal').modal('hide');
                        Swal.fire({
                            text: res.message,
                            icon: 'success'
                        }).then(() => table.ajax.reload(null, false));
                    } else {
                        Swal.fire({
                            text: res.message || 'Mentési hiba történt.',
                            icon: 'error'
                        });
                    }
                },
                error: function (xhr) {
                    let msg = 'Szerverhiba történt.';
                    if (xhr.responseJSON?.errors) {
                        msg = Object.values(xhr.responseJSON.errors).join("\n");
                    } else if (xhr.responseJSON?.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire({ text: msg, icon: 'error' });
                }
            });
        }
    });

    $('body').on('click', '.btnEditRecurring', function () {
        const id = $(this).data('id');

        $.ajax({
            url: '<?= base_url() ?>/it_tickets/getRecurringTask/' + id,
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                if (!res.status || !res.data) return;

                const task = res.data;
                resetRecurringModal();

                $('#recurringModal .modal-title').text('Ismétlődő feladat szerkesztése');
                $('#recurring-id').val(task.id);
                $('#recurring-name-template').val(task.name_template || '');
                $('#recurring-description-template').val(task.description_template || '');
                $('#recurring-frequency').val(task.frequency || 'daily').trigger('change');
                $('#recurring-day-of-week').val(task.day_of_week || '');
                $('#recurring-day-of-month').val(task.day_of_month || '');
                $('#recurring-start-date').val(task.start_date || '');
                $('#recurring-end-date').val(task.end_date || '');
                $('#recurring-is-active').prop('checked', parseInt(task.is_active) === 1);

                if (task.area) {
                    setSelect2Value(areaSelect, task.area, task.area_name || ('#' + task.area));
                }

                if (task.category) {
                    setSelect2Value(categorySelect, task.category, task.category_name || ('#' + task.category));
                }

                if (Array.isArray(task.participants) && task.participants.length > 0) {
                    $.ajax({
                        url: '<?= base_url("it_tickets/getUsers") ?>',
                        type: 'GET',
                        dataType: 'json',
                        data: { q: '' },
                        success: function (userRes) {
                            if (!userRes.results) return;

                            task.participants.forEach(function (participantId) {
                                const found = userRes.results.find(x => String(x.id) === String(participantId));
                                if (found) {
                                    const option = new Option(
                                        found.text,
                                        found.id,
                                        true,
                                        true
                                    );

                                    $(option).data('data', {
                                        id: found.id,
                                        text: found.text,
                                        antraid: found.antraid
                                    });

                                    participantsSelect.append(option);
                                }
                            });

                            participantsSelect.trigger('change');
                        }
                    });
                }

                $('#recurringModal').modal('show');
            }
        });
    });

    $('body').on('click', '.btnDeleteRecurring', function () {
        const id = $(this).data('id');

        Swal.fire({
            text: 'Biztosan törlöd az ismétlődő feladatot?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Igen',
            cancelButtonText: 'Mégse'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: '<?= base_url() ?>/it_tickets/deleteRecurringTask/' + id,
                type: 'POST',
                dataType: 'json',
                data: {
                    "<?= csrf_token() ?>": "<?= csrf_hash() ?>"
                },
                success: function (res) {
                    Swal.fire({
                        text: res.message,
                        icon: res.status === 'success' ? 'success' : 'error'
                    }).then(() => table.ajax.reload(null, false));
                }
            });
        });
    });

    $('body').on('click', '.btnRunRecurring', function () {
        const id = $(this).data('id');

        $.ajax({
            url: '<?= base_url() ?>/it_tickets/runRecurringTaskNow/' + id,
            type: 'POST',
            dataType: 'json',
            data: {
                "<?= csrf_token() ?>": "<?= csrf_hash() ?>"
            },
            success: function (res) {
                Swal.fire({
                    text: res.message,
                    icon: res.status === 'success' ? 'success' : (res.status === 'warning' ? 'warning' : 'error')
                }).then(() => table.ajax.reload(null, false));
            }
        });
    });
    });
</script>
<?= $this->endSection() ?>