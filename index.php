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

// ユニークなグラデーションカラーを HSL で生成
function hslColorFromString($str, $offset = 0) {
    $hash = crc32($str . $offset);
    $hue = $hash % 360;
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
      line-height: 1.2;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .gradient-text {
      background: linear-gradient(270deg, var(--grad-start), var(--grad-end), var(--grad-start));
      background-size: 600% 600%;
      animation: moveGradient 6s ease infinite;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      text-fill-color: transparent;
      cursor: pointer;
      display: inline-block;
    }

    @keyframes moveGradient {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    .host-style {
      font-size: 5em;
    }

    .ip-style {
      font-size: 2em;
      margin-top: 0.5em;
    }

    .progress-bar {
      position: fixed;
      z-index: 9999;
      transition: width 0.4s ease, height 0.4s ease, opacity 0.4s ease;
    }

    #progress-bar-top {
      top: 0;
      left: 0;
      width: 0%;
      height: 4px;
      background: linear-gradient(to right, var(--grad-start), var(--grad-end));
    }

    #progress-bar-bottom {
      bottom: 0;
      right: 0;
      width: 0%;
      height: 4px;
      background: linear-gradient(to left, var(--grad-start), var(--grad-end));
    }

    #progress-bar-left {
      bottom: 0;
      left: 0;
      width: 4px;
      height: 0%;
      background: linear-gradient(to top, var(--grad-start), var(--grad-end));
    }

    #progress-bar-right {
      top: 0;
      right: 0;
      width: 4px;
      height: 0%;
      background: linear-gradient(to bottom, var(--grad-start), var(--grad-end));
    }

    @media (max-width: 600px) {
      .host-style { font-size: 3em; }
      .ip-style   { font-size: 1.5em; }
    }
  </style>
</head>
<body>

  <!-- 四辺バー -->
  <div id="progress-bar-top" class="progress-bar"></div>
  <div id="progress-bar-bottom" class="progress-bar"></div>
  <div id="progress-bar-left" class="progress-bar"></div>
  <div id="progress-bar-right" class="progress-bar"></div>

  <div class="info">
    <?php if ($show_host): ?>
      <div class="host-style gradient-text" onclick="copyWithFeedback(this, '<?= htmlspecialchars($host) ?>')">
        <?= htmlspecialchars($host) ?>
      </div>
      <div class="ip-style gradient-text" onclick="copyWithFeedback(this, '<?= htmlspecialchars($ip) ?>')">
        <?= htmlspecialchars($ip) ?>
      </div>
    <?php else: ?>
      <div class="host-style gradient-text" onclick="copyWithFeedback(this, '<?= htmlspecialchars($ip) ?>')">
        <?= htmlspecialchars($ip) ?>
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
    });
  </script>
</body>
</html>
