<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-list-ul me-2 text-primary"></i>
                        Historique des mises à jour
                    </h5>
                    <a href="{%= CORE.makeSiteUrl() %}/admin/libmanager" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left me-1"></i> Retour
                    </a>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-3 rounded" style="max-height: 600px; overflow-y: auto; font-size: 0.9rem; line-height: 1.5; border: 1px solid #dee2e6;">{%= log_content %}</pre>
                </div>
            </div>
        </div>
    </div>
</div>

