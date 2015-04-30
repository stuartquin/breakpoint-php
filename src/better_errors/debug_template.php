<?php
global $GlobalDebuggerFrames;
global $GlobalDebuggerExceptions;
$frames = $GlobalDebuggerFrames;
$exceptions = $GlobalDebuggerExceptions;

function formatted_lines($frame) {
  $linesBack = 8;
  $lines = $frame["lines"];
  $lineNum = $frame["line_num"];
  $startNum = max(0, $lineNum - $linesBack) + 1;
  $endNum = min(count($lines), $lineNum + $linesBack);
  $highlightLine = $lineNum;

  $output = "<pre data-line='".$highlightLine."'";
  $output .= " data-line-offset='".$startNum."'>";
  $output .= "<code class='language-php'>";

  for ($i = $startNum; $i < $endNum; $i++) {
    $line = $lines[$i];
    $className = "";
    if ($i + 1 === $lineNum) {
      $className = "highlight";
    }
    $output .= $line;
  }

  return $output."</code></pre>";
}

function formatted_nums($frame) {
  $output = "<div class='code_linenums'>";

  $linesBack = 7;
  $lines = $frame["lines"];
  $lineNum = $frame["line_num"];

  $startNum = max(0, $lineNum - $linesBack) + 1;
  $endNum = min(count($lines), $lineNum + $linesBack);

  for ($i = $startNum; $i < $endNum; $i++) {
    $line = $lines[$i];
    $className = "";
    if ($i + 1 === $lineNum) {
      $className = "highlight";
    }
    $output .= "<span class='{$className}'>{$i}</span>";
  }

  return $output."</div>";
}

function formatted_code($frame) {
  return formatted_lines($frame);
}
// header("Content-Type:text/html");
?>

