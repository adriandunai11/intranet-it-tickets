<?= $this->extend('admin/layout/default') ?>
<?= $this->section('content') ?>

<?php $perm = getTicketPermissions($ticket->id); ?>


<style>
    .custom-file-input~.custom-file-label::after {

        content: "Tallózás";
    }
</style>



<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-12">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url('/') ?>"><?php echo lang('App.home') ?></a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url('/it_tickets') ?>"><?php echo lang('App.it_reports') ?></a></li>
                    <li class="breadcrumb-item">
                        <?php echo $ticket->task_number . ' ' . $ticket->categoryName . ' - ' . $ticket->name ?>
                    </li>

                </ol>
            </div>
            <div class="col-sm-12">
                <h1><?php echo $ticket->task_number . ' ' . model('App\Models\ItTicketCategoriesModel')->getRowById($ticket->category, 'name') . ' - ' . $ticket->name ?>
                </h1>
                <p class="mt-3">
                    <?php if ($ticket->status == 'planned'):
                        $status = '<span class="badge badge-light border-warning border font-size-lg">tervezett</span>';
                    elseif ($ticket->status == 'todo'):
                        $status = '<span class="badge badge-warning border font-size-lg">teendő</span>';
                    elseif ($ticket->status == 'project'):
                        $status = '<span class="badge badge-warning border font-size-lg">projekt</span>';
                    elseif ($ticket->status == 'inprogress'):
                        $status = '<span class="badge badge-warning border font-size-lg">folyamatban</span>';
                    elseif ($ticket->status == 'waiting_for_sender'):
                        $status = '<span class="badge badge-warning border font-size-lg">bejelentő válaszára vár</span>';
                    elseif ($ticket->status == 'finished'):
                        $status = '<span class="badge  badge-success font-size-lg">befejezett</span>';
                    endif; ?>
                    <?php echo $status ?>
                    <?php echo ($ticket->status == 'finished' && $ticket->is_validated == 0) ? '<span class="badge badge-warning border font-size-lg">validációra vár...</span>' : ''; ?>
                    <?php echo ($ticket->status == 'finished' && $ticket->is_validated == 1) ? '<span class="badge  badge-success font-size-lg">validált - lezárt</span>' : ''; ?>

            </div>
        </div>
    </div><!-- /.container-fluid -->
</section>


