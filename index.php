<?php
$ip = $_SERVER["REMOTE_ADDR"];
$host = gethostbyaddr($ip);

// CLI 判定
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$is_cli = preg_match('/curl|wget|httpie|fetch/i', $ua);

if ($is_cli) {
    header("Content-Type: text/plain");
    echo $ip . "\n";
    exit;
}

$show_host = ($host !== $ip);
$hashSource = $show_host ? $host : $ip;

// グラデーション色を生成
function hslColorFromString($str, $offset = 0) {
    if (filter_var($str, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        // IPv6: 上位4ブロック = /64
        $blocks = explode(':', $str);
        $blocks = array_pad($blocks, 8, '0000'); // :: 対応で省略補完
        $prefix = implode(':', array_slice($blocks, 0, 4));
    } elseif (filter_var($str, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        // IPv4: 上位3オクテット = /24
        $octets = explode('.', $str);
        $prefix = implode('.', array_slice($octets, 0, 3));
    } else {
        // ホスト名など: 先頭12文字をベースに
        $prefix = substr($str, 0, 12);
    }

    // より分散性の高い sha1 ハッシュで hue 決定
    $hash = sha1($prefix . $offset);
    $dec = hexdec(substr($hash, 0, 6));
    $hue = $dec % 360;

    return "hsl($hue, 100%, 60%)";
}

$gradStart = hslColorFromString($hashSource, 0);
$gradEnd = hslColorFromString($hashSource, 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your IP Address</title>
  <style>
    :root {
      --grad-start: <?= $gradStart ?>;
      --grad-end: <?= $gradEnd ?>;
    }

    html, body {
      height: 100%;
      margin: 0;
      background-color: #111;
      display: flex;
      justify-content: center;
      align-items: center;
      font-family: Impact, 'Arial Black', sans-serif;
      color: #fff;
      overflow: hidden;
    }

    .info {
      text-align: center;
      line-height: 1.4;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 1em;
    }

    .fit-wrapper {
      width: 90vw;
      display: flex;
      justify-content: center;
      overflow: hidden;
    }

    .gradient-text {
      background: linear-gradient(270deg, var(--grad-start), var(--grad-end), var(--grad-start));
      background-size: 600% 600%;
      animation: moveGradient 30s ease infinite;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      text-fill-color: transparent;
      white-space: nowrap;
      display: inline-block;
      transform-origin: center;
      cursor: pointer;
    }

    .host-style {
      font-size: 5em;
    }

    .ip-style {
      font-size: 2em;
    }

    @keyframes moveGradient {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    .progress-bar {
      position: fixed;
      z-index: 9999;
      transition: width 0.4s ease, height 0.4s ease, opacity 0.4s ease;
    }

    #progress-bar-top {
      top: 0; left: 0;
      width: 0%; height: 4px;
      background: linear-gradient(to right, var(--grad-start), var(--grad-end));
    }

    #progress-bar-bottom {
      bottom: 0; right: 0;
      width: 0%; height: 4px;
      background: linear-gradient(to left, var(--grad-start), var(--grad-end));
    }

    #progress-bar-left {
      bottom: 0; left: 0;
      width: 4px; height: 0%;
      background: linear-gradient(to top, var(--grad-start), var(--grad-end));
    }

    #progress-bar-right {
      top: 0; right: 0;
      width: 4px; height: 0%;
      background: linear-gradient(to bottom, var(--grad-start), var(--grad-end));
    }

    @media (max-width: 600px) {
      .host-style { font-size: 3em; }
      .ip-style   { font-size: 1.5em; }
    }
  </style>
</head>
<body>

  <!-- 読み込みバー -->
  <div id="progress-bar-top" class="progress-bar"></div>
  <div id="progress-bar-bottom" class="progress-bar"></div>
  <div id="progress-bar-left" class="progress-bar"></div>
  <div id="progress-bar-right" class="progress-bar"></div>

  <div class="info">
    <?php if ($show_host): ?>
      <div class="fit-wrapper">
        <div id="host" class="gradient-text host-style" onclick="copyWithFeedback(this, '<?= htmlspecialchars($host) ?>')">
          <?= htmlspecialchars($host) ?>
        </div>
      </div>
      <div class="fit-wrapper">
        <div id="ip" class="gradient-text ip-style" onclick="copyWithFeedback(this, '<?= htmlspecialchars($ip) ?>')">
          <?= htmlspecialchars($ip) ?>
        </div>
      </div>
    <?php else: ?>
      <div class="fit-wrapper">
        <div id="host" class="gradient-text host-style" onclick="copyWithFeedback(this, '<?= htmlspecialchars($ip) ?>')">
          <?= htmlspecialchars($ip) ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <script>
    function copyWithFeedback(el, text) {
      navigator.clipboard.writeText(text).then(() => {
        const original = el.innerText;
        el.innerText = "Copied";
        setTimeout(() => {
          el.innerText = original;
        }, 1000);
      });
    }

    function scaleToFit(el) {
      const parent = el.parentElement;
      const scale = parent.offsetWidth / el.scrollWidth;
      el.style.transform = `scale(${Math.min(scale, 1)})`;
    }

    const topBar = document.getElementById('progress-bar-top');
    const bottomBar = document.getElementById('progress-bar-bottom');
    const leftBar = document.getElementById('progress-bar-left');
    const rightBar = document.getElementById('progress-bar-right');

    let progress = 0;
    const interval = setInterval(() => {
      progress += Math.random() * 10;
      if (progress < 90) {
        topBar.style.width = progress + '%';
        bottomBar.style.width = progress + '%';
        leftBar.style.height = progress + '%';
        rightBar.style.height = progress + '%';
      }
    }, 100);

    window.addEventListener('load', () => {
      clearInterval(interval);
      topBar.style.width = '100%';
      bottomBar.style.width = '100%';
      leftBar.style.height = '100%';
      rightBar.style.height = '100%';

      setTimeout(() => {
        topBar.style.opacity = '0';
        bottomBar.style.opacity = '0';
        leftBar.style.opacity = '0';
        rightBar.style.opacity = '0';
      }, 300);

      setTimeout(() => {
        topBar.remove();
        bottomBar.remove();
        leftBar.remove();
        rightBar.remove();
      }, 800);

      // 自動縮小（最大サイズ維持）実行
      const hostEl = document.getElementById('host');
      const ipEl = document.getElementById('ip');
      if (hostEl) scaleToFit(hostEl);
      if (ipEl) scaleToFit(ipEl);
    });
  </script>
</body>
</html>
