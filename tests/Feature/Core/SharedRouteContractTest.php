<?php

namespace Tests\Feature\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SharedRouteContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_route_contract_maps_to_the_expected_controller_methods(): void
    {
        $this->assertRouteAction(
            'stock-requests.show',
            'App\\Http\\Controllers\\Modules\\Purchasing\\StockRequest\\StockRequestController@showInertia'
        );

        $this->assertRouteAction(
            'stock-requests.store',
            'App\\Http\\Controllers\\Modules\\Purchasing\\StockRequest\\StockRequestController@store'
        );

        $this->assertRouteAction(
            'stock-requests.edit',
            'App\\Http\\Controllers\\Modules\\Purchasing\\StockRequest\\StockRequestController@editInertia'
        );

        $this->assertRouteAction(
            'stock-requests.update',
            'App\\Http\\Controllers\\Modules\\Purchasing\\StockRequest\\StockRequestController@update'
        );

        $this->assertRouteAction(
            'stock-requests.resend-approval-email',
            'App\\Http\\Controllers\\Modules\\Purchasing\\StockRequest\\StockRequestController@resendApprovalEmail'
        );

        $this->assertRouteAction(
            'stock-requests.offline-approval-document',
            'App\\Http\\Controllers\\Modules\\Purchasing\\StockRequest\\StockRequestController@offlineApprovalDocument'
        );

        $this->assertRouteAction(
            'sales-crm.activities.store',
            'App\\Http\\Controllers\\SalesCrmController@activitiesStore'
        );

        $this->assertRouteAction(
            'sales-crm.activities.update',
            'App\\Http\\Controllers\\SalesCrmController@activitiesUpdate'
        );

        $this->assertRouteAction(
            'sales-crm.activities.destroy',
            'App\\Http\\Controllers\\SalesCrmController@activitiesDestroy'
        );

        $this->assertRouteAction(
            'sales-crm.contacts.store',
            'App\\Http\\Controllers\\SalesCrmController@contactsStore'
        );

        $this->assertRouteAction(
            'sales-crm.contacts.update',
            'App\\Http\\Controllers\\SalesCrmController@contactsUpdate'
        );

        $this->assertRouteAction(
            'sales-crm.contacts.destroy',
            'App\\Http\\Controllers\\SalesCrmController@contactsDestroy'
        );

        $this->assertRouteAction(
            'activity.backdate.approve',
            'App\\Http\\Controllers\\Modules\\Activity\\ActivityInertiaController@approveBackdate'
        );

        $this->assertRouteAction(
            'activity.backdate.reject',
            'App\\Http\\Controllers\\Modules\\Activity\\ActivityInertiaController@rejectBackdate'
        );
    }

    protected function assertRouteAction(string $routeName, string $expectedAction): void
    {
        $route = Route::getRoutes()->getByName($routeName);

        $this->assertNotNull($route, sprintf('Route [%s] should be registered.', $routeName));
        $this->assertSame($expectedAction, $route->getActionName());
    }
}
