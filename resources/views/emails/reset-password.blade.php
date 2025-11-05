@component('mail::message')
# Redefinição de senha

Recebemos um pedido para redefinir a sua senha no aplicativo **Coinxinhas – Salgados do Marquês**.

Clique no botão abaixo para escolher uma nova senha. Este link expira em 15 minutos.

@component('mail::button', ['url' => $resetUrl])
Redefinir senha
@endcomponent

Se você não solicitou esta alteração, nenhuma ação é necessária.

Atenciosamente,<br>
Equipe Salgados do Marquês
@endcomponent
