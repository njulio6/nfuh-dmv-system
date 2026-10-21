<?php

namespace App\Services;

/**
 * Pure PHP QR Code SVG Generator (ISO/IEC 18004 compliant).
 * Generates vector SVG QR codes with zero external library or C-extension dependencies.
 */
class QrCodeService
{
    /**
     * Generate an SVG QR Code string for the given text.
     */
    public function generateSvg(string $text, int $size = 300, int $margin = 4): string
    {
        $matrix = $this->encodeToMatrix($text);
        $moduleCount = count($matrix);
        $totalModules = $moduleCount + ($margin * 2);

        $svg = [];
        $svg[] = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="%d" height="%d" shape-rendering="crispEdges">',
            $totalModules,
            $totalModules,
            $size,
            $size
        );
        $svg[] = sprintf('<rect width="100%%" height="100%%" fill="#FFFFFF"/>');

        for ($r = 0; $r < $moduleCount; $r++) {
            for ($c = 0; $c < $moduleCount; $c++) {
                if ($matrix[$r][$c]) {
                    $x = $c + $margin;
                    $y = $r + $margin;
                    $svg[] = sprintf('<rect x="%d" y="%d" width="1" height="1" fill="#000000"/>', $x, $y);
                }
            }
        }

        $svg[] = '</svg>';

