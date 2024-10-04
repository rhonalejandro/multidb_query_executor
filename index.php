<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ejecutor de Queries Múltiples</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .databases {
            max-height: 300px;
            overflow-y: auto;
        }
        #queries{
            min-height:342px;
        }
        #spinner {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
        }
        #progressOverlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        #progressContent {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
        }
        #stopButton {
            margin-top: 10px;
        }
        #logTable {
            font-size: 0.9em;
        }
        #logTable pre {
            max-height: 100px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .table-responsive {
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-5">
        <h1 class="mb-4">Ejecutor de Queries Múltiples</h1>
        <div class="mb-3">
            <label for="connection" class="form-label">Seleccione una conexión:</label>
            <select id="connection" class="form-select">
            </select>
        </div>
        
        <div class="row">
            
            <div class="col-md-3">
                <h2 class="h4 mb-3">Seleccione las bases de datos:</h2>
                <button id="selectDB_OpenModalBtn" class="btn btn-secondary mb-3">Seleccionar bases de datos por resultado</button>
                <div class="mb-3" id="DB_Resulted_div" style="display:none;">
                    <label for="DB_Resulted" class="form-label">Bases de datos con resultado:</label>
                    <select class="form-select" id="DB_Resulted">
                        <option value="0">0</option>
                        <option value="1">1 o más</option>
                    </select>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="selectAll">
                    <label class="form-check-label" for="selectAll">Seleccionar todas</label>
                </div>
                <hr>
                <div id="databaseList" class="databases mb-3">
                </div>
                
            </div>
            <div class="col-md-9">
                <div class="row">
                    <div class="col-md-3">

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="saveQueryCheck">
                            <label class="form-check-label" for="saveQueryCheck">Guardar query recurrente</label>
                        </div>
                    </div>
                    <div class="col-md-9">

                        <div id="saveQueryNameDiv" class="mb-3" style="display:none;">
                            <input type="text" class="form-control" id="saveQueryName" placeholder="Nombre para el query recurrente">
                        </div>
                    </div>
                </div>
                <div class="row">

                    <div class="mb-3">
                        <select class="form-select" id="loadQuery">
                            <option value="">Seleccione un query guardado</option>
                        </select>
                    </div>
                </div>
    <div class="mb-3">
        <label for="queries" class="form-label">Ingrese los queries a ejecutar:</label>
        <textarea class="form-control" id="queries" rows="6" placeholder="Ingrese sus queries aquí, separados por punto y coma (;)"></textarea>
    </div>
    <button id="executeBtn" class="btn btn-primary">Ejecutar Queries</button>
</div>
        </div>

        <div id="spinner" class="text-center mt-3">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>

        <div id="results" class="mt-5">
            <h2>Log de Resultados</h2>
            <div class="table-responsive">
                <table id="logTable" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Base de Datos</th>
                            <th>Query</th>
                            <th>Resultado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Los datos se cargarán aquí dinámicamente -->
                    </tbody>
                </table>
            </div>
            <nav aria-label="Paginación de logs">
                <ul class="pagination justify-content-center" id="logPagination">
                    <!-- Los controles de paginación se generarán aquí -->
                </ul>
            </nav>
        </div>
    </div>

    <div id="progressOverlay">
        <div id="progressContent">
            <h3>Progreso de ejecución</h3>
            <p>Base de datos: <span id="currentDatabase"></span></p>
            <p>Query en ejecución: <span id="currentQuery"></span></p>
            <div class="progress">
                <div id="progressBar" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
            </div>
            <button id="stopButton" class="btn btn-danger">Detener ejecución</button>
        </div>
    </div>

    <div class="modal fade" id="contentModal" tabindex="-1" aria-labelledby="contentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="contentModalLabel">Contenido completo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <pre id="modalContent"></pre>
            </div>
            </div>
        </div>
    </div>

    <!-- Modal para seleccionar bases de datos por resultado -->
    <div class="modal fade" id="selectDB_Modal" tabindex="-1" aria-labelledby="selectDB_ModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="selectDB_ModalLabel">Seleccionar bases de datos por resultado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="selectDB_ResultType" class="form-label">Seleccionar bases de datos que retornen:</label>
                        <select class="form-select" id="selectDB_ResultType">
                            <option value="0">0 resultados</option>
                            <option value="1">1 o más resultados</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="selectDB_SavedQueries" class="form-label">Cargar query guardado:</label>
                        <select class="form-select" id="selectDB_SavedQueries">
                            <option value="">Seleccione un query guardado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="selectDB_QueryTextarea" class="form-label">Query:</label>
                        <textarea class="form-control" id="selectDB_QueryTextarea" rows="4"></textarea>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="selectDB_SaveQuery">
                        <label class="form-check-label" for="selectDB_SaveQuery">Guardar este query</label>
                    </div>
                    <div class="mb-3" id="selectDB_SaveQueryNameDiv" style="display:none;">
                        <label for="selectDB_SaveQueryName" class="form-label">Nombre del query:</label>
                        <input type="text" class="form-control" id="selectDB_SaveQueryName">
                    </div>
                    <p>Analizando base de datos: <span id="selectDB_CurrentDatabase"></span></p>
                    <div class="progress mt-3">
                        <div id="selectDB_ProgressBar" class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                    <button id="selectDB_StopButton" class="btn btn-danger mt-3" style="display: none;">Detener ejecución</button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="selectDB_ExecuteButton">Ejecutar y seleccionar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#spinner').show();
            var currentPage = 1;
            var logsPerPage = 10;
            var stopExecution = false;

            // Cargar conexiones
            $.getJSON('db_operations.php', {action: 'getConnections'}, function(data) {
                if (Array.isArray(data)) {
                    $('#connection').html(data.map(conn => `<option value="${conn}">${conn}</option>`).join(''));
                    refreshConexion();
                } else {
                    console.error('Respuesta inesperada:', data);
                    $('#connection').html('<option>Error al cargar conexiones</option>');
                }
                $('#spinner').hide();
            }).fail(function(jqXHR, textStatus, errorThrown) {
                console.error("Error en la solicitud AJAX:", textStatus, errorThrown);
                $('#connection').html('<option>Error al cargar conexiones</option>');
                $('#spinner').hide();
            });

            function refreshConexion(){
                var selectedConnection = $("#connection").val();
                $('#spinner').show();
                $.post('db_operations.php', {
                    action: 'getDatabases',
                    connection: selectedConnection
                }, function(data) {
                    if (Array.isArray(data)) {
                        var html = data.map(db => `
                            <div class="form-check">
                                <input class="form-check-input dbCheckboxSelected" type="checkbox" name="databases[]" value="${db}" id="db_${db}">
                                <label class="form-check-label" for="db_${db}">${db}</label>
                            </div>
                        `).join('');
                        $('#databaseList').html(html);
                    } else {
                        console.error('Respuesta inesperada:', data);
                        $('#databaseList').html('<p>Error al cargar bases de datos</p>');
                    }
                    $('#spinner').hide();
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    $('#databaseList').html('<p>Error al cargar bases de datos</p>');
                    $('#spinner').hide();
                });
            }

            function processQueries(queriesText) {
                // Filtrar comentarios
                queriesText = queriesText.replace(/\/\*[\s\S]*?\*\/|([^\\:]|^)\/\/.*$/gm, '$1');
                
                // Dividir por punto y coma, ignorando los que están dentro de comillas
                let queries = queriesText.match(/('[^']*'|[^;])+/g);
                
                // Limpiar y filtrar queries vacíos
                return queries ? queries.map(q => q.trim()).filter(q => q.length > 0) : [];
            }

            function loadLogs(page) {
    $.get('db_operations.php', {
        action: 'getLogs',
        page: page,
        perPage: logsPerPage
    })
    .done(function(data) {
        console.log("Respuesta recibida:", data);
        if (data.error) {
            console.error("Error recibido:", data.error);
            $('#logTable tbody').html('<tr><td colspan="5">Error: ' + data.error + '</td></tr>');
            return;
        }
        var tbody = $('#logTable tbody');
        tbody.empty();
        if (data.logs && data.logs.length > 0) {
            data.logs.forEach(function(log) {
                var result = JSON.parse(log.result);
                tbody.append(`
                    <tr>
                        <td>${log.id}</td>
                        <td>${log.timestamp}</td>
                        <td>${result.database}</td>
                        <td class="clickable" data-content="${encodeURIComponent(result.query)}">${truncateText(result.query, 50)}</td>
                        <td class="clickable" data-content="${encodeURIComponent(JSON.stringify(result.result, null, 2))}">${JSON.stringify(result.result)}</td>
                    </tr>
                `);
            });
            updatePagination(data.totalPages, page);
        } else {
            tbody.html('<tr><td colspan="5">No se encontraron logs</td></tr>');
        }
    })
    .fail(function(jqXHR, textStatus, errorThrown) {
        console.error("Error en la solicitud AJAX:", textStatus, errorThrown);
        $('#logTable tbody').html('<tr><td colspan="5">Error al cargar los logs</td></tr>');
    });
}

function truncateText(text, maxLength) {
    if (text.length > maxLength) {
        return text.substring(0, maxLength) + '...';
    }
    return text;
}
            function updatePagination(totalPages, currentPage) {
                var pagination = $('#logPagination');
                pagination.empty();
                
                var startPage = Math.max(1, currentPage - 4);
                var endPage = Math.min(totalPages, startPage + 8);
                
                // Botón "Primera página"
                pagination.append(`
                    <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                        <a class="page-link" href="#" data-page="1">&laquo;&laquo;</a>
                    </li>
                `);
                
                // Botón "Anterior"
                pagination.append(`
                    <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                        <a class="page-link" href="#" data-page="${currentPage - 1}">&laquo;</a>
                    </li>
                `);

                // Páginas
                for (var i = startPage; i <= endPage; i++) {
                    pagination.append(`
                        <li class="page-item ${i === currentPage ? 'active' : ''}">
                            <a class="page-link" href="#" data-page="${i}">${i}</a>
                        </li>
                    `);
                }

                // Botón "Siguiente"
                pagination.append(`
                    <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                        <a class="page-link" href="#" data-page="${currentPage + 1}">&raquo;</a>
                    </li>
                `);
                
                // Botón "Última página"
                pagination.append(`
                    <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                        <a class="page-link" href="#" data-page="${totalPages}">&raquo;&raquo;</a>
                    </li>
                `);

                // Evento de clic para los botones de paginación
                $('.page-link').click(function(e) {
                    e.preventDefault();
                    var page = $(this).data('page');
                    if (page >= 1 && page <= totalPages) {
                        currentPage = page;
                        loadLogs(page);
                    }
                });
            }

            // Cargar logs iniciales
            loadLogs(currentPage);

            $('#executeBtn').click(function() {

                if ($('#saveQueryCheck').is(':checked')) {
                    var queryName = $('#saveQueryName').val();
                    var queryContent = $('#queries').val();
                    if (queryName && queryContent) {
                        saveQuery(queryName, queryContent);
                    }
                }

                var selectedConnection = $('#connection').val();
                var selectedDatabases = $('input[name="databases[]"]:checked').map(function() {
                    return this.value;
                }).get();
                var queriesText = $('#queries').val();
                var queries = processQueries(queriesText);

                if (selectedDatabases.length === 0) {
                    alert('Por favor, seleccione al menos una base de datos.');
                    return;
                }

                if (queries.length === 0) {
                    alert('Por favor, ingrese al menos un query válido.');
                    return;
                }

                $('#progressOverlay').show();
                $('#results').html('');
                stopExecution = false;
                $('#stopButton').show().prop('disabled', false).text('Detener ejecución');

                var totalQueries = selectedDatabases.length * queries.length;
                var completedQueries = 0;

                function updateProgress(database, query) {
                    completedQueries++;
                    var progress = (completedQueries / totalQueries) * 100;
                    $('#currentDatabase').text(database);
                    $('#currentQuery').text(query);
                    $('#progressBar').css('width', progress + '%').attr('aria-valuenow', progress).text(Math.round(progress) + '%');
                }

                function executeQuery(database, queryIndex) {
                    if (stopExecution) {
                        $('#progressOverlay').hide();
                        return;
                    }

                    if (queryIndex >= queries.length) {
                        if (selectedDatabases.indexOf(database) === selectedDatabases.length - 1) {
                            $('#progressOverlay').hide();
                            loadLogs(1);  // Recargar logs después de completar todas las queries
                            return;
                        }
                        var nextDatabase = selectedDatabases[selectedDatabases.indexOf(database) + 1];
                        executeQuery(nextDatabase, 0);
                        return;
                    }

                    var query = queries[queryIndex];
                    updateProgress(database, query);

                    $.post('db_operations.php', {
                        action: 'executeSingleQuery',
                        connection: selectedConnection,
                        database: database,
                        query: query
                    }, function(data) {
                        console.log(`Query executed in ${database}:`, data);
                        executeQuery(database, queryIndex + 1);
                    }).fail(function(jqXHR, textStatus, errorThrown) {
                        console.error(`Error executing query in ${database}:`, errorThrown);
                        executeQuery(database, queryIndex + 1);
                    });
                }

                executeQuery(selectedDatabases[0], 0);
            });

            $('#stopButton').click(function() {
                stopExecution = true;
                $(this).prop('disabled', true).text('Deteniendo...');
            });

            $("#selectAll").change(function(){
                $(".dbCheckboxSelected").prop("checked", this.checked);
            });

            $("#connection").change(function(){
                refreshConexion();
            });

            $(document).on('dblclick', '.clickable', function() {
    var content = decodeURIComponent($(this).data('content'));
    $('#modalContent').text(content);
    var modal = new bootstrap.Modal(document.getElementById('contentModal'));
    modal.show();
});
        });
    </script>


