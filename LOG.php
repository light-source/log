<?php

namespace LightSource\Log;

use LightSource\DateTime\DATE_TIME;

use Exception;

/**
 * Class LOG
 * @package LightSource\Log
 */
abstract class LOG {


	//////// comments


	/*
	 * info : correct works in multiple threads environment :
	 * this means lock files && wait threads, this slows down common speed,
	 * but it's not doesn't matter much for DEBUG, for LIVE set min level to MAIN,
	 * this save from slows down && warranty correct work for multiple threads
	 */


	//////// constants


	/*
	 * live (1) - absolute full live logs - all actions, to restore lifecycle
	 * debug (3) - main debug logs : for main info with helps to find error in server or client
	 * main (3) - for standard main msg : start/stop...
	 * warning (4) - for minor errors OR causing questions messages
	 * broken (5) - for major errors OR not right logic behavior
	 * critical (6) - for global stop work messages : abort/interruption/db problem...
	 */
	const LIVE = 'level_live';
	const DEBUG = 'level_debug';
	const MAIN = 'level_main';
	const WARNING = 'level_warning';
	const BROKEN = 'level_broken';
	const CRITICAL = 'level_critical';

	const _WEIGHT = [
		self::LIVE     => 1,
		self::DEBUG    => 2,
		self::MAIN     => 3,
		self::WARNING  => 4,
		self::BROKEN   => 5,
		self::CRITICAL => 6,
	];


	//////// static fields


	/**
	 * @var string
	 */
	public static $PathToLogDir = '';
	/**
	 * @var string
	 */
	public static $FileName = 'log';
	/**
	 * @var string
	 */
	public static $FileExtension = 'html';
	/**
	 * @var int
	 */
	public static $FileMaxSize = 409600;
	/**
	 * @var int
	 */
	public static $FileMaxCountCopies = 5;
	/**
	 * @var int
	 */
	public static $MinLevelWeight = 1;
	/**
	 * @var bool
	 */
	public static $IsShortLogClass = true;
	/**
	 * @var int
	 */
	public static $NotRewritableLevel = 4;
	/**
	 * @var callable|null
	 */
	public static $NotificationCallback = null;
	/**
	 * @var int
	 */
	public static $NotificationMinLevel = 4;
	/**
	 * @var int
	 */
	public static $BackTraceLimit = 3;
	/**
	 * @var int
	 */
	public static $BackTraceSplice = 3; // _BackTrace() + _PrepareLog() + Write()
	/**
	 * @var int
	 */
	public static $RandomStringLength = 8;


	//////// static methods


	/**
	 * @param string $level
	 *
	 * @return bool
	 */
	final private static function _IsAvailableLevel( $level ) {
		return self::_WEIGHT[ $level ] >= self::$MinLevelWeight;
	}

	/**
	 * @param string $level
	 *
	 * @return string
	 */
	final private static function _GetPathToLevelFile( $level ) {
		return self::$PathToLogDir . DIRECTORY_SEPARATOR . $level;
	}

	/**
	 * @return string
	 */
	final private static function _RandomString() {

		$symbols = [];

		$symbols = array_merge( $symbols, range( 'a', 'z' ) );
		$symbols = array_merge( $symbols, range( 'A', 'Z' ) );
		$symbols = array_merge( $symbols, range( '0', '9' ) );

		$lastIndex = count( $symbols ) - 1;

		$password = '';
		try {
			for ( $j = 0; $j < self::$RandomStringLength; $j ++ ) {
				$index    = random_int( 0, $lastIndex );
				$password .= $symbols[ $index ];
			}
		} catch ( Exception $e ) {
			$password = '';
		}

		return $password;

	}

	/**
	 * @param string $className
	 *
	 * @return string
	 */
	final private static function _ClassNameWithoutNamespace( $className ) {

		$fromClass = explode( '\\', $className );
		$lastIndx  = count( $fromClass ) - 1;

		return $fromClass[ $lastIndx ];
	}

	/**
	 * @return string
	 */
	final private static function _GetPathToCopyFile() {
		return implode( '', [
			self::$PathToLogDir,
			DIRECTORY_SEPARATOR . self::$FileName,
			'_' . DATE_TIME::ToTimestamp(),
			'_' . self::_RandomString(),
			'.' . self::$FileExtension,
		] );
	}

