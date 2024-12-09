<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Models\Noticia;
use App\Models\User;
use Carbon\Carbon;

class ColetarNoticiasUOL extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'noticias:coletar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Coleta notícias da UOL e envia por e-mail';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            // Faz a requisição ao feed RSS
            $rssFeed = Http::get('https://rss.uol.com.br/feed/tecnologia.xml');
            
            // Verifica se a requisição foi bem-sucedida
            if (!$rssFeed->ok()) {
                $this->error('Erro ao buscar notícias');
                return;
            }
    
            // Carrega o conteúdo do XML
            $xmlContent = mb_convert_encoding($rssFeed->body(), 'UTF-8', 'auto');
            $xml = simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NOCDATA);
    
            if (!$xml) {
                $this->error('Erro ao processar o XML');
                return;
            }
    
            // Extrai as notícias
            $noticias = collect($xml->channel->item)->map(function ($item) {
                return [
                    'titulo' => (string) $item->title,
                    'link'   => (string) $item->link,
                    'data'   => (string) $item->pubDate,
                ];
            });
    
            // Processa cada notícia
            foreach ($noticias as $noticia) {
                $existe = Noticia::where('link', $noticia['link'])->exists();
    
                if (!$existe) {
                    // Traduzir o dia e o mês para inglês (se necessário)
                    $traduzirData = str_replace(
                        ['Seg', 'Dez'],
                        ['Mon', 'Dec'],
                        $noticia['data']
                    );

                    // Criar o objeto Carbon e formatar a data
                    $data = Carbon::createFromFormat('D, d M Y H:i:s O', $traduzirData);
                    $dataFormatada = $data->format('Y-m-d H:i:s');
                    
                    // Criar a nova notícia com a data formatada
                    $novaNoticia = Noticia::create([
                        'titulo' => $noticia['titulo'],
                        'link'   => $noticia['link'],
                        'data'   => $dataFormatada,
                    ]);
                    
                    // Obtém os usuários para enviar os e-mails
                    $usuarios = User::all();
    
                    // Envia os e-mails para todos os usuários
                    foreach ($usuarios as $usuario) {
                        Mail::send('emails.noticia', compact('novaNoticia'), function ($message) use ($usuario, $novaNoticia) {
                            $message->to($usuario->email)
                                ->subject('Nova Notícia: ' . $novaNoticia->titulo);
                        });
                    }
                }
            }
    
            $this->info('Notícias coletadas e enviadas com sucesso!');
        } catch (\Exception $e) {
            $this->error('Erro inesperado: ' . $e->getMessage());
        }
    }
}
