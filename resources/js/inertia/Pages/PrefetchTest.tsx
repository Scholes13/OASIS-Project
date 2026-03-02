import { Link, Head } from '@inertiajs/react';
import { usePrefetch } from '@/hooks/usePrefetch';

/**
 * PrefetchTest Page
 * 
 * Test page to verify prefetching functionality.
 * Open browser DevTools Network tab to see prefetch requests.
 * 
 * Expected behavior:
 * 1. Hover over a link for 100ms
 * 2. See prefetch request in Network tab
 * 3. Request should have X-Inertia: true header
 * 4. Click the link - should load instantly using cached data
 */
export default function PrefetchTest() {
    const { onMouseEnter, onMouseLeave } = usePrefetch({ delay: 100 });

    return (
        <>
            <Head title="Prefetch Test" />
            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <h1 className="text-2xl font-bold mb-6">Prefetch Test Page</h1>
                            
                            <div className="space-y-4">
                                <div className="p-4 bg-blue-50 rounded-lg">
                                    <h2 className="font-semibold mb-2">Instructions:</h2>
                                    <ol className="list-decimal list-inside space-y-1 text-sm">
                                        <li>Open browser DevTools (F12)</li>
                                        <li>Go to Network tab</li>
                                        <li>Hover over a link below for at least 100ms</li>
                                        <li>You should see a prefetch request</li>
                                        <li>Click the link - it should load instantly</li>
                                    </ol>
                                </div>

                                <div className="space-y-2">
                                    <h2 className="font-semibold">Test Links:</h2>
                                    
                                    <div className="p-4 border rounded-lg hover:bg-gray-50">
                                        <Link 
                                            href="/dashboard"
                                            className="text-primary hover:text-primary font-medium"
                                            onMouseEnter={onMouseEnter}
                                            onMouseLeave={onMouseLeave}
                                        >
                                            Dashboard (with prefetch)
                                        </Link>
                                        <p className="text-sm text-gray-600 mt-1">
                                            Hover for 100ms to trigger prefetch
                                        </p>
                                    </div>

                                    <div className="p-4 border rounded-lg hover:bg-gray-50">
                                        <Link 
                                            href="/purchase-requests"
                                            className="text-primary hover:text-primary font-medium"
                                            onMouseEnter={onMouseEnter}
                                            onMouseLeave={onMouseLeave}
                                        >
                                            Purchase Requests (with prefetch)
                                        </Link>
                                        <p className="text-sm text-gray-600 mt-1">
                                            Hover for 100ms to trigger prefetch
                                        </p>
                                    </div>

                                    <div className="p-4 border rounded-lg hover:bg-gray-50">
                                        <Link 
                                            href="/dashboard"
                                            className="text-gray-600 hover:text-gray-800 font-medium"
                                        >
                                            Dashboard (without prefetch)
                                        </Link>
                                        <p className="text-sm text-gray-600 mt-1">
                                            No prefetch - for comparison
                                        </p>
                                    </div>
                                </div>

                                <div className="p-4 bg-yellow-50 rounded-lg">
                                    <h2 className="font-semibold mb-2">What to look for:</h2>
                                    <ul className="list-disc list-inside space-y-1 text-sm">
                                        <li>Prefetch request appears after 100ms hover</li>
                                        <li>Request has <code className="bg-gray-200 px-1 rounded">X-Inertia: true</code> header</li>
                                        <li>Clicking prefetched link loads instantly</li>
                                        <li>Moving cursor away before 100ms cancels prefetch</li>
                                        <li>Hovering same link again doesn't trigger new request</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
