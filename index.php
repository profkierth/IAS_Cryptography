<?php

function toUpper(string $s): string { return strtoupper($s); }
function lettersOnly(string $s): string { return preg_replace('/[^A-Z]/', '', strtoupper($s)); }
function modInv(int $a, int $m): int {
    $a = (($a % $m) + $m) % $m;
    for ($x = 1; $x < $m; $x++) if (($a * $x) % $m === 1) return $x;
    return -1;
}


function buildPlayfairSquare(string $key): array {
    $k = str_replace('J', 'I', lettersOnly($key));
    $sq = []; $used = array_fill(0, 26, false); $used[ord('J') - 65] = true;
    for ($i = 0; $i < strlen($k); $i++) {
        $idx = ord($k[$i]) - 65;
        if (!$used[$idx]) { $used[$idx] = true; $sq[] = $k[$i]; }
    }
    for ($i = 0; $i < 26; $i++) if (!$used[$i]) $sq[] = chr(65 + $i);
    return $sq;
}
function pfPos(array $sq, string $c): array {
    if ($c === 'J') $c = 'I';
    for ($i = 0; $i < 25; $i++) if ($sq[$i] === $c) return [(int)($i / 5), $i % 5];
    return [-1, -1];
}
function playfairProcess(string $text, string $key, bool $encrypt): string {
    $sq = buildPlayfairSquare($key);
    $t  = str_replace('J', 'I', lettersOnly($text));
    $chars = str_split($t); $dg = []; $i = 0;
    while ($i < count($chars)) {
        $a = $chars[$i]; $b = isset($chars[$i + 1]) ? $chars[$i + 1] : 'X';
        $filler = ($a === 'X') ? 'Q' : 'X';
        if ($a === $b) { $dg[] = [$a, $filler]; $i++; }
        else           { $dg[] = [$a, $b];       $i += 2; }
    }
    $sh = $encrypt ? 1 : 4; $r = '';
    foreach ($dg as [$a, $b]) {
        [$ra, $ca] = pfPos($sq, $a); [$rb, $cb] = pfPos($sq, $b);
        if ($ra === $rb) {
            $r .= $sq[$ra * 5 + ($ca + $sh) % 5] . $sq[$rb * 5 + ($cb + $sh) % 5];
        } elseif ($ca === $cb) {
            $r .= $sq[(($ra + $sh) % 5) * 5 + $ca] . $sq[(($rb + $sh) % 5) * 5 + $cb];
        } else {
            $r .= $sq[$ra * 5 + $cb] . $sq[$rb * 5 + $ca];
        }
    }
    return $r;
}


const PIGPEN = [
    'A' => '[_ ]', 'B' => '[__]', 'C' => '[ _]', 'D' => '[| ]', 'E' => '[ | ]', 'F' => '[ |]',
    'G' => '[~ ]', 'H' => '[~~]', 'I' => '[ ~]', 'J' => '<. >', 'K' => '<..>', 'L' => '< .>',
    'M' => '(. )', 'N' => '(..)', 'O' => '( .)', 'P' => '[. ]', 'Q' => '[..]', 'R' => '[ .]',
    'S' => '/\\',  'T' => '\\|/', 'U' => '\\/',  'V' => '/\\.', 'W' => '\\|/.','X' => '\\/.', 'Y' => '//', 'Z' => '\\\\'
];
function pigpenEncode(string $text): string {
    $tokens = [];
    foreach (str_split(strtoupper($text)) as $c) {
        if (isset(PIGPEN[$c])) $tokens[] = PIGPEN[$c];
        elseif ($c === ' ')    $tokens[] = '/';
    }
    return json_encode($tokens);
}
function pigpenDecode(string $encoded): string {
    $rev = array_flip(PIGPEN); $r = '';
    $tokens = json_decode($encoded, true);
    if (!is_array($tokens)) $tokens = explode('|', trim($encoded, "|\r\n"));
    foreach ($tokens as $t) {
        $t = trim((string)$t);
        if ($t === '/')          $r .= ' ';
        elseif (isset($rev[$t])) $r .= $rev[$t];
        elseif ($t !== '')       $r .= '?';
    }
    return $r;
}


