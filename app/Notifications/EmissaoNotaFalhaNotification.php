<?php

namespace App\Notifications;

use App\Models\NotaFiscal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmissaoNotaFalhaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public NotaFiscal $nota;
    public string $mensagemErro;

    /**
     * Create a new notification instance.
     */
    public function __construct(NotaFiscal $nota, string $mensagemErro)
    {
        $this->nota = $nota;
        $this->mensagemErro = $mensagemErro;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('notas.show', $this->nota->id);
        
        return (new MailMessage)
            ->subject('Atenção: Falha na Emissão da Nota Fiscal')
            ->greeting('Olá!')
            ->line('Houve um erro ao tentar emitir a nota fiscal para o cliente ' . $this->nota->tomador_nome . '.')
            ->line('Detalhes do Erro:')
            ->line($this->mensagemErro)
            ->action('Verificar Nota e Corrigir', $url)
            ->line('Por favor, revise os dados e tente emitir novamente.');
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
            'tomador_nome' => $this->nota->tomador_nome,
            'mensagem' => "Falha ao emitir nota para {$this->nota->tomador_nome}: {$this->mensagemErro}",
            'status' => 'erro'
        ];
    }
}