<section class="content">
    <div class="card">
        <div class="card-header with-border">
            <div class="card-tools pull-right">
                <?php if ($perm['can_validate']): ?>
                    <a data-toggle="modal" data-target="#validateModal" class="btn btn-default">Validálás</a>
                <?php endif; ?>

                <?php if ($perm['can_edit_responsible']): ?>
                    <a data-id="<?php echo $ticket->id ?>" class="btn btn-default btnSelectResponsible">Felelős
                        kiválasztása</a>
                <?php endif; ?>

                <?php if ($perm['can_edit_area']): ?>
                    <a data-id="<?php echo $ticket->id ?>" class="btn btn-default btnSelectArea">Terület
                        kiválasztása</a>
                <?php endif; ?>

                <?php if ($perm['can_change_status']): ?>
                    <a data-id="<?php echo $ticket->id ?>" data-toggle="modal" id="btnStatus" data-target="#editStatusModal"
                        class="btn btn-default btnStatus">Állapot kezelése</a>
                <?php endif; ?>

                <?php if ($perm['can_edit']): ?>
                    <a data-id2="<?php echo $ticket->id ?>" data-toggle="modal" data-focus="false" data-target="#editModal"
                        class="btn btn-default">Módosítás</a>
                <?php endif; ?>
                <?php if ($perm['can_edit_programming_plans']): ?>
                    <a data-id="<?php echo $ticket->id ?>" data-focus="false"
                        class="btn btn-default btnProgrammingPlan">Projekt naptár
                        beállítása</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12 mb-4">

                    <div class="card mt-4">
                        <div class="card-header" style="display: flex; align-items: center;">
                            <h4 style="display: flex; align-items: center; justify-content: center;">
                                <img src="https://intranet.miellgroup.com/assets/admin/img/icons/it.png"
                                    style="width:32px; height:32px; filter: invert(9%) sepia(11%) saturate(834%) hue-rotate(168deg) brightness(94%) contrast(87%); margin-right: 15px;">
                                <?php echo lang('App.report_details') ?> (<?php echo $ticket->task_number ?>)
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <p><i class="fal fa-tasks"></i> <strong><?php echo lang('App.task_denom') ?>:</strong>
                                        <?php echo esc($ticket->name) ?></p>
                                    <p><i class="fal fa-sitemap"></i> <strong><?php echo lang('App.field') ?>:</strong>
                                        <?php echo $ticket->areaName ?></p>
                                    <p><i class="fal fa-folder"></i> <strong><?php echo lang('App.category') ?>:</strong>
                                        <?php echo $ticket->categoryName ?></p>
                                    <p><i class="fal fa-user-tie"></i> <strong><?php echo lang('App.Owner') ?>:</strong>
                                        <?php echo $ticket->responsibleName ?></p>
                                    <p><i class="fal fa-calendar-alt"></i> <strong><?php echo lang('App.due_date') ?>:</strong>
                                        <?php echo esc($ticket->deadline) ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p><i class="fal fa-user-plus"></i> <strong><?php echo lang('App.news_creator') ?>:</strong>
                                        <?php echo $ticket->senderName ?></p>
                                    <p><i class="fal fa-users"></i> <strong><?php echo lang('App.participants') ?>:</strong><br>
                                    <ul class="list-inline">
                                        <?php if (!empty($participantsInfo)): ?>
                                            <?php foreach ($participantsInfo as $participant): ?>
                                                <li class="">
                                                    <?php echo esc($participant['name']); ?>
                                                    (<?php echo esc($participant['antraid']); ?>)
                                                </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li class=""><?php echo lang('App.no_participants') ?>.</li>
                                        <?php endif; ?>
                                    </ul>
                                    </p>
                                    <p><i class="fal fa-calendar-check"></i> <strong><?php echo lang('App.creation_date') ?>:</strong>
                                        <?php echo esc($ticket->created_at) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <strong><?php echo lang('App.report_content') ?></strong>
                        <div class="ck-content"><?php echo $ticket->description ?></div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <h4 class=""><?php echo lang('App.files') ?>
                        <?php if ($perm['can_add_comment_or_file']): ?>
                            <a class="btn btn-default inline-attachment-add" data-toggle="modal"
                                data-target="#addAttachmentModal"><?php echo lang('App.upload') ?></a>
                        <?php endif; ?>
                    </h4>
                    <?php if (empty($attachments)): ?>
                        <div class="alert alert-info" id="card-no-data"><?php echo lang('App.no_disp_files') ?></div>
                    <?php else: ?>
                        <div id="files">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Állománynév</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attachments as $row): ?>
                                        <tr>
                                            <td><?php echo $row->filename ?></td>
                                            <td width="auto">
                                                <a href="<?php echo url($row->path . '/' . $row->filename) ?>" target="_blank"
                                                    data-html="true" title="Letöltés" data-toggle="tooltip"
                                                    class="btn btn-sm btn-default"><i class="fal fa-arrow-down"></i></a>
                                                <a data-html="true"
                                                    title="<?php echo model('App\Models\UserModel')->getRowById($row->uploader, 'name') . ' (' . model('App\Models\UserModel')->getRowById($row->uploader, 'antraid') . ')<br>' . $row->created ?>"
                                                    data-toggle="tooltip" class="btn btn-sm btn-default"><i
                                                        class="fal fa-circle-info"></i></a>
                                                <?php if ($perm['can_add_comment_or_file'] && $row->uploader == logged('id')): ?>
                                                    <a onclick="return confirm('Biztosan törölni szeretnéd a kijelölt mellékletet?')"
                                                        href="<?php echo url('it_tickets/deleteAttachment/' . $row->id) ?>"
                                                        class="btn btn-sm btn-default"><i class="fal fa-trash"></i></a>
                                                <?php endif; ?>
                                            </td>

                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <h4 class=""><?php echo lang('App.notes') ?>
                        <?php if ($perm['can_add_comment_or_file']): ?>
                            <a class="btn btn-default inline-comment-add" data-toggle="modal"
                                data-target="#addCommentModal"><?php echo lang('App.new_notes') ?></a>
                        <?php endif; ?>
                    </h4>
                    <?php if (empty($notes)): ?>
                        <div class="alert alert-info" id="card-no-data"><?php echo lang('App.no_display_notes') ?></div>
                    <?php else: ?>
                        <?php foreach ($notes as $row): ?>
                            <div class="comment-list mt-3" id="commententries-66fd2f4a8ac4b">
                                <div id="comment-entries">
                                    <div class="col-xs-12 comment-entry overflow-hidden " data-private="" data-archived=""
                                        data-comment-id="<?php echo $row->id ?>" onclick="">
                                        <div>
                                            <div class="comment-creation"></div>
                                            <div class="comment-text" id="comment-text-<?php echo $row->id ?>">
                                                <p><?php echo nl2br($row->note) ?></p>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span class="comment-type-pic mr-2">
                                                    <i class="fal fa-clipboard font-size-normal align-middle my-2"></i>
                                                </span>
                                                <div class="comment-date font-size-sm"><?php echo $row->created ?></div>

                                                <div class="comment-user font-size-sm ml-3">
                                                    <?php echo ($row->creator == 0) ? '<strong>' . lang('App.system') . '</strong>' : model('App\Models\UserModel')->getRowById($row->creator, 'name') . ' (' . model('App\Models\UserModel')->getRowById($row->creator, 'antraid') . ')' ?>
                                                </div>
                                                <?php if ($perm['can_add_comment_or_file'] && $row->creator != 0 && $row->creator == logged('id')): ?>
                                                    <!--<a href="javascript:void(0);" class="ml-3 btn btn-sm btn-default edit-comment inline-comment-edit"><i class="fal fa-pencil"></i></a>-->
                                                    <a href="<?php echo url('it_tickets/deleteComment/' . $row->id) ?>"
                                                        class="ml-1 btn btn-sm btn-default edit-comment inline-comment-edit"><i
                                                            class="fal fa-trash"></i></a>
                                                <?php endif; ?>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>


