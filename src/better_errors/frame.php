<?php
// error handler function
function FrameErrorHandler($errNum, $errstr, $errfile, $errline) {
  $errorType = null;
  // print("<pre>");
  // var_dump("NUM: ".$errNum);
  // var_dump($errstr);
  // var_dump($errfile);
  // var_dump($errline);
  // var_dump(E_USER_ERROR);
  // var_dump(E_USER_WARNING);
  // var_dump(E_USER_NOTICE);
  // print("</pre>");


  switch ($errNum) {
    case E_USER_ERROR: $errorType = "ERROR"; break;
    case E_USER_WARNING: $errorType = "WARNING"; break;
    case E_USER_NOTICE: $errorType = "NOTICE"; break;
  }

  if ($errorType === null) {
    return FALSE;
  }

  $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
  Frame::Frame()->except($errorType, $errNum, $errstr, $errline, $errfile, $trace);
  return TRUE;
}


function FatalErrorHandler() {
  $errfile = "unknown file";
  $errstr = "shutdown";
  $errNum = E_CORE_ERROR;
  $errline = 0;

  $error = error_get_last();

  if( $error !== NULL) {
    $errNum  = $error["type"];
    $errfile = $error["file"];
    $errline = $error["line"];
    $errstr  = $error["message"];
  }
  $trace = debug_backtrace();

  Frame::Frame()->except("FATAL", $errNum, $errstr, $errline, $errfile, $trace);
  Frame::Frame()->render();
  return TRUE;
}

// register_shutdown_function("FatalErrorHandler");
set_error_handler("FrameErrorHandler");

class Frame {
  private $frames = array();
  private $exceptions = array();

  static $instance = null;

  function __construct() {
  }

  public static function Frame() {
    if (Frame::$instance === null) {
      Frame::$instance = new Frame();
    }

    return Frame::$instance;
  }

  public function except($errType, $errNum, $message, $lineNum, $fileName, $trace) {
    $this->exceptions = array(
      "type" => $errType ." #".$errNum,
      "message" => $message,
      "path" => $_SERVER["REQUEST_URI"]
    );

    if ($lineNum ) {
      // print("<pre>");
      // var_dump($message);
      // var_dump($trace);
      // print("</pre>");
    }

    $trace = $trace[min(count($trace) - 1, 2)];
    $this->createFrame($message, $lineNum, $fileName, $trace);
  }

  public function createFrame($message, $lineNum, $fileName, $trace) {
    $frame = array();
    $frame["filename"] = $fileName;
    $frame["message"] = $message;
    $frame["lines"] = file($fileName);
    $frame["line_num"] = $lineNum;

    $frame["request"] = $_REQUEST;
    if (isset($trace["local"])) {
      $frame["local"] = $trace["local"];
    } else {
      $frame["local"] = array();
    }
    unset($frame["local"]["this"]);

    if (isset($trace["object"])) {
      $frame["instance"] = get_object_vars($trace["object"]);
      $frame["class_name"] = $trace["class"];
    } else {
      $frame["instance"] = array();
      $frame["class_name"] = null;
    }

    if (isset($trace["function"])) {
      $frame["method_name"] = $trace["function"];
    } else {
      $frame["method_name"] = "";
    }

    $this->frames[] = $frame;
  }

  public function inspect($local) {
    $this->exceptions = array(
      "type" => "Inspect",
      "message" => "Halted for debugging",
      "path" => $_SERVER["REQUEST_URI"]
    );

    $debugTrace = debug_backtrace();
    $trace = $debugTrace[min(count($debugTrace) - 1, 2)];
    $trace["local"] = $local;
    $this->createFrame("Frame", $debugTrace[0]["line"], $debugTrace[0]["file"], $trace);
  }

  public function render() {
    if (count($this->frames) > 0) {
      global $GlobalDebuggerFrames, $GlobalDebuggerException;
      $GlobalDebuggerFrames = $this->frames;
      $GlobalDebuggerException = $this->exceptions;
      include("debug_template.php");
      exit;
    }
  }
}