	/**
	 * @return int
	 */
	final private static function _GetCountLogFiles() {

		$countLogFiles = 0;

		// array or FALSE
		$fileNames = scandir( self::$PathToLogDir );

		if ( ! $fileNames ) {
			return $countLogFiles;
		}

		$fileNames = array_diff( $fileNames, [ '.', '..' ] );

		foreach ( $fileNames as $fileName ) {

			$fileExtension = pathinfo( $fileName, PATHINFO_EXTENSION );

			if ( self::$FileExtension !== $fileExtension ) {
				continue;
			}

			$countLogFiles ++;

		}

		return $countLogFiles;
	}

	/**
	 * Clear log file && clear levels IF file size is will reach limit
	 * ( with creating copy before (if have major levels) )
	 *
	 * @param resource $logFileHandle Must be have cursor at START
	 *
	 * @return void FileHandle cursor always set at END
	 */
	final private static function _Rewrite( $logFileHandle ) {

		// we get file length without filesize function to prevent caching (see filesize function info)
		// set cursor to end

		fseek( $logFileHandle, 0, SEEK_END );

		// get file size (its a current position) - or FALSE, but its equals to 0, so does not need check

		$logFileSize = ftell( $logFileHandle );

		// rewrite does not need

		if ( $logFileSize < self::$FileMaxSize ) {
			return;
		}

		$isRequireSave = false;

		// remove all current levels files && check is current log have major levels

		foreach ( self::_WEIGHT as $level => $levelWeight ) {

			$levelFile = self::_GetPathToLevelFile( $level );

			if ( ! is_file( $levelFile ) ) {
				continue;
			}

			unlink( $levelFile );

			if ( ! $isRequireSave &&
			     $levelWeight >= self::$NotRewritableLevel ) {
				$isRequireSave = true;
			}

		}

		// if current log have major levels
		// && count log copies smaller then limit in config

		if ( $isRequireSave &&
		     self::_GetCountLogFiles() < self::$FileMaxCountCopies
		) {

			// put cursor at start

			rewind( $logFileHandle );

			// read all file && put cursor at end

			$saveString = fread( $logFileHandle, $logFileSize );

			// create copy file

			if ( $saveString ) {
				file_put_contents( self::_GetPathToCopyFile(), $saveString, LOCK_EX );
			}

		}

		// clear file (cursor does not changed)

		ftruncate( $logFileHandle, 0 );

		// put cursor to end (because file is cleared && old cursor is wrong)

		fseek( $logFileHandle, 0, SEEK_END );

		// clear file cache (in end of all rewrite actions, so only if rewrite is done, not needle if rewrite does not need)
		// (is_file for removed levels can cached, and other file func...)

		clearstatcache();
	}

	/**
	 * @param bool $isShortClass
	 * @param int $limit
	 * @param int $spliceLength
	 *
	 * @return array [ [class, type, function ] | [file, line], ]
	 */
	final private static function _BackTrace( $isShortClass, $limit, $spliceLength ) {

		// add splice length to limit

		$limit += $spliceLength;

		$backTraceLines = [];
		$backTrace      = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, $limit );

		// Count last not informed call's : delete end (self) functions

		if ( $spliceLength &&
		     count( $backTrace ) > $spliceLength ) {
			array_splice( $backTrace, 0, $spliceLength );
		}

		foreach ( $backTrace as $debugInfo ) {

			$backTraceLine = [];

			$class = key_exists( 'class', $debugInfo ) ? $debugInfo['class'] : '';
			$class = ( ! $isShortClass || ! $class ) ?
				$class :
				self::_ClassNameWithoutNamespace( $class );

			if ( $class ) {
				$backTraceLine[] = $class;
			}

			if ( key_exists( 'type', $debugInfo ) ) {
				$backTraceLine[] = $debugInfo['type'];
			}

			if ( key_exists( 'function', $debugInfo ) ) {
				$backTraceLine[] = $debugInfo['function'];
			}

			if ( ! $backTraceLine ) {

				if ( key_exists( 'file', $debugInfo ) ) {
					$backTraceLine[] = $debugInfo['file'];
				}

				if ( key_exists( 'line', $debugInfo ) ) {
					$backTraceLine[] = $debugInfo['line'];
				}

			}

			$backTraceLines[] = $backTraceLine;

		}