<?php if ($perm['can_change_status']): ?>

    <div class="modal fade" id="updateStatusModal" role="dialog" data-backdrop="static"
        aria-labelledby="updateStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel"><?php echo lang('App.change_state') ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php echo form_open_multipart('it_tickets/updateTicketStatusAjax', ['class' => '', 'id' => 'updateStatus', 'name' => 'updateStatus']); ?>

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
<?php endif; ?>

<?php if ($perm['can_edit']): ?>

    <!-- Modal -->
    <div class="modal fade" id="editModal" role="dialog" tabindex="-1" data-backdrop="static"
        aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">
                        <?php echo $ticket->task_number . ' ' . $ticket->categoryName . ' - ' . $ticket->name ?>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php echo form_open_multipart('it_tickets/update/' . $ticket->id, ['class' => '', 'id' => 'editTicket', 'name' => 'editTicket']); ?>

                <div class="modal-body">
                    <div class="form-group">
                        <label for="formClient-TaskName" class="required">Feladat megnevezése</label>
                        <input type="text" name="task_name" id="formClient-TaskName" required class="form-control"
                            value="<?php echo $ticket->name ?>" />
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
                        <label for="formClient-Deadline" class="">Határidő</label>
                        <div class="input-group date" id="deadline" data-target-input="nearest">
                            <input type="text" class="form-control datetimepicker-input" name="deadline"
                                data-target="#deadline" value="<?php echo $ticket->deadline ?>">
                            <div class="input-group-append" data-target="#deadline" data-toggle="datetimepicker">
                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                            </div>
                        </div>
                        <small class="d-block text-muted">pl.: 2024-10-17</small>
                    </div>

                    <div class="form-group">

                        <div>
                            <div class="main-container">
                                <div class="editor-container editor-container_classic-editor" id="editor-container">
                                    <div class="editor-container__editor">
                                        <label for="editor" class="">Leírás</label>
                                        <textarea name="description" class="ckeditor" rows="4" id="editor">
                                                                        <?php echo $ticket->description ?>
                                                                    </textarea>
                                    </div>
                                </div>
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

