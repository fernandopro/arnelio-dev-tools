<?php
/**
 * Documentation Tab - Gestión de documentación
 */

$docs_dir = __DIR__ . '/../docs';

function get_available_docs($dir) {
    $docs = [];
    if (is_dir($dir)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['md', 'txt', 'rst'])) {
                $relative_path = str_replace($dir . '/', '', $file->getPathname());
                $docs[] = [
                    'file' => $relative_path,
                    'path' => $file->getPathname(),
                    'name' => basename($file->getBasename('.' . $file->getExtension())),
                    'type' => $file->getExtension(),
                    'size' => $file->getSize(),
                    'modified' => $file->getMTime()
                ];
            }
        }
    }
    return $docs;
}

$available_docs = get_available_docs($docs_dir);
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-file-plus"></i>
                    Crear Nueva Documentación
                </h5>
            </div>
            <div class="card-body">
                <form class="ajax-form" action="<?php echo admin_url('admin-ajax.php'); ?>" method="post">
                    <input type="hidden" name="action" value="tarokina_create_doc">
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('create_doc'); ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="doc-title" class="form-label">Título del Documento</label>
                            <input type="text" class="form-control" id="doc-title" name="doc_title" 
                                   placeholder="ej: Guía de Instalación" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="doc-type" class="form-label">Tipo</label>
                            <select class="form-select" id="doc-type" name="doc_type">
                                <option value="md">Markdown (.md)</option>
                                <option value="txt">Texto (.txt)</option>
                                <option value="rst">reStructuredText (.rst)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="doc-category" class="form-label">Categoría</label>
                            <select class="form-select" id="doc-category" name="doc_category">
                                <option value="general">General</option>
                                <option value="installation">Instalación</option>
                                <option value="configuration">Configuración</option>
                                <option value="development">Desarrollo</option>
                                <option value="api">API</option>
                                <option value="troubleshooting">Resolución de Problemas</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="doc-author" class="form-label">Autor</label>
                            <input type="text" class="form-control" id="doc-author" name="doc_author" 
                                   value="<?php echo wp_get_current_user()->display_name; ?>">
                        </div>
                        
                        <div class="col-12">
                            <label for="doc-content" class="form-label">Contenido</label>
                            <textarea class="form-control" id="doc-content" name="doc_content" 
                                      rows="10" placeholder="Escribe el contenido de la documentación..."></textarea>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-file-plus"></i>
                            Crear Documento
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-bookmarks"></i>
                    Documentos Disponibles (<?php echo count($available_docs); ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($available_docs)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-book text-muted" style="font-size: 3rem;"></i>
                        <h6 class="text-muted mt-3">No hay documentación disponible</h6>
                        <p class="text-muted">Crea tu primer documento usando el formulario de arriba.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Documento</th>
                                    <th>Tipo</th>
                                    <th>Tamaño</th>
                                    <th>Modificado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($available_docs as $doc): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($doc['name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo esc_html($doc['file']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $doc['type'] === 'md' ? 'primary' : ($doc['type'] === 'txt' ? 'secondary' : 'info'); ?>">
                                                <?php echo strtoupper($doc['type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <?php echo number_format($doc['size'] / 1024, 1); ?> KB
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y H:i', $doc['modified']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button class="btn btn-outline-secondary"
                                                        onclick="viewDoc('<?php echo esc_js($doc['file']); ?>')">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-secondary"
                                                        onclick="editDoc('<?php echo esc_js($doc['file']); ?>')">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-tools"></i>
                    Herramientas de Documentación
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-secondary">
                        <i class="bi bi-collection"></i>
                        Generar Índice
                    </button>
                    <button class="btn btn-outline-success">
                        <i class="bi bi-file-pdf"></i>
                        Exportar a PDF
                    </button>
                    <button class="btn btn-outline-warning">
                        <i class="bi bi-search"></i>
                        Buscar en Docs
                    </button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-template"></i>
                    Plantillas
                </h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <button class="list-group-item list-group-item-action" onclick="loadTemplate('api')">
                        <i class="bi bi-code-square text-primary"></i>
                        Documentación API
                    </button>
                    <button class="list-group-item list-group-item-action" onclick="loadTemplate('installation')">
                        <i class="bi bi-download text-success"></i>
                        Guía de Instalación
                    </button>
                    <button class="list-group-item list-group-item-action" onclick="loadTemplate('troubleshooting')">
                        <i class="bi bi-exclamation-triangle text-warning"></i>
                        Solución de Problemas
                    </button>
                    <button class="list-group-item list-group-item-action" onclick="loadTemplate('changelog')">
                        <i class="bi bi-list-ol text-info"></i>
                        Changelog
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// El JavaScript específico para docs se carga automáticamente 
// mediante wp_enqueue_script en loader.php como 'tarokina-dev-tools-docs-js'
// Esto sigue las mejores prácticas de WordPress
?>
