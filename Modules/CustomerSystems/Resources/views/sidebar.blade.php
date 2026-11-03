<div class="customer-systems conv-sidebar-block" data-url="{{ route('customersystems.show', ['id' => $conversation->id]) }}">
    <div class="customer-systems-results panel-group accordion accordion-disabled accordion-empty" aria-live="polite">
        <div class="panel panel-default">
            <div id="customer-systems-details-{{ $conversation->id }}" class="panel-collapse">
                <div class="panel-body">
                    <p class="customer-section">Kundenprofil wird geladen …</p>
                    <button type="button" class="customer-systems-retry btn btn-link btn-xs sidebar-block-link"><i class="glyphicon glyphicon-refresh" aria-hidden="true"></i> Aktualisieren</button>
                </div>
            </div>
        </div>
    </div>
</div>
