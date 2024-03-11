<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Storage;
use App\Models\Scan;
use App\Models\Funcionario;

new  #[Layout('layouts.app')] class extends Component {
    public function detect($faceImageDataUrl) {

        $client = new RekognitionClient([
            'version' => 'latest',
            'region'  => 'us-east-1',
            'credentials' => [
                'key'    => 'SUA_CHAVE_AQUI',
                'secret' => 'SEU_SEGREDO_AQUI',
            ],
        ]);

        $funcionarios = Funcionario::all();

        foreach($funcionarios as $funcionario){

            $now = now();
          

          $ultimoScan = Scan::where('funcionario_id', $funcionario->id)->latest()->first();
      
          
          if ($ultimoScan && $now->diffInMinutes($ultimoScan->created_at) <= 20) {
              continue; 
          }
          
          $inicioExpediente = now()->startOfDay()->setHour($funcionario->workingHours->start_hour);
          $inicioAlmoco = now()->startOfDay()->setHour($funcionario->workingHours->interval["start"]);
          $fimAlmoco = now()->startOfDay()->setHour($funcionario->workingHours->interval["end"]);
          $fimExpediente = now()->startOfDay()->setHour($funcionario->workingHours->end_hour);
        
          if ($now->diffInMinutes($inicioExpediente) <= 10) {
              $now = $inicioExpediente;
          }
          if ($now->diffInMinutes($inicioAlmoco) <= 10) {
              $now = $inicioAlmoco;
          }
          if ($now->diffInMinutes($fimAlmoco) <= 10) {
              $now = $fimAlmoco;
          }
          
         

             
              $result = $client->compareFaces([
                  'SimilarityThreshold' => 90, 
                  'SourceImage' => [
                      'Bytes' => file_get_contents($request->input('imagem')) 
                  ],
                  'TargetImage' => [
                      'Bytes' => file_get_contents(public_path('/imagens/'.$funcionario->imagem)) 
                  ]
              ]);
        $imageName = 'face_image_' . time() . '.jpg'; 
        $imagePath = 'images/' . $imageName; 
       
       
        $imageData = substr($faceImageDataUrl, strpos($faceImageDataUrl, ',') + 1);
        $imageData = base64_decode($imageData);
        if(!empty($result['FacesMatches'])){
        Storage::put($imagePath, $imageData);
     
        $scan =  new Scan();
        $scan->imagem = 'images/' . $imageName;
        if($now){
        $scan->created_at = $now->format('Y-m-d H:i:s.u');
        }
        $scan->save();
        session()->flash('success', 'Scan feito com sucesso');
    }else{
        session()->flash('error', 'Ponto não concedido!'); 
    }
        }
        session()->flash('error', 'Você ainda não pode bater ponto'); 

    }

    #[Computed]
    public function scans() {
        return Scan::all();
    }
}?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Reconhecimento Facial') }}
        </h2>
    </x-slot>

    <div x-data="faceRecognition" x-init="loadModels()" wire:ignore>
        <div class="flex justify-center">
            <div class="relative" x-show="modelsLoaded">
                <video id="video" height="600" width="600" class="rounded-lg"></video>
                <canvas id="canvas" class="absolute inset-0"></canvas>
            </div>
            <div x-show="!modelsLoaded">Carregando modelos...</div>
        </div>
    </div>

    @if(session('success'))
    <div className="bg-green-200 text-green-800 py-2 px-4 rounded">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div className="bg-red-200 text-red-800 py-2 px-4 rounded">
        {{ session('success') }}
    </div>
    @endif
@foreach($this->scans as $scan)
<p>{{ $scan->imagem }}</p>
@endforeach


    @assets 
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    @endassets

    @script 
    <script>
        Alpine.data('faceRecognition', () => ({
            modelsLoaded: false,
            startVideo() {
                navigator.mediaDevices.getUserMedia({ video: { width: 300 } })
                    .then(stream => {
                        const video = document.getElementById('video');
                        video.srcObject = stream;
                        video.play();
                        setInterval(this.faceMyDetect, 2500); // Chamada da detecção facial em intervalos
                    })
                    .catch(err => {
                        console.error("error:", err);
                    });
            },
            async faceMyDetect() {
                const canvas = document.getElementById('canvas');
                const video = document.getElementById('video');
                if (canvas && video) {
                    canvas.innerHTML = faceapi.createCanvasFromMedia(video);
                    const displaySize = {
                        width: 450, // definir largura e altura conforme necessário
                        height: 400
                    };
                    faceapi.matchDimensions(canvas, displaySize);
                    const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks().withFaceExpressions();
                    const resizedDetections = faceapi.resizeResults(detections, displaySize);
                    const context = canvas.getContext('2d');
                    if (context) {
                        context.clearRect(0, 0, displaySize.width, displaySize.height);
                        faceapi.draw.drawDetections(canvas, resizedDetections);
                        faceapi.draw.drawFaceLandmarks(canvas, resizedDetections);
                        faceapi.draw.drawFaceExpressions(canvas, resizedDetections);
                        if (detections.length > 0) {
                            const detection = detections[0];
                            const confidence = detection.detection.score;
                            const box = detection.detection.box;
                            if (confidence >= 0.7) {
                                const faceCanvas = document.createElement('canvas');
                                faceCanvas.width = box.width;
                                faceCanvas.height = box.height;
                                const faceContext = faceCanvas.getContext('2d');
                                faceContext.drawImage(video, box.x, box.y, box.width, box.height, 0, 0, box.width, box.height);
                                const faceImageDataURL = faceCanvas.toDataURL('image/jpeg');
                                @this.detect(faceImageDataURL)
                                console.log('Reconhecimento bem-sucedido:', faceImageDataURL);
                            } else {
                                console.log('Arrume a postura');
                            }
                        }
                    }
                }
            },
            loadModels() {
                Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri('/models'),
                    faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
                    faceapi.nets.faceRecognitionNet.loadFromUri('/models'),
                    faceapi.nets.faceExpressionNet.loadFromUri('/models')
                ]).then(() => {
                    this.modelsLoaded = true;
                    this.startVideo();
                });
            }
        }))
    </script>
    @endscript


</div>
