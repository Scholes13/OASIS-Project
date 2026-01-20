/**
 * Error Test Page
 * 
 * This page is for testing the error logging system.
 * Only accessible in development mode.
 * 
 * Usage: Visit /error-test to test different error scenarios
 */

import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/Card';
import { logError, logWarning, logInfo, logErrorObject, flushErrors } from '@/lib/errorLogger';
import { handleError } from '@/lib/errorHandlers';

export default function ErrorTest() {
    const [errorCount, setErrorCount] = useState(0);

    // Test 1: Simple error log
    const testSimpleError = () => {
        logError('Test error message', {
            level: 'error',
            context: {
                testType: 'simple_error',
                timestamp: Date.now(),
            },
        });
        setErrorCount(prev => prev + 1);
    };

    // Test 2: Error object log
    const testErrorObject = () => {
        const error = new Error('Test Error Object');
        logErrorObject(error, {
            level: 'error',
            context: {
                testType: 'error_object',
            },
        });
        setErrorCount(prev => prev + 1);
    };

    // Test 3: Warning log
    const testWarning = () => {
        logWarning('Test warning message', {
            context: {
                testType: 'warning',
            },
        });
        setErrorCount(prev => prev + 1);
    };

    // Test 4: Info log
    const testInfo = () => {
        logInfo('Test info message', {
            context: {
                testType: 'info',
            },
        });
        setErrorCount(prev => prev + 1);
    };

    // Test 5: Immediate error (not batched)
    const testImmediateError = () => {
        logError('Test immediate error', {
            level: 'error',
            context: {
                testType: 'immediate_error',
            },
            immediate: true,
        });
        setErrorCount(prev => prev + 1);
    };

    // Test 6: Multiple errors (batch test)
    const testBatchErrors = () => {
        for (let i = 0; i < 5; i++) {
            logError(`Batch error ${i + 1}`, {
                level: 'error',
                context: {
                    testType: 'batch_error',
                    batchIndex: i,
                },
            });
        }
        setErrorCount(prev => prev + 5);
    };

    // Test 7: React error (will be caught by ErrorBoundary)
    const testReactError = () => {
        throw new Error('Test React Error - This should be caught by ErrorBoundary');
    };

    // Test 8: Unhandled promise rejection
    const testPromiseRejection = () => {
        Promise.reject(new Error('Test Promise Rejection'));
        setErrorCount(prev => prev + 1);
    };

    // Test 9: Manual error handler
    const testManualError = () => {
        try {
            throw new Error('Test Manual Error');
        } catch (error) {
            handleError(error, 'Manual Error Test');
        }
        setErrorCount(prev => prev + 1);
    };

    // Test 10: Flush queue manually
    const testFlushQueue = () => {
        flushErrors();
        alert('Error queue flushed! Check network tab for batch request.');
    };

    return (
        <div className="p-8 max-w-4xl mx-auto">
            <div className="mb-8">
                <h1 className="text-3xl font-bold text-gray-900 mb-2">
                    Error Logging Test Page
                </h1>
                <p className="text-gray-600">
                    Test different error logging scenarios. Check the browser console and Laravel logs.
                </p>
                <div className="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <p className="text-sm text-blue-800">
                        <strong>Errors logged:</strong> {errorCount}
                    </p>
                    <p className="text-xs text-blue-600 mt-1">
                        Errors are batched and sent every 5 seconds or when 10 errors are queued.
                    </p>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {/* Test 1 */}
                <Card className="p-6">
                    <h3 className="font-semibold text-gray-900 mb-2">1. Simple Error</h3>
                    <p className="text-sm text-gray-600 mb-4">
                        Log a simple error message with context
                    </p>
                    <Button onClick={testSimpleError} variant="destructive">
                        Test Simple Error
                    </Button>
                </Card>

                {/* Test 2 */}
                <Card className="p-6">
                    <h3 className="font-semibold text-gray-900 mb-2">2. Error Object</h3>
                    <p className="text-sm text-gray-600 mb-4">
                        Log an Error object with stack trace
                    </p>
                    <Button onClick={testErrorObject} variant="destructive">
                        Test Error Object
                    </Button>
                </Card>

                {/* Test 3 */}
                <Card className="p-6">
                    <h3 className="font-semibold text-gray-900 mb-2">3. Warning</h3>
                    <p className="text-sm text-gray-600 mb-4">
                        Log a warning message
                    </p>
                    <Button onClick={testWarning} variant="outline">
                        Test Warning
                    </Button>
                </Card>

                {/* Test 4 */}
                <Card className="p-6">
                    <h3 className="font-semibold text-gray-900 mb-2">4. Info</h3>
                    <p className="text-sm text-gray-600 mb-4">
                        Log an info message
                    </p>
                    <Button onClick={testInfo} variant="outline">
                        Test Info
                    </Button>
                </Card>

                {/* Test 5 */}
                <Card className="p-6">
                    <h3 className="font-semibold text-gray-900 mb-2">5. Immediate Error</h3>
                    <p className="text-sm text-gray-600 mb-4">
                        Send error immediately (not batched)
                    </p>
                    <Button onClick={testImmediateError} variant="destructive">
                        Test Immediate Error
                    </Button>
                </Card>

                {/* Test 6 */}
                <Card className="p-6">
                    <h3 className="font-semibold text-gray-900 mb-2">6. Batch Errors</h3>
                    <p className="text-sm text-gray-600 mb-4">
                        Log 5 errors at once (batch test)
                    </p>
                    <Button onClick={testBatchErrors} variant="destructive">
                        Test Batch Errors
                    </Button>
                </Card>

                {/* Test 7 */}
                <Card className="p-6">
                    <h3 className="font-semibold text-gray-900 mb-2">7. React Error</h3>
                    <p className="text-sm text-gray-600 mb-4">
                        Throw error in component (ErrorBoundary)
                    </p>
                    <Button onClick={testReactError} variant="destructive">
                        Test React Error
                    </Button>
                </Card>

                {/* Test 8 */}
                <Card className="p-6">
                    <h3 className="font-semibold text-gray-900 mb-2">8. Promise Rejection</h3>
                    <p className="text-sm text-gray-600 mb-4">
                        Trigger unhandled promise rejection
                    </p>
                    <Button onClick={testPromiseRejection} variant="destructive">
                        Test Promise Rejection
                    </Button>
                </Card>

                {/* Test 9 */}
                <Card className="p-6">
                    <h3 className="font-semibold text-gray-900 mb-2">9. Manual Error</h3>
                    <p className="text-sm text-gray-600 mb-4">
                        Use handleError() in try-catch
                    </p>
                    <Button onClick={testManualError} variant="destructive">
                        Test Manual Error
                    </Button>
                </Card>

                {/* Test 10 */}
                <Card className="p-6">
                    <h3 className="font-semibold text-gray-900 mb-2">10. Flush Queue</h3>
                    <p className="text-sm text-gray-600 mb-4">
                        Manually flush error queue
                    </p>
                    <Button onClick={testFlushQueue}>
                        Flush Queue
                    </Button>
                </Card>
            </div>

            <div className="mt-8 p-6 bg-gray-50 border border-gray-200 rounded-lg">
                <h3 className="font-semibold text-gray-900 mb-2">How to Verify</h3>
                <ol className="list-decimal list-inside space-y-2 text-sm text-gray-700">
                    <li>Open browser DevTools (F12) → Network tab</li>
                    <li>Filter by "error-logs" to see API requests</li>
                    <li>Click test buttons above</li>
                    <li>Wait 5 seconds or trigger 10 errors</li>
                    <li>Check Network tab for POST requests to /api/error-logs/batch</li>
                    <li>Check Laravel logs: <code className="bg-gray-200 px-1 rounded">storage/logs/laravel.log</code></li>
                    <li>Look for entries with "[Frontend]" prefix</li>
                </ol>
            </div>
        </div>
    );
}