<?php endif; ?>

<?php if ($perm['can_edit_responsible']): ?>

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
                <?php echo form_open_multipart('it_tickets/selectResponsibleAjax', ['class' => '', 'id' => 'selectResponsible', 'name' => 'selectResponsible']); ?>

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
<?php endif; ?>

<?php if ($perm['can_edit_programming_plans']): ?>

    <!-- Modal -->
    <div class="modal fade" id="editProgrammingPlanModal" role="dialog" data-backdrop="static"
        aria-labelledby="editProgrammingPlanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProgrammingPlanModalLabel">Projekt naptár beállítása</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php echo form_open_multipart('it_tickets/editProgrammingPlan', ['class' => '', 'id' => 'editProgrammingPlan', 'name' => 'editProgrammingPlan']); ?>

                <div class="modal-body">

                    <input type="hidden" class="form-control" name="ticketId" id="ticketId" />


                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="programming_period" class="">Projekt kezdete/vége</label>
                                <input type="text" name="programming_period" class="js-daterange form-control"
                                    data-opens="right" data-format="YYYY.MM.DD" placeholder="Időszak kiválasztása...">

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
    </div>
<?php endif; ?>

<?php if ($perm['can_edit_area']): ?>


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
                <?php echo form_open_multipart('it_tickets/selectAreaAjax', ['class' => '', 'id' => 'selectArea', 'name' => 'selectArea']); ?>

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
<?php endif; ?>


<div class="modal fade" id="addCommentModal" role="dialog" data-backdrop="static" aria-labelledby="addCommentModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCommentModalLabel">Jegyzet létrehozása</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php echo form_open('it_tickets/addComment/' . $ticket->id, ['class' => 'form-validate', 'id' => 'addComment', 'name' => 'addComment']); ?>

            <div class="modal-body">


                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="comment_add" class="required">Jegyzet</label>
                            <textarea class="form-control" name="comment" id="comment_add"></textarea>
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

<div class="modal fade" id="addAttachmentModal" role="dialog" data-backdrop="static"
    aria-labelledby="addAttachmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAttachmentModalLabel">Állomány feltöltése</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php echo form_open_multipart('it_tickets/addAttachment/' . $ticket->id, ['class' => 'form-validate', 'id' => 'addAttachment', 'name' => 'addAttachment']); ?>

            <div class="modal-body">

                <?php $validationError = session()->getFlashdata('validation_add_attachment'); ?>
                <?php if (session()->getFlashdata('validation_add_attachment')): ?>
                    <div class="alert alert-danger">
                        <ul>
                            <?php foreach (session()->getFlashdata('validation_add_attachment') as $error): ?>
                                <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="fileUpload">Melléklet</label>
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" name="files[]" required id="file"
                                        multiple data-buttonText="Your label here.">
                                    <label class="custom-file-label" for="file">Fájl kiválasztása</label>
                                </div>
                            </div>
                            <p class="text-muted pt-2">Maximum 25 MB</p>
                            <script></script>
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

<div class="modal fade" id="validateModal" role="dialog" data-backdrop="static" aria-labelledby="validateModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="validateModalLabel">Bejelentés validálása</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php echo form_open_multipart('it_tickets/validateTicket/' . $ticket->id, ['class' => 'form-validate', 'id' => 'validateForm']); ?>
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

                <input type="text" class="" hidden name="status" id="validateText"
                    value="<?php echo $ticket->is_validated ?>">
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
                            $('#validateText').val(1);
                            $('#commentLabel').removeClass('required')
                            $("#validateComment").prop("required", false);


                        } else {
                            $('#validateText').val(0);
                            $('#commentLabel').addClass('required')
                            $("#validateComment").prop("required", true);
                        }
                    });

                    $('#taskValidateSetters button#' + $('#validateText').val()).addClass('btn-success');               
                </script>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-default">Mentés</button>
            </div>
        </div>
        <?php echo form_close(); ?>
    </div>
</div>


