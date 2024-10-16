<?php
require 'vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;

function generateQRCode($data, $size = 300) {
    $qrCode = new QrCode($data);
    $qrCode->setSize($size);
    $qrCode->setMargin(10);
    $qrCode->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh);

    $pngWriter = new PngWriter();
    $pngResult = $pngWriter->write($qrCode);
    
    $svgWriter = new SvgWriter();
    $svgResult = $svgWriter->write($qrCode);

    return [
        'png' => $pngResult->getDataUri(),
        'svg' => $svgResult->getDataUri(),
    ];
}

function generateBarcode($data, $type = 'C128', $width = 2, $height = 100) {
    $generatorPNG = new BarcodeGeneratorPNG();
    $generatorSVG = new BarcodeGeneratorSVG();

    try {
        $pngBarcode = 'data:image/png;base64,' . base64_encode($generatorPNG->getBarcode($data, $type, $width, $height));
        $svgBarcode = $generatorSVG->getBarcode($data, $type, $width, $height);
        
        return [
            'png' => $pngBarcode,
            'svg' => $svgBarcode,
            'error' => null
        ];
    } catch (Exception $e) {
        return [
            'png' => null,
            'svg' => null,
            'error' => $e->getMessage()
        ];
    }
}

$code = '';
$type = 'qr';
$barcodeType = 'C128';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';
    $type = $_POST['type'] ?? 'qr';
    $barcodeType = $_POST['barcode_type'] ?? 'C128';

    if ($type === 'qr') {
        $result = generateQRCode($code);
    } else {
        $result = generateBarcode($code, $barcodeType);
        if ($result['error']) {
            $error = $result['error'];
        }
    }

    if (isset($_POST['download']) && !$error) {
        $format = $_POST['format'];
        $resolution = $_POST['resolution'];
        
        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary"); 
        header("Content-disposition: attachment; filename=\"" . $type . "_code." . $format . "\""); 
        
        if ($format === 'png') {
            echo base64_decode(explode(',', $result['png'])[1]);
        } elseif ($format === 'svg') {
            echo $result['svg'];
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code and Barcode Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <header class="bg-white shadow-md p-4 mb-8">
        <div class="container mx-auto flex items-center justify-center">
            <!-- Replace with your actual logo -->
            <img src="logo.png" alt="Logo" class="h-12">
        </div>
    </header>

    <main class="flex-grow container mx-auto px-4">
        <div class="bg-white p-8 rounded-lg shadow-md max-w-md mx-auto">
            <h1 class="text-2xl font-bold mb-6 text-center">QR Code and Barcode Generator</h1>
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Error:</strong>
                    <span class="block sm:inline"><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>
            <form method="post" class="space-y-4">
                <div>
                    <label for="code" class="block mb-1 font-medium">Enter text or URL:</label>
                    <input type="text" id="code" name="code" value="<?= htmlspecialchars($code) ?>" required class="w-full px-3 py-2 border rounded-md">
                </div>
                <div>
                    <label for="type" class="block mb-1 font-medium">Code Type:</label>
                    <select id="type" name="type" class="w-full px-3 py-2 border rounded-md">
                        <option value="qr" <?= $type === 'qr' ? 'selected' : '' ?>>QR Code</option>
                        <option value="barcode" <?= $type === 'barcode' ? 'selected' : '' ?>>Barcode</option>
                    </select>
                </div>
                <div id="barcodeOptions" class="<?= $type === 'qr' ? 'hidden' : '' ?>">
                    <label for="barcode_type" class="block mb-1 font-medium">Barcode Type:</label>
                    <select id="barcode_type" name="barcode_type" class="w-full px-3 py-2 border rounded-md">
                        <option value="C128" <?= $barcodeType === 'C128' ? 'selected' : '' ?>>Code 128</option>
                        <option value="C39" <?= $barcodeType === 'C39' ? 'selected' : '' ?>>Code 39</option>
                        <option value="EAN13" <?= $barcodeType === 'EAN13' ? 'selected' : '' ?>>EAN-13</option>
                    </select>
                </div>
                <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded-md hover:bg-blue-600">Generate</button>
            </form>
            
            <?php if (!empty($code) && isset($result) && !$error): ?>
                <div class="mt-8 text-center">
                    <h2 class="text-xl font-semibold mb-4">Generated Code</h2>
                    <img src="<?= $result['png'] ?>" alt="Generated Code" class="mx-auto mb-4">
                    <form method="post" class="space-y-4">
                        <input type="hidden" name="code" value="<?= htmlspecialchars($code) ?>">
                        <input type="hidden" name="type" value="<?= $type ?>">
                        <input type="hidden" name="barcode_type" value="<?= $barcodeType ?>">
                        <div>
                            <label for="format" class="block mb-1 font-medium">Download Format:</label>
                            <select id="format" name="format" class="w-full px-3 py-2 border rounded-md">
                                <option value="png">PNG</option>
                                <option value="svg">SVG</option>
                            </select>
                        </div>
                        <div>
                            <label for="resolution" class="block mb-1 font-medium">Resolution:</label>
                            <select id="resolution" name="resolution" class="w-full px-3 py-2 border rounded-md">
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <button type="submit" name="download" value="1" class="w-full bg-green-500 text-white py-2 rounded-md hover:bg-green-600">Download</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="bg-gray-800 text-white py-4 mt-8">
        <div class="container mx-auto text-center">
            <p>&copy; <?= date('Y') ?> QR Code and Barcode Generator. CORESOLUTIONSAll rights reserved.</p>
        </div>
    </footer>

    <script>
        document.getElementById('type').addEventListener('change', function() {
            document.getElementById('barcodeOptions').classList.toggle('hidden', this.value === 'qr');
        });
    </script>
</body>
</html>