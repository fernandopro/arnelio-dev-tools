<?php
/**
 * WordPress Test Function Stubs for IntelliSense
 * 
 * Este archivo contiene stubs de las funciones de WordPress Test Suite
 * para mejorar el soporte de IntelliSense en editores como VS Code.
 * 
 * @package DevTools
 * @subpackage Tests
 * @since 3.0.0
 */

if (!function_exists('tests_add_filter')) {
    /**
     * Add a filter hook for tests
     * 
     * @param string $tag The name of the filter to hook the $function_to_add callback to
     * @param callable $function_to_add The callback to be run when the filter is applied
     * @param int $priority Optional. Used to specify the order in which the functions are executed
     * @param int $accepted_args Optional. The number of arguments the function accepts
     * @return true
     */
    function tests_add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        // Stub function for IntelliSense
        return true;
    }
}

if (!function_exists('tests_add_action')) {
    /**
     * Add an action hook for tests
     * 
     * @param string $tag The name of the action to which the $function_to_add is hooked
     * @param callable $function_to_add The name of the function you wish to be called
     * @param int $priority Optional. Used to specify the order in which the functions are executed
     * @param int $accepted_args Optional. The number of arguments the function accepts
     * @return true
     */
    function tests_add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        // Stub function for IntelliSense
        return true;
    }
}

if (!class_exists('WP_UnitTestCase')) {
    /**
     * WordPress Unit Test Case stub for IntelliSense
     */
    class WP_UnitTestCase {
        public function setUp() {}
        public function tearDown() {}
        
        // Basic assertions
        public function assertTrue($condition, $message = '') {}
        public function assertFalse($condition, $message = '') {}
        public function assertEquals($expected, $actual, $message = '') {}
        public function assertNotEquals($expected, $actual, $message = '') {}
        public function assertNull($actual, $message = '') {}
        public function assertNotNull($actual, $message = '') {}
        public function assertEmpty($actual, $message = '') {}
        public function assertNotEmpty($actual, $message = '') {}
        
        // Type assertions
        public function assertIsString($actual, $message = '') {}
        public function assertIsInt($actual, $message = '') {}
        public function assertIsFloat($actual, $message = '') {}
        public function assertIsBool($actual, $message = '') {}
        public function assertIsArray($actual, $message = '') {}
        public function assertIsObject($actual, $message = '') {}
        public function assertIsNumeric($actual, $message = '') {}
        public function assertIsResource($actual, $message = '') {}
        public function assertIsScalar($actual, $message = '') {}
        public function assertIsCallable($actual, $message = '') {}
        public function assertIsIterable($actual, $message = '') {}
        
        // Comparison assertions
        public function assertGreaterThan($expected, $actual, $message = '') {}
        public function assertGreaterThanOrEqual($expected, $actual, $message = '') {}
        public function assertLessThan($expected, $actual, $message = '') {}
        public function assertLessThanOrEqual($expected, $actual, $message = '') {}
        
        // String assertions
        public function assertStringContains($needle, $haystack, $message = '') {}
        public function assertStringNotContains($needle, $haystack, $message = '') {}
        public function assertStringStartsWith($prefix, $string, $message = '') {}
        public function assertStringEndsWith($suffix, $string, $message = '') {}
        public function assertMatchesRegularExpression($pattern, $string, $message = '') {}
        
        // Array assertions
        public function assertArrayHasKey($key, $array, $message = '') {}
        public function assertArrayNotHasKey($key, $array, $message = '') {}
        public function assertContains($needle, $haystack, $message = '') {}
        public function assertNotContains($needle, $haystack, $message = '') {}
        public function assertCount($expectedCount, $haystack, $message = '') {}
        public function assertNotCount($expectedCount, $haystack, $message = '') {}
        
        // File/Directory assertions
        public function assertFileExists($filename, $message = '') {}
        public function assertFileNotExists($filename, $message = '') {}
        public function assertDirectoryExists($directory, $message = '') {}
        public function assertDirectoryNotExists($directory, $message = '') {}
        public function assertFileIsReadable($file, $message = '') {}
        public function assertFileIsWritable($file, $message = '') {}
        
        // Object assertions
        public function assertInstanceOf($expected, $actual, $message = '') {}
        public function assertNotInstanceOf($expected, $actual, $message = '') {}
        public function assertObjectHasAttribute($attributeName, $object, $message = '') {}
        
        // Exception handling
        public function expectException($exception) {}
        public function expectExceptionMessage($message) {}
        public function expectExceptionCode($code) {}
        public function markTestSkipped($message = '') {}
        public function markTestIncomplete($message = '') {}
        
        // WordPress specific
        public function assertWPError($actual, $message = '') {}
        public function assertNotWPError($actual, $message = '') {}
        public function assertEqualSets($expected, $actual, $message = '') {}
        public function assertEqualSetsWithIndex($expected, $actual, $message = '') {}
        public function assertNonEmptyMultidimensionalArray($array, $message = '') {}
    }
}

if (!class_exists('WP_Ajax_UnitTestCase')) {
    /**
     * WordPress AJAX Unit Test Case stub for IntelliSense
     */
    class WP_Ajax_UnitTestCase extends WP_UnitTestCase {
        protected function _handleAjax($action) {}
        protected function _setRole($role) {}
        protected function _last_response_parsed() {}
    }
}