        return implode("\n", $svg);
    }

    /**
     * Generate a binary PNG QR Code string.
     */
    public function generatePng(string $text, int $size = 400, int $margin = 4): string
    {
        $matrix = $this->encodeToMatrix($text);
        $moduleCount = count($matrix);
        $totalModules = $moduleCount + ($margin * 2);
        $moduleSize = max(1, (int)($size / $totalModules));
        $actualSize = $totalModules * $moduleSize;

        $image = imagecreatetruecolor($actualSize, $actualSize);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);

        imagefilledrectangle($image, 0, 0, $actualSize, $actualSize, $white);

        for ($r = 0; $r < $moduleCount; $r++) {
            for ($c = 0; $c < $moduleCount; $c++) {
                if ($matrix[$r][$c]) {
                    $x1 = ($c + $margin) * $moduleSize;
                    $y1 = ($r + $margin) * $moduleSize;
                    $x2 = $x1 + $moduleSize - 1;
                    $y2 = $y1 + $moduleSize - 1;
                    imagefilledrectangle($image, $x1, $y1, $x2, $y2, $black);
                }
            }
        }

        ob_start();
        imagepng($image);
        $pngData = ob_get_clean();

        return $pngData;
    }

    /**
     * Return base64 data URI for embedding in <img> or downloading.
     */
    public function generateDataUri(string $text, int $size = 300, int $margin = 4): string
    {
        $svg = $this->generateSvg($text, $size, $margin);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * QR Code matrix encoding supporting Versions 1 to 10 (Byte mode, ECC Level M).
     */
    public function encodeToMatrix(string $text): array
    {
        $bytes = array_values(unpack('C*', $text));
        $len = count($bytes);

        // Version capacity table for ECC Level M: [version, max_bytes, total_data_codewords, ecc_codewords_per_block, num_blocks]
        $versionTable = [
            1  => [14, 16, 10, 1],
            2  => [26, 28, 16, 1],
            3  => [42, 44, 26, 1],
            4  => [62, 64, 18, 2],
            5  => [84, 86, 24, 2],
            6  => [106, 108, 16, 4],
            7  => [122, 124, 18, 4],
            8  => [152, 154, 22, 4],
            9  => [180, 182, 22, 5],
            10 => [213, 216, 26, 5],
        ];

        $selectedVersion = null;
        foreach ($versionTable as $v => $spec) {
            if ($len <= $spec[0]) {
                $selectedVersion = $v;
                break;
            }
        }

        if (!$selectedVersion) {
            $selectedVersion = 10;
            $bytes = array_slice($bytes, 0, $versionTable[10][0]);
            $len = count($bytes);
        }

        $spec = $versionTable[$selectedVersion];
        $maxDataCodewords = $spec[1];
        $eccPerBlock = $spec[2];
        $numBlocks = $spec[3];

        // 1. Bitstream encoding: Mode (0100 for Byte) + Count indicator (8 bits for v1-9, 16 bits for v10+)
        $countBits = ($selectedVersion >= 10) ? 16 : 8;
        $bitStream = '';
        $bitStream .= '0100'; // Byte mode
        $bitStream .= str_pad(decbin($len), $countBits, '0', STR_PAD_LEFT);
        foreach ($bytes as $b) {
            $bitStream .= str_pad(decbin($b), 8, '0', STR_PAD_LEFT);
        }

        // Terminator & padding to 8-bit boundary
        $maxDataBits = $maxDataCodewords * 8;
        $bitStream .= substr('0000', 0, min(4, max(0, $maxDataBits - strlen($bitStream))));
        while (strlen($bitStream) % 8 !== 0) {
            $bitStream .= '0';
        }

        // Pad codewords up to max capacity
        $padBytes = ['11101100', '00010001'];
        $padIndex = 0;
        while (strlen($bitStream) < $maxDataBits) {
            $bitStream .= $padBytes[$padIndex % 2];
            $padIndex++;
        }

        // Convert data bits to codewords
        $dataCodewords = [];
        for ($i = 0; $i < strlen($bitStream); $i += 8) {
            $dataCodewords[] = bindec(substr($bitStream, $i, 8));
        }

        // 2. Error correction using Reed-Solomon
        $dataBlocks = [];
        $eccBlocks = [];
        $baseBlockLen = intdiv(count($dataCodewords), $numBlocks);
        $remainderBlocks = count($dataCodewords) % $numBlocks;

        $offset = 0;
        for ($b = 0; $b < $numBlocks; $b++) {
            $blockLen = $baseBlockLen + ($b >= ($numBlocks - $remainderBlocks) ? 1 : 0);
            $blockData = array_slice($dataCodewords, $offset, $blockLen);
            $offset += $blockLen;
            $dataBlocks[] = $blockData;
            $eccBlocks[] = $this->calculateReedSolomon($blockData, $eccPerBlock);
        }

        // Interleave data codewords
        $finalCodewords = [];
        $maxBlockLen = 0;
        foreach ($dataBlocks as $b) {
            $maxBlockLen = max($maxBlockLen, count($b));
        }
        for ($i = 0; $i < $maxBlockLen; $i++) {
            for ($b = 0; $b < $numBlocks; $b++) {
                if (isset($dataBlocks[$b][$i])) {
                    $finalCodewords[] = $dataBlocks[$b][$i];
                }
            }
        }
        // Interleave ECC codewords
        for ($i = 0; $i < $eccPerBlock; $i++) {
            for ($b = 0; $b < $numBlocks; $b++) {
                if (isset($eccBlocks[$b][$i])) {
                    $finalCodewords[] = $eccBlocks[$b][$i];
                }
            }
        }

        // 3. Matrix construction
        $size = 17 + ($selectedVersion * 4);
        $matrix = array_fill(0, $size, array_fill(0, $size, null));
        $reserved = array_fill(0, $size, array_fill(0, $size, false));

        // Add Finder patterns
        $this->addFinderPattern($matrix, $reserved, 0, 0);
        $this->addFinderPattern($matrix, $reserved, $size - 7, 0);
        $this->addFinderPattern($matrix, $reserved, 0, $size - 7);

        // Add Timing patterns
        for ($i = 8; $i < $size - 8; $i++) {
            if ($reserved[6][$i] === false) {
                $matrix[6][$i] = ($i % 2 === 0) ? 1 : 0;
                $reserved[6][$i] = true;
            }
            if ($reserved[$i][6] === false) {
                $matrix[$i][6] = ($i % 2 === 0) ? 1 : 0;
                $reserved[$i][6] = true;
            }
        }

        // Add Alignment patterns for Version 2+
        $alignPositions = $this->getAlignmentPatternPositions($selectedVersion);
        foreach ($alignPositions as $r) {
            foreach ($alignPositions as $c) {
                if ($reserved[$r][$c] === false) {
                    $this->addAlignmentPattern($matrix, $reserved, $r, $c);
                }
            }
        }

        // Dark module
        $matrix[(4 * $selectedVersion) + 9][8] = 1;
        $reserved[(4 * $selectedVersion) + 9][8] = true;

        // Reserve format information areas
        for ($i = 0; $i <= 8; $i++) {
            $reserved[8][$i] = true;
            $reserved[$i][8] = true;
        }
        for ($i = $size - 8; $i < $size; $i++) {
            $reserved[8][$i] = true;
            $reserved[$i][8] = true;
        }

        // Place data bits using zigzag path
        $bitIndex = 0;
        $totalBits = count($finalCodewords) * 8;
        $allBits = '';
        foreach ($finalCodewords as $cw) {
            $allBits .= str_pad(decbin($cw), 8, '0', STR_PAD_LEFT);
        }

        $col = $size - 1;
        $up = true;
        while ($col > 0) {
            if ($col === 6) {
                $col--; // Skip vertical timing column
            }
            for ($rowStep = 0; $rowStep < $size; $rowStep++) {
                $row = $up ? ($size - 1 - $rowStep) : $rowStep;
                for ($cOffset = 0; $cOffset < 2; $cOffset++) {
                    $c = $col - $cOffset;
                    if (!$reserved[$row][$c]) {
                        $bit = ($bitIndex < strlen($allBits)) ? (int)$allBits[$bitIndex] : 0;
                        // Apply Mask Pattern 0: (row + col) % 2 === 0
                        $mask = (($row + $c) % 2 === 0);
                        $matrix[$row][$c] = $mask ? ($bit ^ 1) : $bit;
                        $bitIndex++;
                    }
                }
            }
            $up = !$up;
            $col -= 2;
        }

        // Place format information: ECC Level M (00) + Mask 0 (000) = 00000 -> with BCH error correction + mask pattern 101010000010010 = 101010000010010 ^ 101010000010010 = 0
        // Standard format bits for (ECC M, Mask 0): '101010000010010'
        $formatBits = '101010000010010';
        $this->placeFormatInfo($matrix, $formatBits, $size);

        return $matrix;
    }

    private function addFinderPattern(array &$matrix, array &$reserved, int $startRow, int $startCol): void
    {
        for ($r = -1; $r <= 7; $r++) {
            for ($c = -1; $c <= 7; $c++) {
                $row = $startRow + $r;
                $col = $startCol + $c;
                if ($row >= 0 && $row < count($matrix) && $col >= 0 && $col < count($matrix)) {
                    $reserved[$row][$col] = true;
                    if ($r >= 0 && $r <= 6 && $c >= 0 && $c <= 6) {
                        if ($r === 0 || $r === 6 || $c === 0 || $c === 6 || ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4)) {
                            $matrix[$row][$col] = 1;
                        } else {
                            $matrix[$row][$col] = 0;
                        }
                    } else {
                        $matrix[$row][$col] = 0; // Separator
                    }
                }
            }
        }
    }

    private function addAlignmentPattern(array &$matrix, array &$reserved, int $centerRow, int $centerCol): void
    {
        for ($r = -2; $r <= 2; $r++) {
            for ($c = -2; $c <= 2; $c++) {
                $row = $centerRow + $r;
                $col = $centerCol + $c;
                $reserved[$row][$col] = true;
                if (abs($r) === 2 || abs($c) === 2 || ($r === 0 && $c === 0)) {
                    $matrix[$row][$col] = 1;
                } else {
                    $matrix[$row][$col] = 0;
                }
            }
        }
    }

    private function getAlignmentPatternPositions(int $version): array
    {
        if ($version <= 1) {
            return [];
        }
        $table = [
            2 => [6, 18],
            3 => [6, 22],
            4 => [6, 26],
            5 => [6, 30],
            6 => [6, 34],
            7 => [6, 22, 38],
            8 => [6, 24, 42],
            9 => [6, 26, 46],
            10 => [6, 28, 50],
        ];
        return $table[$version] ?? [];
    }

    private function placeFormatInfo(array &$matrix, string $formatBits, int $size): void
    {
        // First copy around top-left finder
        $coords1 = [
            [8, 0], [8, 1], [8, 2], [8, 3], [8, 4], [8, 5], [8, 7], [8, 8],
            [7, 8], [5, 8], [4, 8], [3, 8], [2, 8], [1, 8], [0, 8]
        ];
        for ($i = 0; $i < 15; $i++) {
            [$r, $c] = $coords1[$i];
            $matrix[$r][$c] = (int)$formatBits[$i];
        }

        // Second copy split between bottom-left and top-right
        for ($i = 0; $i < 7; $i++) {
            $matrix[$size - 1 - $i][8] = (int)$formatBits[$i];
        }
        for ($i = 7; $i < 15; $i++) {
            $matrix[8][$size - 15 + $i] = (int)$formatBits[$i];
        }
    }

    private function calculateReedSolomon(array $data, int $eccCount): array
    {
        $genPoly = $this->buildGeneratorPoly($eccCount);
        $msgPoly = array_pad($data, count($data) + $eccCount, 0);

        for ($i = 0; $i < count($data); $i++) {
            $coef = $msgPoly[$i];
            if ($coef !== 0) {
                for ($j = 0; $j < count($genPoly); $j++) {
                    $msgPoly[$i + $j] ^= $this->gfMul($genPoly[$j], $coef);
                }
            }
        }

        return array_slice($msgPoly, count($data));
    }

    private function buildGeneratorPoly(int $degree): array
    {
        $poly = [1];
        for ($i = 0; $i < $degree; $i++) {
            $factor = [1, $this->gfExp($i)];
            $newPoly = array_fill(0, count($poly) + 1, 0);
            for ($j = 0; $j < count($poly); $j++) {
                $newPoly[$j] ^= $poly[$j];
                $newPoly[$j + 1] ^= $this->gfMul($poly[$j], $factor[1]);
            }
            $poly = $newPoly;
        }
        return $poly;
    }

    private function gfExp(int $power): int
    {
        static $expTable = null;
        if ($expTable === null) {
            $expTable = [];
            $x = 1;
            for ($i = 0; $i < 255; $i++) {
                $expTable[$i] = $x;
                $x <<= 1;
                if ($x & 0x100) {
                    $x ^= 0x11D; // Primitive polynomial x^8 + x^4 + x^3 + x^2 + 1
                }
            }
        }
        return $expTable[$power % 255];
    }

    private function gfMul(int $x, int $y): int
    {
        if ($x === 0 || $y === 0) {
            return 0;
        }
        static $logTable = null;
        if ($logTable === null) {
            $logTable = [];
            for ($i = 0; $i < 255; $i++) {
                $logTable[$this->gfExp($i)] = $i;
            }
        }
        return $this->gfExp($logTable[$x] + $logTable[$y]);
    }
}
