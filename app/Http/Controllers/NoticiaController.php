<?php

namespace App\Http\Controllers;

use App\Models\Noticia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class NoticiaController extends Controller
{
    public function buscar()
    {
        $rssFeed = Http::get('https://rss.uol.com.br/feed/tecnologia.xml');

        if (!$rssFeed->ok()) {
            $this->error('Erro ao buscar notícias');
            return;
        }

        $xmlContent = mb_convert_encoding($rssFeed->body(), 'UTF-8', 'ISO-8859-1');
        $xml = simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NOCDATA);

        if (!$xml) {
            $this->error('Erro ao processar o XML');
            return;
        }

        $noticias = [];

        foreach ($xml->channel->item as $item) {
            $noticias[] = [
                'titulo' => (string) $item->title,
                'link'   => (string) $item->link,
                'data'   => (string) $item->pubDate,
            ];
        }

        foreach ($noticias as $noticia) {
            $existe = Noticia::where('link', $noticia['link'])->exists();

            if (!$existe) {
                // Tive que traduzir do portugues para o ingles para poder salvar no banco de dados
                $traduzirData = str_replace(
                    ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],
                    ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    $noticia['data']
                );
                
                $traduzirData = str_replace(
                    ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
                    ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    $traduzirData
                );

                $data = Carbon::createFromFormat('D, d M Y H:i:s O', $traduzirData, 'America/Sao_Paulo');
                $dataFormatada = $data->format('Y-m-d H:i:s');

                // dd($dataFormatada);
                $novaNoticia = Noticia::create([
                    'titulo' => $noticia['titulo'],
                    'link' => $noticia['link'],
                    'data' => $dataFormatada,
                ]);

                $usuarios = User::all();

                foreach ($usuarios as $usuario) {
                    Mail::send('emails.noticia', compact('novaNoticia'), function ($message) use ($usuario, $novaNoticia) {
                        $message->to($usuario->email)
                            ->subject('Nova Notícia: ' . $novaNoticia->titulo);
                    });
                }
            }
        }
    }
}