		return $backTraceLines;
	}

	/**
	 * @param string $level
	 * @param string $message
	 * @param array $info
	 * @param array $object
	 * @param bool $isShortClass
	 * @param int $backTraceLimit
	 * @param int $backTraceSpliceLength
	 *
	 * @return string
	 */
	final private static function _PrepareLog( $level, $message, $info, $object, $isShortClass, $backTraceLimit, $backTraceSpliceLength ) {

		$logLines = [];

		$backTraceInfo = self::_BackTrace( $isShortClass, $backTraceLimit, $backTraceSpliceLength );

		// if detected : class name OR filename

		$endSource = ( $backTraceInfo &&
		               $backTraceInfo[0] ) ?
			$backTraceInfo[0][0] :
			'';

		$logLines[] = implode( ' : ', [ $level, $endSource, $message, ] );

		// convert backTraceInfo sub-arrays to strings

		for ( $i = 0; $i < count( $backTraceInfo ); $i ++ ) {
			$backTraceInfo[ $i ] = implode( '', $backTraceInfo[ $i ] );
		}

		// implode

		$logLines[] = implode( ' ; ', $backTraceInfo );

		if ( $info ) {
			$printRString = print_r( $info, true );
			$printRString = rtrim( $printRString, "\n" );
			$logLines[]   = "info : <!-- {$printRString} -->";
		}

		if ( $object ) {
			$printRString = print_r( $object, true );
			$printRString = rtrim( $printRString, "\n" );
			$logLines[]   = "object : <!-- {$printRString} -->";
		}

		$logLines[] = DATE_TIME::ToString();

		$logString = implode( "\n", $logLines ) . "\n\n";

		return $logString;
	}

	/**
	 * @param string $level
	 * @param string $message
	 * @param array $info
	 * @param array $object
	 * @param bool|null $isShortClass If null value get from config.
	 * @param int $backTraceLimit 0 = disable
	 * @param int $backTraceSpliceLength Count last not informed call's : to delete end (self) functions
	 *
	 * @return void
	 */
	final public static function Write( $level, $message, $info = [], $object = [], $isShortClass = null, $backTraceLimit = null, $backTraceSpliceLength = null ) {

		$backTraceLimit        = ! is_null( $backTraceLimit ) ?
			$backTraceLimit :
			self::$BackTraceLimit;
		$backTraceSpliceLength = ! is_null( $backTraceSpliceLength ) ?
			$backTraceSpliceLength :
			self::$BackTraceSplice;

		if ( ! key_exists( $level, self::_WEIGHT ) ||
		     ! self::_IsAvailableLevel( $level ) ) {
			return;
		}

		$isShortClass = is_null( $isShortClass ) ?
			self::$IsShortLogClass :
			$isShortClass;

		// prepare log line

		$logString = self::_PrepareLog( $level, $message, $info, $object, $isShortClass, $backTraceLimit, $backTraceSpliceLength );

		// open for read/write (created if not exist), not reduced, put cursor at START

		$pathToFile    = implode( DIRECTORY_SEPARATOR, [
			self::$PathToLogDir,
			self::$FileName,
			self::$FileExtension,
		] );
		$logFileHandle = fopen( $pathToFile, 'c+' );

		// something wrong

		if ( false === $logFileHandle ) {
			return;
		}

		// try lock file with exclusive rights, thread is stopped until receipt

		if ( ! flock( $logFileHandle, LOCK_EX ) ) {

			fclose( $logFileHandle );

			return;
		}

		// checks for rewrite AND always set cursor at END

		self::_Rewrite( $logFileHandle );

		// write log line

		fwrite( $logFileHandle, $logString );

		// clear output before unlock

		fflush( $logFileHandle );

		// create level file (if not exist)

		$levelFile = self::_GetPathToLevelFile( $level );

		if ( ! is_file( $levelFile ) ) {
			file_put_contents( $levelFile, '', LOCK_EX );
		}

		// unlock

		flock( $logFileHandle, LOCK_UN );

		// close file

		fclose( $logFileHandle );

		// notify

		if ( self::_WEIGHT[ $level ] >= self::$NotificationMinLevel && is_callable( self::$NotificationCallback ) ) {
			call_user_func_array( self::$NotificationCallback, [
				'level' => $level,
			] );
		}

	}

}