<script>

$(document).ready(function() {

    $('#saveQueryCheck').change(function() {
        $('#saveQueryNameDiv').toggle(this.checked);
    });

    loadSavedQueries();

    $('#loadQuery').change(function() {
        $('#queries').val("");
        var selectedQuery = $(this).val();
        if (selectedQuery) {
            loadQuery(selectedQuery);
        }
    });
});

function saveQuery(name, content) {
    $.post('db_operations.php', {
        action: 'saveQuery',
        name: name,
        content: content
    }, function(response) {
        if (response.success) {
            alert('Query guardado exitosamente');
            loadSavedQueries();  // Actualizar la lista de queries guardados
        } else {
            alert('Error al guardar el query');
        }
    }, 'json');
}

function loadSavedQueries() {
    $.get('db_operations.php', {
        action: 'getSavedQueries'
    }, function(queries) {
        var $loadQuery = $('#loadQuery');
        $loadQuery.find('option:gt(0)').remove();  // Eliminar opciones anteriores
        $.each(queries, function(name, content) {
            $loadQuery.append($('<option></option>').val(name).text(name));
        });
    }, 'json');
}

function loadQuery(name) {
    $.get('db_operations.php', {
        action: 'getSavedQueries'
    }, function(queries) {
        var queryContent = queries[name];
        if (queryContent) {
            $('#queries').val(queryContent);
        }
    }, 'json');
}

    </script>


