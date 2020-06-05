<?php

namespace LightSource\Log;

/**
 * Class BASE
 * @package LightSource\Log
 */
abstract class BASE {


	//////// static methods


	/**
	 * @param string $level
	 * @param string $message
	 * @param array $info
	 * @param array $logObjectInfo
	 * @param bool|null $isShortClass If null value get from config.
	 * @param int|null $backTraceLimit 0 = disable
	 *
	 * @return void
	 */
	final protected static function _SLog( $level, $message, $info = [], $logObjectInfo = [], $isShortClass = null, $backTraceLimit = null ) {

		// +1 to hide self

		$backTraceLimit = ! is_null( $backTraceLimit ) ?
			$backTraceLimit :
			LOG::$BackTraceLimit + 1;

		// increment backTraceSpliceLength to hide this function in trace
		LOG::Write( $level, $message, $info, $logObjectInfo, $isShortClass, $backTraceLimit, $backTraceLimit );

	}


	//////// methods


	/**
	 * @param string $level
	 * @param string $message
	 * @param array $info
	 * @param bool|null $isShortClass
	 * @param int|null $backTraceLimit 0 = disable
	 *
	 * @return void
	 */
	final protected function _log( $level, $message, $info = [], $isShortClass = null, $backTraceLimit = null ) {

		// +1 to hide self

		$backTraceLimit = ! is_null( $backTraceLimit ) ?
			$backTraceLimit :
			LOG::$BackTraceLimit + 1;

		// increment backTraceSpliceLength to hide this function in trace
		LOG::Write( $level, $message, $info, $this->_getLogObjectInfo(), $isShortClass, $backTraceLimit, $backTraceLimit );

	}

	/**
	 * @return array
	 */
	protected function _getLogObjectInfo() {
		return [];
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return rtrim( print_r( $this->_getLogObjectInfo(), true ), "\n" );
	}

}