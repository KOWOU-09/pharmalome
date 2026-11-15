<h1>Nouveau message depuis le site MedExpress TOGO</h1>
<p><strong>Nom :</strong> {{ $senderName }}</p>
<p><strong>E-mail :</strong> {{ $senderEmail }}</p>
<p><strong>Sujet :</strong> {{ $topic }}</p>
<p><strong>Message :</strong></p>
<p>{!! nl2br(e($body)) !!}</p>
