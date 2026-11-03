<div class="customer-systems conv-sidebar-block" data-url="{{ route('customersystems.show', ['id' => $conversation->id]) }}">
    <div class="panel-group accordion accordion-disabled accordion-empty">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title"><div><strong class="small">{{ $title }}</strong></div></h4>
            </div>
            <div id="customer-systems-details-{{ $conversation->id }}" class="panel-collapse">
                <div class="panel-body">
                    <div class="sidebar-block-header2"><strong>{{ $title }}</strong></div>
                    <div class="customer-systems-results" aria-live="polite"><p class="customer-section">Kundendaten werden geladen …</p></div>
                    <button type="button" class="customer-systems-retry btn btn-link btn-xs sidebar-block-link"><i class="glyphicon glyphicon-refresh" aria-hidden="true"></i> Aktualisieren</button>
                </div>
            </div>
        </div>
    </div>
</div>
