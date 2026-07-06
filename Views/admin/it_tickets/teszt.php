<?= $this->extend('admin/layout/default') ?>
<?= $this->section('content') ?>

<style>
    .custom-file-input~.custom-file-label::after {

        content: "Tallózás";
    }
</style>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Bejelentések</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo url('/') ?>"><?php echo lang('App.home') ?></a></li>
                    <li class="breadcrumb-item active">Bejelentések</li>
                </ol>
            </div>
        </div>
    </div><!-- /.container-fluid -->
</section>


<section class="content">
    <div class="row">
        <div class="col-12">
            <?php echo form_open('it_tickets', ['method' => 'POST', 'class' => ' border border-gray-300 pt-3 px-3 mb-3 bg-gray-200', 'id' => 'itTicketsFilter', 'autocomplete' => 'off']); ?>
            <div class="row">

                <div class="col-md-2">

                    <div class="form-row  py-2">
                        <div class="col-12 mb-2 font-size-sm"><label for="status">Státusz</label></div>
                        <div class="input-group col-md-12 col-12 ">
                            <select class="form-control form-control-sm select2" id="status" name="status[]"
                                data-placeholder="Állapot kiválasztása..." multiple>
                                <option <?= isset($filters['status']) && in_array('planned', $filters['status']) ? 'selected' : '' ?> value="planned">tervezett</option>
                                <option <?= isset($filters['status']) && in_array('todo', $filters['status']) ? 'selected' : '' ?> value="todo">teendő</option>
                                <option <?= isset($filters['status']) && in_array('project', $filters['status']) ? 'selected' : '' ?> value="project">projekt</option>
                                <option <?= isset($filters['status']) && in_array('inprogress', $filters['status']) ? 'selected' : '' ?> value="inprogress">folyamatban</option>
                                <option <?= isset($filters['status']) && in_array('finished', $filters['status']) ? 'selected' : '' ?> value="finished">befejezett</option>

                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">

                    <div class="form-row  py-2">
                        <div class="col-12 mb-2 font-size-sm"><label for="responsible">Felelős</label></div>
                        <div class="input-group col-md-12 col-12 ">
                            <select class="form-control form-control-sm select2" id="responsible" name="responsible[]"
                                data-placeholder="Felelős kiválasztása..." multiple>
                                <?php foreach ($responsibles as $row) { ?>
                                    <option value="<?php echo $row->responsible ?>" <?= isset($filters['responsible']) && in_array($row->responsible, $filters['responsible']) ? 'selected' : '' ?>>
                                        <?php echo model('App\Models\UserModel')->getRowById($row->responsible, 'name') ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-2">

                    <div class="form-row  py-2">
                        <div class="col-12 mb-2 font-size-sm"><label for="area">Terület</label></div>
                        <div class="input-group col-md-12 col-12 ">
                            <select class="form-control form-control-sm select2" id="area" name="area[]"
                                data-placeholder="Terület kiválasztása..." multiple>
                                <?php foreach (model('App\Models\BasicdataModel')->getByWhere(['status' => 1, 'can_get_ticket' => 1]) as $row) { ?>
                                    <option value="<?php echo $row->id ?>" <?= isset($filters['area']) && in_array($row->id, $filters['area']) ? 'selected' : '' ?>>
                                        <?php echo $row->name ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-2">

                    <div class="form-row  py-2">
                        <div class="col-12 mb-2 font-size-sm"><label for="category">Kategória</label></div>
                        <div class="input-group col-md-12 col-12 ">
                            <select class="form-control form-control-sm select2" id="category" name="category[]"
                                data-placeholder="Kategória kiválasztása..." multiple>
                                <?php foreach (model('App\Models\ItTicketCategoriesModel')->getByWhere(['status' => 'active']) as $row) { ?>
                                    <option value="<?php echo $row->id ?>" <?= isset($filters['category']) && in_array($row->id, $filters['category']) ? 'selected' : '' ?>>
                                        <?php echo $row->name ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>


                <div class="col-md-2">
                    <div class="form-row  py-2">
                        <div class="col-12 mb-2 font-size-sm"><label for="status">Létrehozás dátuma</label></div>
                        <div class="input-group col-md-12 col-12 ">
                            <?php echo form_input('sentdate', (empty($filters['sentdate'])) ? '' : $filters['sentdate'], 'placeholder="Időszak kiválasztása..." class="form-control form-control-sm"'); ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-row  py-2">
                        <div class="col-12 mb-2 font-size-sm"><label for="sender">Létrehozó</label></div>
                        <div class="input-group col-md-12 col-12 ">
                            <select class="form-control form-control-sm select2" id="sender" name="sender[]"
                                data-placeholder="Létrehozó kiválasztása..." multiple>
                                <?php foreach ($sender as $row) { ?>
                                    <option value="<?php echo $row->sender_id ?>" <?= isset($filters['sender']) && in_array($row->sender_id, $filters['sender']) ? 'selected' : '' ?>>
                                        <?php echo model('App\Models\UserModel')->getRowById($row->sender_id, 'name') ?>
                                    </option>
                                <?php } ?>
                            </select>

                        </div>
                    </div>
                </div>


                <div class="col-md-2">

                    <div class="form-row  py-2">
                        <div class="col-12 mb-2 font-size-sm"><label for="validation">Validáció</label></div>
                        <div class="input-group col-md-12 col-12 ">
                            <select class="form-control form-control-sm select2" id="validation" name="validation[]"
                                data-placeholder="Állapot kiválasztása..." multiple>
                                <option <?= isset($filters['validation']) && in_array('1', $filters['validation']) ? 'selected' : '' ?> value="1">validált</option>
                                <option <?= isset($filters['validation']) && in_array('0', $filters['validation']) ? 'selected' : '' ?> value="0">nem validált</option>

                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-row py-2">
                        <div class="col-12 mb-2 font-size-sm">
                            <label for="include_archived">Archív</label>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="include_archived"
                                    id="include_archived" value="1" <?= !empty($filters['include_archived']) ? 'checked' : '' ?>>
                                <label class="form-check-label ms-2" for="include_archived">
                                    Mutassa a három hónapon túl létrehozott jegyeket is
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (hasPermissions('view_all_tickets')): ?>

                    <!--<div class="col-md-2">
                        <div class="form-row py-2">
                            <div class="col-12 mb-2 font-size-sm">
                                <label for="hide_areas">Csak saját terület</label>
                            </div>
                            <div class="col-md-12 col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="hide_areas" id="hide_areas_yes"
                                        value="1" <?= (isset($filters['hide_areas']) ? $filters['hide_areas'] === '1' : false) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="hide_areas_yes">Igen</label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="hide_areas" id="hide_areas_no"
                                        value="0" <?= (isset($filters['hide_areas']) ? $filters['hide_areas'] === '0' : false) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="hide_areas_no">Nem</label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="hide_areas"
                                        id="hide_areas_no_filter" value="2" <?= (!isset($filters['hide_areas']) || $filters['hide_areas'] === '2') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="hide_areas_no_filter">Nem szűrt</label>
                                </div>
                            </div>
                        </div>
                    </div>-->
                <?php endif; ?>

                <div class="col-md-2">
                    <div class="form-row py-2">
                        <div class="col-12 mb-2 font-size-sm"><label for="year">&nbsp;</label></div>
                        <div class="form-group col-md-12 col-12">
                            <a href="<?php echo url('it_tickets/clearFilters') ?>"
                                class="btn btn-danger font-size-sm form-control-sm"><?php echo lang('App.reset') ?></a>
                            <button type="submit"
                                class="btn btn-primary font-size-sm form-control-sm"><?php echo lang('App.filter') ?></button>
                        </div>
                    </div>
                </div>
            </div>
            <?php echo form_close(); ?>
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title">Bejelentések</h3>

                    <div class="card-tools pull-right">
                        <a href="#createModal" data-toggle="modal" data-target="#createModal"
                            class="btn btn-default btn-sm"><i class="fa fa-plus pr-1"></i> Új bejelentés leadása</a>
                        <?php if (hasPermissions('export_it_ticket_riport')): ?>
                            <a href="it_tickets/exportRiport" class="btn btn-default btn-sm"><i class="fa fa-plus pr-1"></i>
                                Export
                                riport</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <div class="alert alert-info">
                                <strong>Leírás</strong>
                                <div class="ck-content">Ezen az űrlapon bejelentheted az IT felé informatikai problémád,
                                    kérésed, igényed. Kérlek add meg az űrlapon a pontos leírását a bejelentésnek, ha
                                    szükséges csatolj róla képet is. Új bejelentés leadásához kattints jobb oldalon
                                    fenn, az Új bejelentés leadása gombra, az alábbi táblázatban látod a korábban
                                    leadott bejelentéseidet és azok állapotát. </div>
                            </div>
                        </div>
                    </div>
                    <table id="dataTable1" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Bejelentés AZ</th>
                                <th>Név</th>
                                <th>Terület</th>
                                <th>Kategória</th>
                                <th>Felelős</th>
                                <th>Résztvevők</th>
                                <th><?php echo lang('App.status') ?></th>
                                <th>Határidő</th>
                                <th>Teendő</th>
                                <th>Projekt</th>
                                <th>Folyamatban</th>
                                <th>Befejezett</th>
                                <th>Validáció</th>
                                <th>Létrehozó</th>
                                <th>Létrehozás dátuma</th>
                                <th>Leírás</th>
                                <th width="300"><?php echo lang('App.action') ?></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- Modal -->
<div class="modal fade" id="createModal" role="dialog" data-backdrop="static" aria-labelledby="createModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Bejelentés leadása</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php echo form_open_multipart('it_tickets/send', ['class' => 'form-validate', 'id' => 'operationForm']); ?>
            <?= csrf_field() ?>
            <div class="modal-body">
                <?php $validationError = session()->getFlashdata('validation'); ?>
                <?php if (session()->getFlashdata('validation')): ?>
                    <div class="alert alert-danger">
                        <ul>
                            <?php foreach (session()->getFlashdata('validation') as $error): ?>
                                <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="formClient-TaskName" class="required">Feladat megnevezése</label>
                    <input type="text" name="task_name" id="formClient-TaskName" required class="form-control"
                        value="<?= old('task_name') ?>" />

                </div>

                <div class="form-group">
                    <label for="formClient-Area" class="required">Terület</label>
                    <select name="area" id="formClient-Area" class="form-control"
                        data-placeholder="Terület kiválasztása..." required>
                        <!-- Üres, AJAX fogja tölteni -->
                    </select>
                </div>

                <div class="form-group">
                    <label for="formClient-Category" class="required">Kategória</label>
                    <select name="category" id="formClient-Category" class="form-control"
                        data-placeholder="Kategória kiválasztása..." required>
                        <!-- Üres, Select2 AJAX fogja feltölteni -->
                    </select>
                </div>


                <div class="form-group">
                    <label for="formClient-Participants" class="">Résztvevők</label>
                    <select name="participants[]" id="formClient-Participants" class="form-control"
                        data-placeholder="Résztvevők kiválasztása..." multiple>
                        <!-- Üres, Select2 AJAX fogja feltölteni -->
                    </select>
                </div>

                <div class="form-group">

                    <label for="fileUpload">Melléklet</label>
                    <div class="input-group">
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" name="files[]" multiple id="files"
                                data-buttonText="Your label here.">
                            <label class="custom-file-label" for="files">Fájl kiválasztása</label>
                        </div>
                    </div>
                    <p class="text-muted pt-2">Maximum 25 MB</p>
                    <script></script>
                </div>
                <div class="form-group">
                    <label for="editor" class="">Leírás</label>
                    <textarea name="description" class="ckeditor" id="editor"
                        rows="5"><?= old('description') ?></textarea>
                </div>


            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-default">Mentés</button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="selectResponsibleModal" role="dialog" data-backdrop="static"
    aria-labelledby="selectResponsibleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="selectResponsibleModalLabel">Felelős kiválasztása</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php echo form_open_multipart('it_tickets/selectResponsibleAjax', ['class' => 'form-validate', 'id' => 'selectResponsible', 'name' => 'selectResponsible']); ?>

            <div class="modal-body">

                <input type="hidden" class="form-control" name="ticketId" id="ticketId" />


                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="responsible" class="required">Felelős</label>
                            <select name="responsible" id="responsible" class="form-control" required>
                                <option value="0">---</option>

                            </select>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-default">Mentés</button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="selectAreaModal" role="dialog" data-backdrop="static" aria-labelledby="selectAreaModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="selectAreaModalLabel">Terület kiválasztása</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php echo form_open_multipart('it_tickets/selectAreaAjax', ['class' => 'form-validate', 'id' => 'selectArea', 'name' => 'selectArea']); ?>

            <div class="modal-body">

                <input type="hidden" class="form-control" name="ticketId" id="ticketId" />


                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="edit_area" class="required">Terület</label>
                            <select name="edit_area" id="edit_area" class="form-control" required>
                                <option value="0">---</option>

                            </select>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-default">Mentés</button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="updateStatusModal" role="dialog" data-backdrop="static"
    aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateStatusModalLabel">Állapot módosítása</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php echo form_open_multipart('it_tickets/updateTicketStatusAjax', ['class' => 'form-validate', 'id' => 'updateStatus', 'name' => 'updateStatus']); ?>

            <div class="modal-body">

                <div class="mb-3">
                    <strong>Jelenlegi állapot</strong>:
                    <span id="currentStatus"></span>
                </div>

                <div class="row text-center mb-3 line" id="taskStateSetters">
                    <?php foreach (getProgress() as $row): ?>
                        <?php if ($row['id'] != 'planned' && $row['id'] != 'todo'): ?>
                            <div class="col-4 mb-2" style="min-height:100px;" id="<?php echo $row['id'] ?>Btn">
                                <button class="border btn btn-default w-100 h-100" id="<?php echo $row['id'] ?>">
                                    <i style="font-size: 2.625rem;"
                                        class="<?php echo $row['icon'] ?> align-middle my-2"></i><br><?php echo $row['name'] ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    <?php endforeach ?>
                </div>

                <input type="text" class="" hidden name="status" id="taskState2" value="todo">
                <input type="text" class="" hidden name="id" id="taskId2" value="">

                <script>
                    $('#taskStateSetters button').click(function (e) {
                        e.preventDefault();
                        // Set classes of other buttons
                        $('#taskStateSetters button').removeClass('btn-success');
                        $('#taskStateSetters button').addClass('btn-default');

                        // Set class for the clicked button
                        $(this).addClass('btn-success');


                        $('#taskState2').val($(this).attr('id'));
                    });

                    $('#taskStateSetters button#' + $('#taskState2').val()).addClass('btn-success');               
                </script>

            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-default">Mentés</button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<div class="modal fade" id="validationModal" role="dialog" data-backdrop="static" aria-labelledby="validationModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="validationModalLabel"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php echo form_open_multipart('it_tickets/validateTicketAjax', ['class' => 'form-validate', 'id' => 'validateForm']); ?>
            <div class="modal-body">
                <div class="row text-center mb-3 line" id="taskValidateSetters">
                    <div class="col-6 mb-2" style="min-height:100px;">
                        <button class="border btn btn-default w-100 h-100" id="validated">
                            <i class="fas fa-check font-size-xlg align-middle my-2"></i><br>Validált
                        </button>
                    </div>
                    <div class="col-6 mb-2" style="min-height:100px;">
                        <button class="border btn btn-default w-100 h-100" id="no_validate">
                            <i class="fas fa-xmark font-size-xlg align-middle my-2"></i><br>Nem validált
                        </button>
                    </div>
                </div>

                <input type="text" class="" hidden name="ticketIdValidation" id="ticketIdValidation" value="">
                <input type="text" class="" hidden name="validatedText" id="validatedText" value="">
                <label for="validateComment" id="commentLabel" class="required">Megjegyzés</label>
                <textarea class="form-control" id="validateComment" required="required"
                    name="validateComment"></textarea>

                <script>
                    $('#taskValidateSetters button').click(function (e) {
                        e.preventDefault();
                        // Set classes of other buttons
                        $('#taskValidateSetters button').removeClass('btn-success');
                        $('#taskValidateSetters button').addClass('btn-default');

                        // Set class for the clicked button
                        $(this).addClass('btn-success');
                        if ($(this).attr('id') == 'validated') {
                            $('#validatedText').val(1);
                            $('#commentLabel').removeClass('required')
                            $("#validateComment").prop("required", false);


                        } else {
                            $('#validatedText').val(0);
                            $('#commentLabel').addClass('required')
                            $("#validateComment").prop("required", true);
                        }
                    });

                    $('#taskValidateSetters button#' + $('#validatedText').val()).addClass('btn-default');               
                </script>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-default">Mentés</button>
            </div>
        </div>
        <?php echo form_close(); ?>
    </div>
</div>
</div>

<?= $this->endSection() ?>
<?= $this->section('js') ?>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const areaSelect = $("#formClient-Area");
        const categorySelect = $("#formClient-Category");
        const participantsSelect = $("#formClient-Participants");

        areaSelect.select2({
            placeholder: "Terület kiválasztása...",
            allowClear: true,
            ajax: {
                url: '<?= base_url("it_tickets/getAreas") ?>',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term || ''
                    };
                },
                processResults: function (data) {
                    return { results: data.results };
                }
            },
            dropdownParent: areaSelect.closest('.modal')
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
            dropdownParent: areaSelect.closest('.modal')
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
                    return { q: params.term };
                },
                processResults: function (data) {
                    return { results: data.results };
                }
            },
            templateResult: function (user) {
                if (!user.id) return user.text;
                return $(
                    `<span>${user.text}</span>
                    <small>(${user.antraid})</small>`
                );
            },
            templateSelection: function (user) {
                if (!user.id) return user.text;
                return `${user.text} (${user.antraid})`;
            },
            dropdownParent: areaSelect.closest('.modal')
        });

        areaSelect.on("change", function () {
            categorySelect.val(null).trigger("change");
        });

        // --- old() visszatöltés ---
        <?php if (old('area')): ?>
            const oldAreaId = "<?= old('area') ?>";
            const oldAreaName = "<?= esc(model('App\Models\BasicdataModel')->find(old('area'))->name ?? '') ?>";
            if (oldAreaId && oldAreaName) {
                const option = new Option(oldAreaName, oldAreaId, true, true);
                areaSelect.append(option).trigger('change');
            }

            <?php if (old('category')): ?>
                const oldCategoryId = "<?= old('category') ?>";
                const oldCategoryName = "<?= esc(model('App\Models\ItTicketCategoriesModel')->find(old('category'))->name ?? '') ?>";
                if (oldCategoryId && oldCategoryName) {
                    const option = new Option(oldCategoryName, oldCategoryId, true, true);
                    categorySelect.append(option).trigger('change');
                }
            <?php endif; ?>
        <?php endif; ?>

        <?php if (old('participants')): ?>
            const oldParticipants = <?= json_encode(old('participants')) ?>;
            const usersData = <?= json_encode(model('App\Models\UserModel')->find(old('participants')) ?? []) ?>;

            if (Array.isArray(oldParticipants) && usersData.length > 0) {
                usersData.forEach(user => {
                    const option = new Option(
                        `${user.name} (${user.antraid})`,
                        user.id,
                        true,
                        true
                    );
                    participantsSelect.append(option);
                });
                participantsSelect.trigger('change');
            }
        <?php endif; ?>
    });
