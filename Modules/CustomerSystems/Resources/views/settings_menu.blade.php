<li @if (Route::is('mailboxes.customersystems')) class="active" @endif>
    <a href="{{ route('mailboxes.customersystems', ['id' => $mailbox->id]) }}"><i class="glyphicon glyphicon-user"></i> Kundenprofil Abruf</a>
</li>
