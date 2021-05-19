<?php

namespace App\Http\Controllers\Api2;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class GerarPlanilhasController extends Controller
{

    public $request;

    public function __construct(Request $request) {
        ini_set('max_execution_time', 720);
        ini_set('memory_limit', '4096M');
    }

    public function gerarPlanilha() {
        if($this->request->params=='info') {
            header('Access-Control-Allow-Origin: *');
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            $data['csv_title'] = [
                'Nome',
                'Idade',
                'Sexo'
            ];
            $user_data = $this->user_data->get();
            echo json_encode([
                "index" => 0,
                "total" => count($user_data)
            ]);
            echo str_pad('', 4096) . "\n";
            @flush();
            foreach($user_data as $key => $item) {
                try{
                    $data['csv_body'][] = [
                        $item->nome,
                        $item->idade,
                        $item->sexo
                    ];
                    } catch (\Exception $e) {

                        // Registra a Exception no arquivo error.log

                        $error_code = date('Y-m-d H:i:s').'-'.$e->getMessage();
                        Storage::disk('dump_dados_csv')->append('error.log', $error_code . "\n");
                        
                        if(isset($error)){
                            $error++;
                        } else {
                            $error=1;
                        }
                    }
                    $payload = [
                        "error_cod" => $error_code??0, 
                        "index" => $key+1,
                        "nome" => $item->nome,
                        "data" => $item->nome,
                        "error" => $error??0
                    ];
                    unset($error_code);
                    echo json_encode($payload);
                    echo str_pad('', 4096) . "\n";
                    @ob_end_flush();
                    @flush();
                    @ob_start();
                }
    
                $title = implode(',', $data['csv_title']);
                $body = array_map(function($a){
                    return implode(',', $a);
                }, $data['csv_body']??[]);
                Storage::disk('dump_dados_csv')->put($this->ano_path.$this->request->params.'.csv', $title."\n\r".implode("\n\r", $body));

        } 
    }
}