function hillDet(array $m): int { return $m[0][0] * $m[1][1] - $m[0][1] * $m[1][0]; }
function hillInverse(array $m): array {
    $det = ((hillDet($m) % 26) + 26) % 26;
    $di  = modInv($det, 26);
    if ($di === -1) throw new Exception("Key matrix not invertible mod 26");
    return [
        [( $m[1][1] * $di % 26 + 26) % 26, (-$m[0][1] * $di % 26 + 26) % 26],
        [(-$m[1][0] * $di % 26 + 26) % 26, ( $m[0][0] * $di % 26 + 26) % 26],
    ];
}
function keyToMatrix(string $key): array {
    $k = lettersOnly($key); while (strlen($k) < 4) $k .= 'A';
    return [[ord($k[0]) - 65, ord($k[1]) - 65], [ord($k[2]) - 65, ord($k[3]) - 65]];
}
function hillProcess(string $text, string $key, bool $encrypt): string {
    $mat = keyToMatrix($key);
    $det = ((hillDet($mat) % 26) + 26) % 26;
    if (modInv($det, 26) === -1) throw new Exception("Key matrix det has no inverse mod 26");
    $m = $encrypt ? $mat : hillInverse($mat);
    $t = lettersOnly($text); if (strlen($t) % 2 !== 0) $t .= 'X';
    $r = '';
    for ($i = 0; $i < strlen($t); $i += 2) {
        $v0 = ord($t[$i]) - 65; $v1 = ord($t[$i + 1]) - 65;
        $r .= chr(65 + ($m[0][0] * $v0 + $m[0][1] * $v1) % 26)
            . chr(65 + ($m[1][0] * $v0 + $m[1][1] * $v1) % 26);
    }
    return $r;
}

function isCoprime26(int $a): bool {
    return in_array((($a % 26) + 26) % 26, [1, 3, 5, 7, 9, 11, 15, 17, 19, 21, 23, 25]);
}
function affineProcess(string $text, int $a, int $b, bool $encrypt): string {
    if (!isCoprime26($a)) throw new Exception("'a' must be coprime with 26 (valid: 1,3,5,7,9,11,15,17,19,21,23,25)");
    $aInv = modInv((($a % 26) + 26) % 26, 26); $r = '';
    foreach (str_split(strtoupper($text)) as $c) {
        if ($c >= 'A' && $c <= 'Z') {
            $x = ord($c) - 65;
            $y = $encrypt ? ($a * $x + $b) % 26 : ($aInv * (($x - $b + 260) % 26)) % 26;
            $r .= chr(65 + (($y % 26 + 26) % 26));
        } else $r .= $c;
    }
    return $r;
}


function vigenereProcess(string $text, string $key, bool $encrypt): string {
    $k = preg_replace('/[^A-Z0-9]/', '', strtoupper($key));
    if (!$k) throw new Exception("Key cannot be empty");
    $k = preg_replace_callback('/[1-9]/', function ($m) { return chr(64 + (int)$m[0]); }, $k);
    $r = ''; $ki = 0;
    foreach (str_split(strtoupper($text)) as $c) {
        if ($c >= 'A' && $c <= 'Z') {
            $x  = ord($c) - 65;
            $sh = ord($k[$ki % strlen($k)]) - 65;
            $y  = $encrypt ? ($x + $sh) % 26 : (($x - $sh + 26) % 26);
            $r .= chr(65 + $y); $ki++;
        } else $r .= $c;
    }
    return $r;
}


