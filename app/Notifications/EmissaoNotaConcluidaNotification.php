<?php

namespace App\Notifications;

use App\Models\NotaFiscal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmissaoNotaConcluidaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public NotaFiscal $nota;

    /**
     * Create a new notification instance.
     */
    public function __construct(NotaFiscal $nota)
    {
        $this->nota = $nota;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('notas.show', $this->nota->id);
        
        return (new MailMessage)
            ->subject('Nota Fiscal Emitida com Sucesso: ' . $this->nota->numero_nfse)
            ->greeting('Olá!')
            ->line('A nota fiscal para o cliente ' . $this->nota->tomador_nome . ' foi emitida e autorizada com sucesso na Sefin.')
            ->line('Número da Nota: ' . $this->nota->numero_nfse)
            ->line('Valor: R$ ' . number_format($this->nota->valor_servico, 2, ',', '.'))
            ->action('Visualizar Nota', $url)
            ->line('Obrigado por utilizar o nosso sistema!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'nota_id' => $this->nota->id,
            'numero_nfse' => $this->nota->numero_nfse,
            'tomador_nome' => $this->nota->tomador_nome,
            'mensagem' => "A nota fiscal (Nº {$this->nota->numero_nfse}) para {$this->nota->tomador_nome} foi emitida com sucesso.",
            'status' => 'sucesso'
        ];
    }
}
