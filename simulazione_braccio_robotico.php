<?php
$aframePath = __DIR__ . '/assets/vendor/aframe/aframe-1.7.0.min.js';
$extrasPath = __DIR__ . '/assets/vendor/aframe/aframe-extras-7.5.4.min.js';
$modelPath = __DIR__ . '/assets/robot-arm/model.glb';

foreach ([$aframePath, $extrasPath, $modelPath] as $requiredFile) {
    if (!is_file($requiredFile) || !is_readable($requiredFile)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        exit('Simulazione incompleta: manca un file VR sul server.');
    }
}

// InfinityFree applica una verifica JavaScript anche alle risorse statiche.
// Incorporando tutto nella pagina evitiamo richieste separate per JS e GLB.
$aframeJs = str_ireplace('</script', '<\/script', file_get_contents($aframePath));
$extrasJs = str_ireplace('</script', '<\/script', file_get_contents($extrasPath));
$modelBase64 = base64_encode(file_get_contents($modelPath));

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Braccio robotico VR</title>
    <script><?= $aframeJs ?></script>
    <script><?= $extrasJs ?></script>
    <style>
        body { margin: 0; background: #dfe9ee; }
        #loading { position: fixed; inset: 0; z-index: 20; display: grid; place-items: center; color: #123; background: #dfe9ee; font: 600 18px system-ui, sans-serif; pointer-events: none; }
        #loading[hidden], #secure-warning[hidden] { display: none; }
        #secure-warning { position: fixed; left: 12px; right: 12px; bottom: 12px; z-index: 30; padding: 12px 16px; border-radius: 8px; color: #442b00; background: #fff3cd; font: 600 15px system-ui, sans-serif; }
    </style>
</head>
<body>
    <div id="loading">Caricamento del braccio robotico…</div>
    <div id="secure-warning" hidden>Per avviare il visore VR questa pagina deve essere aperta tramite HTTPS.</div>

    <a-scene background="color: #dfe9ee" renderer="antialias: true; colorManagement: true" vr-mode-ui="enabled: true">
        <a-entity light="type: ambient; intensity: 1"></a-entity>
        <a-entity light="type: directional; intensity: 0.8" position="2 4 3"></a-entity>
        <a-entity id="robot-arm" gltf-model="url(data:model/gltf-binary;base64,<?= $modelBase64 ?>)" animation-mixer position="0 0 0"></a-entity>

        <a-entity id="camera-rig" position="0 0 5">
            <a-camera position="0 1.6 0" wasd-controls look-controls></a-camera>
            <a-entity laser-controls="hand: left"></a-entity>
            <a-entity laser-controls="hand: right"></a-entity>
        </a-entity>
    </a-scene>

    <script>
        const loading = document.getElementById('loading');
        const model = document.getElementById('robot-arm');
        const secureWarning = document.getElementById('secure-warning');
        const isLocalhost = ['localhost', '127.0.0.1', '::1'].includes(location.hostname);

        if (!window.isSecureContext && !isLocalhost) secureWarning.hidden = false;
        model.addEventListener('model-loaded', () => { loading.hidden = true; });
        model.addEventListener('model-error', () => { loading.textContent = 'Non è stato possibile aprire il modello 3D.'; });
    </script>
</body>
</html>