if (isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    $cipher = $_POST['cipher']      ?? '1';
    $mode   = $_POST['mode']        ?? 'enc';
    $text   = $_POST['text']        ?? '';
    $key    = $_POST['key']         ?? '';
    $a_key  = (int)($_POST['a_key'] ?? 5);
    $b_key  = (int)($_POST['b_key'] ?? 8);
    $pm     = $_POST['pigpen_mode'] ?? 'enc';
    try {
        $enc = ($mode === 'enc');
        switch ($cipher) {
            case '1': $r = playfairProcess($text, $key, $enc);                         break;
            case '2': $r = ($pm === 'enc') ? pigpenEncode($text) : pigpenDecode($text); break;
            case '3': $r = hillProcess($text, $key, $enc);                             break;
            case '4': $r = affineProcess($text, $a_key, $b_key, $enc);                break;
            case '5': $r = vigenereProcess($text, $key, $enc);                         break;
            default:  $r = '';
        }
        echo json_encode(['ok' => true, 'result' => $r]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Classical Ciphers — Group 3</title>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=IBM+Plex+Mono:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page">


  <header class="header">
    <div class="header-tag">Group 3 &mdash; Classical Cryptography</div>
    <h1>CLASSICAL<br>CIPHERS</h1>
    <p class="header-sub">Playfair &middot; Pigpen &middot; Hill &middot; Affine &middot; Vigen&egrave;re</p>
  </header>


  <div class="tabs" role="tablist">
    <button class="tab-btn active" onclick="switchTab(1)" id="tab1"><span class="tab-num">01</span>PLAYFAIR</button>
    <button class="tab-btn"        onclick="switchTab(2)" id="tab2"><span class="tab-num">02</span>PIGPEN</button>
    <button class="tab-btn"        onclick="switchTab(3)" id="tab3"><span class="tab-num">03</span>HILL</button>
    <button class="tab-btn"        onclick="switchTab(4)" id="tab4"><span class="tab-num">04</span>AFFINE</button>
    <button class="tab-btn"        onclick="switchTab(5)" id="tab5"><span class="tab-num">05</span>VIGEN&Egrave;RE</button>
  </div>

  
  <div class="panel active" id="panel1">
    <div class="card">
      <p class="cipher-desc">
        Encrypts <strong>digraphs</strong> (letter pairs) using a <strong>5&times;5 key square</strong>.
        Letters I and J share one cell. Duplicate pairs are split with X (or Q if the pair is XX).
      </p>
      <div class="mode-toggle">
        <button class="mode-btn enc active" onclick="setMode(this,'enc')">&#8594; ENCRYPT</button>
        <button class="mode-btn dec"        onclick="setMode(this,'dec')">&#8592; DECRYPT</button>
      </div>
      <div class="form-grid">
        <div class="form-row">
          <div class="field">
            <label id="pf-text-lbl">Plaintext</label>
            <input type="text" id="pf-text" placeholder="e.g. HELLO WORLD" oninput="updatePFSquare()">
          </div>
          <div class="field">
            <label>Keyword</label>
            <input type="text" id="pf-key" value="KEYWORD" placeholder="e.g. KEYWORD" oninput="updatePFSquare()">
          </div>
        </div>
      </div>
      <button class="run-btn" onclick="run(1)"><span>RUN PLAYFAIR</span></button>
      <div class="result-wrap" id="res1">
        <div class="result-header">
          <span class="result-label" id="res1-lbl">CIPHERTEXT</span>
          <button class="copy-btn" onclick="copyResult('res1-text')">COPY</button>
        </div>
        <div class="result-body"><div class="result-text" id="res1-text"></div></div>
      </div>
      <div class="sq-wrap" id="pf-sq-wrap">
        <div class="sq-header"><span class="sq-label">Key Square (5&times;5)</span></div>
        <div class="sq-grid" id="pf-sq"></div>
      </div>
    </div>
  </div>

 
  <div class="panel" id="panel2">
    <div class="card">
      <p class="cipher-desc">
        Replaces each letter with a <strong>geometric symbol</strong> derived from tic-tac-toe and X grids.
        Output is JSON tokens. To decode, paste the JSON output back in.
      </p>
      <div class="mode-toggle">
        <button class="mode-btn enc active" onclick="setMode(this,'enc')">&#8594; ENCODE</button>
        <button class="mode-btn dec"        onclick="setMode(this,'dec')">&#8592; DECODE</button>
      </div>
      <div class="form-grid">
        <div class="field">
          <label id="pp-text-lbl">Text</label>
          <input type="text" id="pp-text" placeholder="e.g. HELLO">
        </div>
      </div>
      <button class="run-btn" onclick="run(2)"><span>RUN PIGPEN</span></button>
      <div class="result-wrap" id="res2">
        <div class="result-header">
          <span class="result-label" id="res2-lbl">ENCODED</span>
          <button class="copy-btn" onclick="copyResult('res2-text')">COPY</button>
        </div>
        <div class="result-body"><div class="result-text" id="res2-text" style="font-size:.82rem;line-height:2;"></div></div>
      </div>
      <div class="pigpen-table" id="pp-chart"></div>
    </div>
  </div>

  
  <div class="panel" id="panel3">
    <div class="card">
      <p class="cipher-desc">
        Uses <strong>linear algebra</strong>: a 2&times;2 key matrix multiplies letter vectors mod 26.
        The key must be <strong>4 letters</strong> forming an invertible matrix.
      </p>
      <div class="mode-toggle">
        <button class="mode-btn enc active" onclick="setMode(this,'enc')">&#8594; ENCRYPT</button>
        <button class="mode-btn dec"        onclick="setMode(this,'dec')">&#8592; DECRYPT</button>
      </div>
      <div class="form-grid">
        <div class="form-row">
          <div class="field">
            <label id="hl-text-lbl">Plaintext</label>
            <input type="text" id="hl-text" placeholder="e.g. HELP">
          </div>
          <div class="field">
            <label>4-Letter Key</label>
            <input type="text" id="hl-key" value="GYBN" placeholder="e.g. GYBN" maxlength="4" oninput="updateHillMatrix()">
            <p class="hint">Forms matrix: [k0 k1] / [k2 k3]</p>
          </div>
        </div>
      </div>
      <button class="run-btn" onclick="run(3)"><span>RUN HILL</span></button>
      <div class="result-wrap" id="res3">
        <div class="result-header">
          <span class="result-label" id="res3-lbl">CIPHERTEXT</span>
          <button class="copy-btn" onclick="copyResult('res3-text')">COPY</button>
        </div>
        <div class="result-body"><div class="result-text" id="res3-text"></div></div>
      </div>
      <div class="sq-wrap" style="margin-top:1rem">
        <div class="sq-header"><span class="sq-label" style="color:var(--accent2)">Key Matrix</span></div>
        <div style="padding:12px 16px;font-size:.82rem;color:var(--text);line-height:2;" id="hl-matrix">—</div>
      </div>
    </div>
  </div>

 
  <div class="panel" id="panel4">
    <div class="card">
      <p class="cipher-desc">
        Formula: <strong>E(x) = (a&middot;x + b) mod 26</strong> &nbsp;|&nbsp;
        <strong>D(x) = a&#8315;&sup1;&middot;(x &minus; b) mod 26</strong>.
        Key <em>a</em> must be coprime with 26.
      </p>
      <div class="mode-toggle">
        <button class="mode-btn enc active" onclick="setMode(this,'enc')">&#8594; ENCRYPT</button>
        <button class="mode-btn dec"        onclick="setMode(this,'dec')">&#8592; DECRYPT</button>
      </div>
      <div class="form-grid">
        <div class="field">
          <label id="af-text-lbl">Plaintext</label>
          <input type="text" id="af-text" placeholder="e.g. HELLO WORLD">
        </div>
        <div class="form-row">
          <div class="field">
            <label>Key a</label>
            <input type="number" id="af-a" value="5" min="1" max="25">
            <p class="hint">Coprime with 26: 1,3,5,7,9,11,15,17,19,21,23,25</p>
          </div>
          <div class="field">
            <label>Key b</label>
            <input type="number" id="af-b" value="8" min="0" max="25">
            <p class="hint">Any value 0&ndash;25</p>
          </div>
        </div>
      </div>
      <button class="run-btn" onclick="run(4)"><span>RUN AFFINE</span></button>
      <div class="result-wrap" id="res4">
        <div class="result-header">
          <span class="result-label" id="res4-lbl">CIPHERTEXT</span>
          <button class="copy-btn" onclick="copyResult('res4-text')">COPY</button>
        </div>
        <div class="result-body"><div class="result-text" id="res4-text"></div></div>
      </div>
    </div>
  </div>

 
  <div class="panel" id="panel5">
    <div class="card">
      <p class="cipher-desc">
        Extends Caesar using a <strong>repeating keyword</strong>. Each keyword letter shifts the
        corresponding plaintext letter. Far harder to crack than simple Caesar.
      </p>
      <div class="mode-toggle">
        <button class="mode-btn enc active" onclick="setMode(this,'enc')">&#8594; ENCRYPT</button>
        <button class="mode-btn dec"        onclick="setMode(this,'dec')">&#8592; DECRYPT</button>
      </div>
      <div class="form-grid">
        <div class="form-row">
          <div class="field">
            <label id="vi-text-lbl">Plaintext</label>
            <input type="text" id="vi-text" placeholder="e.g. ATTACK AT DAWN">
          </div>
          <div class="field">
            <label>Keyword</label>
            <input type="text" id="vi-key" value="LEMON" placeholder="e.g. LEMON">
          </div>
        </div>
      </div>
      <button class="run-btn" onclick="run(5)"><span>RUN VIGEN&Egrave;RE</span></button>
      <div class="result-wrap" id="res5">
        <div class="result-header">
          <span class="result-label" id="res5-lbl">CIPHERTEXT</span>
          <button class="copy-btn" onclick="copyResult('res5-text')">COPY</button>
        </div>
        <div class="result-body">
          <div class="result-text" id="res5-text"></div>
          <div id="vi-align" style="margin-top:10px;font-size:.7rem;color:var(--muted);line-height:2.2;"></div>
        </div>
      </div>
    </div>
  </div>

</div>
<script src="app.js"></script>
</body>
</html>