<?= $this->endSection() ?>
<?= $this->section('js') ?>
<script>

    (function ($, window, document) {
        'use strict';

        const DEFAULTS = {
            showDropdowns: true,
            showISOWeekNumbers: true,
            alwaysShowCalendars: true,
            linkedCalendars: true,
            autoUpdateInput: false,
            startDate: moment().subtract(29, 'days'),
            endDate: moment(),
            opens: 'left',
            locale: {
                format: 'YYYY-MM-DD',
                separator: ' - ',
                weekLabel: '',
                firstDay: 1,
                applyLabel: 'Érvényesít',
                cancelLabel: 'Törlés',
                fromLabel: 'Tól',
                toLabel: 'Ig',
                customRangeLabel: 'Egyedi',
                daysOfWeek: ['V', 'H', 'K', 'Sze', 'Cs', 'P', 'Szo'],
                monthNames: ['Január', 'Február', 'Március', 'Április', 'Május', 'Június', 'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December'],
            },
            ranges: {
                'Ma': [moment(), moment()],
                'Tegnap': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Utóbbi 7 nap': [moment().subtract(6, 'days'), moment()],
                'Utóbbi 30 nap': [moment().subtract(29, 'days'), moment()],
                'Jelen hónap': [moment().startOf('month'), moment().endOf('month')],
                'Előző hónap': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            }
        };

        /**
         * Inicializál minden, a selectorral egyező inputot.
         * Data attributumok:
         *  - data-opens="left|right|center"
         *  - data-format="YYYY-MM-DD" (csak a megjelenített értékre)
         *  - data-single="1" (egynapos, singleDatePicker mód)
         *  - data-ranges="none" (tiltsa a gyors tartományokat)
         */
        function initDateRangePickers(selector = '.js-daterange', override = {}) {
            $(selector).each(function () {
                const $input = $(this);

                if ($input.data('drp-initialized')) return;

                const dataOpens = $input.data('opens');
                const dataFormat = $input.data('format');
                const dataSingle = $input.data('single') === 1 || $input.data('single') === true;
                const dataRanges = $input.data('ranges'); // 'none' esetén kikapcsoljuk

                const opts = $.extend(true, {}, DEFAULTS, override);

                if (dataOpens) opts.opens = String(dataOpens);
                if (dataFormat) opts.locale.format = String(dataFormat);
                if (dataSingle) opts.singleDatePicker = true;
                if (dataRanges === 'none') delete opts.ranges;

                // init
                $input.daterangepicker(opts, function (start, end) {
                    // console.log('[DRP]', $input[0].name || $input[0].id, start.format(opts.locale.format), end.format(opts.locale.format));
                });

                $input.on('apply.daterangepicker', function (ev, picker) {
                    if (opts.singleDatePicker) {
                        $(this).val(picker.startDate.format(opts.locale.format));
                    } else {
                        $(this).val(
                            picker.startDate.format(opts.locale.format) +
                            opts.locale.separator +
                            picker.endDate.format(opts.locale.format)
                        );
                    }
                    $(this).trigger('change');
                });

                $input.on('cancel.daterangepicker', function () {
                    $(this).val('').trigger('change');
                });

                $input.data('drp-initialized', true);
                $input.data('drp', $input.data('daterangepicker'));
            });
        }

        window.DRP = {
            init: initDateRangePickers
        };

        $(function () {
            initDateRangePickers('.js-daterange');
        });

    })(jQuery, window, document);