<!DOCTYPE html>
<html>
<head>
<title></title>
<link rel="stylesheet" href="/lib/better_errors/prism.css" />
</head>
<body>
    <style>
    /* Basic reset */
    * {
        margin: 0;
        padding: 0;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th, td {
        vertical-align: top;
        text-align: left;
    }

    textarea {
        resize: none;
    }

    body {
        font-size: 10pt;
    }

    body, td, input, textarea {
        font-family: helvetica neue, lucida grande, sans-serif;
        line-height: 1.5;
        color: #333;
        text-shadow: 0 1px 0 rgba(255, 255, 255, 0.6);
    }

    html {
        background: #f0f0f5;
    }

    .clearfix::after{
        clear: both;
        content: ".";
        display: block;
        height: 0;
        visibility: hidden;
    }

    /* ---------------------------------------------------------------------
     * Basic layout
     * --------------------------------------------------------------------- */

    /* Small */
    @media screen and (max-width: 1100px) {
        html {
            overflow-y: scroll;
        }

        body {
            margin: 0 20px;
        }

        header.exception {
            margin: 0 -20px;
        }

        nav.sidebar {
            padding: 0;
            margin: 20px 0;
        }

        ul.frames {
            max-height: 200px;
            overflow: auto;
        }
    }

    /* Wide */
    @media screen and (min-width: 1100px) {
        header.exception {
           position: fixed;
           top: 0;
           left: 0;
           right: 0;
        }

        nav.sidebar,
        .frame_info {
            position: fixed;
            top: 95px;
            bottom: 0;

            box-sizing: border-box;

            overflow-y: auto;
            overflow-x: hidden;
        }

        nav.sidebar {
            width: 40%;
            left: 20px;
            top: 115px;
            bottom: 20px;
        }

        .frame_info {
            right: 0;
            left: 40%;

            padding: 20px;
            padding-left: 10px;
            margin-left: 30px;

            display: none;
        }

    }
    #frame_info_0 {
      display: block;
    }

    .exception {
      display: none;
    }
    
    #exception_0 {
      display: block;
    }

    nav.sidebar {
        background: #d3d3da;
        border-top: solid 3px #a33;
        border-bottom: solid 3px #a33;
        border-radius: 4px;
        box-shadow: 0 0 6px rgba(0, 0, 0, 0.2), inset 0 0 0 1px rgba(0, 0, 0, 0.1);
    }

    /* ---------------------------------------------------------------------
     * Header
     * --------------------------------------------------------------------- */

    header.exception {
        padding: 18px 20px;

        height: 59px;
        min-height: 59px;

        overflow: hidden;

        background-color: #20202a;
        color: #aaa;
        text-shadow: 0 1px 0 rgba(0, 0, 0, 0.3);
        font-weight: 200;
        box-shadow: inset 0 -5px 3px -3px rgba(0, 0, 0, 0.05), inset 0 -1px 0 rgba(0, 0, 0, 0.05);

        -webkit-text-smoothing: antialiased;
    }

    /* Heading */
    header.exception h2 {
        font-weight: 200;
        font-size: 11pt;
    }

    header.exception h2,
    header.exception p {
        line-height: 1.4em;
        overflow: hidden;
        white-space: pre;
        text-overflow: ellipsis;
    }

    header.exception h2 strong {
        font-weight: 700;
        color: #D95250;
    }

    header.exception p {
        font-weight: 200;
        font-size: 20pt;
        color: white;
    }

    header.exception:hover {
        height: auto;
        z-index: 2;
    }

    header.exception:hover h2,
    header.exception:hover p {
        padding-right: 20px;
        overflow-y: auto;
        word-wrap: break-word;
        white-space: pre-wrap;
        height: auto;
        max-height: 7.5em;
    }

    @media screen and (max-width: 1100px) {
        header.exception {
            height: auto;
        }

        header.exception h2,
        header.exception p {
            padding-right: 20px;
            overflow-y: auto;
            word-wrap: break-word;
            height: auto;
            max-height: 7em;
        }
    }


    /* ---------------------------------------------------------------------
     * Navigation
     * --------------------------------------------------------------------- */

    nav.tabs {
        border-bottom: solid 1px #ddd;

        background-color: #eee;
        text-align: center;

        padding: 6px;

        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.1);
    }

    nav.tabs a {
        display: inline-block;

        height: 22px;
        line-height: 22px;
        padding: 0 10px;

        text-decoration: none;
        font-size: 8pt;
        font-weight: bold;

        color: #999;
        text-shadow: 0 1px 0 rgba(255, 255, 255, 0.6);
    }

    nav.tabs a.selected {
        color: white;
        background: rgba(0, 0, 0, 0.5);
        border-radius: 16px;
        box-shadow: 1px 1px 0 rgba(255, 255, 255, 0.1);
        text-shadow: 0 0 4px rgba(0, 0, 0, 0.4), 0 1px 0 rgba(0, 0, 0, 0.4);
    }

    nav.tabs a.disabled {
        text-decoration: line-through;
        text-shadow: none;
        cursor: default;
    }

    /* ---------------------------------------------------------------------
     * Sidebar
     * --------------------------------------------------------------------- */

    ul.frames {
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    /* Each item */
    ul.frames li {
        background-color: #f8f8f8;
        background: -webkit-linear-gradient(top, #f8f8f8 80%, #f0f0f0);
        background: -moz-linear-gradient(top, #f8f8f8 80%, #f0f0f0);
        background: linear-gradient(top, #f8f8f8 80%, #f0f0f0);
        box-shadow: inset 0 -1px 0 #e2e2e2;
        padding: 7px 20px;

        cursor: pointer;
        overflow: hidden;
    }

    ul.frames .name strong {
      padding-right: 10px;
    }

    ul.frames .name,
    ul.frames .location {
        overflow: hidden;
        height: 1.5em;

        white-space: nowrap;
        word-wrap: none;
        text-overflow: ellipsis;
    }

    ul.frames .method {
        color: #966;
    }

    ul.frames .location {
        font-size: 0.85em;
        font-weight: 400;
        color: #999;
    }

    ul.frames .line {
        font-weight: bold;
    }

    /* Selected frame */
    ul.frames li.selected {
        background: #38a;
        box-shadow: inset 0 1px 0 rgba(0, 0, 0, 0.1), inset 0 2px 0 rgba(255, 255, 255, 0.01), inset 0 -1px 0 rgba(0, 0, 0, 0.1);
    }

    ul.frames li.selected .name,
    ul.frames li.selected .method,
    ul.frames li.selected .location {
        color: white;
        text-shadow: 0 1px 0 rgba(0, 0, 0, 0.2);
    }

    ul.frames li.selected .location {
        opacity: 0.6;
    }

    /* Iconography */
    ul.frames li {
        padding-left: 60px;
        position: relative;
    }

    ul.frames li .icon {
        display: block;
        width: 20px;
        height: 20px;
        line-height: 20px;
        border-radius: 15px;

        text-align: center;

        background: white;
        border: solid 2px #ccc;

        font-size: 9pt;
        font-weight: 200;
        font-style: normal;

        position: absolute;
        top: 14px;
        left: 20px;
    }

    ul.frames .icon.application {
        background: #808090;
        border-color: #555;
    }

    ul.frames .icon.application:before {
        content: 'A';
        color: white;
        text-shadow: 0 0 3px rgba(0, 0, 0, 0.2);
    }

    /* Responsiveness -- flow to single-line mode */
    @media screen and (max-width: 1100px) {
        ul.frames li {
            padding-top: 6px;
            padding-bottom: 6px;
            padding-left: 36px;
            line-height: 1.3;
        }

        ul.frames li .icon {
            width: 11px;
            height: 11px;
            line-height: 11px;

            top: 7px;
            left: 10px;
            font-size: 5pt;
        }

        ul.frames .name,
        ul.frames .location {
            display: inline-block;
            line-height: 1.3;
            height: 1.3em;
        }

        ul.frames .name {
            padding-right: 10px;
        }
    }

    /* ---------------------------------------------------------------------
     * Monospace
     * --------------------------------------------------------------------- */

    pre, code, .repl input, .repl .prompt span, textarea, .code_linenums {
        font-family: menlo, lucida console, monospace;
        font-size: 8pt;
    }

    /* ---------------------------------------------------------------------
     * Display area
     * --------------------------------------------------------------------- */

    .trace_info {
        background: #fff;
        padding: 6px;
        border-radius: 3px;
        margin-bottom: 2px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.03), 1px 1px 0 rgba(0, 0, 0, 0.05), -1px 1px 0 rgba(0, 0, 0, 0.05), 0 0 0 4px rgba(0, 0, 0, 0.04);
    }

    .code_block{
        background: #f1f1f1;
        border-left: 1px solid #ccc;
    }

    /* Titlebar */
    .trace_info .title {
        background: #f1f1f1;

        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.3);
        overflow: hidden;
        padding: 6px 10px;

        border: solid 1px #ccc;
        border-bottom: 0;

        border-top-left-radius: 2px;
        border-top-right-radius: 2px;
    }

    .trace_info .title .name,
    .trace_info .title .location {
        font-size: 9pt;
        line-height: 26px;
        height: 26px;
        overflow: hidden;
    }

    .trace_info .title .location {
        float: left;
        font-weight: bold;
        font-size: 10pt;
    }

    .trace_info .title .location a {
        color:inherit;
        text-decoration:none;
        border-bottom:1px solid #aaaaaa;
    }

    .trace_info .title .location a:hover {
        border-color:#666666;
    }

    .trace_info .title .name {
        float: right;
        font-weight: 200;
    }

    .code, .console, .unavailable {
        background: #fff;
        padding: 5px;

        box-shadow: inset 3px 3px 3px rgba(0, 0, 0, 0.1), inset 0 0 0 1px rgba(0, 0, 0, 0.1);
    }

    .code_linenums{
        background:#f1f1f1;
        padding-top:10px;
        padding-bottom:9px;
        float:left;
    }

    .code_linenums span{
        display:block;
        padding:0 12px;
    }

    .code {
        margin-bottom: -1px;
        border-top-left-radius:2px;
        padding: 10px 0;
        overflow: auto;
    }

    .code pre{
        padding-left:12px;
        min-height:16px;
    }

    /* Source unavailable */
    p.unavailable {
        padding: 20px 0 40px 0;
        text-align: center;
        color: #b99;
        font-weight: bold;
    }

    p.unavailable:before {
        content: '\00d7';
        display: block;

        color: #daa;

        text-align: center;
        font-size: 40pt;
        font-weight: normal;
        margin-bottom: -10px;
    }

    @-webkit-keyframes highlight {
        0%   { background: rgba(220, 30, 30, 0.3); }
        100% { background: rgba(220, 30, 30, 0.1); }
    }
    @-moz-keyframes highlight {
        0%   { background: rgba(220, 30, 30, 0.3); }
        100% { background: rgba(220, 30, 30, 0.1); }
    }
    @keyframes highlight {
        0%   { background: rgba(220, 30, 30, 0.3); }
        100% { background: rgba(220, 30, 30, 0.1); }
    }

    .code .highlight, .code_linenums .highlight {
        background: rgba(220, 30, 30, 0.1);
        -webkit-animation: highlight 400ms linear 1;
        -moz-animation: highlight 400ms linear 1;
        animation: highlight 400ms linear 1;
    }

    /* REPL shell */
    .console {
        padding: 0 1px 10px 1px;
        border-bottom-left-radius: 2px;
        border-bottom-right-radius: 2px;
    }

    .console pre {
        padding: 10px 10px 0 10px;
        max-height: 400px;
        overflow-x: none;
        overflow-y: auto;
        margin-bottom: -3px;
        word-wrap: break-word;
        white-space: pre-wrap;
    }

    /* .prompt > span + input */
    .console .prompt {
        display: table;
        width: 100%;
    }

    .console .prompt span,
    .console .prompt input {
        display: table-cell;
    }

    .console .prompt span {
        width: 1%;
        padding-right: 5px;
        padding-left: 10px;
    }

    .console .prompt input {
        width: 99%;
    }

    /* Input box */
    .console input,
    .console input:focus {
        outline: 0;
        border: 0;
        padding: 0;
        background: transparent;
        margin: 0;
    }

    /* Hint text */
    .hint {
        margin: 15px 0 20px 0;
        font-size: 8pt;
        color: #8080a0;
        padding-left: 20px;
    }

    .hint:before {
        content: '\25b2';
        margin-right: 5px;
        opacity: 0.5;
    }

    /* ---------------------------------------------------------------------
     * Variable infos
     * --------------------------------------------------------------------- */

    .sub {
        padding: 10px 0;
        margin: 10px 0;
    }

    .sub:before {
        content: '';
        display: block;
        width: 100%;
        height: 4px;

        border-radius: 2px;
        background: rgba(0, 150, 200, 0.05);
        box-shadow: 1px 1px 0 rgba(255, 255, 255, 0.7), inset 0 0 0 1px rgba(0, 0, 0, 0.04), inset 2px 2px 2px rgba(0, 0, 0, 0.07);
    }

    .sub h3 {
        color: #39a;
        font-size: 1.1em;
        margin: 10px 0;
        text-shadow: 0 1px 0 rgba(255, 255, 255, 0.6);

        -webkit-font-smoothing: antialiased;
    }

    .sub .inset {
        overflow-y: auto;
    }

    .sub table {
        table-layout: fixed;
    }

    .sub table td {
        border-top: dotted 1px #ddd;
        padding: 7px 1px;
    }

    .sub table td.name {
        width: 150px;

        font-weight: bold;
        font-size: 0.8em;
        padding-right: 20px;

        word-wrap: break-word;
    }

    .sub table td pre {
        max-height: 15em;
        overflow-y: auto;
    }

    .sub table td pre {
        width: 100%;

        word-wrap: break-word;
        white-space: normal;
    }

    /* "(object doesn't support inspect)" */
    .sub .unsupported {
      font-family: sans-serif;
      color: #777;
    }

    /* ---------------------------------------------------------------------
     * Scrollbar
     * --------------------------------------------------------------------- */

    nav.sidebar::-webkit-scrollbar,
    .inset pre::-webkit-scrollbar,
    .console pre::-webkit-scrollbar,
    .code::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }

    .inset pre::-webkit-scrollbar-thumb,
    .console pre::-webkit-scrollbar-thumb,
    .code::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 5px;
    }

    nav.sidebar::-webkit-scrollbar-thumb {
        background: rgba(0, 0, 0, 0.0);
        border-radius: 5px;
    }

    nav.sidebar:hover::-webkit-scrollbar-thumb {
        background-color: #999;
        background: -webkit-linear-gradient(left, #aaa, #999);
    }

    .console pre:hover::-webkit-scrollbar-thumb,
    .inset pre:hover::-webkit-scrollbar-thumb,
    .code:hover::-webkit-scrollbar-thumb {
        background: #888;
    }
    </style>

    <script>
    (function() {
        var elements = ["section", "nav", "header", "footer", "audio"];
        for (var i = 0; i < elements.length; i++) {
            document.createElement(elements[i]);
        }
    })();
    </script>

    <div class='top'>
    <?php for($i = 0; $i < count($exceptions); $i++) { ?>
    <?php $exception = $exceptions[$i]; ?>
        <header class="exception" id="exception_<?= $i ?>">
            <h2><strong><?= $exception["type"] ?></strong> <span> at <?= $exception["path"] ?></span></h2>
            <p><?= $exception["message"] ?></p>
        </header>
    <?php } ?>
    </div>
  <section class="backtrace">
    <nav class="sidebar">
      <ul class="frames">
      <?php for($i = 0; $i < count($frames); $i++) { ?>
      <?php $frame = $frames[$i]; ?>
        <li class="" data-context="" data-index="<?= $i ?>">
          <span class='stroke'></span>
          <i class="icon"></i>
          <div class="info">
            <div class="name">
            <?php if (isset($frame["class_name"])) { ?>
            <strong><?= $frame["class_name"] ?></strong>
            <?php } ?>
            <span class='method'><?= $frame["method_name"] ?></span>
            </div>
            <div class="location">
            <span class="filename"><?= $frame["filename"] ?></span>, line <span class="line"><?= $frame["line_num"] - 1?></span>
            </div>
          </div>
        </li>
      <?php } ?>
        </ul>
    </nav>

    <?php for($i = 0; $i < count($frames); $i++) { ?>
    <?php $frame = $frames[$i]; ?>
    <div class="frame_info" id="frame_info_<?= $i ?>">
      <header class="trace_info clearfix">
          <div class="title">
          <h2 class="name"><?= $frame["method_name"] ?></h2>
              <div class="location"><span class="filename"><a href=""><?= $frame["filename"] ?></a></span></div>
          </div>
          <div class="code_block clearfix">
            <?= formatted_code($frame); ?>
          </div>
      </header>
      
      <div class="sub">
          <h3>Request info</h3>
          <div class='inset variables'>
              <table class="var_table">
                  <?php if(isset($frame["request"])){ ?>
                  <tr><td class="name">Request</td><td><pre><?php print_r($frame["request"]) ?></pre></td></tr>
                  <?php } ?>
                  <?php if(isset($frame["session"])){ ?>
                  <tr><td class="name">Session</td><td><pre><?php print_r($frame["session"]) ?></pre></td></tr>
                  <?php } ?>
              </table>
          </div>
      </div>
      
      <div class="sub">
          <h3>Local Variables</h3>
          <div class='inset variables'>
              <table class="var_table">
                  <?php foreach($frame["local"] as $name => $val) { ?>
                  <tr><td class="name"><?= $name ?></td><td><pre><?php print_r($val) ?></pre></td></tr>
                  <?php } ?>
              </table>
          </div>
      </div>
      
      <div class="sub">
          <h3>Instance Variables</h3>
          <div class="inset variables">
              <table class="var_table">
                  <?php foreach($frame["instance"] as $name => $val) { ?>
                  <tr><td class="name"><?= $name ?></td><td><pre><?php print_r($val) ?></pre></td></tr>
                  <?php } ?>
              </table>
          </div>
      </div>
    </div> <!-- frame_info -->
    <?php } ?>
</section>
</body>
<script>
(function() {
  var previousFrame = null;
  var previousFrameInfo = null;
  var allFrames = document.querySelectorAll("ul.frames li");
  var allFrameInfos = document.querySelectorAll(".frame_info");
  var exceptionInfos = document.querySelectorAll(".exception");

  var selectFrame = function(index, el) {
    if(previousFrame) {
        previousFrame.className = "";
    }
    el.className = "selected";
    previousFrame = el;
    displayFrame(el.attributes["data-index"].value);
  }

  var displayFrame = function(index) {
    var el = allFrameInfos[index];
    var exceptEl = exceptionInfos[index];
    for(var i = 0; i < allFrameInfos.length; i++) {
      allFrameInfos[i].style.display = "none";
      exceptionInfos[i].style.display = "none";
    }

    el.style.display = "block";
    exceptEl.style.display = "block";
  }

  for(var i = 0; i < allFrames.length; i++) {
    (function(i, el) {
      var el = allFrames[i];
      var index = i;
      el.onclick = function() {
        selectFrame(index, el);
      };
    })(i);
  }

  selectFrame(0, allFrames[0]);
})();
</script>
<script src="/lib/better_errors/prism.js">
</script>
</html>
