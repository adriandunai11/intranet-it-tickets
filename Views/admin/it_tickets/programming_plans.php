<?= $this->extend('admin/layout/default') ?>
<?= $this->section('content') ?>

<style>
    .custom-file-input~.custom-file-label::after {
        content: "Tallózás";
    }

    #calendar {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: .5rem;
        padding: .75rem;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
    }

    .fc-event {
        cursor: pointer;
    }
</style>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Projekt naptár</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo url('/') ?>"><?php echo lang('App.home') ?></a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url('it_tickets') ?>">Bejelentések</a></li>
                    <li class="breadcrumb-item active">Projekt naptár</li>
                </ol>
            </div>
        </div>
    </div><!-- /.container-fluid -->
</section>

<section class="content">


    <div class="card">
        <div class="card-body">
            <div id="calendar"></div>

        </div>
    </div>


</section>

<?= $this->endSection() ?>
<?= $this->section('js') ?>

<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.19/index.global.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.19/locales/hu.global.min.js'></script>


<script>
    const TICKET_VIEW_BASE_URL = '<?= url('it_tickets/view') ?>';
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
            console.log("Új dátum tartomány: " + start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD'));
        });

        $("input[name='sentdate']").on('apply.daterangepicker', function (ev, picker) {
            $("input[name='sentdate']").val(picker.startDate.format("YYYY-MM-DD") + " - " + picker.endDate.format("YYYY-MM-DD"));
        });

        $("input[name='sentdate']").on('cancel.daterangepicker', function (ev, picker) {
            $("input[name='sentdate']").val("");
        });

        $('[data-toggle="tooltip"]').tooltip()
        $('#deadline').datetimepicker({ format: 'YYYY-MM-DD' });
    });

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


    document.addEventListener('DOMContentLoaded', function () {
        const calendarEl = document.getElementById('calendar');

        const filterState = { q: '' };

        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            height: 'auto',
            locale: 'hu',
            firstDay: 1,
            nowIndicator: true,
            navLinks: true,
            dayMaxEvents: true,
            stickyHeaderDates: true,

            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            events: {
                url: '<?= site_url('it_tickets/getProgrammingPlans'); ?>',
                method: 'GET',
                failure: function () {
                    alert('Nem sikerült betölteni az eseményeket.');
                }
            },
            eventTimeFormat: { hour: '2-digit', minute: '2-digit' },
            timeZone: 'local',

            eventDidMount: function (info) {
                info.el.title = info.event.title || '';

                const harness = info.el.closest('.fc-daygrid-event-harness');
                if (harness) {
                    harness.style.marginTop = '6px';
                }

                info.el.style.marginTop = '2px';
                info.el.style.marginBottom = '2px';

                const main = info.el.querySelector('.fc-event-main');
                if (main) {
                    main.style.padding = '2px 6px';
                }

                const tHarness = info.el.closest('.fc-timegrid-event-harness');
                if (tHarness) {
                    tHarness.style.marginBottom = '6px';
                }
            },

            eventClick: function (info) {
                info.jsEvent.preventDefault();
                const id = info.event.id;
                if (id) {
                    window.location.href = TICKET_VIEW_BASE_URL + '/' + encodeURIComponent(id);
                }
            },

        });

        calendar.render();

        const $search = document.getElementById('fc-search');
        const $clear = document.getElementById('fc-clear');

        if ($search) {
            $search.addEventListener('input', () => {
                filterState.q = $search.value || '';
                calendar.render();
            });
        }

        if ($clear) {
            $clear.addEventListener('click', () => {
                filterState.q = '';
                if ($search) $search.value = '';
                calendar.render();
            });
        }
    });
</script>

<?= $this->endSection() ?>