</script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const $modal = $('#editModal');
        const $category = $("#formClient-Category");
        const $participants = $("#formClient-Participants");

        const style = document.createElement('style');
        style.textContent = `.select2-container{z-index:2000!important}`;
        document.head.appendChild(style);

        function initInsideModal() {
            if ($category.hasClass('select2-hidden-accessible')) $category.select2('destroy');
            if ($participants.hasClass('select2-hidden-accessible')) $participants.select2('destroy');

            $category.select2({
                width: '100%',
                placeholder: "Kategória kiválasztása...",
                allowClear: true,
                dropdownParent: $modal,
                ajax: {
                    url: '<?= base_url("it_tickets/getCategoriesByArea") ?>',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        areaId: <?= (int) $ticket->area ?>,
                        q: params.term || ''
                    }),
                    processResults: data => ({ results: data.results })
                }
            });

            <?php if (!empty($ticket->category)): ?>
                    (function preloadCategory() {
                        const id = <?= (int) $ticket->category ?>;
                        const name = "<?= esc($ticket->categoryName) ?>";
                        const opt = new Option(name, id, true, true);
                        $category.append(opt).trigger('change');
                    })();
            <?php endif; ?>

            $participants.select2({
                width: '100%',
                placeholder: "Résztvevők kiválasztása...",
                multiple: true,
                allowClear: true,
                dropdownParent: $modal,
                ajax: {
                    url: '<?= base_url("it_tickets/getUsers") ?>',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({ q: params.term || '' }),
                    processResults: data => ({ results: data.results })
                },
                templateResult(user) { return user.id ? $(`<span>${user.text}</span> <small>(${user.antraid})</small>`) : user.text; },
                templateSelection: function (user) {
                    if (!user.id) return user.text || '';
                    const label = user.text || '';
                    if (/\(.*\)$/.test(label)) return label;
                    return user.antraid ? `${label} (${user.antraid})` : label;
                }
            });

            <?php
            $participantIds = json_decode($ticket->participants ?? '[]', true) ?: [];
            $usersData = $participantIds ? (model('App\Models\UserModel')->find($participantIds) ?? []) : [];
            ?>
            <?php if (!empty($usersData)): ?>
                    (function preloadParticipants() {
                        const users = <?= json_encode($usersData) ?>;
                        users.forEach(u => {
                            const opt = new Option(`${u.name} (${u.antraid})`, u.id, true, true);
                            $participants.append(opt);
                        });
                        $participants.trigger('change');
                    })();
            <?php endif; ?>

            $category.on('change', function () { $(this).valid && $(this).valid(); });
            $participants.on('change', function () { $(this).valid && $(this).valid(); });
        }

        $modal.on('shown.bs.modal', initInsideModal);

        if ($modal.is(':visible')) initInsideModal();
    });

</script>

<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })
    $('#deadline').datetimepicker({ format: 'YYYY-MM-DD' });

</script>

<?php if (session()->getFlashdata('validation_add_attachment')): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // A Bootstrap modal ID-ja legyen 'addAttachmentModal'
            var modal = new bootstrap.Modal(document.getElementById('addAttachmentModal'));
            modal.show();
        });
    </script>
<?php endif; ?>



<script>
    $('#file').on('change', function () {
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
        $('#editTicket').validate();
        $('#validateForm').validate();
        $('#addAttachment').validate();

        //Initialize Select2 Elements
        $('.select2').select2({
            dropdownParent: '#editModal'
        })
    });


</script>