</script>


<script>

    $(function () {
        $('input[name="sentdate"]').daterangepicker({
            showDropdowns: true,
            showISOWeekNumbers: true,
            autoUpdateInput: false,
            ranges: {
                "Ma": [moment(), moment()],
                "Tegnap": [moment().subtract(1, "days"), moment().subtract(1, "days")],
                "Utóbbi 7 nap": [moment().subtract(6, 'days'), moment()],
                "Utóbbi 30 nap": [moment().subtract(29, 'days'), moment()],
                "Jelen hónap": [moment().startOf("month"), moment().endOf("month")],
                "Előző hónap": [moment().subtract(1, "month").startOf("month"), moment().subtract(1, "month").endOf("month")]
            },
            locale: {
                format: "YYYY-MM-DD",
                separator: " - ",
                weekLabel: "",
                firstDay: 1,
                applyLabel: "Érvényesít",
                cancelLabel: "Törlés",
                fromLabel: "Tól",
                toLabel: "Ig",
                customRangeLabel: "Egyedi",
                daysOfWeek: ["V", "H", "K", "Sze", "Cs", "P", "Szo"],
                monthNames: ["Január", "Február", "Március", "Április", "Május", "Június", "Július", "Augusztus", "Szeptember", "Október", "November", "December"],
            },
            startDate: moment().subtract(29, "days"),
            endDate: moment(),
            alwaysShowCalendars: true,
            linkedCalendars: true,
            autoUpdateInput: false,
            opens: "left",

        }, function (start, end, label) {
            console.log("A new date selection was made: " + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
        });

        $("input[name='sentdate']").on('apply.daterangepicker', function (ev, picker) {
            $("input[name='sentdate']").val(picker.startDate.format("YYYY-MM-DD") + " - " + picker.endDate.format("YYYY-MM-DD"));

        });

        $("input[name='sentdate']").on('cancel.daterangepicker', function (ev, picker) {
            $("input[name='sentdate']").val("");
        });
    });

    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })

    $('#deadline').datetimepicker({ format: 'YYYY-MM-DD' });
