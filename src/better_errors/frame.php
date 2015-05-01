<?php
error_reporting(E_USER_NOTICE);

function getErrorType($errNum) {
  switch ($errNum) {
    case E_ERROR: return "FATAL";
    case E_USER_ERROR: return "ERROR";
    case E_USER_WARNING: return "WARNING";
    case E_USER_NOTICE: return "NOTICE";
    case E_DEPRECATED: return "DEPRECATED";
  }
}

register_shutdown_function("Frame::FatalErrorHandler");
set_error_handler("Frame::FrameErrorHandler");

class Frame {
  static $DEBUG_ERROR_LEVEL = E_USER_WARNING;
  private $frames = array();
  private $exceptions = array();
  static $instance = null;
  static $level = null;

  function __construct() {
  }

  public static function FrameErrorHandler($errNum, $errstr, $errfile, $errline) {
    $errorType = getErrorType($errNum);
    if ($errorType === null) {
      return FALSE;
    }
  
    $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
    if ($errNum <= error_reporting()) {
      Frame::Frame()->except($errorType, $errNum, $errstr, $errline, $errfile, $trace);
      return TRUE;
    }
  }

  public static function FatalErrorHandler() {
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
  
    $errorType = getErrorType($errNum);
    if ($errorType !== null) {
      $trace = debug_backtrace();
      Frame::Frame()->except($errorType, $errNum, $errstr, $errline, $errfile, $trace);
    }

    Frame::Frame()->render();
    return TRUE;
  }

  public static function Frame() {
    if (Frame::$instance === null) {
      Frame::$instance = new Frame();
    }

    return Frame::$instance;
  }

  public function except($errType, $errNum, $message, $lineNum, $fileName, $trace) {
    $this->exceptions[] = array(
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
    $this->exceptions[] = array(
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
      global $GlobalDebuggerFrames, $GlobalDebuggerExceptions;
      $GlobalDebuggerFrames = $this->frames;
      $GlobalDebuggerExceptions = $this->exceptions;
      include("debug_template.php");
      exit;
    }
  }
}