<script>

    $(document).ready(function () {

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

        $("#selectResponsible").validate({
            rules: {
                responsible: "required",
            },
            messages: {
            },
            submitHandler: function (form) {
                var form_action = $("#selectResponsible").attr("action");
                $.ajax({
                    data: $('#selectResponsible').serialize(),
                    url: form_action,
                    method: "POST",
                    dataType: 'json',
                    success: function (res) {
                        $('#selectResponsible').modal('hide');
                        swal.fire({
                            text: 'Felelős sikeresen módosítva.',
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

        $('body').on('click', '.btnProgrammingPlan', function () {
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
                    $('#editProgrammingPlanModal').modal('show');

                    var ticket = res && res.data ? res.data : null;
                    if (!ticket) return;

                    $('#editProgrammingPlan #ticketId').val(ticket.id);

                    var $input = $('#editProgrammingPlan [name="programming_period"]');
                    var drp = $input.data('daterangepicker');
                    if (!drp) {
                        console.warn('Daterangepicker még nincs inicializálva ezen az inputon.');
                        return;
                    }

                    var isPlan = Number(ticket.is_programming_plan) === 1;
                    var start = ticket.programming_plan_start || null; // 'YYYY-MM-DD'
                    var end = ticket.programming_plan_end || null; // 'YYYY-MM-DD'

                    if (start && !end) end = start;

                    var fmt = drp.locale && drp.locale.format ? drp.locale.format : 'YYYY-MM-DD';
                    var sep = drp.locale && drp.locale.separator ? drp.locale.separator : ' - ';

                    if (isPlan && start) {
                        drp.setStartDate(moment(start, 'YYYY-MM-DD'));
                        drp.setEndDate(moment(end || start, 'YYYY-MM-DD'));

                        $input.val(
                            moment(start, 'YYYY-MM-DD').format(fmt) +
                            sep +
                            moment(end || start, 'YYYY-MM-DD').format(fmt)
                        ).trigger('change');
                    } else {
                        $input.val('').trigger('change');
                    }
                },
                error: function () {
                }
            });
        });

        $('#editProgrammingPlan').validate({
            errorElement: 'span',
            errorPlacement: function (error, element) {
                error.addClass('invalid-feedback');
                element.closest('.form-group').append(error);
            },
            highlight: function (element) { $(element).addClass('is-invalid'); },
            unhighlight: function (element) { $(element).removeClass('is-invalid'); },

            submitHandler: function (form) {
                const $form = $(form);
                const url = $form.attr('action');

                const payload = $form.serialize();

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: payload,
                    dataType: 'json',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (res) {
                        const status = (res.status || '').toLowerCase();
                        const icon = status === 'success' ? 'success'
                            : status === 'warning' ? 'warning'
                                : 'error';

                        Swal.fire({
                            text: res.message || (status === 'success'
                                ? 'Sikeres mentés.'
                                : status === 'warning'
                                    ? 'Figyelem: részben sikerült.'
                                    : 'Ismeretlen hiba történt.'),
                            icon: icon
                        }).then(function () {
                            if (window.calendar && typeof window.calendar.refetchEvents === 'function') {
                                window.calendar.refetchEvents();
                            } else {
                                window.location.reload();
                            }
                        });
                    },
                    error: function (xhr) {
                        let msg = 'Szerverhiba történt. Kérlek jelezd az IT-nek.';
                        if (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.error)) {
                            msg = xhr.responseJSON.message || xhr.responseJSON.error;
                        }
                        Swal.fire({ text: msg, icon: 'error' })
                            .then(function () {
                                if (window.calendar && typeof window.calendar.refetchEvents === 'function') {
                                    window.calendar.refetchEvents();
                                }
                            });
                    }
                });

                return false;
            }
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
                    } else if (res.data.status == 'waiting_for_sender') {
                        $('#updateStatus #currentStatus').html('bejelentő válaszára vár');
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

        $("#updateStatus").validate({
            rules: { status: "required" },
            messages: {},
            submitHandler: function (form) {
                const form_action = $("#updateStatus").attr("action");

                $.ajax({
                    data: $('#updateStatus').serialize(),
                    url: form_action,
                    method: "POST",
                    dataType: 'json',
                    success: function (res) {
                        const status = (res.status || '').toLowerCase();
                        const icon = status === 'success' ? 'success'
                            : status === 'warning' ? 'warning'
                                : 'error';

                        Swal.fire({
                            text: res.message || (status === 'success'
                                ? 'Állapot sikeresen módosítva.'
                                : status === 'warning'
                                    ? 'Állapot módosult, de nem ment ki értesítés.'
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

        var table = $("#dataTable1").DataTable({
            "autoWidth": false,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.11.4/i18n/hu.json',
            },
            "dom": 'Bfrtip',
            stateSave: true,
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
                    extend: 'collection',
                    className: 'ml-2 rounded caret-off btn-default',
                    text: '<i class="fas fa-ellipsis-v"></i>',
                    buttons: [
                        {
                            extend: 'colvis',
                        },
                        {
                            text: 'Reset',
                            action: function (e, dt, node, config) {
                                table.state.clear();
                                window.location.reload();
                            }
                        }
                    ],
                },
            ],
            "initComplete": function (settings, json) {
                $("#dataTable1").wrap("<div style='overflow:auto; width:100%;position:relative;'></div>");
            },
        });

    });
</script>

<?= $this->endSection() ?>