</script>

<?php if (session()->getFlashdata('validation')): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // A Bootstrap modal ID-ja legyen 'createModal'
            var modal = new bootstrap.Modal(document.getElementById('createModal'));
            modal.show();
        });
    </script>
<?php endif; ?>

<script>
    $('#files').on('change', function () {
        files = $(this)[0].files;
        name = '';
        for (var i = 0; i < files.length; i++) {
            name += '\"' + files[i].name + '\"' + (i != files.length - 1 ? ", " : "");
        }
        $(".custom-file-label").html(name);
    })
</script>

<script>
    $(document).ready(function () {
        $.validator.setDefaults({
            errorElement: 'span',
            errorPlacement: function (error, element) {
                error.addClass('invalid-feedback');
                element.closest('.form-group').append(error);
            },
            highlight: function (element, errorClass, validClass) {
                $(element).addClass('is-invalid');
            },
            unhighlight: function (element, errorClass, validClass) {
                $(element).removeClass('is-invalid');
            }
        });
        $('#operationForm').validate();

    });


</script>


<script>

    $(document).ready(function () {

        var table = $("#dataTable1").DataTable({
            processing: true,
            serverSide: true,
            autoWidth: false,
            language: { url: '//cdn.datatables.net/plug-ins/1.11.4/i18n/hu.json' },
            dom: 'Bfrtip',
            stateSave: true,
            order: [[14, 'desc']],
            columnDefs: [
                { targets: [1], width: '300px'},
                { targets: [15], visible: false, searchable: true, className: 'no-colvis' },
                { targets: [16], width: '200px', className: 'dt-nowrap', orderable: false, searchable: false },
            ],
            ajax: {
                url: "<?= url('it_tickets/datatable') ?>",
                type: "POST",
                data: function (d) {
                    d["<?= csrf_token() ?>"] = "<?= csrf_hash() ?>";
                    return d;
                }
            },
            lengthMenu: [
                [10, 25, 50, 100, 150, 500, 1000],
                [10, 25, 50, 100, 150, 500, 1000]
            ],
            columns: [
                { data: 'task_number' },
                { data: 'name' },
                { data: 'area' },
                { data: 'category' },
                { data: 'responsible' },
                { data: 'participants' },
                { data: 'status' },
                { data: 'deadline' },
                { data: 'todo' },
                { data: 'project' },
                { data: 'inprogress' },
                { data: 'finished' },
                { data: 'validation' },
                { data: 'sender' },
                { data: 'created_at' },
                { data: 'description' },
                { data: 'actions' }
            ],
            buttons: [
                {
                    extend: 'collection',
                    className: 'rounded btn-default',
                    text: 'Export',
                    buttons: [
                        {
                            extend: 'copy',
                            text: 'Másolás'
                        },
                        {
                            extend: 'excel',
                            title: '',
                            exportOptions: { columns: ':visible' }
                        },
                        'csv',
                        'pdf',
                        {
                            extend: 'print',
                            text: 'Nyomtatás'
                        },
                    ],
                },
                {
                    extend: 'pageLength',
                    className: 'ml-2 rounded btn btn-default',
                },
                {
                    extend: 'collection',
                    className: 'ml-2 rounded caret-off btn-default',
                    text: '<i class="fas fa-ellipsis-v"></i>',
                    buttons: [
                        { extend: 'colvis', columns: ':not(.no-colvis)' },
                        { text: 'Reset', action: function () { table.state.clear(); window.location.reload(); } }
                    ]
                }
            ],
            initComplete: function () {
                $("#dataTable1").wrap("<div style='overflow:auto; width:100%;position:relative;'></div>");
            }
        });

        $('body').on('click', '.btnSelectArea', function () {
            var ticket_id = $(this).attr('data-id');

            $.ajax({
                url: '<?= base_url() ?>/it_tickets/getTicket/' + ticket_id,
                type: "GET",
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function (res) {
                    $('#selectAreaModal').modal('show');

                    var ticket = res && res.data ? res.data : null;
                    if (!ticket) return;

                    $('#selectArea #ticketId').val(ticket.id);

                    var departmentId = ticket.area || 0;

                    var $select = $('#selectArea #edit_area');
                    if ($select.hasClass('select2-hidden-accessible')) {
                        $select.select2('destroy');
                    }

                    $select.empty().append('<option value="0">---</option>');

                    $select.select2({
                        width: '100%',
                        placeholder: 'Válassz területet',
                        allowClear: true,
                        dropdownParent: $('#selectAreaModal'),
                        minimumInputLength: 0,
                        ajax: {
                            url: '<?= base_url() ?>/it_tickets/getAreas',
                            dataType: 'json',
                            delay: 250,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            data: function (params) {
                                return { q: params.term || '' };
                            },
                            processResults: function (data) {
                                return { results: Array.isArray(data.results) ? data.results : [] };
                            }
                        },
                        language: {
                            inputTooShort: function () { return 'Írj be legalább 1 karaktert…'; },
                            noResults: function () { return 'Nincs találat'; },
                            searching: function () { return 'Keresés…'; }
                        }
                    });

                    var currentAreaId = ticket.area || null;
                    var currentAreaName = ticket.area_name || null;

                    if (currentAreaId) {
                        var optText = currentAreaName ? currentAreaName : ('#' + currentAreaId);
                        var option = new Option(optText, currentAreaId, true, true);
                        $select.append(option).trigger('change');
                    } else {
                        $select.val(null).trigger('change');
                    }
                },
                error: function () {
                }
            });
        });

        $('body').on('click', '.btnSelectResponsible', function () {
            var ticket_id = $(this).attr('data-id');

            $.ajax({
                url: '<?= base_url() ?>/it_tickets/getTicket/' + ticket_id,
                type: "GET",
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function (res) {
                    $('#selectResponsibleModal').modal('show');

                    var ticket = res && res.data ? res.data : null;
                    if (!ticket) return;

                    $('#selectResponsible #ticketId').val(ticket.id);

                    var departmentId = ticket.area || 0;

                    var $select = $('#selectResponsible #responsible');
                    if ($select.hasClass('select2-hidden-accessible')) {
                        $select.select2('destroy');
                    }

                    $select.empty().append('<option value="0">---</option>');

                    $select.select2({
                        width: '100%',
                        placeholder: 'Válassz felelőst…',
                        allowClear: true,
                        dropdownParent: $('#selectResponsibleModal'),
                        minimumInputLength: 0,
                        ajax: {
                            url: '<?= base_url() ?>/it_tickets/getResponsiblesByArea/' + encodeURIComponent(departmentId),
                            dataType: 'json',
                            delay: 250,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            data: function (params) {
                                return { q: params.term || '' };
                            },
                            processResults: function (data/*, params*/) {
                                var results = [];
                                if (data && data.ok && Array.isArray(data.list)) {
                                    results = data.list.map(function (u) {
                                        return { id: u.id, text: u.name + ' (' + u.antraid + ')' };
                                    });
                                }

                                return { results: results };
                            }
                        },
                        language: {
                            inputTooShort: function () { return 'Írj be legalább 1 karaktert…'; },
                            noResults: function () { return 'Nincs találat'; },
                            searching: function () { return 'Keresés…'; }
                        }
                    });

                    var currentResponsibleId = ticket.responsible || null;
                    var currentResponsibleName = ticket.responsible_name || null;

                    if (currentResponsibleId) {
                        var optText = currentResponsibleName ? currentResponsibleName : ('#' + currentResponsibleId);
                        var option = new Option(optText, currentResponsibleId, true, true);
                        $select.append(option).trigger('change');
                    } else {
                        $select.val('0').trigger('change');
                    }
                },
                error: function () {
                }
            });
        });

        $('body').on('click', '.btnStatus', function () {
            var ticket_id = $(this).attr('data-id');
            $.ajax({
                url: '<?= base_url() ?>/it_tickets/getTicket/' + ticket_id,
                type: "GET",
                dataType: 'json',
                success: function (res) {
                    $('#updateStatusModal').modal('show');
                    $('#updateStatus #taskId2').val(res.data.id);
                    $('#updateStatus #taskState2').val(res.data.status);
                    $('#updateStatus #' + res.data.status).attr('disabled', true);


                    if (res.data.status == 'planned') {
                        $('#updateStatus #currentStatus').html('tervezett');
                        $('#updateStatus #currentStatus').addClass('badge badge-light border-warning border font-size-lg');
                    } else if (res.data.status == 'todo') {
                        $('#updateStatus #currentStatus').html('teendő');
                        $('#updateStatus #currentStatus').addClass('badge badge-warning border font-size-lg');
                    } else if (res.data.status == 'project') {
                        $('#updateStatus #currentStatus').html('projekt');
                        $('#updateStatus #currentStatus').addClass('badge badge-warning border font-size-lg');
                    } else if (res.data.status == 'inprogress') {
                        $('#updateStatus #currentStatus').html('folyamatban');
                        $('#updateStatus #currentStatus').addClass('badge badge-warning border font-size-lg');
                    } else if (res.data.status == 'finished') {
                        $('#updateStatus #currentStatus').html('befejezett');
                        $('#updateStatus #currentStatus').addClass('badge badge-success font-size-lg');
                    }

                },
                error: function (data) {
                }
            });
        });

        $('body').on('click', '.btnValidate', function () {
            var ticket_id = $(this).attr('data-id');
            $.ajax({
                url: '<?= base_url() ?>/it_tickets/getTicket/' + ticket_id,
                type: "GET",
                dataType: 'json',
                success: function (res) {
                    $('#validationModal').modal('show');
                    $('#validateForm #ticketIdValidation').val(res.data.id);
                    $('#validateForm #validatedText').val(res.data.is_validated);
                    $('#validationModalLabel').html(res.data.task_number + ' ' + res.data.name);

                },
                error: function (data) {
                }
            });
        });

        $("#updateStatusModal").on("hidden.bs.modal", function () {
            <?php foreach (getProgress() as $row): ?>
                $('#updateStatus #<?php echo $row["id"] ?>').attr('disabled', false);
            <?php endforeach; ?>
            $('#updateStatus #currentStatus').html('');
            $("#updateStatus #currentStatus").attr('class', '');


        });

        $("#selectArea").validate({
            rules: { edit_area: "required" },
            messages: {},
            submitHandler: function (form) {
                const form_action = $("#selectArea").attr("action");

                $.ajax({
                    data: $('#selectArea').serialize(),
                    url: form_action,
                    method: "POST",
                    dataType: 'json',
                    success: function (res) {
                        // status -> ikon
                        const status = (res.status || '').toLowerCase();
                        const icon = status === 'success' ? 'success'
                            : status === 'warning' ? 'warning'
                                : 'error';

                        Swal.fire({
                            text: res.message || (status === 'success'
                                ? 'Terület sikeresen módosítva.'
                                : status === 'warning'
                                    ? 'Terület módosult, de nem ment ki értesítés.'
                                    : 'Ismeretlen hiba történt. Kérlek jelezd az IT osztály felé.'),
                            icon: icon
                        }).then(() => window.location.reload());
                    },
                    error: function (xhr) {
                        let msg = 'Szerverhiba történt. Kérlek jelezd az IT osztály felé.';
                        if (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.error)) {
                            msg = xhr.responseJSON.message || xhr.responseJSON.error;
                        }
                        Swal.fire({ text: msg, icon: 'error' })
                            .then(() => window.location.reload());
                    }
                });
            }
        });

        $("#selectResponsible").validate({
            rules: {
                responsible: "required",
            },
            messages: {},
            submitHandler: function (form) {
                var form_action = $("#selectResponsible").attr("action");
                $.ajax({
                    data: $('#selectResponsible').serialize(),
                    url: form_action,
                    method: "POST",
                    dataType: 'json',
                    success: function (res) {
                        if (res.status === 'success') {
                            Swal.fire({
                                text: res.message || 'Felelős sikeresen módosítva.',
                                icon: 'success',
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.reload();
                                }
                            });
                        } else {
                            Swal.fire({
                                text: res.message || 'Ismeretlen hiba történt. Kérlek jelezd az IT osztály felé.',
                                icon: 'error',
                            });
                        }
                    },
                    error: function (xhr) {
                        // ez akkor fut le, ha pl. 500-as szerverhiba van
                        let msg = 'Szerverhiba történt. Kérlek jelezd az IT osztály felé.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            text: msg,
                            icon: 'error',
                        });
                    }
                });
            }
        });

        $("#updateStatus").validate({
            rules: {
                taskState2: "required",
            },
            messages: {},
            submitHandler: function (form) {
                var form_action = $("#updateStatus").attr("action");
                $.ajax({
                    data: $('#updateStatus').serialize(),
                    url: form_action,
                    method: "POST",
                    dataType: 'json',
                    success: function (res) {
                        // JSON választ várunk: {status: "success"|"error", message: "szöveg"}
                        $('#updateStatusModal').modal('hide');

                        if (res.status === 'success') {
                            Swal.fire({
                                text: res.message || 'Állapot sikeresen módosítva.',
                                icon: 'success',
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.reload();
                                }
                            });
                        } else {
                            Swal.fire({
                                text: res.message || 'Hiba történt az állapot módosítása közben.',
                                icon: 'error',
                            });
                        }
                    },
                    error: function (xhr) {
                        // ez akkor fut le, ha pl. 500-as hiba vagy nem JSON válasz
                        let msg = 'Szerverhiba történt. Kérlek jelezd az IT osztály felé.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            text: msg,
                            icon: 'error',
                        });
                    }
                });
            }
        });
        $("#validateForm").validate({
            rules: {
                validatedText: "required",
                ticketIdValidation: "required",

            },
            messages: {
            },
            submitHandler: function (form) {
                var form_action = $("#validateForm").attr("action");
                $.ajax({
                    data: $('#validateForm').serialize(),
                    url: form_action,
                    method: "POST",
                    dataType: 'json',
                    success: function (res) {
                        $('#validationModal').modal('hide');
                        swal.fire({
                            text: 'Sikeres validáció.',
                            icon: 'success',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.reload();
                            }
                        })
                    },
                    error: function (data) {
                        swal.fire({
                            text: 'Valami hiba történt. Kérlek, hogy jelezd az IT osztály felé..',
                            icon: 'error',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.reload();
                            }
                        })
                    }
                });
            }
        });


    });
</script>

<?= $this->endSection() ?>