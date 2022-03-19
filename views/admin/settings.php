<div class="container">
    <div id="app">
        <div class="row">
            <div class="col-4">
                <div class="alert alert-info">
                    <span id="service-health"><?php echo $this->health; ?></span>
                </div>
                <ul class="list-group">
                    <li class="list-group-item">
                        <a class="ajax-action btn btn-secondary" data-action="generate_models" href="<?php echo $this->baseUrl(); ?>/index.php">Generate Models</a>
                    </li>
                    <li class="list-group-item">
                        <form class="form-inline">
                            <div class="btn-group mb-2 mr-sm-2">
                                <a class="ajax-action btn btn-primary form-control" data-action="start_service" href="<?php echo $this->baseUrl(); ?>/index.php">Start Service</a>
                                <a class="ajax-action btn btn-secondary form-control" data-action="shutdown_service" href="<?php echo $this->baseUrl(); ?>/index.php">Shutdown Service</a>
                            </div>
                        </form>
                    </li>
                    <li class="list-group-item">
                        <form class="form-inline">
                            <div class="input-group mb-2 mr-sm-2">
                                <input id="pid" class="form-control" type="text" placeholder="pid">
                                <div class="input-group-append">
                                    <a class="ajax-action btn btn-secondary form-control" data-action="generate_patient" href="<?php echo $this->baseUrl(); ?>/index.php">Generate JSON</a>
                                </div>
                                <span class="text-sm-left">Leave Blank to generate ALL Patients</span>
                            </div>
                        </form>
                    </li>
                    <li class="list-group-item">
                        <input class="form-control" type="text" id="effectiveDate" value="2018-01-01">
                        <input class="form-control" type="text" id="effectiveEndDate" value="2020-12-31">
                        <select id="measure" class="form-control">
                            <?php foreach ($this->measures as $measure_name => $measure_file_path) { ?>
                                <option value="<?php echo $measure_file_path; ?>"><?php echo $measure_name; ?></option>
                            <?php } ?>
                        </select>
                    </li>
                    <li class="list-group-item">
                        <a class="ajax-action btn btn-secondary" data-action="execute_measure" href="<?php echo $this->baseUrl(); ?>/index.php">Execute Measure</a>
                    </li>
                    <li class="list-group-item">
                        <a class="ajax-action btn btn-secondary" data-action="execute_cat1_export" href="<?php echo $this->baseUrl(); ?>/index.php">Export Cat 1</a>
                    </li>
                    <li class="list-group-item">
                        <a class="ajax-action btn btn-secondary" data-action="execute_cat3_export" href="<?php echo $this->baseUrl(); ?>/index.php">Export Cat 3</a>
                    </li>
                </ul>
            </div>
            <div class="col-8">
                <textarea class="form-control" rows="24" id="sample-json"></textarea>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function () {

        setInterval(function () {
            refreshHealth();
        }, 3000);

        $('.ajax-action').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const action = $(this).attr('data-action');
            const href = $(this).attr('href');
            const pid = $('#pid').val() ?? null;
            const measure = $('#measure').val() ?? null;

            const data = {
                action: 'admin!' + action,
                pid: pid,
                measure: measure,
                effectiveDate: $('#effectiveDate').val(),
                effectiveEndDate: $('#effectiveEndDate').val(),
            };
            $.get(href, data, function (response) {
                if (
                    action == 'generate_patient' ||
                    action == 'execute_measure' ||
                    action == 'execute_cat1_export' ||
                    action == 'execute_cat3_export'
                ) {
                    if (action == 'execute_cat1_export' || action == 'execute_cat3_export') {
                        $('#sample-json').val(response);
                    } else {
                        var textedJson = JSON.stringify(response, undefined, 4);
                        $('#sample-json').text(textedJson);
                    }
                }
                refreshHealth();
            }, 'json')
        });

        function refreshHealth()
        {
            $.get("<?php echo $this->baseUrl(); ?>/index.php?action=admin!get_health", function(data) {
                $('#service-health').text(data);
            });
        }
    });
</script>
</body>
