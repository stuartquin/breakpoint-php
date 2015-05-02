<?php
global $GlobalDebuggerFrames, $GlobalDebuggerExceptions, $GlobalDebuggerPrismCSS, $GlobalDebuggerPrismJS;
$frames = $GlobalDebuggerFrames;
$exceptions = $GlobalDebuggerExceptions;

function formatted_lines($frame) {
  $linesBack = 10;
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

function get_expandable($class, $fields, $type, $id){
  $maxKeys = 4;
  $keys = array_keys($fields);

  $output = "<a href='#' class='expandable-object' data-class='$class'";
  $output .=" data-type='$type' data-info='{$type}_{$id}'>";
  $output .= $class." ";
  $output .= "<span class='expandable-values'>".implode(", ", array_slice($keys, 0, $maxKeys));

  if (count($keys) > $maxKeys) {
    $output .= " &hellip;";
  }

  $output.="</span></a>";

  $output .= "<div class='var-table var-info' id='{$type}_{$id}'>";
  $output .= output_embedded_map($fields);
  $output .= "</div>";

  return $output;
}

function get_flattened($class, $fields) {
  $maxKeys = 4;
  $keys = array_keys($fields);
  $output = $class." ";
  $output .= "<span class='expandable-values'>".implode(", ", array_slice($keys, 0, $maxKeys));

  if (count($keys) > $maxKeys) {
    $output .= " &hellip;";
  }
  $output.="</span>";

  return $output;
}



function get_formatted($val, $id, $type, $expand=TRUE) {
  $output = $val;

  if (is_object($val)) {
    $fields = get_object_vars($val);
    $class = get_class($val);
    if ($expand) {
      $output = get_expandable($class, $fields, $type, $id);
    } else {
      $output = get_flattened($class, $fields);
    }
    return array("obj", $output);
  }

  if (is_array($val)) {
    $class = get_class($val);
    if ($expand) {
      $output = get_expandable("Array", $val, $type, $id);
    } else {
      $output = get_flattened("Array", $val);
    }
    return array("array", $output);
  }

  if (is_null($val)) {
    return array("null", "null");
  }

  if (is_numeric($val)) {
    return array("num", $output);
  }

  if (is_string($val)) {
    return array("str", $output);
  }

  return array("default", $output);
}

function get_formatted_row($key, $format) {
  $output .= "<tr>";
  $output .= "<td class='name'>$key</td>";
  $output .= "<td class='value-{$format[0]}'>{$format[1]}</td>";
  $output .= "</tr>";
  return $output;
}

function output_map($map, $type, $frame) {
  $output = "<table class='var_table'>";
  $id = 0;
  foreach($map as $key => $val) {
    $format = get_formatted($val, $frame."_".$id, $type);
    $output .= get_formatted_row($key, $format);
    $id++;
  }
  return $output."</table>";
}

function output_embedded_map($map) {
  $output = "<table class='var_table'>";
  $id = 0;
  foreach($map as $key => $val) {
    $format = get_formatted($val, $frame."_".$id, $type, FALSE);
    $output .= get_formatted_row($key, $format);
    $id++;
  }
  return $output."</table>";
}


function formatted_code($frame) {
  return formatted_lines($frame);
}

ob_clean();
header("Content-Type:text/html");
?>

<!DOCTYPE html>
<html>
<head>
<title><?= $exceptions[0]["message"] ?></title>
</head>
<body>
    <style>
      <?= $GlobalDebuggerCSS ?>

      <?= $GlobalDebuggerPrismCSS ?>
    </style>

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
      <?php $frame = $frames[$i]; 
            $exception = $exceptions[$i];?>
        <li class="" data-context="" data-index="<?= $i ?>">
          <span class='stroke'></span>
          <i class="icon icon-<?=$exception["type"]?>"></i>
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
      <h3 class="sub-title" data-sub="request-<?=$i?>">Request info</h3>
          <div class='inset variables' id="vars-request-<?=$i?>">
            <?= output_map($frame["request"], "request", $i); ?>
          </div>
      </div>
      <div class="sub">
          <h3 class="sub-title" data-sub="local-<?=$i?>">Local Variables</h3>
          <div class='inset variables' id="vars-local-<?=$i?>">
            <?= output_map($frame["local"], "local", $i); ?>
          </div>
      </div>
      <div class="sub">
          <h3 class="sub-title" data-sub="instance-<?=$i?>">Instance Variables</h3>
          <div class="inset variables" id="vars-instance-<?=$i?>">
            <?= output_map($frame["instance"], "instance", $i); ?>
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
  var expandable = document.querySelectorAll(".expandable-object");
  var subTitles = document.querySelectorAll(".sub-title");

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

  var toggleDisplay = function(el) {
    if (el.style.display == "block") {
      el.style.display = "none";
    } else {
      el.style.display = "block";
    }
  };

  for(var i = 0; i < subTitles.length; i++) {
    (function(i, el) {
      var el = subTitles[i];
      var index = i;
      el.onclick = function() {
        var pane = document.getElementById("vars-"+this.dataset.sub);
        toggleDisplay(pane);
      };
    })(i);
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

  for(var i = 0; i < expandable.length; i++) {
    (function(i, el) {
      var el = expandable[i];
      var index = i;
      el.onclick = function() {
        toggleDisplay(document.getElementById(this.dataset.info));
      };
    })(i);
  }

  selectFrame(0, allFrames[0]);
})();
</script>
<script type="text/javascript">
<?= $GlobalDebuggerPrismJS ?>
</script>
</html>