<!-- inicio de Script funcionalidad de modal para seleccionar bases de datos -->
<script>
(function() {
    var selectDB_StopExecution = false;

    // Función para cargar queries guardados
    function selectDB_LoadSavedQueries() {
        $.get('db_operations.php', {
            action: 'getSavedQueries'
        }, function(queries) {
            var $savedQueries = $('#selectDB_SavedQueries');
            $savedQueries.find('option:gt(0)').remove();
            $.each(queries, function(name, content) {
                $savedQueries.append($('<option></option>').val(name).text(name));
            });
        }, 'json');
    }

    // Función para cargar un query específico
    function selectDB_LoadQuery(name) {
        $.get('db_operations.php', {
            action: 'getSavedQueries'
        }, function(queries) {
            var queryContent = queries[name];
            if (queryContent) {
                $('#selectDB_QueryTextarea').val(queryContent);
            }
        }, 'json');
    }

    // Función para guardar un query
    function selectDB_SaveQuery(name, content) {
        $.post('db_operations.php', {
            action: 'saveQuery',
            name: name,
            content: content
        }, function(response) {
            if (response.success) {
                alert('Query guardado exitosamente');
                selectDB_LoadSavedQueries();
            } else {
                alert('Error al guardar el query');
            }
        }, 'json');
    }

    // Evento para mostrar el modal
    $('#selectDB_OpenModalBtn').click(function() {
        var selectByQueryModal = new bootstrap.Modal(document.getElementById('selectDB_Modal'));
        selectByQueryModal.show();
    });

    // Evento para cargar un query guardado
    $('#selectDB_SavedQueries').change(function() {
        var selectedQuery = $(this).val();
        if (selectedQuery) {
            selectDB_LoadQuery(selectedQuery);
        }
    });

    // Evento para mostrar/ocultar el campo de nombre al guardar query
    $('#selectDB_SaveQuery').change(function() {
        $('#selectDB_SaveQueryNameDiv').toggle(this.checked);
    });

    // Evento principal para ejecutar la selección de bases de datos
    $('#selectDB_ExecuteButton').click(function() {
        var resultType = $('#selectDB_ResultType').val();
        var query = $('#selectDB_QueryTextarea').val();
        var saveName = $('#selectDB_SaveQuery').is(':checked') ? $('#selectDB_SaveQueryName').val() : null;

        if (!query) {
            alert('Por favor, ingrese un query.');
            return;
        }

        if (saveName) {
            selectDB_SaveQuery(saveName, query);
        }

        var selectedConnection = $('#connection').val();
        var databases = $('.dbCheckboxSelected').map(function() {
            return this.value;
        }).get();

        var totalDatabases = databases.length;
        var completedDatabases = 0;
        var selectedDatabases = [];
        var noSelectedDatabases = [];

        $('#selectDB_ProgressBar').css('width', '0%').attr('aria-valuenow', 0).text('0%');
        $('#selectDB_StopButton').show().prop('disabled', false);
        selectDB_StopExecution = false;

        function updateSelectionProgress(database) {
            completedDatabases++;
            var progress = (completedDatabases / totalDatabases) * 100;
            $('#selectDB_CurrentDatabase').text(database);
            $('#selectDB_ProgressBar').css('width', progress + '%').attr('aria-valuenow', progress).text(Math.round(progress) + '%');
        }

        function executeDatabaseSelection(index) {
            if (selectDB_StopExecution || index >= databases.length) {
                finishSelection();
                return;
            }

            var database = databases[index];
            updateSelectionProgress(database);

            $.post('db_operations.php', {
                action: 'executeSingleQuery',
                connection: selectedConnection,
                database: database,
                query: query
            }, function(data) {
                if(data.result && data.result.result && data.result.result.rows)
                {
                    if(resultType === "0" && data.result.result.rows.length === 0)
                    {
                        selectedDatabases.push(database);
                    }
                    else if(resultType === "1" && data.result.result.rows.length === 1)
                    {
                        selectedDatabases.push(database);
                    }else{
                        noSelectedDatabases.push(database);
                    }
                }

                if(selectedDatabases.length>0 || noSelectedDatabases >0)
                {
                    $("#DB_Resulted_div").css('display', 'block');
                }else{
                    $("#DB_Resulted_div").css('display', 'none');
                }

                executeDatabaseSelection(index + 1);
            }).fail(function(jqXHR, textStatus, errorThrown) {
                console.error(`Error executing query in ${database}:`, errorThrown);
                executeDatabaseSelection(index + 1);
            });
        }

        $("#DB_Resulted").on("change", function(){
            resultSelect = $(this).val();
            $('.dbCheckboxSelected').prop('checked', false);
            if(resultSelect === "1")
            {
                selectedDatabases.forEach(function(db) {
                    $('#db_' + db).prop('checked', true);
                });
            }else if(resultSelect === "0") {
                noSelectedDatabases.forEach(function(db) {
                    $('#db_' + db).prop('checked', true);
                });
            }
            
        });
        
        function finishSelection() {
            $('.dbCheckboxSelected').prop('checked', false);
            selectedDatabases.forEach(function(db) {
                $('#db_' + db).prop('checked', true);
            });
            alert('Se han seleccionado ' + selectedDatabases.length + ' bases de datos.');
            var selectByQueryModal = bootstrap.Modal.getInstance(document.getElementById('selectDB_Modal'));
            selectByQueryModal.hide();
            $('#selectDB_StopButton').hide();
        }

        executeDatabaseSelection(0);
    });

    // Evento para detener la ejecución
    $('#selectDB_StopButton').click(function() {
        selectDB_StopExecution = true;
        $(this).prop('disabled', true).text('Deteniendo...');
    });

    // Cargar queries guardados al inicio
    selectDB_LoadSavedQueries();
})();
</script>
<!-- fin del script -->
</body>
</html>