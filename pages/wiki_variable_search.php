<script>
    $(document).ready(function() {
        $('.table_search').dataTable( {"pageLength": 50,"order": [0, "asc"]});
        var table = $('#table_archive').DataTable();

        $('#varsearch').change( function() {
            var variable = $('#varsearch').is(':checked');
            table.column(1).search("").draw();
            if(variable == true) {
                table.column(1).search($('.dataTables_filter input')[0].value).draw();
            }else{
                table.search($('.dataTables_filter input')[0].value).draw();
            }
        } );

        $('#fieldcheckbox').appendTo( '#options_wrapper' );
        $('#table_archive_filter').appendTo( '#options_wrapper' );
        $('#table_archive_length').appendTo( '#options_wrapper' );
        $('#table_archive_filter').attr( 'style','float: right;padding-left: 170px;padding-top: 5px;' );
        $('#fieldcheckbox').attr( 'style','float: right;padding-left: 40px;padding-top: 7px;' );
        $('#table_archive_length').attr( 'style','padding-top: 7px;' );

        $("#table_archive_filter input").on('keyup click', function() {
            var variable = $('#varsearch').is(':checked');
            if(variable == true) {
                table.column(1).search($(this).val()).draw();
            }else{
                table.search($(this).val()).draw();
            }
        });
    });
</script>
<div class="wiki_main">
        <div class='row'>
            <div class='col-md-12'>
                <div>
                    <h3>Variable Search</h3>
                    <p>Enter the variable name to search for it and obtain information related to that variable.</p>
                    <p>To see more information click on the variable or table name.</p>
                </div>
                <div style="padding-top: 10px;">
                        <div id="options_wrapper">
                            <div class="custom-control custom-checkbox" id="fieldcheckbox">
                                <input type="checkbox" class="custom-control-input" id="varsearch" name="varsearch" checked>
                                <label class="custom-control-label" for="varsearch">Search Field Name Only</label>
                            </div>
                        </div>
                </div>
            </div>

            <div class="col-md-12" style="padding-top: 5px;padding-bottom: 60px">
                <div class="panel panel-default" >
                    <div class="panel-heading">
                        <h3 class="panel-title" id="seatch-title">
                            Search results
                        </h3>
                    </div>
                    <table class="table table_search sortable-theme-bootstrap" data-sortable id="table_archive">
                        <thead>
                            <th>Table</th>
                            <th>Field Name</th>
                            <th>Format</th>
                            <th>Description</th>
                        </thead>
                        <tbody>
                        <?php
                            if($settings["des_variable_search"] != "") {
                                $table = "";
                                $sql = "SELECT stored_name,doc_name,doc_size,mime_type FROM redcap_edocs_metadata WHERE doc_id='" . db_escape($settings["des_variable_search"]) . "'";
                                $q = db_query($sql);
                                while ($row = db_fetch_assoc($q)) {
                                    $path = EDOC_PATH.$row['stored_name'];
                                    $strJsonFileContents = file_get_contents($path);
                                    $json_array = json_decode($strJsonFileContents, true);
                                    foreach ($json_array as $tablename=>$variables){
                                        foreach ($variables as $varname=>$data){
                                            if($varname != "table_link")
                                            $table .= "<tr>".
                                                        "<td style='width:130px'><a href='".$variables['table_link']."'>".$tablename."</a></td>".
                                                        "<td style='width:130px'><a href='".$data['variable_link']."'>".$varname."</a></td>".
                                                        "<td style='width:130px'>".$data['data_format']."</td>".
                                                        "<td><div>".$data['description']."</div><div><i>".$data['description_extra']."</i></div><div><i>".$data['code_text']."</i></div></td>".
                                                "</tr>";
                                        }
                                    }
                                }
                                echo $table;
                            }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>